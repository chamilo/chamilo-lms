<?php

/*  A very naive dbm-based oauth storage
 *
 *  NOTE: This is for reference ONLY, 
 *  and contains, amongst others, a hole
 *  where you can get the token secret
 *  easily..
 */
class SimpleOAuthDataStore extends OAuthDataStore {/*{{{*/
  private $dbh;

  function __construct($path = "oauth.gdbm") {/*{{{*/
    $this->dbh = dba_popen($path, 'c', 'gdbm');
  }/*}}}*/

  function __destruct() {/*{{{*/
    dba_close($this->dbh);
  }/*}}}*/

  function lookup_consumer($consumer_key) {/*{{{*/
    $rv = dba_fetch("consumer_$consumer_key", $this->dbh);
    if ($rv === FALSE) {
      return NULL;
    }
    $obj = unserialize($rv);
    if (!($obj instanceof OAuthConsumer)) {
      return NULL;
    }
    return $obj;
  }/*}}}*/

  function lookup_token($consumer, $token_type, $token) {/*{{{*/
    $rv = dba_fetch("${token_type}_${token}", $this->dbh);
    if ($rv === FALSE) {
      return NULL;
    }
    $obj = unserialize($rv);
    if (!($obj instanceof OAuthToken)) {
      return NULL;
    }
    return $obj;
  }/*}}}*/

  function lookup_nonce($consumer, $token, $nonce, $timestamp) {/*{{{*/
    if (dba_exists("nonce_$nonce", $this->dbh)) {
      return TRUE;
    } else {
      dba_insert("nonce_$nonce", "1", $this->dbh);
      return FALSE;
    }
  }/*}}}*/

  function new_token($consumer, $type="request") {/*{{{*/
    $key = md5(time());
    $secret = time() + time();
    $token = new OAuthToken($key, md5(md5($secret)));
    if (!dba_insert("${type}_$key", serialize($token), $this->dbh)) {
      throw new OAuthException("doooom!");
    }
    return $token;
  }/*}}}*/

  function new_request_token($consumer) {/*{{{*/
    return $this->new_token($consumer, "request");
  }/*}}}*/

  function new_access_token($token, $consumer) {/*{{{*/

    $token = $this->new_token($consumer, 'access');
    dba_delete("request_" . $token->key, $this->dbh);
    return $token;
  }/*}}}*/
}/*}}}*/