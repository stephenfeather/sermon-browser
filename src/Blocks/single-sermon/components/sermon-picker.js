/**
 * Sermon Picker Component
 *
 * ComboboxControl for selecting a sermon from the REST API.
 *
 * @package sermon-browser
 */

import { __ } from '@wordpress/i18n';
import { ComboboxControl, Spinner } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export default function SermonPicker( { value, onChange } ) {
	const [ sermons, setSermons ] = useState( [] );
	const [ filteredOptions, setFilteredOptions ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );

	// Fetch sermons from REST API
	useEffect( () => {
		setIsLoading( true );
		apiFetch( {
			path: '/sermon-browser/v1/sermons?per_page=100',
		} )
			.then( ( response ) => {
				const items = response?.items || response || [];
				setSermons( items );
				setIsLoading( false );
			} )
			.catch( () => {
				setSermons( [] );
				setIsLoading( false );
			} );
	}, [] );

	// Build options for ComboboxControl
	useEffect( () => {
		const options = sermons.map( ( sermon ) => ( {
			value: sermon.id,
			label: `${ sermon.title } (${ sermon.datetime?.split( ' ' )[ 0 ] || '' })`,
		} ) );
		setFilteredOptions( options );
	}, [ sermons ] );

	const handleFilterChange = ( inputValue ) => {
		const normalizedInput = inputValue.toLowerCase();
		const filtered = sermons
			.filter( ( sermon ) =>
				sermon.title.toLowerCase().includes( normalizedInput )
			)
			.map( ( sermon ) => ( {
				value: sermon.id,
				label: `${ sermon.title } (${ sermon.datetime?.split( ' ' )[ 0 ] || '' })`,
			} ) );
		setFilteredOptions( filtered );
	};

	if ( isLoading ) {
		return (
			<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
				<Spinner />
				<span>{ __( 'Loading sermons...', 'sermon-browser' ) }</span>
			</div>
		);
	}

	return (
		<ComboboxControl
			label={ __( 'Select Sermon', 'sermon-browser' ) }
			value={ value }
			options={ filteredOptions }
			onChange={ onChange }
			onFilterValueChange={ handleFilterChange }
			help={ __( 'Type to search for a sermon by title.', 'sermon-browser' ) }
		/>
	);
}
