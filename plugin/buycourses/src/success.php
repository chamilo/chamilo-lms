<?php
/* For license terms, see /license.txt */
/**
 * Success page for the purchase of a course in the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Init
 */
use ChamiloSession as Session;

require_once '../config.php';
require_once dirname(__FILE__) . '/buy_course.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'course.lib.php';

$tableBuyCoursePaypal = Database::get_main_table(TABLE_BUY_COURSE_PAYPAL);

$plugin = BuyCoursesPlugin::create();

/**
 * Paypal data
 */
$sql = "SELECT * FROM $tableBuyCoursePaypal WHERE id='1';";
$res = Database::query($sql);
$row = Database::fetch_assoc($res);
$pruebas = ($row['sandbox'] == "YES") ? true: false;
$paypalUsername = $row['username'];
$paypalPassword = $row['password'];
$paypalSignature = $row['signature'];
require_once("paypalfunctions.php");

/**
 * PayPal Express Checkout Call
 */

// Check to see if the Request object contains a variable named 'token'	
$token = "";
if (isset($_REQUEST['token'])) {
    $token = $_REQUEST['token'];
}

// If the Request object contains the variable 'token' then it means that the user is coming from PayPal site.	
if ($token != "") {
    $sql = "SELECT * FROM $tableBuyCoursePaypal WHERE id='1';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    $paypalUsername = $row['username'];
    $paypalPassword = $row['password'];
    $paypalSignature = $row['signature'];
    require_once 'paypalfunctions.php';

    /**
     * Calls the GetExpressCheckoutDetails API call
     * The GetShippingDetails function is defined in PayPalFunctions.jsp
     *included at the top of this file.
     */
    $resArray = GetShippingDetails($token);
    $ack = strtoupper($resArray["ACK"]);
    if ($ack == "SUCCESS" || $ack == "SUCESSWITHWARNING") {
        /**
         * The information that is returned by the GetExpressCheckoutDetails
         * call should be integrated by the partner into his Order Review page
         */
        $email = $resArray["EMAIL"]; // ' Email address of payer.
        $payerId = $resArray["PAYERID"]; // ' Unique PayPal customer account identification number.
        $payerStatus = $resArray["PAYERSTATUS"]; // ' Status of payer. Character length and limitations: 10 single-byte alphabetic characters.
        $salutation = $resArray["SALUTATION"]; // ' Payer's salutation.
        $firstName = $resArray["FIRSTNAME"]; // ' Payer's first name.
        $middleName = $resArray["MIDDLENAME"]; // ' Payer's middle name.
        $lastName = $resArray["LASTNAME"]; // ' Payer's last name.
        $suffix = $resArray["SUFFIX"]; // ' Payer's suffix.
        $cntryCode = $resArray["COUNTRY_CODE"]; // ' Payer's country of residence in the form of ISO standard 3166 two-character country codes.
        $business = $resArray["BUSINESS"]; // ' Payer's business name.
        $shipToName = $resArray["PAYMENTREQUEST_0_SHIPTONAME"]; // ' Person's name associated with this address.
        $shipToStreet = $resArray["PAYMENTREQUEST_0_SHIPTOSTREET"]; // ' First street address.
        $shipToStreet2 = $resArray["PAYMENTREQUEST_0_SHIPTOSTREET2"]; // ' Second street address.
        $shipToCity = $resArray["PAYMENTREQUEST_0_SHIPTOCITY"]; // ' Name of city.
        $shipToState = $resArray["PAYMENTREQUEST_0_SHIPTOSTATE"]; // ' State or province
        $shipToCntryCode = $resArray["PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE"]; // ' Country code.
        $shipToZip = $resArray["PAYMENTREQUEST_0_SHIPTOZIP"]; // ' U.S. Zip code or other country-specific postal code.
        $addressStatus = $resArray["ADDRESSSTATUS"]; // ' Status of street address on file with PayPal
        $invoiceNumber = $resArray["INVNUM"]; // ' Your own invoice or tracking number, as set by you in the element of the same name in SetExpressCheckout request .
        $phonNumber = $resArray["PHONENUM"]; // ' Payer's contact telephone number. Note:  PayPal returns a contact telephone number only if your Merchant account profile settings require that the buyer enter one.
    } else {
        //Display a user friendly Error on the page using any of the following error information returned by PayPal
        $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
        $ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
        $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
        $ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);

        echo "<br />GetExpressCheckoutDetails API call failed. ";
        echo "<br />Detailed Error Message: " . $ErrorLongMsg;
        echo "<br />Short Error Message: " . $ErrorShortMsg;
        echo "<br />Error Code: " . $ErrorCode;
        echo "<br />Error Severity Code: " . $ErrorSeverityCode;
    }
}


if (!isset($_POST['paymentOption'])) {
    // Confirm the order
    $_cid = 0;
    $templateName = $plugin->get_lang('PaymentMethods');
    $interbreadcrumb[] = array("url" => "list.php", "name" => $plugin->get_lang('CourseListOnSale'));

    $tpl = new Template($templateName);

    $code = $_SESSION['bc_course_code'];
    $courseInfo = courseInfo($code);

    $tpl->assign('course', $courseInfo);
    $tpl->assign('server', $_configuration['root_web']);
    $tpl->assign('title', $_SESSION['bc_course_title']);
    $tpl->assign('price', $_SESSION['Payment_Amount']);
    $tpl->assign('currency', $_SESSION['bc_currency_type']);
    if (!isset($_SESSION['_user'])) {
        $tpl->assign('name', $_SESSION['bc_user']['firstName'] . ' ' . $_SESSION['bc_user']['lastName']);
        $tpl->assign('email', $_SESSION['bc_user']['mail']);
        $tpl->assign('user', $_SESSION['bc_user']['username']);
    } else {
        $tpl->assign('name', $_SESSION['bc_user']['firstname'] . ' ' . $_SESSION['bc_user']['lastname']);
        $tpl->assign('email', $_SESSION['bc_user']['email']);
        $tpl->assign('user', $_SESSION['bc_user']['username']);
    }


    $listing_tpl = 'buycourses/view/success.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();

} else {
    /**
     * PayPal Express Checkout Call
     */
    $PaymentOption = $_POST['paymentOption'];
    $sql = "SELECT * FROM $tableBuyCoursePaypal WHERE id='1';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    $paypalUsername = $row['username'];
    $paypalPassword = $row['password'];
    $paypalSignature = $row['signature'];
    require_once("paypalfunctions.php");
    if ($PaymentOption == "PayPal") {

        /**
         * The paymentAmount is the total value of
         * the shopping cart, that was set
         * earlier in a session variable
         * by the shopping cart page
         */
        $finalPaymentAmount = $_SESSION["Payment_Amount"];

        /**
         * Calls the DoExpressCheckoutPayment API call
         * The ConfirmPayment function is defined in the file PayPalFunctions.jsp,
         * that is included at the top of this file.
         */
        $resArray = ConfirmPayment($finalPaymentAmount);
        $ack = strtoupper($resArray["ACK"]);
        if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {

            /**
             * THE PARTNER SHOULD SAVE THE KEY TRANSACTION RELATED INFORMATION LIKE transactionId & orderTime
             * IN THEIR OWN  DATABASE
             * AND THE REST OF THE INFORMATION CAN BE USED TO UNDERSTAND THE STATUS OF THE PAYMENT
             */

            $transactionId = $resArray["PAYMENTINFO_0_TRANSACTIONID"]; // ' Unique transaction ID of the payment. Note:  If the PaymentAction of the request was Authorization or Order, this value is your AuthorizationID for use with the Authorization & Capture APIs.
            $transactionType = $resArray["PAYMENTINFO_0_TRANSACTIONTYPE"]; //' The type of transaction Possible values: l  cart l  express-checkout
            $paymentType = $resArray["PAYMENTINFO_0_PAYMENTTYPE"]; //' Indicates whether the payment is instant or delayed. Possible values: l  none l  echeck l  instant
            $orderTime = $resArray["PAYMENTINFO_0_ORDERTIME"]; //' Time/date stamp of payment
            $amt = $resArray["PAYMENTINFO_0_AMT"]; //' The final amount charged, including any shipping and taxes from your Merchant Profile.
            $currencyCode = $resArray["PAYMENTINFO_0_CURRENCYCODE"]; //' A three-character currency code for one of the currencies listed in PayPay-Supported Transactional Currencies. Default: USD.
            $feeAmt = $resArray["PAYMENTINFO_0_FEEAMT"]; //' PayPal fee amount charged for the transaction
            $settleAmt = $resArray["PAYMENTINFO_0_SETTLEAMT"]; //' Amount deposited in your PayPal account after a currency conversion.
            $taxAmt = $resArray["PAYMENTINFO_0_TAXAMT"]; //' Tax charged on the transaction.
            $exchangeRate = $resArray["PAYMENTINFO_0_EXCHANGERATE"]; //' Exchange rate if a currency conversion occurred. Relevant only if your are billing in their non-primary currency. If the customer chooses to pay with a currency other than the non-primary currency, the conversion occurs in the customer's account.

            /**
             * Status of the payment:
             * Completed: The payment has been completed, and the funds have been added successfully to your account balance.
             * Pending: The payment is pending. See the PendingReason element for more information.
             */

            $paymentStatus = $resArray["PAYMENTINFO_0_PAYMENTSTATUS"];

            /**
             * The reason the payment is pending:
             * none: No pending reason
             * address: The payment is pending because your customer did not include a confirmed
             * shipping address and your Payment Receiving Preferences is set such that you want to
             * manually accept or deny each of these payments. To change your preference, go to the Preferences section of your Profile.
             * echeck: The payment is pending because it was made by an eCheck that has not yet cleared.
             * intl: The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism.
             * You must manually accept or deny this payment from your Account Overview.
             * multi-currency: You do not have a balance in the currency sent, and you do not have your
             * Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment.
             * verify: The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.
             * other: The payment is pending for a reason other than those listed above. For more information, contact PayPal customer service.
             */
            $pendingReason = $resArray["PAYMENTINFO_0_PENDINGREASON"];

            /**
             * The reason for a reversal if TransactionType is reversal:
             *  none: No reason code
             *  chargeback: A reversal has occurred on this transaction due to a chargeback by your customer.
             *  guarantee: A reversal has occurred on this transaction due to your customer triggering a money-back guarantee.
             *  buyer-complaint: A reversal has occurred on this transaction due to a complaint about the transaction from your customer.
             *  refund: A reversal has occurred on this transaction because you have given the customer a refund.
             *  other: A reversal has occurred on this transaction due to a reason not listed above.
             */

            $reasonCode = $resArray["PAYMENTINFO_0_REASONCODE"];

            // Insert the user information to activate the user
            if ($paymentStatus == "Completed") {
                $user_id = $_SESSION['bc_user_id'];
                $course_code = $_SESSION['bc_course_codetext'];
                $all_course_information = CourseManager::get_course_information($course_code);

                if (CourseManager::subscribe_user($user_id, $course_code)) {
                    $send = api_get_course_setting('email_alert_to_teacher_on_new_user_in_course', $course_code);
                    if ($send == 1) {
                        CourseManager::email_to_tutor($user_id, $course_code, $send_to_tutor_also = false);
                    } else if ($send == 2) {
                        CourseManager::email_to_tutor($user_id, $course_code, $send_to_tutor_also = true);
                    }
                    $url = Display::url($all_course_information['title'], api_get_course_url($course_code));
                    $_SESSION['bc_message'] = 'EnrollToCourseXSuccessful';
                    $_SESSION['bc_url'] = $url;
                    $_SESSION['bc_success'] = true;
                } else {
                    $_SESSION['bc_message'] = 'ErrorContactPlatformAdmin';
                    $_SESSION['bc_success'] = false;
                }
                // Activate the use
                $TABLE_USER = Database::get_main_table(TABLE_MAIN_USER);
                $sql = "UPDATE " . $TABLE_USER . "	SET active='1' WHERE user_id='" . $_SESSION['bc_user_id'] . "'";
                Database::query($sql);

                $user_table = Database::get_main_table(TABLE_MAIN_USER);
                $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);
                $track_e_login = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

                $sql = "SELECT user.*, a.user_id is_admin, login.login_date
					FROM $user_table
					LEFT JOIN $admin_table a
					ON user.user_id = a.user_id
					LEFT JOIN $track_e_login login
					ON user.user_id  = login.login_user_id
					WHERE user.user_id = '" . $_SESSION['bc_user_id'] . "'
					ORDER BY login.login_date DESC LIMIT 1";

                $result = Database::query($sql);

                if (Database::num_rows($result) > 0) {
                    // Extracting the user data
                    $uData = Database::fetch_array($result);

                    $_user = _api_format_user($uData, false);
                    $_user['lastLogin'] = api_strtotime($uData['login_date'], 'UTC');

                    $is_platformAdmin = (bool)(!is_null($uData['is_admin']));
                    $is_allowedCreateCourse = (bool)(($uData ['status'] == COURSEMANAGER) or (api_get_setting('drhCourseManagerRights') and $uData['status'] == DRH));
                    ConditionalLogin::check_conditions($uData);

                    Session::write('_user', $_user);

                    UserManager::update_extra_field_value($_user['user_id'], 'already_logged_in', 'true');
                    Session::write('is_platformAdmin', $is_platformAdmin);
                    Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);
                } else {
                    header('location:' . api_get_path(WEB_PATH));
                }

                // Delete variables
                unset($_SESSION['bc_user_id']);
                unset($_SESSION['bc_course_code']);
                unset($_SESSION['bc_course_codetext']);
                unset($_SESSION['bc_course_title']);
                unset($_SESSION['bc_user']);
                unset($_SESSION["Payment_Amount"]);
                unset($_SESSION["sec_token"]);
                unset($_SESSION["currencyCodeType"]);
                unset($_SESSION["PaymentType"]);
                unset($_SESSION["nvpReqArray"]);
                unset($_SESSION['TOKEN']);
                header('Location:list.php');
            } else {
                $_SESSION['bc_message'] = 'CancelOrder';
                unset($_SESSION['bc_course_code']);
                unset($_SESSION['bc_course_title']);
                unset($_SESSION["Payment_Amount"]);
                unset($_SESSION["currencyCodeType"]);
                unset($_SESSION["PaymentType"]);
                unset($_SESSION["nvpReqArray"]);
                unset($_SESSION['TOKEN']);
                header('Location:list.php');
            }
        } else {
            //Display a user friendly Error on the page using any of the following error information returned by PayPal
            $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
            $ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
            $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
            $ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
            $_SESSION['bc_message'] = 'ErrorContactPlatformAdmin';
            unset($_SESSION['bc_course_code']);
            unset($_SESSION['bc_course_codetext']);
            unset($_SESSION['bc_course_title']);
            unset($_SESSION["Payment_Amount"]);
            unset($_SESSION["currencyCodeType"]);
            unset($_SESSION["PaymentType"]);
            unset($_SESSION["nvpReqArray"]);
            unset($_SESSION['TOKEN']);
            header('Location:list.php');
        }
    }
}
