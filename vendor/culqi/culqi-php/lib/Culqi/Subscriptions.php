<?php

namespace Culqi;

/**
 * Class Subscriptions
 *
 * @package Culqi
 */
class Subscriptions extends Resource {

    const URL_SUBSCRIPTIONS = "/subscriptions/";

    /**
     * @param array|null $options
     *
     * @return all Subscriptions.
     */
    public function all($options = NULL) {
        return $this->request("GET", self::URL_SUBSCRIPTIONS, $api_key = $this->culqi->api_key, $options);
    }

    /**
     * @param array|null $options
     *
     * @return create Subscription response.
     */
    public function create($options = NULL) {
        return $this->request("POST", self::URL_SUBSCRIPTIONS, $api_key = $this->culqi->api_key, $options);
    }

    /**
     * @param string|null $id
     *
     * @return delete a Subscription response.
     */
    public function delete($id = NULL) {
       return $this->request("DELETE", self::URL_SUBSCRIPTIONS . $id . "/", $api_key = $this->culqi->api_key);
    }

    /**
     * @param string|null $id
     *
     * @return get a Subscription.
     */
    public function get($id = NULL) {
        return $this->request("GET", self::URL_SUBSCRIPTIONS . $id . "/", $api_key = $this->culqi->api_key);
    }

    /**
     * @param string|null $id
     * @param array|null $options
     *
     * @return update Subscription response.
     */
    public function update($id = NULL, $options = NULL) {
        return $this->request("PATCH", self::URL_SUBSCRIPTIONS . $id . "/", $api_key = $this->culqi->api_key, $options);
    }

}
