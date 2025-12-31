/**
 * DB Client JavaScript
 * Main functionality for database client interface
 */

(function($) {
    'use strict';

    // Global DB Client object
    window.DbClient = {
        /**
         * Load table rows with pagination
         */
        loadRows: function(table, page = 1, perPage = 20) {
            return $.get(`/dbclient/table/${table}/rows`, {
                page: page,
                per_page: perPage
            });
        },

        /**
         * Delete a row
         */
        deleteRow: function(table, id) {
            if (!confirm('Are you sure you want to delete this row?')) {
                return Promise.reject('Cancelled');
            }

            return $.ajax({
                url: `/dbclient/table/${table}/${id}/delete`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || window.csrfToken
                }
            });
        },

        /**
         * Insert a new row
         */
        insertRow: function(table, data) {
            return $.ajax({
                url: `/dbclient/table/${table}/insert`,
                type: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || window.csrfToken
                }
            });
        },

        /**
         * Update a row
         */
        updateRow: function(table, id, data) {
            return $.ajax({
                url: `/dbclient/table/${table}/${id}/update`,
                type: 'PUT',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || window.csrfToken
                }
            });
        },

        /**
         * Get a single row
         */
        getRow: function(table, id) {
            return $.get(`/dbclient/table/${table}/row/${id}`);
        },

        /**
         * Run SQL query
         */
        runQuery: function(query) {
            return $.ajax({
                url: '/dbclient/query/run',
                type: 'POST',
                data: { query: query },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || window.csrfToken
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        // Add any initialization code here
        console.log('DB Client initialized');
    });

})(jQuery);
