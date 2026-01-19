<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\SystemTemplate;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession as Session;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Library of the settings.php file.
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @author Guillaume Viguier <guillaume@viguierjust.com>
 *
 * @since Chamilo 1.8.7
 */
define('CSS_UPLOAD_PATH', api_get_path(SYMFONY_SYS_PATH).'var/themes/');

/**
 * This function allows easy activating and inactivating of regions.
 *
 * @author Julio Montoya <gugli100@gmail.com> Beeznest 2012
 */
function handleRegions()
{
    if (isset($_POST['submit_plugins'])) {
        storeRegions();
        // Add event to the system log.
        $user_id = api_get_user_id();
        $category = $_GET['category'];
        Event::addEvent(
            LOG_CONFIGURATION_SETTINGS_CHANGE,
            LOG_CONFIGURATION_SETTINGS_CATEGORY,
            $category,
            api_get_utc_datetime(),
            $user_id
        );
        echo Display::return_message(get_lang('The settings have been stored'), 'confirmation');
    }

    $plugin_obj = new AppPlugin();
    $installed_plugins = $plugin_obj->getInstalledPlugins();

    echo '<form name="plugins" method="post" action="'.api_get_self().'?category='.Security::remove_XSS($_GET['category']).'">';
    echo '<table class="data_table">';
    echo '<tr>';
    echo '<th width="400px">';
    echo get_lang('Plugin');
    echo '</th><th>';
    echo get_lang('Regions');
    echo '</th>';
    echo '</th>';
    echo '</tr>';

    /* We display all the possible plugins and the checkboxes */
    $plugin_region_list = [];
    $my_plugin_list = $plugin_obj->getPluginRegions();
    foreach ($my_plugin_list as $plugin_item) {
        $plugin_region_list[$plugin_item] = $plugin_item;
    }

    // Removing course tool
    unset($plugin_region_list['course_tool_plugin']);

    foreach ($installed_plugins as $pluginName) {
        $plugin_info_file = api_get_path(SYS_PLUGIN_PATH).$pluginName.'/plugin.php';

        if (file_exists($plugin_info_file)) {
            $plugin_info = [];
            require $plugin_info_file;
            if (isset($_GET['name']) && $_GET['name'] === $pluginName) {
                echo '<tr class="row_selected">';
            } else {
                echo '<tr>';
            }
            echo '<td>';
            echo '<h4>'.$plugin_info['title'].' <small>v'.$plugin_info['version'].'</small></h4>';
            echo '<p>'.$plugin_info['comment'].'</p>';
            echo '</td><td>';
            $selected_plugins = $plugin_obj->getAreasByPlugin($pluginName);
            $region_list = [];
            $isAdminPlugin = isset($plugin_info['is_admin_plugin']) && $plugin_info['is_admin_plugin'];
            $isCoursePlugin = isset($plugin_info['is_course_plugin']) && $plugin_info['is_course_plugin'];

            if (!$isAdminPlugin && !$isCoursePlugin) {
                $region_list = $plugin_region_list;
            } else {
                if ($isAdminPlugin) {
                    $region_list['menu_administrator'] = 'menu_administrator';
                }
                if ($isCoursePlugin) {
                    $region_list['course_tool_plugin'] = 'course_tool_plugin';
                }
            }

            echo Display::select(
                'plugin_'.$pluginName.'[]',
                $region_list,
                $selected_plugins,
                ['multiple' => 'multiple', 'style' => 'width:500px'],
                true,
                get_lang('none')
            );
            echo '</td></tr>';
        }
    }
    echo '</table>';
    echo '<br />';
    echo '<button class="btn btn--success" type="submit" name="submit_plugins">'.get_lang('Enable the selected plugins').'</button></form>';
}

function handleExtensions()
{
    echo Display::page_subheader(get_lang('Configure extensions'));
    echo '<a class="btn btn--success" href="configure_extensions.php?display=ppt2lp" role="button">'.get_lang('Chamilo RAPID').'</a>';
}

/**
 * This function allows easy activating and inactivating of plugins.
 *
 * @todo: a similar function needs to be written to activate or inactivate additional tools.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Julio Montoya <gugli100@gmail.com> Beeznest 2012
 */
function handlePlugins()
{
    Session::erase('plugin_data');
    $pluginRepo = Container::getPluginRepository();

    $allPlugins = (new AppPlugin())->read_plugins_from_path();

    // Header
    echo '<div class="mb-4 flex items-center justify-between">';
    echo '<h2 class="text-2xl font-semibold text-gray-90">'.get_lang('Manage plugins').'</h2>';
    echo '<p class="text-gray-50 text-sm">'.get_lang('Install, activate or deactivate plugins easily.').'</p>';
    echo '</div>';

    echo '<table class="w-full border border-gray-25 rounded-lg shadow-md">';
    echo '<thead>';
    echo '<tr class="bg-gray-10 text-left">';
    echo '<th class="p-3 border-b border-gray-25">'.get_lang('Plugin').'</th>';
    echo '<th class="p-3 border-b border-gray-25">'.get_lang('Version').'</th>';
    echo '<th class="p-3 border-b border-gray-25">'.get_lang('Status').'</th>';
    echo '<th class="p-3 border-b border-gray-25 text-center">'.get_lang('Actions').'</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($allPlugins as $pluginName) {
        $pluginInfoFile = api_get_path(SYS_PLUGIN_PATH).$pluginName.'/plugin.php';
        if (!file_exists($pluginInfoFile)) {
            continue;
        }

        $plugin_info = [];
        try {
            require $pluginInfoFile;
        } catch (\Throwable $e) {
            error_log('[plugins] failed to read '.$pluginName.' metadata: '.$e->getMessage());
            $plugin_info = ['title' => $pluginName, 'version' => 'n/a'];
        }

        $plugin = $pluginRepo->findOneByTitle($pluginName);
        $pluginConfiguration = $plugin?->getConfigurationsByAccessUrl(Container::getAccessUrlUtil()->getCurrent());
        $isInstalled = $plugin && $plugin->isInstalled();
        $isEnabled   = $plugin && $pluginConfiguration && $pluginConfiguration->isActive();

        // Status badge
        $statusBadge = $isInstalled
            ? ($isEnabled
                ? '<span class="badge badge--success">'.get_lang('Enabled').'</span>'
                : '<span class="badge badge--warning">'.get_lang('Disabled').'</span>')
            : '<span class="badge badge--default">'.get_lang('Not installed').'</span>';

        echo '<tr class="border-t border-gray-25 hover:bg-gray-15 transition duration-200">';
        echo '<td class="p-3 font-medium">'.htmlspecialchars($plugin_info['title'] ?? $pluginName, ENT_QUOTES).'</td>';
        echo '<td class="p-3">'.htmlspecialchars($plugin_info['version'] ?? '0.0.0', ENT_QUOTES).'</td>';
        echo '<td class="p-3">'.$statusBadge.'</td>';
        echo '<td class="p-3 text-center"><div class="flex justify-center gap-2">';

        if ($isInstalled) {
            $toggleAction = $isEnabled ? 'disable' : 'enable';
            $toggleText   = $isEnabled ? get_lang('Disable') : get_lang('Enable');
            $toggleColor  = $isEnabled ? 'btn--plain' : 'btn--warning';
            $toggleIcon   = $isEnabled ? 'mdi mdi-toggle-switch-off-outline' : 'mdi mdi-toggle-switch-outline';

            echo '<button class="plugin-action btn btn--sm '.$toggleColor.'"
                    data-plugin="'.htmlspecialchars($pluginName, ENT_QUOTES).'" data-action="'.$toggleAction.'">
                    <i class="'.$toggleIcon.'"></i> '.$toggleText.'
                  </button>';

            echo '<button class="plugin-action btn btn--sm btn--danger"
                    data-plugin="'.htmlspecialchars($pluginName, ENT_QUOTES).'" data-action="uninstall">
                    <i class="mdi mdi-trash-can-outline"></i> '.get_lang('Uninstall').'
                  </button>';

            // Show "Configure" only if the plugin is ENABLED and actually has editable settings.
            if ($isEnabled && plugin_has_editable_settings($pluginName)) {
                $configureUrl = '/main/admin/configure_plugin.php?'.http_build_query(['plugin' => $pluginName]);
                echo Display::url(
                    get_lang('Configure'),
                    $configureUrl,
                    ['class' => 'btn btn--info btn--sm']
                );
            }
        } else {
            echo '<button class="plugin-action btn btn--sm btn--success"
                    data-plugin="'.htmlspecialchars($pluginName, ENT_QUOTES).'" data-action="install">
                    <i class="mdi mdi-download"></i> '.get_lang('Install').'
                  </button>';
        }

        echo '</div></td></tr>';
    }

    echo '</tbody></table>';

    echo '<div id="page-loader" class="hidden fixed inset-0 bg-black/30 z-40">
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="text-white text-sm text-center">
                <i class="mdi mdi-loading mdi-spin text-3xl block mb-2"></i>
                '.get_lang('Processing').'…
              </div>
            </div>
          </div>';

    echo '<script>
(function($){
  function showToast(message, type) {
    var bg = type === "success" ? "bg-green-600" : (type === "warning" ? "bg-yellow-600" : "bg-red-600");
    var $toast = $("<div/>", {
      class: "fixed top-4 right-4 z-50 text-white px-4 py-3 rounded shadow " + bg,
      text: message
    }).appendTo("body");
    setTimeout(function(){ $toast.fadeOut(300, function(){ $(this).remove(); }); }, 3500);
  }
  function actionLabel(a) {
    switch(a){
      case "install": return "'.get_lang('Installing').'";
      case "uninstall": return "'.get_lang('Uninstalling').'";
      case "enable": return "'.get_lang('Enabling').'";
      case "disable": return "'.get_lang('Disabling').'";
      default: return "'.get_lang('Processing').'";
    }
  }
  function showPageLoader(show){ $("#page-loader").toggleClass("hidden", !show); }

  $(document).ready(function () {
    $(".plugin-action").on("click", function () {
      var $btn = $(this);
      if ($btn.data("busy")) return;

      var pluginName = $btn.data("plugin");
      var action = $btn.data("action");
      var originalHtml = $btn.html();

      $btn.data("busy", true)
          .attr("aria-busy", "true")
          .addClass("opacity-60 cursor-not-allowed")
          .html(\'<i class="mdi mdi-loading mdi-spin"></i> \' + actionLabel(action) + "...");
      $.ajax({
        type: "POST",
        url: "'.api_get_path(WEB_AJAX_PATH).'plugin.ajax.php",
        data: { a: action, plugin: pluginName },
        dataType: "json",
        timeout: 120000,
        beforeSend: function(){ showToast(actionLabel(action) + "…", "warning"); },
        success: function(data){
          if (data && data.success) {
            showToast("'.get_lang('Done').': " + action.toUpperCase(), "success");
            setTimeout(function(){ location.reload(); }, 500);
          } else {
            var msg = (data && (data.error || data.message)) ? (data.error || data.message) : "'.get_lang('Error').'";
            showToast("'.get_lang('Error').': " + msg, "error");
            $btn.html(originalHtml);
          }
        },
        error: function(xhr){
          var msg = "'.get_lang('Request failed').'";
          try {
            var j = JSON.parse(xhr.responseText);
            if (j && (j.error || j.message)) msg = j.error || j.message;
          } catch(e) {}
          showToast("'.get_lang('Error').': " + msg, "error");
          $btn.html(originalHtml);
        },
        complete: function(){
          $btn.data("busy", false)
              .removeAttr("aria-busy")
              .removeClass("opacity-60 cursor-not-allowed");
        }
      });
    });
  });
})(jQuery);
</script>';
}

/**
 * Determine if a plugin exposes editable settings (excluding legacy enable/active toggles).
 * Used to decide whether the "Configure" button should be shown.
 */
function plugin_has_editable_settings(string $pluginName): bool
{
    static $cache = [];
    if (array_key_exists($pluginName, $cache)) {
        return $cache[$pluginName];
    }

    $has = false;

    try {
        $app  = new AppPlugin();
        $info = $app->getPluginInfo($pluginName, true) ?? [];

        // Collect fields from Plugin object or from 'settings' array
        if (!empty($info['obj']) && $info['obj'] instanceof Plugin) {
            $fields = (array) $info['obj']->getFieldNames();
        } elseif (!empty($info['settings']) && is_array($info['settings'])) {
            $fields = array_keys($info['settings']);
        } else {
            $fields = [];
        }

        // Strip legacy toggles; these do not qualify as "configurable settings".
        // Keep this list broad to cover plugins that added their own toggle names.
        $legacyToggles = [
            'tool_enable',
            'enable_onlyoffice_plugin',
            'enabled',
            'enable',
            'active',
            'is_active',
        ];
        $fields = array_values(array_diff($fields, $legacyToggles));

        // Final decision: at least one real field
        $has = count($fields) > 0;
    } catch (\Throwable $e) {
        // Fail closed: if metadata lookup fails, do not show Configure
        $has = false;
    }

    return $cache[$pluginName] = $has;
}

/**
 * Creates the folder (if needed) and uploads the stylesheet in it.
 *
 * @param array $values  the values of the form
 * @param array $picture the values of the uploaded file
 *
 * @return bool
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 *
 * @version May 2008
 *
 * @since v1.8.5
 */
function uploadStylesheet($values, $picture)
{
    $result = false;
    // Valid name for the stylesheet folder.
    $style_name = api_preg_replace('/[^A-Za-z0-9]/', '', $values['name_stylesheet']);
    if (empty($style_name) || is_array($style_name)) {
        // The name of the uploaded stylesheet doesn't have the expected format
        return $result;
    }
    $cssToUpload = CSS_UPLOAD_PATH;

    // Check if a virtual instance vchamilo is used
    $virtualInstanceTheme = api_get_configuration_value('virtual_css_theme_folder');
    if (!empty($virtualInstanceTheme)) {
        $cssToUpload = $cssToUpload.$virtualInstanceTheme.'/';
    }

    // Create the folder if needed.
    if (!is_dir($cssToUpload.$style_name.'/')) {
        mkdir($cssToUpload.$style_name.'/', api_get_permissions_for_new_directories());
    }

    $info = pathinfo($picture['name']);

    if ('zip' == $info['extension']) {
        // Try to open the file and extract it in the theme.
        $zip = new ZipArchive();
        if ($zip->open($picture['tmp_name'])) {
            // Make sure all files inside the zip are images or css.
            $num_files = $zip->numFiles;
            $valid = true;
            $single_directory = true;
            $invalid_files = [];

            $allowedFiles = getAllowedFileTypes();

            for ($i = 0; $i < $num_files; $i++) {
                $file = $zip->statIndex($i);
                if ('/' != substr($file['name'], -1)) {
                    $path_parts = pathinfo($file['name']);
                    if (!in_array($path_parts['extension'], $allowedFiles)) {
                        $valid = false;
                        $invalid_files[] = $file['name'];
                    }
                }

                if (false === strpos($file['name'], '/')) {
                    $single_directory = false;
                }
            }
            if (!$valid) {
                $error_string = '<ul>';
                foreach ($invalid_files as $invalid_file) {
                    $error_string .= '<li>'.$invalid_file.'</li>';
                }
                $error_string .= '</ul>';
                echo Display::return_message(
                    get_lang('The only accepted extensions in the ZIP file are .jp(e)g, .png, .gif and .css.').$error_string,
                    'error',
                    false
                );
            } else {
                // If the zip does not contain a single directory, extract it.
                if (!$single_directory) {
                    // Extract zip file.
                    $zip->extractTo($cssToUpload.$style_name.'/');
                    $result = true;
                } else {
                    $extraction_path = $cssToUpload.$style_name.'/';
                    $mode = api_get_permissions_for_new_directories();
                    for ($i = 0; $i < $num_files; $i++) {
                        $entry = $zip->getNameIndex($i);
                        if ('/' == substr($entry, -1)) {
                            continue;
                        }

                        $pos_slash = strpos($entry, '/');
                        $entry_without_first_dir = substr($entry, $pos_slash + 1);
                        // If there is still a slash, we need to make sure the directories are created.
                        if (false !== strpos($entry_without_first_dir, '/')) {
                            if (!is_dir($extraction_path.dirname($entry_without_first_dir))) {
                                // Create it.
                                @mkdir($extraction_path.dirname($entry_without_first_dir), $mode, true);
                            }
                        }

                        $fp = $zip->getStream($entry);
                        $ofp = fopen($extraction_path.dirname($entry_without_first_dir).'/'.basename($entry), 'w');

                        while (!feof($fp)) {
                            fwrite($ofp, fread($fp, 8192));
                        }

                        fclose($fp);
                        fclose($ofp);
                    }
                    $result = true;
                }
            }
            $zip->close();
        } else {
            echo Display::return_message(get_lang('Error reading ZIP file').$info['extension'], 'error', false);
        }
    } else {
        // Simply move the file.
        move_uploaded_file($picture['tmp_name'], $cssToUpload.$style_name.'/'.$picture['name']);
        $result = true;
    }

    if ($result) {
        $fs = new Filesystem();
        $fs->mirror(
            CSS_UPLOAD_PATH,
            api_get_path(SYMFONY_SYS_PATH).'var/themes/',
            null,
            ['override' => true]
        );
    }

    return $result;
}

/**
 * Store plugin regions.
 */
function storeRegions(): void
{
    $currentAccessUrl = Container::getAccessUrlUtil()->getCurrent();
    $pluginRepo = Container::getPluginRepository();
    $em = Container::getEntityManager();

    $plugin_obj = new AppPlugin();
    $plugin_list = $plugin_obj->read_plugins_from_path();

    foreach ($plugin_list as $plugin) {
        if (!isset($_POST['plugin_'.$plugin])) {
            continue;
        }

        $areas_to_installed = array_filter(
            $_POST['plugin_'.$plugin] ?? [],
            fn ($region) => !empty($region) && '-1' != $region
        );

        if (empty($areas_to_installed)) {
            continue;
        }

        $entityPlugin = $pluginRepo->getInstalledByName($plugin);

        if (!$entityPlugin) {
            continue;
        }

        $pluginInUrl = $entityPlugin->getOrCreatePluginConfiguration($currentAccessUrl);

        $pluginConfiguration = $pluginInUrl->getConfiguration();
        $pluginConfiguration['regions'] = $areas_to_installed;

        $pluginInUrl->setConfiguration($pluginConfiguration);

        $em->flush();
    }
}

/**
 * This function checks if the given style is a recognize style that exists in the css directory as
 * a standalone directory.
 *
 * @param string $style
 *
 * @return bool True if this style is recognized, false otherwise
 */
function isStyle($style)
{
    $themeList = api_get_themes();

    return in_array($style, array_keys($themeList));
}

/**
 * Search options
 * TODO: support for multiple site. aka $_configuration['access_url'] == 1.
 *
 * @author Marco Villegas <marvil07@gmail.com>
 */
function handleSearch()
{
    global $SettingsStored, $_configuration;

    $search_enabled = api_get_setting('search_enabled');

    $form = new FormValidator(
        'search-options',
        'post',
        api_get_self().'?category=Search'
    );
    $values = api_get_settings_options('search_enabled');
    $form->addElement('header', null, get_lang('Fulltext search'));

    $group = formGenerateElementsGroup($form, $values, 'search_enabled');

    // SearchEnabledComment
    $form->addGroup(
        $group,
        'search_enabled',
        [get_lang('Fulltext search'), get_lang('This feature allows you to index most of the documents uploaded to your portal, then provide a search feature for users.<br />This feature will not index documents that have already been uploaded, so it is important to enable (if wanted) at the beginning of your implementation.<br />Once enabled, a search box will appear in the courses list of every user. Searching for a specific term will bring a list of corresponding documents, exercises or forum topics, filtered depending on the availability of these contents to the user.')],
        null,
        false
    );

    $search_enabled = api_get_setting('search_enabled');

    if ($form->validate()) {
        $formValues = $form->exportValues();
        setConfigurationSettingsInDatabase($formValues, $_configuration['access_url']);
        $search_enabled = $formValues['search_enabled'];
        echo Display::return_message($SettingsStored, 'confirm');
    }
    $specific_fields = get_specific_field_list();

    if ('true' == $search_enabled) {
        $values = api_get_settings_options('search_show_unlinked_results');
        $group = formGenerateElementsGroup(
            $form,
            $values,
            'search_show_unlinked_results'
        );
        $form->addGroup(
            $group,
            'search_show_unlinked_results',
            [
                get_lang('Full-text search: show unlinked results'),
                get_lang('When showing the results of a full-text search, what should be done with the results that are not accessible to the current user?'),
            ],
            null,
            false
        );
        $default_values['search_show_unlinked_results'] = api_get_setting('search_show_unlinked_results');

        $sf_values = [];
        foreach ($specific_fields as $sf) {
            $sf_values[$sf['code']] = $sf['name'];
        }
        $url = Display::div(
            Display::url(
                get_lang('Add a specific search field'),
                'specific_fields.php'
            ),
            ['class' => 'sectioncomment']
        );
        if (empty($sf_values)) {
            $form->addElement('label', [get_lang('Specific Field for prefilter'), $url]);
        } else {
            $form->addElement(
                'select',
                'search_prefilter_prefix',
                [get_lang('Specific Field for prefilter'), $url],
                $sf_values,
                ''
            );
            $default_values['search_prefilter_prefix'] = api_get_setting('search_prefilter_prefix');
        }
    }

    $default_values['search_enabled'] = $search_enabled;

    $form->addButtonSave(get_lang('Save'));
    $form->setDefaults($default_values);

    echo '<div id="search-options-form">';
    $form->display();
    echo '</div>';

    if ('true' == $search_enabled) {
        //$xapianPath = api_get_path(SYS_UPLOAD_PATH).'plugins/xapian/searchdb';

        /*
        @todo Test the Xapian connection
        if (extension_loaded('xapian')) {
            require_once 'xapian.php';
            try {
                $db = new XapianDatabase($xapianPath.'/');
            } catch (Exception $e) {
                var_dump($e->getMessage());
            }

            require_once api_get_path(LIBRARY_PATH) . 'search/ChamiloIndexer.class.php';
            require_once api_get_path(LIBRARY_PATH) . 'search/IndexableChunk.class.php';
            require_once api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php';

            $indexable = new IndexableChunk();
            $indexable->addValue("content", 'Test');

            $di = new ChamiloIndexer();
            $di->connectDb(NULL, NULL, 'english');
            $di->addChunk($indexable);
            $did = $di->index();
        }
        */

        $xapianLoaded = Display::getMdiIcon(StateIcon::OPEN_VISIBILITY, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Validate'));
        $dir_exists = Display::getMdiIcon(StateIcon::OPEN_VISIBILITY, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Validate'));
        $dir_is_writable = Display::getMdiIcon(StateIcon::OPEN_VISIBILITY, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Validate'));
        $specific_fields_exists = Display::getMdiIcon(StateIcon::OPEN_VISIBILITY, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Validate'));

        //Testing specific fields
        if (empty($specific_fields)) {
            $specific_fields_exists = Display::getMdiIcon(StateIcon::CLOSED_VISIBILITY, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Add a specific search field')
            );
        }
        //Testing xapian extension
        if (!extension_loaded('xapian')) {
            $xapianLoaded = Display::getMdiIcon(StateIcon::CLOSED_VISIBILITY, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Error'));
        }
        //Testing xapian searchdb path
        if (!is_dir($xapianPath)) {
            $dir_exists = Display::getMdiIcon(StateIcon::CLOSED_VISIBILITY, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Error'));
        }
        //Testing xapian searchdb path is writable
        if (!is_writable($xapianPath)) {
            $dir_is_writable = Display::getMdiIcon(StateIcon::CLOSED_VISIBILITY, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Error'));
        }

        $data = [];
        $data[] = [get_lang('Xapian module installed'), $xapianLoaded];
        $data[] = [get_lang('The directory exists').' - '.$xapianPath, $dir_exists];
        $data[] = [get_lang('Is writable').' - '.$xapianPath, $dir_is_writable];
        $data[] = [get_lang('Available custom search fields'), $specific_fields_exists];

        showSearchSettingsTable($data);
        showSearchToolsStatusTable();
    }
}

/**
 * Wrapper for the templates.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @author Julio Montoya.
 *
 * @version August 2008
 *
 * @since v1.8.6
 */
function handleTemplates()
{
    /* Drive-by fix to avoid undefined var warnings, without repeating
     * isset() combos all over the place. */
    $action = isset($_GET['action']) ? $_GET['action'] : "invalid";

    if ('add' != $action) {
        echo '<div class="actions" style="margin-left: 1px;">';
        echo '<a href="settings.php?category=Templates&action=add">'.
                Display::getMdiIcon(ObjectIcon::TEMPLATE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add a template')).'</a>';
        echo '</div>';
    }

    if ('add' == $action || ('edit' == $action && is_numeric($_GET['id']))) {
        addEditTemplate();

        // Add event to the system log.
        $user_id = api_get_user_id();
        $category = $_GET['category'];
        Event::addEvent(
            LOG_CONFIGURATION_SETTINGS_CHANGE,
            LOG_CONFIGURATION_SETTINGS_CATEGORY,
            $category,
            api_get_utc_datetime(),
            $user_id
        );
    } else {
        if ('delete' == $action && is_numeric($_GET['id'])) {
            deleteTemplate($_GET['id']);

            // Add event to the system log
            $user_id = api_get_user_id();
            $category = $_GET['category'];
            Event::addEvent(
                LOG_CONFIGURATION_SETTINGS_CHANGE,
                LOG_CONFIGURATION_SETTINGS_CATEGORY,
                $category,
                api_get_utc_datetime(),
                $user_id
            );
        }
        displayTemplates();
    }
}

/**
 * Display a sortable table with all the templates that the platform administrator has defined.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 *
 * @version August 2008
 *
 * @since v1.8.6
 */
function displayTemplates()
{
    $table = new SortableTable(
        'templates',
        'getNumberOfTemplates',
        'getTemplateData',
        1
    );
    $table->set_additional_parameters(
        ['category' => Security::remove_XSS($_GET['category'])]
    );
    $table->set_header(0, get_lang('Image'), true, ['style' => 'width: 101px;']);
    $table->set_header(1, get_lang('Title'));
    $table->set_header(2, get_lang('Detail'), false, ['style' => 'width:50px;']);
    $table->set_column_filter(2, 'actionsFilter');
    $table->set_column_filter(0, 'searchImageFilter');
    $table->display();
}

/**
 * Gets the number of templates that are defined by the platform admin.
 *
 * @return int
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 *
 * @version August 2008
 *
 * @since v1.8.6
 */
function getNumberOfTemplates()
{
    // Database table definition.
    $table = Database::get_main_table('system_template');

    // The sql statement.
    $sql = "SELECT COUNT(id) AS total FROM $table";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);

    // Returning the number of templates.
    return $row['total'];
}

/**
 * Gets all the template data for the sortable table.
 *
 * @param int    $from            the start of the limit statement
 * @param int    $number_of_items the number of elements that have to be retrieved from the database
 * @param int    $column          the column that is
 * @param string $direction       the sorting direction (ASC or DESC)
 *
 * @return array
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 *
 * @version August 2008
 *
 * @since v1.8.6
 */
function getTemplateData($from, $number_of_items, $column, $direction)
{
    // Database table definition.
    $table_system_template = Database::get_main_table('system_template');

    $from = (int) $from;
    $number_of_items = (int) $number_of_items;
    $column = (int) $column;
    $direction = !in_array(strtolower(trim($direction)), ['asc', 'desc']) ? 'asc' : $direction;
    // The sql statement.
    $sql = "SELECT id as col0, title as col1, id as col2 FROM $table_system_template";
    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from,$number_of_items";
    $result = Database::query($sql);
    $return = [];
    while ($row = Database::fetch_array($result)) {
        $row['1'] = get_lang($row['1']);
        $return[] = $row;
    }
    // Returning all the information for the sortable table.
    return $return;
}

/**
 * display the edit and delete icons in the sortable table.
 *
 * @param int $id the id of the template
 *
 * @return string code for the link to edit and delete the template
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 *
 * @version August 2008
 *
 * @since v1.8.6
 */
function actionsFilter($id)
{
    $return = '<a href="settings.php?category=Templates&action=edit&id='.Security::remove_XSS($id).'">'.Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).'</a>';
    $return .= '<a href="settings.php?category=Templates&action=delete&id='.Security::remove_XSS($id).'" onClick="javascript:if(!confirm('."'".get_lang('Please confirm your choice')."'".')) return false;">'.Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).'</a>';

    return $return;
}

function searchImageFilter(int $id): string
{
    $em = Database::getManager();

    /** @var SystemTemplate $template */
    $template = $em->find(SystemTemplate::class, $id);

    if (null !== $template->getImage()) {
        $assetRepo = Container::getAssetRepository();
        $imageUrl = $assetRepo->getAssetUrl($template->getImage());

        return '<img src="'.$imageUrl.'" alt="'.get_lang('Template preview').'"/>';
    } else {
        return '<img src="'.api_get_path(WEB_PUBLIC_PATH).'img/template_thumb/noimage.gif" alt="'.get_lang('Preview not available').'"/>';
    }
}

/**
 * Add (or edit) a template. This function displays the form and also takes
 * care of uploading the image and storing the information in the database.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 *
 * @version August 2008
 *
 * @since v1.8.6
 */
function addEditTemplate()
{
    $em = Database::getManager();
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    $assetRepo = Container::getAssetRepository();

    /** @var SystemTemplate $template */
    $template = $id ? $em->find(SystemTemplate::class, $id) : new SystemTemplate();

    $form = new FormValidator(
        'template',
        'post',
        'settings.php?category=Templates&action='.Security::remove_XSS($_GET['action']).'&id='.$id
    );

    // Setting the form elements: the header.
    if ('add' == $_GET['action']) {
        $title = get_lang('Add a template');
    } else {
        $title = get_lang('Template edition');
    }
    $form->addElement('header', '', $title);

    // Setting the form elements: the title of the template.
    $form->addText('title', get_lang('Title'), false);
    $form->addText('comment', get_lang('Description'), false);

    // Setting the form elements: the content of the template (wysiwyg editor).
    $form->addHtmlEditor(
        'template_text',
        get_lang('Text'),
        true,
        true,
        ['ToolbarSet' => 'Documents', 'Width' => '100%', 'Height' => '400']
    );

    // Setting the form elements: the form to upload an image to be used with the template.
    if (!$template->hasImage()) {
        // Picture
        $form->addFile(
            'template_image',
            get_lang('Add image'),
            ['id' => 'picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_ratio' => '1 / 1']
        );
        $allowedPictureTypes = api_get_supported_image_extensions(false);
        $form->addRule('template_image', get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(',', $allowedPictureTypes).')', 'filetype', $allowedPictureTypes);
    }

    // Setting the form elements: a little bit of information about the template image.
    $form->addElement('static', 'file_comment', '', get_lang('This image will represent the template in the templates list. It should be no larger than 100x70 pixels'));

    // Getting all the information of the template when editing a template.
    if ('edit' == $_GET['action']) {
        $defaults['template_id'] = $id;
        $defaults['template_text'] = $template->getContent();
        // Forcing get_lang().
        $defaults['title'] = $template->getTitle();
        $defaults['comment'] = $template->getComment();

        // Adding an extra field: a hidden field with the id of the template we are editing.
        $form->addElement('hidden', 'template_id');

        // Adding an extra field: a preview of the image that is currently used.

        if ($template->hasImage()) {
            $imageUrl = $assetRepo->getAssetUrl($template->getImage());
            $form->addElement(
                'static',
                'template_image_preview',
                '',
                '<img src="'.$imageUrl
                    .'" alt="'.get_lang('Template preview')
                    .'"/>'
            );
            $form->addCheckBox('delete_image', null, get_lang('Delete picture'));
        } else {
            $form->addElement(
                'static',
                'template_image_preview',
                '',
                '<img src="'.api_get_path(WEB_PUBLIC_PATH).'img/template_thumb/noimage.gif" alt="'.get_lang('Preview not available').'"/>'
            );
        }

        // Setting the information of the template that we are editing.
        $form->setDefaults($defaults);
    }
    // Setting the form elements: the submit button.
    $form->addButtonSave(get_lang('Validate'), 'submit');

    // Setting the rules: the required fields.
    if (!$template->hasImage()) {
        $form->addRule(
            'template_image',
            get_lang('Required field'),
            'required'
        );
        $form->addRule('title', get_lang('Required field'), 'required');
    }

    // if the form validates (complies to all rules) we save the information,
    // else we display the form again (with error message if needed)
    if ($form->validate()) {
        $check = Security::check_token('post');

        if ($check) {
            // Exporting the values.
            $values = $form->exportValues();
            $asset = null;
            if (isset($values['delete_image']) && !empty($id)) {
                deleteTemplateImage($id);
            }

            // Upload the file.
            if (!empty($_FILES['template_image']['name'])) {
                $picture = $_FILES['template_image'];
                if (!empty($picture['name'])) {
                    $asset = (new Asset())
                        ->setCategory(Asset::SYSTEM_TEMPLATE)
                        ->setTitle($picture['name'])
                    ;
                    if (!empty($values['picture_crop_result'])) {
                        $asset->setCrop($values['picture_crop_result']);
                    }
                    $asset = $assetRepo->createFromRequest($asset, $picture);
                }
            }

            // Store the information in the database (as insert or as update).
            $bootstrap = api_get_bootstrap_and_font_awesome();
            $viewport = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';

            if ('add' == $_GET['action']) {
                $templateContent = '<head>'.$viewport.'<title>'.$values['title'].'</title>'.$bootstrap.'</head>'
                    .$values['template_text'];
                $template
                    ->setTitle($values['title'])
                    ->setComment(Security::remove_XSS($values['comment']))
                    ->setContent(Security::remove_XSS($templateContent, COURSEMANAGERLOWSECURITY))
                    ->setImage($asset);
                $em->persist($template);
                $em->flush();

                // Display a feedback message.
                echo Display::return_message(
                    get_lang('Template added'),
                    'confirm'
                );
                echo '<a href="settings.php?category=Templates&action=add">'.
                    Display::getMdiIcon(ObjectIcon::TEMPLATE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add a template')).
                    '</a>';
            } else {
                $templateContent = '<head>'.$viewport.'<title>'.$values['title'].'</title>'.$bootstrap.'</head>'
                    .$values['template_text'];

                $template
                    ->setTitle($values['title'])
                    ->setContent(Security::remove_XSS($templateContent, COURSEMANAGERLOWSECURITY));

                if ($asset) {
                    $template->setImage($asset);
                }

                $em->persist($template);
                $em->flush();

                // Display a feedback message.
                echo Display::return_message(get_lang('Template edited'), 'confirm');
            }
        }
        Security::clear_token();
        displayTemplates();
    } else {
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => $token]);
        // Display the form.
        $form->display();
    }
}

/**
 * Deletes the template picture as asset.
 *
 * @param int $id
 */
function deleteTemplateImage($id)
{
    $em = Database::getManager();

    /** @var SystemTemplate $template */
    $template = $em->find(SystemTemplate::class, $id);

    if ($template && $template->hasImage()) {
        $image = $template->getImage();
        $em->remove($image);
        $em->flush();
    }
}

/**
 * Delete a template.
 *
 * @param int $id the id of the template that has to be deleted
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 *
 * @version August 2008
 *
 * @since v1.8.6
 */
function deleteTemplate($id)
{
    $id = intval($id);
    // First we remove the image.
    $table = Database::get_main_table('system_template');
    $sql = "SELECT * FROM $table WHERE id = $id";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    if (!empty($row['image'])) {
        @unlink(api_get_path(SYS_APP_PATH).'home/default_platform_document/template_thumb/'.$row['image']);
    }

    // Now we remove it from the database.
    $sql = "DELETE FROM $table WHERE id = $id";
    Database::query($sql);

    deleteTemplateImage($id);

    // Display a feedback message.
    echo Display::return_message(get_lang('Template deleted'), 'confirm');
}

/**
 * @param array $settings
 * @param array $settings_by_access_list
 *
 * @throws \Doctrine\ORM\ORMException
 * @throws \Doctrine\ORM\OptimisticLockException
 * @throws \Doctrine\ORM\TransactionRequiredException
 *
 * @return FormValidator
 */
function generateSettingsForm($settings, $settings_by_access_list)
{
    global $_configuration, $settings_to_avoid, $convert_byte_to_mega_list;
    $em = Database::getManager();
    $table_settings_current = Database::get_main_table(TABLE_MAIN_SETTINGS);

    $form = new FormValidator(
        'settings',
        'post',
        'settings.php?category='.Security::remove_XSS($_GET['category'])
    );

    $form->addElement(
        'hidden',
        'search_field',
        (!empty($_GET['search_field']) ? Security::remove_XSS($_GET['search_field']) : null)
    );

    $url_id = api_get_current_access_url_id();

    $default_values = [];
    $url_info = api_get_access_url($url_id);
    $i = 0;
    $addedSettings = [];
    foreach ($settings as $row) {
        if (in_array($row['variable'], array_keys($settings_to_avoid))) {
            continue;
        }

        if (in_array($row['variable'], $addedSettings)) {
            continue;
        }

        $addedSettings[] = $row['variable'];

        if (api_get_multiple_access_url()) {
            if (api_is_global_platform_admin()) {
                if (0 == $row['access_url_locked']) {
                    if (1 == $url_id) {
                        if ('1' == $row['access_url_changeable']) {
                            $form->addElement(
                                'html',
                                '<div class="float-right"><a class="share_this_setting" data_status = "0"  data_to_send = "'.$row['variable'].'" href="javascript:void(0);">'.
                                Display::getMdiIcon(StateIcon::SHARED_VISIBILITY, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Change setting visibility for the other portals')).'</a></div>'
                            );
                        } else {
                            $form->addElement(
                                'html',
                                '<div class="float-right"><a class="share_this_setting" data_status = "1" data_to_send = "'.$row['variable'].'" href="javascript:void(0);">'.
                                Display::getMdiIcon(StateIcon::SHARED_VISIBILITY, 'ch-tool-icon-disabled', null, ICON_SIZE_MEDIUM, get_lang('Change setting visibility for the other portals')).'</a></div>'
                            );
                        }
                    } else {
                        if ('1' == $row['access_url_changeable']) {
                            $form->addElement(
                                'html',
                                '<div class="float-right">'.
                                Display::getMdiIcon(StateIcon::SHARED_VISIBILITY, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Change setting visibility for the other portals')).'</div>'
                            );
                        } else {
                            $form->addElement(
                                'html',
                                '<div class="float-right">'.
                                Display::getMdiIcon(StateIcon::SHARED_VISIBILITY, 'ch-tool-icon-disabled', null, ICON_SIZE_MEDIUM, get_lang('Change setting visibility for the other portals')).'</div>'
                            );
                        }
                    }
                }
            }
        }

        $hideme = [];
        $hide_element = false;

        if (1 != $_configuration['access_url']) {
            if (0 == $row['access_url_changeable']) {
                // We hide the element in other cases (checkbox, radiobutton) we 'freeze' the element.
                $hide_element = true;
                $hideme = ['disabled'];
            } elseif (1 == $url_info['active']) {
                // We show the elements.
                if (empty($row['variable'])) {
                    $row['variable'] = 0;
                }
                if (empty($row['subkey'])) {
                    $row['subkey'] = 0;
                }
                if (empty($row['category'])) {
                    $row['category'] = 0;
                }
                if (isset($settings_by_access_list[$row['variable']]) &&
                    isset($settings_by_access_list[$row['variable']][$row['subkey']]) &&
                    is_array($settings_by_access_list[$row['variable']][$row['subkey']][$row['category']])
                ) {
                    // We are sure that the other site have a selected value.
                    if ('' != $settings_by_access_list[$row['variable']][$row['subkey']][$row['category']]['selected_value']) {
                        $row['selected_value'] = $settings_by_access_list[$row['variable']][$row['subkey']][$row['category']]['selected_value'];
                    }
                }
                // There is no else{} statement because we load the default $row['selected_value'] of the main Chamilo site.
            }
        }

        switch ($row['type']) {
            case 'textfield':
                if (in_array($row['variable'], $convert_byte_to_mega_list)) {
                    $form->addElement(
                        'text',
                        $row['variable'],
                        [
                            get_lang($row['title']),
                            get_lang($row['comment']),
                            get_lang('MB'),
                        ],
                        ['maxlength' => '8', 'aria-label' => get_lang($row['title'])]
                    );
                    $form->applyFilter($row['variable'], 'html_filter');
                    $default_values[$row['variable']] = round($row['selected_value'] / 1024 / 1024, 1);
                } elseif ('account_valid_duration' == $row['variable']) {
                    $form->addElement(
                        'text',
                        $row['variable'],
                        [
                            get_lang($row['title']),
                            get_lang($row['comment']),
                        ],
                        ['maxlength' => '5', 'aria-label' => get_lang($row['title'])]
                    );
                    $form->applyFilter($row['variable'], 'html_filter');

                    // For platform character set selection:
                    // Conversion of the textfield to a select box with valid values.
                    $default_values[$row['variable']] = $row['selected_value'];
                } elseif ('platform_charset' == $row['variable']) {
                    break;
                } else {
                    $hideme['class'] = 'col-md-4';
                    $hideme['aria-label'] = get_lang($row['title']);
                    $form->addElement(
                        'text',
                        $row['variable'],
                        [
                            get_lang($row['title']),
                            get_lang($row['comment']),
                        ],
                        $hideme
                    );
                    $form->applyFilter($row['variable'], 'html_filter');
                    $default_values[$row['variable']] = $row['selected_value'];
                }
                break;
            case 'textarea':
                if ('header_extra_content' == $row['variable']) {
                    $file = api_get_home_path().'header_extra_content.txt';
                    $value = '';
                    if (file_exists($file)) {
                        $value = file_get_contents($file);
                    }
                    $form->addElement(
                        'textarea',
                        $row['variable'],
                        [get_lang($row['title']), get_lang($row['comment'])],
                        ['rows' => '10', 'id' => $row['variable']],
                        $hideme
                    );
                    $default_values[$row['variable']] = $value;
                } elseif ('footer_extra_content' == $row['variable']) {
                    $file = api_get_home_path().'footer_extra_content.txt';
                    $value = '';
                    if (file_exists($file)) {
                        $value = file_get_contents($file);
                    }
                    $form->addElement(
                        'textarea',
                        $row['variable'],
                        [get_lang($row['title']), get_lang($row['comment'])],
                        ['rows' => '10', 'id' => $row['variable']],
                        $hideme
                    );
                    $default_values[$row['variable']] = $value;
                } else {
                    $form->addElement(
                        'textarea',
                        $row['variable'],
                        [get_lang($row['title']),
                        get_lang($row['comment']), ],
                        ['rows' => '10', 'id' => $row['variable']],
                        $hideme
                    );
                    $default_values[$row['variable']] = $row['selected_value'];
                }
                break;
            case 'radio':
                $values = api_get_settings_options($row['variable']);
                $group = [];
                if (is_array($values)) {
                    foreach ($values as $key => $value) {
                        $element = &$form->createElement(
                            'radio',
                            $row['variable'],
                            '',
                            get_lang($value['display_text']),
                            $value['value']
                        );
                        if ($hide_element) {
                            $element->freeze();
                        }
                        $group[] = $element;
                    }
                }
                $form->addGroup(
                    $group,
                    $row['variable'],
                    [get_lang($row['title']), get_lang($row['comment'])],
                    null,
                    false
                );
                $default_values[$row['variable']] = $row['selected_value'];
                break;
            case 'checkbox':
                // 1. We collect all the options of this variable.
                $sql = "SELECT * FROM $table_settings_current
                        WHERE variable='".$row['variable']."' AND access_url =  1";

                $result = Database::query($sql);
                $group = [];
                while ($rowkeys = Database::fetch_array($result)) {
                    // Profile tab option should be hidden when the social tool is enabled.
                    if ('true' == api_get_setting('allow_social_tool')) {
                        if ('show_tabs' === $rowkeys['variable'] && 'my_profile' === $rowkeys['subkey']) {
                            continue;
                        }
                    }

                    // Hiding the gradebook option.
                    if ('show_tabs' === $rowkeys['variable'] && 'my_gradebook' === $rowkeys['subkey']) {
                        continue;
                    }

                    $element = &$form->createElement(
                        'checkbox',
                        $rowkeys['subkey'],
                        '',
                        get_lang($rowkeys['subkeytext'])
                    );

                    if (1 == $row['access_url_changeable']) {
                        // 2. We look into the DB if there is a setting for a specific access_url.
                        $access_url = $_configuration['access_url'];
                        if (empty($access_url)) {
                            $access_url = 1;
                        }
                        $sql = "SELECT selected_value FROM $table_settings_current
                                WHERE
                                    variable='".$rowkeys['variable']."' AND
                                    subkey='".$rowkeys['subkey']."' AND
                                    subkeytext='".$rowkeys['subkeytext']."' AND
                                    access_url =  $access_url";
                        $result_access = Database::query($sql);
                        $row_access = Database::fetch_array($result_access);
                        if ('true' === $row_access['selected_value'] && !$form->isSubmitted()) {
                            $element->setChecked(true);
                        }
                    } else {
                        if ('true' === $rowkeys['selected_value'] && !$form->isSubmitted()) {
                            $element->setChecked(true);
                        }
                    }
                    if ($hide_element) {
                        $element->freeze();
                    }
                    $group[] = $element;
                }
                $form->addGroup(
                    $group,
                    $row['variable'],
                    [get_lang($row['title']), get_lang($row['comment'])],
                    null
                );
                break;
            case 'link':
                $form->addElement(
                    'static',
                    null,
                    [get_lang($row['title']), get_lang($row['comment'])],
                    get_lang('current value').' : '.$row['selected_value'],
                    $hideme
                );
                break;
            case 'select':
                /*
                * To populate the list of options, the select type dynamically calls a function that must be called select_ + the name of the variable being displayed.
                * The functions being called must be added to the file settings.lib.php.
                */
                $form->addElement(
                    'select',
                    $row['variable'],
                    [get_lang($row['title']), get_lang($row['comment'])],
                    call_user_func('select_'.$row['variable']),
                    $hideme
                );
                $default_values[$row['variable']] = $row['selected_value'];
                break;
            case 'custom':
                break;
            case 'select_course':
                $courseSelectOptions = [];

                if (!empty($row['selected_value'])) {
                    $course = $em->find(Course::class, $row['selected_value']);

                    $courseSelectOptions[$course->getId()] = $course->getTitle();
                }

                $form->addElement(
                    'select_ajax',
                    $row['variable'],
                    [get_lang($row['title']), get_lang($row['comment'])],
                    $courseSelectOptions,
                    ['url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course']
                );
                $default_values[$row['variable']] = $row['selected_value'];
                break;
        }

        switch ($row['variable']) {
            case 'pdf_export_watermark_enable':
                $url = PDF::get_watermark(null);

                if (false != $url) {
                    $delete_url = '<a href="?delete_watermark">'.get_lang('Remove picture').' '.Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Remove picture')).'</a>';
                    $form->addElement('html', '<div style="max-height:100px; max-width:100px; margin-left:162px; margin-bottom:10px; clear:both;"><img src="'.$url.'" style="margin-bottom:10px;" />'.$delete_url.'</div>');
                }

                $form->addElement('file', 'pdf_export_watermark_path', get_lang('Upload a watermark image'));
                $allowed_picture_types = ['jpg', 'jpeg', 'png', 'gif'];
                $form->addRule(
                    'pdf_export_watermark_path',
                    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(',', $allowed_picture_types).')',
                    'filetype',
                    $allowed_picture_types
                );

                break;
            case 'timezone_value':
                $timezone = $row['selected_value'];
                if (empty($timezone)) {
                    $timezone = api_get_timezone();
                }
                $form->addLabel('', sprintf(get_lang('The local time in the portal timezone (%s) is %s'), $timezone, api_get_local_time()));
                break;
        }
    } // end for

    if (!empty($settings)) {
        $form->setDefaults($default_values);
    }
    $form->addHtml('<div class="bottom_actions">');
    $form->addButtonSave(get_lang('Save settings'));
    $form->addHtml('</div>');

    return $form;
}

/**
 * Searches a platform setting in all categories except from the Plugins category.
 *
 * @param string $search
 *
 * @return array
 */
function searchSetting($search)
{
    if (empty($search)) {
        return [];
    }
    $table_settings_current = Database::get_main_table(TABLE_MAIN_SETTINGS);
    $sql = "SELECT * FROM $table_settings_current
            WHERE category <> 'Plugins' ORDER BY id ASC ";
    $result = Database::store_result(Database::query($sql), 'ASSOC');
    $settings = [];

    $search = api_strtolower($search);

    if (!empty($result)) {
        foreach ($result as $setting) {
            $found = false;

            $title = api_strtolower(get_lang($setting['title']));
            // try the title
            if (false === strpos($title, $search)) {
                $comment = api_strtolower(get_lang($setting['comment']));
                //Try the comment
                if (false === strpos($comment, $search)) {
                    //Try the variable name
                    if (false === strpos($setting['variable'], $search)) {
                        continue;
                    } else {
                        $found = true;
                    }
                } else {
                    $found = true;
                }
            } else {
                $found = true;
            }
            if ($found) {
                $settings[] = $setting;
            }
        }
    }

    return $settings;
}
/**
 * Helper function to generates a form elements group.
 *
 * @param object $form   The form where the elements group has to be added
 * @param array  $values Values to browse through
 *
 * @return array
 */
function formGenerateElementsGroup($form, $values = [], $elementName)
{
    $group = [];
    if (is_array($values)) {
        foreach ($values as $key => $value) {
            $element = &$form->createElement('radio', $elementName, '', get_lang($value['display_text']), $value['value']);
            $group[] = $element;
        }
    }

    return $group;
}
/**
 * Helper function with allowed file types for CSS.
 *
 * @return array Array of file types (no indexes)
 */
function getAllowedFileTypes()
{
    $allowedFiles = [
        'css',
        'zip',
        'jpeg',
        'jpg',
        'png',
        'gif',
        'ico',
        'psd',
        'xcf',
        'svg',
        'webp',
        'woff',
        'woff2',
    ];

    return $allowedFiles;
}
/**
 * Helper function to set settings in the database.
 *
 * @param array $parameters List of values
 * @param int   $accessUrl  The current access URL
 */
function setConfigurationSettingsInDatabase($parameters, $accessUrl)
{
    api_set_settings_category('Search', 'false', $accessUrl);
    // Save the settings.
    foreach ($parameters as $key => $value) {
        api_set_setting($key, $value, null, null);
    }
}

/**
 * Helper function to show the status of the search settings table.
 *
 * @param array $data Data to show
 */
function showSearchSettingsTable($data)
{
    echo Display::tag('h3', get_lang('Settings'));
    $table = new SortableTableFromArray($data);
    $table->set_header(0, get_lang('Setting'), false);
    $table->set_header(1, get_lang('Status'), false);
    echo $table->display();
}
/**
 * Helper function to show status table for each command line tool installed.
 */
function showSearchToolsStatusTable()
{
    //@todo windows support
    if (false == api_is_windows_os()) {
        $list_of_programs = ['pdftotext', 'ps2pdf', 'catdoc', 'html2text', 'unrtf', 'catppt', 'xls2csv'];
        foreach ($list_of_programs as $program) {
            $output = [];
            $ret_val = null;
            exec("which $program", $output, $ret_val);

            if (!$output) {
                $output[] = '';
            }

            $icon = Display::getMdiIcon(StateIcon::CLOSED_VISIBILITY, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Not installed'));
            if (!empty($output[0])) {
                $icon = Display::getMdiIcon(StateIcon::OPEN_VISIBILITY, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Installed'));
            }
            $data2[] = [$program, $output[0], $icon];
        }
        echo Display::tag('h3', get_lang('Programs needed to convert files'));
        $table = new SortableTableFromArray($data2);
        $table->set_header(0, get_lang('Software program'), false);
        $table->set_header(1, get_lang('Path'), false);
        $table->set_header(2, get_lang('Status'), false);
        echo $table->display();
    } else {
        echo Display::return_message(
            get_lang('You are using Chamilo in a Windows platform, sadly you can\'t convert documents in order to search the content using this tool'),
            'warning'
        );
    }
}
/**
 * Helper function to generate and show CSS Zip download message.
 *
 * @param string $style Style path
 */
function generateCSSDownloadLink($style)
{
    $arch = api_get_path(SYS_ARCHIVE_PATH).$style.'.zip';
    $themeDir = Template::getThemeDir($style);
    $dir = api_get_path(SYS_CSS_PATH).$themeDir;
    $check = Security::check_abs_path(
        $dir,
        api_get_path(SYS_CSS_PATH).'themes'
    );
    if (is_dir($dir) && $check) {
        $zip = new PclZip($arch);
        // Remove path prefix except the style name and put file on disk
        $zip->create($dir, PCLZIP_OPT_REMOVE_PATH, substr($dir, 0, -strlen($style)));
        $url = api_get_path(WEB_CODE_PATH).'course_info/download.php?archive_path=&archive='.str_replace(api_get_path(SYS_ARCHIVE_PATH), '', $arch);

        //@TODO: use more generic script to download.
        $str = '<a class="btn btn--primary btn-large" href="'.$url.'">'.get_lang('Download the file').'</a>';
        echo Display::return_message($str, 'normal', false);
    } else {
        echo Display::return_message(get_lang('The file was not found'), 'warning');
    }
}

/**
 * Get all settings of one category prepared for display in admin/settings.php.
 *
 * @param string $category
 *
 * @return array
 */
function getCategorySettings($category = '')
{
    $url_id = api_get_current_access_url_id();
    $settings_by_access_list = [];

    if (1 == $url_id) {
        $settings = api_get_settings($category, 'group', $url_id);
    } else {
        $url_info = api_get_access_url($url_id);
        if (1 == $url_info['active']) {
            $categoryToSearch = $category;
            if ('search_setting' == $category) {
                $categoryToSearch = '';
            }
            // The default settings of Chamilo
            $settings = api_get_settings($categoryToSearch, 'group', 1, 0);
            // The settings that are changeable from a particular site.
            $settings_by_access = api_get_settings($categoryToSearch, 'group', $url_id, 1);

            foreach ($settings_by_access as $row) {
                if (empty($row['variable'])) {
                    $row['variable'] = 0;
                }
                if (empty($row['subkey'])) {
                    $row['subkey'] = 0;
                }
                if (empty($row['category'])) {
                    $row['category'] = 0;
                }

                // One more validation if is changeable.
                if (1 == $row['access_url_changeable']) {
                    $settings_by_access_list[$row['variable']][$row['subkey']][$row['category']] = $row;
                } else {
                    $settings_by_access_list[$row['variable']][$row['subkey']][$row['category']] = [];
                }
            }
        }
    }

    if (isset($category) && 'search_setting' == $category) {
        if (!empty($_REQUEST['search_field'])) {
            $settings = searchSetting($_REQUEST['search_field']);
        }
    }

    return [
        'settings' => $settings,
        'settings_by_access_list' => $settings_by_access_list,
    ];
}
