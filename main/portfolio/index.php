<?php
/**
 * @license see /license.txt
 */
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\UserBundle\Entity\User;

// Make sure we void the course context if we are in the social network section
if (empty($_GET['cidReq'])) {
    $cidReset = true;
}
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

if (false === api_get_configuration_value('allow_portfolio_tool')) {
    api_not_allowed(true);
}

$em = Database::getManager();

$currentUserId = api_get_user_id();
$userId = isset($_GET['user']) ? (int) $_GET['user'] : $currentUserId;
/** @var User $user */
$user = api_get_user_entity($userId);
/** @var Course $course */
$course = $em->find('ChamiloCoreBundle:Course', api_get_course_int_id());
/** @var Session $session */
$session = $em->find('ChamiloCoreBundle:Session', api_get_session_id());

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$cidreq = api_get_cidreq();
$baseUrl = api_get_self().'?'.($cidreq ? $cidreq.'&' : '');
$allowEdit = $currentUserId == $user->getId();

if (isset($_GET['preview'])) {
    $allowEdit = false;
}

$toolName = get_lang('Portfolio');
$actions = [];
$content = '';

/**
 * Check if the portfolio item or category is valid for the current user.
 *
 * @param $item
 *
 * @return bool
 */
$isValid = function ($item) use ($user, $course, $session) {
    if (!$item) {
        return false;
    }

    if (get_class($item) == Portfolio::class) {
        if ($session && $item->getSession()->getId() != $session->getId()) {
            return false;
        }

        if ($course && $item->getCourse()->getId() != $course->getId()) {
            return false;
        }
    }

    if ($item->getUser()->getId() != $user->getId()) {
        return false;
    }

    return true;
};

switch ($action) {
    case 'add_category':
        require 'add_category.php';
        break;
    case 'edit_category':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if (!$id) {
            break;
        }

        /** @var PortfolioCategory $category */
        $category = $em->find('ChamiloCoreBundle:PortfolioCategory', $id);

        if (!$isValid($category)) {
            api_not_allowed(true);
        }

        require 'edit_category.php';
        break;
    case 'hide_category':
    case 'show_category':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if (!$id) {
            break;
        }

        /** @var PortfolioCategory $category */
        $category = $em->find('ChamiloCoreBundle:PortfolioCategory', $id);

        if (!$isValid($category)) {
            api_not_allowed(true);
        }

        $category->setIsVisible(!$category->isVisible());

        $em->persist($category);
        $em->flush();

        Display::addFlash(
            Display::return_message(get_lang('VisibilityChanged'), 'success')
        );

        header("Location: $baseUrl");
        exit;
    case 'delete_category':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if (!$id) {
            break;
        }

        /** @var PortfolioCategory $category */
        $category = $em->find('ChamiloCoreBundle:PortfolioCategory', $id);

        if (!$isValid($category)) {
            api_not_allowed(true);
        }

        $em->remove($category);
        $em->flush();

        Display::addFlash(
            Display::return_message(get_lang('CategoryDeleted'), 'success')
        );

        header("Location: $baseUrl");
        exit;
    case 'add_item':
        require 'add_item.php';
        break;
    case 'edit_item':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if (!$id) {
            break;
        }

        /** @var CPortfolio $item */
        $item = $em->find('ChamiloCoreBundle:Portfolio', $id);

        if (!$isValid($item)) {
            api_not_allowed(true);
        }

        require 'edit_item.php';
        break;
    case 'hide_item':
    case 'show_item':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if (!$id) {
            break;
        }

        /** @var Portfolio $item */
        $item = $em->find('ChamiloCoreBundle:Portfolio', $id);

        if (!$isValid($item)) {
            api_not_allowed(true);
        }

        $item->setIsVisible(!$item->isVisible());

        $em->persist($item);
        $em->flush();

        Display::addFlash(
            Display::return_message(get_lang('VisibilityChanged'), 'success')
        );

        header("Location: $baseUrl");
        exit;
    case 'delete_item':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if (!$id) {
            break;
        }

        /** @var Portfolio $item */
        $item = $em->find('ChamiloCoreBundle:Portfolio', $id);

        if (!$isValid($item)) {
            api_not_allowed(true);
        }

        $em->remove($item);
        $em->flush();

        Display::addFlash(
            Display::return_message(get_lang('ItemDeleted'), 'success')
        );

        header("Location: $baseUrl");
        exit;
    case 'list':
    default:
        require 'list.php';
}

/*
 * View
 */
$this_section = $course ? SECTION_COURSES : SECTION_SOCIAL;

$actions = implode(PHP_EOL, $actions);

Display::display_header($toolName);
Display::display_introduction_section(TOOL_PORTFOLIO);
echo $actions ? Display::toolbarAction('portfolio-toolbar', [$actions]) : '';
echo Display::page_header($toolName);
echo $content;
Display::display_footer();
