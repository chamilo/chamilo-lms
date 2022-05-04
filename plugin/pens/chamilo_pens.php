<?php

/* For licensing terms, see /license.txt */

/**
 * ChamiloPens.
 *
 * This file provides the ChamiloPens class
 *
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/lib/pens.php';

/**
 * ChamiloPens.
 *
 * Model class that stores a PENS request made to a Chamilo server into the database
 *
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
class ChamiloPens extends Plugin
{
    /**
     * Database table to be used.
     */
    public const TABLE_NAME = "plugin_pens";

    /**
     * Id of the object.
     *
     * @var int
     */
    protected $_id = null;

    /**
     * PENS Version to be used. Currently, the only valid value is 1.0.0. Required.
     *
     * @var string
     */
    protected $_pens_version = null;

    /**
     * Package type being used. The only valid values are aicc-pkg, scorm-pif, ims-qti. Required.
     *
     * @var string
     */
    protected $_package_type = null;

    /**
     * Package type version. Required.
     *
     * @var string
     */
    protected $_package_type_version = null;

    /**
     * Package format. The only valid values are zip, url, jar, war and xml. Required.
     *
     * @var string
     */
    protected $_package_format = null;

    /**
     * Package id. Requires a valid URI according to RFC 2396. Required.
     *
     * @var string
     */
    protected $_package_id = null;

    /**
     * Name or ID for client submitting the content package to the target system. Required.
     *
     * @var string
     */
    protected $_client = null;

    /**
     * Unstructured character string that may be used to transfer vendor-specific data such as processing hints or deployment information. Optional.
     *
     * @var string
     */
    protected $_vendor_data = null;

    /**
     * Package name.
     *
     * @var string
     */
    protected $_package_name = null;

    /**
     * Date of creation.
     *
     * @var DateTime
     */
    protected $_created_at = null;

    /**
     * Update date.
     *
     * @var DateTime
     */
    protected $_updated_at = null;

    /**
     * Constructor. Takes a PENSRequest as an argument.
     *
     * @param object $request Request
     */
    public function __construct($request)
    {
        if ($request instanceof PENSRequest) {
            $this->_id = 0;
            $this->_pens_version = $request->getPensVersion();
            $this->_package_type = $request->getPackageType();
            $this->_package_type_version = $request->getPackageTypeVersion();
            $this->_package_format = $request->getPackageFormat();
            $this->_package_id = $request->getPackageId();
            $this->_client = $request->getClient();
            $this->_vendor_data = $request->getVendorData();
            $this->_package_name = $request->getFilename();
        } else {
            if (is_array($request)) {
                $this->_id = $request['id'];
                $this->_pens_version = $request['pens_version'];
                $this->_package_type = $request['package_type'];
                $this->_package_type_version = $request['package_type_version'];
                $this->_package_format = $request['package_format'];
                $this->_package_id = $request['package_id'];
                $this->_client = $request['client'];
                $this->_vendor_data = $request['vendor_data'];
                $this->_package_name = $request['package_name'];
                $this->_created_at = new DateTime($request['created_at'], new DateTimeZone('UTC'));
                if (!empty($request['updated_at'])) {
                    $this->_updated_at = new DateTime($request['updated_at'], new DateTimeZone('UTC'));
                }
            }
        }
    }

    /**
     * Saves the object in the DB.
     */
    public function save()
    {
        $clean_package_type_version = Database::escape_string($this->_package_type_version);
        $clean_package_id = Database::escape_string($this->_package_id);
        $clean_client = Database::escape_string($this->_client);
        $clean_vendor_data = Database::escape_string($this->_vendor_data);
        $created_at = api_get_utc_datetime();
        $table = Database::get_main_table(self::TABLE_NAME);
        $sql_query = "INSERT INTO $table (pens_version, package_type, package_type_version, package_format, package_id, client, vendor_data, package_name, created_at) VALUES (".
            "'".$this->_pens_version."', ".
            "'".$this->_package_type."', ".
            "'".$clean_package_type_version."', ".
            "'".$this->_package_format."', ".
            "'".$clean_package_id."', ".
            "'".$clean_client."', ".
            "'".$clean_vendor_data."', ".
            "'".$this->_package_name."', ".
            "'".$created_at."') ON DUPLICATE KEY UPDATE ".
            "pens_version = VALUES(pens_version), ".
            "package_type = VALUES(package_type), ".
            "package_type_version = VALUES(package_type_version), ".
            "package_format = VALUES(package_format), ".
            "client = VALUES(client), ".
            "vendor_data = VALUES(vendor_data), ".
            "package_name = VALUES(package_name), ".
            "updated_at = '".$created_at."';";
        Database::query($sql_query);
    }

    /**
     * Returns a ChamiloPens object, based on package id.
     *
     * @param string $package_id Package id
     *
     * @return ChamiloPens|null
     */
    public static function findByPackageId($package_id)
    {
        $table = Database::get_main_table(self::TABLE_NAME);
        $sql_query = "SELECT * FROM $table WHERE package_id = '".$package_id."';";
        $results = Database::query($sql_query);
        $number = Database::num_rows($results);
        if ($number == 1) {
            $obj = Database::fetch_assoc($results);

            return new ChamiloPens($obj);
        } else {
            return null;
        }
    }

    /**
     * Returns an array of all the objects of the DB.
     *
     * @return array Array of ChamiloPens objects
     */
    public static function findAll()
    {
        $table = Database::get_main_table(self::TABLE_NAME);
        $sql_query = "SELECT * FROM $table ORDER BY created_at;";
        $results = Database::query($sql_query);
        $return = [];
        while ($assoc = Database::fetch_assoc($results)) {
            $return[] = new ChamiloPens($assoc);
        }

        return $return;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getPensVersion()
    {
        return $this->_pens_version;
    }

    public function getPackageType()
    {
        return $this->_package_type;
    }

    public function getPackageTypeVersion()
    {
        return $this->_package_type_version;
    }

    public function getPackageFormat()
    {
        return $this->_package_format;
    }

    public function getPackageId()
    {
        return $this->_package_id;
    }

    public function getClient()
    {
        return $this->_client;
    }

    public function getVendorData()
    {
        return $this->_vendor_data;
    }

    public function getPackageName()
    {
        return $this->_package_name;
    }

    public function getCreatedAt()
    {
        return $this->_created_at;
    }
}
