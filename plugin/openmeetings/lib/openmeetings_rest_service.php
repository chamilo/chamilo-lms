<?php
/*
* Licensed to the Apache Software Foundation (ASF) under one
* or more contributor license agreements.  See the NOTICE file
* distributed with this work for additional information
* regarding copyright ownership.  The ASF licenses this file
* to you under the Apache License, Version 2.0 (the
* "License") +  you may not use this file except in compliance
* with the License.  You may obtain a copy of the License at
*
*   http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing,
* software distributed under the License is distributed on an
* "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
* KIND, either express or implied.  See the License for the
* specific language governing permissions and limitations
* under the License.
*/
/**
 * Created on 03.01.2012 by eugen.schwert@gmail.com.
 *
 * @package chamilo.plugin.openmeetings
 * @requires CURL
 */
/**
 * Class OpenMeetingsRestService.
 */
class OpenMeetingsRestService
{
    public function call($request, $returnAttribute = "return")
    {
        // This will allow you to view errors in the browser
        // Note: set "display_errors" to 0 in production
        // ini_set('display_errors',1);

        // Report all PHP errors (notices, errors, warnings, etc.)
        // error_reporting(E_ALL);

        // URI used for making REST call. Each Web Service uses a unique URL.
        // $request

        // Initialize the session by passing the request as a parameter
        $session = curl_init($request);

        // Set curl options by passing session and flags
        // CURLOPT_HEADER allows us to receive the HTTP header
        curl_setopt($session, CURLOPT_HEADER, true);

        // CURLOPT_RETURNTRANSFER will return the response
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        // Make the request
        $response = curl_exec($session);

        // Close the curl session
        curl_close($session);

        // Confirm that the request was transmitted to the OpenMeetings! Image Search Service
        if (!$response) {
            exit("Request OpenMeetings! OpenMeetings Service failed and no response was returned in ".__CLASS__.'::'.__FUNCTION__.'()');
        }

        // Create an array to store the HTTP response codes
        $status_code = [];

        // Use regular expressions to extract the code from the header
        preg_match('/\d\d\d/', $response, $status_code);
        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $extra = ' (from '.$caller['file'].' at line '.$caller['line'].') ';
        // Check the HTTP Response code and display message if status code is not 200 (OK)
        switch ($status_code[0]) {
            case 200:
                // Success
                break;
            case 503:
                error_log('Your call to OpenMeetings Web Services '.$extra.' failed and returned an HTTP status of 503.
                                 That means: Service unavailable. An internal problem prevented us from returning data to you.');

                return false;
                break;
            case 403:
                error_log('Your call to OpenMeetings Web Services '.$extra.' failed and returned an HTTP status of 403.
                                 That means: Forbidden. You do not have permission to access this resource, or are over your rate limit.');

                return false;
                break;
            case 400:
                // You may want to fall through here and read the specific XML error
                error_log('Your call to OpenMeetings Web Services '.$extra.' failed and returned an HTTP status of 400.
                                 That means:  Bad request. The parameters passed to the service did not match as expected.   
                                 The exact error is returned in the XML response.');

                return false;
                break;
            default:
                error_log('Your call to OpenMeetings Web Services '.$extra.' returned an unexpected HTTP status of: '.$status_code[0]." Request ".$request);

                return false;
        }

        // Get the XML from the response, bypassing the header
        if (!($xml = strstr($response, '<ns'))) {
            $xml = null;
        }

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        if ($returnAttribute == "") {
            //echo "XML".$xml."<br/>";
            return $this->getArray($dom);
        } else {
            $returnNodeList = $dom->getElementsByTagName($returnAttribute);
            $ret = [];
            foreach ($returnNodeList as $returnNode) {
                if ($returnNodeList->length == 1) {
                    return $this->getArray($returnNode);
                } else {
                    $ret[] = $this->getArray($returnNode);
                }
            }

            return $ret;
        }
    }

    public function getArray($node)
    {
        if (is_null($node) || !is_object($node)) {
            return $node;
        }
        $array = false;
        /*
            echo("!!!!!!!! NODE " . XML_TEXT_NODE
                    . " :: name = " . $node->nodeName
                    . " :: local = " . $node->localName
                    . " :: childs ? " . $node->hasChildNodes()
                    . " :: count = " . ($node->hasChildNodes() ? $node->childNodes->length : -1)
                    . " :: type = " . $node->nodeType
                    . " :: val = " . $node->nodeValue
                    . "\n");
        /*
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attr) {
                $array[$attr->nodeName] = $attr->nodeValue;
            }
        }
        */
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $childNode) {
                if ($childNode->nodeType != XML_TEXT_NODE) {
                    if ($node->hasAttributes()) {
                        foreach ($node->attributes as $attr) {
                            if ($attr->localName == "nil") {
                                return null;
                            }
                        }
                    }
                    if ($childNode->childNodes->length == 1) {
                        $array[$childNode->localName] = $this->getArray($childNode);
                    } else {
                        $array[$childNode->localName][] = $this->getArray($childNode);
                    }
                } else {
                    return $childNode->nodeValue;
                    //echo("!!!!!!!! TEXT " . $childNode->nodeValue . "\n");
                    //$array[$childNode->localName]
                }
            }
        }

        return $array;
    }

    public function getError()
    {
        return false;
    }

    public function fault()
    {
        return false;
    }
}
