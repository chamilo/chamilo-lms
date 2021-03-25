<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\TopLinks\TopLinkRelTool;

$httpRequest = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

if ('/main/course_home/course_home.php' === $httpRequest->getScriptName() && !api_is_allowed_to_edit()) {
    $course = api_get_course_entity();

    $em = Database::getManager();
    $linkToolRepo = $em->getRepository(TopLinkRelTool::class);

    $linkTools = $linkToolRepo->findInCourse($course);

    $toolIds = [];

    /** @var TopLinkRelTool $linkTool */
    foreach ($linkTools as $linkTool) {
        $toolIds[] = $linkTool->getTool()->getIid();
    }
    ?>
    <script>
        $(function () {
            var ids = JSON.parse('<?php echo json_encode($toolIds) ?>');

            $(ids).each(function (index, id) {
                var $toolA = $('#istooldesc_' + id).parents('.course-tool').parent();

                $toolA.prependTo($toolA.parent());
            });
        });
    </script>
    <?php
}
