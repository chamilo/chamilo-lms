<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PluginBundle\Entity\EmbedRegistry\Embed;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class EmbedRegistryPlugin.
 */
class EmbedRegistryPlugin extends Plugin
{
    const SETTING_ENABLED = 'tool_enabled';
    const SETTING_TITLE = 'tool_title';
    const SETTING_EXTERNAL_URL = 'external_url';
    const TBL_EMBED = 'plugin_embed_registry_embed';

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

    public function install()
    {
        $entityPath = $this->getEntityPath();

        if (!is_dir($entityPath)) {
            if (!is_writable(dirname($entityPath))) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('ErrorCreatingDir').' '.$entityPath,
                        'error'
                    )
                );

                return false;
            }

            mkdir($entityPath, api_get_permissions_for_new_directories());
        }

        $fs = new Filesystem();
        $fs->mirror(__DIR__.'/Entity/', $entityPath, null, ['override']);

        $this->createPluginTables();
    }

    public function uninstall()
    {
        $entityPath = $this->getEntityPath();
        $fs = new Filesystem();

        if ($fs->exists($entityPath)) {
            $fs->remove($entityPath);
        }

        Database::query('DROP TABLE IF EXISTS '.self::TBL_EMBED);
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

    private function createPluginTables()
    {
        $connection = Database::getManager()->getConnection();

        if ($connection->getSchemaManager()->tablesExist(self::TBL_EMBED)) {
            return;
        }

        $queries = [
            'CREATE TABLE plugin_embed_registry_embed (id INT AUTO_INCREMENT NOT NULL, c_id INT NOT NULL, session_id INT DEFAULT NULL, title LONGTEXT NOT NULL, display_start_date DATETIME NOT NULL, display_end_date DATETIME NOT NULL, html_code LONGTEXT NOT NULL, INDEX IDX_5236D25991D79BD3 (c_id), INDEX IDX_5236D259613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB',
            'ALTER TABLE plugin_embed_registry_embed ADD CONSTRAINT FK_5236D25991D79BD3 FOREIGN KEY (c_id) REFERENCES course (id)',
            'ALTER TABLE plugin_embed_registry_embed ADD CONSTRAINT FK_5236D259613FECDF FOREIGN KEY (session_id) REFERENCES session (id)',
        ];

        foreach ($queries as $query) {
            Database::query($query);
        }
    }

    /**
     * @return string
     */
    private function getEntityPath()
    {
        return api_get_path(SYS_PATH).'src/Chamilo/PluginBundle/Entity/'.$this->getCamelCaseName();
    }

    private function deleteCourseToolLinks()
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloCourseBundle:CTool t WHERE t.category = :category AND t.link LIKE :link')
            ->execute(['category' => 'plugin', 'link' => 'embedregistry/start.php%']);
    }
}
