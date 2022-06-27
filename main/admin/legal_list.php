<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Repository\LegalRepository;

/**
 * Sessions list script.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$tool_name = get_lang('TermsAndConditions');
Display::display_header($tool_name);

$parameters['sec_token'] = Security::get_token();

// action menu
echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/legal_add.php">';
echo Display::return_icon(
    'edit.png',
    get_lang('EditTermsAndConditions')
);
echo get_lang('EditTermsAndConditions').'</a>&nbsp;&nbsp;';
echo '</div>';

$em = Database::getManager();
/** @var LegalRepository $legalTermsRepo */
$legalTermsRepo = $em->getRepository('ChamiloCoreBundle:Legal');
$legalCount = $legalTermsRepo->countAllActiveLegalTerms();
$languages = api_get_languages();
$available_languages = count($languages['folder']);
if ($legalCount != $available_languages) {
    echo Display::return_message(get_lang('YouShouldCreateTermAndConditionsForAllAvailableLanguages'), 'warning');
}

$table = new SortableTable('conditions', 'count_mask', 'get_legal_data_mask', 2);
$table->set_additional_parameters($parameters);
$table->set_header(0, get_lang('Version'), false, 'width="15px"');
$table->set_header(1, get_lang('Language'), false, 'width="30px"');
$table->set_header(2, get_lang('Content'), false);
$table->set_header(3, get_lang('Changes'), false, 'width="60px"');
$table->set_header(4, get_lang('Type'), false, 'width="60px"');
$table->set_header(5, get_lang('Date'), false, 'width="50px"');
$table->display();

// this 2 "mask" function are here just because the SortableTable
function get_legal_data_mask($id, $params = null, $row = null)
{
    return LegalManager::get_legal_data($id, $params, $row);
}

function count_mask()
{
    return LegalManager::count();
}

Display::display_footer();
