/**
 * Sermon List Block - Editor Component
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
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export default function Edit( { attributes, setAttributes } ) {
	const {
		limit,
		preacherId,
		seriesId,
		serviceId,
		showFilters,
		filterType,
		showPagination,
		orderBy,
		order,
	} = attributes;

	const [ sermons, setSermons ] = useState( [] );
	const [ preachers, setPreachers ] = useState( [] );
	const [ series, setSeries ] = useState( [] );
	const [ services, setServices ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ totalCount, setTotalCount ] = useState( 0 );

	// Fetch filter options on mount
	useEffect( () => {
		const fetchOptions = async () => {
			try {
				const [ preacherData, seriesData, servicesData ] =
					await Promise.all( [
						apiFetch( { path: '/sermon-browser/v1/preachers' } ),
						apiFetch( { path: '/sermon-browser/v1/series' } ),
						apiFetch( { path: '/sermon-browser/v1/services' } ),
					] );

				setPreachers( preacherData || [] );
				setSeries( seriesData || [] );
				setServices( servicesData || [] );
			} catch {
				// Silently fail - options won't be available
			}
		};

		fetchOptions();
	}, [] );

	// Fetch sermons when filter attributes change
	useEffect( () => {
		const fetchSermons = async () => {
			setIsLoading( true );

			try {
				let path = `/sermon-browser/v1/sermons?per_page=${ limit }&orderby=${ orderBy }&order=${ order }`;

				if ( preacherId ) {
					path += `&preacher=${ preacherId }`;
				}
				if ( seriesId ) {
					path += `&series=${ seriesId }`;
				}
				if ( serviceId ) {
					path += `&service=${ serviceId }`;
				}

				const response = await apiFetch( { path } );
				const items = response?.items || response || [];
				setSermons( items );
				setTotalCount( response?.total || items.length );
			} catch {
				setSermons( [] );
				setTotalCount( 0 );
			}

			setIsLoading( false );
		};

		fetchSermons();
	}, [ limit, preacherId, seriesId, serviceId, orderBy, order ] );

	const blockProps = useBlockProps( {
		className: 'sb-sermon-list',
	} );

	// Build select options
	const preacherOptions = [
		{ label: __( 'All Preachers', 'sermon-browser' ), value: 0 },
		...preachers.map( ( p ) => ( { label: p.name, value: p.id } ) ),
	];

	const seriesOptions = [
		{ label: __( 'All Series', 'sermon-browser' ), value: 0 },
		...series.map( ( s ) => ( { label: s.name, value: s.id } ) ),
	];

	const serviceOptions = [
		{ label: __( 'All Services', 'sermon-browser' ), value: 0 },
		...services.map( ( s ) => ( { label: s.name, value: s.id } ) ),
	];

	// Preview component
	const SermonsPreview = () => {
		if ( sermons.length === 0 ) {
			return (
				<p className="sb-sermon-list__no-sermons">
					{ __( 'No sermons found.', 'sermon-browser' ) }
				</p>
			);
		}

		return (
			<ul className="sb-sermon-list__items">
				{ sermons.map( ( sermon ) => (
					<li key={ sermon.id } className="sb-sermon-list__item">
						<span className="sb-sermon-list__item-title">
							{ sermon.title }
						</span>
						<span className="sb-sermon-list__item-meta">
							{ sermon.datetime?.split( ' ' )[ 0 ] }
							{ sermon.preacher && ` - ${ sermon.preacher }` }
						</span>
					</li>
				) ) }
			</ul>
		);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Display Settings', 'sermon-browser' ) }>
					<RangeControl
						label={ __( 'Sermons per page', 'sermon-browser' ) }
						value={ limit }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
						min={ 1 }
						max={ 50 }
					/>
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
					<ToggleControl
						label={ __( 'Show filters', 'sermon-browser' ) }
						checked={ showFilters }
						onChange={ ( value ) =>
							setAttributes( { showFilters: value } )
						}
					/>
					{ showFilters && (
						<SelectControl
							label={ __( 'Filter type', 'sermon-browser' ) }
							value={ filterType }
							options={ [
								{
									label: __( 'Dropdown', 'sermon-browser' ),
									value: 'dropdown',
								},
								{
									label: __( 'None', 'sermon-browser' ),
									value: 'none',
								},
							] }
							onChange={ ( value ) =>
								setAttributes( { filterType: value } )
							}
						/>
					) }
					<ToggleControl
						label={ __( 'Show pagination', 'sermon-browser' ) }
						checked={ showPagination }
						onChange={ ( value ) =>
							setAttributes( { showPagination: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Default Filters', 'sermon-browser' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Filter by preacher', 'sermon-browser' ) }
						value={ preacherId }
						options={ preacherOptions }
						onChange={ ( value ) =>
							setAttributes( { preacherId: Number.parseInt( value, 10 ) } )
						}
					/>
					<SelectControl
						label={ __( 'Filter by series', 'sermon-browser' ) }
						value={ seriesId }
						options={ seriesOptions }
						onChange={ ( value ) =>
							setAttributes( { seriesId: Number.parseInt( value, 10 ) } )
						}
					/>
					<SelectControl
						label={ __( 'Filter by service', 'sermon-browser' ) }
						value={ serviceId }
						options={ serviceOptions }
						onChange={ ( value ) =>
							setAttributes( { serviceId: Number.parseInt( value, 10 ) } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ isLoading ? (
					<Placeholder
						icon="list-view"
						label={ __( 'Sermon List', 'sermon-browser' ) }
					>
						<Spinner />
					</Placeholder>
				) : (
					<>
						<div className="sb-sermon-list__header">
							<span className="sb-sermon-list__count">
								{ totalCount }{ ' ' }
								{ totalCount === 1
									? __( 'sermon', 'sermon-browser' )
									: __( 'sermons', 'sermon-browser' ) }
							</span>
							{ showFilters && filterType !== 'none' && (
								<span className="sb-sermon-list__filters-notice">
									{ __(
										'[Filters will display on frontend]',
										'sermon-browser'
									) }
								</span>
							) }
						</div>
						<SermonsPreview />
						{ showPagination && totalCount > limit && (
							<div className="sb-sermon-list__pagination-notice">
								{ __(
									'[Pagination will display on frontend]',
									'sermon-browser'
								) }
							</div>
						) }
					</>
				) }
			</div>
		</>
	);
}
