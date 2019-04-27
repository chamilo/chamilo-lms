<?php

namespace Culqi;

/**
 * Class Cards
 *
 * @package Culqi
 */
class Cards extends Resource {

    const URL_CARDS = "/cards/";

    /**
    * @param array|null $options
    *
    * @return all Cards.
    */
    public function all($options = NULL) {
        return $this->request("GET", self::URL_CARDS, $api_key = $this->culqi->api_key, $options);
    }

    /**
    * @param array|null $options
    *
    * @return create Card response.
    */
    public function create($options = NULL) {
        return $this->request("POST", self::URL_CARDS, $api_key = $this->culqi->api_key, $options);
    }

    /**
    * @param string|null $id
    *
    * @return delete a Card response.
    */
    public function delete($id = NULL) {
        return $this->request("DELETE", self::URL_CARDS . $id . "/", $api_key = $this->culqi->api_key);
    }

    /**
    * @param string|null $id
    *
    * @return get a Card.
    */
    public function get($id = NULL) {
        return $this->request("GET", self::URL_CARDS . $id . "/", $api_key = $this->culqi->api_key);
    }

    /**
    * @param string|null $id
    * @param array|null $options
    *
    * @return update Card response.
    */
    public function update($id = NULL, $options = NULL) {
        return $this->request("PATCH", self::URL_CARDS . $id . "/", $api_key = $this->culqi->api_key, $options);
    }

}
