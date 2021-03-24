<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\TopLinks\TopLink;
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
            'enable' => 'boolean',
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
        $this->createLinkToCourseTool(
            $link->getTitle(),
            $courseId,
            null,
            'toplinks/start.php?'.http_build_query(['link' => $link->getId()])
        );
    }

    public function install()
    {
        $em = Database::getManager();
        $schemaManager = $em->getConnection()->getSchemaManager();

        $tableReferences = [
            'toplinks_link' => $em->getClassMetadata(TopLink::class),
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
        ];

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(array_values($tableReferences));

        $this->uninstallHook();
    }

    public function uninstallHook(): int
    {
        $createCourseObserver = TopLinksCreateCourseHookObserver::create();
        HookCreateCourse::create()->detach($createCourseObserver);

        return 1;
    }
}
