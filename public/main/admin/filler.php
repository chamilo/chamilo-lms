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

$nameTools = get_lang('Administration');

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => $nameTools];

// setting the name of the tool
$nameTools = get_lang('Data filler');

$output = [];
if (!empty($_GET['fill'])) {
    switch ($_GET['fill']) {
        case 'users':
            require __DIR__.'/../../../tests/datafiller/fill_users.php';
            $output = fill_users();
            break;
        case 'courses':
            require __DIR__.'/../../../tests/datafiller/fill_courses.php';
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
    $result .= '<table>';
    foreach ($output as $line) {
        $result .= '<tr>';
        $result .= '<td class="filler-report-data-init">'.$line['line-init'].' </td>
                    <td class="filler-report-data">'.$line['line-info'].'</td>';
        $result .= '</tr>';
    }
    $result .= '</table>';
    $result .= '</div>';
    echo Display::return_message($output[0]['title'], 'normal', false);
    echo $result;
}
?>
<div id="datafiller" class="card">
    <div class="card-body">
    <h4><?php
        echo Display::return_icon('bug.png', get_lang('Data filler'), null, ICON_SIZE_MEDIUM).' '.get_lang('Data filler');
        ?>
    </h4>
    <div class="description"><?php echo get_lang('This section is only visible on installations from source code, not in packaged versions of the platform. It will allow you to quickly populate your platform with test data. Use with care (data is really inserted) and only on development or testing installations.'); ?></div>
    <ul class="fillers">
      <li>
          <a href="filler.php?fill=users">
            <?php
            echo Display::return_icon('user.png', get_lang('Fill users'), null, ICON_SIZE_SMALL).
                ' '.get_lang('Fill users');
            ?>
          </a></li>
      <li>
          <a href="filler.php?fill=courses">
          <?php
          echo Display::return_icon('new-course.png', get_lang('Fill courses'), null, ICON_SIZE_SMALL).
              ' '.get_lang('Fill courses');
            ?>
        </a>
      </li>
    </ul>
    </div>
</div>
<?php
/* FOOTER */
Display::display_footer();
