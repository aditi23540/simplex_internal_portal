<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

define('SAP_ODATA_URL_BASE_TEST', 'https://my412439-api.s4hana.cloud.sap/sap/opu/odata/sap/ZMM_60_BIN/ZMM_60_CDS'); // Your entity set
define('SAP_USERNAME_TEST', 'ZNOVELSH412439');
define('SAP_PASSWORD_TEST', 'PEWMsSS$Pv3TSonFJlFUYJiEmcCfVmXcpzaMfeHw');

// Define only the fields you are 1000% sure exist in the service from your successful JSON viewing
// Let's start with the absolute minimum, like just 'Product' and 'Plant'.
$fields_to_select_test = "Product,Plant"; // Example, ensure these are valid

$odata_params_test = [
    '$format' => 'json',
    '$top'    => '5' // Request only a few records for testing
    // '$skip'   => '0', // Skip is 0 by default usually
];

if (!empty($fields_to_select_test)) {
    $odata_params_test['$select'] = $fields_to_select_test;
}

$url_to_test = SAP_ODATA_URL_BASE_TEST . '?' . http_build_query($odata_params_test);

echo "Attempting to fetch URL: " . htmlspecialchars($url_to_test) . "<br><br>";
error_log("TEST SCRIPT - Fetching URL: " . $url_to_test);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url_to_test,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_USERPWD => SAP_USERNAME_TEST . ":" . SAP_PASSWORD_TEST,
    CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Requested-With: XMLHttpRequest'],
    CURLOPT_TIMEOUT => 60,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false, // TEMPORARY for debugging SSL, remove in production
    CURLOPT_SSL_VERIFYHOST => false  // TEMPORARY for debugging SSL, remove in production
]);

$response_body = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

echo "HTTP Code: " . $http_code . "<br>";
echo "cURL Error: " . ($curl_error ? $curl_error : 'None') . "<br>";
echo "Content-Type: " . ($content_type ? $content_type : 'Not specified') . "<br>";
echo "Response Body (first 500 chars):<br><pre>" . htmlspecialchars(substr($response_body, 0, 500)) . "</pre><br>";
echo "Full Response Body:<br><pre>" . htmlspecialchars($response_body) . "</pre>";

error_log("TEST SCRIPT - HTTP Code: " . $http_code);
error_log("TEST SCRIPT - cURL Error: " . $curl_error);
error_log("TEST SCRIPT - Response Body: " . $response_body);

if ($http_code === 200) {
    $data = json_decode($response_body, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "JSON Decoded Successfully!<br>";
        if (isset($data['d']['results'])) {
            echo "Number of records received: " . count($data['d']['results']) . "<br>";
            print_r($data['d']['results']);
        } else {
            echo "JSON does not contain 'd.results'. Structure:<br>";
            print_r($data);
        }
    } else {
        echo "JSON Decode Error: " . json_last_error_msg() . "<br>";
    }
} elseif ($http_code === 403) {
    echo "ERROR: HTTP 403 - Access Denied. This strongly indicates an SAP authorization issue for the user/service combination.<br>";
} elseif ($http_code === 404) {
    echo "ERROR: HTTP 404 - Not Found. The URL or entity set may be incorrect.<br>";
} elseif ($http_code === 400) {
    echo "ERROR: HTTP 400 - Bad Request. The OData query parameters are likely malformed or invalid for the service.<br>";
} else {
    echo "ERROR: Received HTTP " . $http_code . "<br>";
}
?>