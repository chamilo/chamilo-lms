<?php

class OAuthToken {
  // access tokens and request tokens
  public $key;
  public $secret;

  /**
   * key = the token
   * secret = the token secret
   */
  function __construct($key, $secret) {
    $this->key = $key;
    $this->secret = $secret;
  }

  /**
   * generates the basic string serialization of a token that a server
   * would respond to request_token and access_token calls with
   */
  function to_string() {
    return "oauth_token=" .
           oauthutil::urlencode_rfc3986($this->key) .
           "&oauth_token_secret=" .
           oauthutil::urlencode_rfc3986($this->secret);
  }

  function __tostring() {
    return $this->to_string();
  }
}
