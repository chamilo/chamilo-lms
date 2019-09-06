<?php
/* For licensing terms, see /license.txt */

$form = new FormValidator('edit_category', 'post', $baseUrl."action=edit_category&id={$category->getId()}");
if (api_get_configuration_value('save_titles_as_html')) {
    $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
} else {
    $form->addText('title', get_lang('Title'));
    $form->applyFilter('title', 'trim');
}
$form->addHtmlEditor('description', get_lang('Description'), false, false, ['ToolbarSet' => 'Minimal']);
$form->addButtonUpdate(get_lang('Update'));
$form->setDefaults([
    'title' => $category->getTitle(),
    'description' => $category->getDescription(),
]);

if ($form->validate()) {
    $values = $form->exportValues();

    $category
        ->setTitle($values['title'])
        ->setDescription($values['description']);

    $em->persist($category);
    $em->flush();

    Display::addFlash(
        Display::return_message(get_lang('Updated'), 'success')
    );

    header("Location: $baseUrl");
    exit;
}

$toolName = get_lang('EditCategory');
$interbreadcrumb[] = [
    'name' => get_lang('Portfolio'),
    'url' => $baseUrl,
];
$actions[] = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    $baseUrl
);
$content = $form->returnForm();
