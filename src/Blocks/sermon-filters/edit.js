/**
 * Sermon Filters Block - Editor Component
 *
 * @package sermon-browser
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';

export default function Edit( { attributes, setAttributes } ) {
	const {
		filterType,
		showPreachers,
		showSeries,
		showServices,
		showBooks,
		showTags,
		showDateRange,
		showSearch,
		targetUrl,
		layout,
	} = attributes;

	const [ preachers, setPreachers ] = useState( [] );
	const [ series, setSeries ] = useState( [] );
	const [ services, setServices ] = useState( [] );
	const [ tags, setTags ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );

	// Fetch filter options from REST API
	useEffect( () => {
		setIsLoading( true );

		const fetchPromises = [
			apiFetch( { path: '/sermon-browser/v1/preachers' } ).catch( () => [] ),
			apiFetch( { path: '/sermon-browser/v1/series' } ).catch( () => [] ),
			apiFetch( { path: '/sermon-browser/v1/services' } ).catch( () => [] ),
			apiFetch( { path: '/sermon-browser/v1/tags' } ).catch( () => [] ),
		];

		Promise.all( fetchPromises )
			.then( ( [ preachersData, seriesData, servicesData, tagsData ] ) => {
				setPreachers( preachersData || [] );
				setSeries( seriesData || [] );
				setServices( servicesData || [] );
				setTags( tagsData || [] );
				setIsLoading( false );
			} )
			.catch( () => {
				setIsLoading( false );
			} );
	}, [] );

	const blockProps = useBlockProps( {
		className: `sb-sermon-filters sb-sermon-filters--${ layout } sb-sermon-filters--${ filterType }`,
	} );

	// Check if any filters are enabled
	const hasEnabledFilters =
		showPreachers ||
		showSeries ||
		showServices ||
		showBooks ||
		showTags ||
		showDateRange ||
		showSearch;

	// Render filter group for oneclick style
	const renderOneclickGroup = ( label, items, nameField = 'name' ) => {
		if ( ! items || items.length === 0 ) {
			return null;
		}

		const displayItems = items.slice( 0, 5 );
		const hasMore = items.length > 5;

		return (
			<div className="sb-sermon-filters__group">
				<span className="sb-sermon-filters__label">{ label }</span>
				<div className="sb-sermon-filters__buttons">
					{ displayItems.map( ( item, index ) => (
						<span
							key={ item.id || index }
							className="sb-sermon-filters__button"
						>
							{ item[ nameField ] }
							{ item.sermon_count !== undefined && (
								<span className="sb-sermon-filters__count">
									({ item.sermon_count })
								</span>
							) }
						</span>
					) ) }
					{ hasMore && (
						<span className="sb-sermon-filters__button sb-sermon-filters__button--more">
							+{ items.length - 5 } { __( 'more', 'sermon-browser' ) }
						</span>
					) }
				</div>
			</div>
		);
	};

	// Render filter group for dropdown style
	const renderDropdownGroup = ( label, items, nameField = 'name' ) => {
		if ( ! items || items.length === 0 ) {
			return null;
		}

		return (
			<div className="sb-sermon-filters__group">
				<span className="sb-sermon-filters__label">{ label }</span>
				<select className="sb-sermon-filters__select" disabled>
					<option>
						{ __( 'All', 'sermon-browser' ) } { label }
					</option>
					{ items.slice( 0, 3 ).map( ( item, index ) => (
						<option key={ item.id || index }>
							{ item[ nameField ] }
						</option>
					) ) }
					{ items.length > 3 && (
						<option disabled>
							...{ __( 'and', 'sermon-browser' ) } { items.length - 3 }{ ' ' }
							{ __( 'more', 'sermon-browser' ) }
						</option>
					) }
				</select>
			</div>
		);
	};

	// Render a filter group based on filter type
	const renderGroup = ( label, items, nameField = 'name' ) => {
		if ( filterType === 'dropdown' ) {
			return renderDropdownGroup( label, items, nameField );
		}
		return renderOneclickGroup( label, items, nameField );
	};

	// Render search input
	const renderSearchInput = () => (
		<div className="sb-sermon-filters__group">
			<span className="sb-sermon-filters__label">
				{ __( 'Search', 'sermon-browser' ) }
			</span>
			<div className="sb-sermon-filters__search">
				<input
					type="text"
					className="sb-sermon-filters__search-input"
					placeholder={ __( 'Search sermons...', 'sermon-browser' ) }
					disabled
				/>
				<button
					type="button"
					className="sb-sermon-filters__search-button"
					disabled
				>
					{ __( 'Search', 'sermon-browser' ) }
				</button>
			</div>
		</div>
	);

	// Render date range filter
	const renderDateRange = () => (
		<div className="sb-sermon-filters__group">
			<span className="sb-sermon-filters__label">
				{ __( 'Date Range', 'sermon-browser' ) }
			</span>
			<div className="sb-sermon-filters__date-range">
				<input
					type="date"
					className="sb-sermon-filters__date-input"
					disabled
				/>
				<span className="sb-sermon-filters__date-separator">
					{ __( 'to', 'sermon-browser' ) }
				</span>
				<input
					type="date"
					className="sb-sermon-filters__date-input"
					disabled
				/>
			</div>
		</div>
	);

	// Render books placeholder (static list since no REST endpoint)
	const renderBooksGroup = () => {
		const sampleBooks = [
			{ name: 'Genesis' },
			{ name: 'Exodus' },
			{ name: 'Psalms' },
			{ name: 'Matthew' },
			{ name: 'John' },
		];

		return renderGroup( __( 'Books', 'sermon-browser' ), sampleBooks );
	};

	const FiltersPreview = () => {
		if ( ! hasEnabledFilters ) {
			return (
				<p className="sb-sermon-filters__no-filters">
					{ __(
						'No filters enabled. Use the block settings to enable filters.',
						'sermon-browser'
					) }
				</p>
			);
		}

		return (
			<>
				{ showPreachers &&
					renderGroup( __( 'Preachers', 'sermon-browser' ), preachers ) }
				{ showSeries &&
					renderGroup( __( 'Series', 'sermon-browser' ), series ) }
				{ showServices &&
					renderGroup( __( 'Services', 'sermon-browser' ), services ) }
				{ showBooks && renderBooksGroup() }
				{ showTags && renderGroup( __( 'Tags', 'sermon-browser' ), tags ) }
				{ showDateRange && renderDateRange() }
				{ showSearch && renderSearchInput() }
			</>
		);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Filter Settings', 'sermon-browser' ) }>
					<SelectControl
						label={ __( 'Filter Type', 'sermon-browser' ) }
						value={ filterType }
						options={ [
							{
								label: __( 'One-click Buttons', 'sermon-browser' ),
								value: 'oneclick',
							},
							{
								label: __( 'Dropdown Menus', 'sermon-browser' ),
								value: 'dropdown',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { filterType: value } )
						}
					/>
					<SelectControl
						label={ __( 'Layout', 'sermon-browser' ) }
						value={ layout }
						options={ [
							{
								label: __( 'Horizontal', 'sermon-browser' ),
								value: 'horizontal',
							},
							{
								label: __( 'Vertical', 'sermon-browser' ),
								value: 'vertical',
							},
							{
								label: __( 'Grid', 'sermon-browser' ),
								value: 'grid',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { layout: value } )
						}
					/>
					<TextControl
						label={ __( 'Target URL', 'sermon-browser' ) }
						help={ __(
							'Optional. URL where filters navigate to. Leave empty to filter on current page.',
							'sermon-browser'
						) }
						value={ targetUrl }
						onChange={ ( value ) =>
							setAttributes( { targetUrl: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Filter Categories', 'sermon-browser' ) }
					initialOpen={ true }
				>
					<ToggleControl
						label={ __( 'Show Preachers', 'sermon-browser' ) }
						checked={ showPreachers }
						onChange={ ( value ) =>
							setAttributes( { showPreachers: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Series', 'sermon-browser' ) }
						checked={ showSeries }
						onChange={ ( value ) =>
							setAttributes( { showSeries: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Services', 'sermon-browser' ) }
						checked={ showServices }
						onChange={ ( value ) =>
							setAttributes( { showServices: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Books', 'sermon-browser' ) }
						checked={ showBooks }
						onChange={ ( value ) =>
							setAttributes( { showBooks: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Tags', 'sermon-browser' ) }
						checked={ showTags }
						onChange={ ( value ) =>
							setAttributes( { showTags: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Date Range', 'sermon-browser' ) }
						checked={ showDateRange }
						onChange={ ( value ) =>
							setAttributes( { showDateRange: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Search', 'sermon-browser' ) }
						checked={ showSearch }
						onChange={ ( value ) =>
							setAttributes( { showSearch: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ isLoading ? (
					<Placeholder
						icon="filter"
						label={ __( 'Sermon Filters', 'sermon-browser' ) }
					>
						<Spinner />
					</Placeholder>
				) : (
					<FiltersPreview />
				) }
			</div>
		</>
	);
}
