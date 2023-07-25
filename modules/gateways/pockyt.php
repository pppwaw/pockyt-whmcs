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
        'VENDOR' => array(
            'FriendlyName' => 'Vendor',
            'Type' => 'text',
            'Size' => '25',
            'Default' => 'alipay',
            'Description' => 'Pockyt vendor.',
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
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));

    // Execute curl and get the response
    $response = curl_exec($ch);

    // Close the curl
    curl_close($ch);

    return json_decode($response, true);
}

function pockyt_link($params) {
    // Config Parameters
    $merchantNo = $params['MERCHANT_NO'];
    $storeNo = $params['STORE_NO'];
    $apiToken = $params['API_TOKEN'];  // Replace with your actual API token
    $vendor = $params['VENDOR'];
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
    $postfields['terminal'] = 'ONLINE';
    $postfields['ipnUrl'] = $systemUrl . '/modules/gateways/callback/pockyt.php';
    $postfields['callbackUrl'] = $returnUrl;
    $postfields['vendor'] = $vendor;
    $postfields['reference'] = $invoiceid;  // Replace this with your actual reference
    $postfields['settleCurrency'] = 'USD';
    $postfields['description'] = $description;  // Replace this with your actual description
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
        // Handle error
        $htmlOutput = 'Error: ' . $response['ret_msg'];
    }
    return $htmlOutput;
}
function calculateVeriSign($params, $apiToken)
{
    // Sort the parameters alphabetically according to the parameter name
    ksort($params);

    // Concatenate the parameter names and values using '=' and '&' characters
    $concatenatedParams = http_build_query($params, '', '&');

    // Calculate the MD5 value of API token
    $md5ApiToken = md5($apiToken);

    // Append the MD5 hash value of your API token to the end of your parameters with the '&' prefix
    $concatenatedParams .= "&" . $md5ApiToken;

    // Calculate the MD5 hash value of the result.
    $veriSign = md5($concatenatedParams);

    return $veriSign;
}

function pockyt_refund($params) {
    $merchantNo = $params['MERCHANT_NO'];
    $storeNo = $params['STORE_NO'];
    $apiToken = $params['API_TOKEN'];
    // Payment Parameters
    $amount = $params['amount'];
    $currency = $params['currency'];
    $transactionId = $params['transid'];
    $invoiceid = $params['invoiceid'];
    // System Parameters
    $systemUrl = $params['systemurl'];
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
    $postfields['refundAmount'] = $amount;
    $postfields['currency'] = $currency;
    $postfields['transactionNo'] = $transactionId;
    $postfields['reference'] = $invoiceid;  // Replace this with your actual reference
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