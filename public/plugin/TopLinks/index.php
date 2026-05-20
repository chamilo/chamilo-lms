<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\TopLinks\Entity\TopLinkRelTool;

$httpRequest = Container::getRequest();

if ('/main/course_home/course_home.php' === $httpRequest->getScriptName()) {
    $course = api_get_course_entity();

    $em = Database::getManager();
    $linkToolRepo = $em->getRepository(TopLinkRelTool::class);
    $plugin = TopLinksPlugin::create();

    $linkTools = $linkToolRepo->findInCourse($course);

    $toolIds = [];

    /** @var TopLinkRelTool $linkTool */
    foreach ($linkTools as $linkTool) {
        $icon = $linkTool->getLink()->getIcon();

        $toolIds[] = [
            'id' => $linkTool->getTool()->getIid(),
            'img' => $icon
                ? $plugin->getIconUrl($icon)
                : null,
        ];
    } ?>
    <script>
        $(function () {
            var ids = JSON.parse('<?php echo json_encode($toolIds); ?>');

            $(ids).each(function (index, iconTool) {
                var $toolA = $('#tooldesc_' + iconTool.id);
                var $toolImg = $toolA.find('img#toolimage_' + iconTool.id);

                if (iconTool.img) {
                    $toolImg.prop('src', iconTool.img).data('forced-src', iconTool.img);
                }

                var $block = $toolA.parents('.course-tool').parent();

                $block.prependTo($block.parent());
            });
        });
    </script>
    <?php
}
