/**
 * Single Sermon Block - Editor Component
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
import SermonPicker from './components/sermon-picker';

export default function Edit( { attributes, setAttributes } ) {
	const {
		sermonId,
		useLatest,
		showDescription,
		showPreacher,
		showDate,
		showSeries,
		showPassage,
		showMedia,
		showTags,
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
		className: 'sb-single-sermon',
	} );

	// Preview component
	const SermonPreview = () => {
		if ( ! sermon ) {
			return (
				<p className="sb-single-sermon__no-sermon">
					{ __( 'Sermon not found.', 'sermon-browser' ) }
				</p>
			);
		}

		return (
			<div className="sb-single-sermon__preview">
				<h3 className="sb-single-sermon__title">{ sermon.title }</h3>

				{ showDate && sermon.datetime && (
					<p className="sb-single-sermon__date">
						{ sermon.datetime.split( ' ' )[ 0 ] }
					</p>
				) }

				{ showPreacher && sermon.preacher && (
					<p className="sb-single-sermon__preacher">
						{ sermon.preacher }
					</p>
				) }

				{ showSeries && sermon.series && (
					<p className="sb-single-sermon__series">
						{ __( 'Series:', 'sermon-browser' ) } { sermon.series }
					</p>
				) }

				{ showDescription && sermon.description && (
					<div className="sb-single-sermon__description">
						{ sermon.description }
					</div>
				) }

				{ showMedia && (
					<p className="sb-single-sermon__media-placeholder">
						<em>
							{ __(
								'[Media files will display on frontend]',
								'sermon-browser'
							) }
						</em>
					</p>
				) }

				{ showTags && (
					<p className="sb-single-sermon__tags-placeholder">
						<em>
							{ __(
								'[Tags will display on frontend]',
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
				<PanelBody
					title={ __( 'Sermon Selection', 'sermon-browser' ) }
				>
					<ToggleControl
						label={ __( 'Always show latest sermon', 'sermon-browser' ) }
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
						label={ __( 'Show description', 'sermon-browser' ) }
						checked={ showDescription }
						onChange={ ( value ) =>
							setAttributes( { showDescription: value } )
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
						label={ __( 'Show series', 'sermon-browser' ) }
						checked={ showSeries }
						onChange={ ( value ) =>
							setAttributes( { showSeries: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show passage', 'sermon-browser' ) }
						checked={ showPassage }
						onChange={ ( value ) =>
							setAttributes( { showPassage: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show media', 'sermon-browser' ) }
						checked={ showMedia }
						onChange={ ( value ) =>
							setAttributes( { showMedia: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show tags', 'sermon-browser' ) }
						checked={ showTags }
						onChange={ ( value ) =>
							setAttributes( { showTags: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ isLoading ? (
					<Placeholder
						icon="microphone"
						label={ __( 'Single Sermon', 'sermon-browser' ) }
					>
						<Spinner />
					</Placeholder>
				) : ! sermonId && ! useLatest ? (
					<Placeholder
						icon="microphone"
						label={ __( 'Single Sermon', 'sermon-browser' ) }
						instructions={ __(
							'Select a sermon or enable "Always show latest sermon" in the block settings.',
							'sermon-browser'
						) }
					/>
				) : (
					<SermonPreview />
				) }
			</div>
		</>
	);
}
