<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PluginBundle\Entity\EmbedRegistry\Embed;
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
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public function install()
    {
        $em = Database::getManager();

        if ($em->getConnection()->getSchemaManager()->tablesExist([self::TBL_EMBED])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(
            [
                $em->getClassMetadata(Embed::class),
            ]
        );
    }

    public function uninstall()
    {
        $em = Database::getManager();

        if (!$em->getConnection()->getSchemaManager()->tablesExist([self::TBL_EMBED])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(
            [
                $em->getClassMetadata(Embed::class),
            ]
        );
    }

    /**
     * @return EmbedRegistryPlugin
     */
    public function performActionsAfterConfigure()
    {
        $em = Database::getManager();

        $this->deleteCourseToolLinks();

        if ('true' === $this->get(self::SETTING_ENABLED)) {
            $courses = $em->createQuery('SELECT c.id FROM ChamiloCoreBundle:Course c')->getResult();

            foreach ($courses as $course) {
                $this->createLinkToCourseTool($this->getToolTitle(), $course['id']);
            }
        }

        return $this;
    }

    /**
     * @param int $courseId
     */
    public function doWhenDeletingCourse($courseId)
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloPluginBundle:EmbedRegistry\Embed e WHERE e.course = :course')
            ->execute(['course' => (int) $courseId]);
    }

    /**
     * @param int $sessionId
     */
    public function doWhenDeletingSession($sessionId)
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloPluginBundle:EmbedRegistry\Embed e WHERE e.session = :session')
            ->execute(['session' => (int) $sessionId]);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return Embed
     */
    public function getCurrentEmbed(Course $course, Session $session = null)
    {
        $embedRepo = Database::getManager()->getRepository('ChamiloPluginBundle:EmbedRegistry\Embed');
        $qb = $embedRepo->createQueryBuilder('e');
        $query = $qb
            ->where('e.displayStartDate <= :now')
            ->andWhere('e.displayEndDate >= :now')
            ->andWhere(
                $qb->expr()->eq('e.course', $course->getId())
            );

        $query->andWhere(
            $session
                ? $qb->expr()->eq('e.session', $session->getId())
                : $qb->expr()->isNull('e.session')
        );

        $query = $query
            ->orderBy('e.displayStartDate', 'DESC')
            ->setMaxResults(1)
            ->setParameters(['now' => api_get_utc_datetime(null, false, true)])
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @return string
     */
    public function formatDisplayDate(Embed $embed)
    {
        $startDate = sprintf(
            '<time datetime="%s">%s</time>',
            $embed->getDisplayStartDate()->format(DateTime::W3C),
            api_convert_and_format_date($embed->getDisplayStartDate())
        );
        $endDate = sprintf(
            '<time datetime="%s">%s</time>',
            $embed->getDisplayEndDate()->format(DateTime::W3C),
            api_convert_and_format_date($embed->getDisplayEndDate())
        );

        return sprintf(get_lang('FromDateXToDateY'), $startDate, $endDate);
    }

    /**
     * @return string
     */
    public function getViewUrl(Embed $embed)
    {
        return api_get_path(WEB_PLUGIN_PATH).'embedregistry/view.php?id='.$embed->getId().'&'.api_get_cidreq();
    }

    /**
     * @throws \Doctrine\ORM\Query\QueryException
     *
     * @return int
     */
    public function getMembersCount(Embed $embed)
    {
        $dql = 'SELECT COUNT(DISTINCT tea.accessUserId) FROM ChamiloCoreBundle:TrackEAccess tea
                WHERE
                    tea.accessTool = :tool AND
                    (tea.accessDate >= :start_date AND tea.accessDate <= :end_date) AND
                    tea.cId = :course';

        $params = [
            'tool' => 'plugin_'.$this->get_name(),
            'start_date' => $embed->getDisplayStartDate(),
            'end_date' => $embed->getDisplayEndDate(),
            'course' => $embed->getCourse(),
        ];

        if ($embed->getSession()) {
            $dql .= ' AND tea.accessSessionId = :session ';

            $params['session'] = $embed->getSession();
        }

        $count = Database::getManager()
            ->createQuery($dql)
            ->setParameters($params)
            ->getSingleScalarResult();

        return $count;
    }

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

    private function deleteCourseToolLinks()
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloCourseBundle:CTool t WHERE t.category = :category AND t.link LIKE :link')
            ->execute(['category' => 'plugin', 'link' => 'embedregistry/start.php%']);
    }
}
