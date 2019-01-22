<?php

/**
 * A very simple class that you can pass a base-string, and then have it returned again.
 * Used for testing the signature-methods
 */
class Mock_OAuthBaseStringRequest {
	private $provided_base_string;
	public $base_string; // legacy
	public function __construct($bs) { $this->provided_base_string = $bs; }
	public function get_signature_base_string() { return $this->provided_base_string; }
}
