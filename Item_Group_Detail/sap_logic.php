<?php
// sap_logic.php
// Implements "smart sync":
// 1. Checks if local 'item_groups' table is empty. If so, performs full load (truncate + insert).
// 2. If not empty, compares current SAP total count with last synced SAP total count.
// 3. Fetches all from SAP ONLY if local table is empty OR if SAP total count has increased.
// 4. Uses INSERT IGNORE if local table was not empty (no truncate).
// Relies on a UNIQUE constraint in the DB table for the 7 unique fields.
// MODIFIED: add_session_feedback made stricter for SHOW_DETAILED_SYNC_FEEDBACK = false to only show 'danger' type.
// MODIFIED: Added server-side cap for DataTables "All" entries request & updated DATATABLES_MAX_RECORDS_FOR_ALL.

// Start output buffering at the very beginning
ob_start();

// --- ERROR REPORTING ---
error_reporting(E_ALL);
ini_set('display_errors', 1); 
ini_set('log_errors', 1); 
// ini_set('error_log', '/path/to/your/php-error.log'); 

// --- CONFIGURATION CONSTANTS ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'simplexinternal');
define('DB_TABLE_NAME', 'item_groups'); 

define('SAP_ODATA_URL', 'https://my412439-api.s4hana.cloud.sap/sap/opu/odata/sap/ZMM_60_BIN/ZMM_60_CDS'); 
define('SAP_USERNAME', 'ZNOVELSH412439');
define('SAP_PASSWORD', 'PEWMsSS$Pv3TSonFJlFUYJiEmcCfVmXcpzaMfeHw');

define('SAP_PAGE_SIZE', 5000); 
define('DB_MULTI_INSERT_SQL_BATCH_SIZE', 500); 
// Default to false for silent operation as requested
define('SHOW_DETAILED_SYNC_FEEDBACK', false); 

// Metadata table and keys
define('SYNC_METADATA_TABLE_NAME', 'sync_metadata');
define('METADATA_KEY_LAST_SAP_TOTAL_COUNT', 'last_sap_total_count');

define('SAP_DB_FIELD_MAPPING', [
    'material_no'             => 'Product',
    'material_description'    => 'YY1_MaterialLongDescri_PRD',
    'plant'                   => 'Plant',
    'material_group_no'       => 'ProductGroup',
    'material_group_name'     => 'ProductGroupName',
    'external_group_no'       => 'ExternalProductGroup',
    'external_group_name'     => 'ExternalProductGroupName'
]);

// Maximum number of records to return if DataTables requests "All" (-1)
define('DATATABLES_MAX_RECORDS_FOR_ALL', 1000); 

// --- SESSION MANAGEMENT ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- HELPER FUNCTIONS ---
// MODIFIED: Stricter conditions for adding messages to session when SHOW_DETAILED_SYNC_FEEDBACK is false
function add_session_feedback($msg, $type = 'info') {
    // Always log important messages to server error log for backend debugging,
    // especially if they might be suppressed from the frontend.
    // Log non-'info' types, or all types if detailed feedback is on.
    if ($type !== 'info' || SHOW_DETAILED_SYNC_FEEDBACK) {
        error_log("PHP App Feedback Attempt (Type: {$type}): " . $msg);
    } else if ($type === 'info' && !SHOW_DETAILED_SYNC_FEEDBACK) { // Specifically log suppressed info messages
        error_log("Suppressed Session Feedback (Type: info, not logged above): " . $msg);
    }

    if (!SHOW_DETAILED_SYNC_FEEDBACK) {
        // In non-detailed (QUIET) mode, ONLY allow 'danger' messages to be added to the session for frontend display.
        if ($type !== 'danger') { 
            // For all other types (info, success, primary, warning), do not add to session.
            return; 
        }
    }

    // If SHOW_DETAILED_SYNC_FEEDBACK is true, OR 
    // if SHOW_DETAILED_SYNC_FEEDBACK is false AND message type is 'danger', then add to session.
    if (!isset($_SESSION['sync_feedback_messages'])) {
        $_SESSION['sync_feedback_messages'] = [];
    }
    // Limit total messages in session
    if (count($_SESSION['sync_feedback_messages']) > 20) { // Reduced limit as mostly errors will show in quiet mode
        array_shift($_SESSION['sync_feedback_messages']); 
    }
    $_SESSION['sync_feedback_messages'][] = ['type' => $type, 'text' => "[" . date('Y-m-d H:i:s') . "] " . $msg];
}


function connect_to_mysql_php() {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); 
    try {
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        $conn->set_charset("utf8mb4");
        $conn->autocommit(FALSE); 
        error_log("MySQL connection successful.");
        return $conn;
    } catch (mysqli_sql_exception $e) {
        add_session_feedback("MySQL Connection Failed: " . $e->getMessage(), 'danger'); 
        error_log("MySQL Connection Failed: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
        return false;
    }
}

function clean_record_value_php($value) {
    if ($value === null) return null;
    if (is_array($value) || is_object($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
    return (string)$value;
}

// --- Metadata Functions ---
function get_metadata_value($conn, $meta_key) {
    try {
        $stmt = $conn->prepare("SELECT meta_value FROM `" . SYNC_METADATA_TABLE_NAME . "` WHERE meta_key = ?");
        if (!$stmt) { throw new Exception("Metadata get prepare error: " . $conn->error); }
        $stmt->bind_param("s", $meta_key);
        if (!$stmt->execute()) { throw new Exception("Metadata get execute error: " . $stmt->error); }
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? $row['meta_value'] : null;
    } catch (Exception $e) {
        error_log("Error in get_metadata_value for key {$meta_key}: " . $e->getMessage());
        add_session_feedback("Error getting metadata ('{$meta_key}'): " . $e->getMessage(), 'danger'); 
        return null;
    }
}

function set_metadata_value($conn, $meta_key, $meta_value) {
    try {
        $sql = "INSERT INTO `" . SYNC_METADATA_TABLE_NAME . "` (meta_key, meta_value) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { throw new Exception("Metadata set prepare error: " . $conn->error); }
        $stmt->bind_param("ss", $meta_key, $meta_value);
        $success = $stmt->execute();
        if (!$success) { throw new Exception("Metadata set execute error: " . $stmt->error); }
        $stmt->close();
        return true;
    } catch (Exception $e) {
        error_log("Error in set_metadata_value for key {$meta_key}: " . $e->getMessage());
        add_session_feedback("Error setting metadata ('{$meta_key}'): " . $e->getMessage(), 'danger'); 
        return false;
    }
}

// --- SAP Data Functions ---
function get_current_sap_total_record_count() { 
    $count_url = SAP_ODATA_URL . '?$top=0&$inlinecount=allpages&$format=json';
    add_session_feedback("Fetching current total record count from SAP...", 'info'); 
    error_log("Attempting to fetch current total record count from SAP: " . $count_url);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $count_url, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => SAP_USERNAME . ":" . SAP_PASSWORD,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Requested-With: XMLHttpRequest'],
        CURLOPT_TIMEOUT => 180 
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $http_code !== 200) {
        $error_message = "Failed to get current total count from SAP. HTTP: $http_code, cURL Error: " . $curl_error;
        add_session_feedback($error_message, 'danger'); 
        error_log($error_message . " URL: " . $count_url . " Response Body: " . substr($response, 0, 1000));
        return false;
    }
    $data = json_decode($response, true);
    if ($data !== null && isset($data['d']['__count'])) {
        $count = intval($data['d']['__count']);
        add_session_feedback("Current total records available in SAP: " . $count, 'success'); 
        error_log("Current total records available in SAP from API: " . $count);
        return $count;
    }
    $warning_message = "Could not determine current total count from SAP response. URL: " . $count_url;
    add_session_feedback($warning_message, 'warning'); 
    error_log($warning_message . " Response: " . substr($response, 0, 500));
    return false;
}

function fetch_all_sap_data() { 
    $all_fetched_records = [];
    $ch = curl_init();
    curl_setopt_array($ch, [ 
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => SAP_USERNAME . ":" . SAP_PASSWORD,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Requested-With: XMLHttpRequest', 'Connection: keep-alive'],
        CURLOPT_TIMEOUT => 900, CURLOPT_CONNECTTIMEOUT => 60
    ]);

    $sap_fields_to_select = array_values(SAP_DB_FIELD_MAPPING);
    $select_query_str = implode(',', array_unique($sap_fields_to_select));

    $current_skip = 0;
    $fetch_iteration = 0;
    $next_page_url_from_sap = null;

    add_session_feedback("Starting full data fetch from SAP.", 'info');
    error_log("Starting full data fetch from SAP for smart sync.");

    while (true) {
        $fetch_iteration++;
        $current_page_url_for_curl = "";
        $odata_params_assoc = []; 

        if ($next_page_url_from_sap) { 
            $current_page_url_for_curl = $next_page_url_from_sap;
            if (strpos($current_page_url_for_curl, 'http') !== 0) { 
                $parsed_base = parse_url(SAP_ODATA_URL);
                $sap_host_base = $parsed_base['scheme'] . '://' . $parsed_base['host'] . (isset($parsed_base['port']) ? ':' . $parsed_base['port'] : '');
                $current_page_url_for_curl = $sap_host_base . $current_page_url_for_curl;
            }
            error_log("Fetching records page " . $fetch_iteration . " using __next URL: " . $current_page_url_for_curl);
        } else {
            $odata_params_assoc['$format'] = 'json';
            $odata_params_assoc['$top']    = strval(SAP_PAGE_SIZE);
            $odata_params_assoc['$skip']   = strval($current_skip);
            if (!empty($select_query_str)) { $odata_params_assoc['$select'] = $select_query_str; }
            if ($fetch_iteration == 1) { $odata_params_assoc['$inlinecount'] = 'allpages'; }
            $current_page_url_for_curl = rtrim(SAP_ODATA_URL, '/') . '?' . http_build_query($odata_params_assoc);
            error_log("Fetching records page " . $fetch_iteration . " (Offset: ".$current_skip.") Built URL: " . $current_page_url_for_curl);
        }
        add_session_feedback("Fetching SAP records page " . $fetch_iteration . "...", 'info');
        error_log("DEBUG SAP OData URL for Fetch (Iteration {$fetch_iteration}): " . $current_page_url_for_curl);

        curl_setopt($ch, CURLOPT_URL, $current_page_url_for_curl);
        $response_body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);

        if ($response_body === false || $http_code !== 200) { 
            add_session_feedback("Error fetching SAP page " . $fetch_iteration . ". HTTP: $http_code. cURL: " . $curl_error, 'danger');
            error_log("Error fetching SAP page " . $fetch_iteration . ". HTTP: $http_code. cURL: " . $curl_error . ". URL: " . $current_page_url_for_curl);
            curl_close($ch); return false; 
        }
        $page_data = json_decode($response_body, true);
        if ($page_data === null || !isset($page_data['d'])) { 
            add_session_feedback("Error decoding JSON for SAP page " . $fetch_iteration, 'danger');
            error_log("Error decoding JSON for SAP page " . $fetch_iteration . ". Response: " . substr($response_body,0,200));
            curl_close($ch); return false; 
        }
        
        if ($fetch_iteration == 1 && isset($page_data['d']['__count'])) {
            add_session_feedback("SAP reported total count via inlinecount: " . intval($page_data['d']['__count']), 'info');
        }
        
        $records_on_page = $page_data['d']['results'] ?? [];
        $count_on_page = count($records_on_page);
        add_session_feedback("  Page " . $fetch_iteration . ": Fetched " . $count_on_page . " records.", 'info');
        error_log("DEBUG: Iteration {$fetch_iteration} - Records on page: {$count_on_page}");

        if ($count_on_page > 0) { $all_fetched_records = array_merge($all_fetched_records, $records_on_page); }

        $next_page_url_from_sap = $page_data['d']['__next'] ?? null;
        if ($next_page_url_from_sap) { $current_skip = 0;  } 
        elseif ($count_on_page < SAP_PAGE_SIZE) { break; } 
        else { $current_skip += $count_on_page; }
        if ($count_on_page == 0 && !$next_page_url_from_sap) { break; }
    }
    curl_close($ch);
    add_session_feedback("Finished fetching all SAP records. Total retrieved: " . count($all_fetched_records), 'success');
    return $all_fetched_records;
}

function db_process_records($conn, $sap_records, $truncate_table_first = false) {
    $inserted_count_total = 0; $error_count_total = 0; $truncate_duration = 0;

    if ($truncate_table_first) {
        $ts_before_truncate = microtime(true);
        add_session_feedback("Truncating local table '" . DB_TABLE_NAME . "' for full load...", 'info'); 
        error_log("DB_OPS: Truncating local table '" . DB_TABLE_NAME . "'");
        if (!$conn->query("TRUNCATE TABLE `" . DB_TABLE_NAME . "`")) {
            $error_msg = "Failed to truncate table: " . $conn->error;
            add_session_feedback($error_msg, 'danger'); error_log("DB_OPS: " . $error_msg);
            return ['inserted' => 0, 'errors' => count($sap_records), 'truncate_time' => 0];
        }
        $conn->commit(); 
        $ts_after_truncate = microtime(true); $truncate_duration = round($ts_after_truncate - $ts_before_truncate, 3);
        add_session_feedback("Local table truncated successfully. Time: " . $truncate_duration . "s", 'success'); 
        error_log("DB_OPS: Local table truncated successfully. Time: " . $truncate_duration . "s");
    }

    if (empty($sap_records)) { return ['inserted' => 0, 'errors' => 0, 'truncate_time' => $truncate_duration]; }

    $db_columns = array_keys(SAP_DB_FIELD_MAPPING);
    $num_columns = count($db_columns);
    $single_row_placeholders_sql_part = '(' . implode(',', array_fill(0, $num_columns, '?')) . ')';
    $single_row_bind_types = str_repeat('s', $num_columns);
    $records_in_current_sql_batch = 0; $values_for_current_sql_batch = []; $types_for_current_sql_batch = "";    

    try {
        $conn->begin_transaction(); 
        error_log("DB_OPS: Began transaction for inserting records.");
        foreach ($sap_records as $index => $record_from_sap) { 
            $ordered_row_values_for_db = [];
            foreach ($db_columns as $db_col_name) {
                $sap_field_name_for_this_db_col = SAP_DB_FIELD_MAPPING[$db_col_name];
                $ordered_row_values_for_db[] = clean_record_value_php($record_from_sap[$sap_field_name_for_this_db_col] ?? null);
            }
            foreach($ordered_row_values_for_db as $val) { $values_for_current_sql_batch[] = $val; }
            $types_for_current_sql_batch .= $single_row_bind_types; $records_in_current_sql_batch++;

            if ($records_in_current_sql_batch >= DB_MULTI_INSERT_SQL_BATCH_SIZE || ($index + 1) === count($sap_records)) {
                if ($records_in_current_sql_batch > 0) {
                    $multi_row_placeholders_sql = implode(', ', array_fill(0, $records_in_current_sql_batch, $single_row_placeholders_sql_part));
                    $insert_keyword = $truncate_table_first ? "INSERT" : "INSERT IGNORE";
                    $insert_sql = "{$insert_keyword} INTO `" . DB_TABLE_NAME . "` (`" . implode('`, `', $db_columns) . "`) VALUES $multi_row_placeholders_sql";
                    $stmt = $conn->prepare($insert_sql);
                    if ($stmt) { 
                        $bind_params_ref_multi = [$types_for_current_sql_batch]; $temp_bind_values_for_ref = []; 
                        foreach ($values_for_current_sql_batch as $key_val_ref => $value_val_ref) { $temp_bind_values_for_ref[$key_val_ref] = $value_val_ref; $bind_params_ref_multi[] = &$temp_bind_values_for_ref[$key_val_ref]; }
                        if (call_user_func_array([$stmt, 'bind_param'], $bind_params_ref_multi)) {
                            if ($stmt->execute()) { $inserted_this_sql_batch = $stmt->affected_rows; if ($inserted_this_sql_batch >= 0) { $inserted_count_total += $inserted_this_sql_batch; } else { $error_count_total += $records_in_current_sql_batch; add_session_feedback("MySQL {$insert_keyword} error (negative affected): " . $stmt->error, 'danger');}}
                            else { $error_count_total += $records_in_current_sql_batch; add_session_feedback("MySQL {$insert_keyword} execute error: " . $stmt->error, 'danger');}
                        } else { $error_count_total += $records_in_current_sql_batch; add_session_feedback("MySQL {$insert_keyword} bind_param error: " . $stmt->error, 'danger');}
                        $stmt->close();
                    } else { $error_count_total += $records_in_current_sql_batch; add_session_feedback("MySQL prepare {$insert_keyword} error: " . $conn->error, 'danger');}
                    $records_in_current_sql_batch = 0; $values_for_current_sql_batch = []; $types_for_current_sql_batch = "";
                }
            }
        } 
        if ($error_count_total == 0) { $conn->commit(); add_session_feedback("DB Transaction Committed. Rows affected/inserted: " . $inserted_count_total, 'success');} 
        else { $conn->rollback(); add_session_feedback("DB Transaction Rolled Back due to errors.", 'warning');}
    } catch (Exception $e) { 
        add_session_feedback("General error during DB process: " . $e->getMessage(), 'danger');
        error_log("EXCEPTION in db_process_records: " . $e->getMessage());
        if ($conn && method_exists($conn, 'rollback')) { if (version_compare(PHP_VERSION, '8.0.0', '>=') && property_exists($conn, 'in_transaction') && $conn->in_transaction) $conn->rollback(); else @$conn->rollback(); }
        $error_count_total = count($sap_records); 
    }
    return ['inserted' => $inserted_count_total, 'errors' => $error_count_total, 'truncate_time' => $truncate_duration];
}

function perform_intelligent_sync() { 
    $_SESSION['sync_feedback_messages'] = []; 
    // Only show this if detailed feedback is on, or make it 'primary' if always needed
    add_session_feedback("--- Intelligent Sync Process Initializing ---", 'info'); 
    error_log("--- Intelligent Sync Process Initializing ---");
    $overall_start_time = microtime(true);
    
    @ini_set('max_execution_time', 0); 
    @set_time_limit(0);

    $conn = connect_to_mysql_php();
    if (!$conn) { add_session_feedback("Sync Aborted: DB connection failed.", 'danger'); return; }

    $truncate_table_this_run = false; 
    $last_synced_sap_total_count = -1; 

    try { 
        $create_meta_table_sql = "CREATE TABLE IF NOT EXISTS `" . SYNC_METADATA_TABLE_NAME . "` ( meta_key VARCHAR(100) PRIMARY KEY, meta_value VARCHAR(100) NULL )";
        if (!$conn->query($create_meta_table_sql)) { throw new Exception("Error creating/checking metadata table: " . $conn->error); }
        $conn->commit(); 

        $count_stmt = $conn->query("SELECT COUNT(*) as row_count FROM `" . DB_TABLE_NAME . "`");
        if ($count_stmt) {
            $count_result = $count_stmt->fetch_assoc();
            if (isset($count_result['row_count']) && $count_result['row_count'] == 0) {
                $truncate_table_this_run = true; 
                add_session_feedback("Local table is empty. Preparing for initial full data load.", 'primary'); 
                error_log("Local table '".DB_TABLE_NAME."' is empty. Will perform initial full load.");
            }
            $count_stmt->free();
        } else { throw new Exception("Could not determine if target table is empty: " . $conn->error); }
        $conn->commit(); 

        $stored_count_str = get_metadata_value($conn, METADATA_KEY_LAST_SAP_TOTAL_COUNT);
        if ($stored_count_str !== null) { $last_synced_sap_total_count = intval($stored_count_str); }
        
        $current_sap_total_count = get_current_sap_total_record_count();
        if ($current_sap_total_count === false) { throw new Exception("Could not retrieve current total record count from SAP. Sync aborted."); }

        $should_fetch_and_insert = false;
        if ($truncate_table_this_run) { 
            $should_fetch_and_insert = true;
            add_session_feedback("Proceeding with full data load (local table was empty).", 'info');
        } elseif ($current_sap_total_count > $last_synced_sap_total_count) {
            $should_fetch_and_insert = true;
            // This is an important status, make it 'success' to ensure it's seen even in quiet mode
            add_session_feedback("SAP data has changed (Current SAP count: ".$current_sap_total_count.", Last synced SAP count: ".$last_synced_sap_total_count."). Fetching updates.", 'success'); 
            error_log("SAP count increased. Current: {$current_sap_total_count}, Last Synced: {$last_synced_sap_total_count}. Syncing.");
        } else {
            add_session_feedback("Local data appears up-to-date with SAP (based on record counts). No sync performed.", 'success'); 
            error_log("SAP count not increased. Current: {$current_sap_total_count}, Last Synced: {$last_synced_sap_total_count}. Sync skipped.");
        }

        if ($should_fetch_and_insert) {
            add_session_feedback("Fetching all data from SAP...", 'info');
            $all_sap_records = fetch_all_sap_data(); 

            if ($all_sap_records === false) {
                add_session_feedback("Sync Aborted: Error during SAP data fetch.", 'danger'); 
            } elseif (empty($all_sap_records) && $current_sap_total_count > 0) {
                add_session_feedback("Warning: SAP reported " . $current_sap_total_count . " records, but fetch returned empty.", 'warning'); 
            } elseif (!empty($all_sap_records)) {
                $insert_stats = db_process_records($conn, $all_sap_records, $truncate_table_this_run);
                $inserted_message = $truncate_table_this_run ? "Total Inserted" : "Newly Inserted (via IGNORE)";
                // This is a key result message, make it 'success' or 'warning'
                add_session_feedback("Database sync finished. {$inserted_message}: " . $insert_stats['inserted'] . ", Errors: " . $insert_stats['errors'], $insert_stats['errors'] > 0 ? 'warning' : 'success'); 

                if ($insert_stats['errors'] == 0) { 
                    if (set_metadata_value($conn, METADATA_KEY_LAST_SAP_TOTAL_COUNT, strval($current_sap_total_count))) {
                        $conn->commit();
                        add_session_feedback("Last synced SAP total count updated to: " . $current_sap_total_count, 'success'); 
                    } else {
                        $conn->rollback();
                        add_session_feedback("CRITICAL ERROR: Failed to update last synced SAP total count.", 'danger'); 
                    }
                }
            } else if (empty($all_sap_records) && $current_sap_total_count == 0) {
                 add_session_feedback("SAP has 0 records. Local table processed accordingly (empty or truncated).", 'success'); 
                 if (set_metadata_value($conn, METADATA_KEY_LAST_SAP_TOTAL_COUNT, '0')) {
                    $conn->commit();
                 } else { $conn->rollback(); add_session_feedback("CRITICAL ERROR: Failed to update SAP total count to 0.", 'danger');}
            }
        } 
    } catch (Exception $e) {
        add_session_feedback("Major error in sync process: " . $e->getMessage(), 'danger'); 
        error_log("EXCEPTION in perform_intelligent_sync: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        if ($conn) { 
            if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
                if (property_exists($conn, 'in_transaction') && $conn->in_transaction) $conn->rollback();
            } else {
                if (method_exists($conn, 'rollback')) @$conn->rollback();
            }
        }
    } finally {
        if ($conn && $conn->is_connected) $conn->close(); 
    }
    
    $overall_elapsed = microtime(true) - $overall_start_time;
    add_session_feedback("--- Intelligent Sync Finished. Overall Total Time: " . round($overall_elapsed, 2) . " seconds ---", 'primary'); 
    error_log("--- Intelligent Sync Finished. Overall Total Time: " . round($overall_elapsed, 2) . " seconds ---");
}

// --- ACTION: Get Distinct values for filter dropdowns (from local DB) ---
if (isset($_GET['action']) && $_GET['action'] == 'get_distinct_values_local' && isset($_GET['field'])) {
    ob_clean(); 
    header('Content-Type: application/json');
    $field_for_distinct_js_name = $_GET['field']; 
    $db_column_map = [ 
        'Product' => 'material_no', 'ProductDescription' => 'material_description', 'ProductGroup' => 'material_group_no',
        'ProductGroupName' => 'material_group_name', 'ExternalProductGroup' => 'external_group_no',
        'ExternalProductGroupName' => 'external_group_name', 'Plant' => 'plant'
    ];
    if (!isset($db_column_map[$field_for_distinct_js_name])) { echo json_encode(['error' => 'Invalid field for distinct local values. Field: ' . htmlspecialchars($field_for_distinct_js_name), 'results' => [] ]); ob_end_flush(); exit(); }
    $db_column_to_query = $db_column_map[$field_for_distinct_js_name];
    $mysqli_distinct = connect_to_mysql_php(); 
    if (!$mysqli_distinct) { echo json_encode(['error' => 'DB connection error for distinct values.', 'results' => []]); ob_end_flush(); exit(); }
    
    $where_clauses_distinct = []; $bind_params_distinct_values = []; $param_types_distinct_string = "";
    foreach ($db_column_map as $js_context_field_name => $db_col_name_context) {
        if ($field_for_distinct_js_name !== $js_context_field_name && isset($_GET['active_' . $js_context_field_name]) && !empty($_GET['active_' . $js_context_field_name])) {
            $active_value = $_GET['active_' . $js_context_field_name];
            if (is_array($active_value)) { 
                if (count($active_value) > 0) {
                    $placeholders_ctx = implode(',', array_fill(0, count($active_value), '?'));
                    $where_clauses_distinct[] = "`$db_col_name_context` IN ($placeholders_ctx)";
                    foreach ($active_value as $val_ctx) { $bind_params_distinct_values[] = $val_ctx; $param_types_distinct_string .= "s"; }
                }
            } else { $where_clauses_distinct[] = "`$db_col_name_context` = ?"; $bind_params_distinct_values[] = $active_value; $param_types_distinct_string .= "s"; }
        }
    }
    $search_term_local = isset($_GET['q']) ? $_GET['q'] : '';
    if (!empty($search_term_local)) { $where_clauses_distinct[] = "`$db_column_to_query` LIKE ?"; $bind_params_distinct_values[] = "%" . $search_term_local . "%"; $param_types_distinct_string .= "s"; }
    $sql_distinct = "SELECT DISTINCT `$db_column_to_query` FROM ".DB_TABLE_NAME." WHERE `$db_column_to_query` IS NOT NULL AND `$db_column_to_query` != ''";
    if (!empty($where_clauses_distinct)) { $sql_distinct .= " AND " . implode(" AND ", $where_clauses_distinct); }
    $sql_distinct .= " ORDER BY `$db_column_to_query` ASC LIMIT 50"; 
    $stmt_distinct = $mysqli_distinct->prepare($sql_distinct); $distinct_values = []; $json_error = null;
    if ($stmt_distinct) {
        if (!empty($param_types_distinct_string)) {
            $bind_args_ref = [$param_types_distinct_string]; $temp_bind_params_distinct_refs = []; 
            foreach ($bind_params_distinct_values as $key_val => $value_val) { $temp_bind_params_distinct_refs[$key_val] = $value_val; $bind_args_ref[] = &$temp_bind_params_distinct_refs[$key_val]; }
            if (!call_user_func_array([$stmt_distinct, 'bind_param'], $bind_args_ref)) { $json_error = "Bind param error: " . $stmt_distinct->error; error_log($json_error . " SQL: " . $sql_distinct); }
        }
        if (!$json_error && !$stmt_distinct->execute()) { $json_error = "Execute error: " . $stmt_distinct->error; error_log($json_error . " SQL: " . $sql_distinct);
        } elseif (!$json_error) {
            $result_distinct = $stmt_distinct->get_result();
            if ($result_distinct) { while ($row = $result_distinct->fetch_assoc()) { $distinct_values[] = ['id' => $row[$db_column_to_query], 'text' => $row[$db_column_to_query]]; } $result_distinct->free();
            } else { $json_error = "Get result error: " . $stmt_distinct->error; error_log($json_error . " SQL: " . $sql_distinct); }
        } $stmt_distinct->close();
    } else { $json_error = "Prepare error: " . $mysqli_distinct->error; error_log($json_error . " SQL: " . $sql_distinct); }
    $mysqli_distinct->commit(); 
    $mysqli_distinct->close(); $response_payload = ['results' => $distinct_values];
    if ($json_error) { $response_payload['error'] = $json_error; }
    echo json_encode($response_payload); 
    ob_end_flush(); 
    exit();
}

// --- ACTION: Get Item Data for DataTables (from local DB) ---
elseif (isset($_GET['action']) && $_GET['action'] == 'get_item_data') { 
    ob_clean(); 
    header('Content-Type: application/json');
    $mysqli_table = connect_to_mysql_php();
    if (!$mysqli_table) { echo json_encode(['error' => 'Database connection failed', 'data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0]); ob_end_flush(); exit(); }
    
    $where_clauses = []; $bind_params_for_where = []; $param_types_for_where = "";
    $filter_fields_map = [ 
        'material_no_filter' => ['column' => 'material_no', 'type' => 'select_like'], 'description_filter' => ['column' => 'material_description', 'type' => 'select_like'],
        'plant_filter' => ['column' => 'plant', 'type' => 'exact'], 'product_group_filter' => ['column' => 'material_group_no', 'type' => 'exact'], 
        'product_group_name_filter' => ['column' => 'material_group_name', 'type' => 'exact'], 'ext_material_group_filter' => ['column' => 'external_group_no', 'type' => 'exact'],
        'ext_material_group_name_filter' => ['column' => 'external_group_name', 'type' => 'exact']
    ];
    foreach ($filter_fields_map as $get_param => $filter_detail) {
        if (isset($_GET[$get_param]) && $_GET[$get_param] !== '') { 
            $db_column = $filter_detail['column'];
            if ($filter_detail['type'] === 'exact') { $where_clauses[] = "`$db_column` = ?"; $bind_params_for_where[] = $_GET[$get_param]; } 
            else { $where_clauses[] = "`$db_column` LIKE ?"; $bind_params_for_where[] = "%" . $_GET[$get_param] . "%"; }
            $param_types_for_where .= "s";
        }
    }
    $searchable_columns_for_global_search = array_keys(SAP_DB_FIELD_MAPPING);
    if (isset($_GET['search']['value']) && !empty($_GET['search']['value'])) {
        $global_search_term = "%" . $_GET['search']['value'] . "%"; $global_filter_subclauses = [];
        foreach ($searchable_columns_for_global_search as $col_db_name) { $global_filter_subclauses[] = "`$col_db_name` LIKE ?"; $bind_params_for_where[] = $global_search_term; $param_types_for_where .= "s"; }
        if (!empty($global_filter_subclauses)) { $where_clauses[] = "(" . implode(" OR ", $global_filter_subclauses) . ")";}
    }
    $sql_base = "FROM `" . DB_TABLE_NAME . "`"; $sql_where = "";
    if (!empty($where_clauses)) { $sql_where = " WHERE " . implode(" AND ", $where_clauses); }
    $total_result_q = $mysqli_table->query("SELECT COUNT(id) as total " . $sql_base);
    $records_total = $total_result_q ? $total_result_q->fetch_assoc()['total'] : 0;
    if($total_result_q) $total_result_q->free();
    $records_filtered = 0;
    if (!empty($sql_where)) {
        $count_sql_filtered = "SELECT COUNT(id) as filtered " . $sql_base . $sql_where;
        $stmt_filtered_count = $mysqli_table->prepare($count_sql_filtered);
        if ($stmt_filtered_count) {
            if (!empty($param_types_for_where)) {
                $bind_args_count_refs = [$param_types_for_where]; $temp_bind_params_count_refs = [];
                foreach ($bind_params_for_where as $key_val => $value_val) { $temp_bind_params_count_refs[$key_val] = $value_val; $bind_args_count_refs[] = &$temp_bind_params_count_refs[$key_val]; }
                if(!call_user_func_array([$stmt_filtered_count, 'bind_param'], $bind_args_count_refs)){ error_log("DataTables filtered count bind_param error: " . $stmt_filtered_count->error); }
            }
            if ($stmt_filtered_count->execute()) {
                $filtered_result_obj = $stmt_filtered_count->get_result();
                $records_filtered = $filtered_result_obj ? $filtered_result_obj->fetch_assoc()['filtered'] : 0;
                if($filtered_result_obj) $filtered_result_obj->free();
            } else { error_log("DataTables filtered count execute error: " . $stmt_filtered_count->error . " SQL: " . $count_sql_filtered); $records_filtered = $records_total; }
            $stmt_filtered_count->close();
        } else { error_log("DataTables filtered count prepare error: " . $mysqli_table->error . " SQL: " . $count_sql_filtered); $records_filtered = $records_total; }
    } else { $records_filtered = $records_total; }
    $sql_data_select_cols = "id, " . implode(", ", array_keys(SAP_DB_FIELD_MAPPING)); 
    $sql_data = "SELECT " . $sql_data_select_cols . " " . $sql_base . $sql_where;
    if (isset($_GET['order']) && is_array($_GET['order'])) { 
        $order_by_clauses = []; $allowed_db_sort_cols = array_merge(['id'], array_keys(SAP_DB_FIELD_MAPPING));
        foreach ($_GET['order'] as $order_item) {
            $column_index = intval($order_item['column']); $column_name_dt = $_GET['columns'][$column_index]['data'] ?? null; 
            $direction = strtolower($order_item['dir']) === 'asc' ? 'ASC' : 'DESC';
            if ($column_name_dt && in_array($column_name_dt, $allowed_db_sort_cols)) { $order_by_clauses[] = "`" . $column_name_dt . "` " . $direction; } 
            else { $order_by_clauses[] = "`id` " . $direction; }
        }
        if (!empty($order_by_clauses)) { $sql_data .= " ORDER BY " . implode(", ", $order_by_clauses); } else { $sql_data .= " ORDER BY id DESC"; }
    } else { $sql_data .= " ORDER BY id DESC"; }
    
    $limit = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $offset = isset($_GET['start']) ? intval($_GET['start']) : 0;

    $final_data_bind_params = $bind_params_for_where; 
    $final_data_param_types = $param_types_for_where; 

    if ($limit == -1) {
        $limit = DATATABLES_MAX_RECORDS_FOR_ALL;
        add_session_feedback("DataTables 'All' entries requested. Capping results to " . DATATABLES_MAX_RECORDS_FOR_ALL . " records.", 'info');
        error_log("DataTables 'All' entries requested. Capping to " . DATATABLES_MAX_RECORDS_FOR_ALL);
    }
    
    if ($limit > 0) { 
        $sql_data .= " LIMIT ? OFFSET ?";
        $final_data_bind_params[] = $limit;   $final_data_param_types .= "i"; 
        $final_data_bind_params[] = $offset;  $final_data_param_types .= "i"; 
    }

    $stmt_data = $mysqli_table->prepare($sql_data); $data = []; $json_error_data = null;
    if ($stmt_data) {
        if (!empty($final_data_param_types)) {
            $bind_args_data_refs = [$final_data_param_types]; $temp_final_bind_params_data_refs = [];
            foreach($final_data_bind_params as $idx => $val_final_data){ $temp_final_bind_params_data_refs[$idx] = $val_final_data; $bind_args_data_refs[] = &$temp_final_bind_params_data_refs[$idx]; }
            if (!call_user_func_array([$stmt_data, 'bind_param'], $bind_args_data_refs)) { $json_error_data = "DataTables data bind_param error: " . $stmt_data->error; error_log($json_error_data); }
        }
        if (!$json_error_data && $stmt_data->execute()) {
            $result_data = $stmt_data->get_result();
            if ($result_data) { while ($row = $result_data->fetch_assoc()) $data[] = $row; $result_data->free(); } 
            else { $json_error_data = "DataTables data get_result error: " . $stmt_data->error; error_log($json_error_data); }
        } elseif(!$json_error_data) { $json_error_data = "DataTables data execute error: " . $stmt_data->error; error_log($json_error_data . " SQL: " . $sql_data); }
        $stmt_data->close();
    } else { $json_error_data = "DataTables data prepare error: " . $mysqli_table->error; error_log($json_error_data . " SQL: " . $sql_data); }
    $mysqli_table->commit(); 
    $mysqli_table->close(); $response_payload_data = ['draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 0, 'recordsTotal' => intval($records_total), 'recordsFiltered' => intval($records_filtered), 'data' => $data ];
    if($json_error_data){ $response_payload_data['error'] = $json_error_data; }
    echo json_encode($response_payload_data); 
    ob_end_flush(); 
    exit();
}

// ---- Main Page Action Trigger for Sync ----
$action_trigger = $_GET['action'] ?? ''; 

if ($action_trigger == 'perform_intelligent_sync' || $action_trigger == 'run_manual_sync' || $action_trigger == 'perform_delta_sync') { 
    if ($action_trigger == 'perform_delta_sync' || $action_trigger == 'run_manual_sync') { 
        error_log("Action '{$action_trigger}' called, executing perform_intelligent_sync (full load/insert ignore).");
    }
    perform_intelligent_sync(); 
    
    $final_feedback_messages = $_SESSION['sync_feedback_messages'] ?? [];
    // Clear session messages only if detailed feedback is off, OR if no non-info messages to show
    // AND no danger/warning messages (which we want to keep if quiet mode is on)
    $has_critical_messages = !empty(array_filter($final_feedback_messages, function($m){ 
        return $m['type'] === 'danger' || $m['type'] === 'warning'; 
    }));

    if (!SHOW_DETAILED_SYNC_FEEDBACK && !$has_critical_messages) {
        unset($_SESSION['sync_feedback_messages']); 
    } elseif (SHOW_DETAILED_SYNC_FEEDBACK) {
        unset($_SESSION['sync_feedback_messages']); 
    }
    
    if (ob_get_length() > 0) { 
        error_log("PHP AJAX Handler (Sync): Cleared unexpected output buffer content: " . substr(ob_get_contents(),0, 200)); 
        ob_clean(); 
    }
    if (!headers_sent()) {
        header('Content-Type: application/json');
    } else {
        error_log("PHP AJAX Handler (Sync): Headers already sent before trying to set Content-Type: application/json.");
    }
    echo json_encode(['status' => 'Intelligent sync process completed.', 'feedback_messages' => $final_feedback_messages]);
    ob_end_flush(); 
    exit(); 
}

// If no action matched, flush any output that might have occurred
ob_end_flush();
?>
