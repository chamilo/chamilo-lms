<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as ExtraFieldEntity;
use Chamilo\CoreBundle\Entity\ExtraFieldRelTag;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

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
     * PortfolioController constructor.
     */
    public function __construct()
    {
        $this->em = Database::getManager();

        $this->owner = api_get_user_entity(api_get_user_id());
        $this->course = api_get_course_entity(api_get_course_int_id());
        $this->session = api_get_session_entity(api_get_session_id());

        $cidreq = api_get_cidreq();
        $this->baseUrl = api_get_self().'?'.($cidreq ? $cidreq.'&' : '');
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addCategory()
    {
        global $interbreadcrumb;

        Display::addFlash(
            Display::return_message(get_lang('PortfolioCategoryFieldHelp'), 'info')
        );

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

    public function editCategory(PortfolioCategory $category)
    {
        global $interbreadcrumb;

        if (!$this->categoryBelongToOwner($category)) {
            api_not_allowed(true);
        }

        Display::addFlash(
            Display::return_message(get_lang('PortfolioCategoryFieldHelp'), 'info')
        );

        $form = new FormValidator(
            'edit_category',
            'post',
            $this->baseUrl."action=edit_category&id={$category->getId()}"
        );

        if (api_get_configuration_value('save_titles_as_html')) {
            $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
        } else {
            $form->addText('title', get_lang('Title'));
            $form->applyFilter('title', 'trim');
        }

        $form->addHtmlEditor('description', get_lang('Description'), false, false, ['ToolbarSet' => 'Minimal']);
        $form->addButtonUpdate(get_lang('Update'));
        $form->setDefaults(
            [
                'title' => $category->getTitle(),
                'description' => $category->getDescription(),
            ]
        );

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
        $form->addSelectFromCollection(
            'category',
            [get_lang('Category'), get_lang('PortfolioCategoryFieldHelp')],
            $categories,
            [],
            true
        );

        $extraField = new ExtraField('portfolio');
        $extra = $extraField->addElements($form);

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

            $values['item_id'] = $portfolio->getId();

            $extraFieldValue = new ExtraFieldValue('portfolio');
            $extraFieldValue->saveFieldValues($values);

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

        $this->renderView(
            $content."<script> $(function() { {$extra['jquery_ready_content']} }); </script>",
            get_lang('AddPortfolioItem'),
            $actions
        );
    }

    /**
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

        if ($item->getOrigin()) {
            if (Portfolio::TYPE_ITEM === $item->getOriginType()) {
                $origin = $this->em->find(Portfolio::class, $item->getOrigin());

                $form->addLabel(
                    sprintf(get_lang('PortfolioItemFromXUser'), $origin->getUser()->getCompleteName()),
                    Display::panel($origin->getContent())
                );
            } elseif (Portfolio::TYPE_COMMENT === $item->getOriginType()) {
                $origin = $this->em->find(PortfolioComment::class, $item->getOrigin());

                $form->addLabel(
                    sprintf(get_lang('PortfolioCommentFromXUser'), $origin->getAuthor()->getCompleteName()),
                    Display::panel($origin->getContent())
                );
            }
        }

        $form->addHtmlEditor('content', get_lang('Content'), true, false, ['ToolbarSet' => 'NotebookStudent']);
        $form->addSelectFromCollection(
            'category',
            [get_lang('Category'), get_lang('PortfolioCategoryFieldHelp')],
            $categories,
            [],
            true
        );

        $extraField = new ExtraField('portfolio');
        $extra = $extraField->addElements($form, $item->getId());

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

            $values['item_id'] = $item->getId();

            $extraFieldValue = new ExtraFieldValue('portfolio');
            $extraFieldValue->saveFieldValues($values);

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

        $this->renderView(
            $content."<script> $(function() { {$extra['jquery_ready_content']} }); </script>",
            get_lang('EditPortfolioItem'),
            $actions
        );
    }

    /**
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

    private function getItemsForIndex(
        int $currentUserId,
        bool $listByUser = false,
        FormValidator $frmFilterList = null
    ) {
        if ($this->course) {
            $queryBuilder = $this->em->createQueryBuilder();
            $queryBuilder
                ->select('pi')
                ->from(Portfolio::class, 'pi')
                ->where('pi.course = :course');

            $queryBuilder->setParameter('course', $this->course);

            if ($this->session) {
                $queryBuilder->andWhere('pi.session = :session');
                $queryBuilder->setParameter('session', $this->session);
            } else {
                $queryBuilder->andWhere('pi.session IS NULL');
            }

            if ($listByUser) {
                $queryBuilder->andWhere('pi.user = :user');
                $queryBuilder->setParameter('user', $this->owner);

                if ($currentUserId !== $this->owner->getId()) {
                    $queryBuilder->andWhere('pi.isVisible = TRUE');
                }
            }

            if ($frmFilterList && $frmFilterList->validate()) {
                $values = $frmFilterList->exportValues();

                if (!empty($values['tags'])) {
                    $queryBuilder
                        ->innerJoin(ExtraFieldRelTag::class, 'efrt', Join::WITH, 'efrt.itemId = pi.id')
                        ->innerJoin(ExtraFieldEntity::class, 'ef', Join::WITH, 'ef.id = efrt.fieldId')
                        ->andWhere('ef.extraFieldType = :efType')
                        ->andWhere('ef.variable = :variable')
                        ->andWhere('efrt.tagId IN (:tags)');

                    $queryBuilder->setParameter('efType', ExtraFieldEntity::PORTFOLIO_TYPE);
                    $queryBuilder->setParameter('variable', 'tags');
                    $queryBuilder->setParameter('tags', $values['tags']);
                }

                if (!empty($values['text'])) {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->like('pi.title', ':text'),
                            $queryBuilder->expr()->like('pi.content', ':text')
                        )
                    );

                    $queryBuilder->setParameter('text', '%'.$values['text'].'%');
                }
            }

            $items = $queryBuilder->getQuery()->getResult();
        } else {
            $itemsCriteria = [];
            $itemsCriteria['category'] = null;
            $itemsCriteria['user'] = $this->owner;

            if ($currentUserId !== $this->owner->getId()) {
                $itemsCriteria['isVisible'] = true;
            }

            $items = $this->em
                ->getRepository(Portfolio::class)
                ->findBy($itemsCriteria, ['creationDate' => 'DESC']);
        }

        return $items;
    }

    private function getCategoriesForIndex(int $currentUserId): array
    {
        $categoriesCriteria = [];
        $categoriesCriteria['user'] = $this->owner;

        if ($currentUserId !== $this->owner->getId()) {
            $categoriesCriteria['isVisible'] = true;
        }

        return $this->em
            ->getRepository(PortfolioCategory::class)
            ->findBy($categoriesCriteria);
    }

    public function index(HttpRequest $httpRequest)
    {
        $listByUser = false;

        if ($httpRequest->query->has('user')) {
            $this->owner = api_get_user_entity($httpRequest->query->getInt('user'));

            if (empty($this->owner)) {
                api_not_allowed(true);
            }

            $listByUser = true;
        }

        $currentUserId = api_get_user_id();

        $actions = [];

        if ($currentUserId == $this->owner->getId()) {
            $actions[] = Display::url(
                Display::return_icon('add.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
                $this->baseUrl.'action=add_item'
            );
            $actions[] = Display::url(
                Display::return_icon('folder.png', get_lang('AddCategory'), [], ICON_SIZE_MEDIUM),
                $this->baseUrl.'action=add_category'
            );
        }

        $frmStudentList = null;
        $frmTagList = null;

        $categories = [];

        if ($this->course) {
            $frmTagList = $this->createFormTagFilter();
            $frmStudentList = $this->createFormStudentFilter($listByUser);
            $frmStudentList->setDefaults(['user' => $this->owner->getId()]);
        } else {
            $categories = $this->getCategoriesForIndex($currentUserId);
        }

        $items = $this->getItemsForIndex($currentUserId, $listByUser, $frmTagList);

        $template = new Template(null, false, false, false, false, false, false);
        $template->assign('list_by_user', $listByUser);
        $template->assign('user', $this->owner);
        $template->assign('course', $this->course);
        $template->assign('session', $this->session);
        $template->assign('portfolio', $categories);
        $template->assign('uncategorized_items', $items);
        $template->assign('frm_student_list', $this->course ? $frmStudentList->returnForm() : '');
        $template->assign('frm_tag_list', $this->course ? $frmTagList->returnForm() : '');

        $layout = $template->get_template('portfolio/list.html.twig');
        $content = $template->fetch($layout);

        $this->renderView($content, get_lang('Portfolio'), $actions);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function view(Portfolio $item)
    {
        global $interbreadcrumb;

        $form = $this->createCommentForm($item);

        $commentsRepo = $this->em->getRepository(PortfolioComment::class);

        $query = $commentsRepo->createQueryBuilder('comment')
            ->where('comment.item = :item')
            ->orderBy('comment.root, comment.lft', 'ASC')
            ->setParameter('item', $item)
            ->getQuery();

        $clockIcon = Display::returnFontAwesomeIcon('clock-o', '', true);

        $commentsHtml = $commentsRepo->buildTree(
            $query->getArrayResult(),
            [
                'decorate' => true,
                'rootOpen' => '<ul class="media-list">',
                'rootClose' => '</ul>',
                'childOpen' => function ($node) use ($commentsRepo) {
                    /** @var PortfolioComment $comment */
                    $comment = $commentsRepo->find($node['id']);
                    $author = $comment->getAuthor();

                    $userPicture = UserManager::getUserPicture(
                        $comment->getAuthor()->getId(),
                        USER_IMAGE_SIZE_SMALL,
                        null,
                        [
                            'picture_uri' => $author->getPictureUri(),
                            'email' => $author->getEmail(),
                        ]
                    );

                    return '<li class="media">
                        <div class="media-left">
                            <img class="media-object thumbnail" src="'.$userPicture.'" alt="'.$author->getCompleteName().'">
                        </div>
                        <div class="media-body">';
                },
                'childClose' => '</div></li>',
                'nodeDecorator' => function ($node) use ($commentsRepo, $clockIcon, $item) {
                    /** @var PortfolioComment $comment */
                    $comment = $commentsRepo->find($node['id']);

                    $commentActions = Display::url(
                        Display::return_icon('discuss.png', get_lang('ReplyToThisComment')),
                        '#',
                        [
                            'data-comment' => htmlspecialchars(
                                json_encode(['id' => $comment->getId()])
                            ),
                            'role' => 'button',
                            'class' => 'btn-reply-to',
                        ]
                    );
                    $commentActions .= PHP_EOL;
                    $commentActions .= Display::url(
                        Display::return_icon('copy.png', get_lang('CopyToMyPortfolio')),
                        $this->baseUrl.http_build_query(
                            [
                                'action' => 'copy',
                                'copy' => 'comment',
                                'id' => $comment->getId(),
                            ]
                        )
                    );

                    $isAllowedToEdit = api_is_allowed_to_edit();

                    if ($isAllowedToEdit) {
                        $commentActions .= Display::url(
                            Display::return_icon('copy.png', get_lang('CopyToStudentPortfolio')),
                            $this->baseUrl.http_build_query(
                                [
                                    'action' => 'teacher_copy',
                                    'copy' => 'comment',
                                    'id' => $comment->getId(),
                                ]
                            )
                        );

                        if ($comment->isImportant()) {
                            $commentActions .= Display::url(
                                Display::return_icon('drawing-pin.png', get_lang('UnmarkCommentAsImportant')),
                                $this->baseUrl.http_build_query(
                                    [
                                        'action' => 'mark_important',
                                        'item' => $item->getId(),
                                        'id' => $comment->getId(),
                                    ]
                                )
                            );
                        } else {
                            $commentActions .= Display::url(
                                Display::return_icon('drawing-pin.png', get_lang('MarkCommentAsImportant')),
                                $this->baseUrl.http_build_query(
                                    [
                                        'action' => 'mark_important',
                                        'item' => $item->getId(),
                                        'id' => $comment->getId(),
                                    ]
                                )
                            );
                        }
                    }

                    $nodeHtml = '<p class="media-heading h4">'.PHP_EOL
                        .$comment->getAuthor()->getCompleteName().'</>'.PHP_EOL.'<small>'.$clockIcon.PHP_EOL
                        .Display::dateToStringAgoAndLongDate($comment->getDate()).'</small>'.PHP_EOL;

                    if ($comment->isImportant() &&
                        ($this->itemBelongToOwner($comment->getItem()) || $isAllowedToEdit)
                    ) {
                        $nodeHtml .= '<span class="label label-warning origin-style">'.get_lang('CommentMarkedAsImportant')
                            .'</span>'.PHP_EOL;
                    }

                    $nodeHtml .= '</p>'.PHP_EOL
                        .'<div class="pull-right">'.$commentActions.'</div>'.$comment->getContent().PHP_EOL;

                    return $nodeHtml;
                },
            ]
        );

        $origin = null;

        if ($item->getOrigin() !== null) {
            if ($item->getOriginType() === Portfolio::TYPE_ITEM) {
                $origin = $this->em->find(Portfolio::class, $item->getOrigin());
            } elseif ($item->getOriginType() === Portfolio::TYPE_COMMENT) {
                $origin = $this->em->find(PortfolioComment::class, $item->getOrigin());
            }
        }

        $template = new Template(null, false, false, false, false, false, false);
        $template->assign('baseurl', $this->baseUrl);
        $template->assign('item', $item);
        $template->assign('origin', $origin);
        $template->assign('comments', $commentsHtml);
        $template->assign('form', $form);

        $layout = $template->get_template('portfolio/view.html.twig');
        $content = $template->fetch($layout);

        $interbreadcrumb[] = ['name' => get_lang('Portfolio'), 'url' => $this->baseUrl];

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl
        );

        $this->renderView($content, $item->getTitle(), $actions, false);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function copyItem(Portfolio $originItem)
    {
        $currentTime = api_get_utc_datetime(null, false, true);

        $portfolio = new Portfolio();
        $portfolio
            ->setIsVisible(false)
            ->setTitle(
                sprintf(get_lang('PortfolioItemFromXUser'), $originItem->getUser()->getCompleteName())
            )
            ->setContent('')
            ->setUser($this->owner)
            ->setOrigin($originItem->getId())
            ->setOriginType(Portfolio::TYPE_ITEM)
            ->setCourse($this->course)
            ->setSession($this->session)
            ->setCreationDate($currentTime)
            ->setUpdateDate($currentTime);

        $this->em->persist($portfolio);
        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('PortfolioItemAdded'), 'success')
        );

        header("Location: $this->baseUrl".http_build_query(['action' => 'edit_item', 'id' => $portfolio->getId()]));
        exit;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function copyComment(PortfolioComment $originComment)
    {
        $currentTime = api_get_utc_datetime(null, false, true);

        $portfolio = new Portfolio();
        $portfolio
            ->setIsVisible(false)
            ->setTitle(
                sprintf(get_lang('PortfolioCommentFromXUser'), $originComment->getAuthor()->getCompleteName())
            )
            ->setContent('')
            ->setUser($this->owner)
            ->setOrigin($originComment->getId())
            ->setOriginType(Portfolio::TYPE_COMMENT)
            ->setCourse($this->course)
            ->setSession($this->session)
            ->setCreationDate($currentTime)
            ->setUpdateDate($currentTime);

        $this->em->persist($portfolio);
        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('PortfolioItemAdded'), 'success')
        );

        header("Location: $this->baseUrl".http_build_query(['action' => 'edit_item', 'id' => $portfolio->getId()]));
        exit;
    }

    public function teacherCopyItem(Portfolio $originItem)
    {
        $actionParams = http_build_query(['action' => 'teacher_copy', 'copy' => 'item', 'id' => $originItem->getId()]);

        $form = new FormValidator('teacher_copy_portfolio', 'post', $this->baseUrl.$actionParams);

        if (api_get_configuration_value('save_titles_as_html')) {
            $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
        } else {
            $form->addText('title', get_lang('Title'));
            $form->applyFilter('title', 'trim');
        }

        $form->addLabel(
            sprintf(get_lang('PortfolioItemFromXUser'), $originItem->getUser()->getCompleteName()),
            Display::panel($originItem->getContent())
        );
        $form->addHtmlEditor('content', get_lang('Content'), true, false, ['ToolbarSet' => 'NotebookStudent']);

        $urlParams = http_build_query(
            [
                'a' => 'search_user_by_course',
                'course_id' => $this->course->getId(),
                'session_id' => $this->session ? $this->session->getId() : 0,
            ]
        );
        $form->addSelectAjax(
            'students',
            get_lang('Students'),
            [],
            [
                'url' => api_get_path(WEB_AJAX_PATH)."course.ajax.php?$urlParams",
                'multiple' => true,
            ]
        );
        $form->addRule('students', get_lang('ThisFieldIsRequired'), 'required');
        $form->addButtonCreate(get_lang('Save'));

        $toolName = get_lang('CopyToStudentPortfolio');
        $content = $form->returnForm();

        if ($form->validate()) {
            $values = $form->exportValues();

            $currentTime = api_get_utc_datetime(null, false, true);

            foreach ($values['students'] as $studentId) {
                $owner = api_get_user_entity($studentId);

                $portfolio = new Portfolio();
                $portfolio
                    ->setIsVisible(false)
                    ->setTitle($values['title'])
                    ->setContent($values['content'])
                    ->setUser($owner)
                    ->setOrigin($originItem->getId())
                    ->setOriginType(Portfolio::TYPE_ITEM)
                    ->setCourse($this->course)
                    ->setSession($this->session)
                    ->setCreationDate($currentTime)
                    ->setUpdateDate($currentTime);

                $this->em->persist($portfolio);
            }

            $this->em->flush();

            Display::addFlash(
                Display::return_message(get_lang('PortfolioItemAddedToStudents'), 'success')
            );

            header("Location: $this->baseUrl");
            exit;
        }

        $this->renderView($content, $toolName);
    }

    public function teacherCopyComment(PortfolioComment $originComment)
    {
        $actionParams = http_build_query(['action' => 'teacher_copy', 'copy' => 'comment', 'id' => $originComment->getId()]);

        $form = new FormValidator('teacher_copy_portfolio', 'post', $this->baseUrl.$actionParams);

        if (api_get_configuration_value('save_titles_as_html')) {
            $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
        } else {
            $form->addText('title', get_lang('Title'));
            $form->applyFilter('title', 'trim');
        }

        $form->addLabel(
            sprintf(get_lang('PortfolioCommentFromXUser'), $originComment->getAuthor()->getCompleteName()),
            Display::panel($originComment->getContent())
        );
        $form->addHtmlEditor('content', get_lang('Content'), true, false, ['ToolbarSet' => 'NotebookStudent']);

        $urlParams = http_build_query(
            [
                'a' => 'search_user_by_course',
                'course_id' => $this->course->getId(),
                'session_id' => $this->session ? $this->session->getId() : 0,
            ]
        );
        $form->addSelectAjax(
            'students',
            get_lang('Students'),
            [],
            [
                'url' => api_get_path(WEB_AJAX_PATH)."course.ajax.php?$urlParams",
                'multiple' => true,
            ]
        );
        $form->addRule('students', get_lang('ThisFieldIsRequired'), 'required');
        $form->addButtonCreate(get_lang('Save'));

        $toolName = get_lang('CopyToStudentPortfolio');
        $content = $form->returnForm();

        if ($form->validate()) {
            $values = $form->exportValues();

            $currentTime = api_get_utc_datetime(null, false, true);

            foreach ($values['students'] as $studentId) {
                $owner = api_get_user_entity($studentId);

                $portfolio = new Portfolio();
                $portfolio
                    ->setIsVisible(false)
                    ->setTitle($values['title'])
                    ->setContent($values['content'])
                    ->setUser($owner)
                    ->setOrigin($originComment->getId())
                    ->setOriginType(Portfolio::TYPE_COMMENT)
                    ->setCourse($this->course)
                    ->setSession($this->session)
                    ->setCreationDate($currentTime)
                    ->setUpdateDate($currentTime);

                $this->em->persist($portfolio);
            }

            $this->em->flush();

            Display::addFlash(
                Display::return_message(get_lang('PortfolioItemAddedToStudents'), 'success')
            );

            header("Location: $this->baseUrl");
            exit;
        }

        $this->renderView($content, $toolName);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function markImportantCommentInItem(Portfolio $item, PortfolioComment $comment)
    {
        if ($comment->getItem()->getId() !== $item->getId()) {
            api_not_allowed(true);
        }

        $comment->setIsImportant(
            !$comment->isImportant()
        );

        $this->em->persist($comment);
        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('CommentMarkedAsImportant'), 'success')
        );

        header("Location: $this->baseUrl".http_build_query(['action' => 'view', 'id' => $item->getId()]));
        exit;
    }

    /**
     * @param bool $showHeader
     */
    private function renderView(string $content, string $toolName, array $actions = [], $showHeader = true)
    {
        global $this_section;

        $this_section = $this->course ? SECTION_COURSES : SECTION_SOCIAL;

        $view = new Template($toolName);

        if ($showHeader) {
            $view->assign('header', $toolName);
        }

        $actionsStr = '';

        if ($this->course) {
            $actionsStr .= Display::return_introduction_section(TOOL_PORTFOLIO);
        }

        if ($actions) {
            $actions = implode(PHP_EOL, $actions);

            $actionsStr .= Display::toolbarAction('portfolio-toolbar', [$actions]);
        }

        $view->assign('baseurl', $this->baseUrl);
        $view->assign('actions', $actionsStr);

        $view->assign('content', $content);
        $view->display_one_col_template();
    }

    private function categoryBelongToOwner(PortfolioCategory $category): bool
    {
        if ($category->getUser()->getId() != $this->owner->getId()) {
            return false;
        }

        return true;
    }

    private function itemBelongToOwner(Portfolio $item): bool
    {
        if ($item->getUser()->getId() != $this->owner->getId()) {
            return false;
        }

        return true;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createCommentForm(Portfolio $item): string
    {
        $formAction = $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()]);

        $form = new FormValidator('frm_comment', 'post', $formAction);
        $form->addHtmlEditor('content', get_lang('Comments'), true, false, ['ToolbarSet' => 'Minimal']);
        $form->addHidden('item', $item->getId());
        $form->addHidden('parent', 0);
        $form->applyFilter('content', 'trim');
        $form->addButtonSave(get_lang('Save'));

        if ($form->validate()) {
            $values = $form->exportValues();

            $parentComment = $this->em->find(PortfolioComment::class, $values['parent']);

            $comment = new PortfolioComment();
            $comment
                ->setAuthor($this->owner)
                ->setParent($parentComment)
                ->setContent($values['content'])
                ->setDate(api_get_utc_datetime(null, false, true))
                ->setItem($item);

            $this->em->persist($comment);
            $this->em->flush();

            Display::addFlash(
                Display::return_message(get_lang('CommentAdded'), 'success')
            );

            header("Location: $formAction");
            exit;
        }

        return $form->returnForm();
    }

    private function createFormStudentFilter(bool $listByUser = false): FormValidator
    {
        $frmStudentList = new FormValidator(
            'frm_student_list',
            'get',
            $this->baseUrl,
            '',
            [],
            FormValidator::LAYOUT_BOX
        );
        $slctStudentOptions = [];

        if ($listByUser) {
            $slctStudentOptions[$this->owner->getId()] = $this->owner->getCompleteName();
        }

        $urlParams = http_build_query(
            [
                'a' => 'search_user_by_course',
                'course_id' => $this->course->getId(),
                'session_id' => $this->session ? $this->session->getId() : 0,
            ]
        );

        $frmStudentList->addSelectAjax(
            'user',
            get_lang('SelectLearnerPortfolio'),
            $slctStudentOptions,
            [
                'url' => api_get_path(WEB_AJAX_PATH)."course.ajax.php?$urlParams",
                'placeholder' => get_lang('SearchStudent')
            ]
        );
        $frmStudentList->addHtml('<hr>');
        $frmStudentList->addHtml(
            Display::url(
                get_lang('SeeMyPortfolio'),
                $this->baseUrl.http_build_query(['user' => api_get_user_id()])
            )
        );

        return $frmStudentList;
    }

    private function createFormTagFilter(): FormValidator
    {
        $extraField = new ExtraField('portfolio');
        $tagFieldInfo = $extraField->get_handler_field_info_by_tags('tags');

        $chbxTagOptions = array_map(
            function (array $tagOption) {
                return $tagOption['tag'];
            },
            $tagFieldInfo['options']
        );

        $frmTagList = new FormValidator('frm_tag_list',
            'get',
            $this->baseUrl,
            '',
            [],
            FormValidator::LAYOUT_BOX
        );
        $frmTagList->addCheckBoxGroup('tags', $tagFieldInfo['display_text'], $chbxTagOptions);
        $frmTagList->addText('text', get_lang('Search'), false)->setIcon('search');
        $frmTagList->applyFilter('text', 'trim');
        $frmTagList->addHtml('<br>');
        $frmTagList->addButtonFilter(get_lang('Filter'));

        if ($this->course) {
            $frmTagList->addHidden('cidReq', $this->course->getCode());
            $frmTagList->addHidden('id_session', $this->session ? $this->session->getId() : 0);
            $frmTagList->addHidden('gidReq', 0);
            $frmTagList->addHidden('gradebook', 0);
            $frmTagList->addHidden('origin', '');
        }

        return $frmTagList;
    }
}
