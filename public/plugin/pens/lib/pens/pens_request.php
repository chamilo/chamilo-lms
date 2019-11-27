<?php
/**
 * This file is part of php-pens.
 * 
 * php-pens is free software: you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * php-pens is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with php-pens.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * PENSRequest
 * 
 * Provides the PENSRequest abstract class
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */

require_once __DIR__.'/pens_config.php';
require_once __DIR__.'/pens_message.php';
require_once __DIR__.'/pens_exception.php';
require_once __DIR__.'/lib/rfc2396regexes.php';

/**
 * PENSRequest
 * 
 * Base class for all the request types (collect, receipt, alert)
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */

abstract class PENSRequest extends PENSMessage {

	/**
	 * PENS Version to be used. Currently, the only valid value is 1.0.0. Required.
	 * @var string
	 */
	protected $_pens_version = null;
	
	/**
	 * Command being used. The only valid values are collect, alert and receipt.Required
	 * @var string
	 */
	protected $_command = null;
	
	/**
	 * Package type being used. The only valid values are aicc-pkg, scorm-pif, ims-qti. Required
	 * @var string
	 */
	protected $_package_type = null;
	
	/**
	 * Package type version. Required
	 * @var string
	 */
	protected $_package_type_version = null;
	
	/**
	 * Package format. The only valid values are zip, url, jar, war and xml. Required
	 * @var string
	 */
	protected $_package_format = null;
	
	/**
	 * Package id. Requires a valid URI according to RFC 2396. Required
	 * @var string
	 */
	protected $_package_id = null;
	
	/**
	 * Package url. Requires a valid, fully qualified URL including transport protocol and filename extension. Required
	 * @var string
	 */
	protected $_package_url = null;
	
	/**
	 * User id required for system to retrieve package from URL. Optional.
	 * @var string
	 */
	protected $_package_url_user_id = null;
	
	/**
	 * Account required for system to retrieve package from URL. Optional.
	 * @var string
	 */
	protected $_package_url_account = null;
	
	/**
	 * Password required for system to retrieve package from URL. Optional.
	 * @var string
	 */
	protected $_package_url_password = null;
	
	/**
	 * Expiry date for package URL. ISO 8601 format expressed as UTC. Will be transformed into a PHP DateTime object during construction. Required
	 * @var DateTime
	 */
	protected $_package_url_expiry = null;
	
	/**
	 * Name or ID for client submitting the content package to the target system. Required.
	 * @var string
	 */
	protected $_client = null;
	
	/**
	 * User-id or sign-on for target system. Optional
	 * @var string
	 */
	protected $_system_user_id = null;
	
	/**
	 * Either a URL-encoded password token or the null string. If the 
	 * target system requires a password and the null string value is 
	 * passed, then the target system is responsible for prompting for a 
	 * password for target system. Optional
	 * @var string
	 */
	protected $_system_password = null;
	
	/**
	 * URL to send acknowledgement receipt after collecting a package. Any URL, including mailto (as per RFC 2368 and RFC 2822). Required.
	 * @var string
	 */
	protected $_receipt = null;
	
	/**
	 * URL to send alerts to while processing the package. Any URL, including mailto (as per RFC 2368 and RFC 2822). Optional.
	 * @var string
	 */
	protected $_alerts = null;
	
	/**
	 * Unstructured character string that may be used to transfer vendor-specific data such as processing hints or deployment information. Optional.
	 * @var string
	 */
	protected $_vendor_data = null;
	
	/**
	 * Constructor
	 * 
	 * Constructs a PENSRequest based class using the arguments given
	 * 
	 * @param array Arguments
	 */
	public function __construct($arguments) {
		$this->setPensVersion($arguments["pens-version"]);
		$this->setPackageType($arguments["package-type"]);
		$this->setPackageTypeVersion($arguments["package-type-version"]);
		$this->setPackageFormat($arguments["package-format"]);
		$this->setPackageId($arguments["package-id"]);
		$this->setPackageUrl($arguments["package-url"]);
		$this->setPackageUrlUserId($arguments["package-url-user-id"]);
		$this->setPackageUrlAccount($arguments["package-url-account"]);
		$this->setPackageUrlPassword($arguments["package-url-password"]);
		$this->setPackageUrlExpiry($arguments["package-url-expiry"]);
		$this->setClient($arguments["client"]);
		$this->setSystemUserId($arguments["system-user-id"]);
		$this->setSystemPassword($arguments["system-password"]);
		$this->setReceipt($arguments["receipt"]);
		$this->setAlerts($arguments["alerts"]);
		$this->setVendorData($arguments["vendor-data"]);
	}
	
	public function getPensVersion() {
		return $this->_pens_version;
	}
	
	/**
	 * Sets the PENS version
	 * 
	 * @param string PENS version
	 * 
	 * @throws PENSException with code 2001 if invalid
	 */
	public function setPensVersion($pens_version) {
		if($pens_version == PENSConfig::$version) {
			$this->_pens_version = $pens_version;
		} else {
			throw new PENSException(2001);
		}
	}
	
	public function getCommand() {
		return $this->_command;
	}
	
	/**
	 * Sets the command
	 * 
	 * @param string command
	 * 
	 * @throws PENSException with code 2002 if invalid
	 */
	protected function setCommand($command) {
		if(in_array($command, PENSConfig::$allowed_commands)) {
			$this->_command = $command;
		} else {
			throw new PENSException(2002);
		}
	}
	
	public function getPackageType() {
		return $this->_package_type;
	}
	
	/**
	 * Sets the package type
	 * 
	 * @param string package type
	 * 
	 * @throws PENSException with code 2003 if invalid
	 */
	public function setPackageType($package_type) {
		if(in_array($package_type, PENSConfig::$allowed_package_types)) {
			$this->_package_type = $package_type;
		} else {
			throw new PENSException(2003);
		}
	}
	
	public function getPackageTypeVersion() {
		return $this->_package_type_version;
	}
	
	/**
	 * Sets the package type version
	 * 
	 * @param string package type version
	 * 
	 * @throws PENSException with code 2004 if invalid
	 */
	public function setPackageTypeVersion($package_type_version) {
		if(empty($package_type_version)) {
			throw new PENSException(2004);
		} else {
			$this->_package_type_version = $package_type_version;
		}
	}
	
	public function getPackageFormat() {
		return $this->_package_format;
	}
	
	/**
	 * Sets the package format
	 * 
	 * @param string package format
	 * 
	 * @throws PENSException with code 2005 if invalid
	 */
	public function setPackageFormat($package_format) {
		if(in_array($package_format, PENSConfig::$allowed_package_formats)) {
			$this->_package_format = $package_format;
		} else {
			throw new PENSException(2005);
		}
	}
	
	public function getPackageId() {
		return $this->_package_id;
	}
	
	/**
	 * Sets the package Id
	 * 
	 * @param string package Id
	 * 
	 * @throws PENSException with code 2007 if invalid
	 */
	public function setPackageId($package_id) {
		if (preg_match('/'.ABSOLUTEURI_2396.'/', $package_id)) {
			$this->_package_id = $package_id;
		} else {
			throw new PENSException(2007);
		}
	}
	
	public function getPackageUrl() {
		return $this->_package_url;
	}
	
	/**
	 * Sets the package url
	 * 
	 * @param string package url
	 * 
	 * @throws PENSException with code 2008 if invalid
	 */
	public function setPackageUrl($package_url) {
		if (preg_match('/'.ABSOLUTEURI_2396.'/', $package_url) && substr($package_url, -4) == ".".$this->_package_format) {
			$this->_package_url = $package_url;
		} else {
			throw new PENSException(2008);
		}
	}
	
	public function getFilename() {
		return substr(strrchr($this->_package_url, "/"), 1);
	}
	
	public function getPackageUrlUserId() {
		return $this->_package_url_user_id;
	}
	
	public function setPackageUrlUserId($package_url_user_id) {
		if(!empty($package_url_user_id)) {
			$this->_package_url_user_id = $package_url_user_id;
		}
	}
	
	public function getPackageUrlAccount() {
		return $this->_package_url_account;
	}
	
	public function setPackageUrlAccount($package_url_account) {
		if(!empty($package_url_account)) {
			$this->_package_url_account = $package_url_account;
		}
	}
	
	public function getPackageUrlPassword() {
		return $this->_package_url_password;
	}
	
	public function setPackageUrlPassword($package_url_password) {
		if(!empty($package_url_password)) {
			$this->_package_url_password = $package_url_password;
		}
	}
	
	public function getPackageUrlExpiry() {
		return $this->_package_url_expiry;
	}
	
	/**
	 * Sets the package url expiry and transforms it into a php DateTime object
	 * 
	 * @param string package url expiry
	 * 
	 * @throws PENSException with code 2009 if invalid
	 * @todo Perform a better validation of the date
	 */
	public function setPackageUrlExpiry($package_url_expiry) {
		if(empty($package_url_expiry)) {
			throw new PENSException(2009);
		} else {
			try {
				$expiry = new DateTime($package_url_expiry, new DateTimeZone('UTC'));
				$this->_package_url_expiry = $expiry;
			} catch(Exception $e) {
				throw new PENSException(2009);
			}
		}
	}
	
	public function getClient() {
		return $this->_client;
	}
	
	/**
	 * Sets the client
	 * 
	 * @param string client
	 * 
	 * @throws PENSException with code 2010 if invalid
	 */
	public function setClient($client) {
		if(!empty($client)) {
			$this->_client = $client;
		} else {
			throw new PENSException(2010);
		}
	}
	
	public function getSystemUserId() {
		return $this->_system_user_id;
	}
	
	public function setSystemUserId($system_user_id) {
		if(!empty($system_user_id)) {
			$this->_system_user_id = $system_user_id;
		}
	}
	
	public function getSystemPassword() {
		return $this->_system_password;
	}
	
	public function setSystemPassword($system_password) {
		if(!empty($system_password)) {
			$this->_system_password = $system_password;
		}
	}
	
	public function getReceipt() {
		return $this->_receipt;
	}
	
	/**
	 * Sets the receipt url
	 * 
	 * @param string receipt url
	 * 
	 * @throws PENSException with code 2011 if invalid
	 */
	public function setReceipt($receipt) {
		if($this instanceof PENSRequestCollect) {
			if (preg_match('/'.ABSOLUTEURI_2396.'/', $receipt)) {
				$this->_receipt = $receipt;
			} else {
				throw new PENSException(2011);
			}
		}
	}
	
	public function getAlerts() {
		return $this->_alerts;
	}
	
	public function setAlerts($alerts) {
		if(!empty($alerts)) {
			if(preg_match('/'.ABSOLUTEURI_2396.'/', $alerts)) {
				$this->_alerts = $alerts;
			} else {
				throw new PENSException(1201);
			}
		}
	}
	
	public function getVendorData() {
		return $this->_vendor_data;
	}
	
	public function setVendorData($vendor_data) {
		if(!empty($vendor_data)) {
			$this->_vendor_data = $vendor_data;
		}
	}
	
	/**
	 * Returns an associative that contains all the fields needed to send a
	 * receipt or an alert to the client
	 * 
	 * @return array Associative array
	 */
	protected function getSendReceiptAlertArray() {
		return array("pens-version" => $this->getPensVersion(),
			"package-type" => $this->getPackageType(),
			"package-type-version" => $this->getPackageTypeVersion(),
			"package-format" => $this->getPackageFormat(),
			"package-id" => $this->getPackageId(),
			"package-url" => $this->getPackageUrl(),
			"package-url-expiry" => $this->getPackageUrlExpiry()->format(DateTime::ISO8601),
			"client" => $this->getClient());
	}
	
	/**
	 * Returns an associative that contains all the fields needed to send a
	 * receipt to the client
	 * 
	 * @return array Associative array
	 */
	public function getSendReceiptArray() {
		$receipt = $this->getSendReceiptAlertArray();
		$receipt["command"] = "receipt";
		return $receipt;
	}
	
	/**
	 * Returns an associative that contains all the fields needed to send an
	 * alert to the client
	 * 
	 * @return array Associative array
	 */
	public function getSendAlertArray() {
		$alert = $this->getSendReceiptAlertArray();
		$alert["command"] = "alert";
		return $alert;
	}
	
	
}
