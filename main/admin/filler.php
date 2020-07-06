<?php
/* For licensing terms, see /license.txt */

/**
 * Index of the admin tools.
 */

// resetting the course id
$cidReset = true;

// including some necessary chamilo files
require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

$nameTools = get_lang('PlatformAdmin');

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => $nameTools];

// setting the name of the tool
$nameTools = get_lang('DataFiller');

$output = [];
if (!empty($_GET['fill'])) {
    switch ($_GET['fill']) {
        case 'users':
            require api_get_path(SYS_TEST_PATH).'datafiller/fill_users.php';
            $output = fill_users();
            break;
        case 'courses':
            require api_get_path(SYS_TEST_PATH).'datafiller/fill_courses.php';
            $output = fill_courses();
            break;
        default:
            break;
    }
}

// Displaying the header
Display::display_header($nameTools);

$result = '';
if (count($output) > 0) {
    $result = '<div class="filler-report">'."\n";
    $result .= '<h3>'.$output[0]['title'].'</h3>'."\n";
    $result .= '<table>';
    foreach ($output as $line) {
        $result .= '<tr>';
        $result .= '<td class="filler-report-data-init">'.$line['line-init'].' </td>
                    <td class="filler-report-data">'.$line['line-info'].'</td>';
        $result .= '</tr>';
    }
    $result .= '</table>';
    $result .= '</div>';
    echo Display::return_message($result, 'normal', false);
}
?>
<div id="datafiller" class="panel panel-default">
    <div class="panel-body">
    <h4><?php
        echo Display::return_icon('bug.png', get_lang('DataFiller'), null, ICON_SIZE_MEDIUM).' '.get_lang('DataFiller');
        ?>
    </h4>
    <div class="description"><?php echo get_lang('ThisSectionIsOnlyVisibleOnSourceInstalls'); ?></div>
    <ul class="fillers">
      <li>
          <a href="filler.php?fill=users">
            <?php
            echo Display::return_icon('user.png', get_lang('FillUsers'), null, ICON_SIZE_SMALL).
                ' '.get_lang('FillUsers');
            ?>
          </a></li>
      <li>
          <a href="filler.php?fill=courses">
          <?php
          echo Display::return_icon('new-course.png', get_lang('FillCourses'), null, ICON_SIZE_SMALL).
              ' '.get_lang('FillCourses');
            ?>
        </a>
      </li>
    </ul>
    </div>
</div>
<?php
/* FOOTER */
Display::display_footer();
