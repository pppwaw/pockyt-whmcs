<?php
// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
function calculateSign($params, $apiToken)
{
    ksort($params, SORT_STRING);
    $str = '';
    foreach ($params as $k => $v) {
        $str .= $k . '=' . $v . '&';
    }
    return md5($str . md5($apiToken));
}

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables("pockyt");

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}
logTransaction('pockyt', $_POST, "start");
// Retrieve data returned in payment gateway callback
// Varies per payment gateway
$params = [];
$param_post = $_POST;
$invoiceId = preg_split("-", $param_post["reference"])[0];
$status = $param_post["status"];
$transactionId = $param_post["transactionNo"];
$verifySign = $param_post['verifySign'];
$params["amount"] = $param_post["amount"];
$params["currency"] = $param_post["currency"];
# split to get reference
$params["reference"] = preg_split("-", $param_post["reference"])[0];
$params["settleCurrency"] = $param_post["settleCurrency"];
$params["status"] = $param_post["status"];
$params["time"] = $param_post["time"];
$params["transactionNo"] = $param_post["transactionNo"];
$token = $gatewayParams['API_TOKEN'];
$paymentAmount = $params['amount'];
$sign = calculateSign($params, $token);

$invoiceId = checkCbInvoiceID($invoiceId, "pockyt"); //查询invoice id是否存在
checkCbTransID($transactionId); //验证回调事务
if ($sign == $verifySign) {
    logTransaction('pockyt', $_POST, $status);
    if ($status == "success") {
        addInvoicePayment(
            $invoiceId,
            $transactionId,
            $paymentAmount,
            0.00,
            "pockyt"
        );
        die("success");
    }
} else {
    logTransaction("pockyt", $_POST, "verify sign fail");
    die("fail");
}



