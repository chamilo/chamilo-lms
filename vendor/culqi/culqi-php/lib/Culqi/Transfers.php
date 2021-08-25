<?php

namespace Culqi;

/**
 * Class Transfers
 *
 * @package Culqi
 */
class Transfers extends Resource {

    const URL_TRANSFERS = "/transfers/";

    /**
     * @param array|null $options
     *
     * @return all Transfers.
     */
    public function all($options = NULL) {
        return $this->request("GET", self::URL_TRANSFERS, $api_key = $this->culqi->api_key, $options);
    }

    /**
     * @param string|null $id
     *
     * @return get a Transfers.
     */
    public function get($id = NULL) {
        return $this->request("GET", self::URL_TRANSFERS . $id . "/", $api_key = $this->culqi->api_key);
    }

}
