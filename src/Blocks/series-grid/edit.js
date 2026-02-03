/**
 * Series Grid Block - Editor Component
 *
 * @package sermon-browser
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';

export default function Edit( { attributes, setAttributes } ) {
	const { columns, limit, showCount, showDescription, orderBy, order } =
		attributes;
	const [ series, setSeries ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );

	// Fetch series from REST API
	useEffect( () => {
		setIsLoading( true );
		apiFetch( {
			path: '/sermon-browser/v1/series',
		} )
			.then( ( response ) => {
				setSeries( response || [] );
				setIsLoading( false );
			} )
			.catch( () => {
				setSeries( [] );
				setIsLoading( false );
			} );
	}, [] );

	const blockProps = useBlockProps( {
		className: 'sb-series-grid',
		style: {
			'--sb-series-grid-columns': columns,
		},
	} );

	// Sort and filter series for preview
	const getDisplaySeries = () => {
		if ( ! series.length ) {
			return [];
		}

		let displaySeries = [ ...series ];

		// Sort series
		displaySeries.sort( ( a, b ) => {
			let comparison = 0;
			if ( orderBy === 'name' ) {
				comparison = ( a.name || '' ).localeCompare( b.name || '' );
			} else if ( orderBy === 'count' ) {
				comparison =
					( a.sermon_count || 0 ) - ( b.sermon_count || 0 );
			}
			return order === 'desc' ? -comparison : comparison;
		} );

		// Apply limit
		if ( limit > 0 ) {
			displaySeries = displaySeries.slice( 0, limit );
		}

		return displaySeries;
	};

	const SeriesPreview = () => {
		const displaySeries = getDisplaySeries();

		if ( displaySeries.length === 0 ) {
			return (
				<p className="sb-series-grid__no-series">
					{ __( 'No series found.', 'sermon-browser' ) }
				</p>
			);
		}

		return (
			<div className="sb-series-grid__grid">
				{ displaySeries.map( ( s ) => (
					<div key={ s.id } className="sb-series-grid__card">
						<h3 className="sb-series-grid__title">{ s.name }</h3>
						{ showCount && (
							<span className="sb-series-grid__count">
								{ s.sermon_count || 0 }{ ' ' }
								{ ( s.sermon_count || 0 ) === 1
									? __( 'sermon', 'sermon-browser' )
									: __( 'sermons', 'sermon-browser' ) }
							</span>
						) }
						{ showDescription && s.description && (
							<p className="sb-series-grid__description">
								{ s.description }
							</p>
						) }
					</div>
				) ) }
			</div>
		);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Grid Settings', 'sermon-browser' ) }
				>
					<RangeControl
						label={ __( 'Columns', 'sermon-browser' ) }
						value={ columns }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
						min={ 1 }
						max={ 6 }
					/>
					<RangeControl
						label={ __( 'Maximum series to show', 'sermon-browser' ) }
						help={ __(
							'Set to 0 to show all series.',
							'sermon-browser'
						) }
						value={ limit }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
						min={ 0 }
						max={ 100 }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Display Options', 'sermon-browser' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Show sermon count', 'sermon-browser' ) }
						checked={ showCount }
						onChange={ ( value ) =>
							setAttributes( { showCount: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show description', 'sermon-browser' ) }
						checked={ showDescription }
						onChange={ ( value ) =>
							setAttributes( { showDescription: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Sorting', 'sermon-browser' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Order by', 'sermon-browser' ) }
						value={ orderBy }
						options={ [
							{
								label: __( 'Name', 'sermon-browser' ),
								value: 'name',
							},
							{
								label: __( 'Sermon count', 'sermon-browser' ),
								value: 'count',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { orderBy: value } )
						}
					/>
					<SelectControl
						label={ __( 'Order', 'sermon-browser' ) }
						value={ order }
						options={ [
							{
								label: __( 'Ascending', 'sermon-browser' ),
								value: 'asc',
							},
							{
								label: __( 'Descending', 'sermon-browser' ),
								value: 'desc',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { order: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ isLoading ? (
					<Placeholder
						icon="grid-view"
						label={ __( 'Series Grid', 'sermon-browser' ) }
					>
						<Spinner />
					</Placeholder>
				) : series.length === 0 ? (
					<Placeholder
						icon="grid-view"
						label={ __( 'Series Grid', 'sermon-browser' ) }
					>
						<p>{ __( 'No series found.', 'sermon-browser' ) }</p>
					</Placeholder>
				) : (
					<SeriesPreview />
				) }
			</div>
		</>
	);
}
