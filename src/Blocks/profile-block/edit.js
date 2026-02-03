/**
 * Profile Block - Editor Component
 *
 * @package sermon-browser
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	RangeControl,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import { useState, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import ProfilePicker from './components/profile-picker';

export default function Edit( { attributes, setAttributes } ) {
	const {
		profileType,
		profileId,
		showImage,
		showBio,
		showSermons,
		sermonLimit,
		layout,
	} = attributes;

	const [ profile, setProfile ] = useState( null );
	const [ sermons, setSermons ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const prevProfileTypeRef = useRef( profileType );

	// Reset profileId when profileType changes
	useEffect( () => {
		if ( prevProfileTypeRef.current !== profileType ) {
			setAttributes( { profileId: 0 } );
			prevProfileTypeRef.current = profileType;
		}
	}, [ profileType, setAttributes ] );

	// Fetch profile data when profileId or profileType changes
	useEffect( () => {
		if ( ! profileId ) {
			setProfile( null );
			setSermons( [] );
			return;
		}

		setIsLoading( true );

		const endpoint =
			profileType === 'preacher'
				? `/sermon-browser/v1/preachers/${ profileId }`
				: `/sermon-browser/v1/series/${ profileId }`;

		apiFetch( { path: endpoint } )
			.then( ( response ) => {
				setProfile( response );
				// Fetch sermons for this preacher/series
				const sermonEndpoint =
					profileType === 'preacher'
						? `/sermon-browser/v1/sermons?preacher=${ profileId }&per_page=${ sermonLimit }`
						: `/sermon-browser/v1/sermons?series=${ profileId }&per_page=${ sermonLimit }`;
				return apiFetch( { path: sermonEndpoint } );
			} )
			.then( ( response ) => {
				setSermons( response?.items || response || [] );
				setIsLoading( false );
			} )
			.catch( () => {
				setProfile( null );
				setSermons( [] );
				setIsLoading( false );
			} );
	}, [ profileId, profileType, sermonLimit ] );

	const blockProps = useBlockProps( {
		className: `sb-profile-block sb-profile-block--${ layout }`,
	} );

	const ProfilePreview = () => {
		if ( ! profile ) {
			return (
				<p className="sb-profile-block__not-found">
					{ __( 'Profile not found.', 'sermon-browser' ) }
				</p>
			);
		}

		return (
			<div className="sb-profile-block__content">
				{ showImage && profile.image && (
					<div className="sb-profile-block__image">
						<img src={ profile.image } alt={ profile.name } />
					</div>
				) }
				<div className="sb-profile-block__info">
					<h3 className="sb-profile-block__name">{ profile.name }</h3>
					{ showBio && profile.description && (
						<p className="sb-profile-block__bio">
							{ profile.description }
						</p>
					) }
					{ showSermons && sermons.length > 0 && (
						<div className="sb-profile-block__sermons">
							<h4>{ __( 'Recent Sermons', 'sermon-browser' ) }</h4>
							<ul>
								{ sermons.slice( 0, sermonLimit ).map( ( s ) => (
									<li key={ s.id }>{ s.title }</li>
								) ) }
							</ul>
						</div>
					) }
				</div>
			</div>
		);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Profile Settings', 'sermon-browser' ) }>
					<SelectControl
						label={ __( 'Profile Type', 'sermon-browser' ) }
						value={ profileType }
						options={ [
							{
								label: __( 'Preacher', 'sermon-browser' ),
								value: 'preacher',
							},
							{
								label: __( 'Series', 'sermon-browser' ),
								value: 'series',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { profileType: value } )
						}
					/>
					<ProfilePicker
						profileType={ profileType }
						value={ profileId }
						onChange={ ( value ) =>
							setAttributes( { profileId: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Display Options', 'sermon-browser' ) }
					initialOpen={ false }
				>
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
						] }
						onChange={ ( value ) =>
							setAttributes( { layout: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show image', 'sermon-browser' ) }
						checked={ showImage }
						onChange={ ( value ) =>
							setAttributes( { showImage: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show bio/description', 'sermon-browser' ) }
						checked={ showBio }
						onChange={ ( value ) =>
							setAttributes( { showBio: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show recent sermons', 'sermon-browser' ) }
						checked={ showSermons }
						onChange={ ( value ) =>
							setAttributes( { showSermons: value } )
						}
					/>
					{ showSermons && (
						<RangeControl
							label={ __( 'Number of sermons', 'sermon-browser' ) }
							value={ sermonLimit }
							onChange={ ( value ) =>
								setAttributes( { sermonLimit: value } )
							}
							min={ 1 }
							max={ 10 }
						/>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ isLoading ? (
					<Placeholder
						icon="id-alt"
						label={ __( 'Profile', 'sermon-browser' ) }
					>
						<Spinner />
					</Placeholder>
				) : ! profileId ? (
					<Placeholder
						icon="id-alt"
						label={ __(
							'Preacher/Series Profile',
							'sermon-browser'
						) }
						instructions={ __(
							'Select a preacher or series in the block settings.',
							'sermon-browser'
						) }
					/>
				) : (
					<ProfilePreview />
				) }
			</div>
		</>
	);
}
