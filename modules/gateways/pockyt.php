<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}


function pockyt_MetaData()
{
    return array(
        'DisplayName' => 'Pockyt Payment',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}


function pockyt_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Pockyt',
        ),
        'MERCHANT_NO' => array(
            'FriendlyName' => 'Merchant No',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Pockyt Merchant No.',
        ),
        'STORE_NO' => array(
            'FriendlyName' => 'Store No',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Pockyt Store No.',
        ),
        'API_TOKEN' => array(
            'FriendlyName' => 'API Token',
            'Type' => 'password',
            'Size' => '4096',
            'Default' => '',
            'Description' => 'Pockyt API token.',
        ),
        'SANDBOX' => array(
            'FriendlyName' => 'Sandbox',
            'Type' => 'yesno',
            'Description' => 'Tick to enable sandbox mode',
        )
    );
}
function sendRequestToPockyt($url, $postfields) {
    // Initiate curl
    $ch = curl_init();

    // Set curl options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postfields));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));

    // Execute curl and get the response
    $response = curl_exec($ch);

    // Close the curl
    curl_close($ch);
    logModuleCall(
        'pockyt',
        $url,
        json_encode($postfields),
        $response,
        json_decode($response, true),
        array()
    );
    return json_decode($response, true);
}

function pockyt_link($params) {
    // Config Parameters
    $merchantNo = $params['MERCHANT_NO'];
    $storeNo = $params['STORE_NO'];
    $apiToken = $params['API_TOKEN'];  // Replace with your actual API token

    // Payment Parameters
    $amount = $params['amount'];
    $currency = $params['currency'];
    $description = $params['description'];
    $invoiceid = $params['invoiceid'];
    // System Parameters
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langpaynow = $params['langpaynow'];
    // Endpoint URL
    if ($params['SANDBOX']) {
        $url = 'https://mapi.yuansfer.yunkeguan.com/online/v3/secure-pay';
    } else {
        $url = 'https://mapi.yuansfer.com/online/v3/secure-pay';
    }

    // Prepare the postfields
    $postfields = array();
    $postfields['merchantNo'] = $merchantNo;
    $postfields['storeNo'] = $storeNo;
    $postfields['amount'] = $amount;
    $postfields['currency'] = $currency;
    $postfields['settleCurrency'] = 'USD';
    $postfields['vendor'] = "alipay";
    $postfields['terminal'] = 'ONLINE';
    $postfields['ipnUrl'] = $systemUrl . '/modules/gateways/callback/pockyt.php';
    $postfields['callbackUrl'] = $returnUrl;
    $postfields['reference'] = $invoiceid;
    $postfields['description'] = $description;
    $postfields['verifySign'] = calculateVeriSign($postfields, $apiToken);

    // Prepare the postfields

    // Send the request to Pockyt and get the response
    $response = sendRequestToPockyt($url, $postfields);

    // Check if the request was successful
    if ($response['ret_code'] === '000100') {
        // Use the cashierUrl from the response as the form action
        $url = $response['result']['cashierUrl'];
        $htmlOutput = '<form method="post" action="' . $url . '">';
        $htmlOutput .= '<input type="submit" value='. $langpaynow . ' />';
        $htmlOutput .= '</form>';
    } else {
        logActivity('met error! '.var_export($response, true), $params['clientdetails']['model']['Contact']['id']);
        // Handle error
        $htmlOutput = 'Error: ' . $response['ret_msg'];
    }
    return $htmlOutput;
}
function calculateVeriSign($params, $apiToken)
{
    ksort($params, SORT_STRING);
    $str = '';
    foreach ($params as $k => $v) {
        $str .= $k . '=' . $v . '&';
    }
    return md5($str . md5($apiToken));
}

function pockyt_refund($params) {
    $merchantNo = $params['MERCHANT_NO'];
    $storeNo = $params['STORE_NO'];
    $apiToken = $params['API_TOKEN'];
    // Payment Parameters
    $amount = $params['amount'];
    $currency = $params['currency'];
    $transactionId = $params['transid'];
    // Endpoint URL
    if ($params['SANDBOX']) {
        $url = 'https://mapi.yuansfer.yunkeguan.com/app-data-search/v3/refund';
    } else {
        $url = 'https://mapi.yuansfer.com/app-data-search/v3/refund';
    }

    // Prepare the postfields
    $postfields = array();
    $postfields['merchantNo'] = $merchantNo;
    $postfields['storeNo'] = $storeNo;
    $postfields['refundAmount'] = $amount;
    $postfields['currency'] = $currency;
    $postfields['transactionNo'] = $transactionId;
    $postfields['settleCurrency'] = 'USD';
    $postfields['verifySign'] = calculateVeriSign($postfields, $apiToken);

    // Prepare the postfields

    // Send the request to Pockyt and get the response
    $response = sendRequestToPockyt($url, $postfields);

    // Check if the request was successful
    if ($response['ret_code'] === '000100') {
        $status = 'success';
        $refundTransactionId = $response['result']['refundTransactionNo'];
    } else {
        // Handle error
        logActivity('met error! '.var_export($response, true), 0);
        $status = 'error';
        $refundTransactionId = '';
    }
    return array(
        'status' => $status,
        'rawdata' => $response,
        'transid' => $refundTransactionId,
        'fees' => 0.00,
    );
}