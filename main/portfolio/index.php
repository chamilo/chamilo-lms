<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

// Make sure we void the course context if we are in the social network section
if (empty($_GET['cidReq'])) {
    $cidReset = true;
}
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

if (false === api_get_configuration_value('allow_portfolio_tool')) {
    api_not_allowed(true);
}

$controller = new \PortfolioController();

$em = Database::getManager();
$httpRequest = HttpRequest::createFromGlobals();

$action = $httpRequest->query->getAlpha('action', 'list');

switch ($action) {
    case 'add_category':
        $controller->addCategory();

        return;
    case 'edit_category':
        $id = $httpRequest->query->getInt('id');

        /** @var PortfolioCategory $category */
        $category = $em->find('ChamiloCoreBundle:PortfolioCategory', $id);

        if (empty($category)) {
            break;
        }

        $controller->editCategory($category);
        return;
    case 'hide_category':
    case 'show_category':
        $id = $httpRequest->query->getInt('id');

        $category = $em->find('ChamiloCoreBundle:PortfolioCategory', $id);

        if (empty($category)) {
            break;
        }

        $controller->showHideCategory($category);
        return;
    case 'delete_category':
        $id = $httpRequest->query->getInt('id');

        /** @var PortfolioCategory $category */
        $category = $em->find('ChamiloCoreBundle:PortfolioCategory', $id);

        if (empty($category)) {
            break;
        }

        $controller->deleteCategory($category);
        return;
    case 'add_item':
        $controller->addItem();
        return;
    case 'edit_item':
        $id = $httpRequest->query->getInt('id');

        /** @var Portfolio $item */
        $item = $em->find('ChamiloCoreBundle:Portfolio', $id);

        if (empty($item)) {
            break;
        }

        $controller->editItem($item);
        return;
    case 'hide_item':
    case 'show_item':
        $id = $httpRequest->query->getInt('id');

        /** @var Portfolio $item */
        $item = $em->find('ChamiloCoreBundle:Portfolio', $id);

        if (empty($item)) {
            break;
        }

        $controller->showHideItem($item);
        return;
    case 'delete_item':
        $id = $httpRequest->query->getInt('id');

        /** @var Portfolio $item */
        $item = $em->find('ChamiloCoreBundle:Portfolio', $id);

        if (empty($item)) {
            break;
        }

        $controller->deleteItem($item);
        return;
    case 'view':
        $id = $httpRequest->query->getInt('id');

        /** @var Portfolio $item */
        $item = $em->find('ChamiloCoreBundle:Portfolio', $id);

        if (empty($item)) {
            break;
        }

        $controller->view($item);
        return;
    case 'copy':
        $type = $httpRequest->query->getAlpha('copy');
        $id = $httpRequest->query->getInt('id');

        if ('item' === $type) {
            $item = $em->find(Portfolio::class, $id);

            if (empty($item)) {
                break;
            }

            $controller->copyItem($item);
        } elseif ('comment' === $type) {
            $comment = $em->find(PortfolioComment::class, $id);

            if (empty($comment)) {
                break;
            }

            $controller->copyComment($comment);
        }

        break;
    case 'list':
    default:
        $controller->index();
        return;
}
