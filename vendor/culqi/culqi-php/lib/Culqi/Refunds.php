<?php

namespace Culqi;

/**
 * Class Plans
 *
 * @package Culqi
 */
class Refunds extends Resource {

    const URL_REFUNDS = "/refunds/";

    /**
     * @param array|null $options
     *
     * @return all Refunds.
     */
    public function all($options = NULL) {
        return $this->request("GET", self::URL_REFUNDS, $api_key = $this->culqi->api_key, $options);
    }

    /**
     * @param array|null $options
     *
     * @return create Refund response.
     */
    public function create($options = NULL) {
        return $this->request("POST", self::URL_REFUNDS, $api_key = $this->culqi->api_key, $options);
    }

    /**
     * @param string|null $id
     *
     * @return get a Refund.
     */
    public function get($id = NULL) {
        return $this->request("GET", self::URL_REFUNDS . $id . "/", $api_key = $this->culqi->api_key);
    }

    /**
     * @param string|null $id
     * @param array|null $options
     *
     * @return update Refund response.
     */
    public function update($id = NULL, $options = NULL) {
        return $this->request("PATCH", self::URL_REFUNDS . $id . "/", $api_key = $this->culqi->api_key, $options);
    }

}
