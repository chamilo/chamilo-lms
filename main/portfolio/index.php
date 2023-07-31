<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\CoreBundle\Entity\Tag;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

// Make sure we void the course context if we are in the social network section
if (empty($_GET['cidReq'])) {
    $cidReset = true;
}
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

if (api_get_course_int_id()) {
    api_protect_course_script(true);
}

if (false === api_get_configuration_value('allow_portfolio_tool')) {
    api_not_allowed(true);
}

$httpRequest = HttpRequest::createFromGlobals();
$action = $httpRequest->query->get('action', 'list');

// It validates the management of categories will be only for admins
if (in_array($action, ['list_categories', 'add_category', 'edit_category']) && !api_is_platform_admin()) {
    api_not_allowed(true);
}

// It includes the user language for translations
$checkUserLanguage = true;
if ($checkUserLanguage) {
    global $_user;
    $langPath = api_get_path(SYS_LANG_PATH).$_user['language'].'/trad4all.inc.php';
    if (file_exists($langPath)) {
        require_once $langPath;
    }
}

$controller = new \PortfolioController();

$em = Database::getManager();

$htmlHeadXtra[] = api_get_js('portfolio.js');

switch ($action) {
    case 'translate_category':
        $id = $httpRequest->query->getInt('id');
        $languageId = $httpRequest->query->getInt('sub_language');

        /** @var PortfolioCategory $category */
        $category = $em->find('ChamiloCoreBundle:PortfolioCategory', $id);

        if (empty($category)) {
            break;
        }

        $languages = $em
            ->getRepository('ChamiloCoreBundle:Language')
            ->findAllPlatformSubLanguages();

        $controller->translateCategory($category, $languages, $languageId);

        return;
    case 'list_categories':
        $controller->listCategories();

        return;
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
    case 'visibility':
        $id = $httpRequest->query->getInt('id');

        /** @var Portfolio $item */
        $item = $em->find('ChamiloCoreBundle:Portfolio', $id);

        if (empty($item)) {
            break;
        }

        $controller->showHideItem($item);

        return;
    case 'item_visiblity_choose':
        $id = $httpRequest->query->getInt('id');

        /** @var Portfolio $item */
        $item = $em->find(Portfolio::class, $id);

        if (empty($item)) {
            break;
        }

        $controller->itemVisibilityChooser($item);
        break;
    case 'comment_visiblity_choose':
        $id = $httpRequest->query->getInt('id');

        $comment = $em->find(PortfolioComment::class, $id);

        if (empty($comment)) {
            break;
        }

        $controller->commentVisibilityChooser($comment);
        break;
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
    case 'teacher_copy':
        $type = $httpRequest->query->getAlpha('copy');
        $id = $httpRequest->query->getInt('id');

        if ('item' === $type) {
            $item = $em->find(Portfolio::class, $id);

            if (empty($item)) {
                break;
            }

            if ('copy' === $action) {
                $controller->copyItem($item);
            } elseif ('teacher_copy' === $action) {
                $controller->teacherCopyItem($item);
            }
        } elseif ('comment' === $type) {
            $comment = $em->find(PortfolioComment::class, $id);

            if (empty($comment)) {
                break;
            }

            if ('copy' === $action) {
                $controller->copyComment($comment);
            } elseif ('teacher_copy' === $action) {
                $controller->teacherCopyComment($comment);
            }
        }

        break;
    case 'mark_important':
        api_protect_teacher_script();

        $item = $em->find(Portfolio::class, $httpRequest->query->getInt('item'));
        $comment = $em->find(PortfolioComment::class, $httpRequest->query->getInt('id'));

        if (empty($item) || empty($comment)) {
            break;
        }

        $controller->markImportantCommentInItem($item, $comment);

        return;
    case 'details':
        $controller->details($httpRequest);

        return;
    case 'export_pdf':
        $controller->exportPdf($httpRequest);
        break;
    case 'export_zip':
        $controller->exportZip($httpRequest);
        break;
    case 'qualify':
        api_protect_teacher_script();

        if ($httpRequest->query->has('item')) {
            if ('1' !== api_get_course_setting('qualify_portfolio_item')) {
                api_not_allowed(true);
            }

            /** @var Portfolio $item */
            $item = $em->find(
                Portfolio::class,
                $httpRequest->query->getInt('item')
            );

            if (empty($item)) {
                break;
            }

            $controller->qualifyItem($item);
        } elseif ($httpRequest->query->has('comment')) {
            if ('1' !== api_get_course_setting('qualify_portfolio_comment')) {
                api_not_allowed(true);
            }

            /** @var Portfolio $item */
            $comment = $em->find(
                PortfolioComment::class,
                $httpRequest->query->getInt('comment')
            );

            if (empty($comment)) {
                break;
            }

            $controller->qualifyComment($comment);
        }
        break;
    case 'download_attachment':
        $controller->downloadAttachment($httpRequest);
        break;
    case 'delete_attachment':
        $controller->deleteAttachment($httpRequest);
        break;
    case 'highlighted':
        api_protect_teacher_script();

        $id = $httpRequest->query->getInt('id');

        /** @var Portfolio $item */
        $item = $em->find('ChamiloCoreBundle:Portfolio', $id);

        if (empty($item)) {
            break;
        }

        $controller->markAsHighlighted($item);
        break;
    case 'template':
        $id = $httpRequest->query->getInt('id');

        /** @var Portfolio $item */
        $item = $em->find('ChamiloCoreBundle:Portfolio', $id);

        if (empty($item)) {
            break;
        }

        $controller->markAsTemplate($item);
        break;
    case 'template_comment':
        $id = $httpRequest->query->getInt('id');

        $comment = $em->find(PortfolioComment::class, $id);

        if (empty($comment)) {
            break;
        }

        $controller->markAsTemplateComment($comment);
        break;
    case 'edit_comment':
        $id = $httpRequest->query->getInt('id');

        $comment = $em->find(PortfolioComment::class, $id);

        if (!empty($comment)) {
            $controller->editComment($comment);
        }

        break;
    case 'delete_comment':
        $id = $httpRequest->query->getInt('id');

        $comment = $em->find(PortfolioComment::class, $id);

        if (!empty($comment)) {
            $controller->deleteComment($comment);
        }
        break;
    case 'tags':
    case 'edit_tag':
        $controller->listTags($httpRequest);
        break;
    case 'delete_tag':
        $id = $httpRequest->query->getInt('id');

        $tag = $em->find(Tag::class, $id);

        if (empty($tag)) {
            break;
        }

        $controller->deleteTag($tag);
        break;
    case 'list':
    default:
        $controller->index($httpRequest);

        return;
}
