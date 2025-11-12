<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAccess;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\PluginBundle\EmbedRegistry\Entity\Embed;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Class EmbedRegistryPlugin.
 */
class EmbedRegistryPlugin extends Plugin
{
    public const SETTING_ENABLED = 'tool_enabled';
    public const SETTING_TITLE = 'tool_title';
    public const SETTING_EXTERNAL_URL = 'external_url';
    public const TBL_EMBED = 'plugin_embed_registry_embed';

    /**
     * EmbedRegistryPlugin constructor.
     */
    protected function __construct()
    {
        $authors = [
            'Angel Fernando Quiroz Campos',
        ];

        parent::__construct(
            '1.0',
            implode(', ', $authors),
            [
                self::SETTING_ENABLED => 'boolean',
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
     * @return EmbedRegistryPlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Create DB schema for this plugin if not present.
     *
     * @throws \Doctrine\ORM\Tools\ToolsException
     * @throws \Doctrine\DBAL\Exception
     */
    public function install()
    {
        $em = Database::getManager();

        if ($em->getConnection()->createSchemaManager()->tablesExist([self::TBL_EMBED])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema([$em->getClassMetadata(Embed::class)]);
    }

    /**
     * Drop DB schema if present.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function uninstall()
    {
        $em = Database::getManager();

        if (!$em->getConnection()->createSchemaManager()->tablesExist([self::TBL_EMBED])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema([$em->getClassMetadata(Embed::class)]);
    }

    /**
     * After (re)configuring the plugin, (re)create tool links if enabled.
     *
     * @return EmbedRegistryPlugin
     */
    public function performActionsAfterConfigure()
    {
        $em = Database::getManager();

        $this->deleteCourseToolLinks();

        if ('true' === $this->get(self::SETTING_ENABLED)) {
            // Use FQCN instead of "ChamiloCoreBundle:Course".
            $courses = $em->createQuery('SELECT c.id FROM '.Course::class.' c')->getResult();

            foreach ($courses as $course) {
                $this->createLinkToCourseTool($this->getToolTitle(), $course['id']);
            }
        }

        return $this;
    }

    /**
     * Hook called when a course is deleted.
     * We only have the course ID here, but e.course is a relation,
     * so we must compare using IDENTITY(e.course) = :courseId.
     *
     * @param int $courseId
     */
    public function doWhenDeletingCourse($courseId)
    {
        $em = Database::getManager();

        // IMPORTANT: Use FQCN and IDENTITY() for relation-to-id comparison.
        $em->createQuery(
            'DELETE FROM '.Embed::class.' e WHERE IDENTITY(e.course) = :courseId'
        )
            ->setParameter('courseId', (int) $courseId)
            ->execute();
    }

    /**
     * Hook called when a session is deleted.
     * We only have the session ID here, but e.session is a relation,
     * so we must compare using IDENTITY(e.session) = :sessionId.
     *
     * @param int $sessionId
     */
    public function doWhenDeletingSession($sessionId)
    {
        $em = Database::getManager();

        // IMPORTANT: Use FQCN and IDENTITY() for relation-to-id comparison.
        $em->createQuery(
            'DELETE FROM '.Embed::class.' e WHERE IDENTITY(e.session) = :sessionId'
        )
            ->setParameter('sessionId', (int) $sessionId)
            ->execute();
    }

    /**
     * Get the currently active embed (by date range) for a course and optional session.
     * DO NOT compare an entity relation to a scalar id in DQL; bind the entity itself.
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
        return api_get_path(WEB_PLUGIN_PATH).'EmbedRegistry/view.php?id='.$embed->getId().'&'.api_get_cidreq();
    }

    /**
     * Count distinct users who accessed this plugin within the embed date window.
     * NOTE: TrackEAccess uses scalar fields (cId, accessSessionId), so pass integers.
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
            // IMPORTANT: cId is an integer field, not a relation.
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
     * Track a plugin access event (raw SQL-level insert is fine here).
     */
    public function saveEventAccessTool()
    {
        $tableAccess = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);
        $params = [
            'access_user_id' => api_get_user_id(),
            'c_id' => api_get_course_int_id(),
            'access_tool' => 'plugin_'.$this->get_name(),
            'access_date' => api_get_utc_datetime(),
            'access_session_id' => api_get_session_id(),
            'user_ip' => api_get_real_ip(),
        ];
        Database::insert($tableAccess, $params);
    }

    /**
     * Remove tool links created for this plugin in course tools.
     * Use FQCN (CTool::class) instead of "ChamiloCourseBundle:CTool".
     */
    private function deleteCourseToolLinks()
    {
        Database::getManager()
            ->createQuery(
                'DELETE FROM '.CTool::class.' t WHERE t.category = :category AND t.link LIKE :link'
            )
            ->setParameters(['category' => 'plugin', 'link' => 'EmbedRegistry/start.php%'])
            ->execute();
    }
}
