/**
 * Sermon Player Block - Editor Component
 *
 * @package sermon-browser
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SermonPicker from '../single-sermon/components/sermon-picker';

export default function Edit( { attributes, setAttributes } ) {
	const { sermonId, useLatest, showTitle, showPreacher, showDate, showDownload } =
		attributes;

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
		className: 'sb-sermon-player',
	} );

	// Preview component
	const PlayerPreview = () => {
		if ( ! sermon ) {
			return (
				<p className="sb-sermon-player__no-sermon">
					{ __( 'Sermon not found.', 'sermon-browser' ) }
				</p>
			);
		}

		return (
			<div className="sb-sermon-player__preview">
				{ showTitle && (
					<h3 className="sb-sermon-player__title">{ sermon.title }</h3>
				) }

				<div className="sb-sermon-player__meta">
					{ showDate && sermon.datetime && (
						<span className="sb-sermon-player__date">
							{ sermon.datetime.split( ' ' )[ 0 ] }
						</span>
					) }

					{ showPreacher && sermon.preacher && (
						<span className="sb-sermon-player__preacher">
							{ sermon.preacher }
						</span>
					) }
				</div>

				<div className="sb-sermon-player__audio-placeholder">
					<span className="dashicons dashicons-controls-play"></span>
					<em>
						{ __(
							'Audio player will display on frontend',
							'sermon-browser'
						) }
					</em>
				</div>

				{ showDownload && (
					<p className="sb-sermon-player__download-placeholder">
						<em>
							{ __(
								'[Download link will display on frontend]',
								'sermon-browser'
							) }
						</em>
					</p>
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
					title={ __( 'Display Options', 'sermon-browser' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Show title', 'sermon-browser' ) }
						checked={ showTitle }
						onChange={ ( value ) =>
							setAttributes( { showTitle: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show preacher', 'sermon-browser' ) }
						checked={ showPreacher }
						onChange={ ( value ) =>
							setAttributes( { showPreacher: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show date', 'sermon-browser' ) }
						checked={ showDate }
						onChange={ ( value ) =>
							setAttributes( { showDate: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show download link', 'sermon-browser' ) }
						checked={ showDownload }
						onChange={ ( value ) =>
							setAttributes( { showDownload: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ isLoading ? (
					<Placeholder
						icon="controls-play"
						label={ __( 'Sermon Player', 'sermon-browser' ) }
					>
						<Spinner />
					</Placeholder>
				) : ! sermonId && ! useLatest ? (
					<Placeholder
						icon="controls-play"
						label={ __( 'Sermon Player', 'sermon-browser' ) }
						instructions={ __(
							'Select a sermon or enable "Always show latest sermon" in the block settings.',
							'sermon-browser'
						) }
					/>
				) : (
					<PlayerPreview />
				) }
			</div>
		</>
	);
}
