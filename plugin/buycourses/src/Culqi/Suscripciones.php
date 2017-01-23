<?php
namespace Culqi;


class Suscripciones extends Resource {

    const URL_SUSCRIPCIONES = "/suscripciones/";

    public function create($options = NULL)
    {
        return $this->request("POST", Suscripciones::URL_SUSCRIPCIONES, $api_key = $this->culqi->api_key, $options);
    }
    //
    // public function getList($options = NULL) {
    //     return $this->request("GET", Suscripciones::URL_SUSCRIPCIONES, $api_key = $this->culqi->api_key, $options);
    // }
    // public function get($uid) {
    //     return $this->request("GET", Suscripciones::URL_SUSCRIPCIONES . $uid . "/", $api_key = $this->culqi->api_key);
    // }
    //
    // public function update($uid, $options = NULL) {
    //     return $this->request("PATCH", Suscripciones::URL_SUSCRIPCIONES . $uid . "/", $api_key = $this->culqi->api_key, $options);
    // }
    // public function delete($uid) {
    //     return $this->request("DELETE", Suscripciones::URL_SUSCRIPCIONES . $uid . "/", $api_key = $this->culqi->api_key);
    // }

}
