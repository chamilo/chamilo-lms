<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\PluginBundle\Entity\ImsLti\Platform;
use Chamilo\UserBundle\Entity\User;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Description of MsiLti
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
        $version = '1.8.0';
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
     * Get the class instance
     * @staticvar MsiLtiPlugin $result
     * @return ImsLtiPlugin
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
        return 'ims_lti';
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
            $this->removeTools();
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

        if ($connection->getSchemaManager()->tablesExist(self::TABLE_TOOL)) {
            return true;
        }

        $queries = [
            "CREATE TABLE plugin_ims_lti_tool (
                    id INT AUTO_INCREMENT NOT NULL,
                    c_id INT DEFAULT NULL,
                    gradebook_eval_id INT DEFAULT NULL,
                    parent_id INT DEFAULT NULL,
                    name VARCHAR(255) NOT NULL,
                    description LONGTEXT DEFAULT NULL,
                    launch_url VARCHAR(255) NOT NULL,
                    consumer_key VARCHAR(255) DEFAULT NULL,
                    shared_secret VARCHAR(255) DEFAULT NULL,
                    custom_params LONGTEXT DEFAULT NULL,
                    active_deep_linking TINYINT(1) DEFAULT '0' NOT NULL,
                    privacy LONGTEXT DEFAULT NULL,
                    client_id VARCHAR(255) DEFAULT NULL,
                    public_key LONGTEXT DEFAULT NULL,
                    login_url VARCHAR(255) DEFAULT NULL,
                    redirect_url VARCHAR(255) DEFAULT NULL,
                    advantage_services LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)',
                    version VARCHAR(255) DEFAULT 'lti1p1' NOT NULL,
                    launch_presentation LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
                    replacement_params LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
                    INDEX IDX_C5E47F7C91D79BD3 (c_id),
                    INDEX IDX_C5E47F7C82F80D8B (gradebook_eval_id),
                    INDEX IDX_C5E47F7C727ACA70 (parent_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB",
            "CREATE TABLE plugin_ims_lti_platform (
                    id INT AUTO_INCREMENT NOT NULL,
                    kid VARCHAR(255) NOT NULL,
                    public_key LONGTEXT NOT NULL,
                    private_key LONGTEXT NOT NULL,
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB",
            "CREATE TABLE plugin_ims_lti_token (
                    id INT AUTO_INCREMENT NOT NULL,
                    tool_id INT DEFAULT NULL,
                    scope LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
                    hash VARCHAR(255) NOT NULL,
                    created_at INT NOT NULL,
                    expires_at INT NOT NULL,
                    INDEX IDX_F7B5692F8F7B22CC (tool_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB",
            "ALTER TABLE plugin_ims_lti_tool
                ADD CONSTRAINT FK_C5E47F7C91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id)",
            "ALTER TABLE plugin_ims_lti_tool
                ADD CONSTRAINT FK_C5E47F7C82F80D8B FOREIGN KEY (gradebook_eval_id)
                REFERENCES gradebook_evaluation (id) ON DELETE SET NULL",
            "ALTER TABLE plugin_ims_lti_tool
                ADD CONSTRAINT FK_C5E47F7C727ACA70 FOREIGN KEY (parent_id)
                REFERENCES plugin_ims_lti_tool (id) ON DELETE CASCADE",
            "ALTER TABLE plugin_ims_lti_token
                ADD CONSTRAINT FK_F7B5692F8F7B22CC FOREIGN KEY (tool_id)
                REFERENCES plugin_ims_lti_tool (id) ON DELETE CASCADE",
            "CREATE TABLE plugin_ims_lti_lineitem (
                    id INT AUTO_INCREMENT NOT NULL,
                    tool_id INT NOT NULL,
                    evaluation INT NOT NULL,
                    resource_id VARCHAR(255) DEFAULT NULL,
                    tag VARCHAR(255) DEFAULT NULL,
                    start_date DATETIME DEFAULT NULL,
                    end_date DATETIME DEFAULT NULL,
                    INDEX IDX_BA81BBF08F7B22CC (tool_id),
                    UNIQUE INDEX UNIQ_BA81BBF01323A575 (evaluation),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB",
            "ALTER TABLE plugin_ims_lti_lineitem ADD CONSTRAINT FK_BA81BBF08F7B22CC FOREIGN KEY (tool_id)
                REFERENCES plugin_ims_lti_tool (id) ON DELETE CASCADE",
            "ALTER TABLE plugin_ims_lti_lineitem ADD CONSTRAINT FK_BA81BBF01323A575 FOREIGN KEY (evaluation)
                REFERENCES gradebook_evaluation (id) ON DELETE CASCADE "
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
        Database::query("DROP TABLE IF EXISTS plugin_ims_lti_lineitem");
        Database::query("DROP TABLE IF EXISTS plugin_ims_lti_token");
        Database::query("DROP TABLE IF EXISTS plugin_ims_lti_platform");
        Database::query("DROP TABLE IF EXISTS plugin_ims_lti_tool");

        return true;
    }

    private function removeTools()
    {
        $sql = "DELETE FROM c_tool WHERE link LIKE 'ims_lti/start.php%' AND category = 'plugin'";
        Database::query($sql);
    }

    /**
     * Set the course settings
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
     * @param Course     $course
     * @param ImsLtiTool $ltiTool
     *
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
     * @param CTool      $courseTool
     * @param ImsLtiTool $ltiTool
     *
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
     * @param ImsLtiTool $tool
     *
     * @return string
     */
    private static function generateToolLink(ImsLtiTool $tool)
    {
        return 'ims_lti/start.php?id='.$tool->getId();
    }

    /**
     * Add the course tool.
     *
     * @param Course     $course
     * @param ImsLtiTool $ltiTool
     * @param bool       $isVisible
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
     * @param User $user
     *
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
     * @param User         $user
     *
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
     * @param ImsLtiTool $tool
     * @param User       $user
     *
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
     * @param User $currentUser
     * @param ImsLtiTool $tool
     *
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
     * @param User       $user
     * @param ImsLtiTool $tool
     * @param bool       $generateIdForTool. Optional. Set TRUE for LTI 1.x.
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
     * @param array      $contentItem
     * @param ImsLtiTool $baseLtiTool
     * @param Course     $course
     *
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
     * @param int    $toolId
     * @param Course $course
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
     * @return string
     * @throws Exception
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
     * @param ImsLtiTool $tool
     * @param array      $params
     *
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
     * Avoid conflict with foreign key when deleting a course
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

    /**
     * @return string
     */
    public static function getIssuerUrl()
    {
        $webPath = api_get_path(WEB_PATH);

        return trim($webPath, " /");
    }

    public static function getCoursesForParentTool(ImsLtiTool $tool)
    {
        $coursesId = [];
        if (!$tool->getParent()) {
            $coursesId = $tool->getChildren()->map(function (ImsLtiTool $tool) {
                return $tool->getCourse();
            });
        }

        return $coursesId;
    }
}
