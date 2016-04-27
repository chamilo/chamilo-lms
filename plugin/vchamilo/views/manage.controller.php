<?php

require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';

$table = Database::get_main_table('vchamilo');

if (!defined('CHAMILO_INTERNAL')) {
    die('You cannot use this script this way');
}

$vidlist = isset($_REQUEST['vids']) ? implode("','", array_map('intval', $_REQUEST['vids'])) : '';

if ($action == 'newinstance' || $action == 'instance') {
    $registeronly = $_REQUEST['registeronly'];
    vchamilo_redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/editinstance.php?registeronly='.$registeronly);
}

if ($action == 'editinstance' || $action == 'updateinstance') {
    $vid = $_REQUEST['vid'];
    vchamilo_redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/editinstance.php?vid='.$vid);
}

if ($action == 'deleteinstances' || $action == 'disableinstances') {
    if (!empty($vidlist)) {
        Display::addFlash(Display::return_message("Disabling instance"));
        // Make it not visible.

        $sql = "UPDATE $table SET visible = 0 WHERE id IN ('$vidlist')";
        Database::query($sql);
    }
    vchamilo_redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php');
}
if ($action == 'enableinstances') {
    if (!empty($vidlist)) {
        Display::addFlash(Display::return_message("Enabling instance"));
        $sql = " UPDATE $table SET visible = 1 WHERE id IN ('$vidlist') ";
        Database::query($sql);
    }
    vchamilo_redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php');
}

if ($action == 'fulldeleteinstances') {

    $todelete = [];
    // Removes everything.
    if (empty($automation)) {
        if (!empty($vidlist)) {
            $todelete = Database::select('*', 'vchamilo', array('where' => array("id IN ('$vidlist')" => array())));
        }
    } else {
        $todelete = Database::select('*', 'vchamilo', array('where' => array("root_web = '{$n->root_web}' " => array())));
    }
    if ($todelete) {
        foreach ($todelete as $fooid => $instance) {
            $slug = $instance['slug'];

            Display::addFlash(Display::return_message("Removing instance: ".$instance['root_web']));

            vchamilo_drop_databases($instance);

            // Remove all files and eventual symlinks
            $absalternatecourse = vchamilo_get_config('vchamilo', 'course_real_root');
            $coursedir = $absalternatecourse.$slug;

            Display::addFlash(Display::return_message("Deleting $coursedir"));

            if ($absalternatehome = vchamilo_get_config('vchamilo', 'home_real_root')) {
                $homedir = $absalternatehome.'/'.$slug;

                Display::addFlash(Display::return_message("Deleting $homedir"));
                removeDir($homedir);
            }

            // delete archive
            if ($absalternatearchive = vchamilo_get_config('vchamilo', 'archive_real_root')) {
                $archivedir = $absalternatearchive.'/'.$slug;

                Display::addFlash(Display::return_message("Deleting $archivedir"));
                removeDir($archivedir);
            }

            $sql = "DELETE FROM {$table} WHERE id = {$instance->id}";
            Database::query($sql);
        }
    }
}

if ($action == 'snapshotinstance') {
    $interbreadcrumb[] = array('url' => 'manage.php', 'name' => get_lang('VChamilo'));

    $vid = isset($_REQUEST['vid']) ? $_REQUEST['vid'] : '';
    if ($vid) {
        $vhosts = Database::select('*', 'vchamilo', array('where' => array('id = ?' => $vid)));
        $vhost = (object)array_pop($vhosts);
    } else {
        $vhost = (object)$_configuration;
        $vhost->slug = vchamilo_get_slug_from_url($vhost->root_web);
        $vhost->id = 0;
    }

    // Parsing url for building the template name.
    $wwwroot = $vhost->root_web;
    $vchamilostep = isset($_REQUEST['step']) ? $_REQUEST['step'] : '';

    // Make template directory (files and SQL).
    $separator = DIRECTORY_SEPARATOR;

    $backupDir = $_configuration['root_sys'].'plugin'.$separator.'vchamilo'.$separator.'templates'.$separator.$vhost->slug.$separator;

    $absolute_datadir = $backupDir.'data';
    $absolute_sqldir = $backupDir.'dump.sql';

    if (!is_dir($backupDir)) {
        Display::addFlash(
            Display::return_message('Directory created: '.$backupDir)
        );
        mkdir($backupDir, 0777, true);
    }

    if ($vchamilostep == 0) {
        // Create directories, if necessary.
        if (!is_dir($absolute_datadir)) {
            mkdir($absolute_datadir, 0777, true);
            //mkdir($absolute_datadir.'/archive', 0777, true);
            mkdir($absolute_datadir.'/home', 0777, true);
        }

        if (empty($fullautomation)) {
            $actionurl = $_configuration['root_web'].'/plugin/vchamilo/views/manage.php';
            $content = '<form name"single" action="'.$actionurl.'">';
            $content .= '<input type="hidden" name="what" value="snapshotinstance" />';
            $content .= '<input type="hidden" name="vid" value="'.$vhost->id.'" />';
            $content .= '<input type="hidden" name="step" value="1" />';
            $content .= '<input type="submit" class="btn btn-primary"  name="go_btn" value="'.$plugininstance->get_lang('continue').'" />';
            $content .= '</form>';
            $content .= '</div>';

            $tpl = new Template(get_lang('Snapshot'), true, true, false, true, false);
            $tpl->assign('actions', '');
            $tpl->assign('message', '<h4>'.$plugininstance->get_lang('vchamilosnapshot1').'</h4>');
            $tpl->assign('content', $content);
            $tpl->display_one_col_template();

            die;
        } else {
            // continue next step
            $vchamilostep = 1;
        }
    }

    if ($vchamilostep >= 1) {
        if ($wwwroot == $_configuration['root_web']) {
            // Make fake Vchamilo record.
            $vchamilo = vchamilo_make_this();
            $coursePath = api_get_path(SYS_COURSE_PATH);
            $homePath = api_get_path(SYS_HOME_PATH);
            $archivePath = api_get_path(SYS_ARCHIVE_PATH);
        } else {
            // Get Vchamilo known record.
            $vchamilo = Database::select('*', 'vchamilo', array('where' => array('root_web = ?' => array($wwwroot))), 'first');
            $vchamilo = (object) $vchamilo;
            $coursePath = vchamilo_get_config('vchamilo', 'course_real_root');
            $homePath = vchamilo_get_config('vchamilo', 'home_real_root');
            $archivePath = vchamilo_get_config('vchamilo', 'archive_real_root');

            $coursePath = $coursePath.'/'.$vchamilo->slug;
            $homePath = $homePath.'/'.$vchamilo->slug;
            $archivePath = $archivePath.'/'.$vchamilo->slug;
        }

        $content = '';
        if ($vchamilostep == 1) {
            // Auto dump the databases in a master template folder.
            // this will create three files : dump.sql
            $result = vchamilo_dump_databases($vchamilo, $absolute_sqldir);

            if (empty($fullautomation)) {
                if (!$result) {
                    $actionurl = $_configuration['root_web'].'/plugin/vchamilo/views/manage.php';
                    $content .= '<p><form name"single" action="'.$actionurl.'">';
                    $content .= '<input type="submit" name="go_btn" value="'.$plugininstance->get_lang('cancel').'" />';
                    $content .= '</form></p>';
                } else {
                    $actionurl = $_configuration['root_web'].'/plugin/vchamilo/views/manage.php';

                    $message = $plugininstance->get_lang('vchamilosnapshot2');

                    Display::addFlash(
                        Display::return_message('Database file created: '.$absolute_sqldir)
                    );

                    $content .= '<form name"single" action="'.$actionurl.'">';
                    $content .= '<input type="hidden" name="what" value="snapshotinstance" />';
                    $content .= '<input type="hidden" name="vid" value="'.$vhost->id.'" />';
                    $content .= '<input type="hidden" name="step" value="2" />';
                    $content .= '<input class="btn btn-primary"  type="submit" name="go_btn" value="'.$plugininstance->get_lang('continue').'" />';
                    $content .= '</form>';
                }

                $tpl = new Template(get_lang('Snapshot'), true, true, false, true, false);
                $tpl->assign('actions', '');
                $tpl->assign('message', '<h4>'.$message.'</h4>');
                $tpl->assign('content', $content);
                $tpl->display_one_col_template();

                die;
            }
        }

        Display::addFlash(Display::return_message("Copying from '$homePath' to '{$absolute_datadir}/home' "));
        copyDirTo($homePath, $absolute_datadir.'/home/', false);

        Display::addFlash(Display::return_message("Copying from '$coursePath' to '$absolute_datadir/courses' "));
        copyDirTo($coursePath, $absolute_datadir.'/courses/', false);
        /*Display::addFlash(Display::return_message("Copying from $archivePath to {$absolute_datadir}/archive "));
        copyDirTo($varchivepath, $absolute_datadir.'/archive', false);*/

        // Store original hostname and some config info for further database or filestore replacements.
        $FILE = fopen($backupDir.$separator.'manifest.php', 'w');
        fwrite($FILE, '<'.'?php ');
        fwrite($FILE, "\$templatewwwroot = '".$wwwroot."';\n");
        //fwrite($FILE, "\$templatevdbprefix = '".$vhost->table_prefix."';\n ");
        //fwrite($FILE, "\$coursefolder = '".$vhost->course_folder."';\n ");
        fwrite($FILE, '?'.'>');
        fclose($FILE);

        // Every step was SUCCESS.
        if (empty($fullautomation)) {
            Display::addFlash(Display::return_message($plugininstance->get_lang('successfinishedcapture'), 'success'));

            if (empty($vid)) {
                $template = vchamilo_get_config('vchamilo', 'default_template');
                if (empty($template)) {
                    Display::addFlash(Display::return_message('Set default template as <b>'.$vhost->slug.'</b>', 'success', false));
                    $params = [
                        'subkey' => 'vchamilo',
                        'title' => 'default_template',
                        'type' => 'setting',
                        'category' => 'Plugins',
                        'variable' => 'vchamilo_default_template',
                        'selected_value' => $vhost->slug,
                        'access_url_changeable' => 0
                    ];
                    api_set_setting_simple($params);
                } else {
                    Display::addFlash(Display::return_message('Default template is: <b>'.$vhost->slug.'</b>', 'success', false));
                }
            }

            $actionurl = $_configuration['root_web'].'/plugin/vchamilo/views/manage.php';
            $content .= '<form name"single" action="'.$actionurl.'">';
            $content .= '<input class="btn btn-primary" type="submit" name="go_btn" value="'.$plugininstance->get_lang('backtoindex').'" />';
            $content .= '</form>';

            $tpl = new Template(get_lang('Snapshot'), true, true, false, true, false);
            $tpl->assign('actions', '');
            $tpl->assign('message', $plugininstance->get_lang('vchamilosnapshot3'));
            $tpl->assign('content', $content);
            $tpl->display_one_col_template();

            die;
        }
    }
}

if ($action == 'clearcache') {
    // Removes cache directory.
    if (empty($automation)) {
        if (array_key_exists('vids', $_REQUEST))  {
            $toclear = Database::select('*', 'vchamilo', array('where' => array("id IN ('$vidlist')" => array())));
        } else {
            $vid = isset($_REQUEST['vid']) ? $_REQUEST['vid'] : 0;
            if ($vid) {
                $vhosts = Database::select('*', 'vchamilo', array('where' => array('id = ?' => $vid)));
                $vhost = (object)array_pop($vhosts);
                $toclear[$vhost->id] = $vhost;
            } else {
                $toclear[0] = (object)$_configuration;
            }
        }
    } else {
        $toclear = Database::select(
            '*',
            'vchamilo',
            array('where' => array("root_web = '{$n->root_web}' " => array()))
        );
    }

    foreach ($toclear as $fooid => $instance) {
        if ($fooid == 0) {
            $templatepath = api_get_path(SYS_ARCHIVE_PATH).'twig';
            Display::addFlash(Display::return_message("Deleting master cache $templatepath \n"));
            removeDir($templatepath);
        } else {
            $coursePath = vchamilo_get_config('vchamilo', 'course_real_root');
            $homePath = vchamilo_get_config('vchamilo', 'home_real_root');
            $archivePath = vchamilo_get_config('vchamilo', 'archive_real_root');

            // Get instance archive
            $archivepath = api_get_path(SYS_ARCHIVE_PATH, (array)$instance);
            $templatepath = $archivePath.'/'.$instance['slug'].'/twig';
            Display::addFlash(Display::return_message("Deleting cache $templatepath \n"));
            removeDir($templatepath);
        }
    }
}

if ($action == 'setconfigvalue') {
    $select = '<select name="preset" onchange="setpreset(this.form, this)">';
    $settings = api_get_settings();
    foreach ($settings as $setting) {
        $select .= '<option name="'.$setting['variable'].'/'.$setting['subkey'].'">'.
            $setting['variable'].' - '.$setting['subkey'].
        '</option>';
    }
    $select .= '</select>';

    if (empty($vidlist)) {
        api_not_allowed(true, 'No virtual chamilo selected');
    }

    Display::display_header();
    echo '<h2>'.$plugininstance->get_lang('sendconfigvalue').'</h2>';
    echo '<form name="setconfigform">';
    echo '<input type="hidden" name="vidlist" value="'.$vidlist.'" />';
    echo '<input type="hidden" name="confirm" value="1" />';
    echo '<table>';
    echo '<tr><td>'.get_lang('variable').'</td><td>'.get_lang('subkey').'</td></tr>';
    echo '<tr><td><input type="text" name="variable" value="" size="30" /></td>';
    echo '<td><input type="text" name="subkey" value="" size="30" /></td></tr>';
    echo '<tr><td colspan="2">'.$select.'</td></tr>';
    echo '<tr><td colspan="2">';
    echo '<input class="btn btn-primary" type="submit" name="go_btn" value="'.$plugininstance->get_lang('distributevalue').'"</td></tr>';
    echo '</table>';
    echo '</form>';
    Display::display_footer();
    exit;
}
