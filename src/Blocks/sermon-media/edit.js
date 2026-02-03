/**
 * Sermon Media Block - Editor Component
 *
 * @package sermon-browser
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SermonPicker from '../single-sermon/components/sermon-picker';

export default function Edit( { attributes, setAttributes } ) {
	const {
		sermonId,
		useLatest,
		mediaType,
		showDownload,
		playerStyle,
		autoplay,
		showTitle,
		showMeta,
	} = attributes;

	const [ sermon, setSermon ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( false );

	// Fetch sermon data when sermonId changes or useLatest is enabled
	useEffect( () => {
		const fetchSermon = async () => {
			setIsLoading( true );

			try {
				let id = sermonId;

				// If useLatest, fetch the latest sermon first
				if ( useLatest ) {
					const latest = await apiFetch( {
						path: '/sermon-browser/v1/sermons?per_page=1&orderby=datetime&order=desc',
					} );
					const items = latest?.items || latest || [];
					if ( items.length > 0 ) {
						id = items[ 0 ].id;
					}
				}

				if ( id ) {
					const response = await apiFetch( {
						path: `/sermon-browser/v1/sermons/${ id }`,
					} );
					setSermon( response );
				} else {
					setSermon( null );
				}
			} catch {
				setSermon( null );
			}

			setIsLoading( false );
		};

		if ( sermonId || useLatest ) {
			fetchSermon();
		} else {
			setSermon( null );
		}
	}, [ sermonId, useLatest ] );

	const blockProps = useBlockProps( {
		className: `sb-sermon-media sb-sermon-media--${ playerStyle }`,
	} );

	// Get media type label for display
	const getMediaTypeLabel = () => {
		switch ( mediaType ) {
			case 'audio':
				return __( 'Audio', 'sermon-browser' );
			case 'video':
				return __( 'Video', 'sermon-browser' );
			case 'both':
				return __( 'Audio & Video', 'sermon-browser' );
			default:
				return __( 'Media', 'sermon-browser' );
		}
	};

	// Preview component
	const MediaPreview = () => {
		if ( ! sermon ) {
			return (
				<p className="sb-sermon-media__no-sermon">
					{ __( 'Sermon not found.', 'sermon-browser' ) }
				</p>
			);
		}

		return (
			<div className="sb-sermon-media__preview">
				{ showTitle && (
					<h3 className="sb-sermon-media__title">{ sermon.title }</h3>
				) }

				{ showMeta && ( sermon.datetime || sermon.preacher ) && (
					<div className="sb-sermon-media__meta">
						{ sermon.datetime && (
							<span className="sb-sermon-media__date">
								{ sermon.datetime.split( ' ' )[ 0 ] }
							</span>
						) }
						{ sermon.preacher && (
							<span className="sb-sermon-media__preacher">
								{ sermon.preacher }
							</span>
						) }
					</div>
				) }

				{ ( mediaType === 'video' || mediaType === 'both' ) && (
					<div className="sb-sermon-media__video sb-sermon-media__placeholder">
						<span className="dashicons dashicons-video-alt3"></span>
						<em>
							{ __(
								'Video player will display on frontend',
								'sermon-browser'
							) }
						</em>
					</div>
				) }

				{ ( mediaType === 'audio' || mediaType === 'both' ) && (
					<div className="sb-sermon-media__audio sb-sermon-media__placeholder">
						<span className="dashicons dashicons-controls-play"></span>
						<em>
							{ __(
								'Audio player will display on frontend',
								'sermon-browser'
							) }
						</em>
					</div>
				) }

				{ showDownload && (
					<div className="sb-sermon-media__downloads sb-sermon-media__placeholder">
						<em>
							{ __(
								'[Download links will display on frontend]',
								'sermon-browser'
							) }
						</em>
					</div>
				) }
			</div>
		);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Sermon Selection', 'sermon-browser' ) }>
					<ToggleControl
						label={ __(
							'Always show latest sermon',
							'sermon-browser'
						) }
						help={ __(
							'Automatically display the most recent sermon.',
							'sermon-browser'
						) }
						checked={ useLatest }
						onChange={ ( value ) =>
							setAttributes( { useLatest: value } )
						}
					/>

					{ ! useLatest && (
						<SermonPicker
							value={ sermonId }
							onChange={ ( value ) =>
								setAttributes( { sermonId: value } )
							}
						/>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Media Options', 'sermon-browser' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Media Type', 'sermon-browser' ) }
						value={ mediaType }
						options={ [
							{
								label: __( 'Audio Only', 'sermon-browser' ),
								value: 'audio',
							},
							{
								label: __( 'Video Only', 'sermon-browser' ),
								value: 'video',
							},
							{
								label: __(
									'Both Audio & Video',
									'sermon-browser'
								),
								value: 'both',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { mediaType: value } )
						}
					/>

					<SelectControl
						label={ __( 'Player Style', 'sermon-browser' ) }
						value={ playerStyle }
						options={ [
							{
								label: __( 'Default', 'sermon-browser' ),
								value: 'default',
							},
							{
								label: __( 'Minimal', 'sermon-browser' ),
								value: 'minimal',
							},
							{
								label: __( 'Full', 'sermon-browser' ),
								value: 'full',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { playerStyle: value } )
						}
					/>

					<ToggleControl
						label={ __( 'Autoplay', 'sermon-browser' ) }
						help={ __(
							'Automatically start playing when the page loads.',
							'sermon-browser'
						) }
						checked={ autoplay }
						onChange={ ( value ) =>
							setAttributes( { autoplay: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Display Options', 'sermon-browser' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Show download links', 'sermon-browser' ) }
						checked={ showDownload }
						onChange={ ( value ) =>
							setAttributes( { showDownload: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show title', 'sermon-browser' ) }
						checked={ showTitle }
						onChange={ ( value ) =>
							setAttributes( { showTitle: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Show meta (date & preacher)',
							'sermon-browser'
						) }
						checked={ showMeta }
						onChange={ ( value ) =>
							setAttributes( { showMeta: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ isLoading ? (
					<Placeholder
						icon="format-video"
						label={ __( 'Sermon Media', 'sermon-browser' ) }
					>
						<Spinner />
					</Placeholder>
				) : ! sermonId && ! useLatest ? (
					<Placeholder
						icon="format-video"
						label={ __( 'Sermon Media', 'sermon-browser' ) }
						instructions={ __(
							'Select a sermon or enable "Always show latest sermon" in the block settings.',
							'sermon-browser'
						) }
					>
						<p className="sb-sermon-media__type-indicator">
							{ getMediaTypeLabel() }{ ' ' }
							{ __( 'player', 'sermon-browser' ) }
						</p>
					</Placeholder>
				) : (
					<MediaPreview />
				) }
			</div>
		</>
	);
}
