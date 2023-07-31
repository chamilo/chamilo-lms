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
use Chamilo\CoreBundle\Entity\PortfolioRelTag;
use Chamilo\CoreBundle\Entity\Tag;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Query\Expr\Join;
use Mpdf\MpdfException;
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
     * @var bool
     */
    private $advancedSharingEnabled;

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

        $this->advancedSharingEnabled = true === api_get_configuration_value('portfolio_advanced_sharing')
            && $this->course;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function translateCategory($category, $languages, $languageId)
    {
        global $interbreadcrumb;

        $originalName = $category->getTitle();
        $variableLanguage = '$'.$this->getLanguageVariable($originalName);

        $translateUrl = api_get_path(WEB_AJAX_PATH).'lang.ajax.php?a=translate_portfolio_category&sec_token='.Security::get_token();
        $form = new FormValidator('new_lang_variable', 'POST', $translateUrl);
        $form->addHeader(get_lang('AddWordForTheSubLanguage'));
        $form->addText('variable_language', get_lang('LanguageVariable'), false);
        $form->addText('original_name', get_lang('OriginalName'), false);

        $languagesOptions = [0 => get_lang('None')];
        foreach ($languages as $language) {
            $languagesOptions[$language->getId()] = $language->getOriginalName();
        }

        $form->addSelect(
            'sub_language',
            [get_lang('SubLanguage'), get_lang('OnlyActiveSubLanguagesAreListed')],
            $languagesOptions
        );

        if ($languageId) {
            $languageInfo = api_get_language_info($languageId);
            $form->addText(
                'new_language',
                [get_lang('Translation'), get_lang('IfThisTranslationExistsThisWillReplaceTheTerm')]
            );

            $form->addHidden('category_id', $category->getId());
            $form->addHidden('id', $languageInfo['parent_id']);
            $form->addHidden('sub', $languageInfo['id']);
            $form->addHidden('sub_language_id', $languageInfo['id']);
            $form->addHidden('redirect', true);
            $form->addButtonSave(get_lang('Save'));
        }

        $form->setDefaults([
            'variable_language' => $variableLanguage,
            'original_name' => $originalName,
            'sub_language' => $languageId,
        ]);
        $form->addRule('sub_language', get_lang('Required'), 'required');
        $form->freeze(['variable_language', 'original_name']);

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];
        $interbreadcrumb[] = [
            'name' => get_lang('Categories'),
            'url' => $this->baseUrl.'action=list_categories&parent_id='.$category->getParentId(),
        ];
        $interbreadcrumb[] = [
            'name' => Security::remove_XSS($category->getTitle()),
            'url' => $this->baseUrl.'action=edit_category&id='.$category->getId(),
        ];

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl.'action=edit_category&id='.$category->getId()
        );

        $js = '<script>
            $(function() {
              $("select[name=\'sub_language\']").on("change", function () {
                    location.href += "&sub_language=" + this.value;
                });
            });
        </script>';
        $content = $form->returnForm();

        $this->renderView($content.$js, get_lang('TranslateCategory'), $actions);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function listCategories()
    {
        global $interbreadcrumb;

        $parentId = isset($_REQUEST['parent_id']) ? (int) $_REQUEST['parent_id'] : 0;
        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
        $headers = [
            get_lang('Title'),
            get_lang('Description'),
        ];
        if ($parentId === 0) {
            $headers[] = get_lang('SubCategories');
        }
        $headers[] = get_lang('Actions');

        $column = 0;
        foreach ($headers as $header) {
            $table->setHeaderContents(0, $column, $header);
            $column++;
        }
        $currentUserId = api_get_user_id();
        $row = 1;
        $categories = $this->getCategoriesForIndex(null, $parentId);

        foreach ($categories as $category) {
            $column = 0;
            $subcategories = $this->getCategoriesForIndex(null, $category->getId());
            $linkSubCategories = $category->getTitle();
            if (count($subcategories) > 0) {
                $linkSubCategories = Display::url(
                    $category->getTitle(),
                    $this->baseUrl.'action=list_categories&parent_id='.$category->getId()
                );
            }
            $table->setCellContents($row, $column++, $linkSubCategories);
            $table->setCellContents($row, $column++, strip_tags($category->getDescription()));
            if ($parentId === 0) {
                $table->setCellContents($row, $column++, count($subcategories));
            }

            // Actions
            $links = null;
            // Edit action
            $url = $this->baseUrl.'action=edit_category&id='.$category->getId();
            $links .= Display::url(Display::return_icon('edit.png', get_lang('Edit')), $url).'&nbsp;';
            // Visible action : if active
            if ($category->isVisible() != 0) {
                $url = $this->baseUrl.'action=hide_category&id='.$category->getId();
                $links .= Display::url(Display::return_icon('visible.png', get_lang('Hide')), $url).'&nbsp;';
            } else { // else if not active
                $url = $this->baseUrl.'action=show_category&id='.$category->getId();
                $links .= Display::url(Display::return_icon('invisible.png', get_lang('Show')), $url).'&nbsp;';
            }
            // Delete action
            $url = $this->baseUrl.'action=delete_category&id='.$category->getId();
            $links .= Display::url(Display::return_icon('delete.png', get_lang('Delete')), $url, ['onclick' => 'javascript:if(!confirm(\''.get_lang('AreYouSureToDeleteJS').'\')) return false;']);

            $table->setCellContents($row, $column++, $links);
            $row++;
        }

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];
        if ($parentId > 0) {
            $interbreadcrumb[] = [
                'name' => get_lang('Categories'),
                'url' => $this->baseUrl.'action=list_categories',
            ];
        }

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl.($parentId > 0 ? 'action=list_categories' : '')
        );
        if ($currentUserId == $this->owner->getId() && $parentId === 0) {
            $actions[] = Display::url(
                Display::return_icon('new_folder.png', get_lang('AddCategory'), [], ICON_SIZE_MEDIUM),
                $this->baseUrl.'action=add_category'
            );
        }
        $content = $table->toHtml();

        $pageTitle = get_lang('Categories');
        if ($parentId > 0) {
            $em = Database::getManager();
            $parentCategory = $em->find('ChamiloCoreBundle:PortfolioCategory', $parentId);
            $pageTitle = $parentCategory->getTitle().' : '.get_lang('SubCategories');
        }

        $this->renderView($content, $pageTitle, $actions);
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

        $parentSelect = $form->addSelect(
            'parent_id',
            get_lang('ParentCategory')
        );
        $parentSelect->addOption(get_lang('Level0'), 0);
        $currentUserId = api_get_user_id();
        $categories = $this->getCategoriesForIndex(null, 0);
        foreach ($categories as $category) {
            $parentSelect->addOption($category->getTitle(), $category->getId());
        }

        $form->addButtonCreate(get_lang('Create'));

        if ($form->validate()) {
            $values = $form->exportValues();

            $category = new PortfolioCategory();
            $category
                ->setTitle($values['title'])
                ->setDescription($values['description'])
                ->setParentId($values['parent_id'])
                ->setUser($this->owner);

            $this->em->persist($category);
            $this->em->flush();

            Display::addFlash(
                Display::return_message(get_lang('CategoryAdded'), 'success')
            );

            header("Location: {$this->baseUrl}action=list_categories");
            exit;
        }

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];
        $interbreadcrumb[] = [
            'name' => get_lang('Categories'),
            'url' => $this->baseUrl.'action=list_categories',
        ];

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl.'action=list_categories'
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

        if (!api_is_platform_admin()) {
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
            $translateUrl = $this->baseUrl.'action=translate_category&id='.$category->getId();
            $translateButton = Display::toolbarButton(get_lang('TranslateThisTerm'), $translateUrl, 'language', 'link');
            $form->addText(
                'title',
                [get_lang('Title'), $translateButton]
            );
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

            header("Location: {$this->baseUrl}action=list_categories&parent_id=".$category->getParentId());
            exit;
        }

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];
        $interbreadcrumb[] = [
            'name' => get_lang('Categories'),
            'url' => $this->baseUrl.'action=list_categories',
        ];
        if ($category->getParentId() > 0) {
            $em = Database::getManager();
            $parentCategory = $em->find('ChamiloCoreBundle:PortfolioCategory', $category->getParentId());
            $pageTitle = $parentCategory->getTitle().' : '.get_lang('SubCategories');
            $interbreadcrumb[] = [
                'name' => Security::remove_XSS($pageTitle),
                'url' => $this->baseUrl.'action=list_categories&parent_id='.$category->getParentId(),
            ];
        }

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl.'action=list_categories&parent_id='.$category->getParentId()
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

        header("Location: {$this->baseUrl}action=list_categories");
        exit;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteCategory(PortfolioCategory $category)
    {
        if (!api_is_platform_admin()) {
            api_not_allowed(true);
        }

        $this->em->remove($category);
        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('CategoryDeleted'), 'success')
        );

        header("Location: {$this->baseUrl}action=list_categories");
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

        $this->blockIsNotAllowed();

        $templates = $this->em
            ->getRepository(Portfolio::class)
            ->findBy(
                [
                    'isTemplate' => true,
                    'course' => $this->course,
                    'session' => $this->session,
                    'user' => $this->owner,
                ]
            );

        $form = new FormValidator('add_portfolio', 'post', $this->baseUrl.'action=add_item');
        $form->addSelectFromCollection(
            'template',
            [
                get_lang('Template'),
                null,
                '<span id="portfolio-spinner" class="fa fa-fw fa-spinner fa-spin" style="display: none;"
                    aria-hidden="true" aria-label="'.get_lang('Loading').'"></span>',
            ],
            $templates,
            [],
            true,
            'getTitle'
        );

        if (api_get_configuration_value('save_titles_as_html')) {
            $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
        } else {
            $form->addText('title', get_lang('Title'));
            $form->applyFilter('title', 'trim');
        }
        $editorConfig = [
            'ToolbarSet' => 'NotebookStudent',
            'Width' => '100%',
            'Height' => '400',
            'cols-size' => [2, 10, 0],
        ];
        $form->addHtmlEditor('content', get_lang('Content'), true, false, $editorConfig);

        $categoriesSelect = $form->addSelect(
            'category',
            [get_lang('Category'), get_lang('PortfolioCategoryFieldHelp')]
        );
        $categoriesSelect->addOption(get_lang('SelectACategory'), 0);
        $parentCategories = $this->getCategoriesForIndex(null, 0);
        foreach ($parentCategories as $parentCategory) {
            $categoriesSelect->addOption($this->translateDisplayName($parentCategory->getTitle()), $parentCategory->getId());
            $subCategories = $this->getCategoriesForIndex(null, $parentCategory->getId());
            if (count($subCategories) > 0) {
                foreach ($subCategories as $subCategory) {
                    $categoriesSelect->addOption(' &mdash; '.$this->translateDisplayName($subCategory->getTitle()), $subCategory->getId());
                }
            }
        }

        $extraField = new ExtraField('portfolio');
        $extra = $extraField->addElements(
            $form,
            0,
            $this->course ? [] : ['tags']
        );

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
                $messageContent = sprintf(
                    get_lang('PortfolioAlertNewPostContent'),
                    $this->owner->getCompleteName(),
                    $messageCourseTitle,
                    $this->baseUrl.http_build_query(['action' => 'view', 'id' => $portfolio->getId()])
                );
                $messageContent .= '<br><br><dl>'
                    .'<dt>'.Security::remove_XSS($portfolio->getTitle()).'</dt>'
                    .'<dd>'.$portfolio->getExcerpt().'</dd>'.'</dl>';

                foreach ($userIdListToSend as $userIdToSend) {
                    MessageManager::send_message_simple(
                        $userIdToSend,
                        $messageSubject,
                        $messageContent,
                        0,
                        false,
                        false,
                        [],
                        false
                    );
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
        $actions[] = '<a id="hide_bar_template" href="#" role="button">'.
            Display::return_icon('expand.png', get_lang('Expand'), ['id' => 'expand'], ICON_SIZE_MEDIUM).
            Display::return_icon('contract.png', get_lang('Collapse'), ['id' => 'contract', 'class' => 'hide'], ICON_SIZE_MEDIUM).'</a>';

        $js = '<script>
            $(function() {
                $(".scrollbar-light").scrollbar();
                $(".scroll-wrapper").css("height", "550px");
                expandColumnToogle("#hide_bar_template", {
                    selector: "#template_col",
                    width: 3
                }, {
                    selector: "#doc_form",
                    width: 9
                });
                CKEDITOR.on("instanceReady", function (e) {
                    showTemplates();
                });
                $(window).on("load", function () {
                    $("input[name=\'title\']").focus();
                });
                $(\'#add_portfolio_template\').on(\'change\', function () {
                    $(\'#portfolio-spinner\').show();
                
                    $.getJSON(_p.web_ajax + \'portfolio.ajax.php?a=find_template&item=\' + this.value)
                        .done(function(response) {
                            if (CKEDITOR.instances.title) {
                                CKEDITOR.instances.title.setData(response.title);
                            } else {
                                document.getElementById(\'add_portfolio_title\').value = response.title;
                            }

                            CKEDITOR.instances.content.setData(response.content);
                        })
                        .fail(function () {
                            if (CKEDITOR.instances.title) {
                                CKEDITOR.instances.title.setData(\'\');
                            } else {
                                document.getElementById(\'add_portfolio_title\').value = \'\';
                            }

                            CKEDITOR.instances.content.setData(\'\');
                        })
                        .always(function() {
                          $(\'#portfolio-spinner\').hide();
                        });
                });
                '.$extra['jquery_ready_content'].'
            });
        </script>';
        $content = '<div class="page-create">
            <div class="row" style="overflow:hidden">
            <div id="template_col" class="col-md-3">
                <div class="panel panel-default">
                <div class="panel-body">
                    <div id="frmModel" class="items-templates scrollbar-light"></div>
                </div>
                </div>
            </div>
            <div id="doc_form" class="col-md-9">
                '.$form->returnForm().'
            </div>
          </div></div>';

        $this->renderView(
            $content.$js,
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

        if (!api_is_allowed_to_edit() && !$this->itemBelongToOwner($item)) {
            api_not_allowed(true);
        }

        $itemCourse = $item->getCourse();
        $itemSession = $item->getSession();

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
        $editorConfig = [
            'ToolbarSet' => 'NotebookStudent',
            'Width' => '100%',
            'Height' => '400',
            'cols-size' => [2, 10, 0],
        ];
        $form->addHtmlEditor('content', get_lang('Content'), true, false, $editorConfig);
        $categoriesSelect = $form->addSelect(
            'category',
            [get_lang('Category'), get_lang('PortfolioCategoryFieldHelp')]
        );
        $categoriesSelect->addOption(get_lang('SelectACategory'), 0);
        $parentCategories = $this->getCategoriesForIndex(null, 0);
        foreach ($parentCategories as $parentCategory) {
            $categoriesSelect->addOption($this->translateDisplayName($parentCategory->getTitle()), $parentCategory->getId());
            $subCategories = $this->getCategoriesForIndex(null, $parentCategory->getId());
            if (count($subCategories) > 0) {
                foreach ($subCategories as $subCategory) {
                    $categoriesSelect->addOption(' &mdash; '.$this->translateDisplayName($subCategory->getTitle()), $subCategory->getId());
                }
            }
        }

        $extraField = new ExtraField('portfolio');
        $extra = $extraField->addElements(
            $form,
            $item->getId(),
            $this->course ? [] : ['tags']
        );

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
            if ($itemCourse) {
                api_item_property_update(
                    api_get_course_info($itemCourse->getCode()),
                    TOOL_PORTFOLIO,
                    $item->getId(),
                    'PortfolioUpdated',
                    api_get_user_id(),
                    [],
                    null,
                    '',
                    '',
                    $itemSession ? $itemSession->getId() : 0
                );
            }

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

            HookPortfolioItemEdited::create()
                ->setEventData(['item' => $item])
                ->notifyItemEdited()
            ;

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
        $actions[] = '<a id="hide_bar_template" href="#" role="button">'.
            Display::return_icon('expand.png', get_lang('Expand'), ['id' => 'expand'], ICON_SIZE_MEDIUM).
            Display::return_icon('contract.png', get_lang('Collapse'), ['id' => 'contract', 'class' => 'hide'], ICON_SIZE_MEDIUM).'</a>';

        $js = '<script>
            $(function() {
                $(".scrollbar-light").scrollbar();
                $(".scroll-wrapper").css("height", "550px");
                expandColumnToogle("#hide_bar_template", {
                    selector: "#template_col",
                    width: 3
                }, {
                    selector: "#doc_form",
                    width: 9
                });
                CKEDITOR.on("instanceReady", function (e) {
                    showTemplates();
                });
                $(window).on("load", function () {
                    $("input[name=\'title\']").focus();
                });
                '.$extra['jquery_ready_content'].'
            });
        </script>';
        $content = '<div class="page-create">
            <div class="row" style="overflow:hidden">
            <div id="template_col" class="col-md-3">
                <div class="panel panel-default">
                <div class="panel-body">
                    <div id="frmModel" class="items-templates scrollbar-light"></div>
                </div>
                </div>
            </div>
            <div id="doc_form" class="col-md-9">
                '.$form->returnForm().'
            </div>
          </div></div>';

        $this->renderView(
            $content.$js,
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

        switch ($item->getVisibility()) {
            case Portfolio::VISIBILITY_HIDDEN:
                $item->setVisibility(Portfolio::VISIBILITY_VISIBLE);
                break;
            case Portfolio::VISIBILITY_VISIBLE:
                $item->setVisibility(Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER);
                break;
            case Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER:
            default:
                $item->setVisibility(Portfolio::VISIBILITY_HIDDEN);
                break;
        }

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

        HookPortfolioItemDeleted::create()
            ->setEventData(['item' => $item])
            ->notifyItemDeleted()
        ;

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
        $listHighlighted = $httpRequest->query->has('list_highlighted');

        if ($httpRequest->query->has('user')) {
            $this->owner = api_get_user_entity($httpRequest->query->getInt('user'));

            if (empty($this->owner)) {
                api_not_allowed(true);
            }

            $listByUser = true;
        }

        $currentUserId = api_get_user_id();

        $actions = [];

        if (api_is_platform_admin()) {
            $actions[] = Display::url(
                Display::return_icon('add.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
                $this->baseUrl.'action=add_item'
            );
            $actions[] = Display::url(
                Display::return_icon('folder.png', get_lang('AddCategory'), [], ICON_SIZE_MEDIUM),
                $this->baseUrl.'action=list_categories'
            );
            $actions[] = Display::url(
                Display::return_icon('waiting_list.png', get_lang('PortfolioDetails'), [], ICON_SIZE_MEDIUM),
                $this->baseUrl.'action=details'
            );
        } else {
            if ($currentUserId == $this->owner->getId()) {
                if ($this->isAllowed()) {
                    $actions[] = Display::url(
                        Display::return_icon('add.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
                        $this->baseUrl.'action=add_item'
                    );
                    $actions[] = Display::url(
                        Display::return_icon('waiting_list.png', get_lang('PortfolioDetails'), [], ICON_SIZE_MEDIUM),
                        $this->baseUrl.'action=details'
                    );
                }
            } else {
                $actions[] = Display::url(
                    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                    $this->baseUrl
                );
            }
        }

        if (api_is_allowed_to_edit()) {
            $actions[] = Display::url(
                Display::return_icon('tickets.png', get_lang('Tags'), [], ICON_SIZE_MEDIUM),
                $this->baseUrl.'action=tags'
            );
        }

        $frmStudentList = null;
        $frmTagList = null;

        $categories = [];
        $portfolio = [];
        if ($this->course) {
            $frmTagList = $this->createFormTagFilter($listByUser);
            $frmStudentList = $this->createFormStudentFilter($listByUser, $listHighlighted);
            $frmStudentList->setDefaults(['user' => $this->owner->getId()]);
            // it translates the category title with the current user language
            $categories = $this->getCategoriesForIndex(null, 0);
            if (count($categories) > 0) {
                foreach ($categories as &$category) {
                    $translated = $this->translateDisplayName($category->getTitle());
                    $category->setTitle($translated);
                }
            }
        } else {
            // it displays the list in Network Social for the current user
            $portfolio = $this->getCategoriesForIndex();
        }

        $foundComments = [];

        if ($listHighlighted) {
            $items = $this->getHighlightedItems();
        } else {
            $items = $this->getItemsForIndex($listByUser, $frmTagList);

            $foundComments = $this->getCommentsForIndex($frmTagList);
        }

        // it gets and translate the sub-categories
        $categoryId = $httpRequest->query->getInt('categoryId');
        $subCategoryIdsReq = isset($_REQUEST['subCategoryIds']) ? Security::remove_XSS($_REQUEST['subCategoryIds']) : '';
        $subCategoryIds = $subCategoryIdsReq;
        if ('all' !== $subCategoryIdsReq) {
            $subCategoryIds = !empty($subCategoryIdsReq) ? explode(',', $subCategoryIdsReq) : [];
        }
        $subcategories = [];
        if ($categoryId > 0) {
            $subcategories = $this->getCategoriesForIndex(null, $categoryId);
            if (count($subcategories) > 0) {
                foreach ($subcategories as &$subcategory) {
                    $translated = $this->translateDisplayName($subcategory->getTitle());
                    $subcategory->setTitle($translated);
                }
            }
        }

        $template = new Template(null, false, false, false, false, false, false);
        $template->assign('user', $this->owner);
        $template->assign('course', $this->course);
        $template->assign('session', $this->session);
        $template->assign('portfolio', $portfolio);
        $template->assign('categories', $categories);
        $template->assign('uncategorized_items', $items);
        $template->assign('frm_student_list', $this->course ? $frmStudentList->returnForm() : '');
        $template->assign('frm_tag_list', $this->course ? $frmTagList->returnForm() : '');
        $template->assign('category_id', $categoryId);
        $template->assign('subcategories', $subcategories);
        $template->assign('subcategory_ids', $subCategoryIds);
        $template->assign('found_comments', $foundComments);

        $js = '<script>
            $(function() {
                $(".category-filters").bind("click", function() {
                    var categoryId = parseInt($(this).find("input[type=\'radio\']").val());
                    $("input[name=\'categoryId\']").val(categoryId);
                    $("input[name=\'subCategoryIds\']").val("all");
                    $("#frm_tag_list_submit").trigger("click");
                });
                $(".subcategory-filters").bind("click", function() {
                    var checkedVals = $(".subcategory-filters:checkbox:checked").map(function() {
                        return this.value;
                    }).get();
                    $("input[name=\'subCategoryIds\']").val(checkedVals.join(","));
                    $("#frm_tag_list_submit").trigger("click");
                });
            });
        </script>';
        $template->assign('js_script', $js);
        $layout = $template->get_template('portfolio/list.html.twig');

        Display::addFlash(
            Display::return_message(get_lang('PortfolioPostAddHelp'), 'info', false)
        );

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

        if (!$this->itemBelongToOwner($item)) {
            if ($this->advancedSharingEnabled) {
                $courseInfo = api_get_course_info_by_id($this->course->getId());
                $sessionId = $this->session ? $this->session->getId() : 0;

                $itemPropertyVisiblity = api_get_item_visibility(
                    $courseInfo,
                    TOOL_PORTFOLIO,
                    $item->getId(),
                    $sessionId,
                    $this->owner->getId(),
                    'visible'
                );

                if ($item->getVisibility() === Portfolio::VISIBILITY_PER_USER && 1 !== $itemPropertyVisiblity) {
                    api_not_allowed(true);
                }
            } elseif ($item->getVisibility() === Portfolio::VISIBILITY_HIDDEN
                || ($item->getVisibility() === Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER && !api_is_allowed_to_edit())
            ) {
                api_not_allowed(true);
            }
        }

        HookPortfolioItemViewed::create()
            ->setEventData(['portfolio' => $item])
            ->notifyItemViewed()
        ;

        $itemCourse = $item->getCourse();
        $itemSession = $item->getSession();

        $form = $this->createCommentForm($item);

        $commentsRepo = $this->em->getRepository(PortfolioComment::class);

        $commentsQueryBuilder = $commentsRepo->createQueryBuilder('comment');
        $commentsQueryBuilder->where('comment.item = :item');

        if ($this->advancedSharingEnabled) {
            $commentsQueryBuilder
                ->leftJoin(
                    CItemProperty::class,
                    'cip',
                    Join::WITH,
                    "cip.ref = comment.id
                        AND cip.tool = :cip_tool
                        AND cip.course = :course
                        AND cip.lasteditType = 'visible'
                        AND cip.toUser = :current_user"
                )
                ->andWhere(
                    sprintf(
                        'comment.visibility = %d
                            OR (
                                comment.visibility = %d AND cip IS NOT NULL OR comment.author = :current_user
                            )',
                        PortfolioComment::VISIBILITY_VISIBLE,
                        PortfolioComment::VISIBILITY_PER_USER
                    )
                )
                ->setParameter('cip_tool', TOOL_PORTFOLIO_COMMENT)
                ->setParameter('current_user', $this->owner->getId())
                ->setParameter('course', $item->getCourse())
            ;
        }

        $comments = $commentsQueryBuilder
            ->orderBy('comment.root, comment.lft', 'ASC')
            ->setParameter('item', $item)
            ->getQuery()
            ->getArrayResult()
        ;

        $clockIcon = Display::returnFontAwesomeIcon('clock-o', '', true);

        $commentsHtml = $commentsRepo->buildTree(
            $comments,
            [
                'decorate' => true,
                'rootOpen' => '<div class="media-list">',
                'rootClose' => '</div>',
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

                    return '<article class="media" id="comment-'.$node['id'].'">
                        <div class="media-left"><img class="media-object thumbnail" src="'.$userPicture.'" alt="'
                        .$author->getCompleteName().'"></div>
                        <div class="media-body">';
                },
                'childClose' => '</div></article>',
                'nodeDecorator' => function ($node) use ($commentsRepo, $clockIcon, $item) {
                    $commentActions = [];
                    /** @var PortfolioComment $comment */
                    $comment = $commentsRepo->find($node['id']);

                    if ($this->commentBelongsToOwner($comment)) {
                        $commentActions[] = Display::url(
                            Display::return_icon(
                                $comment->isTemplate() ? 'wizard.png' : 'wizard_na.png',
                                $comment->isTemplate() ? get_lang('RemoveAsTemplate') : get_lang('AddAsTemplate')
                            ),
                            $this->baseUrl.http_build_query(['action' => 'template_comment', 'id' => $comment->getId()])
                        );
                    }

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

                    if ($this->commentBelongsToOwner($comment)) {
                        if ($this->advancedSharingEnabled) {
                            $commentActions[] = Display::url(
                                Display::return_icon('visible.png', get_lang('ChooseRecipients')),
                                $this->baseUrl.http_build_query(['action' => 'comment_visiblity_choose', 'id' => $comment->getId()])
                            );
                        }

                        $commentActions[] = Display::url(
                            Display::return_icon('edit.png', get_lang('Edit')),
                            $this->baseUrl.http_build_query(['action' => 'edit_comment', 'id' => $comment->getId()])
                        );
                        $commentActions[] = Display::url(
                            Display::return_icon('delete.png', get_lang('Delete')),
                            $this->baseUrl.http_build_query(['action' => 'delete_comment', 'id' => $comment->getId()])
                        );
                    }

                    $nodeHtml = '<div class="pull-right">'.implode(PHP_EOL, $commentActions).'</div>'.PHP_EOL
                        .'<footer class="media-heading h4">'.PHP_EOL
                        .'<p>'.$comment->getAuthor()->getCompleteName().'</p>'.PHP_EOL;

                    if ($comment->isImportant()
                        && ($this->itemBelongToOwner($comment->getItem()) || $isAllowedToEdit)
                    ) {
                        $nodeHtml .= '<span class="pull-right label label-warning origin-style">'
                            .get_lang('CommentMarkedAsImportant')
                            .'</span>'.PHP_EOL;
                    }

                    $nodeHtml .= '<small>'.$clockIcon.PHP_EOL
                        .$this->getLabelForCommentDate($comment).'</small>'.PHP_EOL;

                    $nodeHtml .= '</footer>'.PHP_EOL
                        .Security::remove_XSS($comment->getContent()).PHP_EOL;

                    $nodeHtml .= $this->generateAttachmentList($comment);

                    return $nodeHtml;
                },
            ]
        );

        $template = new Template(null, false, false, false, false, false, false);
        $template->assign('baseurl', $this->baseUrl);
        $template->assign('item', $item);
        $template->assign('item_content', $this->generateItemContent($item));
        $template->assign('count_comments', count($comments));
        $template->assign('comments', $commentsHtml);
        $template->assign('form', $form);
        $template->assign('attachment_list', $this->generateAttachmentList($item));

        if ($itemCourse) {
            $propertyInfo = api_get_item_property_info(
                $itemCourse->getId(),
                TOOL_PORTFOLIO,
                $item->getId(),
                $itemSession ? $itemSession->getId() : 0
            );

            if ($propertyInfo && empty($propertyInfo['to_user_id'])) {
                $template->assign(
                    'last_edit',
                    [
                        'date' => $propertyInfo['lastedit_date'],
                        'user' => api_get_user_entity($propertyInfo['lastedit_user_id'])->getCompleteName(),
                    ]
                );
            }
        }

        $layout = $template->get_template('portfolio/view.html.twig');
        $content = $template->fetch($layout);

        $interbreadcrumb[] = ['name' => get_lang('Portfolio'), 'url' => $this->baseUrl];

        $editLink = Display::url(
            Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl.http_build_query(['action' => 'edit_item', 'id' => $item->getId()])
        );

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl
        );

        if ($this->itemBelongToOwner($item)) {
            $actions[] = $editLink;

            $actions[] = Display::url(
                Display::return_icon(
                    $item->isTemplate() ? 'wizard.png' : 'wizard_na.png',
                    $item->isTemplate() ? get_lang('RemoveAsTemplate') : get_lang('AddAsTemplate'),
                    [],
                    ICON_SIZE_MEDIUM
                ),
                $this->baseUrl.http_build_query(['action' => 'template', 'id' => $item->getId()])
            );

            if ($this->advancedSharingEnabled) {
                $actions[] = Display::url(
                    Display::return_icon('visible.png', get_lang('ChooseRecipients'), [], ICON_SIZE_MEDIUM),
                    $this->baseUrl.http_build_query(['action' => 'item_visiblity_choose', 'id' => $item->getId()])
                );
            } else {
                $visibilityUrl = $this->baseUrl.http_build_query(['action' => 'visibility', 'id' => $item->getId()]);

                if ($item->getVisibility() === Portfolio::VISIBILITY_HIDDEN) {
                    $actions[] = Display::url(
                        Display::return_icon('invisible.png', get_lang('MakeVisible'), [], ICON_SIZE_MEDIUM),
                        $visibilityUrl
                    );
                } elseif ($item->getVisibility() === Portfolio::VISIBILITY_VISIBLE) {
                    $actions[] = Display::url(
                        Display::return_icon('visible.png', get_lang('MakeVisibleForTeachers'), [], ICON_SIZE_MEDIUM),
                        $visibilityUrl
                    );
                } elseif ($item->getVisibility() === Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER) {
                    $actions[] = Display::url(
                        Display::return_icon('eye-slash.png', get_lang('MakeInvisible'), [], ICON_SIZE_MEDIUM),
                        $visibilityUrl
                    );
                }
            }

            $actions[] = Display::url(
                Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_MEDIUM),
                $this->baseUrl.http_build_query(['action' => 'delete_item', 'id' => $item->getId()])
            );
        } else {
            $actions[] = Display::url(
                Display::return_icon('copy.png', get_lang('CopyToMyPortfolio'), [], ICON_SIZE_MEDIUM),
                $this->baseUrl.http_build_query(['action' => 'copy', 'copy' => 'item', 'id' => $item->getId()])
            );
        }

        if (api_is_allowed_to_edit()) {
            $actions[] = Display::url(
                Display::return_icon('copy.png', get_lang('CopyToStudentPortfolio'), [], ICON_SIZE_MEDIUM),
                $this->baseUrl.http_build_query(['action' => 'teacher_copy', 'copy' => 'item', 'id' => $item->getId()])
            );
            $actions[] = $editLink;

            $highlightedUrl = $this->baseUrl.http_build_query(['action' => 'highlighted', 'id' => $item->getId()]);

            if ($item->isHighlighted()) {
                $actions[] = Display::url(
                    Display::return_icon('award_red.png', get_lang('UnmarkAsHighlighted'), [], ICON_SIZE_MEDIUM),
                    $highlightedUrl
                );
            } else {
                $actions[] = Display::url(
                    Display::return_icon('award_red_na.png', get_lang('MarkAsHighlighted'), [], ICON_SIZE_MEDIUM),
                    $highlightedUrl
                );
            }

            if ($itemCourse && '1' === api_get_course_setting('qualify_portfolio_item')) {
                $actions[] = Display::url(
                    Display::return_icon('quiz.png', get_lang('QualifyThisPortfolioItem'), [], ICON_SIZE_MEDIUM),
                    $this->baseUrl.http_build_query(['action' => 'qualify', 'item' => $item->getId()])
                );
            }
        }

        $this->renderView($content, $item->getTitle(true), $actions, false);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function copyItem(Portfolio $originItem)
    {
        $this->blockIsNotAllowed();

        $currentTime = api_get_utc_datetime(null, false, true);

        $portfolio = new Portfolio();
        $portfolio
            ->setVisibility(Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER)
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
            ->setVisibility(Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER)
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
        api_protect_teacher_script();

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
                    ->setVisibility(Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER)
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
                    ->setVisibility(Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER)
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
        $this->blockIsNotAllowed();

        $currentUserId = api_get_user_id();
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

            $urlParams = http_build_query(
                [
                    'a' => 'search_user_by_course',
                    'course_id' => $this->course->getId(),
                    'session_id' => $this->session ? $this->session->getId() : 0,
                ]
            );

            $frmStudent
                ->addSelectAjax(
                    'user',
                    get_lang('SelectLearnerPortfolio'),
                    [],
                    [
                        'url' => api_get_path(WEB_AJAX_PATH)."course.ajax.php?$urlParams",
                        'placeholder' => get_lang('SearchStudent'),
                        'formatResult' => SelectAjax::templateResultForUsersInCourse(),
                        'formatSelection' => SelectAjax::templateSelectionForUsersInCourse(),
                    ]
                )
                ->addOption(
                    $this->owner->getCompleteName(),
                    $this->owner->getId(),
                    [
                        'data-avatarurl' => UserManager::getUserPicture($this->owner->getId()),
                        'data-username' => $this->owner->getUsername(),
                    ]
                )
            ;
            $frmStudent->setDefaults(['user' => $this->owner->getId()]);
            $frmStudent->addHidden('action', 'details');
            $frmStudent->addHidden('cidReq', $this->course->getCode());
            $frmStudent->addHidden('id_session', $this->session ? $this->session->getId() : 0);
            $frmStudent->addButtonFilter(get_lang('Filter'));
        }

        $itemsRepo = $this->em->getRepository(Portfolio::class);
        $commentsRepo = $this->em->getRepository(PortfolioComment::class);

        $getItemsTotalNumber = function () use ($itemsRepo, $isAllowedToFilterStudent, $currentUserId) {
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

            if ($isAllowedToFilterStudent && $currentUserId !== $this->owner->getId()) {
                $visibilityCriteria = [
                    Portfolio::VISIBILITY_VISIBLE,
                    Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER,
                ];

                $qb->andWhere(
                    $qb->expr()->in('i.visibility', $visibilityCriteria)
                );
            }

            return $qb->getQuery()->getSingleScalarResult();
        };
        $getItemsData = function ($from, $limit, $columnNo, $orderDirection) use ($itemsRepo, $isAllowedToFilterStudent, $currentUserId) {
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

            if ($isAllowedToFilterStudent && $currentUserId !== $this->owner->getId()) {
                $visibilityCriteria = [
                    Portfolio::VISIBILITY_VISIBLE,
                    Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER,
                ];

                $qb->andWhere(
                    $qb->expr()->in('item.visibility', $visibilityCriteria)
                );
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
                $item->getTitle(true),
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

        $totalNumberOfItems = $tblItems->get_total_number_of_items();
        $totalNumberOfComments = $tblComments->get_total_number_of_items();
        $requiredNumberOfItems = (int) api_get_course_setting('portfolio_number_items');
        $requiredNumberOfComments = (int) api_get_course_setting('portfolio_number_comments');

        $itemsSubtitle = '';

        if ($requiredNumberOfItems > 0) {
            $itemsSubtitle = sprintf(
                get_lang('XAddedYRequired'),
                $totalNumberOfItems,
                $requiredNumberOfItems
            );
        }

        $content .= Display::page_subheader2(
            get_lang('PortfolioItems'),
            $itemsSubtitle
        ).PHP_EOL;

        if ($totalNumberOfItems > 0) {
            $content .= $tblItems->return_table().PHP_EOL;
        } else {
            $content .= Display::return_message(get_lang('NoItemsInYourPortfolio'), 'warning');
        }

        $commentsSubtitle = '';

        if ($requiredNumberOfComments > 0) {
            $commentsSubtitle = sprintf(
                get_lang('XAddedYRequired'),
                $totalNumberOfComments,
                $requiredNumberOfComments
            );
        }

        $content .= Display::page_subheader2(
            get_lang('PortfolioCommentsMade'),
            $commentsSubtitle
        ).PHP_EOL;

        if ($totalNumberOfComments > 0) {
            $content .= $tblComments->return_table().PHP_EOL;
        } else {
            $content .= Display::return_message(get_lang('YouHaveNotCommented'), 'warning');
        }

        $this->renderView($content, get_lang('PortfolioDetails'), $actions);
    }

    /**
     * @throws MpdfException
     */
    public function exportPdf(HttpRequest $httpRequest)
    {
        $currentUserId = api_get_user_id();
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

        $visibility = [];

        if ($isAllowedToFilterStudent && $currentUserId !== $this->owner->getId()) {
            $visibility[] = Portfolio::VISIBILITY_VISIBLE;
            $visibility[] = Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER;
        }

        $items = $this->em
            ->getRepository(Portfolio::class)
            ->findItemsByUser(
                $this->owner,
                $this->course,
                $this->session,
                null,
                $visibility
            );
        $comments = $this->em
            ->getRepository(PortfolioComment::class)
            ->findCommentsByUser($this->owner, $this->course, $this->session);

        $itemsHtml = $this->getItemsInHtmlFormatted($items);
        $commentsHtml = $this->getCommentsInHtmlFormatted($comments);

        $totalNumberOfItems = count($itemsHtml);
        $totalNumberOfComments = count($commentsHtml);
        $requiredNumberOfItems = (int) api_get_course_setting('portfolio_number_items');
        $requiredNumberOfComments = (int) api_get_course_setting('portfolio_number_comments');

        $itemsSubtitle = '';
        $commentsSubtitle = '';

        if ($requiredNumberOfItems > 0) {
            $itemsSubtitle = sprintf(
                get_lang('XAddedYRequired'),
                $totalNumberOfItems,
                $requiredNumberOfItems
            );
        }

        if ($requiredNumberOfComments > 0) {
            $commentsSubtitle = sprintf(
                get_lang('XAddedYRequired'),
                $totalNumberOfComments,
                $requiredNumberOfComments
            );
        }

        $pdfContent .= Display::page_subheader2(
            get_lang('PortfolioItems'),
            $itemsSubtitle
        );

        if ($totalNumberOfItems > 0) {
            $pdfContent .= implode(PHP_EOL, $itemsHtml);
        } else {
            $pdfContent .= Display::return_message(get_lang('NoItemsInYourPortfolio'), 'warning');
        }

        $pdfContent .= Display::page_subheader2(
            get_lang('PortfolioCommentsMade'),
            $commentsSubtitle
        );

        if ($totalNumberOfComments > 0) {
            $pdfContent .= implode(PHP_EOL, $commentsHtml);
        } else {
            $pdfContent .= Display::return_message(get_lang('YouHaveNotCommented'), 'warning');
        }

        $pdfName = $this->owner->getCompleteName()
            .($this->course ? '_'.$this->course->getCode() : '')
            .'_'.get_lang('Portfolio');

        HookPortfolioDownloaded::create()
            ->setEventData(['owner' => $this->owner])
            ->notifyPortfolioDownloaded()
        ;

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
        $currentUserId = api_get_user_id();
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

        $visibility = [];

        if ($isAllowedToFilterStudent && $currentUserId !== $this->owner->getId()) {
            $visibility[] = Portfolio::VISIBILITY_VISIBLE;
            $visibility[] = Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER;
        }

        $items = $itemsRepo->findItemsByUser(
            $this->owner,
            $this->course,
            $this->session,
            null,
            $visibility
        );
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

        HookPortfolioDownloaded::create()
            ->setEventData(['owner' => $this->owner])
            ->notifyPortfolioDownloaded()
        ;

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

        $itemContent = $this->generateItemContent($item);

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

            HookPortfolioItemScored::create()
                ->setEventData(['item' => $item])
                ->notifyItemScored()
            ;

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
            'name' => $item->getTitle(true),
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

            HookPortfolioCommentScored::create()
                ->setEventData(['comment' => $comment])
                ->notifyCommentScored()
            ;

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
            'name' => $item->getTitle(true),
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

            $url = $this->baseUrl.http_build_query(['action' => 'view', 'id' => $itemId]);

            if (PortfolioAttachment::TYPE_COMMENT === $attachment->getOriginType() && isset($comment)) {
                $url .= '#comment-'.$comment->getId();
            }

            header("Location: $url");
        }

        exit;
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     */
    public function markAsHighlighted(Portfolio $item)
    {
        if ($item->getCourse()->getId() !== (int) api_get_course_int_id()) {
            api_not_allowed(true);
        }

        $item->setIsHighlighted(
            !$item->isHighlighted()
        );

        Database::getManager()->flush();

        if ($item->isHighlighted()) {
            HookPortfolioItemHighlighted::create()
                ->setEventData(['item' => $item])
                ->notifyItemHighlighted()
            ;
        }

        Display::addFlash(
            Display::return_message(
                $item->isHighlighted() ? get_lang('MarkedAsHighlighted') : get_lang('UnmarkedAsHighlighted'),
                'success'
            )
        );

        header("Location: $this->baseUrl".http_build_query(['action' => 'view', 'id' => $item->getId()]));
        exit;
    }

    public function markAsTemplate(Portfolio $item)
    {
        if (!$this->itemBelongToOwner($item)) {
            api_not_allowed(true);
        }

        $item->setIsTemplate(
            !$item->isTemplate()
        );

        Database::getManager()->flush($item);

        Display::addFlash(
            Display::return_message(
                $item->isTemplate() ? get_lang('PortfolioItemSetAsTemplate') : get_lang('PortfolioItemUnsetAsTemplate'),
                'success'
            )
        );

        header("Location: $this->baseUrl".http_build_query(['action' => 'view', 'id' => $item->getId()]));
        exit;
    }

    public function markAsTemplateComment(PortfolioComment $comment)
    {
        if (!$this->commentBelongsToOwner($comment)) {
            api_not_allowed(true);
        }

        $comment->setIsTemplate(
            !$comment->isTemplate()
        );

        Database::getManager()->flush();

        Display::addFlash(
            Display::return_message(
                $comment->isTemplate() ? get_lang('PortfolioCommentSetAsTemplate') : get_lang('PortfolioCommentUnsetAsTemplate'),
                'success'
            )
        );

        header("Location: $this->baseUrl".http_build_query(['action' => 'view', 'id' => $comment->getItem()->getId()]));
        exit;
    }

    public function listTags(HttpRequest $request)
    {
        global $interbreadcrumb;

        api_protect_course_script();
        api_protect_teacher_script();

        $em = Database::getManager();
        $tagRepo = $em->getRepository(Tag::class);

        $tagsQuery = $tagRepo->findForPortfolioInCourseQuery($this->course, $this->session);

        $tag = $request->query->has('id')
            ? $tagRepo->find($request->query->getInt('id'))
            : null;

        $formAction = ['action' => $request->query->get('action')];

        if ($tag) {
            $formAction['id'] = $tag->getId();
        }

        $form = new FormValidator('frm_add_tag', 'post', $this->baseUrl.http_build_query($formAction));
        $form->addText('name', get_lang('Tag'));

        if ($tag) {
            $form->addButtonUpdate(get_lang('Edit'));
        } else {
            $form->addButtonCreate(get_lang('Add'));
        }

        if ($form->validate()) {
            $values = $form->exportValues();

            $extraFieldInfo = (new ExtraField('portfolio'))->get_handler_field_info_by_field_variable('tags');

            if (!$tag) {
                $tag = (new Tag())->setCount(0);

                $portfolioRelTag = (new PortfolioRelTag())
                    ->setTag($tag)
                    ->setCourse($this->course)
                    ->setSession($this->session)
                ;

                $em->persist($tag);
                $em->persist($portfolioRelTag);
            }

            $tag
                ->setTag($values['name'])
                ->setFieldId((int) $extraFieldInfo['id'])
            ;

            $em->flush();

            Display::addFlash(
                Display::return_message(get_lang('TagSaved'), 'success')
            );

            header('Location: '.$this->baseUrl.http_build_query($formAction));
            exit();
        } else {
            $form->protect();

            if ($tag) {
                $form->setDefaults(['name' => $tag->getTag()]);
            }
        }

        $langTags = get_lang('Tags');
        $langEdit = get_lang('Edit');

        $deleteIcon = Display::return_icon('delete.png', get_lang('Delete'));
        $editIcon = Display::return_icon('edit.png', $langEdit);

        $table = new SortableTable(
            'portfolio_tags',
            function () use ($tagsQuery) {
                return (int) $tagsQuery
                    ->select('COUNT(t)')
                    ->getQuery()
                    ->getSingleScalarResult()
                ;
            },
            function ($from, $limit, $column, $direction) use ($tagsQuery) {
                $data = [];

                /** @var array<int, Tag> $tags */
                $tags = $tagsQuery
                    ->select('t')
                    ->orderBy('t.tag', $direction)
                    ->setFirstResult($from)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();

                foreach ($tags as $tag) {
                    $data[] = [
                        $tag->getTag(),
                        $tag->getId(),
                    ];
                }

                return $data;
            },
            0,
            40
        );
        $table->set_header(0, get_lang('Name'));
        $table->set_header(1, get_lang('Actions'), false, ['class' => 'text-right'], ['class' => 'text-right']);
        $table->set_column_filter(
            1,
            function ($id) use ($editIcon, $deleteIcon) {
                $editParams = http_build_query(['action' => 'edit_tag', 'id' => $id]);
                $deleteParams = http_build_query(['action' => 'delete_tag', 'id' => $id]);

                return Display::url($editIcon, $this->baseUrl.$editParams).PHP_EOL
                    .Display::url($deleteIcon, $this->baseUrl.$deleteParams).PHP_EOL;
            }
        );
        $table->set_additional_parameters(
            [
                'action' => 'tags',
                'cidReq' => $this->course->getCode(),
                'id_session' => $this->session ? $this->session->getId() : 0,
                'gidReq' => 0,
            ]
        );

        $content = $form->returnForm().PHP_EOL
            .$table->return_table();

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];

        $pageTitle = $langTags;

        if ($tag) {
            $pageTitle = $langEdit;

            $interbreadcrumb[] = [
                'name' => $langTags,
                'url' => $this->baseUrl.'action=tags',
            ];
        }

        $this->renderView($content, $pageTitle);
    }

    public function deleteTag(Tag $tag)
    {
        api_protect_course_script();
        api_protect_teacher_script();

        $em = Database::getManager();
        $portfolioTagRepo = $em->getRepository(PortfolioRelTag::class);

        $portfolioTag = $portfolioTagRepo
            ->findOneBy(['tag' => $tag, 'course' => $this->course, 'session' => $this->session]);

        if ($portfolioTag) {
            $em->remove($portfolioTag);
            $em->flush();

            Display::addFlash(
                Display::return_message(get_lang('TagDeleted'), 'success')
            );
        }

        header('Location: '.$this->baseUrl.http_build_query(['action' => 'tags']));
        exit();
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     */
    public function editComment(PortfolioComment $comment)
    {
        global $interbreadcrumb;

        if (!$this->commentBelongsToOwner($comment)) {
            api_not_allowed(true);
        }

        $item = $comment->getItem();
        $commmentCourse = $item->getCourse();
        $commmentSession = $item->getSession();

        $formAction = $this->baseUrl.http_build_query(['action' => 'edit_comment', 'id' => $comment->getId()]);

        $form = new FormValidator('frm_comment', 'post', $formAction);
        $form->addLabel(
            get_lang('Date'),
            $this->getLabelForCommentDate($comment)
        );
        $form->addHtmlEditor('content', get_lang('Comments'), true, false, ['ToolbarSet' => 'Minimal']);
        $form->applyFilter('content', 'trim');

        $this->addAttachmentsFieldToForm($form);

        $form->addButtonUpdate(get_lang('Update'));

        if ($form->validate()) {
            if ($commmentCourse) {
                api_item_property_update(
                    api_get_course_info($commmentCourse->getCode()),
                    TOOL_PORTFOLIO_COMMENT,
                    $comment->getId(),
                    'PortfolioCommentUpdated',
                    api_get_user_id(),
                    [],
                    null,
                    '',
                    '',
                    $commmentSession ? $commmentSession->getId() : 0
                );
            }

            $values = $form->exportValues();

            $comment->setContent($values['content']);

            $this->em->flush();

            $this->processAttachments(
                $form,
                $comment->getAuthor(),
                $comment->getId(),
                PortfolioAttachment::TYPE_COMMENT
            );

            HookPortfolioCommentEdited::create()
                ->setEventData(['comment' => $comment])
                ->notifyCommentEdited()
            ;

            Display::addFlash(
                Display::return_message(get_lang('ItemUpdated'), 'success')
            );

            header("Location: $this->baseUrl"
                .http_build_query(['action' => 'view', 'id' => $item->getId()])
                .'#comment-'.$comment->getId()
            );
            exit;
        }

        $form->setDefaults([
            'content' => $comment->getContent(),
        ]);

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];
        $interbreadcrumb[] = [
            'name' => $item->getTitle(true),
            'url' => $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()]),
        ];

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl
        );

        $content = $form->returnForm()
            .PHP_EOL
            .'<div class="row"> <div class="col-sm-8 col-sm-offset-2">'
            .$this->generateAttachmentList($comment)
            .'</div></div>';

        $this->renderView(
            $content,
            get_lang('EditPortfolioComment'),
            $actions
        );
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     */
    public function deleteComment(PortfolioComment $comment)
    {
        if (!$this->commentBelongsToOwner($comment)) {
            api_not_allowed(true);
        }

        $this->em->remove($comment);

        $this->em
            ->getRepository(PortfolioAttachment::class)
            ->removeFromComment($comment);

        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('CommentDeleted'), 'success')
        );

        header("Location: $this->baseUrl");
        exit;
    }

    public function itemVisibilityChooser(Portfolio $item)
    {
        global $interbreadcrumb;

        if (!$this->itemBelongToOwner($item)) {
            api_not_allowed(true);
        }

        $em = Database::getManager();
        $tblItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $courseId = $this->course->getId();
        $sessionId = $this->session ? $this->session->getId() : 0;

        $formAction = $this->baseUrl.http_build_query(['action' => 'item_visiblity_choose', 'id' => $item->getId()]);

        $form = new FormValidator('visibility', 'post', $formAction);
        CourseManager::addUserGroupMultiSelect($form, ['USER:'.$this->owner->getId()]);
        $form->addLabel(
            '',
            Display::return_message(
                get_lang('OnlySelectedUsersWillSeeTheContent')
                    .'<br>'.get_lang('LeaveEmptyToEnableTheContentForEveryone'),
                'info',
                false
            )
        );
        $form->addCheckBox('hidden', '', get_lang('HiddenButVisibleForMe'));
        $form->addButtonSave(get_lang('Save'));

        if ($form->validate()) {
            $values = $form->exportValues();
            $recipients = CourseManager::separateUsersGroups($values['users'])['users'];
            $courseInfo = api_get_course_info_by_id($courseId);

            Database::delete(
                $tblItemProperty,
                [
                    'c_id = ? ' => [$courseId],
                    'AND tool = ? AND ref = ? ' => [TOOL_PORTFOLIO, $item->getId()],
                    'AND lastedit_type = ? ' => ['visible'],
                ]
            );

            if (empty($recipients) && empty($values['hidden'])) {
                $item->setVisibility(Portfolio::VISIBILITY_VISIBLE);
            } else {
                if (empty($values['hidden'])) {
                    foreach ($recipients as $userId) {
                        api_item_property_update(
                            $courseInfo,
                            TOOL_PORTFOLIO,
                            $item->getId(),
                            'visible',
                            api_get_user_id(),
                            [],
                            $userId,
                            '',
                            '',
                            $sessionId
                        );
                    }
                }

                $item->setVisibility(Portfolio::VISIBILITY_PER_USER);
            }

            $em->flush();

            HookPortfolioItemVisibility::create()
                ->setEventData([
                    'item' => $item,
                    'recipients' => array_values($recipients),
                ])
                ->notifyItemVisibility()
            ;

            Display::addFlash(
                Display::return_message(get_lang('VisibilityChanged'), 'success')
            );

            header("Location: $formAction");
            exit;
        }

        $result = Database::select(
            'to_user_id',
            $tblItemProperty,
            [
                'where' => [
                    'c_id = ? ' => [$courseId],
                    'AND tool = ? AND ref = ? ' => [TOOL_PORTFOLIO, $item->getId()],
                    'AND to_user_id IS NOT NULL ' => [],
                ],
            ]
        );

        $recipients = array_map(
            function (array $item): string {
                return 'USER:'.$item['to_user_id'];
            },
            $result
        );

        $defaults = ['users' => $recipients];

        if (empty($recipients) && Portfolio::VISIBILITY_PER_USER === $item->getVisibility()) {
            $defaults['hidden'] = true;
        }

        $form->setDefaults($defaults);
        $form->protect();

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];
        $interbreadcrumb[] = [
            'name' => $item->getTitle(true),
            'url' => $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()]),
        ];

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()])
        );

        $this->renderView(
            $form->returnForm(),
            get_lang('ChooseRecipients'),
            $actions
        );
    }

    public function commentVisibilityChooser(PortfolioComment $comment)
    {
        global $interbreadcrumb;

        if (!$this->commentBelongsToOwner($comment)) {
            api_not_allowed(true);
        }

        $em = Database::getManager();
        $tblItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $courseId = $this->course->getId();
        $sessionId = $this->session ? $this->session->getId() : 0;
        $item = $comment->getItem();

        $formAction = $this->baseUrl.http_build_query(['action' => 'comment_visiblity_choose', 'id' => $comment->getId()]);

        $form = new FormValidator('visibility', 'post', $formAction);
        CourseManager::addUserGroupMultiSelect($form, ['USER:'.$this->owner->getId()]);
        $form->addLabel(
            '',
            Display::return_message(
                get_lang('OnlySelectedUsersWillSeeTheContent')
                    .'<br>'.get_lang('LeaveEmptyToEnableTheContentForEveryone'),
                'info',
                false
            )
        );
        $form->addCheckBox('hidden', '', get_lang('HiddenButVisibleForMe'));
        $form->addButtonSave(get_lang('Save'));

        if ($form->validate()) {
            $values = $form->exportValues();
            $recipients = CourseManager::separateUsersGroups($values['users'])['users'];
            $courseInfo = api_get_course_info_by_id($courseId);

            Database::delete(
                $tblItemProperty,
                [
                    'c_id = ? ' => [$courseId],
                    'AND tool = ? AND ref = ? ' => [TOOL_PORTFOLIO_COMMENT, $comment->getId()],
                    'AND lastedit_type = ? ' => ['visible'],
                ]
            );

            if (empty($recipients) && empty($values['hidden'])) {
                $comment->setVisibility(PortfolioComment::VISIBILITY_VISIBLE);
            } else {
                if (empty($values['hidden'])) {
                    foreach ($recipients as $userId) {
                        api_item_property_update(
                            $courseInfo,
                            TOOL_PORTFOLIO_COMMENT,
                            $comment->getId(),
                            'visible',
                            api_get_user_id(),
                            [],
                            $userId,
                            '',
                            '',
                            $sessionId
                        );
                    }
                }

                $comment->setVisibility(PortfolioComment::VISIBILITY_PER_USER);
            }

            $em->flush();

            Display::addFlash(
                Display::return_message(get_lang('VisibilityChanged'), 'success')
            );

            header("Location: $formAction");
            exit;
        }

        $result = Database::select(
            'to_user_id',
            $tblItemProperty,
            [
                'where' => [
                    'c_id = ? ' => [$courseId],
                    'AND tool = ? AND ref = ? ' => [TOOL_PORTFOLIO_COMMENT, $comment->getId()],
                    'AND to_user_id IS NOT NULL ' => [],
                ],
            ]
        );

        $recipients = array_map(
            function (array $itemProperty): string {
                return 'USER:'.$itemProperty['to_user_id'];
            },
            $result
        );

        $defaults = ['users' => $recipients];

        if (empty($recipients) && PortfolioComment::VISIBILITY_PER_USER === $comment->getVisibility()) {
            $defaults['hidden'] = true;
        }

        $form->setDefaults($defaults);
        $form->protect();

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];
        $interbreadcrumb[] = [
            'name' => $item->getTitle(true),
            'url' => $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()]),
        ];
        $interbreadcrumb[] = [
            'name' => $comment->getExcerpt(40),
            'url' => $this->baseUrl
                .http_build_query(['action' => 'view', 'id' => $item->getId()])
                .'#comment-'.$comment->getId(),
        ];

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
            $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()])
        );

        $this->renderView(
            $form->returnForm(),
            get_lang('ChooseRecipients'),
            $actions
        );
    }

    private function isAllowed(): bool
    {
        $isSubscribedInCourse = false;

        if ($this->course) {
            $isSubscribedInCourse = CourseManager::is_user_subscribed_in_course(
                api_get_user_id(),
                $this->course->getCode(),
                (bool) $this->session,
                $this->session ? $this->session->getId() : 0
            );
        }

        if (!$this->course || $isSubscribedInCourse) {
            return true;
        }

        return false;
    }

    private function blockIsNotAllowed()
    {
        if (!$this->isAllowed()) {
            api_not_allowed(true);
        }
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
            $actions = implode('', $actions);

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

    private function commentBelongsToOwner(PortfolioComment $comment): bool
    {
        return $comment->getAuthor() === $this->owner;
    }

    private function createFormTagFilter(bool $listByUser = false): FormValidator
    {
        $tags = Database::getManager()
            ->getRepository(Tag::class)
            ->findForPortfolioInCourseQuery($this->course, $this->session)
            ->getQuery()
            ->getResult()
        ;

        $frmTagList = new FormValidator(
            'frm_tag_list',
            'get',
            $this->baseUrl.($listByUser ? 'user='.$this->owner->getId() : ''),
            '',
            [],
            FormValidator::LAYOUT_BOX
        );

        $frmTagList->addDatePicker('date', get_lang('CreationDate'));

        $frmTagList->addSelectFromCollection(
            'tags',
            get_lang('Tags'),
            $tags,
            ['multiple' => 'multiple'],
            false,
            'getTag'
        );

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
            $frmTagList->addHidden('categoryId', 0);
            $frmTagList->addHidden('subCategoryIds', '');

            if ($listByUser) {
                $frmTagList->addHidden('user', $this->owner->getId());
            }
        }

        return $frmTagList;
    }

    /**
     * @throws Exception
     */
    private function createFormStudentFilter(bool $listByUser = false, bool $listHighlighted = false): FormValidator
    {
        $frmStudentList = new FormValidator(
            'frm_student_list',
            'get',
            $this->baseUrl,
            '',
            [],
            FormValidator::LAYOUT_BOX
        );

        $urlParams = http_build_query(
            [
                'a' => 'search_user_by_course',
                'course_id' => $this->course->getId(),
                'session_id' => $this->session ? $this->session->getId() : 0,
            ]
        );

        /** @var SelectAjax $slctUser */
        $slctUser = $frmStudentList->addSelectAjax(
            'user',
            get_lang('SelectLearnerPortfolio'),
            [],
            [
                'url' => api_get_path(WEB_AJAX_PATH)."course.ajax.php?$urlParams",
                'placeholder' => get_lang('SearchStudent'),
                'formatResult' => SelectAjax::templateResultForUsersInCourse(),
                'formatSelection' => SelectAjax::templateSelectionForUsersInCourse(),
            ]
        );

        if ($listByUser) {
            $slctUser->addOption(
                $this->owner->getCompleteName(),
                $this->owner->getId(),
                [
                    'data-avatarurl' => UserManager::getUserPicture($this->owner->getId()),
                    'data-username' => $this->owner->getUsername(),
                ]
            );

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

        $frmStudentList->addHtml("<p>$link</p>");

        if ($listHighlighted) {
            $link = Display::url(
                get_lang('BackToMainPortfolio'),
                $this->baseUrl
            );
        } else {
            $link = Display::url(
                get_lang('SeeHighlights'),
                $this->baseUrl.http_build_query(['list_highlighted' => true])
            );
        }

        $frmStudentList->addHtml("<p>$link</p>");

        return $frmStudentList;
    }

    private function getCategoriesForIndex(?int $currentUserId = null, ?int $parentId = null): array
    {
        $categoriesCriteria = [];
        if (isset($currentUserId)) {
            $categoriesCriteria['user'] = $this->owner;
        }
        if (!api_is_platform_admin() && $currentUserId !== $this->owner->getId()) {
            $categoriesCriteria['isVisible'] = true;
        }
        if (isset($parentId)) {
            $categoriesCriteria['parentId'] = $parentId;
        }

        return $this->em
            ->getRepository(PortfolioCategory::class)
            ->findBy($categoriesCriteria);
    }

    private function getHighlightedItems()
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select('pi')
            ->from(Portfolio::class, 'pi')
            ->where('pi.course = :course')
            ->andWhere('pi.isHighlighted = TRUE')
            ->setParameter('course', $this->course);

        if ($this->session) {
            $queryBuilder->andWhere('pi.session = :session');
            $queryBuilder->setParameter('session', $this->session);
        } else {
            $queryBuilder->andWhere('pi.session IS NULL');
        }

        if ($this->advancedSharingEnabled) {
            $queryBuilder
                ->leftJoin(
                    CItemProperty::class,
                    'cip',
                    Join::WITH,
                    "cip.ref = pi.id
                        AND cip.tool = :cip_tool
                        AND cip.course = pi.course
                        AND cip.lasteditType = 'visible'
                        AND cip.toUser = :current_user"
                )
                ->andWhere(
                    sprintf(
                        'pi.visibility = %d
                            OR (
                                pi.visibility = %d AND cip IS NOT NULL OR pi.user = :current_user
                            )',
                        Portfolio::VISIBILITY_VISIBLE,
                        Portfolio::VISIBILITY_PER_USER
                    )
                )
                ->setParameter('cip_tool', TOOL_PORTFOLIO)
            ;
        } else {
            $visibilityCriteria = [Portfolio::VISIBILITY_VISIBLE];

            if (api_is_allowed_to_edit()) {
                $visibilityCriteria[] = Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER;
            }

            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    'pi.user = :current_user',
                    $queryBuilder->expr()->andX(
                        'pi.user != :current_user',
                        $queryBuilder->expr()->in('pi.visibility', $visibilityCriteria)
                    )
                )
            );
        }

        $queryBuilder->setParameter('current_user', api_get_user_id());
        $queryBuilder->orderBy('pi.creationDate', 'DESC');

        return $queryBuilder->getQuery()->getResult();
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

                if (!empty($values['date'])) {
                    $queryBuilder
                        ->andWhere('pi.creationDate >= :date')
                        ->setParameter(':date', api_get_utc_datetime($values['date'], false, true))
                    ;
                }

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

                // Filters by category level 0
                $searchCategories = [];
                if (!empty($values['categoryId'])) {
                    $searchCategories[] = $values['categoryId'];
                    $subCategories = $this->getCategoriesForIndex(null, $values['categoryId']);
                    if (count($subCategories) > 0) {
                        foreach ($subCategories as $subCategory) {
                            $searchCategories[] = $subCategory->getId();
                        }
                    }
                    $queryBuilder->andWhere('pi.category IN('.implode(',', $searchCategories).')');
                }

                // Filters by sub-category, don't show the selected values
                $diff = [];
                if (!empty($values['subCategoryIds']) && !('all' === $values['subCategoryIds'])) {
                    $subCategoryIds = explode(',', $values['subCategoryIds']);
                    $diff = array_diff($searchCategories, $subCategoryIds);
                } else {
                    if (trim($values['subCategoryIds']) === '') {
                        $diff = $searchCategories;
                    }
                }
                if (!empty($diff)) {
                    unset($diff[0]);
                    if (!empty($diff)) {
                        $queryBuilder->andWhere('pi.category NOT IN('.implode(',', $diff).')');
                    }
                }
            }

            if ($listByUser) {
                $queryBuilder
                    ->andWhere('pi.user = :user')
                    ->setParameter('user', $this->owner);
            }

            if ($this->advancedSharingEnabled) {
                $queryBuilder
                    ->leftJoin(
                        CItemProperty::class,
                        'cip',
                        Join::WITH,
                        "cip.ref = pi.id
                            AND cip.tool = :cip_tool
                            AND cip.course = pi.course
                            AND cip.lasteditType = 'visible'
                            AND cip.toUser = :current_user"
                    )
                    ->andWhere(
                        sprintf(
                            'pi.visibility = %d
                            OR (
                                pi.visibility = %d AND cip IS NOT NULL OR pi.user = :current_user
                            )',
                            Portfolio::VISIBILITY_VISIBLE,
                            Portfolio::VISIBILITY_PER_USER
                        )
                    )
                    ->setParameter('cip_tool', TOOL_PORTFOLIO)
                ;
            } else {
                $visibilityCriteria = [Portfolio::VISIBILITY_VISIBLE];

                if (api_is_allowed_to_edit()) {
                    $visibilityCriteria[] = Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER;
                }

                $queryBuilder->andWhere(
                    $queryBuilder->expr()->orX(
                        'pi.user = :current_user',
                        $queryBuilder->expr()->andX(
                            'pi.user != :current_user',
                            $queryBuilder->expr()->in('pi.visibility', $visibilityCriteria)
                        )
                    )
                );
            }

            $queryBuilder->setParameter('current_user', $currentUserId);
            $queryBuilder->orderBy('pi.creationDate', 'DESC');

            $items = $queryBuilder->getQuery()->getResult();
        } else {
            $itemsCriteria = [];
            $itemsCriteria['category'] = null;
            $itemsCriteria['user'] = $this->owner;

            if ($currentUserId !== $this->owner->getId()) {
                $itemsCriteria['visibility'] = Portfolio::VISIBILITY_VISIBLE;
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

        $templates = $this->em
            ->getRepository(PortfolioComment::class)
            ->findBy(
                [
                    'isTemplate' => true,
                    'author' => $this->owner,
                ]
            );

        $form = new FormValidator('frm_comment', 'post', $formAction);
        $form->addHeader(get_lang('AddNewComment'));
        $form->addSelectFromCollection(
            'template',
            [
                get_lang('Template'),
                null,
                '<span id="portfolio-spinner" class="fa fa-fw fa-spinner fa-spin" style="display: none;"
                    aria-hidden="true" aria-label="'.get_lang('Loading').'"></span>',
            ],
            $templates,
            [],
            true,
            'getExcerpt'
        );
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

            PortfolioNotifier::notifyTeachersAndAuthor($comment);

            Display::addFlash(
                Display::return_message(get_lang('CommentAdded'), 'success')
            );

            header("Location: $formAction");
            exit;
        }

        $js = '<script>
            $(function() {
                $(\'#frm_comment_template\').on(\'change\', function () {
                    $(\'#portfolio-spinner\').show();
                
                    $.getJSON(_p.web_ajax + \'portfolio.ajax.php?a=find_template_comment&comment=\' + this.value)
                        .done(function(response) {
                            CKEDITOR.instances.content.setData(response.content);
                        })
                        .fail(function () {
                            CKEDITOR.instances.content.setData(\'\');
                        })
                        .always(function() {
                          $(\'#portfolio-spinner\').hide();
                        });
                });
            });
        </script>';

        return $form->returnForm().$js;
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
                $listItems .= '<p class="text-muted">'.Security::remove_XSS($attachment->getComment()).'</p>';
            }

            $listItems .= '</li>';
        }

        $listItems .= '</ul>';

        if ($includeHeader) {
            $listItems = '<h1 class="h4">'.get_lang('FilesAttachment').'</h1>'
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
                    [
                        "<cite>{$origin->getTitle(true)}</cite>",
                        $origin->getUser()->getCompleteName(),
                    ]
                );
            }
        } elseif (Portfolio::TYPE_COMMENT === $item->getOriginType()) {
            $origin = $em->find(PortfolioComment::class, $item->getOrigin());

            if ($origin) {
                $originContent = $origin->getContent();
                $originContentFooter = vsprintf(
                    get_lang('OriginallyCommentedByXUserInYItem'),
                    [
                        $origin->getAuthor()->getCompleteName(),
                        "<cite>{$origin->getItem()->getTitle(true)}</cite>",
                    ]
                );
            }
        }

        if ($originContent) {
            return "<figure>
                    <blockquote>$originContent</blockquote>
                    <figcaption style=\"margin-bottom: 10px;\">$originContentFooter</figcaption>
                </figure>
                <div class=\"clearfix\">".Security::remove_XSS($item->getContent()).'</div>'
            ;
        }

        return Security::remove_XSS($item->getContent());
    }

    private function getItemsInHtmlFormatted(array $items): array
    {
        $itemsHtml = [];

        /** @var Portfolio $item */
        foreach ($items as $item) {
            $itemCourse = $item->getCourse();
            $itemSession = $item->getSession();

            $creationDate = api_convert_and_format_date($item->getCreationDate());
            $updateDate = api_convert_and_format_date($item->getUpdateDate());

            $metadata = '<ul class="list-unstyled text-muted">';

            if ($itemSession) {
                $metadata .= '<li>'.get_lang('Course').': '.$itemSession->getName().' ('
                    .$itemCourse->getTitle().') </li>';
            } elseif ($itemCourse) {
                $metadata .= '<li>'.get_lang('Course').': '.$itemCourse->getTitle().'</li>';
            }

            $metadata .= '<li>'.sprintf(get_lang('CreationDateXDate'), $creationDate).'</li>';

            if ($itemCourse) {
                $propertyInfo = api_get_item_property_info(
                    $itemCourse->getId(),
                    TOOL_PORTFOLIO,
                    $item->getId(),
                    $itemSession ? $itemSession->getId() : 0
                );

                if ($propertyInfo) {
                    $metadata .= '<li>'
                        .sprintf(
                            get_lang('UpdatedOnDateXByUserY'),
                            api_convert_and_format_date($propertyInfo['lastedit_date'], DATE_TIME_FORMAT_LONG),
                            api_get_user_entity($propertyInfo['lastedit_user_id'])->getCompleteName()
                        )
                        .'</li>';
                }
            } else {
                $metadata .= '<li>'.sprintf(get_lang('UpdateDateXDate'), $updateDate).'</li>';
            }

            if ($item->getCategory()) {
                $metadata .= '<li>'.sprintf(get_lang('CategoryXName'), $item->getCategory()->getTitle()).'</li>';
            }

            $metadata .= '</ul>';

            $itemContent = $this->generateItemContent($item);

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

    /**
     * It parsers a title for a variable in lang.
     *
     * @param $defaultDisplayText
     *
     * @return string
     */
    private function getLanguageVariable($defaultDisplayText)
    {
        $variableLanguage = api_replace_dangerous_char(strtolower($defaultDisplayText));
        $variableLanguage = preg_replace('/[^A-Za-z0-9\_]/', '', $variableLanguage); // Removes special chars except underscore.
        if (is_numeric($variableLanguage[0])) {
            $variableLanguage = '_'.$variableLanguage;
        }
        $variableLanguage = api_underscore_to_camel_case($variableLanguage);

        return $variableLanguage;
    }

    /**
     * It translates the text as parameter.
     *
     * @param $defaultDisplayText
     *
     * @return mixed
     */
    private function translateDisplayName($defaultDisplayText)
    {
        $variableLanguage = $this->getLanguageVariable($defaultDisplayText);

        return isset($GLOBALS[$variableLanguage]) ? $GLOBALS[$variableLanguage] : $defaultDisplayText;
    }

    private function getCommentsForIndex(FormValidator $frmFilterList = null): array
    {
        if (null === $frmFilterList) {
            return [];
        }

        if (!$frmFilterList->validate()) {
            return [];
        }

        $values = $frmFilterList->exportValues();

        if (empty($values['date']) && empty($values['text'])) {
            return [];
        }

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('c')
            ->from(PortfolioComment::class, 'c')
        ;

        if (!empty($values['date'])) {
            $queryBuilder
                ->andWhere('c.date >= :date')
                ->setParameter(':date', api_get_utc_datetime($values['date'], false, true))
            ;
        }

        if (!empty($values['text'])) {
            $queryBuilder
                ->andWhere('c.content LIKE :text')
                ->setParameter('text', '%'.$values['text'].'%')
            ;
        }

        if ($this->advancedSharingEnabled) {
            $queryBuilder
                ->leftJoin(
                    CItemProperty::class,
                    'cip',
                    Join::WITH,
                    "cip.ref = c.id
                        AND cip.tool = :cip_tool
                        AND cip.course = :course
                        AND cip.lasteditType = 'visible'
                        AND cip.toUser = :current_user"
                )
                ->andWhere(
                    sprintf(
                        'c.visibility = %d
                            OR (
                                c.visibility = %d AND cip IS NOT NULL OR c.author = :current_user
                            )',
                        PortfolioComment::VISIBILITY_VISIBLE,
                        PortfolioComment::VISIBILITY_PER_USER
                    )
                )
                ->setParameter('cip_tool', TOOL_PORTFOLIO_COMMENT)
                ->setParameter('current_user', $this->owner->getId())
                ->setParameter('course', $this->course)
            ;
        }

        $queryBuilder->orderBy('c.date', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    private function getLabelForCommentDate(PortfolioComment $comment): string
    {
        $item = $comment->getItem();
        $commmentCourse = $item->getCourse();
        $commmentSession = $item->getSession();

        $dateLabel = Display::dateToStringAgoAndLongDate($comment->getDate()).PHP_EOL;

        if ($commmentCourse) {
            $propertyInfo = api_get_item_property_info(
                $commmentCourse->getId(),
                TOOL_PORTFOLIO_COMMENT,
                $comment->getId(),
                $commmentSession ? $commmentSession->getId() : 0
            );

            if ($propertyInfo) {
                $dateLabel .= '|'.PHP_EOL
                    .sprintf(
                        get_lang('UpdatedDateX'),
                        Display::dateToStringAgoAndLongDate($propertyInfo['lastedit_date'])
                    );
            }
        }

        return $dateLabel;
    }
}
