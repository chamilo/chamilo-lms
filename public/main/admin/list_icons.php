<?php

/* For licensing terms, see /license.txt */

/**
 * Script to display all icons defined in the *Icon classes
 * @package chamilo.admin
 */
/**
 * Includes and declarations
 */
use Chamilo\CoreBundle\Component\Utils\ActionIcon;
use Chamilo\CoreBundle\Component\Utils\ToolIcon;
use Chamilo\CoreBundle\Component\Utils\ObjectIcon;
use Chamilo\CoreBundle\Component\Utils\StateIcon;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
/**
 * Main code
 */
$classes = [
    ActionIcon::class,
    ToolIcon::class,
    ObjectIcon::class,
    StateIcon::class,
];
Display::display_header('List icons');
echo '<table style="border: 1px solid grey">';
echo '<tr style="border: 1px solid grey"><td>Icon</td><td>CONSTANT NAME</td><td>Icon name in MDI</td></tr>'.PHP_EOL;
foreach ($classes as $class) {
    echo '<tr><td colspan="3">'.$class.'</td></tr>'.PHP_EOL;
    foreach ($class::cases() as $icon) {
        echo '<tr style="border: 1px solid grey"><td style="align:left;">'.Display::getMdiIcon($icon->value, 'ch-tool-icon', 'padding-top: 2px;', ICON_SIZE_MEDIUM).'</td><td>'.$icon->name.'</td><td>'.$icon->value.'</td></tr>'.PHP_EOL;
    }
}
echo '</table>';
Display::display_footer();
