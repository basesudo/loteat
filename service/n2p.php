<?php
/**
 * NOVA2PAY PHP Demo
 *
 * This file provides a comprehensive demonstration of the NOVA2PAY API integration,
 * based on the "N2P_JS支付接口文档(1).pdf".
 *
 * It covers:
 * - Payment Flow (Token Creation + JS Payment Form)
 * - Synchronous Notification Handling (Return URL)
 * - Asynchronous Notification Handling (Notify URL)
 * - Refund API
 * - Query API
 * - MD5 Signature Generation for requests
 * - RSA Signature Verification for notifications
 *
 * @version 1.0
 * @author Gemini
 */

// ###################################################################################
// 1. CONFIGURATION
// ###################################################################################
// Replace these with your actual test credentials provided by NOVA2PAY.
define('NOVA_ACCOUNT_ID', '10004801'); // Your merchant accountId 
define('NOVA_MD5_KEY', 'bx8POjQLKb7y2Pch9km2pVDwwBAJ8BTXR1Ad0dqr3GbfTkutmeySzht3gKibagq3sqw9WiB4dycAKhHOIHLKiqV9ApxksaLP4sMSvQJv8Kur0k5cdhvrP9AhACOZz98D'); // Your MD5 Key from the merchant portal 

// Your NOVA2PAY RSA Public Key for verifying notifications 
// Make sure to include the BEGIN/END markers and format it as a single string.
define('NOVA_RSA_PUBLIC_KEY', "-----BEGIN RSA PUBLIC KEY-----\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC4MAtJOkbtlo3ZzkpDbH7Okk7jvMGs2Cw45od68M4EwYY6orTCQDcENGIlro8QBDjTqeaQb9O/aPgnxlChcqfoPIP8hAj5NRcTQZs0skwyy/3jVXDt+1XsLnrLSEXlNqFFKRQDMMEEZDKOznt4pVwSWdZidWatOgCP0xZC3vWIFQIDAQAB\n-----END RSA PUBLIC KEY-----");
// API Endpoints for the TEST environment 
define('NOVA_API_BASE_URL', 'https://n2p-api.test.nova2pay.com');
define('NOVA_JS_SECURE_URL', 'https://n2p-secure.test.nova2pay.com');

// Demo URLs. This script will handle requests to these paths.
// In a real application, these would point to your server.
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$scriptName = $_SERVER['SCRIPT_NAME'];
define('DEMO_BASE_URL', "{$protocol}{$host}{$scriptName}");

// ###################################################################################
// 2. HELPER FUNCTIONS
// ###################################################################################


/**
 * Recursively sorts an array by its keys at all levels.
 * This is required for creating the correct signature string.
 *
 * @param array &$array The array to sort.
 */
function recursive_ksort(array &$array) {
    ksort($array, SORT_STRING);
    foreach ($array as &$value) {
        if (is_array($value)) {
            recursive_ksort($value);
        }
    }
}

/**
 * Builds the signature string from an array of parameters.
 * The process, as per documentation:
 * 1. Filter out empty values.
 * 2. Sort all keys alphabetically by ASCII order at all levels (including md5Key).
 * 3. Build a URL query string (key1=value1&key2=value2...) without URL encoding.
 *
 * @param array $params The parameters for the signature.
 * @return string The sorted and concatenated string.
 */
function build_sorted_query_string(array $params): string {
    // Filter out parameters with empty values, as they don't participate in signing 
    $params = array_filter($params, function ($value) {
        return $value !== '' && $value !== null && $value !== [];
    });

    // Recursively sort all levels of the array by key in ASCII order
    recursive_ksort($params);
    
    log_message("Sorted Params: " . print_r($params, true));
    
    // Build query string manually without URL encoding
    $pairs = [];
    foreach ($params as $key => $value) {
        if (is_array($value)) {
            // For nested arrays, convert to JSON string after sorting
            $pairs[] = $key . '=' . json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $pairs[] = $key . '=' . $value;
        }
    }
    return implode('&', $pairs);
}
/**
 * Generates the MD5 signature for API requests.
 *
 * @param array $params The request parameters.
 * @return string The uppercase MD5 signature.
 * @see 7.1 MD5 签名与验证 
 */
function generateMd5Sign(array $params): string {
    // Add the MD5 key to the parameters to be signed 
    $params['md5Key'] = NOVA_MD5_KEY;
    $stringA = build_sorted_query_string($params);
    log_message("MD5 Sign String: " . $stringA);
    // The final signature is the uppercase MD5 hash of stringA 
    return strtoupper(md5($stringA));
}

// 16 进制转为字符串
function hex2str($hex) {
    $string = "";
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
        $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }
    return $string;
}

/**
 * Alternative function following sign.php pattern more closely
 * Use this if the above doesn't work
 */
function checkSign($pubKey, $sign, $toSign, $signature_alg = OPENSSL_ALGO_SHA1) {
    $publicKeyId = openssl_pkey_get_public($pubKey);
    if ($publicKeyId === false) {
        return false;
    }
    
    $result = openssl_verify($toSign, base64_decode(hex2str($sign)), $publicKeyId, $signature_alg);
    
    // Only call openssl_free_key if PHP version is less than 8.0
    if (PHP_VERSION_ID < 80000) {
        openssl_free_key($publicKeyId);
    }
    
    return $result === 1;
}

/**
 * Get signature string following sign.php pattern
 */
function getSignString($params) {
    unset($params['tf_sign']); // Remove signature from params
    unset($params['action']); // Remove MD5 key if present
    ksort($params);
    reset($params);

    $pairs = array();
    foreach ($params as $k => $v) {
        if (!empty($v)) {
            $pairs[] = "$k=$v";
        }
    }

    return implode('&', $pairs);
}

/**
 * Makes a POST request to the NOVA2PAY API using cURL.
 *
 * @param string $url The API endpoint URL.
 * @param array $data The data to post, in key-value format.
 * @return array The decoded JSON response.
 */
function makeApiRequest(string $url, array $data): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    // Per documentation, request format is key-value, but examples show JSON.
    // We will send as JSON as it correctly handles nested structures.
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['resultCode' => 'CURL_ERROR', 'resultMessage' => $error];
    }

    return json_decode($response, true) ?? ['resultCode' => 'INVALID_JSON', 'resultMessage' => 'Failed to decode JSON response.'];
}


// ###################################################################################
// 3. PAGE ROUTER & LOGIC
// ###################################################################################

$action = $_REQUEST['action'] ?? 'default';

// Simple logging for demo purposes
function log_message($message) {
    $logFile = 'nova2pay_demo_log.txt';
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

log_message("Action triggered: {$action}");
if (!empty($_POST)) {
    log_message("POST data: " . http_build_query($_POST));
}
if (!empty($_GET)) {
    log_message("GET data: " . http_build_query($_GET));
}


switch ($action) {
    case 'create_token':
        header('Content-Type: application/json');
        
        $merOrderId = 'ORDER-' . uniqid();
        $merTradeId = 'TRADE-' . uniqid();
        $amountValue = number_format((float)($_POST['amount'] ?? '25.50'), 2, '.', '');

        // Prepare the request data for creating a token 
        $requestData = [
            "accountId" => NOVA_ACCOUNT_ID,
            "merOrderId" => $merOrderId,
            "merTradeId" => $merTradeId,
            "shopperUrl" => DEMO_BASE_URL . "?action=return", // Sync notification URL 
            "notifyUrl" => DEMO_BASE_URL . "?action=notify", // Async notification URL 
            "amount" => [
                "currency" => "USD", // Per ISO-4217 
                "value" => $amountValue
            ],
            "billingAddress" => [ // Optional, but recommended 
                "country" => "US", // Per ISO-3166-1 
                "firstName" => "John",
                "lastName" => "Doe",
                "stateOrProvince" => "CA",
                "city" => "Walnut",
                "phone" => "13226264455",
                "houseNumberOrName" => "888",
                "street" => "some random street",
                "postalCode" => "91789",
                "email" => "johndoe.test@example.com"
            ],
            // Add other optional fields like 'basket', 'deliveryAddress' as needed
        ];

        // Generate the MD5 signature 
        $requestData['tf_sign'] = generateMd5Sign($requestData);

        // Make the API call 
        $url = NOVA_API_BASE_URL . '/payment-order/api/transaction/session/pay';
        $response = makeApiRequest($url, $requestData);
        
        log_message("Create Token Request: " . json_encode($requestData));
        log_message("Create Token Response: " . json_encode($response));

        echo json_encode($response);
        exit;

    case 'return':
        // Synchronous notification handler 
        // NOVA2PAY redirects the user's browser here.
        $title = "Payment Return";
        $responseData = $_GET;
        $isSuccess = false;
        $message = "Verification failed or payment was not successful.";

        if (!empty($responseData) && isset($responseData['tf_sign'])) {
            $signString = getSignString($responseData);
            log_message("Sign string for verification: " . $signString);
            $isValid = checkSign(NOVA_RSA_PUBLIC_KEY, $responseData['tf_sign'], $signString);
            if ($isValid) {
                // Signature is valid, check the transaction status
                if (isset($responseData['tradeStatus']) && $responseData['tradeStatus'] === 'Approved') {
                    $isSuccess = true;
                    $message = "Payment successful and signature verified!";
                } else {
                     $message = "Signature verified, but payment status is: " . htmlspecialchars($responseData['tradeStatus'] ?? 'Unknown');
                }
            } else {
                $message = "RSA Signature Verification Failed!";
            }
        } else {
            $message = "No response data or signature received.";
        }
        
        display_header($title);
        echo '<h1>Synchronous Notification (Return URL)</h1>';
        echo '<div class="message ' . ($isSuccess ? 'success' : 'error') . '">' . $message . '</div>';
        echo '<h2>Received Data:</h2><pre>' . htmlspecialchars(print_r($responseData, true)) . '</pre>';
        echo '<a href="' . DEMO_BASE_URL . '">Back to Home</a>';
        display_footer();
        exit;
    
    case 'notify':
        // Asynchronous notification handler 
        // This is a server-to-server call from NOVA2PAY.
        $responseData = $_POST;
        log_message("Async Notify Received: " . http_build_query($responseData));

        if (!empty($responseData) && isset($responseData['tf_sign'])) {
            $signString = getSignString($responseData);
            log_message("Sign string for verification: " . $signString);
            $isValid = checkSign(NOVA_RSA_PUBLIC_KEY, $responseData['tf_sign'], $signString);
            if ($isValid) {
                log_message("Async Notify: RSA Signature VERIFIED.");
                // TODO: Add your business logic here
                // e.g., check if orderId exists, check if amount is correct, update order status in your database.
                // The 'tradeStatus' indicates the final outcome of the transaction.
                $status = $responseData['tradeStatus'] ?? 'Unknown';
                $merTradeId = $responseData['merTradeId'] ?? 'Unknown';
                log_message("Processing trade {$merTradeId} with status {$status}.");
                
                // You MUST respond with "success" or "ok" to stop NOVA2PAY from resending the notification 
                echo "success";
            } else {
                log_message("Async Notify: RSA Signature FAILED.");
                // Do not respond with "success" so it might be resent.
                http_response_code(400); // Bad Request
                echo "Signature verification failed.";
            }
        } else {
            log_message("Async Notify: Invalid request, no data or signature.");
            http_response_code(400);
            echo "Invalid request.";
        }
        exit;

    case 'refund':
        display_header("Refund Result");
        $merTradeId = 'REFUND-' . uniqid();
        $orgTradeId = $_POST['orgTradeId'] ?? '';
        $amount = $_POST['amount'] ?? '';

        if (empty($orgTradeId) || empty($amount)) {
            echo "<h1>Error</h1><p>Original Trade ID and Amount are required for refund.</p>";
            echo '<p><a href="' . DEMO_BASE_URL . '?action=refund_form">Try again</a></p>';
        } else {
            // Prepare refund request data 
             $requestData = [
                "accountId" => NOVA_ACCOUNT_ID,
                "merTradeId" => $merTradeId, // A new unique ID for the refund transaction
                "orgTradeId" => $orgTradeId, // The original platform transaction ID to be refunded
                "version" => "2.2",
                "modificationAmount" => [
                    "currency" => "USD",
                    "value" => number_format((float)$amount, 2, '.', '')
                ]
            ];

            $requestData['tf_sign'] = generateMd5Sign($requestData);
            $url = NOVA_API_BASE_URL . '/payment-order/api/transaction/refund'; // 
            $response = makeApiRequest($url, $requestData);

            echo "<h1>Refund API Response</h1>";
            echo "<h2>Request Data:</h2><pre>" . htmlspecialchars(print_r($requestData, true)) . "</pre>";
            echo "<h2>Response Data:</h2><pre>" . htmlspecialchars(print_r($response, true)) . "</pre>";
        }
        echo '<a href="' . DEMO_BASE_URL . '">Back to Home</a>';
        display_footer();
        exit;

    case 'query':
        display_header("Query Result");
        $tradeId = $_POST['tradeId'] ?? '';
        $merTradeId = $_POST['merTradeId'] ?? '';

        if (empty($tradeId) && empty($merTradeId)) {
             echo "<h1>Error</h1><p>Either Platform Trade ID or Merchant Trade ID is required for query.</p>";
             echo '<p><a href="' . DEMO_BASE_URL . '?action=query_form">Try again</a></p>';
        } else {
            // Prepare query request data 
            $requestData = [
                "accountId" => NOVA_ACCOUNT_ID,
            ];
            // One of merTradeId or tradeId is required
            if (!empty($merTradeId)) {
                $requestData['merTradeId'] = $merTradeId;
            } else {
                $requestData['tradeId'] = $tradeId;
            }

            $requestData['tf_sign'] = generateMd5Sign($requestData);
            $url = NOVA_API_BASE_URL . '/payment-order/api/query/transaction'; // 
            $response = makeApiRequest($url, $requestData);

            echo "<h1>Query API Response</h1>";
            echo "<h2>Request Data:</h2><pre>" . htmlspecialchars(print_r($requestData, true)) . "</pre>";
            echo "<h2>Response Data:</h2><pre>" . htmlspecialchars(print_r($response, true)) . "</pre>";
        }
        echo '<a href="' . DEMO_BASE_URL . '">Back to Home</a>';
        display_footer();
        exit;

    case 'refund_form':
        display_header("Refund Transaction");
        ?>
        <h1>Refund a Transaction</h1>
        <p>This initiates a refund for a previously successful transaction.</p>
        <form action="<?php echo DEMO_BASE_URL; ?>" method="POST" class="api-form">
            <input type="hidden" name="action" value="refund">
            <div class="form-group">
                <label for="orgTradeId">Original Platform Trade ID (<code>orgTradeId</code>)</label>
                <input type="text" id="orgTradeId" name="orgTradeId" placeholder="e.g., TD20240821..." required>
            </div>
            <div class="form-group">
                <label for="amount">Refund Amount (<code>modificationAmount.value</code>)</label>
                <input type="number" step="0.01" id="amount" name="amount" placeholder="e.g., 22.00" required>
            </div>
            <button type="submit">Submit Refund</button>
        </form>
        <?php
        display_footer();
        exit;

    case 'query_form':
        display_header("Query Transaction");
        ?>
        <h1>Query a Transaction</h1>
        <p>Query a transaction status using either the Merchant Trade ID or the Platform Trade ID.</p>
        <form action="<?php echo DEMO_BASE_URL; ?>" method="POST" class="api-form">
            <input type="hidden" name="action" value="query">
            <div class="form-group">
                <label for="merTradeId">Merchant Trade ID (<code>merTradeId</code>)</label>
                <input type="text" id="merTradeId" name="merTradeId" placeholder="e.g., TRADE-...">
            </div>
             <p style="text-align:center; margin: 10px 0;">OR</p>
            <div class="form-group">
                <label for="tradeId">Platform Trade ID (<code>tradeId</code>)</label>
                <input type="text" id="tradeId" name="tradeId" placeholder="e.g., TD20240821...">
            </div>
            <button type="submit">Query Transaction</button>
        </form>
        <?php
        display_footer();
        exit;
    
    case 'pay_form':
    default:
        display_header("NOVA2PAY JS Payment Demo");
        ?>
        <h1>NOVA2PAY JS Payment Demo</h1>
        <p>Follow these steps to complete a test payment.</p>

        <div id="step1">
            <h2>Step 1: Create Payment Token</h2>
            <p>First, we ask our backend to create a transaction token from NOVA2PAY.</p>
            <form id="tokenForm" class="api-form">
                 <div class="form-group">
                    <label for="amount">Amount (USD)</label>
                    <input type="number" step="0.01" id="amount" value="25.50">
                </div>
                <button type="button" id="getTokenBtn">Get Payment Token</button>
            </form>
            <div id="tokenResult" class="message" style="display:none;"></div>
        </div>

        <div id="step2" style="display:none;">
            <h2>Step 2: Enter Card Details and Pay</h2>
            <p>The token was created successfully. Now use the NOVA2PAY JS form to submit card details directly to their servers.</p>
            <form id="paymentForm" class="api-form">
                <div class="form-group">
                    <label for="firstName">Cardholder First Name</label>
                    <input type="text" id="firstName" value="John">
                </div>
                <div class="form-group">
                    <label for="lastName">Cardholder Last Name</label>
                    <input type="text" id="lastName" value="Doe">
                </div>
                <div class="form-group">
                    <label for="number">Card Number</label>
                    <input type="text" id="number" placeholder="Enter card number without spaces">
                </div>
                 <div class="form-group">
                    <label for="expiryMonth">Expiry Month (MM)</label>
                    <input type="text" id="expiryMonth" placeholder="e.g., 03">
                </div>
                <div class="form-group">
                    <label for="expiryYear">Expiry Year (YYYY)</label>
                    <input type="text" id="expiryYear" placeholder="e.g., 2027">
                </div>
                <div class="form-group">
                    <label for="cvc">CVC/CVV</label>
                    <input type="text" id="cvc" placeholder="e.g., 123">
                </div>
                <button type="button" id="payBtn">Submit Payment</button>
            </form>
            <div id="paymentResult" class="message" style="display:none;"></div>
        </div>
        
        <hr>
        <h1>Other API Functions</h1>
        <div class="other-actions">
            <a href="<?php echo DEMO_BASE_URL; ?>?action=refund_form">Go to Refund</a>
            <a href="<?php echo DEMO_BASE_URL; ?>?action=query_form">Go to Query</a>
        </div>

        <script src="<?php echo NOVA_JS_SECURE_URL; ?>/js/nova2pay-pay.min.js"></script>
        
        <script>
            // Global vars to hold token info
            let aToken, oId, jsTranType;

            // Step 1 Logic
            document.getElementById('getTokenBtn').addEventListener('click', function() {
                this.disabled = true;
                this.textContent = 'Requesting...';
                const amount = document.getElementById('amount').value;

                fetch('<?php echo DEMO_BASE_URL; ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'create_token',
                        amount: amount
                    })
                })
                .then(response => response.json())
                .then(data => {
                    const resultDiv = document.getElementById('tokenResult');
                    if (data.resultCode === '10000') {
                        // Success: store tokens and show next step 
                        aToken = data.aToken;
                        oId = data.oId;
                        jsTranType = data.jsTranType || ''; // 
                        
                        resultDiv.className = 'message success';
                        resultDiv.innerHTML = `<strong>Success!</strong> Token received.<br>aToken: ${aToken.substring(0, 30)}...<br>oId: ${oId}`;
                        resultDiv.style.display = 'block';

                        document.getElementById('step1').style.display = 'none';
                        document.getElementById('step2').style.display = 'block';
                    } else {
                        // Error
                        resultDiv.className = 'message error';
                        resultDiv.innerHTML = `<strong>Error:</strong> ${data.resultMessage} (Code: ${data.resultCode})`;
                        resultDiv.style.display = 'block';
                        this.disabled = false;
                        this.textContent = 'Get Payment Token';
                    }
                })
                .catch(err => {
                    const resultDiv = document.getElementById('tokenResult');
                    resultDiv.className = 'message error';
                    resultDiv.innerHTML = '<strong>Request Failed:</strong> ' + err;
                    resultDiv.style.display = 'block';
                    this.disabled = false;
                    this.textContent = 'Get Payment Token';
                });
            });

            // Step 2 Logic
            document.getElementById('payBtn').addEventListener('click', function() {
                this.disabled = true;
                this.textContent = 'Processing...';

                const paymentResultDiv = document.getElementById('paymentResult');
                paymentResultDiv.style.display = 'none';

                // Call the Nova2pay JS library 
                new Nova2pay({
                    aToken: aToken, // from create_token response 
                    oId: oId,       // from create_token response 
                    firstName: document.getElementById("firstName").value,
                    lastName: document.getElementById("lastName").value,
                    cvc: document.getElementById("cvc").value,
                    number: document.getElementById("number").value,
                    expiryMonth: document.getElementById('expiryMonth').value,
                    expiryYear: document.getElementById('expiryYear').value,
                    jsUrl: '<?php echo NOVA_API_BASE_URL; ?>', // Test API URL 
                    tranType: jsTranType 
                }, function (res) {
                    // This function is the callback that handles the result 
                    if (res && res.status === 'SUCCESS' && res.successMsg.resultCode === '10000') {
                        // On success, redirect the user to the merchant's result page.
                        // The URL is provided in the response and contains signed transaction details. 
                        paymentResultDiv.className = 'message success';
                        paymentResultDiv.innerHTML = 'Payment successful! Redirecting...';
                        paymentResultDiv.style.display = 'block';

                        var url = decodeURIComponent(res.successMsg.redirectUrl);
                        window.location.href = url;
                    } else {
                        // On failure, display the error message 
                        const errorMsg = res ? `${res.errorMsg} (Code: ${res.errorCode})` : 'An unknown error occurred.';
                        paymentResultDiv.className = 'message error';
                        paymentResultDiv.innerHTML = '<strong>Payment Failed:</strong> ' + errorMsg;
                        paymentResultDiv.style.display = 'block';
                        document.getElementById('payBtn').disabled = false;
                        document.getElementById('payBtn').textContent = 'Submit Payment';
                    }
                });
            });
        </script>
        <?php
        display_footer();
        exit;
}

// ###################################################################################
// 4. HTML TEMPLATE FUNCTIONS
// ###################################################################################

function display_header($title = "NOVA2PAY Demo") {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 20px auto; padding: 0 20px; background-color: #f4f7f6; }
        h1, h2 { color: #2c3e50; }
        h1 { border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        pre { background-color: #ecf0f1; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word; border: 1px solid #bdc3c7; }
        .container { background-color: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .message { padding: 15px; border-radius: 5px; margin: 15px 0; border: 1px solid; }
        .success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .error { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .api-form { display: flex; flex-direction: column; gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 5px; font-weight: bold; }
        .form-group input { padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        button, .other-actions a {
            padding: 12px 20px; font-size: 16px; color: #fff; background-color: #3498db;
            border: none; border-radius: 5px; cursor: pointer; text-align: center;
        }
        button:hover, .other-actions a:hover { background-color: #2980b9; }
        button:disabled { background-color: #bdc3c7; cursor: not-allowed; }
        hr { border: 0; height: 1px; background: #ddd; margin: 30px 0; }
        .other-actions { display: flex; gap: 15px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
HTML;
}

function display_footer() {
    $year = date('Y');
    echo <<<HTML
    </div>
    <footer>
        <p style="text-align:center; color: #7f8c8d; margin-top: 20px;">
            NOVA2PAY Demo &copy; {$year}. For testing purposes only.
        </p>
    </footer>
</body>
</html>
HTML;
}