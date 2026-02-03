/**
 * Sermon Grid Block - Editor Component
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
	const {
		layout,
		columns,
		limit,
		showThumbnails,
		showExcerpt,
		excerptLength,
		showPreacher,
		showDate,
		showSeries,
		preacherId,
		seriesId,
		orderBy,
		order,
	} = attributes;

	const [ sermons, setSermons ] = useState( [] );
	const [ preachers, setPreachers ] = useState( [] );
	const [ series, setSeries ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );

	// Fetch sermons from REST API
	useEffect( () => {
		setIsLoading( true );
		apiFetch( {
			path: '/sermon-browser/v1/sermons',
		} )
			.then( ( response ) => {
				setSermons( response || [] );
				setIsLoading( false );
			} )
			.catch( () => {
				setSermons( [] );
				setIsLoading( false );
			} );
	}, [] );

	// Fetch preachers for filter
	useEffect( () => {
		apiFetch( {
			path: '/sermon-browser/v1/preachers',
		} )
			.then( ( response ) => {
				setPreachers( response || [] );
			} )
			.catch( () => {
				setPreachers( [] );
			} );
	}, [] );

	// Fetch series for filter
	useEffect( () => {
		apiFetch( {
			path: '/sermon-browser/v1/series',
		} )
			.then( ( response ) => {
				setSeries( response || [] );
			} )
			.catch( () => {
				setSeries( [] );
			} );
	}, [] );

	const blockProps = useBlockProps( {
		className: `sb-sermon-grid sb-sermon-grid--${ layout }`,
		style: {
			'--sb-sermon-grid-columns': columns,
		},
	} );

	// Helper function to truncate text
	const truncateText = ( text, wordLimit ) => {
		if ( ! text ) {
			return '';
		}
		const words = text.split( ' ' );
		if ( words.length <= wordLimit ) {
			return text;
		}
		return words.slice( 0, wordLimit ).join( ' ' ) + '...';
	};

	// Filter and sort sermons for preview
	const getDisplaySermons = () => {
		if ( ! sermons.length ) {
			return [];
		}

		let displaySermons = [ ...sermons ];

		// Filter by preacher
		if ( preacherId > 0 ) {
			displaySermons = displaySermons.filter(
				( s ) => s.preacher_id === preacherId
			);
		}

		// Filter by series
		if ( seriesId > 0 ) {
			displaySermons = displaySermons.filter(
				( s ) => s.series_id === seriesId
			);
		}

		// Sort sermons
		displaySermons.sort( ( a, b ) => {
			let comparison = 0;
			if ( orderBy === 'datetime' ) {
				comparison = new Date( a.datetime ) - new Date( b.datetime );
			} else if ( orderBy === 'title' ) {
				comparison = ( a.title || '' ).localeCompare( b.title || '' );
			}
			return order === 'desc' ? -comparison : comparison;
		} );

		// Apply limit
		if ( limit > 0 ) {
			displaySermons = displaySermons.slice( 0, limit );
		}

		return displaySermons;
	};

	// Build preacher options for SelectControl
	const preacherOptions = [
		{ label: __( 'All Preachers', 'sermon-browser' ), value: 0 },
		...preachers.map( ( p ) => ( {
			label: p.name,
			value: p.id,
		} ) ),
	];

	// Build series options for SelectControl
	const seriesOptions = [
		{ label: __( 'All Series', 'sermon-browser' ), value: 0 },
		...series.map( ( s ) => ( {
			label: s.name,
			value: s.id,
		} ) ),
	];

	const SermonPreview = () => {
		const displaySermons = getDisplaySermons();

		if ( displaySermons.length === 0 ) {
			return (
				<p className="sb-sermon-grid__no-sermons">
					{ __( 'No sermons found.', 'sermon-browser' ) }
				</p>
			);
		}

		return (
			<div className="sb-sermon-grid__grid">
				{ displaySermons.map( ( sermon ) => (
					<div key={ sermon.id } className="sb-sermon-grid__card">
						{ showThumbnails && sermon.thumbnail && (
							<img
								src={ sermon.thumbnail }
								alt={ sermon.title }
								className="sb-sermon-grid__thumbnail"
							/>
						) }
						<h3 className="sb-sermon-grid__title">
							{ sermon.title }
						</h3>
						<div className="sb-sermon-grid__meta">
							{ showPreacher && sermon.preacher && (
								<span className="sb-sermon-grid__preacher">
									{ sermon.preacher }
								</span>
							) }
							{ showDate && sermon.datetime && (
								<span className="sb-sermon-grid__date">
									{ new Date(
										sermon.datetime
									).toLocaleDateString() }
								</span>
							) }
							{ showSeries && sermon.series && (
								<span className="sb-sermon-grid__series">
									{ sermon.series }
								</span>
							) }
						</div>
						{ showExcerpt && sermon.description && (
							<p className="sb-sermon-grid__excerpt">
								{ truncateText(
									sermon.description,
									excerptLength
								) }
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
				<PanelBody title={ __( 'Grid Settings', 'sermon-browser' ) }>
					<SelectControl
						label={ __( 'Layout', 'sermon-browser' ) }
						value={ layout }
						options={ [
							{
								label: __( 'Grid', 'sermon-browser' ),
								value: 'grid',
							},
							{
								label: __( 'Cards', 'sermon-browser' ),
								value: 'cards',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { layout: value } )
						}
					/>
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
						label={ __( 'Number of sermons', 'sermon-browser' ) }
						value={ limit }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
						min={ 1 }
						max={ 24 }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Display Options', 'sermon-browser' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Show thumbnails', 'sermon-browser' ) }
						checked={ showThumbnails }
						onChange={ ( value ) =>
							setAttributes( { showThumbnails: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show excerpt', 'sermon-browser' ) }
						checked={ showExcerpt }
						onChange={ ( value ) =>
							setAttributes( { showExcerpt: value } )
						}
					/>
					{ showExcerpt && (
						<RangeControl
							label={ __(
								'Excerpt length (words)',
								'sermon-browser'
							) }
							value={ excerptLength }
							onChange={ ( value ) =>
								setAttributes( { excerptLength: value } )
							}
							min={ 5 }
							max={ 100 }
						/>
					) }
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
				</PanelBody>

				<PanelBody
					title={ __( 'Filters', 'sermon-browser' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Filter by preacher', 'sermon-browser' ) }
						value={ preacherId }
						options={ preacherOptions }
						onChange={ ( value ) =>
							setAttributes( { preacherId: parseInt( value ) } )
						}
					/>
					<SelectControl
						label={ __( 'Filter by series', 'sermon-browser' ) }
						value={ seriesId }
						options={ seriesOptions }
						onChange={ ( value ) =>
							setAttributes( { seriesId: parseInt( value ) } )
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
								label: __( 'Date', 'sermon-browser' ),
								value: 'datetime',
							},
							{
								label: __( 'Title', 'sermon-browser' ),
								value: 'title',
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
								label: __( 'Descending', 'sermon-browser' ),
								value: 'desc',
							},
							{
								label: __( 'Ascending', 'sermon-browser' ),
								value: 'asc',
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
						label={ __( 'Sermon Grid', 'sermon-browser' ) }
					>
						<Spinner />
					</Placeholder>
				) : sermons.length === 0 ? (
					<Placeholder
						icon="grid-view"
						label={ __( 'Sermon Grid', 'sermon-browser' ) }
					>
						<p>{ __( 'No sermons found.', 'sermon-browser' ) }</p>
					</Placeholder>
				) : (
					<SermonPreview />
				) }
			</div>
		</>
	);
}
