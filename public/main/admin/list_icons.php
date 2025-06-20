<?php

/* For licensing terms, see /license.txt */

/**
 * Script to display all icons defined in the *Icon classes
 * @package chamilo.admin
 */
/**
 * Includes and declarations
 */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;

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
    $lastSlashPos = strrpos($class, '\\');
    $shortClass = substr($class, $lastSlashPos + 1);
    echo '<tr><td colspan="3"><u>'.$shortClass.'</u>('.$class.')</td></tr>'.PHP_EOL;
    foreach ($class::cases() as $icon) {
        echo '<tr style="border: 1px solid grey"><td style="align:left;">'.Display::getMdiIcon($icon->value, 'ch-tool-icon', 'padding-top: 2px;', ICON_SIZE_MEDIUM).'</td><td>'.$shortClass.'::'.$icon->name.'</td><td>'.$icon->value.'</td></tr>'.PHP_EOL;
    }
}
echo '</table>';
Display::display_footer();
