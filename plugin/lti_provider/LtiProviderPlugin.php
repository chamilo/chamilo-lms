<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\LtiProvider\Platform;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Description of LtiProvider
 *
 * @author Christian Beeznest <christian.fasanando@beeznest.com>
 */
class LtiProviderPlugin extends Plugin
{

    const TABLE_PLATFORM = 'plugin_lti_provider_platform';

    public $isAdminPlugin = true;

    protected function __construct()
    {
        $version = '1.0';
        $author = 'Christian Beeznest';
        $publicKey = $this->getPublicKey();
        $message = Display::return_message($this->get_lang('description'));

        $pkHtml = '<div class="form-group ">
                    <label for="lti_provider_public_key" class="col-sm-2 control-label">'.$this->get_lang('public_key').'</label>
                    <div class="col-sm-8">
                        <pre>'.$publicKey.'</pre>
                        <p class="help-block"></p>
                    </div>
                    <div class="col-sm-2"></div>
                </div>';

        $settings = [
            $message => 'html',
            'name' => 'text',
            'launch_url' => 'text',
            'login_url' => 'text',
            'redirect_url' => 'text',
            $pkHtml => 'html',
            'enabled' => 'boolean'
        ];
        parent::__construct($version, $author, $settings);

    }

    /**
     * Get the class instance
     * @staticvar LtiProviderPlugin $result
     * @return LtiProviderPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Get the plugin directory name
     */
    public function get_name()
    {
        return 'lti_provider';
    }

    /**
     * Install the plugin. Setup the database
     */
    public function install()
    {
        $pluginEntityPath = $this->getEntityPath();

        if (!is_dir($pluginEntityPath)) {
            if (!is_writable(dirname($pluginEntityPath))) {
                $message = get_lang('ErrorCreatingDir').': '.$pluginEntityPath;
                Display::addFlash(Display::return_message($message, 'error'));

                return false;
            }

            mkdir($pluginEntityPath, api_get_permissions_for_new_directories());
        }

        $fs = new Filesystem();
        $fs->mirror(__DIR__.'/Entity/', $pluginEntityPath, null, ['override']);

        $this->createPluginTables();
    }

    /**
     * Save configuration for plugin.
     *
     * Generate a new key pair for platform when enabling plugin.
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return $this|Plugin
     */
    public function performActionsAfterConfigure()
    {
        return $this;
    }

    /**
     * Unistall plugin. Clear the database
     */
    public function uninstall()
    {
        $pluginEntityPath = $this->getEntityPath();
        $fs = new Filesystem();

        if ($fs->exists($pluginEntityPath)) {
            $fs->remove($pluginEntityPath);
        }

        try {
            $this->dropPluginTables();
        } catch (DBALException $e) {
            error_log('Error while uninstalling IMS/LTI plugin: '.$e->getMessage());
        }
    }

    /**
     * Creates the plugin tables on database
     *
     * @return boolean
     * @throws DBALException
     */
    private function createPluginTables()
    {
        $entityManager = Database::getManager();
        $connection = $entityManager->getConnection();

        if ($connection->getSchemaManager()->tablesExist(self::TABLE_PLATFORM)) {
            return true;
        }

        $queries = [
            "CREATE TABLE plugin_lti_provider_platform (
                id int NOT NULL AUTO_INCREMENT,
                issuer varchar(255) NOT NULL,
                client_id varchar(255) NOT NULL,
                kid varchar(255) NOT NULL,
                auth_login_url varchar(255) NOT NULL,
                auth_token_url varchar(255) NOT NULL,
                key_set_url varchar(255) NOT NULL,
                deployment_id varchar(255) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB",
        ];

        foreach ($queries as $query) {
            Database::query($query);
        }

        return true;
    }

    /**
     * Drops the plugin tables on database
     *
     * @return boolean
     */
    private function dropPluginTables()
    {
        Database::query("DROP TABLE IF EXISTS plugin_lti_provider_platform");
        return true;
    }

    /**
     * @return string
     */
    public function getEntityPath()
    {
        return api_get_path(SYS_PATH).'src/Chamilo/PluginBundle/Entity/'.$this->getCamelCaseName();
    }

    public static function isInstructor()
    {
        api_is_allowed_to_edit(false, true);
    }

    /**
     * @return null|SimpleXMLElement
     */
    private function getRequestXmlElement()
    {
        $request = file_get_contents("php://input");

        if (empty($request)) {
            return null;
        }

        return new SimpleXMLElement($request);
    }

    /**
     * @param array $params
     */
    public function trimParams(array &$params)
    {
        foreach ($params as $key => $value) {
            $newValue = preg_replace('/\s+/', ' ', $value);
            $params[$key] = trim($newValue);
        }
    }

    /**
     * Generate a key pair and key id for the platform.
     *
     * Rerturn a associative array like ['kid' => '...', 'private' => '...', 'public' => '...'].
     *
     * @return array
     */
    private static function generatePlatformKeys()
    {
        // Create the private and public key
        $res = openssl_pkey_new(
            [
                'digest_alg' => 'sha256',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]
        );

        // Extract the private key from $res to $privateKey
        $privateKey = '';
        openssl_pkey_export($res, $privateKey);

        // Extract the public key from $res to $publicKey
        $publicKey = openssl_pkey_get_details($res);

        return [
            'kid' => bin2hex(openssl_random_pseudo_bytes(10)),
            'private' => $privateKey,
            'public' => $publicKey["key"],
        ];
    }

    public function getPublicKey() {
        $keyPath = __DIR__.'/keys/public.key';
        $publicKey = file_get_contents($keyPath);
        return $publicKey;
    }

}
