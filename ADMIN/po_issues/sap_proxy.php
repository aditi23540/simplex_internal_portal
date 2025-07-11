<?php
// page name - sap_proxy.php

// IMPORTANT: Do NOT include config.php or any other HTML here.
// This file must only output the JSON data from SAP.

// --- IMPORTANT: Replace with your actual SAP credentials ---
$sapUsername = "ZNOVELSH412439";
$sapPassword = 'PEWMsSS$Pv3TSonFJlFUYJiEmcCfVmXcpzaMfeHw';

// The target URL can be passed as a parameter or be fixed
$targetUrl = 'https://my412439-api.s4hana.cloud.sap/sap/opu/odata/sap/ZCUSTOMER_DETAILS_BINDING/zSUPPLIER_details1?$format=json';

// Check if a 'next' URL is provided for pagination
if (isset($_GET['next_url']) && !empty($_GET['next_url'])) {
    // Basic validation to ensure it's a valid SAP URL
    if (filter_var($_GET['next_url'], FILTER_VALIDATE_URL) && strpos($_GET['next_url'], 'sap') !== false) {
        $targetUrl = $_GET['next_url'];
    }
}

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept: application/json'
));
curl_setopt($ch, CURLOPT_USERPWD, "$sapUsername:$sapPassword");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

// In a production environment with valid SSL, you wouldn't need these.
// For local development (like XAMPP), you might need to bypass SSL verification.
// Use with caution.
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


// Execute the cURL request
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

// Close the cURL session
curl_close($ch);

// Check for cURL errors or bad HTTP response
if ($error) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['error' => "cURL Error: " . $error]);
    exit;
}

if ($httpcode >= 400) {
     header("HTTP/1.1 " . $httpcode);
     // Pass along the response from SAP if available
     echo $response;
     exit;
}

// Set the content type to JSON and echo the response from SAP
header('Content-Type: application/json');
echo $response;
