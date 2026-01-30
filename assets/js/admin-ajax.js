/**
 * Sermon Browser Admin AJAX Module.
 *
 * Provides CRUD operations for preachers, series, services, and files
 * using the WordPress AJAX API with proper nonce verification.
 *
 * @package SermonBrowser
 * @since 0.6.0
 */

/* global jQuery, sbAjaxSettings */

(function($) {
    'use strict';

    /**
     * Sermon Browser AJAX handler.
     *
     * @type {Object}
     */
    window.SBAdmin = window.SBAdmin || {};

    var settings = window.sbAjaxSettings || {};

    /**
     * Make an AJAX request to the WordPress admin-ajax.php endpoint.
     *
     * @param {string} action The AJAX action name.
     * @param {Object} data Additional data to send.
     * @param {string} nonceKey The nonce key to use from settings.
     * @returns {jQuery.Deferred} jQuery AJAX promise.
     */
    function ajaxRequest(action, data, nonceKey) {
        var requestData = $.extend({}, data, {
            action: action,
            _sb_nonce: settings[nonceKey] || ''
        });

        return $.post(settings.ajaxUrl || window.ajaxurl, requestData);
    }

    /**
     * Preacher operations.
     */
    SBAdmin.preacher = {
        /**
         * Create a new preacher.
         *
         * @param {string} name Preacher name.
         * @returns {jQuery.Deferred}
         */
        create: function(name) {
            return ajaxRequest('sb_preacher_create', { name: name }, 'preacherNonce');
        },

        /**
         * Update a preacher.
         *
         * @param {number} id Preacher ID.
         * @param {string} name New name.
         * @returns {jQuery.Deferred}
         */
        update: function(id, name) {
            return ajaxRequest('sb_preacher_update', { id: id, name: name }, 'preacherNonce');
        },

        /**
         * Delete a preacher.
         *
         * @param {number} id Preacher ID.
         * @returns {jQuery.Deferred}
         */
        delete: function(id) {
            return ajaxRequest('sb_preacher_delete', { id: id }, 'preacherNonce');
        }
    };

    /**
     * Series operations.
     */
    SBAdmin.series = {
        /**
         * Create a new series.
         *
         * @param {string} name Series name.
         * @returns {jQuery.Deferred}
         */
        create: function(name) {
            return ajaxRequest('sb_series_create', { name: name }, 'seriesNonce');
        },

        /**
         * Update a series.
         *
         * @param {number} id Series ID.
         * @param {string} name New name.
         * @returns {jQuery.Deferred}
         */
        update: function(id, name) {
            return ajaxRequest('sb_series_update', { id: id, name: name }, 'seriesNonce');
        },

        /**
         * Delete a series.
         *
         * @param {number} id Series ID.
         * @returns {jQuery.Deferred}
         */
        delete: function(id) {
            return ajaxRequest('sb_series_delete', { id: id }, 'seriesNonce');
        }
    };

    /**
     * Service operations.
     */
    SBAdmin.service = {
        /**
         * Create a new service.
         *
         * @param {string} name Service name with time (format: "Name @ HH:MM").
         * @returns {jQuery.Deferred}
         */
        create: function(name) {
            return ajaxRequest('sb_service_create', { name: name }, 'serviceNonce');
        },

        /**
         * Update a service.
         *
         * @param {number} id Service ID.
         * @param {string} name New name with time (format: "Name @ HH:MM").
         * @returns {jQuery.Deferred}
         */
        update: function(id, name) {
            return ajaxRequest('sb_service_update', { id: id, name: name }, 'serviceNonce');
        },

        /**
         * Delete a service.
         *
         * @param {number} id Service ID.
         * @returns {jQuery.Deferred}
         */
        delete: function(id) {
            return ajaxRequest('sb_service_delete', { id: id }, 'serviceNonce');
        }
    };

    /**
     * File operations.
     */
    SBAdmin.file = {
        /**
         * Rename a file.
         *
         * @param {number} id File ID.
         * @param {string} newName New filename.
         * @param {string} oldName Original filename.
         * @returns {jQuery.Deferred}
         */
        rename: function(id, newName, oldName) {
            return ajaxRequest('sb_file_rename', {
                id: id,
                name: newName,
                old_name: oldName
            }, 'fileNonce');
        },

        /**
         * Delete a file.
         *
         * @param {number} id File ID.
         * @param {string} name Filename.
         * @returns {jQuery.Deferred}
         */
        delete: function(id, name) {
            return ajaxRequest('sb_file_delete', { id: id, name: name }, 'fileNonce');
        }
    };

    /**
     * Handle AJAX response.
     *
     * @param {Object} response WordPress AJAX response.
     * @param {Function} onSuccess Success callback.
     * @param {Function} onError Error callback.
     */
    SBAdmin.handleResponse = function(response, onSuccess, onError) {
        if (response && response.success) {
            if (typeof onSuccess === 'function') {
                onSuccess(response.data);
            }
        } else {
            var message = (response && response.data && response.data.message)
                ? response.data.message
                : 'An error occurred.';
            if (typeof onError === 'function') {
                onError(message);
            } else {
                alert(message);
            }
        }
    };

    /**
     * Sermon pagination operations.
     */
    SBAdmin.sermon = {
        /**
         * Get paginated sermon list with filters.
         *
         * @param {number} page Page number (1-based).
         * @param {Object} filters Optional filters (title, preacher, series).
         * @returns {jQuery.Deferred}
         */
        list: function(page, filters) {
            var data = $.extend({ page: page }, filters || {});
            return ajaxRequest('sb_sermon_list', data, 'sermonNonce');
        },

        /**
         * Render sermon list table rows.
         *
         * @param {Array} items Sermon items from API.
         * @param {number} rowIndex Starting row index for alternating.
         * @returns {string} HTML string.
         */
        renderRows: function(items, rowIndex) {
            rowIndex = rowIndex || 0;
            return items.map(function(sermon, i) {
                var rowClass = (rowIndex + i) % 2 === 0 ? 'alternate' : '';
                var actions = '';

                if (sermon.can_edit) {
                    actions = '<a href="' + sermon.edit_url + '">' + SBAdmin.i18n.edit + '</a> | ' +
                              '<a onclick="return confirm(\'' + SBAdmin.i18n.confirmDelete + '\')" href="' + sermon.delete_url + '">' + SBAdmin.i18n.delete + '</a> | ';
                }
                actions += '<a href="' + sermon.view_url + '">' + SBAdmin.i18n.view + '</a>';

                return '<tr class="' + rowClass + '">' +
                    '<th style="text-align:center" scope="row">' + sermon.id + '</th>' +
                    '<td>' + sermon.title + '</td>' +
                    '<td>' + sermon.preacher_name + '</td>' +
                    '<td>' + sermon.formatted_date + '</td>' +
                    '<td>' + sermon.service_name + '</td>' +
                    '<td>' + sermon.series_name + '</td>' +
                    '<td>' + sermon.stats + '</td>' +
                    '<td style="text-align:center">' + actions + '</td>' +
                    '</tr>';
            }).join('');
        }
    };

    /**
     * File pagination operations.
     */
    SBAdmin.filePagination = {
        /**
         * Get paginated unlinked files.
         *
         * @param {number} page Page number (1-based).
         * @returns {jQuery.Deferred}
         */
        unlinked: function(page) {
            return ajaxRequest('sb_file_unlinked', { page: page }, 'fileNonce');
        },

        /**
         * Get paginated linked files.
         *
         * @param {number} page Page number (1-based).
         * @returns {jQuery.Deferred}
         */
        linked: function(page) {
            return ajaxRequest('sb_file_linked', { page: page }, 'fileNonce');
        },

        /**
         * Search files by name.
         *
         * @param {string} searchTerm Search term.
         * @param {number} page Page number (1-based).
         * @returns {jQuery.Deferred}
         */
        search: function(searchTerm, page) {
            return ajaxRequest('sb_file_search', { search: searchTerm, page: page || 1 }, 'fileNonce');
        },

        /**
         * Render file list table rows.
         *
         * @param {Array} items File items from API.
         * @param {number} rowIndex Starting row index for alternating.
         * @returns {string} HTML string.
         */
        renderRows: function(items, rowIndex) {
            rowIndex = rowIndex || 0;
            return items.map(function(file, i) {
                var rowClass = 'file ' + ((rowIndex + i) % 2 === 0 ? 'alternate' : '');
                var idPrefix = file.is_unlinked ? '' : 's';
                var sermonCol = file.is_unlinked ? '' : '<td>' + file.sermon_title + '</td>';
                var createLink = file.is_unlinked
                    ? '<a href="' + file.create_sermon_url + '">' + SBAdmin.i18n.createSermon + '</a> | '
                    : '';

                return '<tr class="' + rowClass + '" id="' + idPrefix + 'file' + file.id + '">' +
                    '<th style="text-align:center" scope="row">' + file.id + '</th>' +
                    '<td id="' + idPrefix + file.id + '">' + file.basename + '</td>' +
                    '<td style="text-align:center">' + file.type_name + '</td>' +
                    sermonCol +
                    '<td style="text-align:center">' +
                        createLink +
                        '<a id="link' + file.id + '" href="javascript:rename(' + file.id + ', \'' + file.name.replace(/'/g, "\\'") + '\')">' + SBAdmin.i18n.rename + '</a> | ' +
                        '<a href="javascript:kill(' + file.id + ', \'' + file.name.replace(/'/g, "\\'") + '\');">' + SBAdmin.i18n.delete + '</a>' +
                    '</td>' +
                    '</tr>';
            }).join('');
        },

        /**
         * Render "No results" row.
         *
         * @returns {string} HTML string.
         */
        renderNoResults: function() {
            return '<tr><td>' + SBAdmin.i18n.noResults + '</td></tr>';
        }
    };

    /**
     * Default i18n strings (can be overridden via sbAjaxSettings.i18n).
     */
    SBAdmin.i18n = $.extend({
        edit: 'Edit',
        delete: 'Delete',
        view: 'View',
        rename: 'Rename',
        createSermon: 'Create sermon',
        noResults: 'No results',
        confirmDelete: 'Are you sure?',
        previous: '« Previous',
        next: 'Next »'
    }, settings.i18n || {});

})(jQuery);
