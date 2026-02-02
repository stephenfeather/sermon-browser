/**
 * Sermon Tag Cloud Block - Editor Component
 *
 * @package sermon-browser
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	ToggleControl,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';

export default function Edit( { attributes, setAttributes } ) {
	const { limit, minFontPercent, maxFontPercent, showCount } = attributes;
	const [ tags, setTags ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );

	// Fetch tags from REST API
	useEffect( () => {
		setIsLoading( true );
		apiFetch( {
			path: '/sermon-browser/v1/tags',
		} )
			.then( ( response ) => {
				setTags( response || [] );
				setIsLoading( false );
			} )
			.catch( () => {
				setTags( [] );
				setIsLoading( false );
			} );
	}, [] );

	const blockProps = useBlockProps( {
		className: 'sb-tag-cloud',
	} );

	// Calculate preview tags
	const previewTags = () => {
		if ( ! tags.length ) {
			return null;
		}

		let displayTags = [ ...tags ];

		// Apply limit
		if ( limit > 0 ) {
			displayTags = displayTags.slice( 0, limit );
		}

		if ( ! displayTags.length ) {
			return null;
		}

		// Calculate font sizes using log scale
		const counts = displayTags.map( ( t ) => t.sermon_count || 1 );
		const maxCount = Math.max( ...counts );
		const minCount = Math.min( ...counts );
		const fontRange = maxFontPercent - minFontPercent;
		const minLog = Math.log( minCount || 1 );
		const maxLog = Math.log( maxCount || 1 );
		const logRange = maxLog === minLog ? 1 : maxLog - minLog;

		return displayTags.map( ( tag, index ) => {
			const count = tag.sermon_count || 1;
			const size =
				minFontPercent +
				( fontRange * ( Math.log( count ) - minLog ) ) / logRange;

			return (
				<span
					key={ tag.id || index }
					className="sb-tag-cloud__tag"
					style={ { fontSize: `${ Math.round( size ) }%` } }
				>
					{ tag.name }
					{ showCount && (
						<span className="sb-tag-cloud__count">
							({ count })
						</span>
					) }{ ' ' }
				</span>
			);
		} );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Tag Cloud Settings', 'sermon-browser' ) }
				>
					<RangeControl
						label={ __( 'Maximum tags to show', 'sermon-browser' ) }
						help={ __(
							'Set to 0 to show all tags.',
							'sermon-browser'
						) }
						value={ limit }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
						min={ 0 }
						max={ 100 }
					/>
					<RangeControl
						label={ __( 'Minimum font size (%)', 'sermon-browser' ) }
						value={ minFontPercent }
						onChange={ ( value ) =>
							setAttributes( { minFontPercent: value } )
						}
						min={ 50 }
						max={ 150 }
					/>
					<RangeControl
						label={ __( 'Maximum font size (%)', 'sermon-browser' ) }
						value={ maxFontPercent }
						onChange={ ( value ) =>
							setAttributes( { maxFontPercent: value } )
						}
						min={ 100 }
						max={ 300 }
					/>
					<ToggleControl
						label={ __( 'Show sermon count', 'sermon-browser' ) }
						checked={ showCount }
						onChange={ ( value ) =>
							setAttributes( { showCount: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ isLoading ? (
					<Placeholder
						icon="tag"
						label={ __( 'Sermon Tag Cloud', 'sermon-browser' ) }
					>
						<Spinner />
					</Placeholder>
				) : tags.length === 0 ? (
					<Placeholder
						icon="tag"
						label={ __( 'Sermon Tag Cloud', 'sermon-browser' ) }
					>
						<p>{ __( 'No sermon tags found.', 'sermon-browser' ) }</p>
					</Placeholder>
				) : (
					<div className="sb-tag-cloud__tags">{ previewTags() }</div>
				) }
			</div>
		</>
	);
}
