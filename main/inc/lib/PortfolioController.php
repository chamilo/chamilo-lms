<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\CoreBundle\Entity\ExtraField as ExtraFieldEntity;
use Chamilo\CoreBundle\Entity\ExtraFieldRelTag;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioAttachment;
use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Filesystem\Filesystem;
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
     * @var CourseEntity|null
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

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
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

        $this->renderView($content, get_lang('EditCategory'), $actions);
    }

    /**
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
     * @throws \Exception
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

        $this->addAttachmentsFieldToForm($form);

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

            $this->processAttachments(
                $form,
                $portfolio->getUser(),
                $portfolio->getId(),
                PortfolioAttachment::TYPE_ITEM
            );

            $hook = HookPortfolioItemAdded::create();
            $hook->setEventData(['portfolio' => $portfolio]);
            $hook->notifyItemAdded();

            if (1 == api_get_course_setting('email_alert_teachers_new_post')) {
                if ($this->session) {
                    $messageCourseTitle = "{$this->course->getTitle()} ({$this->session->getName()})";

                    $teachers = SessionManager::getCoachesByCourseSession(
                        $this->session->getId(),
                        $this->course->getId()
                    );
                    $userIdListToSend = array_values($teachers);
                } else {
                    $messageCourseTitle = $this->course->getTitle();

                    $teachers = CourseManager::get_teacher_list_from_course_code($this->course->getCode());

                    $userIdListToSend = array_keys($teachers);
                }

                $messageSubject = sprintf(get_lang('PortfolioAlertNewPostSubject'), $messageCourseTitle);

                foreach ($userIdListToSend as $userIdToSend) {
                    $messageContent = sprintf(
                        get_lang('PortfolioAlertNewPostContent'),
                        $this->owner->getCompleteName(),
                        $messageCourseTitle,
                        $this->baseUrl.http_build_query(['action' => 'view', 'id' => $portfolio->getId()])
                    );

                    MessageManager::send_message_simple($userIdToSend, $messageSubject, $messageContent, 0, false, false, [], false);
                }
            }

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
     * @throws \Exception
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
                    Display::panel(
                        Security::remove_XSS($origin->getContent())
                    )
                );
            } elseif (Portfolio::TYPE_COMMENT === $item->getOriginType()) {
                $origin = $this->em->find(PortfolioComment::class, $item->getOrigin());

                $form->addLabel(
                    sprintf(get_lang('PortfolioCommentFromXUser'), $origin->getAuthor()->getCompleteName()),
                    Display::panel(
                        Security::remove_XSS($origin->getContent())
                    )
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

        $attachmentList = $this->generateAttachmentList($item, false);

        if (!empty($attachmentList)) {
            $form->addLabel(get_lang('AttachmentFiles'), $attachmentList);
        }

        $this->addAttachmentsFieldToForm($form);

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

            $this->processAttachments(
                $form,
                $item->getUser(),
                $item->getId(),
                PortfolioAttachment::TYPE_ITEM
            );

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

    /**
     * @throws \Exception
     */
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
            $actions[] = Display::url(
                Display::return_icon('waiting_list.png', get_lang('PortfolioDetails'), [], ICON_SIZE_MEDIUM),
                $this->baseUrl.'action=details'
            );
        } else {
            $actions[] = Display::url(
                Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                $this->baseUrl
            );
        }

        $frmStudentList = null;
        $frmTagList = null;

        $categories = [];

        if ($this->course) {
            $frmTagList = $this->createFormTagFilter($listByUser);
            $frmStudentList = $this->createFormStudentFilter($listByUser);
            $frmStudentList->setDefaults(['user' => $this->owner->getId()]);
        } else {
            $categories = $this->getCategoriesForIndex($currentUserId);
        }

        $items = $this->getItemsForIndex($listByUser, $frmTagList);

        $template = new Template(null, false, false, false, false, false, false);
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
     * @throws \Doctrine\ORM\TransactionRequiredException
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

                    return '<li class="media" id="comment-'.$node['id'].'">
                        <div class="media-left"><img class="media-object thumbnail" src="'.$userPicture.'" alt="'
                        .$author->getCompleteName().'"></div>
                        <div class="media-body">';
                },
                'childClose' => '</div></li>',
                'nodeDecorator' => function ($node) use ($commentsRepo, $clockIcon, $item) {
                    /** @var PortfolioComment $comment */
                    $comment = $commentsRepo->find($node['id']);

                    $commentActions[] = Display::url(
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
                    $commentActions[] = Display::url(
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
                        $commentActions[] = Display::url(
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
                            $commentActions[] = Display::url(
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
                            $commentActions[] = Display::url(
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

                        if ($this->course && '1' === api_get_course_setting('qualify_portfolio_comment')) {
                            $commentActions[] = Display::url(
                                Display::return_icon('quiz.png', get_lang('QualifyThisPortfolioComment')),
                                $this->baseUrl.http_build_query(
                                    [
                                        'action' => 'qualify',
                                        'comment' => $comment->getId(),
                                    ]
                                )
                            );
                        }
                    }

                    $nodeHtml = '<p class="media-heading h4">'.PHP_EOL
                        .$comment->getAuthor()->getCompleteName().PHP_EOL.'<small>'.$clockIcon.PHP_EOL
                        .Display::dateToStringAgoAndLongDate($comment->getDate()).'</small>'.PHP_EOL;

                    if ($comment->isImportant()
                        && ($this->itemBelongToOwner($comment->getItem()) || $isAllowedToEdit)
                    ) {
                        $nodeHtml .= '<span class="label label-warning origin-style">'
                            .get_lang('CommentMarkedAsImportant')
                            .'</span>'.PHP_EOL;
                    }

                    $nodeHtml .= '</p>'.PHP_EOL
                        .'<div class="pull-right">'.implode(PHP_EOL, $commentActions).'</div>'
                        .Security::remove_XSS($comment->getContent())
                        .PHP_EOL;

                    $nodeHtml .= $this->generateAttachmentList($comment);

                    return $nodeHtml;
                },
            ]
        );

        $template = new Template(null, false, false, false, false, false, false);
        $template->assign('baseurl', $this->baseUrl);
        $template->assign('item', $item);
        $template->assign('item_content', $this->generateItemContent($item));
        $template->assign('comments', $commentsHtml);
        $template->assign('form', $form);
        $template->assign('attachment_list', $this->generateAttachmentList($item));

        $layout = $template->get_template('portfolio/view.html.twig');
        $content = $template->fetch($layout);

        $interbreadcrumb[] = ['name' => get_lang('Portfolio'), 'url' => $this->baseUrl];

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl
        );

        $this->renderView($content, Security::remove_XSS($item->getTitle()), $actions, false);
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

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
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
            Display::panel(
                Security::remove_XSS($originItem->getContent())
            )
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

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function teacherCopyComment(PortfolioComment $originComment)
    {
        $actionParams = http_build_query(
            [
                'action' => 'teacher_copy',
                'copy' => 'comment',
                'id' => $originComment->getId(),
            ]
        );

        $form = new FormValidator('teacher_copy_portfolio', 'post', $this->baseUrl.$actionParams);

        if (api_get_configuration_value('save_titles_as_html')) {
            $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
        } else {
            $form->addText('title', get_lang('Title'));
            $form->applyFilter('title', 'trim');
        }

        $form->addLabel(
            sprintf(get_lang('PortfolioCommentFromXUser'), $originComment->getAuthor()->getCompleteName()),
            Display::panel(
                Security::remove_XSS($originComment->getContent())
            )
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
     * @throws \Exception
     */
    public function details(HttpRequest $httpRequest)
    {
        $isAllowedToFilterStudent = $this->course && api_is_allowed_to_edit();

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl
        );
        $actions[] = Display::url(
            Display::return_icon('pdf.png', get_lang('ExportMyPortfolioDataPdf'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl.http_build_query(['action' => 'export_pdf'])
        );
        $actions[] = Display::url(
            Display::return_icon('save_pack.png', get_lang('ExportMyPortfolioDataZip'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl.http_build_query(['action' => 'export_zip'])
        );

        $frmStudent = null;

        if ($isAllowedToFilterStudent) {
            if ($httpRequest->query->has('user')) {
                $this->owner = api_get_user_entity($httpRequest->query->getInt('user'));

                if (empty($this->owner)) {
                    api_not_allowed(true);
                }

                $actions[1] = Display::url(
                    Display::return_icon('pdf.png', get_lang('ExportMyPortfolioDataPdf'), [], ICON_SIZE_MEDIUM),
                    $this->baseUrl.http_build_query(['action' => 'export_pdf', 'user' => $this->owner->getId()])
                );
                $actions[2] = Display::url(
                    Display::return_icon('save_pack.png', get_lang('ExportMyPortfolioDataZip'), [], ICON_SIZE_MEDIUM),
                    $this->baseUrl.http_build_query(['action' => 'export_zip', 'user' => $this->owner->getId()])
                );
            }

            $frmStudent = new FormValidator('frm_student_list', 'get');
            $slctStudentOptions = [];
            $slctStudentOptions[$this->owner->getId()] = $this->owner->getCompleteName();

            $urlParams = http_build_query(
                [
                    'a' => 'search_user_by_course',
                    'course_id' => $this->course->getId(),
                    'session_id' => $this->session ? $this->session->getId() : 0,
                ]
            );

            $frmStudent->addSelectAjax(
                'user',
                get_lang('SelectLearnerPortfolio'),
                $slctStudentOptions,
                [
                    'url' => api_get_path(WEB_AJAX_PATH)."course.ajax.php?$urlParams",
                    'placeholder' => get_lang('SearchStudent'),
                ]
            );
            $frmStudent->setDefaults(['user' => $this->owner->getId()]);
            $frmStudent->addHidden('action', 'details');
            $frmStudent->addHidden('cidReq', $this->course->getCode());
            $frmStudent->addHidden('id_session', $this->session ? $this->session->getId() : 0);
            $frmStudent->addButtonFilter(get_lang('Filter'));
        }

        $itemsRepo = $this->em->getRepository(Portfolio::class);
        $commentsRepo = $this->em->getRepository(PortfolioComment::class);

        $getItemsTotalNumber = function () use ($itemsRepo) {
            $qb = $itemsRepo->createQueryBuilder('i');
            $qb
                ->select('COUNT(i)')
                ->where('i.user = :user')
                ->setParameter('user', $this->owner);

            if ($this->course) {
                $qb
                    ->andWhere('i.course = :course')
                    ->setParameter('course', $this->course);

                if ($this->session) {
                    $qb
                        ->andWhere('i.session = :session')
                        ->setParameter('session', $this->session);
                } else {
                    $qb->andWhere('i.session IS NULL');
                }
            }

            return $qb->getQuery()->getSingleScalarResult();
        };
        $getItemsData = function ($from, $limit, $columnNo, $orderDirection) use ($itemsRepo) {
            $qb = $itemsRepo->createQueryBuilder('item')
                ->where('item.user = :user')
                ->leftJoin('item.category', 'category')
                ->leftJoin('item.course', 'course')
                ->leftJoin('item.session', 'session')
                ->setParameter('user', $this->owner);

            if ($this->course) {
                $qb
                    ->andWhere('item.course = :course_id')
                    ->setParameter('course_id', $this->course);

                if ($this->session) {
                    $qb
                        ->andWhere('item.session = :session')
                        ->setParameter('session', $this->session);
                } else {
                    $qb->andWhere('item.session IS NULL');
                }
            }

            if (0 == $columnNo) {
                $qb->orderBy('item.title', $orderDirection);
            } elseif (1 == $columnNo) {
                $qb->orderBy('item.creationDate', $orderDirection);
            } elseif (2 == $columnNo) {
                $qb->orderBy('item.updateDate', $orderDirection);
            } elseif (3 == $columnNo) {
                $qb->orderBy('category.title', $orderDirection);
            } elseif (5 == $columnNo) {
                $qb->orderBy('item.score', $orderDirection);
            } elseif (6 == $columnNo) {
                $qb->orderBy('course.title', $orderDirection);
            } elseif (7 == $columnNo) {
                $qb->orderBy('session.name', $orderDirection);
            }

            $qb->setFirstResult($from)->setMaxResults($limit);

            return array_map(
                function (Portfolio $item) {
                    $category = $item->getCategory();

                    $row = [];
                    $row[] = $item;
                    $row[] = $item->getCreationDate();
                    $row[] = $item->getUpdateDate();
                    $row[] = $category ? $item->getCategory()->getTitle() : null;
                    $row[] = $item->getComments()->count();
                    $row[] = $item->getScore();

                    if (!$this->course) {
                        $row[] = $item->getCourse();
                        $row[] = $item->getSession();
                    }

                    return $row;
                },
                $qb->getQuery()->getResult()
            );
        };

        $portfolioItemColumnFilter = function (Portfolio $item) {
            return Display::url(
                Security::remove_XSS($item->getTitle()),
                $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()])
            );
        };
        $convertFormatDateColumnFilter = function (DateTime $date) {
            return api_convert_and_format_date($date);
        };

        $tblItems = new SortableTable('tbl_items', $getItemsTotalNumber, $getItemsData, 1, 20, 'DESC');
        $tblItems->set_additional_parameters(['action' => 'details', 'user' => $this->owner->getId()]);
        $tblItems->set_header(0, get_lang('Title'));
        $tblItems->set_column_filter(0, $portfolioItemColumnFilter);
        $tblItems->set_header(1, get_lang('CreationDate'), true, [], ['class' => 'text-center']);
        $tblItems->set_column_filter(1, $convertFormatDateColumnFilter);
        $tblItems->set_header(2, get_lang('LastUpdate'), true, [], ['class' => 'text-center']);
        $tblItems->set_column_filter(2, $convertFormatDateColumnFilter);
        $tblItems->set_header(3, get_lang('Category'));
        $tblItems->set_header(4, get_lang('Comments'), false, [], ['class' => 'text-right']);
        $tblItems->set_header(5, get_lang('Score'), true, [], ['class' => 'text-right']);

        if (!$this->course) {
            $tblItems->set_header(6, get_lang('Course'));
            $tblItems->set_header(7, get_lang('Session'));
        }

        $getCommentsTotalNumber = function () use ($commentsRepo) {
            $qb = $commentsRepo->createQueryBuilder('c');
            $qb
                ->select('COUNT(c)')
                ->where('c.author = :author')
                ->setParameter('author', $this->owner);

            if ($this->course) {
                $qb
                    ->innerJoin('c.item', 'i')
                    ->andWhere('i.course = :course')
                    ->setParameter('course', $this->course);

                if ($this->session) {
                    $qb
                        ->andWhere('i.session = :session')
                        ->setParameter('session', $this->session);
                } else {
                    $qb->andWhere('i.session IS NULL');
                }
            }

            return $qb->getQuery()->getSingleScalarResult();
        };
        $getCommentsData = function ($from, $limit, $columnNo, $orderDirection) use ($commentsRepo) {
            $qb = $commentsRepo->createQueryBuilder('comment');
            $qb
                ->where('comment.author = :user')
                ->innerJoin('comment.item', 'item')
                ->setParameter('user', $this->owner);

            if ($this->course) {
                $qb
                    ->innerJoin('comment.item', 'i')
                    ->andWhere('item.course = :course')
                    ->setParameter('course', $this->course);

                if ($this->session) {
                    $qb
                        ->andWhere('item.session = :session')
                        ->setParameter('session', $this->session);
                } else {
                    $qb->andWhere('item.session IS NULL');
                }
            }

            if (0 == $columnNo) {
                $qb->orderBy('comment.content', $orderDirection);
            } elseif (1 == $columnNo) {
                $qb->orderBy('comment.date', $orderDirection);
            } elseif (2 == $columnNo) {
                $qb->orderBy('item.title', $orderDirection);
            } elseif (3 == $columnNo) {
                $qb->orderBy('comment.score', $orderDirection);
            }

            $qb->setFirstResult($from)->setMaxResults($limit);

            return array_map(
                function (PortfolioComment $comment) {
                    return [
                        $comment,
                        $comment->getDate(),
                        $comment->getItem(),
                        $comment->getScore(),
                    ];
                },
                $qb->getQuery()->getResult()
            );
        };

        $tblComments = new SortableTable('tbl_comments', $getCommentsTotalNumber, $getCommentsData, 1, 20, 'DESC');
        $tblComments->set_additional_parameters(['action' => 'details', 'user' => $this->owner->getId()]);
        $tblComments->set_header(0, get_lang('Resume'));
        $tblComments->set_column_filter(
            0,
            function (PortfolioComment $comment) {
                return Display::url(
                    $comment->getExcerpt(),
                    $this->baseUrl.http_build_query(['action' => 'view', 'id' => $comment->getItem()->getId()])
                    .'#comment-'.$comment->getId()
                );
            }
        );
        $tblComments->set_header(1, get_lang('Date'), true, [], ['class' => 'text-center']);
        $tblComments->set_column_filter(1, $convertFormatDateColumnFilter);
        $tblComments->set_header(2, get_lang('PortfolioItemTitle'));
        $tblComments->set_column_filter(2, $portfolioItemColumnFilter);
        $tblComments->set_header(3, get_lang('Score'), true, [], ['class' => 'text-right']);

        $content = '';

        if ($frmStudent) {
            $content .= $frmStudent->returnForm();
        }

        $content .= Display::page_subheader2(get_lang('PortfolioItems')).PHP_EOL;

        if ($tblItems->get_total_number_of_items() > 0) {
            $content .= $tblItems->return_table().PHP_EOL;
        } else {
            $content .= Display::return_message(get_lang('NoItemsInYourPortfolio'), 'warning');
        }

        $content .= Display::page_subheader2(get_lang('PortfolioCommentsMade')).PHP_EOL;

        if ($tblComments->get_total_number_of_items() > 0) {
            $content .= $tblComments->return_table().PHP_EOL;
        } else {
            $content .= Display::return_message(get_lang('YouHaveNotCommented'), 'warning');
        }

        $this->renderView($content, get_lang('PortfolioDetails'), $actions);
    }

    /**
     * @throws \MpdfException
     */
    public function exportPdf(HttpRequest $httpRequest)
    {
        $isAllowedToFilterStudent = $this->course && api_is_allowed_to_edit();

        if ($isAllowedToFilterStudent) {
            if ($httpRequest->query->has('user')) {
                $this->owner = api_get_user_entity($httpRequest->query->getInt('user'));

                if (empty($this->owner)) {
                    api_not_allowed(true);
                }
            }
        }

        $pdfContent = Display::page_header($this->owner->getCompleteName());

        if ($this->course) {
            $pdfContent .= '<p>'.get_lang('Course').': ';

            if ($this->session) {
                $pdfContent .= $this->session->getName().' ('.$this->course->getTitle().')';
            } else {
                $pdfContent .= $this->course->getTitle();
            }

            $pdfContent .= '</p>';
        }

        $items = $this->em
            ->getRepository(Portfolio::class)
            ->findItemsByUser($this->owner, $this->course, $this->session);
        $comments = $this->em
            ->getRepository(PortfolioComment::class)
            ->findCommentsByUser($this->owner, $this->course, $this->session);

        $itemsHtml = $this->getItemsInHtmlFormatted($items);
        $commentsHtml = $this->getCommentsInHtmlFormatted($comments);

        $pdfContent .= Display::page_subheader2(get_lang('PortfolioItems'));

        if (count($itemsHtml) > 0) {
            $pdfContent .= implode(PHP_EOL, $itemsHtml);
        } else {
            $pdfContent .= Display::return_message(get_lang('NoItemsInYourPortfolio'), 'warning');
        }

        $pdfContent .= Display::page_subheader2(get_lang('PortfolioCommentsMade'));

        if (count($commentsHtml) > 0) {
            $pdfContent .= implode(PHP_EOL, $commentsHtml);
        } else {
            $pdfContent .= Display::return_message(get_lang('YouHaveNotCommented'), 'warning');
        }

        $pdfName = $this->owner->getCompleteName()
            .($this->course ? '_'.$this->course->getCode() : '')
            .'_'.get_lang('Portfolio');

        $pdf = new PDF();
        $pdf->content_to_pdf(
            $pdfContent,
            null,
            $pdfName,
            $this->course ? $this->course->getCode() : null,
            'D',
            false,
            null,
            false,
            true
        );
    }

    public function exportZip(HttpRequest $httpRequest)
    {
        $isAllowedToFilterStudent = $this->course && api_is_allowed_to_edit();

        if ($isAllowedToFilterStudent) {
            if ($httpRequest->query->has('user')) {
                $this->owner = api_get_user_entity($httpRequest->query->getInt('user'));

                if (empty($this->owner)) {
                    api_not_allowed(true);
                }
            }
        }

        $itemsRepo = $this->em->getRepository(Portfolio::class);
        $commentsRepo = $this->em->getRepository(PortfolioComment::class);
        $attachmentsRepo = $this->em->getRepository(PortfolioAttachment::class);

        $items = $itemsRepo->findItemsByUser($this->owner, $this->course, $this->session);
        $comments = $commentsRepo->findCommentsByUser($this->owner, $this->course, $this->session);

        $itemsHtml = $this->getItemsInHtmlFormatted($items);
        $commentsHtml = $this->getCommentsInHtmlFormatted($comments);

        $sysArchivePath = api_get_path(SYS_ARCHIVE_PATH);
        $tempPortfolioDirectory = $sysArchivePath."portfolio/{$this->owner->getId()}";

        $userDirectory = UserManager::getUserPathById($this->owner->getId(), 'system');
        $attachmentsDirectory = $userDirectory.'portfolio_attachments/';

        $tblItemsHeaders = [];
        $tblItemsHeaders[] = get_lang('Title');
        $tblItemsHeaders[] = get_lang('CreationDate');
        $tblItemsHeaders[] = get_lang('LastUpdate');
        $tblItemsHeaders[] = get_lang('Category');
        $tblItemsHeaders[] = get_lang('Category');
        $tblItemsHeaders[] = get_lang('Score');
        $tblItemsHeaders[] = get_lang('Course');
        $tblItemsHeaders[] = get_lang('Session');
        $tblItemsData = [];

        $tblCommentsHeaders = [];
        $tblCommentsHeaders[] = get_lang('Resume');
        $tblCommentsHeaders[] = get_lang('Date');
        $tblCommentsHeaders[] = get_lang('PortfolioItemTitle');
        $tblCommentsHeaders[] = get_lang('Score');
        $tblCommentsData = [];

        $filenames = [];

        $fs = new Filesystem();

        /**
         * @var int       $i
         * @var Portfolio $item
         */
        foreach ($items as $i => $item) {
            $itemCategory = $item->getCategory();
            $itemCourse = $item->getCourse();
            $itemSession = $item->getSession();

            $itemDirectory = $item->getCreationDate()->format('Y-m-d-H-i-s');

            $itemFilename = sprintf('%s/items/%s/item.html', $tempPortfolioDirectory, $itemDirectory);
            $itemFileContent = $this->fixImagesSourcesToHtml($itemsHtml[$i]);

            $fs->dumpFile($itemFilename, $itemFileContent);

            $filenames[] = $itemFilename;

            $attachments = $attachmentsRepo->findFromItem($item);

            /** @var PortfolioAttachment $attachment */
            foreach ($attachments as $attachment) {
                $attachmentFilename = sprintf(
                    '%s/items/%s/attachments/%s',
                    $tempPortfolioDirectory,
                    $itemDirectory,
                    $attachment->getFilename()
                );

                $fs->copy(
                    $attachmentsDirectory.$attachment->getPath(),
                    $attachmentFilename
                );

                $filenames[] = $attachmentFilename;
            }

            $tblItemsData[] = [
                Display::url(
                    Security::remove_XSS($item->getTitle()),
                    sprintf('items/%s/item.html', $itemDirectory)
                ),
                api_convert_and_format_date($item->getCreationDate()),
                api_convert_and_format_date($item->getUpdateDate()),
                $itemCategory ? $itemCategory->getTitle() : null,
                $item->getComments()->count(),
                $item->getScore(),
                $itemCourse->getTitle(),
                $itemSession ? $itemSession->getName() : null,
            ];
        }

        /**
         * @var int              $i
         * @var PortfolioComment $comment
         */
        foreach ($comments as $i => $comment) {
            $commentDirectory = $comment->getDate()->format('Y-m-d-H-i-s');

            $commentFileContent = $this->fixImagesSourcesToHtml($commentsHtml[$i]);
            $commentFilename = sprintf('%s/comments/%s/comment.html', $tempPortfolioDirectory, $commentDirectory);

            $fs->dumpFile($commentFilename, $commentFileContent);

            $filenames[] = $commentFilename;

            $attachments = $attachmentsRepo->findFromComment($comment);

            /** @var PortfolioAttachment $attachment */
            foreach ($attachments as $attachment) {
                $attachmentFilename = sprintf(
                    '%s/comments/%s/attachments/%s',
                    $tempPortfolioDirectory,
                    $commentDirectory,
                    $attachment->getFilename()
                );

                $fs->copy(
                    $attachmentsDirectory.$attachment->getPath(),
                    $attachmentFilename
                );

                $filenames[] = $attachmentFilename;
            }

            $tblCommentsData[] = [
                Display::url(
                    $comment->getExcerpt(),
                    sprintf('comments/%s/comment.html', $commentDirectory)
                ),
                api_convert_and_format_date($comment->getDate()),
                Security::remove_XSS($comment->getItem()->getTitle()),
                $comment->getScore(),
            ];
        }

        $tblItems = new HTML_Table(['class' => 'table table-hover table-striped table-bordered data_table']);
        $tblItems->setHeaders($tblItemsHeaders);
        $tblItems->setData($tblItemsData);

        $tblComments = new HTML_Table(['class' => 'table table-hover table-striped table-bordered data_table']);
        $tblComments->setHeaders($tblCommentsHeaders);
        $tblComments->setData($tblCommentsData);

        $itemFilename = sprintf('%s/index.html', $tempPortfolioDirectory);

        $filenames[] = $itemFilename;

        $fs->dumpFile(
            $itemFilename,
            $this->formatZipIndexFile($tblItems, $tblComments)
        );

        $zipName = $this->owner->getCompleteName()
            .($this->course ? '_'.$this->course->getCode() : '')
            .'_'.get_lang('Portfolio');
        $tempZipFile = $sysArchivePath."portfolio/$zipName.zip";
        $zip = new PclZip($tempZipFile);

        foreach ($filenames as $filename) {
            $zip->add($filename, PCLZIP_OPT_REMOVE_PATH, $tempPortfolioDirectory);
        }

        DocumentManager::file_send_for_download($tempZipFile, true, "$zipName.zip");

        $fs->remove($tempPortfolioDirectory);
        $fs->remove($tempZipFile);
    }

    public function qualifyItem(Portfolio $item)
    {
        global $interbreadcrumb;

        $em = Database::getManager();

        $formAction = $this->baseUrl.http_build_query(['action' => 'qualify', 'item' => $item->getId()]);

        $form = new FormValidator('frm_qualify', 'post', $formAction);
        $form->addUserAvatar('user', get_lang('Author'));
        $form->addLabel(get_lang('Title'), $item->getTitle());

        $itemContent = Security::remove_XSS(
            $this->generateItemContent($item)
        );

        $form->addLabel(get_lang('Content'), $itemContent);
        $form->addNumeric(
            'score',
            [get_lang('QualifyNumeric'), null, ' / '.api_get_course_setting('portfolio_max_score')]
        );
        $form->addButtonSave(get_lang('QualifyThisPortfolioItem'));

        if ($form->validate()) {
            $values = $form->exportValues();

            $item->setScore($values['score']);

            $em->persist($item);
            $em->flush();

            Display::addFlash(
                Display::return_message(get_lang('PortfolioItemGraded'), 'success')
            );

            header("Location: $formAction");
            exit();
        }

        $form->setDefaults(
            [
                'user' => $item->getUser(),
                'score' => (float) $item->getScore(),
            ]
        );

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];
        $interbreadcrumb[] = [
            'name' => Security::remove_XSS($item->getTitle()),
            'url' => $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()]),
        ];

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()])
        );

        $this->renderView($form->returnForm(), get_lang('Qualify'), $actions);
    }

    public function qualifyComment(PortfolioComment $comment)
    {
        global $interbreadcrumb;

        $em = Database::getManager();

        $item = $comment->getItem();
        $commentPath = $em->getRepository(PortfolioComment::class)->getPath($comment);

        $template = new Template('', false, false, false, true, false, false);
        $template->assign('item', $item);
        $template->assign('comments_path', $commentPath);
        $commentContext = $template->fetch(
            $template->get_template('portfolio/comment_context.html.twig')
        );

        $formAction = $this->baseUrl.http_build_query(['action' => 'qualify', 'comment' => $comment->getId()]);

        $form = new FormValidator('frm_qualify', 'post', $formAction);
        $form->addHtml($commentContext);
        $form->addUserAvatar('user', get_lang('Author'));
        $form->addLabel(get_lang('Comment'), $comment->getContent());
        $form->addNumeric(
            'score',
            [get_lang('QualifyNumeric'), null, '/ '.api_get_course_setting('portfolio_max_score')]
        );
        $form->addButtonSave(get_lang('QualifyThisPortfolioComment'));

        if ($form->validate()) {
            $values = $form->exportValues();

            $comment->setScore($values['score']);

            $em->persist($comment);
            $em->flush();

            Display::addFlash(
                Display::return_message(get_lang('PortfolioCommentGraded'), 'success')
            );

            header("Location: $formAction");
            exit();
        }

        $form->setDefaults(
            [
                'user' => $comment->getAuthor(),
                'score' => (float) $comment->getScore(),
            ]
        );

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];
        $interbreadcrumb[] = [
            'name' => Security::remove_XSS($item->getTitle()),
            'url' => $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()]),
        ];

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()])
        );

        $this->renderView($form->returnForm(), get_lang('Qualify'), $actions);
    }

    public function downloadAttachment(HttpRequest $httpRequest)
    {
        $path = $httpRequest->query->get('file');

        if (empty($path)) {
            api_not_allowed(true);
        }

        $em = Database::getManager();
        $attachmentRepo = $em->getRepository(PortfolioAttachment::class);

        $attachment = $attachmentRepo->findOneByPath($path);

        if (empty($attachment)) {
            api_not_allowed(true);
        }

        $originOwnerId = 0;

        if (PortfolioAttachment::TYPE_ITEM === $attachment->getOriginType()) {
            $item = $em->find(Portfolio::class, $attachment->getOrigin());

            $originOwnerId = $item->getUser()->getId();
        } elseif (PortfolioAttachment::TYPE_COMMENT === $attachment->getOriginType()) {
            $comment = $em->find(PortfolioComment::class, $attachment->getOrigin());

            $originOwnerId = $comment->getAuthor()->getId();
        } else {
            api_not_allowed(true);
        }

        $userDirectory = UserManager::getUserPathById($originOwnerId, 'system');
        $attachmentsDirectory = $userDirectory.'portfolio_attachments/';
        $attachmentFilename = $attachmentsDirectory.$attachment->getPath();

        if (!Security::check_abs_path($attachmentFilename, $attachmentsDirectory)) {
            api_not_allowed(true);
        }

        $downloaded = DocumentManager::file_send_for_download(
            $attachmentFilename,
            true,
            $attachment->getFilename()
        );

        if (!$downloaded) {
            api_not_allowed(true);
        }
    }

    public function deleteAttachment(HttpRequest $httpRequest)
    {
        $currentUserId = api_get_user_id();

        $path = $httpRequest->query->get('file');

        if (empty($path)) {
            api_not_allowed(true);
        }

        $em = Database::getManager();
        $fs = new Filesystem();

        $attachmentRepo = $em->getRepository(PortfolioAttachment::class);
        $attachment = $attachmentRepo->findOneByPath($path);

        if (empty($attachment)) {
            api_not_allowed(true);
        }

        $originOwnerId = 0;
        $itemId = 0;

        if (PortfolioAttachment::TYPE_ITEM === $attachment->getOriginType()) {
            $item = $em->find(Portfolio::class, $attachment->getOrigin());
            $originOwnerId = $item->getUser()->getId();
            $itemId = $item->getId();
        } elseif (PortfolioAttachment::TYPE_COMMENT === $attachment->getOriginType()) {
            $comment = $em->find(PortfolioComment::class, $attachment->getOrigin());
            $originOwnerId = $comment->getAuthor()->getId();
            $itemId = $comment->getItem()->getId();
        }

        if ($currentUserId !== $originOwnerId) {
            api_not_allowed(true);
        }

        $em->remove($attachment);
        $em->flush();

        $userDirectory = UserManager::getUserPathById($originOwnerId, 'system');
        $attachmentsDirectory = $userDirectory.'portfolio_attachments/';
        $attachmentFilename = $attachmentsDirectory.$attachment->getPath();

        $fs->remove($attachmentFilename);

        if ($httpRequest->isXmlHttpRequest()) {
            echo Display::return_message(get_lang('AttachmentFileDeleteSuccess'), 'success');
        } else {
            Display::addFlash(
                Display::return_message(get_lang('AttachmentFileDeleteSuccess'), 'success')
            );

            header('Location: '.$this->baseUrl.http_build_query(['action' => 'view', 'id' => $itemId]));
        }

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

    private function addAttachmentsFieldToForm(FormValidator $form)
    {
        $form->addButton('add_attachment', get_lang('AddAttachment'), 'plus');
        $form->addHtml('<div id="container-attachments" style="display: none;">');
        $form->addFile('attachment_file[]', get_lang('FilesAttachment'));
        $form->addText('attachment_comment[]', get_lang('Description'), false);
        $form->addHtml('</div>');

        $script = "$(function () {
            var attachmentsTemplate = $('#container-attachments').html();
            var \$btnAdd = $('[name=\"add_attachment\"]');
            var \$reference = \$btnAdd.parents('.form-group');

            \$btnAdd.on('click', function (e) {
                e.preventDefault();

                $(attachmentsTemplate).insertBefore(\$reference);
            });
        })";

        $form->addHtml("<script>$script</script>");
    }

    private function processAttachments(
        FormValidator $form,
        User $user,
        int $originId,
        int $originType
    ) {
        $em = Database::getManager();
        $fs = new Filesystem();

        $comments = $form->getSubmitValue('attachment_comment');

        foreach ($_FILES['attachment_file']['error'] as $i => $attachmentFileError) {
            if ($attachmentFileError != UPLOAD_ERR_OK) {
                continue;
            }

            $_file = [
                'name' => $_FILES['attachment_file']['name'][$i],
                'type' => $_FILES['attachment_file']['type'][$i],
                'tmp_name' => $_FILES['attachment_file']['tmp_name'][$i],
                'size' => $_FILES['attachment_file']['size'][$i],
            ];

            if (empty($_file['type'])) {
                $_file['type'] = DocumentManager::file_get_mime_type($_file['name']);
            }

            $newFileName = add_ext_on_mime(stripslashes($_file['name']), $_file['type']);

            if (!filter_extension($newFileName)) {
                Display::addFlash(Display::return_message(get_lang('UplUnableToSaveFileFilteredExtension'), 'error'));
                continue;
            }

            $newFileName = uniqid();
            $attachmentsDirectory = UserManager::getUserPathById($user->getId(), 'system').'portfolio_attachments/';

            if (!$fs->exists($attachmentsDirectory)) {
                $fs->mkdir($attachmentsDirectory, api_get_permissions_for_new_directories());
            }

            $attachmentFilename = $attachmentsDirectory.$newFileName;

            if (is_uploaded_file($_file['tmp_name'])) {
                $moved = move_uploaded_file($_file['tmp_name'], $attachmentFilename);

                if (!$moved) {
                    Display::addFlash(Display::return_message(get_lang('UplUnableToSaveFile'), 'error'));
                    continue;
                }
            }

            $attachment = new PortfolioAttachment();
            $attachment
                ->setFilename($_file['name'])
                ->setComment($comments[$i])
                ->setPath($newFileName)
                ->setOrigin($originId)
                ->setOriginType($originType)
                ->setSize($_file['size']);

            $em->persist($attachment);
            $em->flush();
        }
    }

    private function itemBelongToOwner(Portfolio $item): bool
    {
        if ($item->getUser()->getId() != $this->owner->getId()) {
            return false;
        }

        return true;
    }

    private function createFormTagFilter(bool $listByUser = false): FormValidator
    {
        $extraField = new ExtraField('portfolio');
        $tagFieldInfo = $extraField->get_handler_field_info_by_tags('tags');

        $chbxTagOptions = array_map(
            function (array $tagOption) {
                return $tagOption['tag'];
            },
            $tagFieldInfo['options'] ?? []
        );

        $frmTagList = new FormValidator(
            'frm_tag_list',
            'get',
            $this->baseUrl.($listByUser ? 'user='.$this->owner->getId() : ''),
            '',
            [],
            FormValidator::LAYOUT_BOX
        );

        if (!empty($chbxTagOptions)) {
            $frmTagList->addCheckBoxGroup('tags', $tagFieldInfo['display_text'], $chbxTagOptions);
        }

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

            if ($listByUser) {
                $frmTagList->addHidden('user', $this->owner->getId());
            }
        }

        return $frmTagList;
    }

    /**
     * @throws \Exception
     *
     * @return \FormValidator
     */
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
                'placeholder' => get_lang('SearchStudent'),
            ]
        );

        if ($listByUser) {
            $link = Display::url(
                get_lang('BackToMainPortfolio'),
                $this->baseUrl
            );
        } else {
            $link = Display::url(
                get_lang('SeeMyPortfolio'),
                $this->baseUrl.http_build_query(['user' => api_get_user_id()])
            );
        }

        $frmStudentList->addHtml($link);

        return $frmStudentList;
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

    private function getItemsForIndex(
        bool $listByUser = false,
        FormValidator $frmFilterList = null
    ) {
        $currentUserId = api_get_user_id();

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

            if ($listByUser) {
                $queryBuilder
                    ->andWhere('pi.user = :user')
                    ->setParameter('user', $this->owner);
            }

            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        'pi.user = :current_user AND (pi.isVisible = TRUE OR pi.isVisible = FALSE)',
                        'pi.user != :current_user AND pi.isVisible = TRUE'
                    )
                )
                ->setParameter('current_user', $currentUserId);

            $queryBuilder->orderBy('pi.creationDate', 'DESC');

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

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    private function createCommentForm(Portfolio $item): string
    {
        $formAction = $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()]);

        $form = new FormValidator('frm_comment', 'post', $formAction);
        $form->addHtmlEditor('content', get_lang('Comments'), true, false, ['ToolbarSet' => 'Minimal']);
        $form->addHidden('item', $item->getId());
        $form->addHidden('parent', 0);
        $form->applyFilter('content', 'trim');

        $this->addAttachmentsFieldToForm($form);

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

            $this->processAttachments(
                $form,
                $comment->getAuthor(),
                $comment->getId(),
                PortfolioAttachment::TYPE_COMMENT
            );

            $hook = HookPortfolioItemCommented::create();
            $hook->setEventData(['comment' => $comment]);
            $hook->notifyItemCommented();

            Display::addFlash(
                Display::return_message(get_lang('CommentAdded'), 'success')
            );

            header("Location: $formAction");
            exit;
        }

        return $form->returnForm();
    }

    private function generateAttachmentList($post, bool $includeHeader = true): string
    {
        $attachmentsRepo = $this->em->getRepository(PortfolioAttachment::class);

        $postOwnerId = 0;

        if ($post instanceof Portfolio) {
            $attachments = $attachmentsRepo->findFromItem($post);

            $postOwnerId = $post->getUser()->getId();
        } elseif ($post instanceof PortfolioComment) {
            $attachments = $attachmentsRepo->findFromComment($post);

            $postOwnerId = $post->getAuthor()->getId();
        }

        if (empty($attachments)) {
            return '';
        }

        $currentUserId = api_get_user_id();

        $listItems = '<ul class="fa-ul">';

        $deleteIcon = Display::return_icon(
            'delete.png',
            get_lang('DeleteAttachment'),
            ['style' => 'display: inline-block'],
            ICON_SIZE_TINY
        );
        $deleteAttrs = ['class' => 'btn-portfolio-delete'];

        /** @var PortfolioAttachment $attachment */
        foreach ($attachments as $attachment) {
            $downloadParams = http_build_query(['action' => 'download_attachment', 'file' => $attachment->getPath()]);
            $deleteParams = http_build_query(['action' => 'delete_attachment', 'file' => $attachment->getPath()]);

            $listItems .= '<li>'
                .'<span class="fa-li fa fa-paperclip" aria-hidden="true"></span>'
                .Display::url(
                    Security::remove_XSS($attachment->getFilename()),
                    $this->baseUrl.$downloadParams
                );

            if ($currentUserId === $postOwnerId) {
                $listItems .= PHP_EOL.Display::url($deleteIcon, $this->baseUrl.$deleteParams, $deleteAttrs);
            }

            if ($attachment->getComment()) {
                $listItems .= PHP_EOL.Display::span(
                        Security::remove_XSS($attachment->getComment()),
                        ['class' => 'text-muted']
                    );
            }

            $listItems .= '</li>';
        }

        $listItems .= '</ul>';

        if ($includeHeader) {
            $listItems = Display::page_subheader(get_lang('AttachmentFiles'), null, 'h5', ['class' => 'h4'])
                .$listItems;
        }

        return $listItems;
    }

    private function generateItemContent(Portfolio $item): string
    {
        $originId = $item->getOrigin();

        if (empty($originId)) {
            return $item->getContent();
        }

        $em = Database::getManager();

        $originContent = '';
        $originContentFooter = '';

        if (Portfolio::TYPE_ITEM === $item->getOriginType()) {
            $origin = $em->find(Portfolio::class, $item->getOrigin());

            if ($origin) {
                $originContent = $origin->getContent();
                $originContentFooter = vsprintf(
                    get_lang('OriginallyPublishedAsXTitleByYUser'),
                    [$origin->getTitle(), $origin->getUser()->getCompleteName()]
                );
            }
        } elseif (Portfolio::TYPE_COMMENT === $item->getOriginType()) {
            $origin = $em->find(PortfolioComment::class, $item->getOrigin());

            if ($origin) {
                $originContent = $origin->getContent();
                $originContentFooter = vsprintf(
                    get_lang('OriginallyCommentedByXUserInYItem'),
                    [$origin->getAuthor()->getCompleteName(), $origin->getItem()->getTitle()]
                );
            }
        }

        if ($originContent) {
            return "<blockquote>$originContent<footer>$originContentFooter</footer></blockquote>"
                .'<div class="clearfix">'.$item->getContent().'</div>';
        }

        return $item->getContent();
    }

    private function getItemsInHtmlFormatted(array $items): array
    {
        $itemsHtml = [];

        /** @var Portfolio $item */
        foreach ($items as $item) {
            $creationDate = api_convert_and_format_date($item->getCreationDate());
            $updateDate = api_convert_and_format_date($item->getUpdateDate());

            $metadata = '<ul class="list-unstyled text-muted">';

            if ($item->getSession()) {
                $metadata .= '<li>'.get_lang('Course').': '.$item->getSession()->getName().' ('
                    .$item->getCourse()->getTitle().') </li>';
            } elseif (!$item->getSession() && $item->getCourse()) {
                $metadata .= '<li>'.get_lang('Course').': '.$item->getCourse()->getTitle().'</li>';
            }

            $metadata .= '<li>'.sprintf(get_lang('CreationDateXDate'), $creationDate).'</li>';
            $metadata .= '<li>'.sprintf(get_lang('UpdateDateXDate'), $updateDate).'</li>';

            if ($item->getCategory()) {
                $metadata .= '<li>'.sprintf(get_lang('CategoryXName'), $item->getCategory()->getTitle()).'</li>';
            }

            $metadata .= '</ul>';

            $itemContent = Security::remove_XSS(
                $this->generateItemContent($item)
            );

            $itemsHtml[] = Display::panel($itemContent, Security::remove_XSS($item->getTitle()), '', 'info', $metadata);
        }

        return $itemsHtml;
    }

    private function getCommentsInHtmlFormatted(array $comments): array
    {
        $commentsHtml = [];

        /** @var PortfolioComment $comment */
        foreach ($comments as $comment) {
            $item = $comment->getItem();
            $date = api_convert_and_format_date($comment->getDate());

            $metadata = '<ul class="list-unstyled text-muted">';
            $metadata .= '<li>'.sprintf(get_lang('DateXDate'), $date).'</li>';
            $metadata .= '<li>'.sprintf(get_lang('PortfolioItemTitleXName'), Security::remove_XSS($item->getTitle()))
                .'</li>';
            $metadata .= '</ul>';

            $commentsHtml[] = Display::panel(
                Security::remove_XSS($comment->getContent()),
                '',
                '',
                'default',
                $metadata
            );
        }

        return $commentsHtml;
    }

    private function fixImagesSourcesToHtml(string $htmlContent): string
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($htmlContent);

        $elements = $doc->getElementsByTagName('img');

        if (empty($elements->length)) {
            return $htmlContent;
        }

        $webCoursePath = api_get_path(WEB_COURSE_PATH);
        $webUploadPath = api_get_path(WEB_UPLOAD_PATH);

        /** @var \DOMElement $element */
        foreach ($elements as $element) {
            $src = trim($element->getAttribute('src'));

            if (strpos($src, 'http') === 0) {
                continue;
            }

            if (strpos($src, '/app/upload/') === 0) {
                $element->setAttribute(
                    'src',
                    preg_replace('/\/app/upload\//', $webUploadPath, $src, 1)
                );

                continue;
            }

            if (strpos($src, '/courses/') === 0) {
                $element->setAttribute(
                    'src',
                    preg_replace('/\/courses\//', $webCoursePath, $src, 1)
                );

                continue;
            }
        }

        return $doc->saveHTML();
    }

    private function formatZipIndexFile(HTML_Table $tblItems, HTML_Table $tblComments): string
    {
        $htmlContent = Display::page_header($this->owner->getCompleteNameWithUsername());
        $htmlContent .= Display::page_subheader2(get_lang('PortfolioItems'));

        $htmlContent .= $tblItems->getRowCount() > 0
            ? $tblItems->toHtml()
            : Display::return_message(get_lang('NoItemsInYourPortfolio'), 'warning');

        $htmlContent .= Display::page_subheader2(get_lang('PortfolioCommentsMade'));

        $htmlContent .= $tblComments->getRowCount() > 0
            ? $tblComments->toHtml()
            : Display::return_message(get_lang('YouHaveNotCommented'), 'warning');

        $webAssetsPath = api_get_path(WEB_PUBLIC_PATH).'assets/';

        $doc = new DOMDocument();
        @$doc->loadHTML($htmlContent);

        $stylesheet1 = $doc->createElement('link');
        $stylesheet1->setAttribute('rel', 'stylesheet');
        $stylesheet1->setAttribute('href', $webAssetsPath.'bootstrap/dist/css/bootstrap.min.css');
        $stylesheet2 = $doc->createElement('link');
        $stylesheet2->setAttribute('rel', 'stylesheet');
        $stylesheet2->setAttribute('href', $webAssetsPath.'fontawesome/css/font-awesome.min.css');
        $stylesheet3 = $doc->createElement('link');
        $stylesheet3->setAttribute('rel', 'stylesheet');
        $stylesheet3->setAttribute('href', ChamiloApi::getEditorDocStylePath());

        $head = $doc->createElement('head');
        $head->appendChild($stylesheet1);
        $head->appendChild($stylesheet2);
        $head->appendChild($stylesheet3);

        $doc->documentElement->insertBefore(
            $head,
            $doc->getElementsByTagName('body')->item(0)
        );

        return $doc->saveHTML();
    }
}
