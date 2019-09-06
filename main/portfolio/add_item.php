<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Portfolio;

$categories = $em
    ->getRepository('ChamiloCoreBundle:PortfolioCategory')
    ->findBy([
        'user' => $user,
    ]);

$form = new FormValidator('add_portfolio', 'post', $baseUrl.'action=add_item');
if (api_get_configuration_value('save_titles_as_html')) {
    $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
} else {
    $form->addText('title', get_lang('Title'));
    $form->applyFilter('title', 'trim');
}
$form->addHtmlEditor('content', get_lang('Content'), true, false, ['ToolbarSet' => 'NotebookStudent']);
$form->addSelectFromCollection('category', get_lang('Category'), $categories, [], true);
$form->addButtonCreate(get_lang('Create'));

if ($form->validate()) {
    $values = $form->exportValues();
    $currentTime = new DateTime(
        api_get_utc_datetime(),
        new DateTimeZone('UTC')
    );

    $portfolio = new Portfolio();
    $portfolio
        ->setTitle($values['title'])
        ->setContent($values['content'])
        ->setUser($user)
        ->setCourse($course)
        ->setSession($session)
        ->setCategory(
            $em->find('ChamiloCoreBundle:PortfolioCategory', $values['category'])
        )
        ->setCreationDate($currentTime)
        ->setUpdateDate($currentTime);

    $em->persist($portfolio);
    $em->flush();

    Display::addFlash(
        Display::return_message(get_lang('PortfolioItemAdded'), 'success')
    );

    header("Location: $baseUrl");
    exit;
}

$toolName = get_lang('AddPortfolioItem');
$interbreadcrumb[] = [
    'name' => get_lang('Portfolio'),
    'url' => $baseUrl,
];

$actions[] = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    $baseUrl
);
$content = $form->returnForm();
