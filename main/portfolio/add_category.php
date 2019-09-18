<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\PortfolioCategory;

$form = new FormValidator('add_category', 'post', "$baseUrl&action=add_category");
if (api_get_configuration_value('save_titles_as_html')) {
    $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
} else {
    $form->addText('title', get_lang('Title'));
    $form->applyFilter('title', 'trim');
}
$form->addHtmlEditor('description', get_lang('Description'), false, false, ['ToolbarSet' => 'Minimal']);
$form->addButtonCreate(get_lang('Create'));

if ($form->validate()) {
    $values = $form->exportValues();

    $category = new PortfolioCategory();
    $category
        ->setTitle($values['title'])
        ->setDescription($values['description'])
        ->setUser($user);

    $em->persist($category);
    $em->flush();

    Display::addFlash(
        Display::return_message(get_lang('CategoryAdded'), 'success')
    );

    header("Location: $baseUrl");
    exit;
}

$toolName = get_lang('AddCategory');
$interbreadcrumb[] = [
    'name' => get_lang('Portfolio'),
    'url' => $baseUrl,
];

$actions[] = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    $baseUrl
);
$content = $form->returnForm();
