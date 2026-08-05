<?php
/**
 * This file is part of php-pens.
 *
 * php-pens is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * php-pens is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with php-pens.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * PENSServer.
 *
 * Provides the PENSServer class
 *
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */

use Chamilo\CoreBundle\Helpers\SafeHttpClientHelper;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

require_once __DIR__.'/pens_controller.php';
require_once __DIR__.'/pens_package_handler.php';
require_once __DIR__.'/pens_exception.php';
require_once __DIR__.'/pens_response.php';

/**
 * PENSServer.
 *
 * Class that implements the PENS Server
 *
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
class PENSServer extends PENSController
{
    /**
     * Instance of the PENSServer.
     *
     * @var PENSServer
     */
    private static $_instance;

    /**
     * Package handler.
     *
     * @var PENSPackageHandler
     */
    protected $_package_handler = null;

    /**
     * Private constructor.
     */
    private function __construct()
    {
    }

    /**
     * Singleton method.
     */
    public static function singleton()
    {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c();
        }

        return self::$_instance;
    }

    /**
     * Prevent users to clone the instance.
     */
    public function __clone()
    {
        trigger_error('Clone is not allowed', E_USER_ERROR);
    }

    public function getPackageHandler()
    {
        return $this->_package_handler;
    }

    /**
     * Sets the package handler. Does nothing if the argument is not an instance of PENSPackageHandler.
     *
     * @param PENSPackageHandler $package_handler Package handler
     */
    public function setPackageHandler($package_handler)
    {
        if ($package_handler instanceof PENSPackageHandler) {
            $this->_package_handler = $package_handler;
        }
    }

    /**
     * Receives a collect request and treats it.
     */
    public function receiveCollect()
    {
        $request = null;

        try {
            // First, try to parse the request
            $request = $this->parseRequest();
            if ('collect' == $request->getCommand()) {
                if (isset($_REQUEST['process'])) {
                    // Collect the package and process it
                    $receipt = null;
                    $path_to_package = null;

                    try {
                        // Collect the package
                        $path_to_package = $this->collectPackage($request);
                        $receipt = new PENSResponse(0, 'package successfully collected');
                    } catch (PENSException $e) {
                        $receipt = new PENSResponse($e);
                    }
                    // Send receipt
                    $response = $this->sendReceipt($request, $receipt);
                    if (null !== $response && null !== $path_to_package) {
                        if (0 === $response->getError()) {
                            // Process package
                            $this->processPackage($request, $path_to_package);
                        }

                        if (is_string($path_to_package) && is_file($path_to_package)) {
                            unlink($path_to_package);
                        }
                    }
                } else {
                    // Then, send a success response to the client
                    $this->sendResponse(new PENSResponse(0, 'collect command received and understood'));
                    // Send a request to process the package: fake multithreading
                    $params = $_REQUEST;
                    $params['process'] = 1;
                    $scheme = 'http';
                    if (!empty($_SERVER['HTTPS'])) {
                        $scheme = 'https';
                    }
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $scheme.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
                    curl_exec($ch);
                    curl_close($ch);
                }
            }
        } catch (PENSException $e) {
            // If we could not parse the request, send the error back to the client
            $this->sendResponse(new PENSResponse($e));
        }
    }

    /**
     * Collects the package onto the local server.
     *
     * @param  PENSRequest  $request
     *
     * @return string Path to the package on the hard drive
     *
     * @throws PENSException if an exception occurred
     */
    protected function collectPackage(PENSRequest $request): string
    {
        $supported_package_types = $this->_package_handler->getSupportedPackageTypes();
        if (!in_array($request->getPackageType(), $supported_package_types)) {
            throw new PENSException(1430);
        }
        $supported_package_formats = $this->_package_handler->getSupportedPackageFormats();
        if (!in_array($request->getPackageFormat(), $supported_package_formats)) {
            throw new PENSException(1431);
        }
        if (!$this->isExpiryDateValid($request->getPackageUrlExpiry())) {
            throw new PENSException(1322);
        }

        // Try to download the package in the temporary directory
        if (function_exists('sys_get_temp_dir')) {
            $tmp = sys_get_temp_dir();
        } else {
            $tmp = '/tmp';
        }
        $path_to_file = $tmp.'/'.$request->getFilename();
        // SSRF-safe download: blocks targets resolving to loopback/private/
        // reserved ranges (incl. the cloud metadata endpoint), validates each
        // redirect hop and speaks only http(s). Any transport/HTTP failure is
        // reported as the PENS "could not retrieve package" error (1310).
        $options = [];

        if (null !== $request->getPackageUrlUserId()) {
            $options['auth_basic'] = [
                (string) $request->getPackageUrlUserId(),
                (string) $request->getPackageUrlPassword(),
            ];
        }

        $fp = fopen($path_to_file, 'w');

        try {
            $client = SafeHttpClientHelper::create();
            $response = $client->request('GET', (string) $request->getPackageUrl(), $options);

            foreach ($client->stream($response) as $chunk) {
                fwrite($fp, $chunk->getContent());
            }

            fclose($fp);
        } catch (ExceptionInterface $e) {
            if (is_resource($fp)) {
                fclose($fp);
            }

            throw new PENSException(1310);
        }

        return $path_to_file;
    }

    /**
     * Verifies that the package url is not expired.
     *
     * @param DateTime $expiry DateTime object to verify against current time
     */
    protected function isExpiryDateValid($expiry)
    {
        date_default_timezone_set('UTC');
        $current_time = time();
        $expiry_time = strtotime($expiry->format(DateTime::ISO8601));
        if ($current_time > $expiry_time) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Sends an alert or a receipt. Called by sendReceipt and sendAlert.
     *
     * @param PENSRequest  $request  Original collect request
     * @param PENSResponse $response Reponse to send in the receipt or the alert
     * @param string       $mode     Mode (alert | receipt)
     *
     * @return PENSResponse Response
     */
    protected function sendAlertOrReceipt($request, $response, $mode)
    {
        if ('alert' == $mode) {
            $url = $request->getAlerts();
        } else {
            $url = $request->getReceipt();
        }
        if (!empty($url)) {
            $url_components = parse_url($url);
            $scheme = $url_components['scheme'];
            if ('mailto' == $scheme) {
                $to = $url_components['path'];
                if ('alert' == $mode) {
                    $subject = 'PENS Alert for '.$request->getPackageId();
                } else {
                    $subject = 'PENS Receipt for '.$request->getPackageId();
                }
                $message = $response->__toString();
                mail($to, $subject, $message);

                return new PENSResponse(0, '');
            } elseif ('http' == $scheme || 'https' == $scheme) {
                if ('alert' == $mode) {
                    $params = array_merge($request->getSendAlertArray(), $response->getArray());
                } else {
                    $params = array_merge($request->getSendReceiptArray(), $response->getArray());
                }
                // SSRF-safe POST: blocks targets resolving to loopback/private/
                // reserved ranges (incl. the cloud metadata endpoint) and
                // validates each redirect hop.
                try {
                    $data = SafeHttpClientHelper::create()
                        ->request('POST', $url, ['body' => $params])
                        ->getContent(false);
                } catch (ExceptionInterface $e) {
                    return null;
                }

                return new PENSResponse($data);
            }
        }
    }

    /**
     * Sends a receipt. Returns a PENSResponse in case of success, null if a problem occurred.
     *
     * @param PENSRequest  $request Original collect request
     * @param PENSResponse $receipt Response to send in the receipt
     *
     * @return PENSResponse Response
     */
    protected function sendReceipt($request, $receipt)
    {
        return $this->sendAlertOrReceipt($request, $receipt, 'receipt');
    }

    /**
     * Processes the package using the handler provided.
     *
     * @param PENSRequest $request         Original collect request
     * @param string      $path_to_package Path to the package on the hard drive
     */
    protected function processPackage($request, $path_to_package)
    {
        return $this->_package_handler->processPackage($request, $path_to_package);
    }

    /**
     * Sends an alert to the client. Returns a PENSResponse in case of success, null if a problem occurred.
     *
     * @param PENSRequest  $request Original collect request
     * @param PENSResponse $alert   Response to send in the alert
     *
     * @return PENSResponse Response
     */
    public function sendAlert($request, $alert)
    {
        return $this->sendAlertOrReceipt($request, $alert, 'alert');
    }
}
