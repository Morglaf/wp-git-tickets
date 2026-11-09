<?php
/**
 * Client API GitHub + cache transients.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPGIT_GitHub {

	const FEED_CACHE_TTL   = 15 * MINUTE_IN_SECONDS;
	const README_CACHE_TTL = 5 * MINUTE_IN_SECONDS;
	const SHA_CACHE_TTL    = MINUTE_IN_SECONDS;
	const REPOS_CACHE_TTL  = 6 * HOUR_IN_SECONDS;

	/**
	 * @return array{token:string,org:string,debug_mode:int}
	 */
	public static function get_settings() {
		$options = get_option( 'wpgit_settings', array() );
		return array(
			'token'      => $options['github_token'] ?? '',
			'org'        => $options['github_org'] ?? '',
			'debug_mode' => (int) ( $options['debug_mode'] ?? 0 ),
		);
	}

	/**
	 * Parse une URL GitHub en owner/repo.
	 *
	 * @param string $url
	 * @return array{owner:string,repo:string}|WP_Error
	 */
	public static function parse_repo_url( $url ) {
		$url = trim( (string) $url );
		if ( $url === '' ) {
			return new WP_Error( 'empty_url', 'URL du dépôt manquante.' );
		}

		// Accepte owner/repo ou URL complète.
		if ( preg_match( '#^([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+)/?$#', $url, $m ) ) {
			return array(
				'owner' => $m[1],
				'repo'  => preg_replace( '/\.git$/', '', $m[2] ),
			);
		}

		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( ! $path ) {
			return new WP_Error( 'invalid_url', 'URL GitHub invalide.' );
		}

		$parts = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
		if ( count( $parts ) < 2 ) {
			return new WP_Error( 'invalid_url', 'URL GitHub invalide (owner/repo attendu).' );
		}

		return array(
			'owner' => $parts[0],
			'repo'  => preg_replace( '/\.git$/', '', $parts[1] ),
		);
	}

	/**
	 * Résout l'URL depuis les attributs de bloc (url manuelle ou champ ACF/SCF).
	 *
	 * @param array $attrs
	 * @return string|WP_Error
	 */
	public static function resolve_repo_url_from_attrs( $attrs ) {
		$source = $attrs['sourceType'] ?? 'url';

		if ( $source === 'acf' ) {
			$field = trim( (string) ( $attrs['acfField'] ?? '' ) );
			if ( $field === '' ) {
				return new WP_Error( 'missing_acf', 'Nom du champ ACF/SCF manquant.' );
			}

			$value = '';
			if ( function_exists( 'get_field' ) ) {
				$value = get_field( $field );
			}
			if ( empty( $value ) ) {
				$value = get_post_meta( get_the_ID(), $field, true );
			}

			$value = is_array( $value ) ? ( $value[0] ?? '' ) : $value;
			$value = trim( (string) $value );

			if ( $value === '' ) {
				return new WP_Error( 'empty_acf', 'Le champ ACF/SCF est vide.' );
			}

			return $value;
		}

		$url = trim( (string) ( $attrs['repoUrl'] ?? '' ) );
		if ( $url === '' ) {
			return new WP_Error( 'missing_url', 'URL du dépôt manquante.' );
		}

		return $url;
	}

	/**
	 * Requête GET GitHub authentifiée.
	 *
	 * @param string $endpoint Chemin relatif (ex. /repos/o/r/commits) ou URL absolue.
	 * @param array  $args     Args wp_remote_get supplémentaires.
	 * @return array|WP_Error  Corps JSON décodé.
	 */
	public static function request( $endpoint, $args = array() ) {
		$settings = self::get_settings();
		$token    = $settings['token'];

		$url = ( 0 === strpos( $endpoint, 'http' ) )
			? $endpoint
			: 'https://api.github.com' . $endpoint;

		$headers = array(
			'Accept'     => 'application/vnd.github.v3+json',
			'User-Agent' => 'WP-Git-Plugin',
		);
		if ( $token ) {
			$headers['Authorization'] = 'token ' . $token;
		}

		$response = wp_remote_get(
			$url,
			array_merge(
				array(
					'headers' => $headers,
					'timeout' => 20,
				),
				$args
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 403 ) {
			return new WP_Error( 'rate_limit', 'Limite de taux GitHub atteinte. Réessayez plus tard.' );
		}
		if ( $code === 404 ) {
			return new WP_Error( 'not_found', 'Ressource GitHub introuvable.' );
		}
		if ( $code < 200 || $code >= 300 ) {
			$message = is_array( $body ) && ! empty( $body['message'] ) ? $body['message'] : "Erreur GitHub ($code)";
			return new WP_Error( 'gh_error', $message );
		}

		return $body;
	}

	/**
	 * Noms des dépôts non archivés de l'organisation configurée.
	 *
	 * @param string|null $org
	 * @return array|WP_Error
	 */
	public static function get_org_repo_names( $org = null ) {
		$settings = self::get_settings();
		$org      = $org ?: $settings['org'];
		$token    = $settings['token'];

		if ( ! $token || ! $org ) {
			return new WP_Error( 'missing_config', 'Configuration incomplète.' );
		}

		$cache_key = 'wpgit_all_repos_cache';
		if ( $org === $settings['org'] ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$body = self::request( '/orgs/' . rawurlencode( $org ) . '/repos?per_page=100&sort=name' );
		if ( is_wp_error( $body ) ) {
			// Fallback user repos si ce n'est pas une org.
			$body = self::request( '/users/' . rawurlencode( $org ) . '/repos?per_page=100&sort=name' );
			if ( is_wp_error( $body ) ) {
				return $body;
			}
		}

		$list = array();
		foreach ( (array) $body as $repo ) {
			if ( is_array( $repo ) && empty( $repo['archived'] ) && ! empty( $repo['name'] ) ) {
				$list[] = $repo['name'];
			}
		}

		if ( $org === $settings['org'] ) {
			set_transient( $cache_key, $list, self::REPOS_CACHE_TTL );
		}

		return $list;
	}

	/**
	 * Repos autorisés (cases cochées dans l'admin — option wpgit_blacklist).
	 *
	 * @return array
	 */
	public static function get_allowed_repos() {
		$all = self::get_org_repo_names();
		if ( is_wp_error( $all ) ) {
			return array();
		}
		$allowed = get_option( 'wpgit_blacklist', array() );
		if ( ! is_array( $allowed ) ) {
			$allowed = array();
		}
		return array_values( array_intersect( $all, $allowed ) );
	}

	/**
	 * Commits d'un dépôt.
	 *
	 * @param string $owner
	 * @param string $repo
	 * @param int    $limit
	 * @return array|WP_Error
	 */
	public static function get_repo_commits( $owner, $repo, $limit = 10 ) {
		$limit     = max( 1, min( 100, (int) $limit ) );
		$cache_key = 'wpgit_commits_' . md5( "$owner/$repo/$limit" );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$body = self::request(
			'/repos/' . rawurlencode( $owner ) . '/' . rawurlencode( $repo ) . '/commits?per_page=' . $limit
		);
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$items = array();
		foreach ( (array) $body as $commit ) {
			if ( ! is_array( $commit ) ) {
				continue;
			}
			$message = $commit['commit']['message'] ?? '';
			$message = strtok( $message, "\n" );
			$items[] = array(
				'type'    => 'commit',
				'title'   => $message,
				'author'  => $commit['commit']['author']['name']
					?? ( $commit['author']['login'] ?? '' ),
				'date'    => $commit['commit']['author']['date'] ?? '',
				'url'     => $commit['html_url'] ?? '',
				'repo'    => $repo,
				'sha'     => substr( $commit['sha'] ?? '', 0, 7 ),
			);
		}

		set_transient( $cache_key, $items, self::FEED_CACHE_TTL );
		return $items;
	}

	/**
	 * Events d'un dépôt.
	 *
	 * @param string $owner
	 * @param string $repo
	 * @param int    $limit
	 * @return array|WP_Error
	 */
	public static function get_repo_events( $owner, $repo, $limit = 10 ) {
		$limit     = max( 1, min( 100, (int) $limit ) );
		$cache_key = 'wpgit_repo_events_' . md5( "$owner/$repo/$limit" );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$body = self::request(
			'/repos/' . rawurlencode( $owner ) . '/' . rawurlencode( $repo ) . '/events?per_page=' . $limit
		);
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$items = self::normalize_events( (array) $body, $limit );
		set_transient( $cache_key, $items, self::FEED_CACHE_TTL );
		return $items;
	}

	/**
	 * Events d'une organisation.
	 *
	 * @param string $org
	 * @param int    $limit
	 * @return array|WP_Error
	 */
	public static function get_org_events( $org, $limit = 10 ) {
		$limit     = max( 1, min( 100, (int) $limit ) );
		$cache_key = 'wpgit_org_events_' . md5( "$org/$limit" );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$body = self::request( '/orgs/' . rawurlencode( $org ) . '/events?per_page=' . $limit );
		if ( is_wp_error( $body ) ) {
			$body = self::request( '/users/' . rawurlencode( $org ) . '/events/public?per_page=' . $limit );
			if ( is_wp_error( $body ) ) {
				return $body;
			}
		}

		$items = self::normalize_events( (array) $body, $limit );
		set_transient( $cache_key, $items, self::FEED_CACHE_TTL );
		return $items;
	}

	/**
	 * Commits fusionnés de tous les dépôts autorisés de l'org.
	 *
	 * @param string $org
	 * @param int    $limit
	 * @return array|WP_Error
	 */
	public static function get_org_commits( $org, $limit = 10 ) {
		$limit     = max( 1, min( 50, (int) $limit ) );
		$cache_key = 'wpgit_org_commits_' . md5( "$org/$limit" );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$repos = self::get_allowed_repos();
		if ( empty( $repos ) ) {
			$all = self::get_org_repo_names( $org );
			if ( is_wp_error( $all ) ) {
				return $all;
			}
			$repos = $all;
		}

		// Limite le nombre de repos interrogés pour éviter le rate limit.
		$repos  = array_slice( $repos, 0, 20 );
		$merged = array();

		foreach ( $repos as $repo_name ) {
			$commits = self::get_repo_commits( $org, $repo_name, min( 5, $limit ) );
			if ( is_wp_error( $commits ) ) {
				continue;
			}
			$merged = array_merge( $merged, $commits );
		}

		usort(
			$merged,
			function ( $a, $b ) {
				return strcmp( $b['date'] ?? '', $a['date'] ?? '' );
			}
		);

		$merged = array_slice( $merged, 0, $limit );
		set_transient( $cache_key, $merged, self::FEED_CACHE_TTL );
		return $merged;
	}

	/**
	 * Normalise les events GitHub en items de flux.
	 *
	 * @param array $events
	 * @param int   $limit
	 * @return array
	 */
	private static function normalize_events( $events, $limit ) {
		$items = array();
		foreach ( $events as $event ) {
			if ( ! is_array( $event ) || count( $items ) >= $limit ) {
				break;
			}
			$type = $event['type'] ?? '';
			$repo = isset( $event['repo']['name'] ) ? basename( $event['repo']['name'] ) : '';
			$payload = $event['payload'] ?? array();
			$title   = self::event_title( $type, $payload, $repo );
			$url     = self::event_url( $type, $payload, $event );

			$items[] = array(
				'type'   => $type,
				'title'  => $title,
				'author' => $event['actor']['login'] ?? '',
				'date'   => $event['created_at'] ?? '',
				'url'    => $url,
				'repo'   => $repo,
			);
		}
		return $items;
	}

	/**
	 * @param string $type
	 * @param array  $payload
	 * @param string $repo
	 * @return string
	 */
	private static function event_title( $type, $payload, $repo ) {
		switch ( $type ) {
			case 'PushEvent':
				$count = isset( $payload['commits'] ) ? count( $payload['commits'] ) : (int) ( $payload['size'] ?? 0 );
				$ref   = isset( $payload['ref'] ) ? str_replace( 'refs/heads/', '', $payload['ref'] ) : '';
				return sprintf( 'Push de %d commit(s) sur %s%s', $count, $repo, $ref ? " ($ref)" : '' );
			case 'IssuesEvent':
				$action = $payload['action'] ?? '';
				$num    = $payload['issue']['number'] ?? '';
				$issue  = $payload['issue']['title'] ?? '';
				return trim( "Issue #$num $action : $issue" );
			case 'PullRequestEvent':
				$action = $payload['action'] ?? '';
				$num    = $payload['pull_request']['number'] ?? '';
				$pr     = $payload['pull_request']['title'] ?? '';
				return trim( "PR #$num $action : $pr" );
			case 'ReleaseEvent':
				$tag = $payload['release']['tag_name'] ?? '';
				return "Release $tag sur $repo";
			case 'CreateEvent':
				$ref_type = $payload['ref_type'] ?? 'ref';
				$ref      = $payload['ref'] ?? '';
				return "Création $ref_type $ref";
			case 'WatchEvent':
				return "Star sur $repo";
			case 'ForkEvent':
				return "Fork de $repo";
			default:
				return $type . ( $repo ? " — $repo" : '' );
		}
	}

	/**
	 * @param string $type
	 * @param array  $payload
	 * @param array  $event
	 * @return string
	 */
	private static function event_url( $type, $payload, $event ) {
		switch ( $type ) {
			case 'IssuesEvent':
				return $payload['issue']['html_url'] ?? '';
			case 'PullRequestEvent':
				return $payload['pull_request']['html_url'] ?? '';
			case 'ReleaseEvent':
				return $payload['release']['html_url'] ?? '';
			case 'PushEvent':
				if ( ! empty( $payload['commits'][0]['url'] ) ) {
					return str_replace( 'api.github.com/repos', 'github.com', $payload['commits'][0]['url'] );
				}
				break;
		}
		$repo = $event['repo']['name'] ?? '';
		return $repo ? 'https://github.com/' . $repo : '';
	}

	/**
	 * SHA du dernier commit d'une branche (cache court pour invalider les README).
	 *
	 * @param string $owner
	 * @param string $repo
	 * @param string $branch
	 * @return string
	 */
	public static function get_branch_sha( $owner, $repo, $branch ) {
		$cache_key = 'wpgit_sha_' . md5( "$owner/$repo/$branch" );
		$cached    = get_transient( $cache_key );
		if ( is_string( $cached ) && $cached !== '' ) {
			return $cached;
		}

		$body = self::request(
			'/repos/' . rawurlencode( $owner ) . '/' . rawurlencode( $repo ) . '/commits/' . rawurlencode( $branch ) . '?per_page=1'
		);

		$sha = '';
		if ( ! is_wp_error( $body ) && ! empty( $body['sha'] ) ) {
			$sha = (string) $body['sha'];
			set_transient( $cache_key, $sha, self::SHA_CACHE_TTL );
		}

		return $sha;
	}

	/**
	 * Liste les fichiers README*.md à la racine.
	 *
	 * @param string $owner
	 * @param string $repo
	 * @param string $branch
	 * @return array|WP_Error  Liste de {file, lang, content}
	 */
	public static function get_readmes( $owner, $repo, $branch = 'main' ) {
		$branch = $branch ?: 'main';
		$sha    = self::get_branch_sha( $owner, $repo, $branch );
		$cache_key = 'wpgit_readmes_v3_' . md5( "$owner/$repo/$branch/$sha" );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$contents = self::request(
			'/repos/' . rawurlencode( $owner ) . '/' . rawurlencode( $repo ) . '/contents/?ref=' . rawurlencode( $branch )
		);

		if ( is_wp_error( $contents ) && $branch === 'main' ) {
			$branch = 'master';
			$sha    = self::get_branch_sha( $owner, $repo, $branch );
			$cache_key = 'wpgit_readmes_v3_' . md5( "$owner/$repo/$branch/$sha" );
			$cached    = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
			$contents = self::request(
				'/repos/' . rawurlencode( $owner ) . '/' . rawurlencode( $repo ) . '/contents/?ref=' . rawurlencode( $branch )
			);
		}

		if ( is_wp_error( $contents ) ) {
			return $contents;
		}

		$readme_files = array();
		foreach ( (array) $contents as $item ) {
			if ( ! is_array( $item ) || ( $item['type'] ?? '' ) !== 'file' ) {
				continue;
			}
			$name = $item['name'] ?? '';
			if ( preg_match( '/^README(?:\.([A-Za-z0-9_-]+))?\.md$/i', $name, $m ) ) {
				$lang = isset( $m[1] ) ? strtolower( $m[1] ) : '';
				$readme_files[] = array(
					'file' => $name,
					'lang' => $lang,
					'path' => $item['path'] ?? $name,
					'sha'  => $item['sha'] ?? '',
				);
			}
		}

		if ( empty( $readme_files ) ) {
			return new WP_Error( 'no_readme', 'Aucun README.md trouvé à la racine du dépôt.' );
		}

		// Tri : README.md (sans langue) en premier, puis alphabétique.
		usort(
			$readme_files,
			function ( $a, $b ) {
				if ( $a['lang'] === '' && $b['lang'] !== '' ) {
					return -1;
				}
				if ( $b['lang'] === '' && $a['lang'] !== '' ) {
					return 1;
				}
				return strcmp( $a['lang'], $b['lang'] );
			}
		);

		$result = array();
		foreach ( $readme_files as $rf ) {
			// Préfère raw.githubusercontent (contenu à jour, pas de truncation API).
			$raw_url = 'https://raw.githubusercontent.com/' . rawurlencode( $owner ) . '/' . rawurlencode( $repo ) . '/' . rawurlencode( $branch ) . '/' . str_replace( '%2F', '/', rawurlencode( $rf['path'] ) );
			if ( ! empty( $rf['sha'] ) ) {
				$raw_url = add_query_arg( 'wpgit_sha', substr( $rf['sha'], 0, 12 ), $raw_url );
			}

			$raw_headers = array(
				'User-Agent' => 'WP-Git-Plugin',
				'Accept'     => 'text/plain',
			);
			$token = self::get_settings()['token'];
			if ( $token ) {
				$raw_headers['Authorization'] = 'token ' . $token;
			}

			$raw = wp_remote_get(
				$raw_url,
				array(
					'timeout' => 20,
					'headers' => $raw_headers,
				)
			);

			$content = '';
			if ( ! is_wp_error( $raw ) && wp_remote_retrieve_response_code( $raw ) === 200 ) {
				$content = WPGIT_Markdown::clean_text( wp_remote_retrieve_body( $raw ) );
			} else {
				// Fallback API contents.
				$file_data = self::request(
					'/repos/' . rawurlencode( $owner ) . '/' . rawurlencode( $repo ) . '/contents/' . rawurlencode( $rf['path'] ) . '?ref=' . rawurlencode( $branch )
				);
				if ( ! is_wp_error( $file_data ) && ! empty( $file_data['content'] ) && ( $file_data['encoding'] ?? '' ) === 'base64' ) {
					$decoded = base64_decode( str_replace( "\n", '', $file_data['content'] ), true );
					if ( false !== $decoded ) {
						$content = WPGIT_Markdown::clean_text( $decoded );
					}
				}
			}

			if ( $content === '' ) {
				continue;
			}

			$result[] = array(
				'file'    => $rf['file'],
				'lang'    => $rf['lang'],
				'label'   => $rf['lang'] !== '' ? strtoupper( $rf['lang'] ) : 'README',
				'content' => $content,
				'branch'  => $branch,
				'sha'     => $rf['sha'],
			);
		}

		if ( empty( $result ) ) {
			return new WP_Error( 'no_readme', 'Impossible de lire le(s) README.' );
		}

		set_transient( $cache_key, $result, self::README_CACHE_TTL );
		return $result;
	}
}
