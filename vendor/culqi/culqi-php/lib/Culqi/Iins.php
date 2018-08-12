<?php

namespace Culqi;

/**
 * Class Iins
 *
 * @package Culqi
 */
class Iins extends Resource {

    const URL_IINS = "/iins/";

    /**
     * @param array|null $options
     *
     * @return all Iins.
     */
    public function all($options = NULL) {
        return $this->request("GET", self::URL_IINS, $api_key = $this->culqi->api_key, $options);
    }

    /**
     * @param string|null $id
     *
     * @return get a Iin.
     */
    public function get($id = NULL) {
        return $this->request("GET", self::URL_IINS . $id . "/", $api_key = $this->culqi->api_key);
    }

}
