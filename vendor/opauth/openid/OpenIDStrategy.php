<?php
/**
 * OpenID strategy for Opauth
 * 
 * Implemented with Mewp's LightOpenID Library,
 *   included at Vendor/lightopenid
 *   (https://gitorious.org/lightopenid/lightopenid)
 * 
 * More information on Opauth: http://opauth.org
 * 
 * @copyright    Copyright © 2012 U-Zyn Chua (http://uzyn.com)
 * @link         http://opauth.org
 * @package      Opauth.OpenIDStrategy
 * @license      MIT License
 */

/**
 * OpenID strategy for Opauth
 * 
 * @package			Opauth.OpenIDStrategy
 */
class OpenIDStrategy extends OpauthStrategy{
	
	/**
	 * Compulsory config keys, listed as unassociative arrays
	 */
	public $expects = array();
	
	/**
	 * Optional config keys, without predefining any default values.
	 */
	public $optionals = array();
	
	/**
	 * Optional config keys with respective default values, listed as associative arrays
	 * eg. array('scope' => 'email');
	 */
	public $defaults = array(
		// Refer to http://openid.net/specs/openid-attribute-properties-list-1_0-01.html if
		// you wish to overwrite these
		'required' => array(
			'contact/email',
			'namePerson',
			'namePerson/first',
			'namePerson/last',
			'namePerson/friendly'
		),
		'optional' => array(
			'contact/phone',
			'contact/web',
			'media/image'
		),
		'identifier_form' => 'identifier_request.html'
	);
	
	public function __construct($strategy, $env){
		parent::__construct($strategy, $env);
		
		$parsed = parse_url($this->env['host']);
		require dirname(__FILE__).'/Vendor/lightopenid/openid.php';
		$this->openid = new LightOpenID($parsed['host']);
		$this->openid->required = $this->strategy['required'];
		$this->openid->optional = $this->strategy['optional'];
	}
	
	/**
	 * Ask for OpenID identifer
	 */
	public function request(){
		if (!$this->openid->mode){
			if (empty($_POST['openid_url'])){
				$this->render($this->strategy['identifier_form']);
			}
			else{
				$this->openid->identity = $_POST['openid_url'];
				try{
					$this->redirect($this->openid->authUrl());
				} catch (Exception $e){
					$error = array(
						'provider' => 'OpenID',
						'code' => 'bad_identifier',
						'message' => $e->getMessage()
					);

					$this->errorCallback($error);
				}
			}
		}
		elseif ($this->openid->mode == 'cancel'){
			$error = array(
				'provider' => 'OpenID',
				'code' => 'cancel_authentication',
				'message' => 'User has canceled authentication'
			);

			$this->errorCallback($error);
	    }
		elseif (!$this->openid->validate()){
			$error = array(
				'provider' => 'OpenID',
				'code' => 'not_logged_in',
				'message' => 'User has not logged in'
			);

			$this->errorCallback($error);
		}
		else{
			$attributes = $this->openid->getAttributes();
			$this->auth = array(
				'provider' => 'OpenID',
				'uid' => $this->openid->identity,
				'info' => array(),
				'credentials' => array(),
				'raw' => $this->openid->getAttributes()
			);
			
			if (!empty($attributes['contact/email'])) $this->auth['info']['email'] = $attributes['contact/email'];
			if (!empty($attributes['namePerson'])) $this->auth['info']['name'] = $attributes['namePerson'];
			if (!empty($attributes['fullname'])) $this->auth['info']['name'] = $attributes['fullname'];
			if (!empty($attributes['namePerson/first'])) $this->auth['info']['first_name'] = $attributes['namePerson/first'];
			if (!empty($attributes['namePerson/last'])) $this->auth['info']['last_name'] = $attributes['namePerson/last'];
			if (!empty($attributes['namePerson/friendly'])) $this->auth['info']['nickname'] = $attributes['namePerson/friendly'];
			if (!empty($attributes['contact/phone'])) $this->auth['info']['phone'] = $attributes['contact/phone'];
			if (!empty($attributes['contact/web'])) $this->auth['info']['urls']['website'] = $attributes['contact/web'];
			if (!empty($attributes['media/image'])) $this->auth['info']['image'] = $attributes['media/image'];
			
			$this->callback();
		}
	}
	
	/**
	 * Render a view
	 */
	protected function render($view, $exit = true){
		require($view);
		if ($exit) exit();
	}
}