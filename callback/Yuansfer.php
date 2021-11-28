<?php
/**
 * WHMCS Sample Payment Callback File
 *
 * This sample file demonstrates how a payment gateway callback should be
 * handled within WHMCS.
 *
 * It demonstrates verifying that the payment gateway module is active,
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/callbacks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Retrieve data returned in payment gateway callback
// Varies per payment gateway
$params = [];
$param_post = $_POST;
$invoiceId = $param_post["reference"];
$status = $param_post["status"];
$transactionId = $param_post["transactionNo"];
$verifySign = $param_post['verifySign'];
$params["amount"] = $param_post["amount"];
$params["currency"] = $param_post["currency"];
$params["reference"] = $param_post["reference"];
$params["settleCurrency"] = $param_post["settleCurrency"];
$params["status"] = $param_post["status"];
$params["time"] = $param_post["time"];
$params["transactionNo"] = $param_post["transactionNo"];
$token = $gatewayParams['API_TOKEN'];
$paymentAmount = $params['amount'];

ksort($params, SORT_STRING);

$str = '';
foreach ($params as $k => $v) {
    $str .= $k . '=' . $v . '&';
}
logActivity($str,0);
$sign = md5($str . md5($token));

$invoiceId = checkCbInvoiceID($invoiceId, $gatewayModuleName); //查询invoice id是否存在
checkCbTransID($transactionId); //验证回调事务
if($sign == $verifySign)
{
    logTransaction($gatewayParams['name'], $_POST, $status);
    if ($status == "success"){
        addInvoicePayment(
            $invoiceId,
            $transactionId,
            $paymentAmount,
            0.00,
            $gatewayModuleName
        );
        logTransaction($gatewayModuleName, $_POST, "success");
        die("success");
    }
}
else{
    logTransaction($gatewayModuleName, $_POST, "fail");
    die("fail");
}



