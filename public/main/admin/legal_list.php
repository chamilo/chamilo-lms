<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Legal;
use Chamilo\CoreBundle\Repository\LegalRepository;

/**
 * Sessions list script.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$tool_name = get_lang('Terms and Conditions');
Display::display_header($tool_name);

$parameters['sec_token'] = Security::get_token();

// action menu
echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/legal_add.php">';
echo Display::return_icon(
    'edit.png',
    get_lang('EditTerms and Conditions')
);
echo get_lang('EditTerms and Conditions').'</a>&nbsp;&nbsp;';
echo '</div>';

$em = Database::getManager();
/** @var LegalRepository $legalTermsRepo */
$legalTermsRepo = $em->getRepository(Legal::class);
$legalCount = $legalTermsRepo->countAllActiveLegalTerms();
$languages = api_get_languages();
$availableLanguages = count($languages);
if ($legalCount != $availableLanguages) {
    echo Display::return_message(get_lang('You should create the "Term and Conditions" for all the available languages.'), 'warning');
}

$table = new SortableTable('conditions', 'countMask', 'getLegalDataMask', 2);
$table->set_additional_parameters($parameters);
$table->set_header(0, get_lang('Version'), false, 'width="15px"');
$table->set_header(1, get_lang('Language'), false, 'width="30px"');
$table->set_header(2, get_lang('Content'), false);
$table->set_header(3, get_lang('Changes'), false, 'width="60px"');
$table->set_header(4, get_lang('Type'), false, 'width="60px"');
$table->set_header(5, get_lang('Date'), false, 'width="50px"');
$table->display();

// this 2 "mask" function are here just because the SortableTable
function getLegalDataMask($id, $params = null, $row = null)
{
    return LegalManager::get_legal_data($id, $params, $row);
}

function countMask()
{
    return LegalManager::count();
}

Display :: display_footer();
