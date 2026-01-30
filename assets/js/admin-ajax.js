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

})(jQuery);
