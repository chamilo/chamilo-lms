<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = DictionaryPlugin::create();

$table = 'plugin_dictionary';
$sql = "SELECT * FROM $table ORDER BY TERM";
$result = Database::query($sql);
$terms = Database::store_result($result, 'ASSOC');

$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : 'add';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$term = null;
if (!empty($id)) {
    $sql = "SELECT * FROM $table WHERE id = $id";
    $result = Database::query($sql);
    $term = Database::fetch_array($result, 'ASSOC');
    if (empty($term)) {
        api_not_allowed(true);
    }
}

$form = new FormValidator('dictionary', 'post', api_get_self().'?action='.$action.'&id='.$id);
$form->addText('term', $plugin->get_lang('Term'), true);
$form->addTextarea('definition', $plugin->get_lang('Definition'), [], true);
//$form->addHtmlEditor('definition', get_lang('Definition'), true);
$form->addButtonSave(get_lang('Save'));

switch ($action) {
    case 'add':
        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $params = [
                'term' => $values['term'],
                'definition' => $values['definition'],
            ];
            $result = Database::insert($table, $params);
            if ($result) {
                Display::addFlash(Display::return_message(get_lang('Added')));
            }
            header('Location: '.api_get_self());
            exit;
        }
        break;
    case 'edit':
        $form->setDefaults($term);
        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $params = [
                'term' => $values['term'],
                'definition' => $values['definition'],
            ];
            Database::update($table, $params, ['id = ?' => $id]);
            Display::addFlash(Display::return_message(get_lang('Updated')));

            header('Location: '.api_get_self());
            exit;
        }
        break;
    case 'delete':
        if (!empty($term)) {
            Database::delete($table, ['id = ?' => $id]);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
            header('Location: '.api_get_self());
            exit;
        }
        break;
}

$tpl = new Template($plugin->get_lang('plugin_title'));
$tpl->assign('terms', $terms);
$tpl->assign('form', $form->returnForm());
$content = $tpl->fetch('/'.$plugin->get_name().'/view/terms.html.twig');
// Assign into content
$tpl->assign('content', $content);
// Display
$tpl->display_one_col_template();
