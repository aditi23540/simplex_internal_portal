// app_scripts.js
// JavaScript for SAP Data Viewer with Smart Sync functionality.

$(document).ready(function() {
    var itemTable; // Declare itemTable in a broader scope

    // Function to initialize Select2 dropdowns (same as your provided version)
    function initSelect2Local(selector, fieldName, placeholderText, allowTags = false) {
        var $selector = $(selector);
        $selector.select2({
            theme: "bootstrap-5",
            width: '100%',
            placeholder: placeholderText,
            allowClear: true,
            tags: allowTags,
            createTag: function (params) {
                var term = $.trim(params.term);
                if (term === '') { return null; }
                return { id: term, text: term, newTag: true };
            },
            ajax: {
                url: ajaxUrl + "?action=get_distinct_values_local&field=" + fieldName,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    var query = { q: params.term, field: fieldName };
                    if (fieldName !== 'Product') { let val = $('#material_no_filter_input').val(); if (val && val.length > 0) query.active_Product = Array.isArray(val) ? val.join(',') : val;}
                    if (fieldName !== 'ProductDescription') { let val = $('#description_filter_input').val(); if (val && val.length > 0) query.active_ProductDescription = Array.isArray(val) ? val.join(',') : val;}
                    if (fieldName !== 'Plant') { let val = $('#plant_filter').val(); if (val && val.length > 0) query.active_Plant = Array.isArray(val) ? val.join(',') : val;}
                    if (fieldName !== 'ProductGroup') { let val = $('#product_group_filter').val(); if (val && val.length > 0) query.active_ProductGroup = Array.isArray(val) ? val.join(',') : val;}
                    if (fieldName !== 'ProductGroupName') { let val = $('#product_group_name_filter').val(); if (val && val.length > 0) query.active_ProductGroupName = Array.isArray(val) ? val.join(',') : val;}
                    if (fieldName !== 'ExternalProductGroup') { let val = $('#ext_material_group_filter').val(); if (val && val.length > 0) query.active_ExternalProductGroup = Array.isArray(val) ? val.join(',') : val;}
                    if (fieldName !== 'ExternalProductGroupName') { let val = $('#ext_material_group_name_filter').val(); if (val && val.length > 0) query.active_ExternalProductGroupName = Array.isArray(val) ? val.join(',') : val;}
                    return query;
                },
                processResults: function (data) {
                    if (data.error) {
                        console.error("Error in Select2 AJAX for " + fieldName + ":", data.error);
                        showUserFeedback("Filter options error for " + fieldName + ": " + escapeHtml(data.error), 'danger');
                        return { results: [] };
                    }
                    return { results: data.results || [] };
                },
                cache: true
            }
        }).on('change', function(e) {
            if (!$(this).data('clearing_by_button')) {
                if (typeof itemTable !== 'undefined' && itemTable && typeof itemTable.ajax.reload === 'function') {
                    itemTable.ajax.reload(null, false);
                }
            }
        });
    }

    // Initialize all Select2 filter dropdowns
    initSelect2Local('#material_no_filter_input', 'Product', 'Type or Select Material No', true);
    initSelect2Local('#description_filter_input', 'ProductDescription', 'Type or Select Description', true);
    initSelect2Local('#plant_filter', 'Plant', 'Select Plant');
    initSelect2Local('#product_group_filter', 'ProductGroup', 'Select Product Group');
    initSelect2Local('#product_group_name_filter', 'ProductGroupName', 'Select Prod. Grp. Name');
    initSelect2Local('#ext_material_group_filter', 'ExternalProductGroup', 'Select External Mat. Group');
    initSelect2Local('#ext_material_group_name_filter', 'ExternalProductGroupName', 'Select Ext. Mat. Grp. Name');

    // Initialize the main DataTable
    itemTable = $('#itemGroupTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": ajaxUrl + "?action=get_item_data",
            "type": "GET",
            "data": function (d) {
                d.material_no_filter = $('#material_no_filter_input').val();
                d.description_filter = $('#description_filter_input').val();
                d.plant_filter = $('#plant_filter').val();
                d.product_group_filter = $('#product_group_filter').val();
                d.product_group_name_filter = $('#product_group_name_filter').val();
                d.ext_material_group_filter = $('#ext_material_group_filter').val();
                d.ext_material_group_name_filter = $('#ext_material_group_name_filter').val();
                d.search = { value: $('#globalSearchInput').val(), regex: false };
                return d;
            },
            "dataSrc": function (json) {
                if (json.error) {
                    console.error("DataTable AJAX error:", json.error);
                    showUserFeedback('Table Error: ' + escapeHtml(json.error), 'danger');
                    return [];
                }
                return json.data;
            },
            "error": function(jqXHR, textStatus, errorThrown) {
                console.error('DataTable AJAX Error. Status:', textStatus, 'Error:', errorThrown, jqXHR.responseText);
                showUserFeedback('Error fetching table data: ' + escapeHtml(textStatus), 'danger');
                if (typeof itemTable !== 'undefined' && itemTable) {
                    try { itemTable.clear().draw(); } catch(e) { console.error("Error clearing table:", e); }
                }
            }
        },
        "columns": [
            { "data": "id", "name":"id", "title": "ID", "searchable": false, "orderable": true },
            { "data": "material_no", "name":"material_no", "title": "Material No", "orderable": true },
            { "data": "material_description", "name":"material_description", "title": "Description", "orderable": true },
            { "data": "plant", "name":"plant", "title": "Plant", "orderable": true },
            { "data": "material_group_no", "name":"material_group_no", "title": "Prod. Group", "orderable": true },
            { "data": "material_group_name", "name":"material_group_name", "title": "Prod. Group Name", "orderable": true },
            { "data": "external_group_no", "name":"external_group_no", "title": "Ext. Group", "orderable": true },
            { "data": "external_group_name", "name":"external_group_name", "title": "Ext. Group Name", "orderable": true }
        ],
        "responsive": true,
        "scrollX": true,
        "scrollY": "50vh",
        "scrollCollapse": true,
        "lengthMenu": [[25, 50, 100, 500], [25, 50, 100, 500]],
        "pageLength": 25,
        "dom":  "<'row'<'col-sm-12 col-md-6 dataTables_length_temporary_wrapper'l><'col-sm-12 col-md-6 dataTables_filter_placeholder'f>>" +
                "<'row'<'col-sm-12'tr>>" + 
                "<'row dt-footer-row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        "initComplete": function(settings, json) {
            var $lengthMenuDiv = $('.dataTables_length_temporary_wrapper .dataTables_length');
            if ($lengthMenuDiv.length && $('#lengthMenuContainer').length) {
                $lengthMenuDiv.detach().appendTo('#lengthMenuContainer');
                var $tempWrapperRow = $('.dataTables_length_temporary_wrapper').parent();
                if ($tempWrapperRow.children('.dataTables_length_temporary_wrapper').children().length === 0) {
                    if($tempWrapperRow.children(':not(.dataTables_length_temporary_wrapper)').html().trim() === '') {
                        $tempWrapperRow.remove();
                    } else {
                        $('.dataTables_length_temporary_wrapper').hide();
                    }
                }
            }
            $('.dataTables_filter_placeholder').hide(); 
        }
    });

    // Custom Global Search
    var searchDelayGlobal;
    $('#globalSearchInput').on('keyup input', function () {
        clearTimeout(searchDelayGlobal);
        var searchTerm = $(this).val();
        searchDelayGlobal = setTimeout(function() {
            if (typeof itemTable !== 'undefined' && itemTable) {
                itemTable.search(searchTerm).draw();
            }
        }, 350);
    });

    // Clear Filters Button
    $('#clearFiltersButtonDb').on('click', function() {
        var $selects = $('#filterForm .select2-dropdown-local');
        var needsReload = false;
        $selects.each(function() {
            $(this).data('clearing_by_button', true); 
            if ($(this).val() !== null && $(this).val() !== '' && (!Array.isArray($(this).val()) || $(this).val().length > 0) ) {
                $(this).val(null).trigger('change.select2'); 
                needsReload = true;
            }
            $(this).removeData('clearing_by_button'); 
        });
        if ($('#globalSearchInput').val() !== '') {
            $('#globalSearchInput').val('');
            needsReload = true; 
        }
        if (needsReload) {
            if (typeof itemTable !== 'undefined' && itemTable) {
                itemTable.search('').draw(); 
            }
            showUserFeedback('All filters cleared.', 'info', 2000);
        } else {
            showUserFeedback('Filters are already clear.', 'info', 2000);
        }
    });

    // User Feedback Helper
    var $userFeedback = $('#userFeedback');
    function showUserFeedback(message, type = 'info', duration = 7000) {
        var alertClass = 'alert-' + type;
        var feedbackHtml =
            '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
            escapeHtml(message) +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>';
        var $newAlert = $(feedbackHtml);
        $userFeedback.append($newAlert); 
        if (duration > 0 && (type === 'info' || type === 'success')) {
            setTimeout(function() {
                if ($newAlert && $newAlert.closest('body').length) { 
                    $newAlert.alert('close');
                }
            }, duration);
        }
    }

    // HTML Escape Helper
    function escapeHtml(unsafe) {
        if (unsafe === null || typeof unsafe === 'undefined') return '';
        return unsafe.toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    // --- Smart Sync Functionality (Full load or Insert Ignore) ---
    function triggerSmartSync() {
        var $syncIndicator = $('#syncStatusIndicator');
        $syncIndicator.text('Syncing data with SAP...').removeClass('synced error').addClass('syncing').fadeIn();
        console.log('[Debug] Triggering Smart Sync...');

        $.ajax({
            url: ajaxUrl, 
            type: 'GET',
            // Use the action name that triggers perform_intelligent_sync() in sap_logic.php
            data: { action: 'perform_intelligent_sync' }, 
            dataType: 'json', // Expecting JSON response from sap_logic.php action trigger
            success: function(response) {
                console.log('[Debug] Smart sync AJAX success:', response);
                $('#userFeedback').empty(); // Clear previous messages before showing new ones

                if (response && response.feedback_messages && Array.isArray(response.feedback_messages)) {
                    response.feedback_messages.forEach(function(msg) {
                        showUserFeedback(msg.text, msg.type, msg.type === 'danger' || msg.type === 'warning' ? 0 : 7000); // Keep errors/warnings visible longer
                    });
                } else if (response && response.status) {
                     showUserFeedback(response.status, 'success');
                } else {
                     showUserFeedback('Smart sync completed. Check table for updates.', 'success', 5000);
                }

                $syncIndicator.text('Sync Complete').removeClass('syncing error').addClass('synced');
                setTimeout(function() { $syncIndicator.fadeOut(); }, 3000);

                if (typeof itemTable !== 'undefined' && itemTable) {
                    itemTable.ajax.reload(null, false); 
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('[Debug] Smart sync AJAX Error:', textStatus, errorThrown, jqXHR.responseText);
                showUserFeedback('Error during smart sync: ' + escapeHtml(textStatus) + (errorThrown ? ' - ' + escapeHtml(errorThrown) : ''), 'danger');
                $syncIndicator.text('Sync Error!').removeClass('syncing synced').addClass('error').show();
            }
        });
    }

    // Trigger smart sync automatically when the page is ready
    triggerSmartSync();

});
