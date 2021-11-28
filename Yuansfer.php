<?php
/**
 * This is the gateway module of Yuansfer
 * We use cashier to collect the funds
 *
 */

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function Yuansfer_MetaData()
{
    return array(
        'DisplayName' => 'Yuansfer, Go Global, Think Local',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * Supported field types include:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each field type and their possible configuration parameters are
 * provided in the sample function below.
 *
 * @return array
 */
function Yuansfer_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Yuansfer',
        ),
        'MERCHANT_NO' => array(
            'FriendlyName' => 'Merchant No',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'The merchant NO.',
        ),
        'STORE_NO' =>array(
            'FriendlyName' => 'Store No',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'The store NO.',
        ),
        'API_TOKEN' => array(
            'FriendlyName' => 'API TOKEN',
            'Type' => 'password',
            'Size' => '4096',
            'Default' => '',
            'Description' => 'Yuansfer API token.',
        ),
    );
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 * @return string
 */
function Yuansfer_link($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['MERCHANT_NO'];
    $secretKey = $params['API_TOKEN'];
    $Store_No = $params['STORE_NO'];
    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    $url = 'https://mapi.yuansfer.com/online/v3/secure-pay';

    $postfields = array();

    $postfields['reference'] = $invoiceId;
    $postfields['amount'] = $amount;
    $postfields['currency'] = $currencyCode;
    $postfields['ipnUrl'] = $systemUrl . 'modules/gateways/callback/' . $moduleName . '.php';
    $postfields['terminal'] = 'ONLINE';
    $postfields['vendor'] = 'alipay';
    $postfields['settleCurrency'] = 'USD';
    $postfields['merchantNo'] = $accountId;
    $postfields['storeNo'] = $Store_No;
    $postfields['callbackUrl'] = $returnUrl;
    $postfields['description'] = $description;

    ksort($postfields, SORT_STRING);
    $str = '';
    foreach ($postfields as $k => $v) {
        $str .= $k . '=' . $v . '&';
    }
    $postfields['verifySign'] = md5($str . md5($secretKey));
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postfields),
    ));
    $result = curl_exec($ch);
    $json_result = json_decode($result, true);
    $cashier_url = $json_result['result']['cashierUrl'];
    $htmlOutput = '<form method="get" action="' . $cashier_url . '">';
    $htmlOutput .= '<input type="submit" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';
    return $htmlOutput;
}

/**
 * Refund transaction.
 *
 * Called when a refund is requested for a previously successful transaction.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/refunds/
 *
 * @return array Transaction response status
 */
/* function gatewaymodule_refund($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];
    $testMode = $params['testMode'];
    $dropdownField = $params['dropdownField'];
    $radioField = $params['radioField'];
    $textareaField = $params['textareaField'];

    // Transaction Parameters
    $transactionIdToRefund = $params['transid'];
    $refundAmount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    // perform API call to initiate refund and interpret result

    return array(
        // 'success' if successful, otherwise 'declined', 'error' for failure
        'status' => 'success',
        // Data to be recorded in the gateway log - can be a string or array
        'rawdata' => $responseData,
        // Unique Transaction ID for the refund transaction
        'transid' => $refundTransactionId,
        // Optional fee amount for the fee value refunded
        'fees' => $feeAmount,
    );
}
 */

/**
 * Cancel subscription.
 *
 * If the payment gateway creates subscriptions and stores the subscription
 * ID in tblhosting.subscriptionid, this function is called upon cancellation
 * or request by an admin user.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/subscription-management/
 *
 * @return array Transaction response status
 */
/**
 * function gatewaymodule_cancelSubscription($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['MERCHANT_NO'];
    $secretKey = $params['STORE_NO'];
    $Store_No = $params['STORE_NO'];

    // Subscription Parameters
    $subscriptionIdToCancel = $params['subscriptionID'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    // perform API call to cancel subscription and interpret result

    return array(
        // 'success' if successful, any other value for failure
        'status' => 'success',
        // Data to be recorded in the gateway log - can be a string or array
        'rawdata' => $responseData,
    );

}
 *  */