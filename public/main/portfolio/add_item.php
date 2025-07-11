<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\PortfolioItemAddedEvent;
use Chamilo\CoreBundle\Framework\Container;

$categories = $em
    ->getRepository(PortfolioCategory::class)
    ->findBy([
        'user' => $user,
    ]);

$form = new FormValidator('add_portfolio', 'post', $baseUrl.'action=add_item');
if ('true' === api_get_setting('editor.save_titles_as_html')) {
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
            $em->find(PortfolioCategory::class, $values['category'])
        )
        ->setCreationDate($currentTime)
        ->setUpdateDate($currentTime);

    $em->persist($portfolio);
    $em->flush();

    Container::getEventDispatcher()->dispatch(
        new PortfolioItemAddedEvent(['portfolio' => $portfolio]),
        Events::PORTFOLIO_ITEM_ADDED
    );

    Display::addFlash(
        Display::return_message(get_lang('Portfolio item added'), 'success')
    );

    header("Location: $baseUrl");
    exit;
}

$toolName = get_lang('Add item to portfolio');
$interbreadcrumb[] = [
    'name' => get_lang('Portfolio'),
    'url' => $baseUrl,
];

$actions[] = Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
    $baseUrl
);
$content = $form->returnForm();
