<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\PortfolioItemEditedEvent;
use Chamilo\CoreBundle\Framework\Container;

$categories = $em
    ->getRepository(PortfolioCategory::class)
    ->findBy([
        'user' => $user,
    ]);

$form = new FormValidator('edit_portfolio', 'post', $baseUrl."action=edit_item&id={$item->getId()}");
if ('true' === api_get_setting('editor.save_titles_as_html')) {
    $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
} else {
    $form->addText('title', get_lang('Title'));
    $form->applyFilter('title', 'trim');
}
$form->addHtmlEditor('content', get_lang('Content'), true, false, ['ToolbarSet' => 'NotebookStudent']);
$form->addSelectFromCollection('category', get_lang('Category'), $categories, [], true, '__toString');
$form->addButtonUpdate(get_lang('Update'));
$form->setDefaults([
    'title' => $item->getTitle(),
    'content' => $item->getContent(),
    'category' => $item->getCategory() ? $item->getCategory()->getId() : '',
]);

if ($form->validate()) {
    $values = $form->exportValues();
    $currentTime = new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));

    $item
        ->setTitle($values['title'])
        ->setContent($values['content'])
        ->setUpdateDate($currentTime)
        ->setCategory(
            $em->find(PortfolioCategory::class, $values['category'])
        );

    $em->persist($item);
    $em->flush();

    Container::getEventDispatcher()->dispatch(
        new PortfolioItemEditedEvent(['portfolio' => $item]),
        Events::PORTFOLIO_ITEM_EDITED
    );

    Display::addFlash(
        Display::return_message(get_lang('Item updated'), 'success')
    );

    header("Location: $baseUrl");
    exit;
}

$toolName = get_lang('Edit portfolio item');
$interbreadcrumb[] = [
    'name' => get_lang('Portfolio'),
    'url' => $baseUrl,
];
$actions[] = Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
    $baseUrl
);
$content = $form->returnForm();
