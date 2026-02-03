/**
 * Profile Picker Component
 *
 * Selects a preacher or series from REST API.
 *
 * @package sermon-browser
 */

import { __ } from '@wordpress/i18n';
import { SelectControl, Spinner } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export default function ProfilePicker( { profileType, value, onChange } ) {
	const [ options, setOptions ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );

	useEffect( () => {
		setIsLoading( true );
		const endpoint =
			profileType === 'preacher'
				? '/sermon-browser/v1/preachers'
				: '/sermon-browser/v1/series';

		apiFetch( { path: endpoint } )
			.then( ( response ) => {
				const items = response || [];
				const selectOptions = [
					{ label: __( 'Select...', 'sermon-browser' ), value: 0 },
					...items.map( ( item ) => ( {
						label: item.name,
						value: item.id,
					} ) ),
				];
				setOptions( selectOptions );
				setIsLoading( false );
			} )
			.catch( () => {
				setOptions( [
					{
						label: __( 'Error loading', 'sermon-browser' ),
						value: 0,
					},
				] );
				setIsLoading( false );
			} );
	}, [ profileType ] );

	if ( isLoading ) {
		return (
			<div
				style={ { display: 'flex', alignItems: 'center', gap: '8px' } }
			>
				<Spinner />
				<span>
					{ profileType === 'preacher'
						? __( 'Loading preachers...', 'sermon-browser' )
						: __( 'Loading series...', 'sermon-browser' ) }
				</span>
			</div>
		);
	}

	return (
		<SelectControl
			label={
				profileType === 'preacher'
					? __( 'Select Preacher', 'sermon-browser' )
					: __( 'Select Series', 'sermon-browser' )
			}
			value={ value }
			options={ options }
			onChange={ ( newValue ) => onChange( parseInt( newValue, 10 ) ) }
		/>
	);
}
