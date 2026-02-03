/**
 * Popular Content Block - Editor Component
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
	const { limit, contentType, showCount, layout } = attributes;

	const [ items, setItems ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );

	// Fetch data from REST API based on content type
	useEffect( () => {
		setIsLoading( true );

		const endpoint =
			contentType === 'sermons'
				? '/sermon-browser/v1/sermons?per_page=20'
				: contentType === 'series'
				? '/sermon-browser/v1/series'
				: '/sermon-browser/v1/preachers';

		apiFetch( { path: endpoint } )
			.then( ( response ) => {
				setItems( response || [] );
				setIsLoading( false );
			} )
			.catch( () => {
				setItems( [] );
				setIsLoading( false );
			} );
	}, [ contentType ] );

	const blockProps = useBlockProps( {
		className: `sb-popular-content sb-popular-content--${ layout } sb-popular-content--${ contentType }`,
	} );

	// Get items for preview (limit applied)
	const getDisplayItems = () => {
		if ( ! items.length ) {
			return [];
		}
		return items.slice( 0, limit );
	};

	const contentTypeOptions = [
		{ value: 'sermons', label: __( 'Sermons', 'sermon-browser' ) },
		{ value: 'series', label: __( 'Series', 'sermon-browser' ) },
		{ value: 'preachers', label: __( 'Preachers', 'sermon-browser' ) },
	];

	const layoutOptions = [
		{ value: 'list', label: __( 'List', 'sermon-browser' ) },
		{ value: 'grid', label: __( 'Grid', 'sermon-browser' ) },
	];

	const ItemsPreview = () => {
		const displayItems = getDisplayItems();

		if ( displayItems.length === 0 ) {
			return (
				<p className="sb-popular-content__no-items">
					{ __( 'No items found.', 'sermon-browser' ) }
				</p>
			);
		}

		const getItemName = ( item ) => {
			return contentType === 'sermons' ? item.title : item.name;
		};

		const getItemCount = ( item ) => {
			if ( contentType === 'sermons' ) {
				return item.hit_count || 0;
			}
			return item.sermon_count || 0;
		};

		if ( layout === 'grid' ) {
			return (
				<div className="sb-popular-content__grid">
					{ displayItems.map( ( item ) => (
						<div key={ item.id } className="sb-popular-content__card">
							<span className="sb-popular-content__name">
								{ getItemName( item ) }
							</span>
							{ showCount && (
								<span className="sb-popular-content__count">
									{ getItemCount( item ) }
								</span>
							) }
						</div>
					) ) }
				</div>
			);
		}

		return (
			<ul className="sb-popular-content__list">
				{ displayItems.map( ( item ) => (
					<li key={ item.id } className="sb-popular-content__item">
						<span className="sb-popular-content__name">
							{ getItemName( item ) }
						</span>
						{ showCount && (
							<span className="sb-popular-content__count">
								({ getItemCount( item ) })
							</span>
						) }
					</li>
				) ) }
			</ul>
		);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'sermon-browser' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Content Type', 'sermon-browser' ) }
						value={ contentType }
						options={ contentTypeOptions }
						onChange={ ( value ) =>
							setAttributes( { contentType: value } )
						}
					/>
					<RangeControl
						label={ __( 'Number of items', 'sermon-browser' ) }
						value={ limit }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
						min={ 1 }
						max={ 15 }
					/>
					<SelectControl
						label={ __( 'Layout', 'sermon-browser' ) }
						value={ layout }
						options={ layoutOptions }
						onChange={ ( value ) =>
							setAttributes( { layout: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show view count', 'sermon-browser' ) }
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
						icon="star-filled"
						label={ __( 'Popular Content', 'sermon-browser' ) }
					>
						<Spinner />
					</Placeholder>
				) : (
					<ItemsPreview />
				) }
			</div>
		</>
	);
}
