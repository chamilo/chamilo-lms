<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\H5pImport\Entity\H5pImport;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImportResults;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pPackageImporter;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pPackageTools;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();
api_protect_course_script(true);

$plugin = H5pImportPlugin::create();
if (!$plugin->isToolEnabled()) {
    api_not_allowed(true);
}

$cidReq = api_get_cidreq();
$pluginIndex = api_get_path(WEB_PLUGIN_PATH).'H5pImport/start.php'.('' !== $cidReq ? '?'.$cidReq : '');
$isAllowedToEdit = api_is_allowed_to_edit(true);
$action = $_REQUEST['action'] ?? null;

$em = Database::getManager();
$h5pRepo = $em->getRepository(H5pImport::class);
$h5pResultsRepo = $em->getRepository(H5pImportResults::class);

$course = api_get_course_entity(api_get_course_int_id());
$session = api_get_session_entity(api_get_session_id());
$user = api_get_user_entity(api_get_user_id());

$view = new Template($plugin->getToolTitle());
$view->assign('is_allowed_to_edit', $isAllowedToEdit);

switch ($action) {
    case 'add':
        if (!$isAllowedToEdit) {
            api_not_allowed(true);
        }

        $actions = [
            Display::url(
                Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                $pluginIndex
            ),
        ];

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
            $zipFileInfo = $_FILES['file'] ?? null;

            if (empty($zipFileInfo['tmp_name'])) {
                Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
                header('Location: '.$pluginIndex);
                exit;
            }

            try {
                $importer = H5pPackageImporter::create($zipFileInfo, $course);
                $packageFile = $importer->import();

                $h5pJson = H5pPackageTools::getJson($packageFile.'/h5p.json');
                $contentJson = H5pPackageTools::getJson($packageFile.'/content/content.json');
                if (false === $contentJson) {
                    $contentJson = H5pPackageTools::getJson($packageFile.'/content.json');
                }

                if ($h5pJson && $contentJson && H5pPackageTools::checkPackageIntegrity($h5pJson, $packageFile)) {
                    H5pPackageTools::storeH5pPackage($packageFile, $h5pJson, $course, $session, $values);
                    Display::addFlash(Display::return_message(get_lang('Added'), 'success'));
                } else {
                    Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
                }

                header('Location: '.$pluginIndex);
                exit;
            } catch (Throwable $e) {
                Display::addFlash(Display::return_message($e->getMessage(), 'error'));
                header('Location: '.$pluginIndex);
                exit;
            }
        }

        $view->assign('header', $plugin->get_lang('import_h5p_package'));
        $view->assign('actions', Display::toolbarAction($plugin->get_name(), $actions));
        $view->assign('form', $form->returnForm());

        break;

    case 'delete':
        if (!$isAllowedToEdit) {
            api_not_allowed(true);
        }

        $h5pImportId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
        if (!$h5pImportId || !Security::check_token('get')) {
            Display::addFlash(Display::return_message(get_lang('Error'), 'danger'));
            header('Location: '.$pluginIndex);
            exit;
        }

        /** @var H5pImport|null $h5pImport */
        $h5pImport = $h5pRepo->find($h5pImportId);

        if (
            !$h5pImport
            || $course->getId() !== $h5pImport->getCourse()->getId()
            || (($session && $h5pImport->getSession()) && $session->getId() !== $h5pImport->getSession()->getId())
        ) {
            Display::addFlash(Display::return_message($plugin->get_lang('ContentNotFound'), 'danger'));
            header('Location: '.$pluginIndex);
            exit;
        }

        if (H5pPackageTools::deleteH5pPackage($h5pImport)) {
            Display::addFlash(Display::return_message(get_lang('Deleted'), 'success'));
        } else {
            Display::addFlash(Display::return_message(get_lang('Error'), 'danger'));
        }

        header('Location: '.$pluginIndex);
        exit;

    default:
        /** @var H5pImport[] $h5pImports */
        $h5pImports = $h5pRepo->findBy(['course' => $course, 'session' => $session]);

        $tableData = [];
        foreach ($h5pImports as $h5pImport) {
            $attemptCount = $h5pResultsRepo->count([
                'course' => $course,
                'session' => $session,
                'user' => $user,
                'h5pImport' => $h5pImport,
            ]);

            $row = [
                Display::url($h5pImport->getName(), $plugin->getViewUrl($h5pImport)),
                $h5pImport->getDescription(),
                $attemptCount,
            ];

            if ($isAllowedToEdit) {
                $row[] = $h5pImport;
            }

            $tableData[] = $row;
        }

        if ($isAllowedToEdit) {
            $btnAdd = Display::toolbarButton(
                get_lang('Upload'),
                $pluginIndex.('' !== $cidReq ? '&' : '?').'action=add',
                'file-code-o',
                'primary'
            );

            $view->assign('actions', Display::toolbarAction($plugin->get_name(), [$btnAdd]));
        }

        $table = new SortableTableFromArray($tableData, 0);
        $table->set_header(0, get_lang('Title'));
        $table->set_header(1, get_lang('Description'));
        $table->set_header(2, $plugin->get_lang('attempts'));

        if ($isAllowedToEdit) {
            $table->set_header(3, get_lang('Actions'), false, 'th-header text-right', ['class' => 'text-right']);
            $table->set_column_filter(
                3,
                static function (H5pImport $value) use ($pluginIndex) {
                    return Display::url(
                        Display::return_icon('delete.png', get_lang('Delete')),
                        $pluginIndex.('' !== parse_url($pluginIndex, PHP_URL_QUERY) ? '&' : '?').http_build_query([
                            'action' => 'delete',
                            'id' => $value->getIid(),
                            'sec_token' => Security::getTokenFromSession(),
                        ])
                    );
                }
            );
        }

        $view->assign('header', $plugin->getToolTitle());
        $view->assign('table', $table->return_table());

        break;
}

$view->display_one_col_template();
