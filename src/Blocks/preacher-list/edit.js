/**
 * Preacher List Block - Editor Component
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
	const { limit, showCount, orderBy, order, layout } = attributes;
	const [ preachers, setPreachers ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );

	// Fetch preachers from REST API
	useEffect( () => {
		setIsLoading( true );
		apiFetch( {
			path: '/sermon-browser/v1/preachers',
		} )
			.then( ( response ) => {
				setPreachers( response || [] );
				setIsLoading( false );
			} )
			.catch( () => {
				setPreachers( [] );
				setIsLoading( false );
			} );
	}, [] );

	const blockProps = useBlockProps( {
		className: `sb-preacher-list sb-preacher-list--${ layout }`,
	} );

	// Sort and filter preachers for preview
	const getDisplayPreachers = () => {
		if ( ! preachers.length ) {
			return [];
		}

		let displayPreachers = [ ...preachers ];

		// Sort preachers
		displayPreachers.sort( ( a, b ) => {
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
			displayPreachers = displayPreachers.slice( 0, limit );
		}

		return displayPreachers;
	};

	const PreachersPreview = () => {
		const displayPreachers = getDisplayPreachers();

		if ( displayPreachers.length === 0 ) {
			return (
				<p className="sb-preacher-list__no-preachers">
					{ __( 'No preachers found.', 'sermon-browser' ) }
				</p>
			);
		}

		if ( layout === 'grid' ) {
			return (
				<div className="sb-preacher-list__grid">
					{ displayPreachers.map( ( preacher ) => (
						<div
							key={ preacher.id }
							className="sb-preacher-list__card"
						>
							<span className="sb-preacher-list__name">
								{ preacher.name }
							</span>
							{ showCount && (
								<span className="sb-preacher-list__count">
									{ preacher.sermon_count || 0 }{ ' ' }
									{ ( preacher.sermon_count || 0 ) === 1
										? __( 'sermon', 'sermon-browser' )
										: __( 'sermons', 'sermon-browser' ) }
								</span>
							) }
						</div>
					) ) }
				</div>
			);
		}

		return (
			<ul className="sb-preacher-list__items">
				{ displayPreachers.map( ( preacher ) => (
					<li key={ preacher.id } className="sb-preacher-list__item">
						<span className="sb-preacher-list__name">
							{ preacher.name }
						</span>
						{ showCount && (
							<span className="sb-preacher-list__count">
								({ preacher.sermon_count || 0 })
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
					title={ __( 'Preacher List Settings', 'sermon-browser' ) }
				>
					<RangeControl
						label={ __(
							'Maximum preachers to show',
							'sermon-browser'
						) }
						help={ __(
							'Set to 0 to show all preachers.',
							'sermon-browser'
						) }
						value={ limit }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
						min={ 0 }
						max={ 100 }
					/>
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
					<SelectControl
						label={ __( 'Layout', 'sermon-browser' ) }
						value={ layout }
						options={ [
							{
								label: __( 'List', 'sermon-browser' ),
								value: 'list',
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
						icon="groups"
						label={ __( 'Preacher List', 'sermon-browser' ) }
					>
						<Spinner />
					</Placeholder>
				) : preachers.length === 0 ? (
					<Placeholder
						icon="groups"
						label={ __( 'Preacher List', 'sermon-browser' ) }
					>
						<p>
							{ __( 'No preachers found.', 'sermon-browser' ) }
						</p>
					</Placeholder>
				) : (
					<PreachersPreview />
				) }
			</div>
		</>
	);
}
