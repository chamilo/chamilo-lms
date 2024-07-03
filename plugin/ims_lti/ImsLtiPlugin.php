<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\PluginBundle\Entity\ImsLti\LineItem;
use Chamilo\PluginBundle\Entity\ImsLti\Platform;
use Chamilo\PluginBundle\Entity\ImsLti\Token;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Tools\SchemaTool;
use Firebase\JWT\JWK;

/**
 * Description of MsiLti.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class ImsLtiPlugin extends Plugin
{
    const TABLE_TOOL = 'plugin_ims_lti_tool';
    const TABLE_PLATFORM = 'plugin_ims_lti_platform';

    public $isAdminPlugin = true;

    protected function __construct()
    {
        $version = '1.9.0';
        $author = 'Angel Fernando Quiroz Campos';

        $message = Display::return_message($this->get_lang('GenerateKeyPairInfo'));
        $settings = [
            $message => 'html',
            'enabled' => 'boolean',
        ];

        parent::__construct($version, $author, $settings);

        $this->setCourseSettings();
    }

    /**
     * Get the class instance.
     *
     * @staticvar MsiLtiPlugin $result
     *
     * @return ImsLtiPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Get the plugin directory name.
     */
    public function get_name()
    {
        return 'ims_lti';
    }

    /**
     * Install the plugin. Setup the database.
     *
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public function install()
    {
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
        $em = Database::getManager();

        /** @var Platform $platform */
        $platform = $em
            ->getRepository('ChamiloPluginBundle:ImsLti\Platform')
            ->findOneBy([]);

        if ($this->get('enabled') === 'true') {
            if (!$platform) {
                $platform = new Platform();
            }

            $keyPair = self::generatePlatformKeys();

            $platform->setKid($keyPair['kid']);
            $platform->publicKey = $keyPair['public'];
            $platform->setPrivateKey($keyPair['private']);

            $em->persist($platform);
        } else {
            if ($platform) {
                $em->remove($platform);
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
        try {
            $this->dropPluginTables();
            $this->removeTools();
        } catch (Exception $e) {
            error_log('Error while uninstalling IMS/LTI plugin: '.$e->getMessage());
        }
    }

    /**
     * @return CTool
     */
    public function findCourseToolByLink(Course $course, ImsLtiTool $ltiTool)
    {
        $em = Database::getManager();
        $toolRepo = $em->getRepository('ChamiloCourseBundle:CTool');

        /** @var CTool $cTool */
        $cTool = $toolRepo->findOneBy(
            [
                'cId' => $course,
                'link' => self::generateToolLink($ltiTool),
            ]
        );

        return $cTool;
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateCourseTool(CTool $courseTool, ImsLtiTool $ltiTool)
    {
        $em = Database::getManager();

        $courseTool->setName($ltiTool->getName());

        if ('iframe' !== $ltiTool->getDocumentTarget()) {
            $courseTool->setTarget('_blank');
        } else {
            $courseTool->setTarget('_self');
        }

        $em->persist($courseTool);
        $em->flush();
    }

    /**
     * Add the course tool.
     *
     * @param bool $isVisible
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addCourseTool(Course $course, ImsLtiTool $ltiTool, $isVisible = true)
    {
        $cTool = $this->createLinkToCourseTool(
            $ltiTool->getName(),
            $course->getId(),
            null,
            self::generateToolLink($ltiTool)
        );
        $cTool
            ->setTarget(
                $ltiTool->getDocumentTarget() === 'iframe' ? '_self' : '_blank'
            )
            ->setVisibility($isVisible);

        $em = Database::getManager();
        $em->persist($cTool);
        $em->flush();
    }

    /**
     * Add the course session tool.
     *
     * @param bool $isVisible
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addCourseSessionTool(Course $course, Session $session, ImsLtiTool $ltiTool, $isVisible = true)
    {
        $cTool = $this->createLinkToCourseTool(
            $ltiTool->getName(),
            $course->getId(),
            null,
            self::generateToolLink($ltiTool),
            $session->getId()
        );
        $cTool
            ->setTarget(
                $ltiTool->getDocumentTarget() === 'iframe' ? '_self' : '_blank'
            )
            ->setVisibility($isVisible);

        $em = Database::getManager();
        $em->persist($cTool);
        $em->flush();
    }

    public static function isInstructor()
    {
        api_is_allowed_to_edit(false, true);
    }

    /**
     * @return array
     */
    public static function getRoles(User $user)
    {
        $roles = ['http://purl.imsglobal.org/vocab/lis/v2/system/person#User'];

        if (DRH === $user->getStatus()) {
            $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Mentor';
            $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Mentor';

            return $roles;
        }

        if (!api_is_allowed_to_edit(false, true)) {
            $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner';
            $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Student';

            if ($user->getStatus() === INVITEE) {
                $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Guest';
            }

            return $roles;
        }

        $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Instructor';
        $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor';

        if (api_is_platform_admin_by_id($user->getId())) {
            $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator';
            $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/system/person#SysAdmin';
            $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator';
        }

        return $roles;
    }

    /**
     * @return string
     */
    public static function getUserRoles(User $user)
    {
        if (DRH === $user->getStatus()) {
            return 'urn:lti:role:ims/lis/Mentor';
        }

        if ($user->getStatus() === INVITEE) {
            return 'Learner,urn:lti:role:ims/lis/Learner/GuestLearner';
        }

        if (!api_is_allowed_to_edit(false, true)) {
            return 'Learner';
        }

        $roles = ['Instructor'];

        if (api_is_platform_admin_by_id($user->getId())) {
            $roles[] = 'urn:lti:role:ims/lis/Administrator';
        }

        return implode(',', $roles);
    }

    /**
     * @param int $userId
     *
     * @return string
     */
    public static function generateToolUserId($userId)
    {
        $siteName = api_get_setting('siteName');
        $institution = api_get_setting('Institution');
        $toolUserId = "$siteName - $institution - $userId";
        $toolUserId = api_replace_dangerous_char($toolUserId);

        return $toolUserId;
    }

    /**
     * @return string
     */
    public static function getLaunchUserIdClaim(ImsLtiTool $tool, User $user)
    {
        if (null !== $tool->getParent()) {
            $tool = $tool->getParent();
        }

        $replacement = $tool->getReplacementForUserId();

        if (empty($replacement)) {
            if ($tool->getVersion() === ImsLti::V_1P1) {
                return self::generateToolUserId($user->getId());
            }

            return (string) $user->getId();
        }

        $replaced = str_replace(
            ['$User.id', '$User.username'],
            [$user->getId(), $user->getUsername()],
            $replacement
        );

        return $replaced;
    }

    /**
     * @return string
     */
    public static function getRoleScopeMentor(User $currentUser, ImsLtiTool $tool)
    {
        $scope = self::getRoleScopeMentorAsArray($currentUser, $tool, true);

        return implode(',', $scope);
    }

    /**
     * Tool User IDs which the user DRH can access as a mentor.
     *
     * @param bool $generateIdForTool. Optional. Set TRUE for LTI 1.x.
     *
     * @return array
     */
    public static function getRoleScopeMentorAsArray(User $user, ImsLtiTool $tool, $generateIdForTool = false)
    {
        if (DRH !== $user->getStatus()) {
            return [];
        }

        $followedUsers = UserManager::get_users_followed_by_drh($user->getId(), 0, true);
        $scope = [];
        /** @var array $userInfo */
        foreach ($followedUsers as $userInfo) {
            if ($generateIdForTool) {
                $followedUser = api_get_user_entity($userInfo['user_id']);

                $scope[] = self::getLaunchUserIdClaim($tool, $followedUser);
            } else {
                $scope[] = (string) $userInfo['user_id'];
            }
        }

        return $scope;
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveItemAsLtiLink(array $contentItem, ImsLtiTool $baseLtiTool, Course $course)
    {
        $em = Database::getManager();
        $ltiToolRepo = $em->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool');

        $url = empty($contentItem['url']) ? $baseLtiTool->getLaunchUrl() : $contentItem['url'];

        /** @var ImsLtiTool $newLtiTool */
        $newLtiTool = $ltiToolRepo->findOneBy(['launchUrl' => $url, 'parent' => $baseLtiTool, 'course' => $course]);

        if (null === $newLtiTool) {
            $newLtiTool = new ImsLtiTool();
            $newLtiTool
                ->setLaunchUrl($url)
                ->setParent(
                    $baseLtiTool
                )
                ->setPrivacy(
                    $baseLtiTool->isSharingName(),
                    $baseLtiTool->isSharingEmail(),
                    $baseLtiTool->isSharingPicture()
                )
                ->setCourse($course);
        }

        $newLtiTool
            ->setName(
                !empty($contentItem['title']) ? $contentItem['title'] : $baseLtiTool->getName()
            )
            ->setDescription(
                !empty($contentItem['text']) ? $contentItem['text'] : null
            );

        if (!empty($contentItem['custom'])) {
            $newLtiTool
                ->setCustomParams(
                    $newLtiTool->encodeCustomParams($contentItem['custom'])
                );
        }

        $em->persist($newLtiTool);
        $em->flush();

        $courseTool = $this->findCourseToolByLink($course, $newLtiTool);

        if ($courseTool) {
            $this->updateCourseTool($courseTool, $newLtiTool);

            return;
        }

        $this->addCourseTool($course, $newLtiTool);
    }

    /**
     * @return ImsLtiServiceResponse|null
     */
    public function processServiceRequest()
    {
        $xml = $this->getRequestXmlElement();

        if (empty($xml)) {
            return null;
        }

        $request = ImsLtiServiceRequestFactory::create($xml);

        return $request->process();
    }

    /**
     * @param int $toolId
     *
     * @return bool
     */
    public static function existsToolInCourse($toolId, Course $course)
    {
        $em = Database::getManager();
        $toolRepo = $em->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool');

        /** @var ImsLtiTool $tool */
        $tool = $toolRepo->findOneBy(['id' => $toolId, 'course' => $course]);

        return !empty($tool);
    }

    /**
     * @param string $configUrl
     *
     * @throws Exception
     *
     * @return string
     */
    public function getLaunchUrlFromCartridge($configUrl)
    {
        $options = [
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POST => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYPEER => false,
        ];

        $ch = curl_init($configUrl);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0) {
            throw new Exception($this->get_lang('NoAccessToUrl'));
        }

        $xml = new SimpleXMLElement($content);
        $result = $xml->xpath('blti:launch_url');

        if (empty($result)) {
            throw new Exception($this->get_lang('LaunchUrlNotFound'));
        }

        $launchUrl = $result[0];

        return (string) $launchUrl;
    }

    public function trimParams(array &$params)
    {
        foreach ($params as $key => $value) {
            $newValue = preg_replace('/\s+/', ' ', $value);
            $params[$key] = trim($newValue);
        }
    }

    /**
     * @return array
     */
    public function removeUrlParamsFromLaunchParams(ImsLtiTool $tool, array &$params)
    {
        $urlQuery = parse_url($tool->getLaunchUrl(), PHP_URL_QUERY);

        if (empty($urlQuery)) {
            return $params;
        }

        $queryParams = [];
        parse_str($urlQuery, $queryParams);
        $queryKeys = array_keys($queryParams);

        foreach ($queryKeys as $key) {
            if (isset($params[$key])) {
                unset($params[$key]);
            }
        }
    }

    /**
     * Avoid conflict with foreign key when deleting a course.
     *
     * @param int $courseId
     */
    public function doWhenDeletingCourse($courseId)
    {
        $em = Database::getManager();
        $q = $em
            ->createQuery(
                'DELETE FROM ChamiloPluginBundle:ImsLti\ImsLtiTool tool
                    WHERE tool.course = :c_id and tool.parent IS NOT NULL'
            );
        $q->execute(['c_id' => (int) $courseId]);

        $em->createQuery('DELETE FROM ChamiloPluginBundle:ImsLti\ImsLtiTool tool WHERE tool.course = :c_id')
            ->execute(['c_id' => (int) $courseId]);
    }

    /**
     * @return string
     */
    public static function getIssuerUrl()
    {
        $webPath = api_get_path(WEB_PATH);

        return trim($webPath, " /");
    }

    public static function getCoursesForParentTool(ImsLtiTool $tool, Session $session = null)
    {
        if ($tool->getParent()) {
            return [];
        }

        $children = $tool->getChildren();

        if ($session) {
            $children = $children->filter(function (ImsLtiTool $tool) use ($session) {
                if (null === $tool->getSession()) {
                    return false;
                }

                if ($tool->getSession()->getId() !== $session->getId()) {
                    return false;
                }

                return true;
            });
        }

        return $children->map(function (ImsLtiTool $tool) {
            return $tool->getCourse();
        });
    }

    /**
     * It gets the public key from jwks or rsa keys.
     *
     * @param ImsLtiTool $tool
     *
     * @return mixed|string|null
     */
    public static function getToolPublicKey(ImsLtiTool $tool)
    {
        $publicKey = '';
        if (!empty($tool->getJwksUrl())) {
            $publicKeySet = json_decode(file_get_contents($tool->getJwksUrl()), true);
            $pk = [];
            foreach ($publicKeySet['keys'] as $key) {
                $pk = openssl_pkey_get_details(
                    JWK::parseKeySet(['keys' => [$key]])[$key['kid']]
                );
            }
            if (!empty($pk)) {
                $publicKey = $pk['key'];
            }
        } else {
            $publicKey = $tool->publicKey;
        };

        return $publicKey;
    }

    /**
     * @return string
     */
    protected function getConfigExtraText()
    {
        $text = $this->get_lang('ImsLtiDescription');
        $text .= sprintf(
            $this->get_lang('ManageToolButton'),
            api_get_path(WEB_PLUGIN_PATH).'ims_lti/admin.php'
        );

        return $text;
    }

    /**
     * Creates the plugin tables on database.
     *
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    private function createPluginTables()
    {
        $em = Database::getManager();

        if ($em->getConnection()->getSchemaManager()->tablesExist([self::TABLE_TOOL])) {
            return;
        };

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(
            [
                $em->getClassMetadata(ImsLtiTool::class),
                $em->getClassMetadata(LineItem::class),
                $em->getClassMetadata(Platform::class),
                $em->getClassMetadata(Token::class),
            ]
        );
    }

    /**
     * Drops the plugin tables on database.
     */
    private function dropPluginTables()
    {
        $em = Database::getManager();

        if (!$em->getConnection()->getSchemaManager()->tablesExist([self::TABLE_TOOL])) {
            return;
        };

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(
            [
                $em->getClassMetadata(ImsLtiTool::class),
                $em->getClassMetadata(LineItem::class),
                $em->getClassMetadata(Platform::class),
                $em->getClassMetadata(Token::class),
            ]
        );
    }

    private function removeTools()
    {
        $sql = "DELETE FROM c_tool WHERE link LIKE 'ims_lti/start.php%' AND category = 'plugin'";
        Database::query($sql);
    }

    /**
     * Set the course settings.
     */
    private function setCourseSettings()
    {
        $button = Display::toolbarButton(
            $this->get_lang('ConfigureExternalTool'),
            api_get_path(WEB_PLUGIN_PATH).'ims_lti/configure.php?'.api_get_cidreq(),
            'cog',
            'primary'
        );

        // This setting won't be saved in the database.
        $this->course_settings = [
            [
                'name' => $this->get_lang('ImsLtiDescription').$button.'<hr>',
                'type' => 'html',
            ],
        ];
    }

    /**
     * @return string
     */
    private static function generateToolLink(ImsLtiTool $tool)
    {
        return 'ims_lti/start.php?id='.$tool->getId();
    }

    /**
     * @return SimpleXMLElement|null
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
}
