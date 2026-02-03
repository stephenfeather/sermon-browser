/**
 * Recent Sermons Block - Editor Component
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
		limit,
		showPreacher,
		showDate,
		showSeries,
		showPassage,
		preacherId,
		seriesId,
		serviceId,
	} = attributes;

	const [ sermons, setSermons ] = useState( [] );
	const [ preachers, setPreachers ] = useState( [] );
	const [ series, setSeries ] = useState( [] );
	const [ services, setServices ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );

	// Fetch data from REST API
	useEffect( () => {
		setIsLoading( true );

		Promise.all( [
			apiFetch( { path: '/sermon-browser/v1/sermons?per_page=20' } ),
			apiFetch( { path: '/sermon-browser/v1/preachers' } ),
			apiFetch( { path: '/sermon-browser/v1/series' } ),
			apiFetch( { path: '/sermon-browser/v1/services' } ),
		] )
			.then( ( [ sermonsData, preachersData, seriesData, servicesData ] ) => {
				setSermons( sermonsData || [] );
				setPreachers( preachersData || [] );
				setSeries( seriesData || [] );
				setServices( servicesData || [] );
				setIsLoading( false );
			} )
			.catch( () => {
				setSermons( [] );
				setPreachers( [] );
				setSeries( [] );
				setServices( [] );
				setIsLoading( false );
			} );
	}, [] );

	const blockProps = useBlockProps( {
		className: 'sb-recent-sermons',
	} );

	// Filter sermons for preview
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

		// Filter by service
		if ( serviceId > 0 ) {
			displaySermons = displaySermons.filter(
				( s ) => s.service_id === serviceId
			);
		}

		// Apply limit
		return displaySermons.slice( 0, limit );
	};

	// Build select options
	const preacherOptions = [
		{ value: 0, label: __( 'All Preachers', 'sermon-browser' ) },
		...preachers.map( ( p ) => ( { value: p.id, label: p.name } ) ),
	];

	const seriesOptions = [
		{ value: 0, label: __( 'All Series', 'sermon-browser' ) },
		...series.map( ( s ) => ( { value: s.id, label: s.name } ) ),
	];

	const serviceOptions = [
		{ value: 0, label: __( 'All Services', 'sermon-browser' ) },
		...services.map( ( s ) => ( { value: s.id, label: s.name } ) ),
	];

	const SermonsPreview = () => {
		const displaySermons = getDisplaySermons();

		if ( displaySermons.length === 0 ) {
			return (
				<p className="sb-recent-sermons__no-sermons">
					{ __( 'No sermons found.', 'sermon-browser' ) }
				</p>
			);
		}

		return (
			<ul className="sb-recent-sermons__list">
				{ displaySermons.map( ( sermon ) => (
					<li key={ sermon.id } className="sb-recent-sermons__item">
						<span className="sb-recent-sermons__title">
							{ sermon.title }
						</span>
						{ showPassage && sermon.passage && (
							<span className="sb-recent-sermons__passage">
								{ ' ' }({ sermon.passage })
							</span>
						) }
						{ showPreacher && sermon.preacher && (
							<span className="sb-recent-sermons__preacher">
								{ ' ' }{ __( 'by', 'sermon-browser' ) }{ ' ' }
								{ sermon.preacher }
							</span>
						) }
						{ showSeries && sermon.series && (
							<span className="sb-recent-sermons__series">
								{ ' ' }{ __( 'in', 'sermon-browser' ) }{ ' ' }
								{ sermon.series }
							</span>
						) }
						{ showDate && sermon.date && (
							<span className="sb-recent-sermons__date">
								{ ' ' }{ __( 'on', 'sermon-browser' ) }{ ' ' }
								{ sermon.date }
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
					<RangeControl
						label={ __( 'Number of sermons', 'sermon-browser' ) }
						value={ limit }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
						min={ 1 }
						max={ 20 }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Display Options', 'sermon-browser' ) }
					initialOpen={ true }
				>
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
						label={ __( 'Show Bible passage', 'sermon-browser' ) }
						checked={ showPassage }
						onChange={ ( value ) =>
							setAttributes( { showPassage: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Filters', 'sermon-browser' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Preacher', 'sermon-browser' ) }
						value={ preacherId }
						options={ preacherOptions }
						onChange={ ( value ) =>
							setAttributes( { preacherId: parseInt( value, 10 ) } )
						}
					/>
					<SelectControl
						label={ __( 'Series', 'sermon-browser' ) }
						value={ seriesId }
						options={ seriesOptions }
						onChange={ ( value ) =>
							setAttributes( { seriesId: parseInt( value, 10 ) } )
						}
					/>
					<SelectControl
						label={ __( 'Service', 'sermon-browser' ) }
						value={ serviceId }
						options={ serviceOptions }
						onChange={ ( value ) =>
							setAttributes( { serviceId: parseInt( value, 10 ) } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ isLoading ? (
					<Placeholder
						icon="playlist-audio"
						label={ __( 'Recent Sermons', 'sermon-browser' ) }
					>
						<Spinner />
					</Placeholder>
				) : (
					<SermonsPreview />
				) }
			</div>
		</>
	);
}
