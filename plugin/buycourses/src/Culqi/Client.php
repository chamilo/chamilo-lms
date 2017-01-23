<?php
namespace Culqi;

use Culqi\Error as Errors;


class Client
{
    /**
    * La versión de API usada
    */
    const API_VERSION = "v1.2";

    /**
     * La URL Base por defecto
     */
    const BASE_URL = "https://integ-pago.culqi.com/api/v1";


    public function request($method, $url, $api_key, $data = NULL, $headers= array("Content-Type" => "application/json", "Accept" => "application/json") ) {
        try {
            $options = array(
                'auth' => new AuthBearer($api_key),
                'timeout' => 120
            );
            if($method == "GET") {
                $url_params = is_array($data) ? '?' . http_build_query($data) : '';
                $response = \Requests::get(Culqi::$api_base . $url . $url_params, $headers, $options);
            } else if($method == "POST") {
                $response = \Requests::post(Culqi::$api_base . $url, $headers, json_encode($data), $options);


            } else if($method == "PATCH") {
                $response = \Requests::patch(Culqi::$api_base . $url, $headers, json_encode($data), $options);
            } else if($method == "DELETE") {
                $response = \Requests::delete(Culqi::$api_base, $options);
            }
        } catch (\Exception $e) {
            throw new Errors\UnableToConnect();
        }
        if ($response->status_code >= 200 && $response->status_code <= 206) {
            if ($method == "DELETE") {
                return $response->status_code == 204 || $response->status_code == 200;
            }
            return json_decode($response->body);
        }
        if ($response->status_code == 400) {
            $code = 0;
            $message = "";

            throw new Errors\UnhandledError($response->body, $response->status_code);            
        }
        if ($response->status_code == 401) {
            throw new Errors\AuthenticationError();
        }
        if ($response->status_code == 404) {
            throw new Errors\NotFound();
        }
        if ($response->status_code == 403) {
            throw new Errors\InvalidApiKey();
        }
        if ($response->status_code == 405) {
            throw new Errors\MethodNotAllowed();
        }
        throw new Errors\UnhandledError($response->body, $response->status_code);
    }
}
