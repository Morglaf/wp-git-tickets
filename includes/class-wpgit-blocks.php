<?php
/**
 * Enregistrement des blocs Gutenberg dynamiques.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPGIT_Blocks {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'localize_editor_data' ) );
	}

	public static function register_blocks() {
		$asset_version = WPGIT_VERSION . '.' . (string) filemtime( WPGIT_PLUGIN_DIR . 'assets/blocks.js' );

		wp_register_script(
			'wpgit-blocks-editor',
			WPGIT_PLUGIN_URL . 'assets/blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render', 'wp-data' ),
			$asset_version,
			true
		);

		wp_register_style(
			'wpgit-blocks',
			WPGIT_PLUGIN_URL . 'assets/blocks.css',
			array(),
			WPGIT_VERSION . '.' . (string) filemtime( WPGIT_PLUGIN_DIR . 'assets/blocks.css' )
		);

		wp_register_script(
			'wpgit-blocks-front',
			WPGIT_PLUGIN_URL . 'assets/blocks-front.js',
			array(),
			WPGIT_VERSION . '.' . (string) filemtime( WPGIT_PLUGIN_DIR . 'assets/blocks-front.js' ),
			true
		);

		$blocks = array(
			'repo-feed'   => 'render_repo_feed',
			'org-feed'    => 'render_org_feed',
			'repo-readme' => 'render_repo_readme',
		);

		foreach ( $blocks as $block => $callback ) {
			register_block_type(
				WPGIT_PLUGIN_DIR . 'blocks/' . $block,
				array(
					'render_callback' => array( __CLASS__, $callback ),
				)
			);
		}
	}

	/**
	 * Expose la liste des champs ACF/SCF à l'éditeur.
	 */
	public static function localize_editor_data() {
		wp_localize_script(
			'wpgit-blocks-editor',
			'wpgitBlocks',
			array(
				'acfFields' => self::get_acf_field_options(),
				'hasAcf'    => function_exists( 'acf_get_field_groups' ) || function_exists( 'get_field' ),
			)
		);
	}

	/**
	 * Options SelectControl pour les champs ACF/SCF (text/url/link…).
	 *
	 * @return array<int, array{label:string,value:string}>
	 */
	public static function get_acf_field_options() {
		$options = array(
			array(
				'label' => __( '— Choisir un champ —', 'wp-git-tickets' ),
				'value' => '',
			),
		);

		if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_get_fields' ) ) {
			return $options;
		}

		$allowed_types = array( 'text', 'textarea', 'url', 'link', 'email', 'select', 'radio' );
		$groups        = acf_get_field_groups();

		if ( ! is_array( $groups ) ) {
			return $options;
		}

		$seen = array();

		foreach ( $groups as $group ) {
			$fields = acf_get_fields( $group );
			if ( ! is_array( $fields ) ) {
				continue;
			}

			$group_title = $group['title'] ?? '';

			foreach ( $fields as $field ) {
				self::collect_acf_field_options( $field, $options, $seen, $allowed_types, $group_title );
			}
		}

		return $options;
	}

	/**
	 * @param array  $field
	 * @param array  $options
	 * @param array  $seen
	 * @param array  $allowed_types
	 * @param string $group_title
	 * @param string $prefix
	 */
	private static function collect_acf_field_options( $field, &$options, &$seen, $allowed_types, $group_title, $prefix = '' ) {
		if ( empty( $field['name'] ) ) {
			return;
		}

		$type = $field['type'] ?? '';
		$name = $field['name'];
		$key  = $prefix . $name;

		if ( in_array( $type, array( 'group', 'repeater', 'flexible_content' ), true ) && ! empty( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
			foreach ( $field['sub_fields'] as $sub ) {
				self::collect_acf_field_options( $sub, $options, $seen, $allowed_types, $group_title, $key . '_' );
			}
			return;
		}

		if ( ! in_array( $type, $allowed_types, true ) ) {
			return;
		}

		if ( isset( $seen[ $name ] ) ) {
			return;
		}
		$seen[ $name ] = true;

		$label = $field['label'] ?? $name;
		if ( $group_title ) {
			$label = $group_title . ' — ' . $label;
		}
		$label .= ' (' . $name . ')';

		$options[] = array(
			'label' => $label,
			'value' => $name,
		);
	}

	/**
	 * @param WP_Error|string $error
	 * @return string
	 */
	private static function error_html( $error ) {
		$message = is_wp_error( $error ) ? $error->get_error_message() : (string) $error;
		$message = WPGIT_Markdown::clean_text( $message );
		return '<div class="wpgit-error">' . esc_html( $message ) . '</div>';
	}

	/**
	 * Attributs HTML du wrapper (classe + largeur).
	 *
	 * @param array  $attrs
	 * @param string $class
	 * @return string
	 */
	private static function wrapper_attrs( $attrs, $class ) {
		$extra = array( 'class' => $class );
		$width = trim( (string) ( $attrs['width'] ?? '' ) );

		if ( $width !== '' && preg_match( '/^(\d{1,4})(px|%|rem|em)$/', $width ) ) {
			// % : remplir le parent sans déborder (min-width:0 + box-sizing).
			// px/rem/em : largeur fixe plafonnée à 100%.
			if ( substr( $width, -1 ) === '%' ) {
				$extra['style'] = 'width:' . $width . ';max-width:100%;min-width:0;box-sizing:border-box;';
			} else {
				$extra['style'] = 'width:' . $width . ';max-width:100%;box-sizing:border-box;';
			}
		}

		// get_block_wrapper_attributes peut échouer hors contexte de bloc : fallback manuel.
		if ( function_exists( 'get_block_wrapper_attributes' ) ) {
			try {
				return get_block_wrapper_attributes( $extra );
			} catch ( Throwable $e ) {
				// continue fallback
			}
		}

		$html = 'class="' . esc_attr( $class ) . '"';
		if ( ! empty( $extra['style'] ) ) {
			$html .= ' style="' . esc_attr( $extra['style'] ) . '"';
		}
		return $html;
	}

	/**
	 * @param string $iso
	 * @return string
	 */
	private static function format_date( $iso ) {
		if ( ! $iso ) {
			return '';
		}
		$ts = strtotime( $iso );
		if ( ! $ts ) {
			return esc_html( $iso );
		}
		return esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts ) );
	}

	/**
	 * @param array  $items
	 * @param string $class
	 * @param bool   $show_repo
	 * @return string
	 */
	private static function render_feed_list( $items, $class = 'wpgit-feed', $show_repo = false ) {
		if ( empty( $items ) ) {
			return '<div class="' . esc_attr( $class ) . '"><p class="wpgit-empty">Aucune activité.</p></div>';
		}

		$html = '<ul class="' . esc_attr( $class ) . '">';
		foreach ( $items as $item ) {
			$title  = WPGIT_Markdown::clean_text( $item['title'] ?? '' );
			$author = WPGIT_Markdown::clean_text( $item['author'] ?? '' );
			$date   = self::format_date( $item['date'] ?? '' );
			$url    = $item['url'] ?? '';
			$repo   = WPGIT_Markdown::clean_text( $item['repo'] ?? '' );
			$type   = $item['type'] ?? '';

			$html .= '<li class="wpgit-feed__item" data-type="' . esc_attr( $type ) . '">';
			$html .= '<div class="wpgit-feed__title">';
			if ( $url ) {
				$html .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $title ) . '</a>';
			} else {
				$html .= esc_html( $title );
			}
			$html .= '</div>';
			$html .= '<div class="wpgit-feed__meta">';
			if ( $show_repo && $repo ) {
				$html .= '<span class="wpgit-feed__repo">' . esc_html( $repo ) . '</span>';
			}
			if ( $author ) {
				$html .= '<span class="wpgit-feed__author">' . esc_html( $author ) . '</span>';
			}
			if ( $date ) {
				$html .= '<time class="wpgit-feed__date" datetime="' . esc_attr( $item['date'] ?? '' ) . '">' . $date . '</time>';
			}
			$html .= '</div></li>';
		}
		$html .= '</ul>';
		return $html;
	}

	/**
	 * @param array $attrs
	 * @return string
	 */
	public static function render_repo_feed( $attrs ) {
		try {
			$attrs = wp_parse_args(
				$attrs,
				array(
					'sourceType' => 'url',
					'repoUrl'    => '',
					'acfField'   => '',
					'feedType'   => 'commits',
					'limit'      => 10,
					'width'      => '',
				)
			);

			$url = WPGIT_GitHub::resolve_repo_url_from_attrs( $attrs );
			if ( is_wp_error( $url ) ) {
				return self::error_html( $url );
			}

			$parsed = WPGIT_GitHub::parse_repo_url( $url );
			if ( is_wp_error( $parsed ) ) {
				return self::error_html( $parsed );
			}

			$limit = (int) ( $attrs['limit'] ?? 10 );
			if ( ( $attrs['feedType'] ?? 'commits' ) === 'events' ) {
				$items = WPGIT_GitHub::get_repo_events( $parsed['owner'], $parsed['repo'], $limit );
			} else {
				$items = WPGIT_GitHub::get_repo_commits( $parsed['owner'], $parsed['repo'], $limit );
			}

			if ( is_wp_error( $items ) ) {
				return self::error_html( $items );
			}

			return '<div ' . self::wrapper_attrs( $attrs, 'wpgit-repo-feed' ) . '>' . self::render_feed_list( $items, 'wpgit-feed' ) . '</div>';
		} catch ( Throwable $e ) {
			return self::error_html( 'Erreur lors du rendu du flux.' );
		}
	}

	/**
	 * @param array $attrs
	 * @return string
	 */
	public static function render_org_feed( $attrs ) {
		try {
			$attrs = wp_parse_args(
				$attrs,
				array(
					'org'      => '',
					'feedType' => 'events',
					'limit'    => 10,
					'width'    => '',
				)
			);

			$settings = WPGIT_GitHub::get_settings();
			$org      = trim( (string) ( $attrs['org'] ?? '' ) );
			if ( $org === '' ) {
				$org = $settings['org'];
			}
			if ( $org === '' ) {
				return self::error_html( 'Organisation non configurée.' );
			}

			$limit = (int) ( $attrs['limit'] ?? 10 );
			if ( ( $attrs['feedType'] ?? 'events' ) === 'commits' ) {
				$items = WPGIT_GitHub::get_org_commits( $org, $limit );
			} else {
				$items = WPGIT_GitHub::get_org_events( $org, $limit );
			}

			if ( is_wp_error( $items ) ) {
				return self::error_html( $items );
			}

			return '<div ' . self::wrapper_attrs( $attrs, 'wpgit-org-feed' ) . '>' . self::render_feed_list( $items, 'wpgit-feed', true ) . '</div>';
		} catch ( Throwable $e ) {
			return self::error_html( 'Erreur lors du rendu du flux organisation.' );
		}
	}

	/**
	 * @param array $attrs
	 * @return string
	 */
	public static function render_repo_readme( $attrs ) {
		try {
			$attrs = wp_parse_args(
				$attrs,
				array(
					'sourceType'    => 'url',
					'repoUrl'       => '',
					'acfField'      => '',
					'branch'        => 'main',
					'defaultLang'   => '',
					'showTabs'      => true,
					'width'           => '',
					'hideHeader'      => false,
					'hideSelectors'   => '',
					'defaultTabLabel' => '',
				)
			);

			$url = WPGIT_GitHub::resolve_repo_url_from_attrs( $attrs );
			if ( is_wp_error( $url ) ) {
				return self::error_html( $url );
			}

			$parsed = WPGIT_GitHub::parse_repo_url( $url );
			if ( is_wp_error( $parsed ) ) {
				return self::error_html( $parsed );
			}

			$branch  = trim( (string) ( $attrs['branch'] ?? 'main' ) ) ?: 'main';
			$readmes = WPGIT_GitHub::get_readmes( $parsed['owner'], $parsed['repo'], $branch );
			if ( is_wp_error( $readmes ) ) {
				return self::error_html( $readmes );
			}

			$default_lang = strtolower( trim( (string) ( $attrs['defaultLang'] ?? '' ) ) );
			$show_tabs    = ! empty( $attrs['showTabs'] ) && count( $readmes ) > 1;

			$active_index = 0;
			if ( $default_lang !== '' ) {
				foreach ( $readmes as $i => $rm ) {
					if ( ( $rm['lang'] ?? '' ) === $default_lang ) {
						$active_index = $i;
						break;
					}
				}
			}

			$uid  = 'wpgit-readme-' . uniqid( '', false );
			$html = '<div ' . self::wrapper_attrs( $attrs, 'wpgit-repo-readme' ) . ' data-wpgit-readme="' . esc_attr( $uid ) . '">';

			$default_tab_label = trim( (string) ( $attrs['defaultTabLabel'] ?? '' ) );

			if ( $show_tabs ) {
				$html .= '<div class="wpgit-readme-tabs" role="tablist">';
				foreach ( $readmes as $i => $rm ) {
					$label = $rm['label'] ?? 'README';
					if ( ( $rm['lang'] ?? '' ) === '' && $default_tab_label !== '' ) {
						$label = $default_tab_label;
					}
					$selected = ( $i === $active_index ) ? 'true' : 'false';
					$active   = ( $i === $active_index ) ? ' is-active' : '';
					$html    .= '<button type="button" class="wpgit-readme-tab' . $active . '" role="tab" aria-selected="' . $selected . '" data-tab="' . esc_attr( (string) $i ) . '" aria-controls="' . esc_attr( $uid . '-panel-' . $i ) . '">';
					$html    .= esc_html( $label );
					$html    .= '</button>';
				}
				$html .= '</div>';
			}

			$md_context = array(
				'owner'         => $parsed['owner'],
				'repo'          => $parsed['repo'],
				'branch'        => $readmes[0]['branch'] ?? $branch,
				'hideHeader'    => ! empty( $attrs['hideHeader'] ),
				'hideSelectors' => (string) ( $attrs['hideSelectors'] ?? '' ),
			);

			foreach ( $readmes as $i => $rm ) {
				$hidden = ( $i !== $active_index ) ? ' hidden' : '';
				$ctx    = $md_context;
				if ( ! empty( $rm['branch'] ) ) {
					$ctx['branch'] = $rm['branch'];
				}
				$html .= '<div class="wpgit-readme-panel" id="' . esc_attr( $uid . '-panel-' . $i ) . '" role="tabpanel"' . $hidden . '>';
				$html .= '<div class="wpgit-readme-content">' . WPGIT_Markdown::to_html( $rm['content'] ?? '', $ctx ) . '</div>';
				$html .= '</div>';
			}

			$html .= '</div>';
			return WPGIT_Markdown::clean_text( $html );
		} catch ( Throwable $e ) {
			return self::error_html( 'Erreur lors du rendu du README.' );
		}
	}
}
