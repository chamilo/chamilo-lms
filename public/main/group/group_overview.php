<?php

/* For licensing terms, see /license.txt */

/**
 * Main page for the group module.
 * This script displays the general group settings,
 * and a list of groups with buttons to view, edit...
 *
 * @author Thomas Depraetere, Hugues Peeters, Christophe Gesche: initial versions
 * @author Bert Vanderkimpen, improved self-unsubscribe for cvs
 * @author Patrick Cool, show group comment under the group name
 * @author Roan Embrechts, initial self-unsubscribe code, code cleaning, virtual course support
 * @author Bart Mollet, code cleaning, use of Display-library, list of courseAdmin-tools, use of GroupManager
 */

use Chamilo\CoreBundle\Component\Utils\ActionIcon;
use Chamilo\CoreBundle\Component\Utils\ToolIcon;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

$nameTools = get_lang('Groups overview');
$course = api_get_course_entity();

$groupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$keyword = $_GET['keyword'] ?? '';

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'export_surveys':
            $extraFieldValue = new ExtraFieldValue('survey');
            $surveyList = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
                'group_id',
                $groupId,
                false,
                false,
                true
            );

            if (!empty($surveyList)) {
                $exportList = [];
                foreach ($surveyList as $data) {
                    $surveyId = $data['item_id'];
                    $surveyData = SurveyManager::get_survey($surveyId, 0, api_get_course_id());
                    if (!empty($surveyData)) {
                        $filename = $surveyData['code'].'.xlsx';
                        $exportList[] = @SurveyUtil::export_complete_report_xls($surveyData, $filename, 0, true);
                    }
                }
                throw new Exception('export_surveys');
                /*if (!empty($exportList)) {
                    $tempZipFile = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().'.zip';
                    $zip = new PclZip($tempZipFile);
                    foreach ($exportList as $file) {
                        $zip->add($file, PCLZIP_OPT_REMOVE_ALL_PATH);
                    }

                    DocumentManager::file_send_for_download(
                        $tempZipFile,
                        true,
                        get_lang('Surveys').'-'.api_get_course_id().'-'.api_get_local_time().'.zip'
                    );
                    unlink($tempZipFile);
                    exit;
                }*/
            }

            Display::addFlash(Display::return_message(get_lang('NoSurveyAvailable')));

            header('Location: '.api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq());
            exit;

            break;
        case 'export_all':
            $data = GroupManager::exportCategoriesAndGroupsToArray(null, true);
            Export::arrayToCsv($data);
            exit;

            break;
        case 'export_pdf':
            $content = GroupManager::getOverview($course, $keyword);
            $pdf = new PDF();
            $extra = '<div style="text-align:center"><h2>'.get_lang('Groups list').'</h2></div>';
            $extra .= '<strong>'.get_lang('Course').': </strong>'.$course->getTitle().' ('.$course->getCode().')';

            $content = $extra.$content;
            $pdf->content_to_pdf($content, null, null, api_get_course_id());

            break;
        case 'export':
            $data = GroupManager::exportCategoriesAndGroupsToArray($groupId, true);
            switch ($_GET['type']) {
                case 'csv':
                    Export::arrayToCsv($data);
                    exit;

                    break;
                case 'xls':
                    if (!empty($data)) {
                        Export::arrayToXls($data);
                        exit;
                    }

                    break;
            }

            break;
    }
}

$interbreadcrumb[] = ['url' => 'group.php?'.api_get_cidreq(), 'name' => get_lang('Groups')];
$origin = api_get_origin();
if ('learnpath' !== $origin) {
    // So we are not in learnpath tool
    if (!api_is_allowed_in_course()) {
        api_not_allowed(true);
    }
    if (!api_is_allowed_to_edit(false, true)) {
        api_not_allowed(true);
    } else {
        Display::display_header($nameTools, 'Group');
        Display::display_introduction_section(TOOL_GROUP);
    }
} else {
    Display::display_reduced_header();
}

$actions = '<a href="group_creation.php?'.api_get_cidreq().'">'.
        Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Create new group(s)')).'</a>';

if ('true' === api_get_setting('allow_group_categories')) {
    $actions .= '<a href="group_category.php?'.api_get_cidreq().'&action=add_category">'.
        Display::getMdiIcon(ActionIcon::CREATE_CATEGORY, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add category')).'</a>';
} else {
    $actions .= '<a href="group_category.php?'.api_get_cidreq().'&id=2">'.
        Display::getMdiIcon(ActionIcon::CONFIGURE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit settings')).'</a>';
}
$actions .= '<a href="import.php?'.api_get_cidreq().'&action=import">'.
    Display::getMdiIcon(ActionIcon::IMPORT_ARCHIVE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Import')).'</a>';

$actions .= '<a href="group_overview.php?'.api_get_cidreq().'&action=export_all&type=csv">'.
    Display::getMdiIcon(ActionIcon::EXPORT_CSV, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export')).'</a>';

$actions .= '<a href="group_overview.php?'.api_get_cidreq().'&action=export&type=xls">'.
Display::getMdiIcon(ActionIcon::EXPORT_SPREADSHEET, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Excel export')).'</a>';

$actions .= '<a href="group_overview.php?'.api_get_cidreq().'&action=export_pdf">'.
    Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export to PDF')).'</a>';

$actions .= '<a href="group.php?'.api_get_cidreq().'">'.
    Display::getMdiIcon(ToolIcon::GROUP, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Groups')).'</a>';

$actions .= '<a href="../user/user.php?'.api_get_cidreq().'">'.
Display::getMdiIcon(ToolIcon::MEMBER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Go to').' '.get_lang('Users')).'</a>';

// Action links
echo Display::toolbarAction('actions', [$actions, GroupManager::getSearchForm()]);
echo GroupManager::getOverview($course, $keyword);

if ('learnpath' !== $origin) {
    Display::display_footer();
}
