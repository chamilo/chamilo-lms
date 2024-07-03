<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\TopLinks\TopLink;
use Chamilo\PluginBundle\Entity\TopLinks\TopLinkRelTool;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Class TopLinksPlugin.
 */
class TopLinksPlugin extends Plugin implements HookPluginInterface
{
    /**
     * TopLinksPlugin constructor.
     */
    protected function __construct()
    {
        $settings = [
        ];

        parent::__construct(
            '0.1',
            'Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>',
            $settings
        );
    }

    /**
     * @return \TopLinksPlugin
     */
    public static function create(): TopLinksPlugin
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminUrl()
    {
        $webPath = api_get_path(WEB_PLUGIN_PATH).$this->get_name();

        return "$webPath/admin.php";
    }

    public function addToolInCourse(int $courseId, TopLink $link)
    {
        // The $link param is set to "../plugin" as a hack to link correctly to the plugin URL in course tool.
        // Otherwise, the link en the course tool will link to "/main/" URL.
        $tool = $this->createLinkToCourseTool(
            $link->getTitle(),
            $courseId,
            'external_link.png',
            '../plugin/toplinks/start.php?'.http_build_query(['link' => $link->getId()]),
            0,
            'authoring'
        );

        if (null === $tool) {
            return;
        }

        $tool->setTarget($link->getTarget());

        $link->addTool($tool);

        $em = Database::getManager();
        $em->persist($link);
        $em->flush();
    }

    public function install()
    {
        $em = Database::getManager();
        $schemaManager = $em->getConnection()->getSchemaManager();

        $tableReferences = [
            'toplinks_link' => $em->getClassMetadata(TopLink::class),
            'toplinks_link_rel_tool' => $em->getClassMetadata(TopLinkRelTool::class),
        ];

        $tablesExists = $schemaManager->tablesExist(array_keys($tableReferences));

        if ($tablesExists) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(array_values($tableReferences));

        $this->installHook();
    }

    public function installHook(): int
    {
        $createCourseObserver = TopLinksCreateCourseHookObserver::create();
        HookCreateCourse::create()->attach($createCourseObserver);

        return 1;
    }

    public function uninstall()
    {
        $em = Database::getManager();

        $tableReferences = [
            'toplinks_link' => $em->getClassMetadata(TopLink::class),
            'toplinks_link_rel_tool' => $em->getClassMetadata(TopLinkRelTool::class),
        ];

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(array_values($tableReferences));

        $this->uninstallHook();

        $this->deleteCourseTools();
    }

    public function uninstallHook(): int
    {
        $createCourseObserver = TopLinksCreateCourseHookObserver::create();
        HookCreateCourse::create()->detach($createCourseObserver);

        return 1;
    }

    private function deleteCourseTools()
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloCourseBundle:CTool t WHERE t.category = :category AND t.link LIKE :link')
            ->execute(['category' => 'authoring', 'link' => '../plugin/toplinks/start.php%']);
    }
}
