<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioCategory;

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

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

switch ($action) {
    case 'add_category':
        $controller->addCategory();

        return;
    case 'edit_category':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        /** @var PortfolioCategory $category */
        $category = $em->find('ChamiloCoreBundle:PortfolioCategory', $id);

        if (empty($category)) {
            break;
        }

        $controller->editCategory($category);
        return;
    case 'hide_category':
    case 'show_category':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        $category = $em->find('ChamiloCoreBundle:PortfolioCategory', $id);

        if (empty($category)) {
            break;
        }

        $controller->showHideCategory($category);
        return;
    case 'delete_category':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

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
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        /** @var Portfolio $item */
        $item = $em->find('ChamiloCoreBundle:Portfolio', $id);

        if (empty($item)) {
            break;
        }

        $controller->editItem($item);
        return;
    case 'hide_item':
    case 'show_item':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        /** @var Portfolio $item */
        $item = $em->find('ChamiloCoreBundle:Portfolio', $id);

        if (empty($item)) {
            break;
        }

        $controller->showHideItem($item);
        return;
    case 'delete_item':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        /** @var Portfolio $item */
        $item = $em->find('ChamiloCoreBundle:Portfolio', $id);

        if (empty($item)) {
            break;
        }

        $controller->deleteItem($item);
        return;
    case 'list':
    default:
        $controller->index();
        return;
}
