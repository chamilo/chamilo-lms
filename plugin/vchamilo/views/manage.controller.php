<?php
/* For licensing terms, see /license.txt */

$table = Database::get_main_table('vchamilo');

if (!defined('CHAMILO_INTERNAL')) {
    exit('You cannot use this script this way');
}

$vidlist = isset($_REQUEST['vids']) ? implode("','", array_map('intval', $_REQUEST['vids'])) : '';

switch ($action) {
    case 'upgrade':
        Virtual::redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/upgrade.php?vid='.$vidlist);
        break;
    case 'import':
        Virtual::redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/import.php');
        break;
    case 'newinstance':
    case 'instance':
        $registeronly = isset($_REQUEST['registeronly']) ? $_REQUEST['registeronly'] : 0;
        Virtual::redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/editinstance.php?registeronly='.$registeronly);
        break;
    case 'editinstance':
    case 'updateinstance':
        $vid = $_REQUEST['vid'];
        Virtual::redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/editinstance.php?vid='.$vid);
        break;
    case 'deleteinstances':
    case 'disableinstances':
        if (!empty($vidlist)) {
            Display::addFlash(Display::return_message("Disabling instance"));
            // Make it not visible.

            $sql = "UPDATE $table SET visible = 0 WHERE id IN ('$vidlist')";
            Database::query($sql);
        }
        Virtual::redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php');
        break;
    case 'enableinstances':
        if (!empty($vidlist)) {
            Display::addFlash(Display::return_message("Enabling instance"));
            $sql = "UPDATE $table SET visible = 1 WHERE id IN ('$vidlist') ";
            Database::query($sql);
        }
        Virtual::redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php');
        break;
    case 'fulldeleteinstances':
        $todelete = [];
        // Removes everything.
        if (empty($automation)) {
            if (!empty($vidlist)) {
                $todelete = Database::select('*', 'vchamilo', ['where' => ["id IN ('$vidlist')" => []]]);
            }
        } else {
            $todelete = Database::select(
                '*',
                'vchamilo',
                ['where' => ["root_web = '{$n->root_web}' " => []]]
            );
        }

        if ($todelete) {
            foreach ($todelete as $fooid => $instance) {
                $slug = $instance['slug'];

                if (!empty($slug)) {
                    // Remove all files and eventual symlinks
                    $absalternatecourse = Virtual::getConfig('vchamilo', 'course_real_root');
                    $coursedir = $absalternatecourse.$slug;

                    Display::addFlash(Display::return_message("Deleting $coursedir"));

                    removeDir($coursedir);

                    if ($absalternatehome = Virtual::getConfig('vchamilo', 'home_real_root')) {
                        $homedir = $absalternatehome.'/'.$slug;

                        Display::addFlash(Display::return_message("Deleting $homedir"));
                        removeDir($homedir);
                    }

                    // delete archive
                    if ($absalternatearchive = Virtual::getConfig('vchamilo', 'archive_real_root')) {
                        $archivedir = $absalternatearchive.'/'.$slug;

                        Display::addFlash(Display::return_message("Deleting $archivedir"));
                        removeDir($archivedir);
                    }

                    // Delete upload
                    if ($dir = Virtual::getConfig('vchamilo', 'upload_real_root')) {
                        $dir = $dir.'/'.$slug;

                        Display::addFlash(Display::return_message("Deleting $dir"));
                        removeDir($dir);
                    }
                }

                $sql = "DELETE FROM {$table} WHERE id = ".$instance['id'];
                Database::query($sql);

                Display::addFlash(Display::return_message("Removing instance: ".$instance['root_web']));

                Virtual::dropDatabase((object) $instance);
            }
        }
        break;
    case 'snapshotinstance':
        $interbreadcrumb[] = ['url' => 'manage.php', 'name' => get_lang('VChamilo')];

        $vid = isset($_REQUEST['vid']) ? $_REQUEST['vid'] : '';
        if ($vid) {
            $vhosts = Database::select('*', 'vchamilo', ['where' => ['id = ?' => $vid]]);
            $vhost = (object) array_pop($vhosts);
        } else {
            $vhost = (object) $_configuration;
            $vhost->slug = Virtual::getSlugFromUrl($vhost->root_web);
            $vhost->id = 0;
        }

        // Parsing url for building the template name.
        $wwwroot = $vhost->root_web;
        $vchamilostep = isset($_REQUEST['step']) ? $_REQUEST['step'] : '';

        // Make template directory (files and SQL).
        $separator = DIRECTORY_SEPARATOR;
        $dirMode = api_get_permissions_for_new_directories();

        $backupDir = api_get_path(SYS_PATH).'plugin'.$separator.'vchamilo'.$separator.'templates'.$separator.$vhost->slug.$separator;

        $absolute_datadir = $backupDir.'data';
        $absolute_sqldir = $backupDir.'dump.sql';

        if (!is_dir($backupDir)) {
            $result = mkdir($backupDir, $dirMode, true);
            if ($result) {
                Display::addFlash(
                    Display::return_message('Directory created: '.$backupDir)
                );
            } else {
                Display::addFlash(
                    Display::return_message("Cannot create directory: $backupDir check the folder permissions", 'error')
                );
            }
        }

        if ($vchamilostep == 0) {
            // Create directories, if necessary.
            if (!is_dir($absolute_datadir)) {
                mkdir($absolute_datadir, $dirMode, true);
                mkdir($absolute_datadir.'/home', $dirMode, true);
            }

            if (empty($fullautomation)) {
                $actionurl = $_configuration['root_web'].'/plugin/vchamilo/views/manage.php';
                $content = '<form name"single" action="'.$actionurl.'">';
                $content .= '<input type="hidden" name="what" value="snapshotinstance" />';
                $content .= '<input type="hidden" name="vid" value="'.$vhost->id.'" />';
                $content .= '<input type="hidden" name="step" value="1" />';
                $content .= '<input type="submit" class="btn btn-primary"  name="go_btn" value="'.$plugin->get_lang('continue').'" />';
                $content .= '</form>';
                $content .= '</div>';

                $tpl = new Template(get_lang('Snapshot'), true, true, false, true, false);
                $tpl->assign('message', '<h4>'.$plugin->get_lang('vchamilosnapshot1').'</h4>');
                $tpl->assign('content', $content);
                $tpl->display_one_col_template();

                exit;
            } else {
                // continue next step
                $vchamilostep = 1;
            }
        }

        if ($vchamilostep >= 1) {
            if ($wwwroot == $_configuration['root_web']) {
                // Make fake Vchamilo record.
                $vchamilo = Virtual::makeThis();
                $coursePath = api_get_path(SYS_COURSE_PATH);
                $homePath = api_get_path(SYS_HOME_PATH);
                $archivePath = api_get_path(SYS_ARCHIVE_PATH);
                $uploadPath = api_get_path(SYS_UPLOAD_PATH);
            } else {
                // Get Vchamilo known record.
                $vchamilo = Database::select('*', 'vchamilo', ['where' => ['root_web = ?' => [$wwwroot]]], 'first');
                $vchamilo = (object) $vchamilo;
                $coursePath = Virtual::getConfig('vchamilo', 'course_real_root');
                $homePath = Virtual::getConfig('vchamilo', 'home_real_root');
                $archivePath = Virtual::getConfig('vchamilo', 'archive_real_root');
                $uploadPath = Virtual::getConfig('vchamilo', 'upload_real_root');

                $coursePath = $coursePath.'/'.$vchamilo->slug;
                $homePath = $homePath.'/'.$vchamilo->slug;
                $archivePath = $archivePath.'/'.$vchamilo->slug;
                $uploadPath = $uploadPath.'/'.$vchamilo->slug;
            }

            $content = '';
            if ($vchamilostep == 1) {
                // Auto dump the databases in a master template folder.
                // this will create three files : dump.sql
                $result = Virtual::backupDatabase($vchamilo, $absolute_sqldir);

                if (empty($fullautomation)) {
                    if (!$result) {
                        $actionurl = $_configuration['root_web'].'/plugin/vchamilo/views/manage.php';
                        $content .= '<p><form name"single" action="'.$actionurl.'">';
                        $content .= '<input type="submit" name="go_btn" value="'.$plugin->get_lang('cancel').'" />';
                        $content .= '</form></p>';
                    } else {
                        $actionurl = $_configuration['root_web'].'/plugin/vchamilo/views/manage.php';

                        $message = $plugin->get_lang('vchamilosnapshot2');

                        Display::addFlash(
                            Display::return_message('Database file created: '.$absolute_sqldir)
                        );

                        $content .= '<form name"single" action="'.$actionurl.'">';
                        $content .= '<input type="hidden" name="what" value="snapshotinstance" />';
                        $content .= '<input type="hidden" name="vid" value="'.$vhost->id.'" />';
                        $content .= '<input type="hidden" name="step" value="2" />';
                        $content .= '<input class="btn btn-primary"  type="submit" name="go_btn" value="'.$plugin->get_lang('continue').'" />';
                        $content .= '</form>';
                    }

                    $tpl = new Template(get_lang('Snapshot'), true, true, false, true, false);
                    $tpl->assign('message', '<h4>'.$message.'</h4>');
                    $tpl->assign('content', $content);
                    $tpl->display_one_col_template();

                    exit;
                }
            }

            Display::addFlash(Display::return_message("Copying from '$homePath' to '{$absolute_datadir}/home' "));
            copyDirTo($homePath, $absolute_datadir.'/home/', false);

            Display::addFlash(Display::return_message("Copying from '$coursePath' to '$absolute_datadir/courses' "));
            copyDirTo($coursePath, $absolute_datadir.'/courses/', false);

            Display::addFlash(Display::return_message("Copying from '$uploadPath' to '$absolute_datadir/upload' "));
            copyDirTo($uploadPath, $absolute_datadir.'/upload/', false);

            // Store original hostname and some config info for further database or filestore replacements.
            $FILE = fopen($backupDir.$separator.'manifest.php', 'w');
            fwrite($FILE, '<'.'?php ');
            fwrite($FILE, "\$templatewwwroot = '".$wwwroot."';\n");
            fwrite($FILE, '?'.'>');
            fclose($FILE);

            // Every step was SUCCESS.
            if (empty($fullautomation)) {
                Display::addFlash(
                    Display::return_message(
                        $plugin->get_lang('successfinishedcapture'),
                        'success'
                    )
                );

                if (empty($vid)) {
                    $template = Virtual::getConfig('vchamilo', 'default_template');
                    if (empty($template)) {
                        Display::addFlash(
                            Display::return_message('Set default template as <b>'.$vhost->slug.'</b>', 'success', false)
                        );
                        $params = [
                            'subkey' => 'vchamilo',
                            'title' => 'default_template',
                            'type' => 'setting',
                            'category' => 'Plugins',
                            'variable' => 'vchamilo_default_template',
                            'selected_value' => $vhost->slug,
                            'access_url_changeable' => 0,
                        ];
                        api_set_setting_simple($params);
                    } else {
                        Display::addFlash(
                            Display::return_message('Default template is: <b>'.$vhost->slug.'</b>', 'success', false)
                        );
                    }
                }

                $actionurl = $_configuration['root_web'].'/plugin/vchamilo/views/manage.php';
                $content .= '<form name"single" action="'.$actionurl.'">';
                $content .= '<input class="btn btn-primary" type="submit" name="go_btn" value="'.$plugin->get_lang('backtoindex').'" />';
                $content .= '</form>';

                $tpl = new Template(get_lang('Snapshot'), true, true, false, true, false);
                $tpl->assign('message', $plugin->get_lang('vchamilosnapshot3'));
                $tpl->assign('content', $content);
                $tpl->display_one_col_template();

                exit;
            }
        }
        break;
    case 'clearcache':
        // Removes cache directory.
        if (empty($automation)) {
            if (array_key_exists('vids', $_REQUEST)) {
                $toclear = Database::select('*', 'vchamilo', ['where' => ["id IN ('$vidlist')" => []]]);
            } else {
                $vid = isset($_REQUEST['vid']) ? $_REQUEST['vid'] : 0;
                if ($vid) {
                    $vhosts = Database::select('*', 'vchamilo', ['where' => ['id = ?' => $vid]]);
                    $vhost = (object) array_pop($vhosts);
                    $toclear[$vhost->id] = $vhost;
                } else {
                    $toclear[0] = (object) $_configuration;
                }
            }
        } else {
            $toclear = Database::select(
                '*',
                'vchamilo',
                ['where' => ["root_web = '{$n->root_web}' " => []]]
            );
        }

        foreach ($toclear as $fooid => $instance) {
            if ($fooid == 0) {
                $templatepath = api_get_path(SYS_ARCHIVE_PATH).'twig';
                Display::addFlash(Display::return_message("Deleting master cache $templatepath \n"));
                removeDir($templatepath);
            } else {
                $coursePath = Virtual::getConfig('vchamilo', 'course_real_root');
                $homePath = Virtual::getConfig('vchamilo', 'home_real_root');
                $archivePath = Virtual::getConfig('vchamilo', 'archive_real_root');
                //$uploadPath = Virtual::getConfig('vchamilo', 'upload_real_root');

                // Get instance archive
                $archivepath = api_get_path(SYS_ARCHIVE_PATH, (array) $instance);
                $templatepath = $archivePath.'/'.$instance['slug'].'/twig';
                Display::addFlash(Display::return_message("Deleting cache $templatepath \n"));
                removeDir($templatepath);
            }
        }
        break;
    case 'setconfigvalue':
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
        echo '<h2>'.$plugin->get_lang('sendconfigvalue').'</h2>';
        echo '<form name="setconfigform">';
        echo '<input type="hidden" name="vidlist" value="'.$vidlist.'" />';
        echo '<input type="hidden" name="confirm" value="1" />';
        echo '<table>';
        echo '<tr><td>'.get_lang('variable').'</td><td>'.get_lang('subkey').'</td></tr>';
        echo '<tr><td><input type="text" name="variable" value="" size="30" /></td>';
        echo '<td><input type="text" name="subkey" value="" size="30" /></td></tr>';
        echo '<tr><td colspan="2">'.$select.'</td></tr>';
        echo '<tr><td colspan="2">';
        echo '<input class="btn btn-primary" type="submit" name="go_btn" value="'.$plugin->get_lang('distributevalue').'"</td></tr>';
        echo '</table>';
        echo '</form>';
        Display::display_footer();
        exit;
        break;
}
