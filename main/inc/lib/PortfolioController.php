<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioCategory;

/**
 * Class PortfolioController.
 */
class PortfolioController
{
    /**
     * @var string
     */
    public $baseUrl;
    /**
     * @var \Chamilo\CoreBundle\Entity\Course|null
     */
    private $course;
    /**
     * @var \Chamilo\CoreBundle\Entity\Session|null
     */
    private $session;
    /**
     * @var \Chamilo\UserBundle\Entity\User
     */
    private $owner;
    /**
     * @var int
     */
    private $currentUserId;
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;
    /**
     * @var bool
     */
    private $allowEdit;

    /**
     * PortfolioController constructor.
     */
    public function __construct()
    {
        $this->em = Database::getManager();

        $this->currentUserId = api_get_user_id();
        $ownerId = isset($_GET['user']) ? (int) $_GET['user'] : $this->currentUserId;
        $this->owner = api_get_user_entity($ownerId);
        $this->course = api_get_course_entity(api_get_course_int_id());
        $this->session = api_get_session_entity(api_get_session_id());

        $cidreq = api_get_cidreq();
        $this->baseUrl = api_get_self().'?'.($cidreq ? $cidreq.'&' : '');

        $this->allowEdit = $this->currentUserId == $this->owner->getId();

        if (isset($_GET['preview'])) {
            $this->allowEdit = false;
        }
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addCategory()
    {
        global $interbreadcrumb;

        $form = new FormValidator('add_category', 'post', "{$this->baseUrl}&action=add_category");

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
                ->setUser($this->owner);

            $this->em->persist($category);
            $this->em->flush();

            Display::addFlash(
                Display::return_message(get_lang('CategoryAdded'), 'success')
            );

            header("Location: {$this->baseUrl}");
            exit;
        }

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl
        );

        $content = $form->returnForm();

        $this->renderView($content, get_lang('AddCategory'), $actions);
    }

    /**
     * @param \Chamilo\CoreBundle\Entity\PortfolioCategory $category
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function editCategory(PortfolioCategory $category)
    {
        global $interbreadcrumb;

        if (!$this->categoryBelongToOwner($category)) {
            api_not_allowed(true);
        }

        $form = new FormValidator('edit_category', 'post', $this->baseUrl."action=edit_category&id={$category->getId()}");

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

            $this->em->persist($category);
            $this->em->flush();

            Display::addFlash(
                Display::return_message(get_lang('Updated'), 'success')
            );

            header("Location: $this->baseUrl");
            exit;
        }

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl
        );

        $content = $form->returnForm();

        return $this->renderView($content, get_lang('EditCategory'), $actions);
    }

    /**
     * @param \Chamilo\CoreBundle\Entity\PortfolioCategory $category
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function showHideCategory(PortfolioCategory $category)
    {
        if (!$this->categoryBelongToOwner($category)) {
            api_not_allowed(true);
        }

        $category->setIsVisible(!$category->isVisible());

        $this->em->persist($category);
        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('VisibilityChanged'), 'success')
        );

        header("Location: $this->baseUrl");
        exit;
    }

    /**
     * @param \Chamilo\CoreBundle\Entity\PortfolioCategory $category
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteCategory(PortfolioCategory $category)
    {
        if (!$this->categoryBelongToOwner($category)) {
            api_not_allowed(true);
        }

        $this->em->remove($category);
        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('CategoryDeleted'), 'success')
        );

        header("Location: $this->baseUrl");
        exit;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function addItem()
    {
        global $interbreadcrumb;

        $categories = $this->em
            ->getRepository('ChamiloCoreBundle:PortfolioCategory')
            ->findBy(['user' => $this->owner]);

        $form = new FormValidator('add_portfolio', 'post', $this->baseUrl.'action=add_item');

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
                ->setUser($this->owner)
                ->setCourse($this->course)
                ->setSession($this->session)
                ->setCategory(
                    $this->em->find('ChamiloCoreBundle:PortfolioCategory', $values['category'])
                )
                ->setCreationDate($currentTime)
                ->setUpdateDate($currentTime);

            $this->em->persist($portfolio);
            $this->em->flush();

            Display::addFlash(
                Display::return_message(get_lang('PortfolioItemAdded'), 'success')
            );

            header("Location: $this->baseUrl");
            exit;
        }

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl
        );

        $content = $form->returnForm();

        $this->renderView($content, get_lang('AddPortfolioItem'), $actions);
    }

    /**
     * @param \Chamilo\CoreBundle\Entity\Portfolio $item
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function editItem(Portfolio $item)
    {
        global $interbreadcrumb;

        if (!$this->itemBelongToOwner($item)) {
            api_not_allowed(true);
        }

        $categories = $this->em
            ->getRepository('ChamiloCoreBundle:PortfolioCategory')
            ->findBy(['user' => $this->owner]);

        $form = new FormValidator('edit_portfolio', 'post', $this->baseUrl."action=edit_item&id={$item->getId()}");

        if (api_get_configuration_value('save_titles_as_html')) {
            $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
        } else {
            $form->addText('title', get_lang('Title'));
            $form->applyFilter('title', 'trim');
        }

        $form->addHtmlEditor('content', get_lang('Content'), true, false, ['ToolbarSet' => 'NotebookStudent']);
        $form->addSelectFromCollection('category', get_lang('Category'), $categories, [], true, '__toString');
        $form->addButtonUpdate(get_lang('Update'));
        $form->setDefaults(
            [
                'title' => $item->getTitle(),
                'content' => $item->getContent(),
                'category' => $item->getCategory() ? $item->getCategory()->getId() : '',
            ]
        );

        if ($form->validate()) {
            $values = $form->exportValues();
            $currentTime = new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));

            $item
                ->setTitle($values['title'])
                ->setContent($values['content'])
                ->setUpdateDate($currentTime)
                ->setCategory(
                    $this->em->find('ChamiloCoreBundle:PortfolioCategory', $values['category'])
                );

            $this->em->persist($item);
            $this->em->flush();

            Display::addFlash(
                Display::return_message(get_lang('ItemUpdated'), 'success')
            );

            header("Location: $this->baseUrl");
            exit;
        }

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];
        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl
        );
        $content = $form->returnForm();

        $this->renderView($content, get_lang('EditPortfolioItem'), $actions);
    }

    /**
     * @param \Chamilo\CoreBundle\Entity\Portfolio $item
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function showHideItem(Portfolio $item)
    {
        if (!$this->itemBelongToOwner($item)) {
            api_not_allowed(true);
        }

        $item->setIsVisible(
            !$item->isVisible()
        );

        $this->em->persist($item);
        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('VisibilityChanged'), 'success')
        );

        header("Location: $this->baseUrl");
        exit;
    }

    /**
     * @param \Chamilo\CoreBundle\Entity\Portfolio $item
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteItem(Portfolio $item)
    {
        if (!$this->itemBelongToOwner($item)) {
            api_not_allowed(true);
        }

        $this->em->remove($item);
        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('ItemDeleted'), 'success')
        );

        header("Location: $this->baseUrl");
        exit;
    }

    /**
     * @throws \Exception
     */
    public function index()
    {
        $actions = [];

        if ($this->currentUserId == $this->owner->getId()) {
            if ($this->allowEdit) {
                $actions[] = Display::url(
                    Display::return_icon('add.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
                    $this->baseUrl.'action=add_item'
                );
                $actions[] = Display::url(
                    Display::return_icon('folder.png', get_lang('AddCategory'), [], ICON_SIZE_MEDIUM),
                    $this->baseUrl.'action=add_category'
                );
                $actions[] = Display::url(
                    Display::return_icon('shared_setting.png', get_lang('Preview'), [], ICON_SIZE_MEDIUM),
                    $this->baseUrl.'preview=&user='.$this->owner->getId()
                );
            } else {
                $actions[] = Display::url(
                    Display::return_icon('shared_setting_na.png', get_lang('Preview'), [], ICON_SIZE_MEDIUM),
                    $this->baseUrl
                );
            }
        }

        $form = new FormValidator('a');
        $form->addUserAvatar('user', get_lang('User'), 'medium');
        $form->setDefaults(['user' => $this->owner]);

        $criteria = [];

        if (!$this->allowEdit) {
            $criteria['isVisible'] = true;
        }

        $categories = $this->em
            ->getRepository(PortfolioCategory::class)
            ->findBy($criteria);

        if ($this->course) {
            $criteria['course'] = $this->course;
            $criteria['session'] = $this->session;
        } else {
            $criteria['user'] = $this->owner;
        }

        $criteria['category'] = null;

        $items = $this->em
            ->getRepository(Portfolio::class)
            ->findBy($criteria, ['creationDate' => 'DESC']);

        $template = new Template(null, false, false, false, false, false, false);
        $template->assign('user', $this->owner);
        $template->assign('course', $this->course);
        $template->assign('session', $this->session);
        $template->assign('allow_edit', $this->allowEdit);
        $template->assign('portfolio', $categories);
        $template->assign('uncategorized_items', $items);

        $layout = $template->get_template('portfolio/list.html.twig');
        $content = $template->fetch($layout);

        $this->renderView($content, get_lang('Portfolio'), $actions);
    }

    /**
     * @param \Chamilo\CoreBundle\Entity\PortfolioCategory $category
     *
     * @return bool
     */
    private function categoryBelongToOwner(PortfolioCategory $category): bool
    {
        if ($category->getUser()->getId() != $this->owner->getId()) {
            return false;
        }

        return true;
    }

    /**
     * @param \Chamilo\CoreBundle\Entity\Portfolio $item
     *
     * @return bool
     */
    private function itemBelongToOwner(Portfolio $item): bool
    {
        if ($this->session && $item->getSession()->getId() != $this->session->getId()) {
            return false;
        }

        if ($this->course && $item->getCourse()->getId() != $this->course->getId()) {
            return false;
        }

        if ($item->getUser()->getId() != $this->owner->getId()) {
            return false;
        }

        return true;
    }

    /**
     * @param string $content
     * @param string $toolName
     * @param array  $actions
     */
    private function renderView(string $content, string $toolName, array $actions = [])
    {
        global $this_section;

        $this_section = $this->course ? SECTION_COURSES : SECTION_SOCIAL;

        $view = new Template($toolName);
        $view->assign('header', $toolName);

        $actionsStr = '';

        if ($this->course) {
            $actionsStr .= Display::return_introduction_section(TOOL_PORTFOLIO);
        }

        if ($actions) {
            $actions = implode(PHP_EOL, $actions);

            $actionsStr .= Display::toolbarAction('portfolio-toolbar', [$actions]);
        }

        $view->assign('actions', $actionsStr);

        $view->assign('content', $content);
        $view->display_one_col_template();
    }
}
