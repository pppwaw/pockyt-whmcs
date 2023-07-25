<?php
// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
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
// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables("pockyt");

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
$sign = calculateVeriSign($params, $token);

$invoiceId = checkCbInvoiceID($invoiceId, "pockyt"); //查询invoice id是否存在
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
            "pockyt"
        );
        die("success");
    }
}
else{
    logTransaction("pockyt", $_POST, "fail");
    die("fail");
}



