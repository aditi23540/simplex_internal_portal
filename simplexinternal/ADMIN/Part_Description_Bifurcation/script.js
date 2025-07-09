// script.js for Part Description Bifurcation
$(document).ready(function() {
    const ajaxUrl = 'data_handler.php';
    let bifurcationTable;
    const $mainTypeSelector = $('#filter-main_type');
    const $typeSpecificFiltersContainer = $('#type-specific-filters-container');
    const $bifurcationFilterForm = $('#bifurcation-filter-form');
    const $filterPlaceholderMessage = $('#filter-placeholder-message');
    let cachedStaticAnalyticsData = null;
    // columnConfigsGlobal is passed from PHP in index.php

    function initAllSelect2Filters() {
        $mainTypeSelector.select2({
            theme: "bootstrap-5", width: '100%',
            placeholder: "Select Primary Type...", allowClear: true
        });
        $('#filter-plant').select2({
            theme: "bootstrap-5", width: '100%',
            placeholder: "Select Plant...", allowClear: true
        });
        $('#filter-material_no').select2({
            theme: "bootstrap-5", width: '100%',
            placeholder: "Type or Select Material No...", allowClear: true,
            ajax: {
                url: ajaxUrl, dataType: 'json', delay: 300,
                data: function (params) {
                    let query = {
                        action: 'get_distinct_field_values', field: 'material_no',
                        term: params.term || "", page: params.page || 1
                    };
                    const mainType = $mainTypeSelector.val(); if (mainType) query.active_type = mainType;
                    const plant = $('#filter-plant').val(); if (plant) query.active_plant = plant;
                    return query;
                },
                processResults: function (data, params) {
                    if (data.error) { console.error("Material No options error:", data.error); return { results: [] }; }
                    params.page = params.page || 1;
                    return { results: data.results || [], pagination: { more: data.pagination ? data.pagination.more : false } };
                }, cache: true
            }, minimumInputLength: 0
        });
        $('#filter-material_description').select2({
            theme: "bootstrap-5", width: '100%',
            placeholder: "Type to search Description...", allowClear: true,
            ajax: {
                url: ajaxUrl, dataType: 'json', delay: 300,
                data: function (params) {
                    let query = {
                        action: 'get_distinct_field_values', field: 'material_description',
                        term: params.term || "", page: params.page || 1
                    };
                    const mainType = $mainTypeSelector.val(); if (mainType) query.active_type = mainType;
                    const plant = $('#filter-plant').val(); if (plant) query.active_plant = plant;
                    const materialNo = $('#filter-material_no').val(); if(materialNo) query.active_material_no = materialNo;
                    return query;
                },
                processResults: function (data, params) {
                    if (data.error) { console.error("Description options error:", data.error); return { results: [] }; }
                    params.page = params.page || 1;
                    return { results: data.results || [], pagination: { more: data.pagination ? data.pagination.more : false } };
                }, cache: true
            }, minimumInputLength: 1
        });
        $typeSpecificFiltersContainer.find('.select2-dropdown').each(function() {
            if (!$(this).data('select2')) {
                $(this).select2({
                    theme: "bootstrap-5", width: '100%',
                    placeholder: $(this).data('placeholder') || 'Select...', allowClear: true
                });
            }
        });
    }

    function manageFilterVisibility(selectedType) {
        // Hide all type-specific groups first and clear/disable their filters
        $typeSpecificFiltersContainer.find('.type-specific-filter-group').hide();
        $typeSpecificFiltersContainer.find('.type-specific-filter-group select, .type-specific-filter-group input').each(function() {
            const $this = $(this);
            if ($this.data('select2')) { $this.val(null).trigger('change.select2'); }
            else { $this.val(''); }
            $this.prop('disabled', true);
        });

        // If a specific type (not "Other") is selected, show its group and enable its filters
        if (selectedType && selectedType !== "Other") {
            const $targetGroup = $typeSpecificFiltersContainer.find(`.type-specific-filter-group[data-filter-group-for="${selectedType}"]`);
            $targetGroup.show();
            $targetGroup.find('select, input').each(function() {
                const $thisFilter = $(this);
                if ($thisFilter.attr('name') === 'uom') { // UOM is typically fixed and disabled
                    if (selectedType === "Pipes") $thisFilter.val('M').trigger('change.select2');
                    else if (selectedType === "Plate") $thisFilter.val('Kg').trigger('change.select2');
                    else if (selectedType === "Screw Nut Bolt") $thisFilter.val('Pcs').trigger('change.select2');
                    $thisFilter.prop('disabled', true);
                } else {
                    $thisFilter.prop('disabled', false); // Enable other filters in the active group
                }
            });
        }
    }

    function loadDataAndFilters(typeForColumnView = 'initial_static_only') {
        let activeFilters = {};
        const mainTypeSelected = $mainTypeSelector.val();

        if (typeForColumnView !== 'initial_static_only') {
            if (mainTypeSelected) { activeFilters['type'] = mainTypeSelected; }

            $('#filter-material_no, #filter-material_description, #filter-plant').each(function() {
                if ($(this).val() && $(this).val() !== '') {
                    activeFilters[$(this).attr('name')] = $(this).val();
                }
            });

            if (mainTypeSelected && mainTypeSelected !== 'initial' && mainTypeSelected !== 'Other') {
                $(`.type-specific-filter-group[data-filter-group-for="${mainTypeSelected}"]:visible select, .type-specific-filter-group[data-filter-group-for="${mainTypeSelected}"]:visible input`).each(function() {
                    const filterName = $(this).attr('name');
                    const filterValue = $(this).val();
                    if (filterValue && filterValue !== '' && !$(this).prop('disabled')) {
                        if (filterName !== 'type') { activeFilters[filterName] = filterValue; }
                    }
                });
            }
        }

        const colCountCurrent = $('#bifurcation-table-head tr th').length || 3;

        if (typeForColumnView !== 'initial_static_only' && $bifurcationFilterForm.is(":visible")) {
             $('#bifurcation-table-body').html(`<tr><td colspan="${colCountCurrent}" style="text-align:center;">Fetching data...</td></tr>`);
        }

        $.ajax({
            url: ajaxUrl, type: 'POST',
            data: {
                action: 'get_bifurcation_data', filters: activeFilters,
                selected_type_for_view: typeForColumnView
            },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    showUserFeedback('Error: ' + response.error, 'danger');
                    if(typeForColumnView !== 'initial_static_only' && bifurcationTable) {
                         bifurcationTable.clear().draw();
                    }
                    updateAnalytics(null, response.analyticsStatic || cachedStaticAnalyticsData || null, false);
                    return;
                }

                if (response.analyticsStatic) {
                    cachedStaticAnalyticsData = response.analyticsStatic;
                }

                const isTypeActiveForDisplay = (typeForColumnView !== 'initial_static_only' && typeForColumnView !== 'initial' && mainTypeSelected !== "");
                updateAnalytics(response.analyticsDynamic, cachedStaticAnalyticsData, isTypeActiveForDisplay);

                if (typeForColumnView !== 'initial_static_only') {
                    populateFilterOptions(response.filterOptions, activeFilters, typeForColumnView, response.relevantFiltersForView || []);

                    if (response.columnConfig) {
                        let currentColsSignature = "";
                        if (bifurcationTable) { try { currentColsSignature = bifurcationTable.columns().header().map(th => $(th).text()).join(','); } catch(e){} }
                        let newColsSignature = Object.values(response.columnConfig).join(',');

                        const dataToLoad = (typeForColumnView === 'initial' && !mainTypeSelected) ? [] : (response.tableData || []);

                        if (!bifurcationTable || currentColsSignature !== newColsSignature) {
                            initializeDataTable(dataToLoad, response.columnConfig);
                        } else {
                            bifurcationTable.clear().rows.add(dataToLoad).draw(false);
                        }
                    } else {
                        showUserFeedback('Error: Table structure (columnConfig) not received.', 'danger');
                         $('#bifurcation-table-body').html(`<tr><td colspan="${colCountCurrent}" style="text-align:center; color:red;">Table structure error.</td></tr>`);
                    }
                } else if (typeForColumnView === 'initial_static_only' && !bifurcationTable){
                    const initialColsConfig = (typeof columnConfigsGlobal !== 'undefined' && columnConfigsGlobal && columnConfigsGlobal['initial']) ?
                                                columnConfigsGlobal['initial']['displayMap'] :
                                                { 'id': 'ID', 'material_no': 'Material No', 'material_description': 'Description' };
                    initializeDataTable([], initialColsConfig);
                }
            },
            error: function(xhr) {
                console.error("AJAX Error:", xhr.status, xhr.statusText, xhr.responseText);
                let errorMsg = "Error loading data. Please try again.";
                try { const errResp = JSON.parse(xhr.responseText); if(errResp && errResp.error) errorMsg = errResp.error; } catch(e){}
                showUserFeedback(errorMsg, 'danger');
                if(typeForColumnView !== 'initial_static_only' && bifurcationTable) {
                    bifurcationTable.clear().draw();
                } else if (typeForColumnView !== 'initial_static_only') {
                     $('#bifurcation-table-body').html(`<tr><td colspan="3" style="text-align:center; color:red;">${escapeHtml(errorMsg)}</td></tr>`);
                }
                updateAnalytics(null, cachedStaticAnalyticsData, false);
            }
        });
    }

    function initializeDataTable(initialData, columnConfig) {
        const dataTableColumns = [];
        if (columnConfig && Object.keys(columnConfig).length > 0) {
            for (const key in columnConfig) {
                dataTableColumns.push({ data: key, title: escapeHtml(columnConfig[key]), defaultContent: "" });
            }
        } else {
            dataTableColumns.push({ data: 'id', title: 'ID', defaultContent: "" });
            dataTableColumns.push({ data: 'material_no', title: 'Material No', defaultContent: "" });
            dataTableColumns.push({ data: 'material_description', title: 'Description', defaultContent: "" });
        }

        if (bifurcationTable) { bifurcationTable.destroy(); }
        $('#bifurcation-table-head').empty();
        $('#bifurcation-table-body').empty();

        const $thead = $('#bifurcation-table-head');
        let headerRow = '<tr>';
        dataTableColumns.forEach(col => headerRow += `<th>${col.title}</th>`);
        headerRow += '</tr>'; $thead.append(headerRow);

        bifurcationTable = $('#bifurcationTable').DataTable({
            data: initialData,
            columns: dataTableColumns,
            responsive: true,
            scrollX: true,
            scrollY: '60vh', 
            scrollCollapse: true, 
            lengthMenu: [[25, 50, 100, 500], [25, 50, 100, 500]], pageLength: 25,
            dom: "<'row'<'col-sm-12'tr>>" +
                 "<'row dt-footer-row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            language: {
                emptyTable: "Please select a category from the Analytics Overview to view data.",
                zeroRecords: "No matching records found."
            },
            deferRender: true,
            initComplete: function() {
                const $lengthContainer = $('#lengthMenuContainerBifurcation');
                $lengthContainer.empty();
                const $label = $('<label class="form-label me-2 mb-0">Show: </label>');
                const $select = $('<select class="form-select form-select-sm"></select>');

                const dtSettings = this.api().settings()[0];
                const lengths = dtSettings.aLengthMenu[0];
                const displayLengths = dtSettings.aLengthMenu[1];

                lengths.forEach((len, i) => {
                    $select.append($('<option></option>').attr('value', len).text(displayLengths[i]));
                });

                try { $select.val(this.api().page.len());  } catch(e) { $select.val(lengths[0]); }

                $select.on('change', function () {
                    if(bifurcationTable) bifurcationTable.page.len($(this).val()).draw();
                });
                $lengthContainer.append($label).append($select).append('<span class="ms-2 entries-text">entries</span>');
            }
        });
    }

    function updateAnalytics(dynamicData, staticData, isTypeActive = false) {
        const promptMsg = "Click to View";
        const sData = staticData || { 'Screw Nut Bolt': 0, Pipes: 0, Plate: 0, Other: 0 };
        const dDataEffective = isTypeActive && dynamicData ? dynamicData : { 'Screw Nut Bolt': 0, Pipes: 0, Plate: 0, Other: 0 };

        let staticTotal = 0;
        let dynamicTotal = 0;

        const sFasteners = parseInt(sData['Screw Nut Bolt'] || 0);
        $('#fastener-total-static-count').text(sFasteners);
        staticTotal += sFasteners;
        if (isTypeActive) {
            const dFasteners = parseInt(dDataEffective['Screw Nut Bolt'] || 0);
            $('#fastener-filtered-count').removeClass('prompt-message').text(dFasteners);
            dynamicTotal += dFasteners;
        } else {
            $('#fastener-filtered-count').addClass('prompt-message').text(promptMsg);
        }

        const sPipes = parseInt(sData.Pipes || 0);
        $('#pipe-total-static-count').text(sPipes);
        staticTotal += sPipes;
        if (isTypeActive) {
            const dPipes = parseInt(dDataEffective.Pipes || 0);
            $('#pipe-filtered-count').removeClass('prompt-message').text(dPipes);
            dynamicTotal += dPipes;
        } else {
            $('#pipe-filtered-count').addClass('prompt-message').text(promptMsg);
        }

        const sPlates = parseInt(sData.Plate || 0);
        $('#plate-total-static-count').text(sPlates);
        staticTotal += sPlates;
        if (isTypeActive) {
            const dPlates = parseInt(dDataEffective.Plate || 0);
            $('#plate-filtered-count').removeClass('prompt-message').text(dPlates);
            dynamicTotal += dPlates;
        } else {
            $('#plate-filtered-count').addClass('prompt-message').text(promptMsg);
        }

        const sOther = parseInt(sData.Other || 0);
        $('#other-total-static-count').text(sOther);
        staticTotal += sOther;
        if (isTypeActive) {
            const dOther = parseInt(dDataEffective.Other || 0);
            $('#other-filtered-count').removeClass('prompt-message').text(dOther);
            dynamicTotal += dOther;
        } else {
            $('#other-filtered-count').addClass('prompt-message').text(promptMsg);
        }

        $('#total-item-static-count').text(staticTotal);
        if (isTypeActive) {
            $('#total-item-filtered-count').removeClass('prompt-message').text(dynamicTotal);
        } else {
            $('#total-item-filtered-count').addClass('prompt-message').text('-');
        }
    }

    function populateFilterOptions(filterOptionsFromServer, currentSelections, currentViewType, relevantFiltersForView) {
        function populateSelect($selectElement, options, currentValFromForm, defaultText) {
            if (!$selectElement.length) return;
            const originalVal = $selectElement.val();
            $selectElement.empty().append(`<option value="">${defaultText}</option>`);
            if (options && Array.isArray(options)) {
                options.forEach(opt => {
                    if (opt !== null && opt !== undefined) {
                         $selectElement.append($('<option></option>').attr('value', opt).text(escapeHtml(String(opt)) ));
                    }
                });
            }
            let valueToSet = currentValFromForm !== undefined ? currentValFromForm : (originalVal || "");
             if (valueToSet === null) valueToSet = "";

            if (options && options.map(String).includes(String(valueToSet))) {
                $selectElement.val(String(valueToSet));
            } else {
                 $selectElement.val("");
            }
            if ($selectElement.hasClass('select2-dropdown') && !$selectElement.hasClass('select2-ajax-dropdown') && $selectElement.data('select2')) {
                $selectElement.trigger('change.select2');
            }
        }

        if (relevantFiltersForView.includes('plant') || currentViewType === 'initial_static_only' || currentViewType === 'initial') {
             populateSelect($('#filter-plant'), filterOptionsFromServer.plant, currentSelections.plant, "Select Plant...");
        }

        if (currentViewType && currentViewType !== 'initial' && currentViewType !== 'Other' && relevantFiltersForView) {
            const $visibleGroup = $(`.type-specific-filter-group[data-filter-group-for="${currentViewType}"]:visible`);
            relevantFiltersForView.forEach(filterKey => {
                if (['plant', 'type', 'material_no', 'material_description'].includes(filterKey)) return;

                const $filterElement = $visibleGroup.find(`select[name="${filterKey}"]`);
                if ($filterElement.length) {
                    if (filterKey === 'uom') { /* UOM handled by manageFilterVisibility */ }
                    else if (filterOptionsFromServer[filterKey]) {
                        populateSelect($filterElement, filterOptionsFromServer[filterKey], currentSelections[filterKey], $filterElement.data('placeholder') || `Select ${filterKey}...`);
                    } else {
                         populateSelect($filterElement, [], currentSelections[filterKey], $filterElement.data('placeholder') || `Select ${filterKey}...`);
                    }
                }
            });
        } else if (currentViewType !== 'initial_static_only') {
             $typeSpecificFiltersContainer.find('.select2-dropdown').each(function(){
                 if(!$(this).prop('disabled') && ($(this).attr('name') !== 'uom') && !['filter-main_type', 'filter-plant', 'filter-material_no', 'filter-material_description'].includes($(this).attr('id')) ){
                      populateSelect($(this), [], null, $(this).data('placeholder') || 'Select...');
                 }
            });
        }
    }

    // --- Event Handlers ---
    $('.analytic-card-button').on('click', function() {
        const typeToSelect = $(this).data('type');
        if (typeToSelect) {
            // Check if the type is actually changing to avoid unnecessary filter clearing
            if ($mainTypeSelector.val() !== typeToSelect) {
                // Clear common filters explicitly when an analytic card causes a type change
                $('#filter-material_no').val(null).trigger('change.select2');
                $('#filter-material_description').val(null).trigger('change.select2');
                $('#filter-plant').val(null).trigger('change.select2');
            }
            $filterPlaceholderMessage.hide();
            $bifurcationFilterForm.show();
            $mainTypeSelector.val(typeToSelect).trigger('change'); // This will call the .on('change') handler below
        }
    });

    $mainTypeSelector.on('change', function() {
        const selectedType = $(this).val();

        // MODIFIED: Clear common filters if a new main type is selected.
        // This logic is now also partially triggered by the analytic card click setting the values first.
        // The manageFilterVisibility function already handles clearing type-specific filters.
        // We ensure common filters are cleared here when the main type selection truly finalizes.
        // This check helps avoid clearing if the event is somehow triggered without a real type change.
        // However, the primary clearing for analytics card clicks is handled above to ensure it happens *before* this event.
        // For direct changes to the (hidden) dropdown, this ensures common filters are also cleared.
        
        // No, the clearing of common filters should happen once when the type *changes*.
        // The analytic card click handler now clears common filters if type changes.
        // The $mainTypeSelector.on('change') will then proceed.
        // If the change is to a new type, type-specific filters are handled by manageFilterVisibility.

        manageFilterVisibility(selectedType); // Handles clearing old type-specific and enabling new ones

        if (selectedType) {
            $filterPlaceholderMessage.hide();
            $bifurcationFilterForm.show();
            loadDataAndFilters(selectedType);
        } else {
            // No main type selected (e.g., user cleared the main type selector via "Clear All" or if it were visible)
            $filterPlaceholderMessage.show();
            $bifurcationFilterForm.hide();

            // Clear common filters as well when going back to no type selected.
            $('#filter-material_no').val(null).trigger('change.select2');
            $('#filter-material_description').val(null).trigger('change.select2');
            $('#filter-plant').val(null).trigger('change.select2');
            // Type-specific filters are already handled by manageFilterVisibility(null)

            if(bifurcationTable) {
                bifurcationTable.clear().draw();
                const initialCols = (typeof columnConfigsGlobal !== 'undefined' && columnConfigsGlobal && columnConfigsGlobal['initial'])
                                    ? columnConfigsGlobal['initial']['displayMap']
                                    : {'id':'ID', 'material_no':'Material No', 'material_description':'Description'};
                initializeDataTable([], initialCols);
            }
            updateAnalytics(null, cachedStaticAnalyticsData, false);
        }
    });

    $('#clear-filters-bifurcation-button').on('click', function() {
        const currentMainType = $mainTypeSelector.val();

        $('#filter-material_no, #filter-material_description, #filter-plant').val(null).trigger('change.select2');
        $typeSpecificFiltersContainer.find('.select2-dropdown:not([name="uom"])').val(null).trigger('change.select2');
        $typeSpecificFiltersContainer.find('input[type="text"]').val('');


        if (currentMainType) {
            // A main type is selected, so we only cleared its sub-filters and common filters.
            // Reload data for that main type. manageFilterVisibility was already called when type was set.
            // We need to ensure manageFilterVisibility is correctly recalled or state is correct.
            // Calling it again is safe.
            manageFilterVisibility(currentMainType); // Resets UOM and enables fields for current type
            loadDataAndFilters(currentMainType);
            showUserFeedback('Attribute filters cleared for ' + currentMainType + '. Displaying all ' + currentMainType + ' items.', 'info');
        } else {
            // No main type was selected, so fully revert to the initial page state
            // $mainTypeSelector is already null if we are in this branch after clearing common filters
            // manageFilterVisibility(null) would have been called by .trigger('change') if $mainTypeSelector was cleared.
            // If $mainTypeSelector was already null, ensure the full reset happens:
            $mainTypeSelector.val(null).trigger('change'); // This effectively calls the else block of $mainTypeSelector.on('change')
            showUserFeedback('Filters cleared. Please select a category from Analytics Overview.', 'info');
        }
    });

    $('#filter-material_no, #filter-material_description, #filter-plant').on('change', function() {
        const currentMainType = $mainTypeSelector.val();
        if ($bifurcationFilterForm.is(":visible") && currentMainType) {
             loadDataAndFilters(currentMainType);
        } else if ($bifurcationFilterForm.is(":visible") && !currentMainType && $(this).attr('id') === 'filter-plant') {
            loadDataAndFilters('initial');
        }
    });

    $typeSpecificFiltersContainer.on('change', 'select:not([name="uom"])', debounce(function() {
        const currentMainType = $mainTypeSelector.val();
        if (currentMainType) { loadDataAndFilters(currentMainType); }
    }, 500));
    $typeSpecificFiltersContainer.on('input keyup', 'input[type="text"]', debounce(function() {
        const currentMainType = $mainTypeSelector.val();
        if (currentMainType) { loadDataAndFilters(currentMainType); }
    }, 500));

    // Initial Setup
    initAllSelect2Filters();
    manageFilterVisibility(null);
    $bifurcationFilterForm.hide();
    $filterPlaceholderMessage.show();

    loadDataAndFilters('initial_static_only');

});

// Debounce, escapeHtml, showUserFeedback functions
function debounce(func, wait) { let timeout; return function(...args) { const context = this; clearTimeout(timeout); timeout = setTimeout(() => func.apply(context, args), wait); }; }
function escapeHtml(unsafe) { if (unsafe === null || typeof unsafe === 'undefined') { return ''; } return String(unsafe).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;"); }
function showUserFeedback(message, type = 'info', duration = 5000) { const $userFeedback = $('#userFeedback'); const alertId = 'alert-' + Date.now(); const feedbackHtml = `<div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">${escapeHtml(message)}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`; $userFeedback.append(feedbackHtml); if (duration > 0) { setTimeout(function() { $('#' + alertId).alert('close'); }, duration); } }