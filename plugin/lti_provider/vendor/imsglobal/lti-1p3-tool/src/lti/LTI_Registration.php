<?php
namespace IMSGlobal\LTI;

class LTI_Registration {

    private $issuer;
    private $client_id;
    private $key_set_url;
    private $auth_token_url;
    private $auth_login_url;
    private $auth_server;
    private $tool_private_key;
    private $kid;

    public static function new() {
        return new LTI_Registration();
    }

    public function get_issuer() {
        return $this->issuer;
    }

    public function set_issuer($issuer) {
        $this->issuer = $issuer;
        return $this;
    }

    public function get_client_id() {
        return $this->client_id;
    }

    public function set_client_id($client_id) {
        $this->client_id = $client_id;
        return $this;
    }

    public function get_key_set_url() {
        return $this->key_set_url;
    }

    public function set_key_set_url($key_set_url) {
        $this->key_set_url = $key_set_url;
        return $this;
    }

    public function get_auth_token_url() {
        return $this->auth_token_url;
    }

    public function set_auth_token_url($auth_token_url) {
        $this->auth_token_url = $auth_token_url;
        return $this;
    }

    public function get_auth_login_url() {
        return $this->auth_login_url;
    }

    public function set_auth_login_url($auth_login_url) {
        $this->auth_login_url = $auth_login_url;
        return $this;
    }

    public function get_auth_server() {
        return empty($this->auth_server) ? $this->auth_token_url : $this->auth_server;
    }

    public function set_auth_server($auth_server) {
        $this->auth_server = $auth_server;
        return $this;
    }

    public function get_tool_private_key() {
        return $this->tool_private_key;
    }

    public function set_tool_private_key($tool_private_key) {
        $this->tool_private_key = $tool_private_key;
        return $this;
    }

    public function get_kid() {
        return empty($this->kid) ? hash('sha256', trim($this->issuer . $this->client_id)) : $this->kid;
    }

    public function set_kid($kid) {
        $this->kid = $kid;
        return $this;
    }

}

?>