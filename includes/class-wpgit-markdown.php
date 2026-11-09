<?php
/**
 * Rendu Markdown → HTML sécurisé via Parsedown.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPGIT_Markdown {

	/**
	 * Nettoie une chaîne pour un HTML/JSON WordPress sûr.
	 *
	 * @param string $text
	 * @return string
	 */
	public static function clean_text( $text ) {
		$text = (string) $text;
		$text = str_replace( "\0", '', $text );

		if ( function_exists( 'mb_convert_encoding' ) ) {
			$text = mb_convert_encoding( $text, 'UTF-8', 'UTF-8' );
		}

		$text = wp_check_invalid_utf8( $text, true );

		return $text;
	}

	/**
	 * Tags HTML autorisés pour un README (Markdown + HTML inline GitHub).
	 *
	 * @return array
	 */
	public static function allowed_html() {
		$allowed = wp_kses_allowed_html( 'post' );

		$style_align = array(
			'class' => true,
			'id'    => true,
			'align' => true,
			'style' => true,
		);

		$extra = array(
			'details' => array(
				'open'  => true,
				'class' => true,
				'id'    => true,
				'style' => true,
			),
			'summary' => array(
				'class' => true,
				'id'    => true,
				'style' => true,
			),
			'div'     => $style_align,
			'span'    => $style_align,
			'img'     => array(
				'src'     => true,
				'alt'     => true,
				'title'   => true,
				'width'   => true,
				'height'  => true,
				'class'   => true,
				'id'      => true,
				'align'   => true,
				'style'   => true,
				'loading' => true,
				'srcset'  => true,
			),
			'table'   => array(
				'class'       => true,
				'id'          => true,
				'align'       => true,
				'style'       => true,
				'border'      => true,
				'cellpadding' => true,
				'cellspacing' => true,
				'width'       => true,
			),
			'thead'   => array( 'class' => true, 'style' => true ),
			'tbody'   => array( 'class' => true, 'style' => true ),
			'tfoot'   => array( 'class' => true, 'style' => true ),
			'tr'      => array( 'class' => true, 'align' => true, 'style' => true ),
			'th'      => array(
				'class'   => true,
				'align'   => true,
				'style'   => true,
				'colspan' => true,
				'rowspan' => true,
				'scope'   => true,
				'width'   => true,
			),
			'td'      => array(
				'class'   => true,
				'align'   => true,
				'style'   => true,
				'colspan' => true,
				'rowspan' => true,
				'width'   => true,
			),
			'h1'      => $style_align,
			'h2'      => $style_align,
			'h3'      => $style_align,
			'h4'      => $style_align,
			'h5'      => $style_align,
			'h6'      => $style_align,
			'p'       => $style_align,
			'a'       => array(
				'href'   => true,
				'title'  => true,
				'rel'    => true,
				'target' => true,
				'class'  => true,
				'id'     => true,
				'name'   => true,
				'style'  => true,
			),
			'code'    => array( 'class' => true, 'style' => true ),
			'pre'     => array( 'class' => true, 'style' => true ),
			'ul'      => array( 'class' => true, 'style' => true ),
			'ol'      => array( 'class' => true, 'start' => true, 'style' => true ),
			'li'      => array( 'class' => true, 'style' => true ),
			'br'      => array(),
			'hr'      => array(),
			'sup'     => array( 'style' => true ),
			'sub'     => array( 'style' => true ),
			'kbd'     => array(),
			'samp'    => array(),
			'var'     => array(),
			'mark'    => array(),
			'abbr'    => array( 'title' => true ),
			'strong'  => array( 'style' => true ),
			'em'      => array( 'style' => true ),
			'b'       => array( 'style' => true ),
			'i'       => array( 'style' => true ),
		);

		foreach ( $extra as $tag => $attrs ) {
			if ( ! isset( $allowed[ $tag ] ) ) {
				$allowed[ $tag ] = array();
			}
			$allowed[ $tag ] = array_merge( $allowed[ $tag ], $attrs );
		}

		return $allowed;
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	public static function is_relative_url( $url ) {
		$url = trim( (string) $url );
		if ( $url === '' || $url[0] === '#' ) {
			return false;
		}
		if ( preg_match( '#^(?:[a-z][a-z0-9+.-]*:|//)#i', $url ) ) {
			return false;
		}
		return true;
	}

	/**
	 * @param string $path
	 * @param array  $context
	 * @param string $kind image|link
	 * @return string
	 */
	public static function resolve_repo_url( $path, $context, $kind = 'image' ) {
		$owner  = $context['owner'] ?? '';
		$repo   = $context['repo'] ?? '';
		$branch = $context['branch'] ?? 'main';
		$base   = isset( $context['base_path'] ) ? trim( (string) $context['base_path'], '/' ) : '';

		if ( ! $owner || ! $repo ) {
			return $path;
		}

		$path = str_replace( '\\', '/', trim( (string) $path ) );
		// Ignore query/hash for path resolution, preserve them after.
		$hash  = '';
		$query = '';
		if ( strpos( $path, '#' ) !== false ) {
			list( $path, $hash ) = explode( '#', $path, 2 );
			$hash = '#' . $hash;
		}
		if ( strpos( $path, '?' ) !== false ) {
			list( $path, $query ) = explode( '?', $path, 2 );
			$query = '?' . $query;
		}

		$path = preg_replace( '#^\./#', '', $path );

		$parts = array();
		if ( $base !== '' ) {
			$parts = array_values( array_filter( explode( '/', $base ), 'strlen' ) );
		}
		foreach ( explode( '/', $path ) as $segment ) {
			if ( $segment === '' || $segment === '.' ) {
				continue;
			}
			if ( $segment === '..' ) {
				array_pop( $parts );
				continue;
			}
			$parts[] = rawurldecode( $segment );
		}

		$encoded = implode(
			'/',
			array_map( 'rawurlencode', $parts )
		);

		if ( $kind === 'image' || preg_match( '/\.(png|jpe?g|gif|webp|svg|bmp|ico)$/i', $encoded ) ) {
			return 'https://raw.githubusercontent.com/' . rawurlencode( $owner ) . '/' . rawurlencode( $repo ) . '/' . rawurlencode( $branch ) . '/' . $encoded . $query . $hash;
		}

		return 'https://github.com/' . rawurlencode( $owner ) . '/' . rawurlencode( $repo ) . '/blob/' . rawurlencode( $branch ) . '/' . $encoded . $query . $hash;
	}

	/**
	 * Réécrit les URLs relatives dans le Markdown source (avant parse).
	 *
	 * @param string $markdown
	 * @param array  $context
	 * @return string
	 */
	public static function rewrite_markdown_urls( $markdown, $context ) {
		if ( empty( $context['owner'] ) || empty( $context['repo'] ) ) {
			return $markdown;
		}

		$replace_url = function ( $url, $kind ) use ( $context ) {
			$url = html_entity_decode( trim( $url ), ENT_QUOTES, 'UTF-8' );
			if ( ! self::is_relative_url( $url ) ) {
				return $url;
			}
			return self::resolve_repo_url( $url, $context, $kind );
		};

		// src="..." / src='...'
		$markdown = preg_replace_callback(
			'#(\bsrc\s*=\s*)(["\'])([^"\']+)\2#i',
			function ( $m ) use ( $replace_url ) {
				return $m[1] . $m[2] . $replace_url( $m[3], 'image' ) . $m[2];
			},
			$markdown
		);

		// Markdown images/links : ![alt](url) or [text](url)
		$markdown = preg_replace_callback(
			'#(!?\[[^\]]*\]\()([^\s\)]+)(\))#',
			function ( $m ) use ( $replace_url ) {
				$kind = ( isset( $m[0][0] ) && $m[0][0] === '!' ) ? 'image' : 'link';
				return $m[1] . $replace_url( $m[2], $kind ) . $m[3];
			},
			$markdown
		);

		return $markdown;
	}

	/**
	 * Réécrit src/href relatifs dans le HTML final.
	 *
	 * @param string $html
	 * @param array  $context
	 * @return string
	 */
	public static function rewrite_relative_urls( $html, $context ) {
		if ( empty( $context['owner'] ) || empty( $context['repo'] ) || $html === '' ) {
			return $html;
		}

		$replace_url = function ( $url, $kind ) use ( $context ) {
			$url = html_entity_decode( trim( $url ), ENT_QUOTES, 'UTF-8' );
			if ( ! self::is_relative_url( $url ) ) {
				return $url;
			}
			return self::resolve_repo_url( $url, $context, $kind );
		};

		if ( class_exists( 'DOMDocument' ) ) {
			$previous = libxml_use_internal_errors( true );
			$dom      = new DOMDocument( '1.0', 'UTF-8' );
			$wrapped  = '<?xml encoding="UTF-8"><div id="wpgit-root">' . $html . '</div>';
			$dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

			foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {
				$src = $img->getAttribute( 'src' );
				if ( $src !== '' ) {
					$img->setAttribute( 'src', $replace_url( $src, 'image' ) );
				}
				$srcset = $img->getAttribute( 'srcset' );
				if ( $srcset !== '' ) {
					$parts = array_map( 'trim', explode( ',', $srcset ) );
					$out   = array();
					foreach ( $parts as $part ) {
						if ( $part === '' ) {
							continue;
						}
						$bits = preg_split( '/\s+/', $part, 2 );
						$u    = $replace_url( $bits[0], 'image' );
						$out[] = $u . ( isset( $bits[1] ) ? ' ' . $bits[1] : '' );
					}
					$img->setAttribute( 'srcset', implode( ', ', $out ) );
				}
			}

			foreach ( $dom->getElementsByTagName( 'a' ) as $a ) {
				$href = $a->getAttribute( 'href' );
				if ( $href !== '' ) {
					$kind = preg_match( '/\.(png|jpe?g|gif|webp|svg|bmp|ico)(?:\?|#|$)/i', $href ) ? 'image' : 'link';
					$a->setAttribute( 'href', $replace_url( $href, $kind ) );
				}
			}

			$root = $dom->getElementById( 'wpgit-root' );
			$out  = '';
			if ( $root ) {
				foreach ( $root->childNodes as $child ) {
					$out .= $dom->saveHTML( $child );
				}
			} else {
				$out = $html;
			}

			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
			return $out;
		}

		// Fallback regex.
		$html = preg_replace_callback(
			'#(\bsrc\s*=\s*)(["\'])([^"\']+)\2#i',
			function ( $m ) use ( $replace_url ) {
				return $m[1] . $m[2] . esc_url( $replace_url( $m[3], 'image' ) ) . $m[2];
			},
			$html
		);

		$html = preg_replace_callback(
			'#(\bhref\s*=\s*)(["\'])([^"\']+)\2#i',
			function ( $m ) use ( $replace_url ) {
				$kind = preg_match( '/\.(png|jpe?g|gif|webp|svg|bmp|ico)(?:\?|#|$)/i', $m[3] ) ? 'image' : 'link';
				return $m[1] . $m[2] . esc_url( $replace_url( $m[3], $kind ) ) . $m[2];
			},
			$html
		);

		return $html;
	}

	/**
	 * Convertit un sélecteur CSS simple en XPath.
	 * Supporte : tag, .class, #id, [attr], [attr=value], [attr="value"], combinaisons tag.class[attr=value]
	 *
	 * @param string $selector
	 * @return string|null
	 */
	public static function css_to_xpath( $selector ) {
		$selector = trim( $selector );
		if ( $selector === '' || ! preg_match( '/^[a-zA-Z0-9#.\["_=\-\s\]]+$/', $selector ) ) {
			return null;
		}

		$tag   = '*';
		$class = null;
		$id    = null;
		$attrs = array();

		if ( preg_match( '/^([a-zA-Z][a-zA-Z0-9]*)/', $selector, $m ) ) {
			$tag      = $m[1];
			$selector = substr( $selector, strlen( $m[1] ) );
		}

		if ( preg_match_all( '/\.([a-zA-Z0-9_-]+)/', $selector, $m ) ) {
			$class = $m[1];
		}
		if ( preg_match( '/#([a-zA-Z0-9_-]+)/', $selector, $m ) ) {
			$id = $m[1];
		}
		if ( preg_match_all( '/\[([a-zA-Z_:][\w:.-]*)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\]]+)))?\]/', $selector, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $match ) {
				$value = null;
				if ( isset( $match[2] ) && $match[2] !== '' ) {
					$value = $match[2];
				} elseif ( isset( $match[3] ) && $match[3] !== '' ) {
					$value = $match[3];
				} elseif ( isset( $match[4] ) && $match[4] !== '' ) {
					$value = trim( $match[4] );
				}
				$attrs[] = array(
					'name'  => $match[1],
					'value' => $value,
				);
			}
		}

		$xpath = '//' . $tag;
		$conds = array();

		if ( $id ) {
			$conds[] = '@id="' . $id . '"';
		}
		if ( $class ) {
			foreach ( $class as $c ) {
				$conds[] = 'contains(concat(" ", normalize-space(@class), " "), " ' . $c . ' ")';
			}
		}
		foreach ( $attrs as $attr ) {
			$name = $attr['name'];
			if ( $attr['value'] === null || $attr['value'] === '' ) {
				$conds[] = '@' . $name;
			} else {
				$conds[] = 'translate(@' . $name . ', "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="' . strtolower( $attr['value'] ) . '"';
			}
		}

		if ( $conds ) {
			$xpath .= '[' . implode( ' and ', $conds ) . ']';
		}

		return $xpath;
	}

	/**
	 * Supprime les éléments correspondant aux sélecteurs CSS.
	 *
	 * @param string   $html
	 * @param string[] $selectors
	 * @return string
	 */
	public static function strip_selectors( $html, $selectors ) {
		$selectors = array_values(
			array_filter(
				array_map( 'trim', (array) $selectors ),
				'strlen'
			)
		);
		if ( empty( $selectors ) || $html === '' || ! class_exists( 'DOMDocument' ) ) {
			return $html;
		}

		$previous = libxml_use_internal_errors( true );
		$dom      = new DOMDocument( '1.0', 'UTF-8' );
		$wrapped  = '<?xml encoding="UTF-8"><div id="wpgit-root">' . $html . '</div>';
		$dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		$xpath = new DOMXPath( $dom );

		foreach ( $selectors as $selector ) {
			$query = self::css_to_xpath( $selector );
			if ( ! $query ) {
				continue;
			}
			$nodes = $xpath->query( $query );
			if ( ! $nodes ) {
				continue;
			}
			// Collect then remove (avoid live list issues).
			$to_remove = array();
			foreach ( $nodes as $node ) {
				$to_remove[] = $node;
			}
			foreach ( $to_remove as $node ) {
				if ( $node->parentNode ) {
					$node->parentNode->removeChild( $node );
				}
			}
		}

		$root = $dom->getElementById( 'wpgit-root' );
		$out  = '';
		if ( $root ) {
			foreach ( $root->childNodes as $child ) {
				$out .= $dom->saveHTML( $child );
			}
		} else {
			$out = $html;
		}

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		return $out;
	}

	/**
	 * Construit la liste de sélecteurs à masquer depuis le contexte bloc.
	 *
	 * @param array $context
	 * @return string[]
	 */
	public static function build_hide_selectors( $context ) {
		$selectors = array();

		if ( ! empty( $context['hideHeader'] ) ) {
			// Bandeau type GitHub : <div align="center">…</div>
			$selectors[] = 'div[align="center"]';
		}

		$custom = $context['hideSelectors'] ?? '';
		if ( is_string( $custom ) && $custom !== '' ) {
			$parts = preg_split( '/[\r\n,]+/', $custom );
			foreach ( $parts as $part ) {
				$part = trim( $part );
				if ( $part !== '' ) {
					$selectors[] = $part;
				}
			}
		}

		return array_values( array_unique( $selectors ) );
	}

	/**
	 * @param string $markdown
	 * @param array  $context {owner,repo,branch,hideHeader,hideSelectors}
	 * @return string
	 */
	public static function to_html( $markdown, $context = array() ) {
		$markdown = self::clean_text( $markdown );
		$markdown = self::rewrite_markdown_urls( $markdown, $context );

		try {
			if ( ! class_exists( 'Parsedown' ) ) {
				require_once WPGIT_PLUGIN_DIR . 'vendor/Parsedown.php';
			}

			$parsedown = new Parsedown();
			if ( method_exists( $parsedown, 'setSafeMode' ) ) {
				$parsedown->setSafeMode( false );
			}
			if ( method_exists( $parsedown, 'setMarkupEscaped' ) ) {
				$parsedown->setMarkupEscaped( false );
			}

			$html = $parsedown->text( $markdown );
		} catch ( Throwable $e ) {
			return '<div class="wpgit-error">' . esc_html( 'Erreur de rendu Markdown.' ) . '</div>';
		}

		$html = self::clean_text( $html );
		$html = self::rewrite_relative_urls( $html, $context );
		$html = self::strip_selectors( $html, self::build_hide_selectors( $context ) );
		$html = wp_kses( $html, self::allowed_html() );
		// Retire width/height HTML des images : ils forcent le débordement des tableaux.
		$html = preg_replace( '/(<img\b[^>]*?)\s+(?:width|height)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '$1', $html );
		$html = preg_replace( '/(<img\b[^>]*?)\s+(?:width|height)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '$1', $html );
		return $html;
	}
}
