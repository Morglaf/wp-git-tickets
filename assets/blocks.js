( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const {
		PanelBody,
		TextControl,
		SelectControl,
		RangeControl,
		ToggleControl,
		TextareaControl,
		Notice,
		Placeholder,
	} = wp.components;
	const { __ } = wp.i18n;

	let ServerSideRender = wp.serverSideRender;
	if ( ServerSideRender && ServerSideRender.default ) {
		ServerSideRender = ServerSideRender.default;
	}
	if ( ! ServerSideRender && wp.components && wp.components.ServerSideRender ) {
		ServerSideRender = wp.components.ServerSideRender;
	}

	const acfFieldOptions =
		window.wpgitBlocks && Array.isArray( window.wpgitBlocks.acfFields )
			? window.wpgitBlocks.acfFields
			: [ { label: '— Select a field —', value: '' } ];
	const hasAcfFields = acfFieldOptions.length > 1;

	const widthOptions = [
		{ label: __( 'Default (100%)', 'wp-git-tickets' ), value: '' },
		{ label: '100%', value: '100%' },
		{ label: '75%', value: '75%' },
		{ label: '66%', value: '66%' },
		{ label: '50%', value: '50%' },
		{ label: '33%', value: '33%' },
		{ label: '400px', value: '400px' },
		{ label: '600px', value: '600px' },
		{ label: '800px', value: '800px' },
		{ label: __( 'Custom…', 'wp-git-tickets' ), value: 'custom' },
	];

	function WidthControls( { attributes, setAttributes } ) {
		const width = attributes.width || '';
		const presetValues = widthOptions.map( function ( o ) {
			return o.value;
		} );
		const isCustom = width !== '' && presetValues.indexOf( width ) === -1;
		const selectValue = isCustom ? 'custom' : width;

		return el(
			Fragment,
			null,
			el( SelectControl, {
				label: __( 'Width', 'wp-git-tickets' ),
				value: selectValue,
				options: widthOptions,
				onChange: function ( value ) {
					if ( value === 'custom' ) {
						setAttributes( { width: isCustom ? width : '640px' } );
					} else {
						setAttributes( { width: value } );
					}
				},
			} ),
			selectValue === 'custom'
				? el( TextControl, {
						label: __( 'Width personnalisée', 'wp-git-tickets' ),
						value: width,
						help: __( 'e.g. 640px, 80%, 40rem', 'wp-git-tickets' ),
						onChange: function ( value ) {
							setAttributes( { width: value } );
						},
				  } )
				: null
		);
	}

	function SourceControls( { attributes, setAttributes } ) {
		return el(
			Fragment,
			null,
			el( SelectControl, {
				label: __( 'Repository source', 'wp-git-tickets' ),
				value: attributes.sourceType,
				options: [
					{ label: __( 'Manual URL', 'wp-git-tickets' ), value: 'url' },
					{ label: __( 'ACF / SCF field', 'wp-git-tickets' ), value: 'acf' },
				],
				onChange: function ( value ) {
					setAttributes( { sourceType: value } );
				},
			} ),
			attributes.sourceType === 'url'
				? el( TextControl, {
						label: __( 'Repository URL', 'wp-git-tickets' ),
						value: attributes.repoUrl,
						placeholder: 'https://github.com/owner/repo',
						onChange: function ( value ) {
							setAttributes( { repoUrl: value } );
						},
				  } )
				: hasAcfFields
					? el( SelectControl, {
							label: __( 'ACF / SCF field', 'wp-git-tickets' ),
							value: attributes.acfField,
							options: acfFieldOptions,
							help: __(
								'Value read from the current post.',
								'wp-git-tickets'
							),
							onChange: function ( value ) {
								setAttributes( { acfField: value } );
							},
					  } )
					: el(
							Fragment,
							null,
							el(
								Notice,
								{ status: 'warning', isDismissible: false },
								__(
									'No ACF/SCF field detected. Enter the field name manually.',
									'wp-git-tickets'
								)
							),
							el( TextControl, {
								label: __( 'ACF / SCF field name', 'wp-git-tickets' ),
								value: attributes.acfField,
								onChange: function ( value ) {
									setAttributes( { acfField: value } );
								},
							} )
					  )
		);
	}

	function hasRepoSource( attributes ) {
		if ( attributes.sourceType === 'acf' ) {
			return !! attributes.acfField;
		}
		return !! attributes.repoUrl;
	}

	function FeedTypeControl( { attributes, setAttributes } ) {
		return el( SelectControl, {
			label: __( 'Feed type', 'wp-git-tickets' ),
			value: attributes.feedType,
			options: [
				{ label: __( 'Commits', 'wp-git-tickets' ), value: 'commits' },
				{ label: __( 'Activity (events)', 'wp-git-tickets' ), value: 'events' },
			],
			onChange: function ( value ) {
				setAttributes( { feedType: value } );
			},
		} );
	}

	function blockStyle( attributes ) {
		const style = {};
		if ( attributes.width ) {
			style.width = attributes.width;
			style.maxWidth = '100%';
		}
		return style;
	}

	function PreviewOrPlaceholder( { block, attributes, label, configured } ) {
		const blockProps = useBlockProps( {
			className: 'wpgit-block-editor',
			style: blockStyle( attributes ),
		} );

		if ( ! configured ) {
			return el(
				'div',
				blockProps,
				el( Placeholder, {
					icon: 'github',
					label: label,
					instructions: __(
						'Configure the block in the sidebar.',
						'wp-git-tickets'
					),
				} )
			);
		}

		if ( attributes.sourceType === 'acf' ) {
			return el(
				'div',
				blockProps,
				el(
					Notice,
					{ status: 'info', isDismissible: false },
					__( 'Preview for ACF/SCF field "', 'wp-git-tickets' ) +
						attributes.acfField +
						__(
							'" — real content appears in preview / front-end.',
							'wp-git-tickets'
						)
				)
			);
		}

		if ( ! ServerSideRender ) {
			return el(
				'div',
				blockProps,
				el(
					Notice,
					{ status: 'error', isDismissible: false },
					__( 'ServerSideRender is unavailable.', 'wp-git-tickets' )
				)
			);
		}

		return el(
			'div',
			blockProps,
			el( ServerSideRender, {
				block: block,
				attributes: attributes,
				ErrorResponsePlaceholder: function ( props ) {
					const err = props && props.response ? props.response : props;
					let message = __( 'Block render error.', 'wp-git-tickets' );
					if ( err ) {
						if ( typeof err.message === 'string' ) {
							message = err.message;
						} else if ( typeof err === 'string' ) {
							message = err;
						} else if ( err.code ) {
							message = String( err.code );
						}
					}
					return el(
						Notice,
						{ status: 'error', isDismissible: false },
						message
					);
				},
			} )
		);
	}

	registerBlockType( 'wpgit/repo-feed', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Feed settings', 'wp-git-tickets' ), initialOpen: true },
						el( SourceControls, { attributes: attributes, setAttributes: setAttributes } ),
						el( FeedTypeControl, { attributes: attributes, setAttributes: setAttributes } ),
						el( RangeControl, {
							label: __( 'Number of items', 'wp-git-tickets' ),
							value: attributes.limit,
							onChange: function ( value ) {
								setAttributes( { limit: value || 10 } );
							},
							min: 1,
							max: 50,
						} ),
						el( WidthControls, { attributes: attributes, setAttributes: setAttributes } )
					)
				),
				el( PreviewOrPlaceholder, {
					block: 'wpgit/repo-feed',
					attributes: attributes,
					label: __( 'GitHub repository feed', 'wp-git-tickets' ),
					configured: hasRepoSource( attributes ),
				} )
			);
		},
		save: function () {
			return null;
		},
	} );

	registerBlockType( 'wpgit/org-feed', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps( {
				className: 'wpgit-block-editor',
				style: blockStyle( attributes ),
			} );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Feed settings', 'wp-git-tickets' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Organization', 'wp-git-tickets' ),
							value: attributes.org,
							help: __(
								'Leave empty to use the organization from plugin settings.',
								'wp-git-tickets'
							),
							placeholder: 'Myrepo',
							onChange: function ( value ) {
								setAttributes( { org: value } );
							},
						} ),
						el( FeedTypeControl, { attributes: attributes, setAttributes: setAttributes } ),
						el( RangeControl, {
							label: __( 'Number of items', 'wp-git-tickets' ),
							value: attributes.limit,
							onChange: function ( value ) {
								setAttributes( { limit: value || 10 } );
							},
							min: 1,
							max: 50,
						} ),
						el( WidthControls, { attributes: attributes, setAttributes: setAttributes } )
					)
				),
				el(
					'div',
					blockProps,
					ServerSideRender
						? el( ServerSideRender, {
								block: 'wpgit/org-feed',
								attributes: attributes,
								ErrorResponsePlaceholder: function ( props ) {
									const err = props && props.response ? props.response : props;
									let message = __( 'Block render error.', 'wp-git-tickets' );
									if ( err && typeof err.message === 'string' ) {
										message = err.message;
									}
									return el(
										Notice,
										{ status: 'error', isDismissible: false },
										message
									);
								},
						  } )
						: null
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	registerBlockType( 'wpgit/repo-readme', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'README settings', 'wp-git-tickets' ), initialOpen: true },
						el( SourceControls, { attributes: attributes, setAttributes: setAttributes } ),
						el( TextControl, {
							label: __( 'Branch', 'wp-git-tickets' ),
							value: attributes.branch,
							help: __( 'Default: main (falls back to master).', 'wp-git-tickets' ),
							onChange: function ( value ) {
								setAttributes( { branch: value || 'main' } );
							},
						} ),
						el( TextControl, {
							label: __( 'Default language', 'wp-git-tickets' ),
							value: attributes.defaultLang,
							help: __(
								'e.g. en for README.en.md. Empty = README.md.',
								'wp-git-tickets'
							),
							onChange: function ( value ) {
								setAttributes( { defaultLang: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show tabs when multiple READMEs exist', 'wp-git-tickets' ),
							checked: !! attributes.showTabs,
							onChange: function ( value ) {
								setAttributes( { showTabs: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'First tab label', 'wp-git-tickets' ),
							value: attributes.defaultTabLabel || '',
							placeholder: 'README',
							help: __(
								'Label for README.md (no language suffix). e.g. French, FR…',
								'wp-git-tickets'
							),
							onChange: function ( value ) {
								setAttributes( { defaultTabLabel: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Hide header (div align="center")', 'wp-git-tickets' ),
							checked: !! attributes.hideHeader,
							help: __(
								'Removes the top logos / badges / language links banner from the README.',
								'wp-git-tickets'
							),
							onChange: function ( value ) {
								setAttributes( { hideHeader: value } );
							},
						} ),
						el( TextareaControl, {
							label: __( 'Also hide (CSS selectors)', 'wp-git-tickets' ),
							value: attributes.hideSelectors || '',
							help: __(
								'One selector per line. e.g. div[align="center"]  ·  .header  ·  #badges',
								'wp-git-tickets'
							),
							rows: 3,
							onChange: function ( value ) {
								setAttributes( { hideSelectors: value } );
							},
						} ),
						el( WidthControls, { attributes: attributes, setAttributes: setAttributes } )
					)
				),
				el( PreviewOrPlaceholder, {
					block: 'wpgit/repo-readme',
					attributes: attributes,
					label: __( 'GitHub repository README', 'wp-git-tickets' ),
					configured: hasRepoSource( attributes ),
				} )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
