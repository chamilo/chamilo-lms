<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAccess;
use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\PluginBundle\EmbedRegistry\Entity\Embed;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Class EmbedRegistryPlugin.
 */
class EmbedRegistryPlugin extends Plugin
{
    public const SETTING_TITLE = 'tool_title';
    public const SETTING_EXTERNAL_URL = 'external_url';
    public const TBL_EMBED = 'plugin_embed_registry_embed';
    public const TBL_SHORTCUT = 'plugin_embed_registry_shortcut';

    /**
     * EmbedRegistryPlugin constructor.
     */
    protected function __construct()
    {
        $authors = [
            'Angel Fernando Quiroz Campos',
        ];

        $this->isCoursePlugin = true;
        $this->addCourseTool = false;

        parent::__construct(
            '1.0',
            implode(', ', $authors),
            [
                self::SETTING_TITLE => 'text',
                self::SETTING_EXTERNAL_URL => 'text',
            ]
        );
    }

    /**
     * @return string
     */
    public function getToolTitle()
    {
        $title = $this->get(self::SETTING_TITLE);

        if (!empty($title)) {
            return $title;
        }

        return $this->get_title();
    }

    /**
     * @return string
     */
    public function getExternalUrl()
    {
        $url = trim((string) $this->get(self::SETTING_EXTERNAL_URL));

        if (empty($url)) {
            return '';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return $url;
    }

    /**
     * @return EmbedRegistryPlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Create DB schema and shortcuts for this plugin if not present.
     *
     * @throws \Doctrine\ORM\Tools\ToolsException
     * @throws \Doctrine\DBAL\Exception
     */
    public function install()
    {
        $this->ensureSchema();

        if ($this->isEnabled()) {
            $this->addShortcutInAllCourses();
        }
    }

    /**
     * Drop DB schema if present.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function uninstall()
    {
        $this->deleteCourseShortcuts();
        $this->deleteCourseToolLinks();

        $em = Database::getManager();
        $connection = $em->getConnection();
        $schemaManager = $connection->createSchemaManager();

        if ($schemaManager->tablesExist([self::TBL_SHORTCUT])) {
            $connection->executeStatement('DROP TABLE '.self::TBL_SHORTCUT);
        }

        if (!$schemaManager->tablesExist([self::TBL_EMBED])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema([
            $em->getClassMetadata(Embed::class),
        ]);
    }

    /**
     * After (re)configuring the plugin, (re)create course home shortcuts if the plugin is active.
     *
     * @return EmbedRegistryPlugin
     */
    public function performActionsAfterConfigure()
    {
        $this->ensureSchema();
        $this->deleteCourseToolLinks();

        if (!$this->isEnabled()) {
            return $this;
        }

        $this->addShortcutInAllCourses();

        return $this;
    }

    public function ensureSchema(): void
    {
        $em = Database::getManager();
        $connection = $em->getConnection();
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::TBL_EMBED])) {
            $schemaTool = new SchemaTool($em);
            $schemaTool->createSchema([
                $em->getClassMetadata(Embed::class),
            ]);
        }

        if ($schemaManager->tablesExist([self::TBL_SHORTCUT])) {
            return;
        }

        $connection->executeStatement(
            'CREATE TABLE '.self::TBL_SHORTCUT.' (
                id INT AUTO_INCREMENT NOT NULL,
                course_id INT NOT NULL,
                shortcut_id INT NOT NULL,
                UNIQUE INDEX UNIQ_EMBED_REGISTRY_SHORTCUT_COURSE (course_id),
                UNIQUE INDEX UNIQ_EMBED_REGISTRY_SHORTCUT_SHORTCUT (shortcut_id),
                INDEX IDX_EMBED_REGISTRY_SHORTCUT_COURSE (course_id),
                INDEX IDX_EMBED_REGISTRY_SHORTCUT_SHORTCUT (shortcut_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
    }

    /**
     * Hook called when a course is deleted.
     *
     * @param int $courseId
     */
    public function doWhenDeletingCourse($courseId)
    {
        $this->deleteShortcutForCourse((int) $courseId);

        $em = Database::getManager();

        $em->createQuery(
            'DELETE FROM '.Embed::class.' e WHERE IDENTITY(e.course) = :courseId'
        )
            ->setParameter('courseId', (int) $courseId)
            ->execute();
    }

    /**
     * Hook called when a session is deleted.
     *
     * @param int $sessionId
     */
    public function doWhenDeletingSession($sessionId)
    {
        $em = Database::getManager();

        $em->createQuery(
            'DELETE FROM '.Embed::class.' e WHERE IDENTITY(e.session) = :sessionId'
        )
            ->setParameter('sessionId', (int) $sessionId)
            ->execute();
    }

    /**
     * Get the currently active embed for a course and optional session.
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return Embed|null
     */
    public function getCurrentEmbed(Course $course, Session $session = null)
    {
        $em = Database::getManager();
        $repo = $em->getRepository(Embed::class);
        $qb = $repo->createQueryBuilder('e');

        $qb
            ->where('e.displayStartDate <= :now')
            ->andWhere('e.displayEndDate >= :now')
            ->andWhere('e.course = :course')
            ->setParameter('now', new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->setParameter('course', $course);

        if ($session) {
            $qb->andWhere('e.session = :session')
                ->setParameter('session', $session);
        } else {
            $qb->andWhere('e.session IS NULL');
        }

        return $qb
            ->orderBy('e.displayStartDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Human-readable date range for an embed.
     *
     * @return string
     */
    public function formatDisplayDate(Embed $embed)
    {
        $startDate = sprintf(
            '<time datetime="%s">%s</time>',
            $embed->getDisplayStartDate()->format(\DateTimeInterface::W3C),
            api_convert_and_format_date($embed->getDisplayStartDate())
        );
        $endDate = sprintf(
            '<time datetime="%s">%s</time>',
            $embed->getDisplayEndDate()->format(\DateTimeInterface::W3C),
            api_convert_and_format_date($embed->getDisplayEndDate())
        );

        return sprintf(get_lang('From %s to %s'), $startDate, $endDate);
    }

    /**
     * URL to view a single embed in the plugin.
     *
     * @return string
     */
    public function getViewUrl(Embed $embed)
    {
        return api_get_path(WEB_PLUGIN_PATH).'EmbedRegistry/view.php?id='.(int) $embed->getId().'&'.api_get_cidreq();
    }

    /**
     * Count distinct users who accessed this plugin within the embed date window.
     *
     * @throws \Doctrine\ORM\Query\QueryException
     *
     * @return int
     */
    public function getMembersCount(Embed $embed)
    {
        $dql = 'SELECT COUNT(DISTINCT tea.accessUserId) FROM '.TrackEAccess::class.' tea
                WHERE
                    tea.accessTool = :tool AND
                    (tea.accessDate >= :start_date AND tea.accessDate <= :end_date) AND
                    tea.cId = :courseId';

        $params = [
            'tool' => 'plugin_'.$this->get_name(),
            'start_date' => $embed->getDisplayStartDate(),
            'end_date' => $embed->getDisplayEndDate(),
            'courseId' => $embed->getCourse()->getId(),
        ];

        if ($embed->getSession()) {
            $dql .= ' AND tea.accessSessionId = :sessionId ';
            $params['sessionId'] = $embed->getSession()->getId();
        }

        return (int) Database::getManager()
            ->createQuery($dql)
            ->setParameters($params)
            ->getSingleScalarResult();
    }

    /**
     * Track a plugin access event without breaking the embedded content view.
     */
    public function saveEventAccessTool()
    {
        $tableAccess = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);

        try {
            $columns = [];
            $result = Database::query('SHOW COLUMNS FROM '.$tableAccess);

            while ($row = Database::fetch_assoc($result)) {
                if (isset($row['Field'])) {
                    $columns[(string) $row['Field']] = true;
                }
            }

            $params = [
                'access_user_id' => api_get_user_id(),
                'c_id' => api_get_course_int_id(),
                'access_tool' => 'plugin_'.$this->get_name(),
                'access_date' => api_get_utc_datetime(),
                'access_session_id' => api_get_session_id(),
                'user_ip' => api_get_real_ip(),
            ];

            $params = array_intersect_key($params, $columns);

            if (empty($params)) {
                return;
            }

            Database::insert($tableAccess, $params);
        } catch (Throwable $exception) {
            error_log('[EmbedRegistry] Unable to save access event: '.$exception->getMessage());
        }
    }

    public function addShortcutInAllCourses(): void
    {
        $em = Database::getManager();

        /** @var Course $course */
        foreach ($em->getRepository(Course::class)->findAll() as $course) {
            $this->addShortcutInCourse($course);
        }
    }

    public function addShortcutInCourse(Course $course): void
    {
        $this->ensureSchema();

        $em = Database::getManager();
        $connection = $em->getConnection();
        $courseId = (int) $course->getId();

        $shortcutId = (int) $connection->fetchOne(
            'SELECT shortcut_id FROM '.self::TBL_SHORTCUT.' WHERE course_id = :courseId',
            ['courseId' => $courseId]
        );

        if ($shortcutId > 0) {
            $shortcut = $em->getRepository(CShortcut::class)->find($shortcutId);

            if ($shortcut instanceof CShortcut) {
                $shortcut->setTitle($this->getToolTitle());
                $em->persist($shortcut);
                $em->flush();

                return;
            }

            $connection->executeStatement(
                'DELETE FROM '.self::TBL_SHORTCUT.' WHERE course_id = :courseId',
                ['courseId' => $courseId]
            );
        }

        $creator = $course->getCreator();

        if (null === $creator) {
            $creator = api_get_user_entity();
        }

        if (null === $creator) {
            return;
        }

        $shortcut = (new CShortcut())
            ->setTitle($this->getToolTitle())
            ->setParent($course)
            ->setCreator($creator)
            ->addCourseLink($course)
        ;

        $em->persist($shortcut);
        $em->flush();

        if (null === $shortcut->getResourceNode()) {
            return;
        }

        $shortcut->setShortCutNode($shortcut->getResourceNode());
        $em->persist($shortcut);
        $em->flush();

        $connection->executeStatement(
            'INSERT INTO '.self::TBL_SHORTCUT.' (course_id, shortcut_id) VALUES (:courseId, :shortcutId)',
            [
                'courseId' => $courseId,
                'shortcutId' => (int) $shortcut->getId(),
            ]
        );
    }

    public function deleteCourseShortcuts(): void
    {
        $em = Database::getManager();
        $connection = $em->getConnection();
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::TBL_SHORTCUT])) {
            return;
        }

        $rows = $connection->fetchAllAssociative('SELECT shortcut_id FROM '.self::TBL_SHORTCUT);

        foreach ($rows as $row) {
            $shortcutId = (int) ($row['shortcut_id'] ?? 0);

            if (0 === $shortcutId) {
                continue;
            }

            $shortcut = $em->getRepository(CShortcut::class)->find($shortcutId);

            if ($shortcut instanceof CShortcut) {
                $em->remove($shortcut);
            }
        }

        $connection->executeStatement('DELETE FROM '.self::TBL_SHORTCUT);
        $em->flush();
    }

    private function deleteShortcutForCourse(int $courseId): void
    {
        $em = Database::getManager();
        $connection = $em->getConnection();
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::TBL_SHORTCUT])) {
            return;
        }

        $shortcutId = (int) $connection->fetchOne(
            'SELECT shortcut_id FROM '.self::TBL_SHORTCUT.' WHERE course_id = :courseId',
            ['courseId' => $courseId]
        );

        $connection->executeStatement(
            'DELETE FROM '.self::TBL_SHORTCUT.' WHERE course_id = :courseId',
            ['courseId' => $courseId]
        );

        if (0 === $shortcutId) {
            return;
        }

        $shortcut = $em->getRepository(CShortcut::class)->find($shortcutId);

        if ($shortcut instanceof CShortcut) {
            $em->remove($shortcut);
            $em->flush();
        }
    }

    /**
     * Remove old CTool rows created by previous EmbedRegistry versions.
     */
    private function deleteCourseToolLinks(): void
    {
        $connection = Database::getManager()->getConnection();
        $schemaManager = $connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tool']) || !$schemaManager->tablesExist(['c_tool'])) {
            return;
        }

        $toolRows = $connection->fetchAllAssociative(
            'SELECT id FROM tool WHERE title IN (:pluginTitle, :configuredTitle, :legacyTitle)',
            [
                'pluginTitle' => $this->get_name(),
                'configuredTitle' => $this->getToolTitle(),
                'legacyTitle' => $this->get_title(),
            ]
        );

        foreach ($toolRows as $toolRow) {
            $toolId = (int) ($toolRow['id'] ?? 0);

            if (0 === $toolId) {
                continue;
            }

            $connection->executeStatement(
                'DELETE FROM c_tool WHERE tool_id = :toolId',
                ['toolId' => $toolId]
            );
        }
    }
}
