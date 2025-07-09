<?php
// data_handler.php (Optimized for Performance)
header('Content-Type: application/json');
// error_reporting(E_ALL); ini_set('display_errors', 1); // Uncomment for development

require_once 'db_connection.php'; 
require_once 'bifurcation_utils.php'; 
require_once 'config.php'; 

// --- Optimized Helper Functions ---

function getStaticCategoryTotals($pdo) {
    $totals = [ 'Pipes' => 0, 'Plate' => 0, 'Screw Nut Bolt' => 0, 'Other' => 0 ];
    $screw_prefixes_sql_array = ['20', '13', '17', '19', '21', '24', '26'];
    $screw_placeholders = implode(',', array_fill(0, count($screw_prefixes_sql_array), '?'));

    $sql = "SELECT 
                CASE 
                    WHEN SUBSTR(material_no, 1, 2) IN ($screw_placeholders) THEN 'Screw Nut Bolt'
                    WHEN SUBSTR(material_no, 1, 2) = ? THEN 'Plate' -- for '31'
                    WHEN SUBSTR(material_no, 1, 2) = ? THEN 'Pipes' -- for '25'
                    ELSE 'Other' 
                END as bifurcation_type,
                COUNT(*) as type_count
            FROM item_groups
            GROUP BY bifurcation_type";
    
    $params = array_merge($screw_prefixes_sql_array, ['31', '25']);

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($totals[$row['bifurcation_type']])) {
                $totals[$row['bifurcation_type']] = (int)$row['type_count'];
            }
        }
    } catch (PDOException $e) {
        error_log("Error in getStaticCategoryTotals: " . $e->getMessage());
    }
    return $totals;
}

function finalizeAttributesBasedOnType($bifurcationType, $extractedAttributes) {
    $final = [];
    $final['standard'] = $extractedAttributes['standard_extracted'] ?? null;
    $final['length'] = $extractedAttributes['length'] ?? null;
    $final['class'] = $extractedAttributes['class'] ?? null;
    $final['grade'] = $extractedAttributes['grade'] ?? null;
    $final['nb'] = $extractedAttributes['nb'] ?? null;
    $final['od'] = $extractedAttributes['od'] ?? null;
    $final['diameter'] = $extractedAttributes['diameter'] ?? null;
    $final['thickness'] = null; 

    switch ($bifurcationType) {
        case "Screw Nut Bolt": $final['uom'] = 'Pcs'; $final['diameter'] = $extractedAttributes['diameter'] ?? $final['diameter']; break;
        case "Plate": $final['uom'] = 'Kg'; $final['thickness'] = $extractedAttributes['thickness_plate'] ?? null; break;
        case "Pipes": $final['uom'] = 'M'; $final['nb'] = $extractedAttributes['nb'] ?? $final['nb']; $final['od'] = $extractedAttributes['od'] ?? $final['od']; $final['thickness'] = $extractedAttributes['thickness_pipe'] ?? null; break;
        default: $final['uom'] = $extractedAttributes['uom_extracted'] ?? null; $final['diameter'] = $extractedAttributes['diameter'] ?? $final['diameter']; $final['nb'] = $extractedAttributes['nb'] ?? $final['nb']; $final['od'] = $extractedAttributes['od'] ?? $final['od']; $final['thickness'] = $extractedAttributes['thickness_pipe'] ?? $extractedAttributes['thickness_plate'] ?? null; break;
    }
    return $final;
 }

function passesPostParsingFilters($item, $filters) { /* ... same as previous working version ... */
    foreach ($filters as $filterKey => $filterValue) {
        if (empty($filterValue) || in_array($filterKey, ['plant', 'material_no', 'material_description', 'type', 'main_type'])) {
            continue; 
        }
        $itemValueToCompare = null;
        if ($filterKey === 'thickness') {
            $itemValueToCompare = $item['thickness'] ?? null; 
        } else {
            $itemValueToCompare = $item[$filterKey] ?? null;
        }

        if ($itemValueToCompare === null && $filterKey !== 'type') return false; 

        if ($itemValueToCompare !== null) { 
            if (is_string($itemValueToCompare) && is_string($filterValue)) {
                if (stripos($itemValueToCompare, $filterValue) === false) {
                    return false;
                }
            } elseif ($itemValueToCompare != $filterValue) {
                return false;
            }
        }
    }
    return true;
 }

function collectFilterableValues($item, &$allFilterableValues, $relevantFilterKeys) {
    // Collect for 'plant' if it's relevant or always needed for its own dropdown
    if (!empty($item['plant']) && (empty($relevantFilterKeys) || in_array('plant', $relevantFilterKeys))) {
        if (!isset($allFilterableValues['plant'])) $allFilterableValues['plant'] = [];
        $allFilterableValues['plant'][] = $item['plant'];
    }

    foreach($relevantFilterKeys as $key) {
        if (in_array($key, ['plant', 'type', 'material_no', 'material_description'])) continue; 
        $dataKey = $key; 
        if (isset($item[$dataKey]) && $item[$dataKey] !== null && (string)$item[$dataKey] !== '') {
            if (!isset($allFilterableValues[$key])) $allFilterableValues[$key] = [];
            $allFilterableValues[$key][] = $item[$dataKey];
        }
    }
 }
function initializeFilterableValuesArray() { /* ... same as previous ... */ 
    return [
        'plant' => [], 'nb' => [], 'od' => [], 'diameter' => [],
        'thickness' => [], 'length' => [], 'class' => [], 'grade' => [],
        'standard' => [], 'uom' => []
    ];
}
function generateDistinctFilterOptions($allFilterableValues) { /* ... same as previous ... */ 
    $distinctFilterOptions = [];
    foreach ($allFilterableValues as $key => $values) {
        if (!empty($values)) {
            $uniqueVals = array_values(array_unique($values));
            sort($uniqueVals, SORT_NATURAL | SORT_FLAG_CASE); 
            $distinctFilterOptions[$key] = $uniqueVals;
        } else {
            $distinctFilterOptions[$key] = [];
        }
    }
    return $distinctFilterOptions;
}
function calculateAnalytics($itemsCollection) { /* ... same as previous (uses getBifurcationType if item['type'] not set) ... */
    $counts = [ 'Pipes' => 0, 'Plate' => 0, 'Screw Nut Bolt' => 0, 'Other' => 0 ];
    if (empty($itemsCollection)) return $counts;

    foreach ($itemsCollection as $item) {
        $itemType = $item['type'] ?? getBifurcationType($item['material_no']); 
        if (isset($counts[$itemType])) {
            $counts[$itemType]++;
        }
    }
    return $counts;
 }

// --- Main Data Fetching Logic ---
function getBifurcationModuleData($pdo, $filters = [], $typeForView = 'initial') {
    global $columnConfigs;
    $staticAnalytics = getStaticCategoryTotals($pdo); 

    if ($typeForView === 'initial_static_only') {
        $initialPlantOptions = [];
        $stmtPlant = $pdo->query("SELECT DISTINCT plant FROM item_groups WHERE plant IS NOT NULL AND plant != '' ORDER BY plant ASC");
        $initialPlantOptions = $stmtPlant->fetchAll(PDO::FETCH_COLUMN);

        return [
            'tableData' => [], 'filterOptions' => ['plant' => $initialPlantOptions], 
            'analyticsStatic' => $staticAnalytics,
            'analyticsDynamic' => ['Pipes' => 0, 'Plate' => 0, 'Screw Nut Bolt' => 0, 'Other' => 0],
            'columnConfig' => $columnConfigs['initial']['displayMap'], 
            'relevantFiltersForView' => $columnConfigs['initial']['relevantFilters'],
            'allColumnConfigs' => $columnConfigs 
        ];
    }

    $baseSql = "SELECT id, material_no, material_description, plant FROM item_groups";
    $whereClauses = [];
    $executeParams = []; 

    if (!empty($filters['plant'])) { $whereClauses[] = "plant = ?"; $executeParams[] = $filters['plant']; }
    if (!empty($filters['material_no'])) { $whereClauses[] = "material_no = ?"; $executeParams[] = $filters['material_no']; }
    if (!empty($filters['material_description'])) { 
        $whereClauses[] = "material_description LIKE ?"; // For main table filter
        $executeParams[] = '%' . $filters['material_description'] . '%';
    }

    $mainTypeFilterValue = $filters['type'] ?? ($typeForView !== 'initial' ? $typeForView : null);
    if ($mainTypeFilterValue) {
        $prefixes = [];
        if ($mainTypeFilterValue === "Screw Nut Bolt") $prefixes = ['20', '13', '17', '19', '21', '24', '26'];
        elseif ($mainTypeFilterValue === "Plate") $prefixes = ['31'];
        elseif ($mainTypeFilterValue === "Pipes") $prefixes = ['25'];
        
        if (!empty($prefixes)) {
            $inQueryPlaceholders = implode(',', array_fill(0, count($prefixes), '?'));
            $whereClauses[] = "SUBSTR(material_no, 1, 2) IN (" . $inQueryPlaceholders . ")";
            foreach ($prefixes as $p) { $executeParams[] = $p; }
        } elseif ($mainTypeFilterValue === "Other") {
            $knownPrefixes = ['20', '13', '17', '19', '21', '24', '26', '31', '25'];
            $notInQueryPlaceholders = implode(',', array_fill(0, count($knownPrefixes), '?'));
            $whereClauses[] = "SUBSTR(material_no, 1, 2) NOT IN (" . $notInQueryPlaceholders . ")";
            foreach ($knownPrefixes as $p) { $executeParams[] = $p; }
        }
    }

    $currentDataSql = $baseSql;
    if (!empty($whereClauses)) { $currentDataSql .= " WHERE " . implode(" AND ", $whereClauses); }
    $currentDataSql .= " ORDER BY id ASC"; 
    
    $stmtData = $pdo->prepare($currentDataSql);
    $stmtData->execute($executeParams);
    $itemsMatchingSqlFilters = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    $processedTableItems = []; 
    $allFilterableValues = initializeFilterableValuesArray(); 

    foreach ($itemsMatchingSqlFilters as $dbItem) {
        $bifurcationType = getBifurcationType($dbItem['material_no']);
        if ($mainTypeFilterValue && $mainTypeFilterValue !== 'initial') {
            if ($mainTypeFilterValue === "Other" && in_array($bifurcationType, ["Pipes", "Plate", "Screw Nut Bolt"])) continue;
            elseif ($bifurcationType !== $mainTypeFilterValue && $mainTypeFilterValue !== "Other") continue;
        }
        $extractedAttributes = extractAttributesFromDescription($dbItem['material_description'], $bifurcationType);
        $finalAttributes = finalizeAttributesBasedOnType($bifurcationType, $extractedAttributes);
        $currentItemData = array_merge($dbItem, ['type' => $bifurcationType], $finalAttributes);

        if (passesPostParsingFilters($currentItemData, $filters)) {
            $processedTableItems[] = $currentItemData;
        }
    }
    
    // OPTIMIZATION: Populate filter options for parsed attributes ONLY from the $processedTableItems
    $configKeyForRelevantFilters = $mainTypeFilterValue ?: ($typeForView !== 'initial_static_only' ? $typeForView : 'initial');
    if(!isset($columnConfigs[$configKeyForRelevantFilters])) $configKeyForRelevantFilters = 'initial';
    $relevantFiltersForCurrentView = $columnConfigs[$configKeyForRelevantFilters]['relevantFilters'] ?? [];

    foreach($processedTableItems as $itemToConsiderForOption) { 
        collectFilterableValues($itemToConsiderForOption, $allFilterableValues, $relevantFiltersForCurrentView);
    }
    $distinctFilterOptions = generateDistinctFilterOptions($allFilterableValues);
    
    // For Plant filter, get all distinct plants if no plant filter is active, or only the selected one
    if (empty($filters['plant'])) {
        $stmtPlantAll = $pdo->query("SELECT DISTINCT plant FROM item_groups WHERE plant IS NOT NULL AND plant != '' ORDER BY plant ASC");
        $distinctFilterOptions['plant'] = $stmtPlantAll->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $distinctFilterOptions['plant'] = [$filters['plant']]; // Show only the selected one
    }


    $dynamicAnalyticsCounts = calculateAnalytics($processedTableItems); 

    return [
        'tableData' => $processedTableItems, 'filterOptions' => $distinctFilterOptions,
        'analyticsStatic' => $staticAnalytics, 'analyticsDynamic' => $dynamicAnalyticsCounts, 
        'relevantFiltersForView' => $relevantFiltersForCurrentView,
        'allColumnConfigs' => ($typeForView === 'initial_static_only') ? $columnConfigs : null
    ];
}

// ACTION for Select2 AJAX (Material No, Plant, Material Description)
if (isset($_GET['action']) && $_GET['action'] == 'get_distinct_field_values' && isset($_GET['field'])) {
    global $pdo; 
    $fieldToQuery = $_GET['field']; 
    $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
    // $page = isset($_GET['page']) ? intval($_GET['page']) : 1; // For Select2 pagination
    $limit = 50; // Max results for dropdown suggestions
    // $offset = ($page - 1) * $limit;

    $allowedDirectFields = ['material_no', 'plant', 'material_description']; 
    if (!in_array($fieldToQuery, $allowedDirectFields)) {
        echo json_encode(['results' => [], 'error' => 'Invalid field.']); exit;
    }

    $sql = "SELECT DISTINCT `$fieldToQuery` as id, `$fieldToQuery` as text 
            FROM item_groups 
            WHERE `$fieldToQuery` IS NOT NULL AND `$fieldToQuery` != ''";
    $executeParams = []; 

    if (!empty($searchTerm)) {
        if ($fieldToQuery === 'material_description') { 
            $sql .= " AND `$fieldToQuery` LIKE ?"; 
            $executeParams[] = $searchTerm . '%'; // "Starts with" for description suggestions
        } else { 
            $sql .= " AND `$fieldToQuery` LIKE ?"; 
            $executeParams[] = '%' . $searchTerm . '%'; // "Contains" for material_no, plant suggestions
        }
    }

    // Contextual filtering
    if (!empty($_GET['active_type'])) {
        $type = $_GET['active_type']; $prefixes = [];
        if ($type === "Screw Nut Bolt") $prefixes = ['20', '13', '17', '19', '21', '24', '26'];
        elseif ($type === "Plate") $prefixes = ['31'];
        elseif ($type === "Pipes") $prefixes = ['25'];
        
        if (!empty($prefixes)) {
            $inQueryPlaceholders = implode(',', array_fill(0, count($prefixes), '?'));
            $sql .= " AND SUBSTR(material_no, 1, 2) IN ($inQueryPlaceholders)";
            foreach($prefixes as $p) $executeParams[] = $p;
        } elseif ($type === "Other") {
            $knownPrefixes = ['20', '13', '17', '19', '21', '24', '26', '31', '25'];
            $notInQueryPlaceholders = implode(',', array_fill(0, count($knownPrefixes), '?'));
            $sql .= " AND SUBSTR(material_no, 1, 2) NOT IN ($notInQueryPlaceholders)";
            foreach($knownPrefixes as $p) $executeParams[] = $p;
        }
    }
    if (!empty($_GET['active_plant']) && $fieldToQuery !== 'plant') {
        $sql .= " AND plant = ?";
        $executeParams[] = $_GET['active_plant'];
    }
    if (!empty($_GET['active_material_no']) && $fieldToQuery === 'material_description') {
        $sql .= " AND material_no = ?";
        $executeParams[] = $_GET['active_material_no'];
    }
    
    $sql .= " ORDER BY `$fieldToQuery` ASC LIMIT ?"; // Removed OFFSET for simplicity, Select2 pagination handles 'more'
    $executeParams[] = $limit;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($executeParams);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $k => $v) { if ($results[$k]['text'] === null) { $results[$k]['text'] = ''; }}
        // Simple 'more' logic: if we hit the limit, assume there might be more.
        // Select2 will request next 'page' if user scrolls and 'more' is true.
        $hasMore = count($results) === $limit; 
        echo json_encode(['results' => $results, 'pagination' => ['more' => $hasMore]]);
    } catch (PDOException $e) {
        error_log("Error in get_distinct_field_values for $fieldToQuery: " . $e->getMessage());
        echo json_encode(['results' => [], 'error' => 'Could not load results. DB Error.']);
    }
    exit;
}

// Main execution for POST requests (get_bifurcation_data)
if (isset($_POST['action']) && $_POST['action'] == 'get_bifurcation_data') {
    // ... (main try-catch block from previous response, calling getBifurcationModuleData) ...
    try {
        global $pdo, $columnConfigs;
        $filtersFromPost = $_POST['filters'] ?? [];
        $selectedTypeForView = $_POST['selected_type_for_view'] ?? 'initial';

        $responseData = getBifurcationModuleData($pdo, $filtersFromPost, $selectedTypeForView);
        
        $configKey = $selectedTypeForView;
        if ($selectedTypeForView === 'initial_static_only') { $configKey = 'initial'; } 
        elseif (!isset($columnConfigs[$configKey])) { $configKey = 'initial'; }

        $responseData['columnConfig'] = $columnConfigs[$configKey]['displayMap'];
        $responseData['relevantFiltersForView'] = $columnConfigs[$configKey]['relevantFilters'] ?? [];
        
        if($selectedTypeForView === 'initial_static_only' && !isset($responseData['allColumnConfigs'])) {
             $responseData['allColumnConfigs'] = $columnConfigs;
        }

        echo json_encode($responseData);

    } catch (PDOException $e) { 
        error_log("PDOException in main handler: " . $e->getMessage());
        echo json_encode(['error' => 'Database error processing request.']);
    } catch (Exception $e) { 
        error_log("Exception in main handler: " . $e->getMessage());
        echo json_encode(['error' => 'Application error processing request.']);
    }
    exit;
}
?>