<?php

require_once(api_get_path(LIBRARY_PATH).'fileManage.lib.php');

$table = Database::get_main_table('vchamilo');

if (!defined('CHAMILO_INTERNAL')) {
    die('You cannot use this script this way');
}

if ($action == 'newinstance' || $action == 'instance') {
    $registeronly = $_REQUEST['registeronly'];
    vchamilo_redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/editinstance.php?registeronly='.$registeronly);
}

if ($action == 'editinstance') {
    $vid = $_REQUEST['vid'];
    vchamilo_redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/editinstance.php?vid='.$vid);
}

if ($action == 'deleteinstances' || $action == 'disableinstances') {

    ctrace("Disabling instance");
    // Make it not visible.
    $vidlist = implode("','", $_REQUEST['vids']);
    $sql = "
        UPDATE 
            {$table}
        SET
            visible = 0
        WHERE
            id IN ('$vidlist')
    ";
    Database::query($sql);
    vchamilo_redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php');
}
if ($action == 'enableinstances') {

    ctrace("Enabling instance");
    $vidlist = implode("','", $_REQUEST['vids']);
    $sql = "
        UPDATE 
            {$table}
        SET
            visible = 1
        WHERE
            id IN ('$vidlist')
    ";
    Database::query($sql);
    vchamilo_redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php');
}
if ($action == 'fulldeleteinstances') {

    ctrace("Destroying instance");
    // Removes everything.
    if (empty($automation)) {
        $vidlist = implode("','", $_REQUEST['vids']);
        $todelete = Database::select('*', 'vchamilo', array('where' => array("id IN ('$vidlist')" => array())));
    } else {
        $todelete = Database::select('*', 'vchamilo', array('where' => array("root_web = '{$n->root_web}' " => array())));
    }

    foreach ($todelete as $fooid => $instance) {

        echo "<pre>";
        echo ("Dropping instance databases \n");
        vchamilo_drop_databases($instance);

        // Remove all files and eventual symlinks

        $absalternatecourse = vchamilo_get_config('vchamilo', 'course_real_root');
        if (!empty($absalternatecourse)){
            // this is the relocated case
            $coursedir = str_replace('//', '/', $absalternatecourse.'/'.$instance->course_folder);
        } else {
            // this is the standard local case
            $coursedir = api_get_path(SYS_PATH).$instance->course_folder;
        }
        $standardlocation = str_replace('//', '/', $_configuration['root_sys'].'/'.$instance->course_folder); // where it should be

        echo ("Deleting $coursedir \n");
        removeDir($coursedir);
        if (is_link($standardlocation)) {
            unlink($standardlocation);
        }

        preg_match('#https?://([^\.]+)#', $instance->root_web, $matches);
        $home_folder = $matches[1];
        $archive_folder = $matches[1]; // prepare it now

        if ($absalternatehome = vchamilo_get_config('vchamilo', 'home_real_root')){
            $homedir = str_replace('//', '/', $absalternatehome.'/'.$home_folder);
        } else {
            $homedir = api_get_path(SYS_PATH).'home/'.$home_folder;
        }
        $standardlocation = $_configuration['root_sys'].'home/'.$home_folder; // where it should be

        echo ("Deleting $homedir \n");
        removeDir($homedir);
        if (is_link($standardlocation)) {
            unlink($standardlocation);
        }

        // delete archive
        if($absalternatearchive = vchamilo_get_config('vchamilo', 'archive_real_root')){
            $archivedir = str_replace('//', '/', $absalternatearchive.'/'.$archive_folder);
        } else {
            $archivedir = $_configuration['root_sys'].'archive/'.$archive_folder;
        }
        $standardlocation = $_configuration['root_sys'].'archive/'.$archive_folder; // where it should be

        echo ("Deleting $archivedir \n");
        removeDir($archivedir);
        if (is_link($standardlocation)) {
            unlink($standardlocation);
        }
        echo '</pre>';

        echo ("Removing vchamilo record \n");
        $sql = "
            DELETE FROM
                {$table}
            WHERE
                id = {$instance->id}
        ";
        Database::query($sql);

    }

    // vchamilo_redirect($_configuration['root_web'].'/plugin/vchamilo/views/manage.php');
}
if ($action == 'snapshotinstance') {

    $vid = $_REQUEST['vid'];
    if ($vid) {
        $vhosts = Database::select('*', 'vchamilo', array('where' => array('id = ?' => $vid)));
        $vhost = (object)array_pop($vhosts);
    } else {
        $vhost = (object)$_configuration;
    }

    // Parsing url for building the template name.
    $wwwroot    = $vhost->root_web;
    $vchamilostep    = $_REQUEST['step'];
    preg_match('#https?://([^/]+)#', $wwwroot, $matches);
    $hostname = $matches[1];

    // Make template directory (files and SQL).
    $separator    =    DIRECTORY_SEPARATOR;
    $templatefoldername    =    'plugin'.$separator.'vchamilo'.$separator.'templates';
    $relative_datadir    =    $templatefoldername.$separator.$hostname.'_vchamilodata';
    $absolute_datadir    =    $_configuration['root_sys'].$relative_datadir;
    $relative_sqldir     =    $templatefoldername.$separator.$hostname.'_sql';
    $absolute_sqldir     =    $_configuration['root_sys'].$separator.$relative_sqldir;
//    $absolute_templatesdir = api_get_path(SYS_PATH, CHAMILO_ARCHIVE_PATH).$separator.$templatefoldername; // can be problematic as self containing
    $absolute_templatesdir = $_configuration['root_sys'].$templatefoldername;

    if (preg_match('/ /', $absolute_sqldir)){
        $erroritem = new StdClass();
        $erroritem->message = $plugininstance->get_lang('errorspacesinpath');
        vchamilo_print_error(array($erroritem));
    }

    if (!is_dir($absolute_templatesdir)){
        mkdir($absolute_templatesdir, 0777, true);
    }

    if ($vchamilostep == 0) {
        // Create directories, if necessary.
        if (!is_dir($absolute_datadir)){
            mkdir($absolute_datadir, 0777, true);
            mkdir($absolute_datadir.'/archive', 0777, true);
            mkdir($absolute_datadir.'/home', 0777, true);
        }
        if (!is_dir($absolute_sqldir)) {
            mkdir($absolute_sqldir, 0777, true);
        }
        if (empty($fullautomation)) {
            $actionurl = $_configuration['root_web'].'/plugin/vchamilo/views/manage.php';
            $content .= '<form name"single" action="'.$actionurl.'">';
            $content .= '<input type="hidden" name="what" value="snapshotinstance" />';
            $content .= '<input type="hidden" name="vid" value="'.$vhost->id.'" />';
            $content .= '<input type="hidden" name="step" value="1" />';
            $content .= '<input type="submit" name="go_btn" value="'.$plugininstance->get_lang('continue').'" />';
            $content .= '</form>';
            $content .= '</div>';
    
            $tpl = new Template($tool_name, true, true, false, true, false);
            $tpl->assign('actions', '');
            $tpl->assign('message', $plugininstance->get_lang('vchamilosnapshot1'));
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
            $vcoursepath = api_get_path(SYS_COURSE_PATH);
            $vhomepath = api_get_path(SYS_HOME_PATH);
            $varchivepath = api_get_path(SYS_ARCHIVE_PATH);
        } else {
            // Get Vchamilo known record.
            $vchamilos = Database::select('*', 'vchamilo', array('where' => array('root_web = ?' => array($wwwroot))));
            $vchamilo = (object)array_pop($vchamilos);
            $vcoursepath = api_get_path(SYS_COURSE_PATH, (array)$vchamilo);
            $vhomepath = api_get_path(SYS_HOME_PATH, (array)$vchamilo);
            $varchivepath = api_get_path(SYS_ARCHIVE_PATH, (array)$vchamilo);
        }

        if ($vchamilostep == 1) {
            // Auto dump the databases in a master template folder.
            // this will create three files : chamilo_master_main.sql, chamilo_master_statistics.sql, chamilo_master_user_personal.sql
            $errors = vchamilo_dump_databases($vchamilo, $absolute_sqldir.$separator.'chamilo_master');

            if (empty($fullautomation)) {
                if(!empty($errors)) {
                    $actionurl = $_configuration['root_web'].'/plugin/vchamilo/views/manage.php';

                    $message = vchamilo_print_error($errors, true);

                    $content .= '<p><form name"single" action="'.$actionurl.'">';
                    $content .= '<input type="submit" name="go_btn" value="'.$plugininstance->get_lang('cancel').'" />';
                    $content .= '</form></p>';
                } else {
                    $actionurl = $_configuration['root_web'].'/plugin/vchamilo/views/manage.php';

                    $message = $plugininstance->get_lang('vchamilosnapshot2');

                    $content .= '<form name"single" action="'.$actionurl.'">';
                    $content .= '<input type="hidden" name="what" value="snapshotinstance" />';
                    $content .= '<input type="hidden" name="vid" value="'.$vhost->id.'" />';
                    $content .= '<input type="hidden" name="step" value="2" />';
                    $content .= '<input type="submit" name="go_btn" value="'.$plugininstance->get_lang('continue').'" />';
                    $content .= '</form>';
                }
    
                $tpl = new Template($tool_name, true, true, false, true, false);
                $tpl->assign('actions', '');
                $tpl->assign('message', $message);
                $tpl->assign('content', $content);
                $tpl->display_one_col_template();

                die;
            }
        }

    // end of process

        // copy chamilo data dirs and protect against copy recursion.
        /*
        echo "<pre>";
        echo "copyDirTo($vcoursepath, $absolute_datadir, false);
        copyDirTo($varchivepath, $absolute_datadir.'/archive, false);
        copyDirTo($vhomepath, $absolute_datadir.'/home', false);";
        echo "</pre>";
        */

        echo "<pre>";
        echo ("Copying from $vcoursepath to $absolute_datadir \n");
        copyDirTo($vcoursepath, $absolute_datadir, false);
        echo ("Copying from $varchivepath to {$absolute_datadir}/archive \n");
        copyDirTo($varchivepath, $absolute_datadir.'/archive', false);
        echo ("Copying from $vhomepath to {$absolute_datadir}/home \n");
        copyDirTo($vhomepath, $absolute_datadir.'/home', false);
        echo "</pre>";

        // Store original hostname and some config info for further database or filestore replacements.
        $FILE = fopen($absolute_sqldir.$separator.'manifest.php', 'w');
        fwrite($FILE, '<'.'?php ');
        fwrite($FILE, "\$templatewwwroot = '".$wwwroot."';\n");
        fwrite($FILE, "\$templatevdbprefix = '".$vhost->table_prefix."';\n ");
        fwrite($FILE, "\$coursefolder = '".$vhost->course_folder."';\n ");
        fwrite($FILE, '?'.'>');
        fclose($FILE);

        // Every step was SUCCESS.
        if (empty($fullautomation)){
            $message_object->message = $plugininstance->get_lang('successfinishedcapture');
            $message_object->style = 'notifysuccess';

            // Save confirm message before redirection.
            $_SESSION['confirm_message'] = $message_object;
            $actionurl = $_configuration['root_web'].'/plugin/vchamilo/views/manage.php';
            $content .= '<div>'.$message_object->message.'</div>';
            $content .= '<form name"single" action="'.$actionurl.'">';
            $content .= '<input type="submit" name="go_btn" value="'.$plugininstance->get_lang('backtoindex').'" />';
            $content .= '</form>';

            $tpl = new Template($tool_name, true, true, false, true, false);
            $tpl->assign('actions', '');
            $tpl->assign('message', $plugininstance->get_lang('vchamilosnapshot3'));
            $tpl->assign('content', $content);
            $tpl->display_one_col_template();

            die;
        }
    }
}

if ($action == 'clearcache') {

    ctrace("Clearing cache");
    // Removes cache directory.
    if (empty($automation)) {
        if (array_key_exists('vids', $_REQUEST))  {
            $vidlist = implode("','", $_REQUEST['vids']);
            $toclear = Database::select('*', 'vchamilo', array('where' => array("id IN ('$vidlist')" => array())));
        } else {
            $vid = $_REQUEST['vid'];
            if ($vid) {
                $vhosts = Database::select('*', 'vchamilo', array('where' => array('id = ?' => $vid)));
                $vhost = (object)array_pop($vhosts);
                $toclear[$vhost->id] = $vhost;
            } else {
                $toclear[0] = (object)$_configuration;
            }
        }
    } else {
        $toclear = Database::select('*', 'vchamilo', array('where' => array("root_web = '{$n->root_web}' " => array())));
    }

    echo '<pre>';
    foreach ($toclear as $fooid => $instance) {

        if ($fooid == 0) {
            echo ("Clearing master template cache \n");
        } else {
            echo ("Clearing instance template cache \n");
        }

        // Get instance archive
        $archivepath = api_get_path(SYS_ARCHIVE_PATH, TO_SYS, (array)$instance);
        $templatepath = $archivepath.'twig';
        echo ("Deleting $templatepath \n");
        removeDir($templatepath);
    }
    echo '</pre>';
}

if ($action == 'setconfigvalue') {

    if ($_REQUEST['confirm']) {
    }

    $select = '<select name="preset" onchange="setpreset(this.form, this)">';
    $vars = $DB->get_records('settings_current', array(), 'id,variable,subkey', 'variable,subkey');
    foreach($vars as $setting) {
        $select .= '<option name="'.$setting->variable.'/'.$setting->subkey.'">'.$setting->variable.' / '.$setting->subkey.'</option>';
    }
    $select .= '</select>';

    Display::display_header();

    echo '<h2>'.$plugininstance->get_lang('sendconfigvalue').'</h2>';
    echo '<form name="setconfigform">';
    $vidlist = implode("','", $_REQUEST['vids']);
    echo '<input type="hidden" name="vidlist" value="'.$vidlist.'" />';
    echo '<input type="hidden" name="confirm" value="1" />';
    echo '<table>';
    echo '<tr><td>'.get_lang('variable').'</td><td>'.get_lang('subkey').'</td></tr>';
    echo '<tr><td><input type="text" name="variable" value="" size="30" /></td>';
    echo '<td><input type="text" name="subkey" value="" size="30" /></td></tr>';
    echo '<tr><td colspan="2">'.$select.'</td></tr>';
    echo '<tr><td colspan="2"><input type="submit" name="go_btn" value="'.$plugininstance->get_lang('distributevalue').'"</td></tr>';
    echo '</table>';
    echo '</form>';
    Display::display_footer();
    exit;
}
