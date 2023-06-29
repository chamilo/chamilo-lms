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

$course = api_get_course_entity(api_get_course_int_id());
$session = api_get_session_entity(api_get_session_id());

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

        $form->addButtonUpdate(get_lang('Add'));
        $form->addHidden('action', 'add');

        if ($form->validate()) {
            $values = $form->exportValues();

            $zipFileInfo = $_FILES['file'];

            try {
                $importer = H5pPackageImporter::create($zipFileInfo, $course);
                $packageFile = $importer->import();

                // Get the h5p.json and content.json contents
                $h5pJson  = H5pPackageTools::getJson($packageFile.DIRECTORY_SEPARATOR.'h5p.json');
                $contentJson = H5pPackageTools::getJson(
                    $packageFile.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'content.json'
                );
                if ($h5pJson && $contentJson) {

                    if (H5pPackageTools::checkPackageIntegrity($h5pJson, $packageFile)) {

                        H5pPackageTools::storeH5pPackage($packageFile, $h5pJson, $course, $session);

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
    case 'edit':
        if (!$isAllowedToEdit) {
            api_not_allowed(true);
        }

        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            api_get_self()
        );

        $embedId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        if (!$embedId) {
            break;
        }

        /** @var Embed|null $embed */
        $embed = $h5pRepo->find($embedId);

        if (!$embed) {
            Display::addFlash(Display::return_message($plugin->get_lang('ContentNotFound'), 'danger'));

            break;
        }

        $form = new FormValidator('frm_edit');
        $form->addText('title', get_lang('Title'), true);
        $form->addDateRangePicker('range', get_lang('DateRange'));
        $form->addTextarea('html_code', $plugin->get_lang('HtmlCode'), ['rows' => 5], true);
        $form->addButtonUpdate(get_lang('Edit'));
        $form->addHidden('id', $embed->getId());
        $form->addHidden('action', 'edit');

        if ($form->validate()) {
            $values = $form->exportValues();

            $startDate = api_get_utc_datetime($values['range_start'], false, true);
            $endDate = api_get_utc_datetime($values['range_end'], false, true);

            $embed
                ->setTitle($values['title'])
                ->setDisplayStartDate($startDate)
                ->setDisplayEndDate($endDate)
                ->setHtmlCode($values['html_code']);

            $em->persist($embed);
            $em->flush();

            Display::addFlash(
                Display::return_message(get_lang('Updated'), 'success')
            );

            header('Location: '.api_get_self());
            exit;
        }

        $form->setDefaults(
            [
                'title' => $embed->getTitle(),
                'range' => api_get_local_time($embed->getDisplayStartDate())
                    .' / '
                    .api_get_local_time($embed->getDisplayEndDate()),
                'html_code' => $embed->getHtmlCode(),
            ]
        );

        $view->assign('header', $plugin->get_lang('EditEmbeddable'));
        $view->assign('form', $form->returnForm());
        break;
    case 'delete':
        $h5pImportId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        if (!$h5pImportId) {
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
        foreach ($h5pImports as $h5pImport) {
            $data = [
                $h5pImport->getName(),
                $h5pImport->getPath(),
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
        $table->set_header(1, get_lang('Path'));

        if ($isAllowedToEdit) {

            $table->set_header(
                2,
                get_lang('Actions'),
                false,
                'th-header text-right',
                ['class' => 'text-right']
            );
        }
        $table->set_column_filter(
            2,
            function (H5pImport $value) use ($isAllowedToEdit, $plugin) {
                $actions = [];

                $actions[] = Display::url(
                    Display::return_icon('external_link.png', get_lang('View')),
                    $plugin->getViewUrl($value)
                );

                if ($isAllowedToEdit) {

                    $actions[] = Display::url(
                        Display::return_icon('delete.png', get_lang('Delete')),
                        api_get_self().'?action=delete&id='.$value->getIid()
                    );
                }

                return implode(PHP_EOL, $actions);
            }
        );
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
