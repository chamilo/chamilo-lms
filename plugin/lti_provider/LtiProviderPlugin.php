<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\LtiProvider\Platform;
use Chamilo\PluginBundle\Entity\LtiProvider\PlatformKey;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Description of LtiProvider.
 *
 * @author Christian Beeznest <christian.fasanando@beeznest.com>
 */
class LtiProviderPlugin extends Plugin
{
    public const TABLE_PLATFORM = 'plugin_lti_provider_platform';

    public $isAdminPlugin = true;

    protected function __construct()
    {
        $version = '1.0';
        $author = 'Christian Beeznest';

        $message = Display::return_message($this->get_lang('Description'));

        if ($this->areTablesCreated()) {
            $publicKey = $this->getPublicKey();

            $pkHtml = '<div class="form-group">
                    <label for="lti_provider_public_key" class="col-sm-2 control-label">'
                .$this->get_lang('PublicKey').'</label>
                    <div class="col-sm-8">
                        <pre>'.$publicKey.'</pre>
                    </div>
                    <div class="col-sm-2"></div>
                </div>';
        } else {
            $pkHtml = $this->get_lang('GenerateKeyPairInfo');
        }

        $settings = [
            $message => 'html',
            'name' => 'text',
            'launch_url' => 'text',
            'login_url' => 'text',
            'redirect_url' => 'text',
            $pkHtml => 'html',
            'enabled' => 'boolean',
        ];
        parent::__construct($version, $author, $settings);
    }

    /**
     * Get a selectbox with quizzes in courses , used for a tool provider.
     *
     * @param null $issuer
     *
     * @return string
     */
    public function getQuizzesSelect($issuer = null)
    {
        $courses = CourseManager::get_courses_list();
        $toolProvider = $this->getToolProvider($issuer);
        $htmlcontent = '<div class="form-group">
            <label for="lti_provider_create_platform_kid" class="col-sm-2 control-label">'.$this->get_lang('ToolProvider').'</label>
            <div class="col-sm-8">
                <select name="tool_provider">';
        $htmlcontent .= '<option value="">-- '.$this->get_lang('SelectOneActivity').' --</option>';
        foreach ($courses as $course) {
            $courseInfo = api_get_course_info($course['code']);
            $optgroupLabel = "{$course['title']} : ".get_lang('Quizzes');
            $htmlcontent .= '<optgroup label="'.$optgroupLabel.'">';
            $exerciseList = ExerciseLib::get_all_exercises_for_course_id(
                $courseInfo,
                0,
                $course['id'],
                false
            );
            foreach ($exerciseList as $key => $exercise) {
                $selectValue = "{$course['code']}@@quiz-{$exercise['iid']}";
                $htmlcontent .= '<option value="'.$selectValue.'" '.($toolProvider == $selectValue ? ' selected="selected"' : '').'>'.Security::remove_XSS($exercise['title']).'</option>';
            }
            $htmlcontent .= '</optgroup>';
        }
        $htmlcontent .= "</select>";
        $htmlcontent .= '   </div>
                    <div class="col-sm-2"></div>
                    </div>';

        return $htmlcontent;
    }

    /**
     * Get the public key.
     */
    public function getPublicKey(): string
    {
        $publicKey = '';
        $platformKey = Database::getManager()
           ->getRepository('ChamiloPluginBundle:LtiProvider\PlatformKey')
           ->findOneBy([]);

        if ($platformKey) {
            $publicKey = $platformKey->getPublicKey();
        }

        return $publicKey;
    }

    /**
     * Get the tool provider.
     */
    public function getToolProvider($issuer): string
    {
        $toolProvider = '';
        $platform = Database::getManager()
            ->getRepository('ChamiloPluginBundle:LtiProvider\Platform')
            ->findOneBy(['issuer' => $issuer]);

        if ($platform) {
            $toolProvider = $platform->getToolProvider();
        }

        return $toolProvider;
    }

    public function getToolProviderVars($issuer): array
    {
        $toolProvider = $this->getToolProvider($issuer);
        list($courseCode, $tool) = explode('@@', $toolProvider);
        list($toolName, $toolId) = explode('-', $tool);
        $vars = ['courseCode' => $courseCode, 'toolName' => $toolName, 'toolId' => $toolId];

        return $vars;
    }

    /**
     * Get the class instance.
     *
     * @staticvar LtiProviderPlugin $result
     */
    public static function create(): LtiProviderPlugin
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Check whether the current user is a teacher in this context.
     */
    public static function isInstructor()
    {
        api_is_allowed_to_edit(false, true);
    }

    /**
     * Get the plugin directory name.
     */
    public function get_name(): string
    {
        return 'lti_provider';
    }

    /**
     * Install the plugin. Set the database up.
     *
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public function install()
    {
        $em = Database::getManager();

        if ($em->getConnection()->getSchemaManager()->tablesExist(['sfu_post'])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(
            [
                $em->getClassMetadata(Platform::class),
                $em->getClassMetadata(PlatformKey::class),
            ]
        );
    }

    /**
     * Save configuration for plugin.
     *
     * Generate a new key pair for platform when enabling plugin.
     *
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     *
     * @return $this|Plugin
     */
    public function performActionsAfterConfigure()
    {
        $em = Database::getManager();

        /** @var PlatformKey $platformKey */
        $platformKey = $em
            ->getRepository('ChamiloPluginBundle:LtiProvider\PlatformKey')
            ->findOneBy([]);

        if ($this->get('enabled') === 'true') {
            if (!$platformKey) {
                $platformKey = new PlatformKey();
            }

            $keyPair = self::generatePlatformKeys();

            $platformKey->setKid($keyPair['kid']);
            $platformKey->publicKey = $keyPair['public'];
            $platformKey->setPrivateKey($keyPair['private']);

            $em->persist($platformKey);
        } else {
            if ($platformKey) {
                $em->remove($platformKey);
            }
        }

        $em->flush();

        return $this;
    }

    /**
     * Unistall plugin. Clear the database.
     */
    public function uninstall()
    {
        $em = Database::getManager();

        if (!$em->getConnection()->getSchemaManager()->tablesExist(['sfu_post'])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(
            [
                $em->getClassMetadata(Platform::class),
                $em->getClassMetadata(PlatformKey::class),
            ]
        );
    }

    public function trimParams(array &$params)
    {
        foreach ($params as $key => $value) {
            $newValue = preg_replace('/\s+/', ' ', $value);
            $params[$key] = trim($newValue);
        }
    }

    private function areTablesCreated(): bool
    {
        $entityManager = Database::getManager();
        $connection = $entityManager->getConnection();

        return $connection->getSchemaManager()->tablesExist(self::TABLE_PLATFORM);
    }

    /**
     * Generate a key pair and key id for the platform.
     *
     * Return a associative array like ['kid' => '...', 'private' => '...', 'public' => '...'].
     */
    private static function generatePlatformKeys(): array
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

    /**
     * Get a SimpleXMLElement object with the request received on php://input.
     *
     * @throws Exception
     */
    private function getRequestXmlElement(): ?SimpleXMLElement
    {
        $request = file_get_contents("php://input");

        if (empty($request)) {
            return null;
        }

        return new SimpleXMLElement($request);
    }
}
