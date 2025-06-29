<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Enums\ActionIcon;

$form = new FormValidator('add_category', 'post', "$baseUrl&action=add_category");
if ('true' === api_get_setting('editor.save_titles_as_html')) {
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
        Display::return_message(get_lang('Category added'), 'success')
    );

    header("Location: $baseUrl");
    exit;
}

$toolName = get_lang('Add category');
$interbreadcrumb[] = [
    'name' => get_lang('Portfolio'),
    'url' => $baseUrl,
];

$actions[] = Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
    $baseUrl
);
$content = $form->returnForm();
