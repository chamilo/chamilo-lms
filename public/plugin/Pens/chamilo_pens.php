<?php

/* For licensing terms, see /license.txt. */

/**
 * Stores received PENS package metadata in the database.
 */

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/lib/pens.php';

class ChamiloPens extends Plugin
{
    public const TABLE_NAME = 'plugin_pens';

    protected $_id = null;
    protected $_pens_version = null;
    protected $_package_type = null;
    protected $_package_type_version = null;
    protected $_package_format = null;
    protected $_package_id = null;
    protected $_client = null;
    protected $_vendor_data = null;
    protected $_package_name = null;
    protected $_created_at = null;
    protected $_updated_at = null;

    /**
     * @param PENSRequest|array $request
     * @param string|null       $packageName
     */
    public function __construct($request, $packageName = null)
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
            $this->_package_name = $packageName ?: $request->getFilename();

            return;
        }

        if (is_array($request)) {
            $this->_id = isset($request['id']) ? (int) $request['id'] : 0;
            $this->_pens_version = $request['pens_version'] ?? null;
            $this->_package_type = $request['package_type'] ?? null;
            $this->_package_type_version = $request['package_type_version'] ?? null;
            $this->_package_format = $request['package_format'] ?? null;
            $this->_package_id = $request['package_id'] ?? null;
            $this->_client = $request['client'] ?? null;
            $this->_vendor_data = $request['vendor_data'] ?? null;
            $this->_package_name = $request['package_name'] ?? null;

            if (!empty($request['created_at'])) {
                $this->_created_at = new DateTime($request['created_at'], new DateTimeZone('UTC'));
            }

            if (!empty($request['updated_at'])) {
                $this->_updated_at = new DateTime($request['updated_at'], new DateTimeZone('UTC'));
            }
        }
    }

    public function save()
    {
        $table = Database::get_main_table(self::TABLE_NAME);
        $createdAt = api_get_utc_datetime();

        $cleanPensVersion = Database::escape_string((string) $this->_pens_version);
        $cleanPackageType = Database::escape_string((string) $this->_package_type);
        $cleanPackageTypeVersion = Database::escape_string((string) $this->_package_type_version);
        $cleanPackageFormat = Database::escape_string((string) $this->_package_format);
        $cleanPackageId = Database::escape_string((string) $this->_package_id);
        $cleanClient = Database::escape_string((string) $this->_client);
        $cleanVendorData = Database::escape_string((string) $this->_vendor_data);
        $cleanPackageName = Database::escape_string((string) $this->_package_name);

        $sql = "INSERT INTO $table (
                    pens_version,
                    package_type,
                    package_type_version,
                    package_format,
                    package_id,
                    client,
                    vendor_data,
                    package_name,
                    created_at
                ) VALUES (
                    '$cleanPensVersion',
                    '$cleanPackageType',
                    '$cleanPackageTypeVersion',
                    '$cleanPackageFormat',
                    '$cleanPackageId',
                    '$cleanClient',
                    '$cleanVendorData',
                    '$cleanPackageName',
                    '$createdAt'
                )
                ON DUPLICATE KEY UPDATE
                    pens_version = VALUES(pens_version),
                    package_type = VALUES(package_type),
                    package_type_version = VALUES(package_type_version),
                    package_format = VALUES(package_format),
                    client = VALUES(client),
                    vendor_data = VALUES(vendor_data),
                    package_name = VALUES(package_name),
                    updated_at = '$createdAt'";

        Database::query($sql);
    }

    public static function findByPackageId($packageId)
    {
        $table = Database::get_main_table(self::TABLE_NAME);
        $cleanPackageId = Database::escape_string((string) $packageId);

        $sql = "SELECT * FROM $table WHERE package_id = '$cleanPackageId' LIMIT 1";
        $results = Database::query($sql);

        if (1 === Database::num_rows($results)) {
            $row = Database::fetch_assoc($results);

            return new self($row);
        }

        return null;
    }

    public static function findAll()
    {
        $table = Database::get_main_table(self::TABLE_NAME);
        $sql = "SELECT * FROM $table ORDER BY created_at";
        $results = Database::query($sql);

        $items = [];

        while ($row = Database::fetch_assoc($results)) {
            $items[] = new self($row);
        }

        return $items;
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

    public function getUpdatedAt()
    {
        return $this->_updated_at;
    }
}
