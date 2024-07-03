<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\H5pImport\H5pImport;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pPackageImporter;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pPackageTools;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();
api_protect_course_script(true);
$cidReq = api_get_cidreq();
$pluginIndex = "./start.php?$cidReq";
$plugin = H5pImportPlugin::create();

if ('false' === $plugin->get('tool_enable')) {
    api_not_allowed(true);
}

$isAllowedToEdit = api_is_allowed_to_edit(true);

$action = $_REQUEST['action'] ?? null;

$em = Database::getManager();
$h5pRepo = $em->getRepository('ChamiloPluginBundle:H5pImport\H5pImport');
$h5pResultsRepo = $em->getRepository('ChamiloPluginBundle:H5pImport\H5pImportResults');

$course = api_get_course_entity(api_get_course_int_id());
$session = api_get_session_entity(api_get_session_id());
$user = api_get_user_entity(api_get_user_id());

$actions = [];

$view = new Template($plugin->getToolTitle());
$view->assign('is_allowed_to_edit', $isAllowedToEdit);

switch ($action) {
    case 'add':
        if (!$isAllowedToEdit) {
            api_not_allowed(true);
        }

        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            api_get_self()
        );

        // Set the max upload size in php.ini in the file uploader
        $maxFileSize = getIniMaxFileSizeInBytes();
        $form = new FormValidator('frm_edit');
        $form->addFile('file', $plugin->get_lang('h5p_package'), ['accept' => '.h5p']);
        $form->addRule(
            'file',
            'The file size cannot exceed: '.$maxFileSize.' bytes',
            'maxfilesize',
            $maxFileSize,
            'client'
        );
        $form->addButtonAdvancedSettings('advanced_params');
        $form->addHtml('<div id="advanced_params_options" style="display:none">');
        $form->addTextarea('description', get_lang('Description'));
        $form->applyFilter('description', 'trim');
        $form->addHtml('</div>');
        $form->addButtonUpdate(get_lang('Add'));
        $form->addHidden('action', 'add');
        $form->protect();

        if ($form->validate()) {
            $values = $form->exportValues();

            $zipFileInfo = $_FILES['file'];

            try {
                $importer = H5pPackageImporter::create($zipFileInfo, $course);
                $packageFile = $importer->import();

                // Get the h5p.json and content.json contents
                $h5pJson = H5pPackageTools::getJson($packageFile.DIRECTORY_SEPARATOR.'h5p.json');
                $contentJson = H5pPackageTools::getJson(
                    $packageFile.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'content.json'
                );
                if ($h5pJson && $contentJson) {
                    if (H5pPackageTools::checkPackageIntegrity($h5pJson, $packageFile)) {
                        H5pPackageTools::storeH5pPackage($packageFile, $h5pJson, $course, $session, $values);

                        Display::addFlash(
                            Display::return_message(get_lang('Added'), 'success')
                        );
                    } else {
                        Display::addFlash(
                            Display::return_message(get_lang('Error'), 'error')
                        );
                        break;
                    }

                    header('Location: '.api_get_self());
                }

                exit;
            } catch (Exception $e) {
                Display::addFlash(
                    Display::return_message($e->getMessage(), 'error')
                );

                header("Location: $pluginIndex");
                exit;
            }
        }

        $view->assign('header', $plugin->get_lang('import_h5p_package'));
        $view->assign('form', $form->returnForm());

        break;
    case 'delete':
        $h5pImportId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        if (!$h5pImportId && !Security::check_token('get')) {
            break;
        }

        /** @var H5pImport|null $h5pImport */
        $h5pImport = $h5pRepo->find($h5pImportId);

        if (!$h5pImport) {
            Display::addFlash(Display::return_message($plugin->get_lang('ContentNotFound'), 'danger'));

            break;
        }
        if (H5pPackageTools::deleteH5pPackage($h5pImport)) {
            Display::addFlash(
                Display::return_message(get_lang('Deleted'), 'success')
            );
        } else {
            Display::addFlash(
                Display::return_message(get_lang('Error'), 'danger')
            );
        }

        header('Location: '.api_get_self());
        exit;
    default:

        /** @var array|H5pImport[] $h5pImports */
        $h5pImports = $h5pRepo->findBy(['course' => $course, 'session' => $session]);

        $tableData = [];
        /** @var H5pImport $h5pImport */
        foreach ($h5pImports as $h5pImport) {
            $h5pImportsResults = $h5pResultsRepo->count(
                [
                    'course' => $course,
                    'session' => $session,
                    'user' => $user,
                    'h5pImport' => $h5pImport,
                ]
            );
            $data = [
                Display::url(
                    $h5pImport->getName(),
                    $plugin->getViewUrl($h5pImport)
                ),
                $h5pImport->getDescription(),
                $h5pImportsResults,
            ];

            if ($isAllowedToEdit) {
                $data[] = $h5pImport;
            }

            $tableData[] = $data;
        }

        if ($isAllowedToEdit) {
            $btnAdd = Display::toolbarButton(
                get_lang('Upload'),
                api_get_self().'?action=add',
                'file-code-o',
                'primary'
            );

            $view->assign(
                'actions',
                Display::toolbarAction($plugin->get_name(), [$btnAdd])
            );

            if (in_array($action, ['add', 'edit'])) {
                $view->assign('form', $form->returnForm());
            }
        }

        $table = new SortableTableFromArray($tableData, 0);
        $table->set_header(0, get_lang('Title'));
        $table->set_header(1, get_lang('Description'));
        $table->set_header(2, $plugin->get_lang('attempts'));

        if ($isAllowedToEdit) {
            $table->set_header(
                3,
                get_lang('Actions'),
                false,
                'th-header text-right',
                ['class' => 'text-right']
            );
            $table->set_column_filter(
                3,
                function (H5pImport $value) use ($isAllowedToEdit) {
                    $actions = [];

                    if ($isAllowedToEdit) {
                        $actions[] = Display::url(
                            Display::return_icon('delete.png', get_lang('Delete')),
                            api_get_self().'?'.http_build_query([
                                'action' => 'delete',
                                'id' => $value->getIid(),
                                'sec_token' => Security::getTokenFromSession(),
                            ])
                        );
                    }

                    return implode(PHP_EOL, $actions);
                }
            );
        }
        $view->assign('h5pImports', $h5pImports);
        $view->assign('table', $table->return_table());

}

$content = $view->fetch('h5pimport/view/index.tpl');
if ($actions) {
    $actions = implode(PHP_EOL, $actions);

    $view->assign(
        'actions',
        Display::toolbarAction($plugin->get_name(), [$actions])
    );
}

$view->assign('content', $content);
$view->display_one_col_template();
