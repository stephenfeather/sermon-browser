/**
 * Sermon List Block - Frontend Dynamic Filtering
 *
 * Handles AJAX-based filtering without page reloads.
 * Only active when enableDynamicFiltering is true.
 *
 * @package sermon-browser
 */

import DOMPurify from 'dompurify';

( function () {
	'use strict';

	/**
	 * Initialize dynamic filtering for all sermon list blocks on the page.
	 */
	function init() {
		const blocks = document.querySelectorAll(
			'.sb-sermon-list[data-dynamic-filtering="true"]'
		);

		blocks.forEach( ( block ) => {
			new SermonListDynamic( block );
		} );
	}

	/**
	 * SermonListDynamic class handles filtering for a single block instance.
	 */
	class SermonListDynamic {
		/**
		 * Constructor.
		 *
		 * @param {HTMLElement} container The block container element.
		 */
		constructor( container ) {
			this.container = container;
			this.form = container.querySelector( '.sb-sermon-list__filter-form' );
			this.resultsContainer = container.querySelector(
				'.sb-sermon-list__results'
			);
			this.paginationContainer = container.querySelector(
				'.sb-sermon-list__pagination'
			);
			this.noResultsContainer = container.querySelector(
				'.sb-sermon-list__no-results'
			);

			// Get configuration from data attributes.
			this.config = {
				restUrl: container.dataset.restUrl,
				nonce: container.dataset.nonce,
				limit: parseInt( container.dataset.limit, 10 ) || 10,
				orderBy: container.dataset.orderBy || 'datetime',
				order: container.dataset.order || 'desc',
				showPagination: container.dataset.showPagination === 'true',
				preacherId: parseInt( container.dataset.preacherId, 10 ) || 0,
				seriesId: parseInt( container.dataset.seriesId, 10 ) || 0,
				serviceId: parseInt( container.dataset.serviceId, 10 ) || 0,
			};

			this.currentPage = 1;
			this.isLoading = false;

			this.bindEvents();
		}

		/**
		 * Bind event listeners.
		 */
		bindEvents() {
			// Form submission (dropdown filters).
			if ( this.form ) {
				this.form.addEventListener( 'submit', ( e ) => {
					e.preventDefault();
					this.currentPage = 1;
					this.fetchSermons();
				} );

				// Auto-submit on select change for better UX.
				this.form
					.querySelectorAll( 'select' )
					.forEach( ( select ) => {
						select.addEventListener( 'change', () => {
							this.currentPage = 1;
							this.fetchSermons();
						} );
					} );
			}

			// Oneclick filter buttons.
			this.container
				.querySelectorAll( '.sb-sermon-list__filter-button' )
				.forEach( ( button ) => {
					button.addEventListener( 'click', ( e ) => {
						e.preventDefault();
						this.handleOneclickFilter( button );
					} );
				} );

			// Pagination buttons (delegated).
			this.container.addEventListener( 'click', ( e ) => {
				const paginationButton = e.target.closest(
					'.sb-sermon-list__pagination-prev, .sb-sermon-list__pagination-next'
				);
				if ( paginationButton ) {
					e.preventDefault();
					const page = parseInt( paginationButton.dataset.page, 10 );
					if ( page ) {
						this.currentPage = page;
						this.fetchSermons();
					}
				}
			} );
		}

		/**
		 * Handle oneclick filter button clicks.
		 *
		 * @param {HTMLElement} button The clicked button.
		 */
		handleOneclickFilter( button ) {
			const href = button.getAttribute( 'href' );
			if ( ! href ) {
				return;
			}

			// Parse the URL to extract filter params.
			const url = new URL( href, window.location.origin );
			const params = url.searchParams;

			// Update form inputs if they exist.
			if ( this.form ) {
				[ 'preacher', 'series', 'service', 'book', 'stag' ].forEach(
					( param ) => {
						const input = this.form.querySelector(
							`[name="${ param }"]`
						);
						if ( input ) {
							input.value = params.get( param ) || '';
						}
					}
				);
			}

			// Toggle active state on buttons.
			const group = button.closest(
				'.sb-sermon-list__filter-group'
			);
			if ( group ) {
				group
					.querySelectorAll( '.sb-sermon-list__filter-button' )
					.forEach( ( btn ) => {
						btn.classList.remove(
							'sb-sermon-list__filter-button--active'
						);
					} );

				if (
					! button.classList.contains(
						'sb-sermon-list__filter-button--clear'
					)
				) {
					button.classList.add(
						'sb-sermon-list__filter-button--active'
					);
				}
			}

			this.currentPage = 1;
			this.fetchSermons();
		}

		/**
		 * Get current filter values from form or URL.
		 *
		 * @return {Object} Filter parameters.
		 */
		getFilterParams() {
			const params = {
				per_page: this.config.limit,
				orderby: this.config.orderBy,
				order: this.config.order,
				page: this.currentPage,
			};

			// Add default presets from block config.
			if ( this.config.preacherId ) {
				params.preacher = this.config.preacherId;
			}
			if ( this.config.seriesId ) {
				params.series = this.config.seriesId;
			}
			if ( this.config.serviceId ) {
				params.service = this.config.serviceId;
			}

			// Get values from form.
			if ( this.form ) {
				const formData = new FormData( this.form );

				// Override with form values.
				const preacher = formData.get( 'preacher' );
				if ( preacher ) {
					params.preacher = preacher;
				}

				const series = formData.get( 'series' );
				if ( series ) {
					params.series = series;
				}

				const service = formData.get( 'service' );
				if ( service ) {
					params.service = service;
				}

				const book = formData.get( 'book' );
				if ( book ) {
					params.book = book;
				}

				const stag = formData.get( 'stag' );
				if ( stag ) {
					params.stag = stag;
				}

				const date = formData.get( 'date' );
				if ( date ) {
					params.date = date;
				}

				const enddate = formData.get( 'enddate' );
				if ( enddate ) {
					params.enddate = enddate;
				}

				const title = formData.get( 'title' );
				if ( title ) {
					params.title = title;
				}
			}

			return params;
		}

		/**
		 * Fetch sermons from the REST API.
		 */
		async fetchSermons() {
			if ( this.isLoading ) {
				return;
			}

			this.isLoading = true;
			this.setLoadingState( true );

			const params = this.getFilterParams();
			const url = new URL( this.config.restUrl );

			// Add params to URL.
			Object.keys( params ).forEach( ( key ) => {
				if ( params[ key ] !== undefined && params[ key ] !== '' ) {
					url.searchParams.set( key, params[ key ] );
				}
			} );

			try {
				const response = await fetch( url.toString(), {
					method: 'GET',
					headers: {
						'X-WP-Nonce': this.config.nonce,
					},
				} );

				if ( ! response.ok ) {
					throw new Error( `HTTP error! status: ${ response.status }` );
				}

				const data = await response.json();
				this.updateContent( data );
				this.updateUrl( params );
				this.announceResults( data.total );
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error( 'Sermon filter error:', error );
				this.showError();
			} finally {
				this.isLoading = false;
				this.setLoadingState( false );
			}
		}

		/**
		 * Update the DOM with new content.
		 *
		 * @param {Object} data Response data from API.
		 */
		updateContent( data ) {
			// Find or create results container.
			let resultsContainer = this.container.querySelector(
				'.sb-sermon-list__results'
			);
			const noResults = this.container.querySelector(
				'.sb-sermon-list__no-results'
			);

			// Remove existing results/no-results.
			if ( resultsContainer ) {
				resultsContainer.remove();
			}
			if ( noResults ) {
				noResults.remove();
			}

			// Insert new HTML after filters.
			const filters = this.container.querySelector(
				'.sb-sermon-list__filters'
			);
			const insertTarget = filters || this.container.firstChild;

			if ( data.html ) {
				const tempDiv = document.createElement( 'div' );
				tempDiv.innerHTML = DOMPurify.sanitize( data.html );

				// Insert each child after the insert target.
				while ( tempDiv.firstChild ) {
					if ( filters ) {
						filters.insertAdjacentElement(
							'afterend',
							tempDiv.firstChild
						);
					} else {
						this.container.insertBefore(
							tempDiv.firstChild,
							insertTarget
						);
					}
				}
			}

			// Update pagination.
			const existingPagination = this.container.querySelector(
				'.sb-sermon-list__pagination'
			);
			if ( existingPagination ) {
				existingPagination.remove();
			}

			if ( this.config.showPagination && data.pagination ) {
				this.container.insertAdjacentHTML( 'beforeend', DOMPurify.sanitize( data.pagination ) );
			}

			// Update reference to results container.
			this.resultsContainer = this.container.querySelector(
				'.sb-sermon-list__results'
			);
		}

		/**
		 * Update browser URL with current filter state.
		 *
		 * @param {Object} params Current filter parameters.
		 */
		updateUrl( params ) {
			const url = new URL( window.location.href );

			// Clear existing sermon filter params.
			[
				'preacher',
				'series',
				'service',
				'book',
				'stag',
				'date',
				'enddate',
				'title',
				'pagenum',
			].forEach( ( key ) => {
				url.searchParams.delete( key );
			} );

			// Add current params (excluding defaults).
			if ( params.preacher && params.preacher !== '0' ) {
				url.searchParams.set( 'preacher', params.preacher );
			}
			if ( params.series && params.series !== '0' ) {
				url.searchParams.set( 'series', params.series );
			}
			if ( params.service && params.service !== '0' ) {
				url.searchParams.set( 'service', params.service );
			}
			if ( params.book ) {
				url.searchParams.set( 'book', params.book );
			}
			if ( params.stag ) {
				url.searchParams.set( 'stag', params.stag );
			}
			if ( params.date ) {
				url.searchParams.set( 'date', params.date );
			}
			if ( params.enddate ) {
				url.searchParams.set( 'enddate', params.enddate );
			}
			if ( params.title ) {
				url.searchParams.set( 'title', params.title );
			}
			if ( params.page > 1 ) {
				url.searchParams.set( 'pagenum', params.page );
			}

			// Update URL without reload.
			window.history.pushState( {}, '', url.toString() );
		}

		/**
		 * Set loading state on the container.
		 *
		 * @param {boolean} loading Whether loading is in progress.
		 */
		setLoadingState( loading ) {
			this.container.classList.toggle( 'sb-sermon-list--loading', loading );
			this.container.setAttribute( 'aria-busy', loading ? 'true' : 'false' );
		}

		/**
		 * Announce results to screen readers.
		 *
		 * @param {number} total Total number of results.
		 */
		announceResults( total ) {
			// Create or update live region.
			let liveRegion = this.container.querySelector(
				'.sb-sermon-list__live-region'
			);

			if ( ! liveRegion ) {
				liveRegion = document.createElement( 'div' );
				liveRegion.className = 'sb-sermon-list__live-region';
				liveRegion.setAttribute( 'role', 'status' );
				liveRegion.setAttribute( 'aria-live', 'polite' );
				liveRegion.setAttribute( 'aria-atomic', 'true' );
				liveRegion.style.cssText =
					'position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;';
				this.container.appendChild( liveRegion );
			}

			const message =
				total === 0
					? 'No sermons found.'
					: total === 1
					? '1 sermon found.'
					: `${ total } sermons found.`;

			liveRegion.textContent = message;
		}

		/**
		 * Show error message.
		 */
		showError() {
			const resultsContainer = this.container.querySelector(
				'.sb-sermon-list__results'
			);
			if ( resultsContainer ) {
				resultsContainer.innerHTML =
					'<p class="sb-sermon-list__error">Error loading sermons. Please try again.</p>';
			}
		}
	}

	// Initialize when DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
