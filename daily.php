<?php
require_once 'vendor/autoload.php';

// Set the maximum execution time to 300 seconds
set_time_limit(300);

function getToken() {
    $request = new \HTTP_Request2();
    $url = 'https://wfapp01.wfmobile.com.sg/v500IPL030/Webapi/api/User/LoginUser/';
    $request->setUrl($url);
    $request->setMethod(\HTTP_Request2::METHOD_POST);
    $request->setHeader(array(
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ));
    $request->setBody(json_encode(array(
        'Username' => 'micr',
        'Password' => 'micr',
        'CompanyCode' => 'IPL030'
    )));

    try {
        $response = $request->send();

        if ($response->getStatus() == 200) {
            $jsonResponse = $response->getBody();
            $data = json_decode($jsonResponse, true);
            if (isset($data['data']['Token'])) { 
                return $data['data']['Token'];
            } else {
                echo 'Failed to retrieve token: Token not found in response.';
                return null;
            }
        } else {
            echo 'Failed to retrieve token. Unexpected HTTP status: ' . $response->getStatus() . ' ' . $response->getReasonPhrase();
            echo '<br>';
            echo 'Response content: ' . $response->getBody();
            return null;
        }
    } catch (\HTTP_Request2_Exception $e) {
        echo 'Error: ' . $e->getMessage();
        return null;
    }
}

function getApiData($request, $maxRetries = 3) {
    $attempts = 0;
    while ($attempts < $maxRetries) {
        try {
            $response = $request->send();
            if ($response->getStatus() == 200) {
                return $response->getBody();
            } else {
                echo 'Failed to retrieve data. Unexpected HTTP status: ' . $response->getStatus() . ' ' . $response->getReasonPhrase();
                echo '<br>';
                echo 'Response content: ' . $response->getBody();
                return null;
            }
        } catch (\HTTP_Request2_Exception $e) {
            $attempts++;
            echo 'Attempt ' . $attempts . ' failed: ' . $e->getMessage();
            if ($attempts < $maxRetries) {
                sleep(1); // Wait for 1 second before retrying
            } else {
                return null; // Max retries reached, return null
            }
        }
    }
}

// Set the date range to 6 months ago from today
$startDate = date('Y-m-d', strtotime('-6 months'));
$endDate = date('Y-m-d'); // Today

$request = new \HTTP_Request2();
$url = 'https://wfapp01.wfmobile.com.sg/v500ipl030/Webapi/api/ReportAPI/GetTaskRecordFullReport';
$url .= '?AppName=06%20SEB%20MOLD%20Repair%20Order%20Rev06&createdDateFrom=' . $startDate . '&createdDateTo=' . $endDate;

$token = getToken();

if ($token !== null) {
    $request->setUrl($url);
    $request->setMethod(\HTTP_Request2::METHOD_GET);
    $request->setConfig(array(
        'follow_redirects' => TRUE,
        'timeout' => 300 // Set timeout to 300 seconds
    ));
    $request->setHeader(array(
        'Cache-Control' => 'no-cache',
        'User-Agent' => 'PostmanRuntime/7.30.1',
        'Accept' => 'application/json',
        'Connection' => 'keep-alive',
        'wfmobile-token' => $token,
        'wfmobile-companycode' => 'IPL030',
        'Authorization' => 'Basic bWljcjptaWNy'
    ));
    
    // Attempt to retrieve data with retries
    $jsonResponse = getApiData($request);
    
    if ($jsonResponse) {
        // Save the response to api.json (overwriting previous data)
        file_put_contents('daily.json', $jsonResponse);
        echo 'Data successfully retrieved and saved to daily.json';
    }
} else {
    echo 'Could not retrieve token.';
}
?>
