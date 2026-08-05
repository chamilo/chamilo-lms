<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/DictionaryPlugin.php';

api_protect_admin_script();

$plugin = DictionaryPlugin::create();

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

$plugin->ensureTableExists();

$action = isset($_GET['action']) ? Security::remove_XSS((string) $_GET['action']) : 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!in_array($action, ['list', 'add', 'edit', 'delete'], true)) {
    $action = 'list';
}

$currentUrl = api_get_self();

if ('delete' === $action) {
    if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')) {
        api_not_allowed(true);
    }

    if (false === Security::check_token('post')) {
        Security::clear_token();
        Display::addFlash(Display::return_message(get_lang('Invalid security token'), 'error'));
        header('Location: '.$currentUrl);
        exit;
    }

    if ($plugin->deleteTerm($id)) {
        Display::addFlash(Display::return_message(get_lang('Deleted')));
    } else {
        Display::addFlash(Display::return_message(get_lang('The item could not be deleted'), 'error'));
    }

    Security::clear_token();
    header('Location: '.$currentUrl);
    exit;
}

$term = null;

if ('edit' === $action) {
    $term = $plugin->getTermById($id);

    if (empty($term)) {
        api_not_allowed(true);
    }
}

$form = null;
$formHtml = '';

if (in_array($action, ['add', 'edit'], true)) {
    $formUrl = api_get_self().'?action='.$action;

    if ('edit' === $action) {
        $formUrl .= '&id='.$id;
    }

    $form = new FormValidator('dictionary', 'post', $formUrl);
    $form->addHeader('add' === $action ? $plugin->get_lang('AddTerm') : $plugin->get_lang('EditTerm'));
    $form->addText('term', $plugin->get_lang('Term'), true);
    $form->addTextarea('definition', $plugin->get_lang('Definition'), [], true);

    if ('add' === $action) {
        $form->addButtonCreate($plugin->get_lang('AddTerm'));
    } else {
        $form->addButtonUpdate($plugin->get_lang('UpdateTerm'));
    }

    if ('edit' === $action && !empty($term)) {
        $form->setDefaults($term);
    }

    if ($form->validate()) {
        if (false === Security::check_token('post')) {
            Security::clear_token();
            Display::addFlash(Display::return_message(get_lang('Invalid security token'), 'error'));
            header('Location: '.$currentUrl);
            exit;
        }

        $values = $form->getSubmitValues();
        $submittedTerm = isset($values['term']) ? (string) $values['term'] : '';
        $submittedDefinition = isset($values['definition']) ? (string) $values['definition'] : '';

        if ('add' === $action) {
            $result = $plugin->addTerm($submittedTerm, $submittedDefinition);
            $message = $result ? get_lang('Added') : get_lang('The item could not be added');
        } else {
            $result = $plugin->updateTerm($id, $submittedTerm, $submittedDefinition);
            $message = $result ? get_lang('Update successful') : get_lang('The item could not be updated');
        }

        Display::addFlash(Display::return_message($message, $result ? 'success' : 'error'));
        Security::clear_token();
        header('Location: '.$currentUrl);
        exit;
    }

    $token = Security::get_token();
    $form->addElement('hidden', 'sec_token');
    $form->setConstants(['sec_token' => $token]);
    $formHtml = $form->returnForm();
}

$terms = $plugin->getTerms();
$deleteToken = 'list' === $action ? Security::get_token() : '';

$tpl = new Template($plugin->get_lang('plugin_title'));
$tpl->assign('mode', in_array($action, ['add', 'edit'], true) ? 'form' : 'list');
$tpl->assign('terms', $terms);
$tpl->assign('form', $formHtml);
$tpl->assign('delete_token', $deleteToken);
$tpl->assign('admin_url', api_get_self());
$tpl->assign('add_url', api_get_self().'?action=add');
$tpl->assign('back_url', api_get_self());

$content = $tpl->fetch('/'.$plugin->get_name().'/view/terms.html.twig');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
