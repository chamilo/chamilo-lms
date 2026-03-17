<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\CoreBundle\Entity\PortfolioRelTag;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tag;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\PortfolioCommentEditedEvent;
use Chamilo\CoreBundle\Event\PortfolioCommentScoredEvent;
use Chamilo\CoreBundle\Event\PortfolioItemAddedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemCommentedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemDeletedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemDownloadedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemEditedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemHighlightedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemScoredEvent;
use Chamilo\CoreBundle\Event\PortfolioItemViewedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemVisibilityChangedEvent;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use League\Flysystem\FilesystemException;
use Mpdf\MpdfException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class PortfolioController.
 */
class PortfolioController
{
    public string $baseUrl;
    private ?Course $course;
    private ?Session $session;
    private User $owner;
    private EntityManagerInterface $em;
    private bool $advancedSharingEnabled;

    public function __construct()
    {
        $this->em = Database::getManager();

        $this->owner = api_get_user_entity();
        $this->course = api_get_course_entity();
        $this->session = api_get_session_entity();

        $cidreq = api_get_cidreq();
        $this->baseUrl = api_get_self().'?'.($cidreq ? $cidreq.'&' : '');

        $this->advancedSharingEnabled = 'true' === api_get_setting('platform.portfolio_advanced_sharing')
            && $this->course;
    }

    /**
     * @param mixed $languages
     * @param mixed $languageId
     *
     * @throws Exception
     */
    public function translateCategory(PortfolioCategory $category, $languages, $languageId): void
    {
        global $interbreadcrumb;

        $originalName = $category->getTitle();
        $variableLanguage = '$'.$this->getLanguageVariable($originalName);

        $translateUrl = api_get_path(WEB_AJAX_PATH).'lang.ajax.php?a=translate_portfolio_category&sec_token='
            .Security::get_token();
        $form = new FormValidator('new_lang_variable', 'POST', $translateUrl);
        $form->addHeader(get_lang('Add terms to the sub-language'));
        $form->addText('variable_language', get_lang('Language variable'), false);
        $form->addText('original_name', get_lang('Original name'), false);

        $languagesOptions = [0 => get_lang('None')];
        foreach ($languages as $language) {
            $languagesOptions[$language->getId()] = $language->getOriginalName();
        }

        $form->addSelect(
            'sub_language',
            [get_lang('Sub-language'), get_lang('Only active sub-languages appear in this list')],
            $languagesOptions
        );

        if ($languageId) {
            $languageInfo = api_get_language_info($languageId);
            $form->addText(
                'new_language',
                [
                    get_lang('Translation'),
                    get_lang('If this term has already been translated, this operation will replace its translation for this sub-language.'),
                ]
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
            'url' => $this->baseUrl.'action=list_categories&parent_id='.$category->getParent()?->getId(),
        ];
        $interbreadcrumb[] = [
            'name' => Security::remove_XSS($category->getTitle()),
            'url' => $this->baseUrl.'action=edit_category&id='.$category->getId(),
        ];

        $actions = [];
        $actions[] = Display::url(
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
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

        $this->renderView($content.$js, get_lang('Translate category'), $actions);
    }

    /**
     * It parsers a title for a variable in lang.
     *
     * @return string
     */
    private function getLanguageVariable($defaultDisplayText)
    {
        $variableLanguage = api_replace_dangerous_char(strtolower($defaultDisplayText));
        $variableLanguage = preg_replace(
            '/[^A-Za-z0-9\_]/',
            '',
            $variableLanguage
        ); // Removes special chars except underscore.
        if (is_numeric($variableLanguage[0])) {
            $variableLanguage = '_'.$variableLanguage;
        }

        return api_underscore_to_camel_case($variableLanguage);
    }

    private function renderView(string $content, string $toolName, array $actions = [], bool $showHeader = true): void
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

    public function listCategories(): void
    {
        global $interbreadcrumb;

        $parentId = isset($_REQUEST['parent_id']) ? (int) $_REQUEST['parent_id'] : null;
        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
        $headers = [
            get_lang('Title'),
            get_lang('Description'),
        ];
        if (empty($parentId)) {
            $headers[] = get_lang('Sub-categories');
        }
        $headers[] = get_lang('Actions');

        $column = 0;
        foreach ($headers as $header) {
            $table->setHeaderContents(0, $column, $header);
            $column++;
        }
        $currentUserId = api_get_user_id();
        $row = 1;
        $categories = $this->getCategoriesForIndex($parentId);

        foreach ($categories as $category) {
            $column = 0;
            $subcategories = $this->getCategoriesForIndex($category->getId());
            $linkSubCategories = $category->getTitle();
            if (count($subcategories) > 0) {
                $linkSubCategories = Display::url(
                    $category->getTitle(),
                    $this->baseUrl.'action=list_categories&parent_id='.$category->getId()
                );
            }
            $table->setCellContents($row, $column++, $linkSubCategories);
            $table->setCellContents($row, $column++, strip_tags($category->getDescription()));
            if (!$parentId) {
                $table->setCellContents($row, $column++, count($subcategories));
            }

            // Actions
            $links = null;
            // Edit action
            $url = $this->baseUrl.'action=edit_category&id='.$category->getId();
            $links .= Display::url(Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit')), $url).'&nbsp;';
            // Visible action: if active
            if (0 != $category->isVisible()) {
                $url = $this->baseUrl.'action=hide_category&id='.$category->getId();
                $links .= Display::url(Display::getMdiIcon(ActionIcon::VISIBLE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Hide')), $url).'&nbsp;';
            } else { // else if not active
                $url = $this->baseUrl.'action=show_category&id='.$category->getId();
                $links .= Display::url(Display::getMdiIcon(ActionIcon::INVISIBLE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Show')), $url).'&nbsp;';
            }
            // Delete action
            $url = $this->baseUrl.'action=delete_category&id='.$category->getId();
            $links .= Display::url(Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Delete')), $url, ['onclick' => 'javascript:if(!confirm(\''.get_lang('Are you sure to delete').'?\')) return false;']);

            $table->setCellContents($row, $column++, $links);
            $row++;
        }

        $interbreadcrumb[] = [
            'name' => get_lang('Portfolio'),
            'url' => $this->baseUrl,
        ];
        if ($parentId) {
            $interbreadcrumb[] = [
                'name' => get_lang('Categories'),
                'url' => $this->baseUrl.'action=list_categories',
            ];
        }

        $actions = [];
        $actions[] = Display::url(
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            $this->baseUrl.($parentId ? 'action=list_categories' : '')
        );
        if ($currentUserId == $this->owner->getId() && !$parentId) {
            $actions[] = Display::url(
                Display::getMdiIcon(ActionIcon::CREATE_FOLDER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add category')),
                $this->baseUrl.'action=add_category'
            );
        }
        $content = $table->toHtml();

        $pageTitle = get_lang('Categories');
        if ($parentId) {
            $em = Database::getManager();
            $parentCategory = $em->find(PortfolioCategory::class, $parentId);
            $pageTitle = $parentCategory->getTitle().' : '.get_lang('Sub-categories');
        }

        $this->renderView($content, $pageTitle, $actions);
    }

    private function getCategoriesForIndex(?int $parentId = null): array
    {
        $categoriesCriteria = [
            'parent' => $parentId,
        ];

        if (!api_is_platform_admin() && null !== $this->owner->getId()) {
            $categoriesCriteria['isVisible'] = true;
        }

        return $this->em
            ->getRepository(PortfolioCategory::class)
            ->findBy($categoriesCriteria)
        ;
    }

    /**
     * @throws Exception
     */
    public function addCategory(): void
    {
        global $interbreadcrumb;

        Display::addFlash(
            Display::return_message(get_lang('Categories are for organization only in personal portfolio.'), 'info')
        );

        $form = new FormValidator('add_category', 'post', "{$this->baseUrl}&action=add_category");

        if ('true' === api_get_setting('editor.save_titles_as_html')) {
            $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
        } else {
            $form->addText('title', get_lang('Title'));
            $form->applyFilter('title', 'trim');
        }

        $form->addHtmlEditor('description', get_lang('Description'), false, false, ['ToolbarSet' => 'Minimal']);

        $parentSelect = $form->addSelect(
            'parent_id',
            get_lang('Parent category')
        );
        $parentSelect->addOption(sprintf(get_lang('Level %s'), '0'), 0);
        $categories = $this->getCategoriesForIndex();

        foreach ($categories as $category) {
            $parentSelect->addOption($category->getTitle(), $category->getId());
        }

        $form->addButtonCreate(get_lang('Create'));

        if ($form->validate()) {
            $values = $form->exportValues();

            $categoryRepo = $this->em->getRepository(PortfolioCategory::class);

            $category = new PortfolioCategory();
            $category
                ->setTitle($values['title'])
                ->setDescription($values['description'])
                ->setParent(
                    $categoryRepo->find($values['parent_id'])
                )
                ->setUser($this->owner)
            ;

            $this->em->persist($category);
            $this->em->flush();

            Display::addFlash(
                Display::return_message(get_lang('Category added'), 'success')
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
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            $this->baseUrl.'action=list_categories'
        );

        $content = $form->returnForm();

        $this->renderView($content, get_lang('Add category'), $actions);
    }

    /**
     * @throws Exception
     */
    public function editCategory(PortfolioCategory $category): void
    {
        global $interbreadcrumb;

        if (!api_is_platform_admin()) {
            api_not_allowed(true);
        }

        Display::addFlash(
            Display::return_message(get_lang('Categories are for organization only in personal portfolio.'), 'info')
        );

        $form = new FormValidator(
            'edit_category',
            'post',
            $this->baseUrl."action=edit_category&id={$category->getId()}"
        );

        if ('true' === api_get_setting('editor.save_titles_as_html')) {
            $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
        } else {
            $translateUrl = $this->baseUrl.'action=translate_category&id='.$category->getId();
            $translateButton = Display::toolbarButton(get_lang('Translate this term'), $translateUrl, 'language', 'link');
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
                ->setDescription($values['description'])
            ;

            $this->em->persist($category);
            $this->em->flush();

            Display::addFlash(
                Display::return_message(get_lang('Updated'), 'success')
            );

            header("Location: {$this->baseUrl}action=list_categories&parent_id=".$category->getParent()?->getId());

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
        if ($category->getParent()) {
            $em = Database::getManager();
            $parentCategory = $em->find(PortfolioCategory::class, $category->getParent()->getId());
            $pageTitle = $parentCategory->getTitle().' : '.get_lang('Sub-categories');
            $interbreadcrumb[] = [
                'name' => Security::remove_XSS($pageTitle),
                'url' => $this->baseUrl.'action=list_categories&parent_id='.$category->getParent()->getId(),
            ];
        }

        $actions = [];
        $actions[] = Display::url(
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            $this->baseUrl.'action=list_categories&parent_id='.$category->getParent()->getId()
        );

        $content = $form->returnForm();

        $this->renderView($content, get_lang('Edit this category'), $actions);
    }

    /**
     * @throws OptimisticLockException
     * @throws Doctrine\ORM\Exception\ORMException
     */
    public function showHideCategory(PortfolioCategory $category): never
    {
        if (!$this->categoryBelongToOwner($category)) {
            api_not_allowed(true);
        }

        $category->setIsVisible(!$category->isVisible());

        $this->em->persist($category);
        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('Post visibility changed'), 'success')
        );

        header("Location: {$this->baseUrl}action=list_categories");

        exit;
    }

    private function categoryBelongToOwner(PortfolioCategory $category): bool
    {
        if ($category->getUser()->getId() != $this->owner->getId()) {
            return false;
        }

        return true;
    }

    /**
     * @throws OptimisticLockException
     * @throws Doctrine\ORM\Exception\ORMException
     */
    public function deleteCategory(PortfolioCategory $category): never
    {
        if (!api_is_platform_admin()) {
            api_not_allowed(true);
        }

        $this->em->remove($category);
        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('The category has been deleted.'), 'success')
        );

        header("Location: {$this->baseUrl}action=list_categories");

        exit;
    }

    /**
     * @throws Exception
     */
    public function addItem(): void
    {
        global $interbreadcrumb;

        $this->blockIsNotAllowed();

        $templates = Container::getPortfolioRepository()->findTemplates(
            $this->owner,
            $this->course,
            $this->session
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

        if ('true' === api_get_setting('editor.save_titles_as_html')) {
            $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
        } else {
            $form->addText('title', get_lang('Title'));
            $form->applyFilter('title', 'trim');
        }
        $editorConfig = [
            'ToolbarSet' => 'Documents',
            'Width' => '100%',
            'Height' => '400',
            'cols-size' => [2, 10, 0],
        ];
        $form->addHtmlEditor('content', get_lang('Content'), true, false, $editorConfig);

        $categoriesSelect = $form->addSelect(
            'category',
            [get_lang('Category'), get_lang('Categories are for organization only in personal portfolio.')]
        );
        $categoriesSelect->addOption(get_lang('Select a category'), 0);
        $parentCategories = $this->getCategoriesForIndex();
        foreach ($parentCategories as $parentCategory) {
            $categoriesSelect->addOption($this->translateDisplayName($parentCategory->getTitle()), $parentCategory->getId());
            $subCategories = $this->getCategoriesForIndex($parentCategory->getId());
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

            $portfolio = new Portfolio();
            $portfolio
                ->setTitle($values['title'])
                ->setContent($values['content'])
                ->setCreator($this->owner)
                ->setParent($this->owner)
                ->setCategory(
                    $this->em->find(PortfolioCategory::class, $values['category'])
                )
            ;

            if ($this->course) {
                $portfolio->addCourseLink($this->course, $this->session);
            }

            $this->em->persist($portfolio);
            $this->em->flush();

            $values['item_id'] = $portfolio->getId();

            $extraFieldValue = new ExtraFieldValue('portfolio');
            $extraFieldValue->saveFieldValues($values);

            $this->processAttachments(
                $form,
                $portfolio->getId(),
                Portfolio::TYPE_ITEM
            );

            Container::getEventDispatcher()->dispatch(
                new PortfolioItemAddedEvent(['portfolio' => $portfolio]),
                Events::PORTFOLIO_ITEM_ADDED
            );

            if (1 == api_get_course_setting('email_alert_teachers_new_post')) {
                if ($this->session) {
                    $messageCourseTitle = "{$this->course->getTitle()} ({$this->session->getTitle()})";

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

                $messageSubject = sprintf(get_lang('[Portfolio] New post in course %s'), $messageCourseTitle);
                $messageContent = sprintf(
                    get_lang("There is a new post by %s in the portfolio of course %s. To view it <a href='%s'>go here</a>."),
                    $this->owner->getFullName(),
                    $messageCourseTitle,
                    $this->baseUrl.http_build_query(['action' => 'view', 'id' => $portfolio->getId()])
                );
                $messageContent .= '<br><br><dl>'
                    .'<dt>'.Security::remove_XSS($portfolio->getTitle()).'</dt>'
                    .'<dd>'.$portfolio->getExcerpt().'</dd></dl>';

                foreach ($userIdListToSend as $userIdToSend) {
                    MessageManager::send_message_simple(
                        $userIdToSend,
                        $messageSubject,
                        $messageContent,
                        0,
                        false,
                        false,
                        false
                    );
                }
            }

            Display::addFlash(
                Display::return_message(get_lang('Portfolio item added'), 'success')
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
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            $this->baseUrl
        );
        $actions[] = '<a id="hide_bar_template" href="#" role="button">'.
            Display::getMdiIcon(ActionIcon::EXPAND, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Expand')).
            Display::getMdiIcon(ActionIcon::COLLAPSE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Collapse')).'</a>';

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
            get_lang('Add item to portfolio'),
            $actions
        );
    }

    private function blockIsNotAllowed(): void
    {
        if (!$this->isAllowed()) {
            api_not_allowed(true);
        }
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

    /**
     * It translates the text as parameter.
     *
     * @return mixed
     */
    private function translateDisplayName($defaultDisplayText)
    {
        $variableLanguage = $this->getLanguageVariable($defaultDisplayText);

        return $GLOBALS[$variableLanguage] ?? $defaultDisplayText;
    }

    private function addAttachmentsFieldToForm(FormValidator $form): void
    {
        $form->addButton('add_attachment', get_lang('Add attachment'), 'plus');
        $form->addHtml('<div id="container-attachments" style="display: none;">');
        $form->addFile('attachment_file[]', get_lang('Files attachments'));
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

    /**
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    private function processAttachments(
        FormValidator $form,
        int $originId,
        int $originType
    ): void {
        $em = Database::getManager();

        $comments = $form->getSubmitValue('attachment_comment');

        foreach ($_FILES['attachment_file']['error'] as $i => $attachmentFileError) {
            if (\UPLOAD_ERR_OK != $attachmentFileError) {
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
                Display::addFlash(Display::return_message(
                    get_lang('File upload failed: this file extension or file type is prohibited'),
                    'error'
                ));

                continue;
            }

            $resourceRepo = null;
            $resource = null;

            $file = new UploadedFile(
                $_file['tmp_name'],
                $_file['name'],
                $_file['type'] ?? null,
                $_file['error'] ?? 0,
                true
            );

            if (Portfolio::TYPE_ITEM === $originType) {
                $resourceRepo = $em->getRepository(Portfolio::class);
                $resource = $resourceRepo->find($originId);

                // Explicit false for clarity (no behavior change).

            } elseif (Portfolio::TYPE_COMMENT === $originType) {
                $resourceRepo = $em->getRepository(PortfolioComment::class);
                $resource = $resourceRepo->find($originId);
            } else {
                continue;
            }

            $resourceRepo->addFile($resource, $file, $comments[$i], true);
        }
    }

    /**
     * @throws Exception
     */
    public function editItem(Portfolio $item): void
    {
        global $interbreadcrumb;

        if (!api_is_allowed_to_edit() && !$this->itemBelongToOwner($item)) {
            api_not_allowed(true);
        }

        $form = new FormValidator('edit_portfolio', 'post', $this->baseUrl."action=edit_item&id={$item->getId()}");

        if ('true' === api_get_setting('editor.save_titles_as_html')) {
            $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
        } else {
            $form->addText('title', get_lang('Title'));
            $form->applyFilter('title', 'trim');
        }

        if ($item->getOrigin()) {
            if (Portfolio::TYPE_ITEM === $item->getOriginType()) {
                $origin = $this->em->find(Portfolio::class, $item->getOrigin());

                $form->addLabel(
                    sprintf(get_lang('Portfolio item by %s'), $origin->getUser()->getFullName()),
                    Display::panel(
                        Security::remove_XSS($origin->getContent())
                    )
                );
            } elseif (Portfolio::TYPE_COMMENT === $item->getOriginType()) {
                $origin = $this->em->find(PortfolioComment::class, $item->getOrigin());

                $form->addLabel(
                    sprintf(get_lang('Comment by %s'), $origin->getCreator()->getFullName()),
                    Display::panel(
                        Security::remove_XSS($origin->getContent())
                    )
                );
            }
        }
        $editorConfig = [
            'ToolbarSet' => 'Documents',
            'Width' => '100%',
            'Height' => '400',
            'cols-size' => [2, 10, 0],
        ];
        $form->addHtmlEditor('content', get_lang('Content'), true, false, $editorConfig);
        $categoriesSelect = $form->addSelect(
            'category',
            [get_lang('Category'), get_lang('Categories are for organization only in personal portfolio.')]
        );
        $categoriesSelect->addOption(get_lang('Select a category'), 0);
        $parentCategories = $this->getCategoriesForIndex();
        foreach ($parentCategories as $parentCategory) {
            $categoriesSelect->addOption($this->translateDisplayName($parentCategory->getTitle()), $parentCategory->getId());
            $subCategories = $this->getCategoriesForIndex($parentCategory->getId());
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
            $form->addLabel(get_lang('Attachments'), $attachmentList);
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

            $item
                ->setTitle($values['title'])
                ->setContent($values['content'])
                ->setCategory(
                    $this->em->find(PortfolioCategory::class, $values['category'])
                )
            ;

            $values['item_id'] = $item->getId();

            $extraFieldValue = new ExtraFieldValue('portfolio');
            $extraFieldValue->saveFieldValues($values);

            $this->em->flush();

            Container::getEventDispatcher()->dispatch(
                new PortfolioItemEditedEvent(['portfolio' => $item]),
                Events::PORTFOLIO_ITEM_EDITED
            );

            $this->processAttachments(
                $form,
                $item->getId(),
                Portfolio::TYPE_ITEM
            );

            Display::addFlash(
                Display::return_message(get_lang('Item updated'), 'success')
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
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            $this->baseUrl
        );
        $actions[] = '<a id="hide_bar_template" href="#" role="button">'.
            Display::getMdiIcon(ActionIcon::EXPAND, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Expand')).
            Display::getMdiIcon(ActionIcon::COLLAPSE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Collapse')).'</a>';

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
            get_lang('Edit portfolio item'),
            $actions
        );
    }

    private function itemBelongToOwner(Portfolio $item): bool
    {
        if ($item->getCreator()->getId() != $this->owner->getId()) {
            return false;
        }

        return true;
    }

    private function generateAttachmentList(Portfolio|PortfolioComment $post, bool $includeHeader = true): string
    {
        $attachments = $post->resourceNode->getResourceFiles();

        $postOwnerId = $post->getCreator()?->getId();

        if (!$attachments->count()) {
            return '';
        }

        /** @var ResourceRepository $nodeRepo */
        $resourceRepo = $this->em->getRepository(ResourceRepository::class);

        $currentUserId = api_get_user_id();

        $listItems = '<ul class="fa-ul">';

        $deleteIcon = Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', 'display: inline-block', ICON_SIZE_MEDIUM, get_lang('Delete attachment'));
        $deleteAttrs = ['class' => 'btn-portfolio-delete'];

        foreach ($attachments as $attachment) {
            $downloadParams = http_build_query([
                'action' => 'download_attachment',
                'node' => $post->resourceNode->getId(),
                'attachment' => $attachment->getId(),
            ]);
            $deleteParams = http_build_query([
                'action' => 'download_attachment',
                'node' => $post->resourceNode->getId(),
                'attachment' => $attachment->getId(),
            ]);

            $listItems .= '<li>'
                .'<span class="fa-li fa fa-paperclip" aria-hidden="true"></span>'
                .Display::url(
                    Security::remove_XSS($attachment->getOriginalName()),
                    $this->baseUrl.$downloadParams
                );

            if ($currentUserId === $postOwnerId) {
                $listItems .= \PHP_EOL.Display::url($deleteIcon, $this->baseUrl.$deleteParams, $deleteAttrs);
            }

            if ($fileDescription = $attachment->getDescription()) {
                $listItems .= '<p class="text-muted">'.Security::remove_XSS($fileDescription).'</p>';
            }

            $listItems .= '</li>';
        }

        $listItems .= '</ul>';

        if ($includeHeader) {
            $listItems = '<h1 class="h4">'.get_lang('Files attachments').'</h1>'
                .$listItems;
        }

        return $listItems;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function showHideItem(Portfolio $item): never
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
            Display::return_message(get_lang('The visibility has been changed.'), 'success')
        );

        header("Location: $this->baseUrl");

        exit;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteItem(Portfolio $item): void
    {
        if (!$this->itemBelongToOwner($item)) {
            api_not_allowed(true);
        }

        Container::getEventDispatcher()->dispatch(
            new PortfolioItemDeletedEvent(['portfolio' => $item]),
            Events::PORTFOLIO_ITEM_DELETED
        );

        $this->em->remove($item);
        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('Item deleted'), 'success')
        );

        header("Location: $this->baseUrl");

        exit;
    }

    /**
     * @throws Exception
     */
    public function index(HttpRequest $httpRequest): void
    {
        $listByUser = false;
        $listHighlighted = $httpRequest->query->has('list_highlighted');
        $listAlphabetical = $httpRequest->query->has('list_alphabetical');

        if ($httpRequest->query->has('user')) {
            $this->owner = api_get_user_entity($httpRequest->query->getInt('user'));

            if (empty($this->owner)) {
                api_not_allowed(true);
            }

            $listByUser = true;
        }

        $actions = [];

        if (api_is_platform_admin()) {
            $actions[] = Display::url(
                Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add')),
                $this->baseUrl.'action=add_item'
            );
            $actions[] = Display::url(
                Display::getMdiIcon(ActionIcon::CREATE_FOLDER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add category')),
                $this->baseUrl.'action=list_categories'
            );
            $actions[] = Display::url(
                Display::getMdiIcon(ObjectIcon::WAITING_LIST, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Portfolio details')),
                $this->baseUrl.'action=details'
            );
        } elseif (api_get_user_entity() === $this->owner) {
            if ($this->isAllowed()) {
                $actions[] = Display::url(
                    Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add')),
                    $this->baseUrl.'action=add_item'
                );
                $actions[] = Display::url(
                    Display::getMdiIcon(ObjectIcon::WAITING_LIST, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Portfolio details')),
                    $this->baseUrl.'action=details'
                );
            }
        } else {
            $actions[] = Display::url(
                Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
                $this->baseUrl
            );
        }

        if (api_is_allowed_to_edit()) {
            $actions[] = Display::url(
                Display::getMdiIcon(ObjectIcon::TICKET, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Tags')),
                $this->baseUrl.'action=tags'
            );
        }

        $frmStudentList = null;
        $frmTagList = null;

        $categories = [];
        $portfolio = [];
        if ($this->course) {
            $frmTagList = $this->createFormTagFilter($listByUser);
            $frmStudentList = $this->createFormStudentFilter($listByUser, $listHighlighted, $listAlphabetical);
            $frmStudentList->setDefaults(['user' => $this->owner->getId()]);
            // it translates the category title with the current user language
            $categories = $this->getCategoriesForIndex();
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
            $items = $this->getItemsForIndex($listByUser, $frmTagList, $listAlphabetical);

            $foundComments = $this->getCommentsForIndex($frmTagList);
        }

        // it gets and translate the subcategories
        $categoryId = $httpRequest->query->getInt('categoryId');
        $subCategoryIdsReq = isset($_REQUEST['subCategoryIds']) ? Security::remove_XSS($_REQUEST['subCategoryIds']) : '';
        $subCategoryIds = $subCategoryIdsReq;
        if ('all' !== $subCategoryIdsReq) {
            $subCategoryIds = !empty($subCategoryIdsReq) ? explode(',', $subCategoryIdsReq) : [];
        }
        $subcategories = [];
        if ($categoryId > 0) {
            $subcategories = $this->getCategoriesForIndex($categoryId);
            if (count($subcategories) > 0) {
                foreach ($subcategories as &$subcategory) {
                    $translated = $this->translateDisplayName($subcategory->getTitle());
                    $subcategory->setTitle($translated);
                }
            }
        }

        $context = [
            'user' => $this->owner,
            'listByUser' => $listByUser,
            'course' => $this->course,
            'session' => $this->session,
            'portfolio' => $portfolio,
            'categories' => $categories,
            'uncategorized_items' => $items,
            'frm_student_list' => $this->course ? $frmStudentList->returnForm() : '',
            'frm_tag_list' => $this->course ? $frmTagList->returnForm() : '',
            'category_id' => $categoryId,
            'subcategories' => $subcategories,
            'subcategory_ids' => $subCategoryIds,
            'found_comments' => $foundComments,
            '_p' => Template::getLegacyP(),
            '_c' => Template::getLegacyC(),
        ];

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
        $context['js_script'] = $js;

        $content = Container::getTwig()->render('@ChamiloCore/Portfolio/list.html.twig', $context);

        Display::addFlash(
            Display::return_message(get_lang('Portfolio tool introduction'), 'info', false)
        );

        $this->renderView($content, get_lang('Portfolio'), $actions);
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

        $frmTagList->addDatePicker('date', get_lang('Creation date'));

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
            $frmTagList->addHidden('cid', $this->course->getId());
            $frmTagList->addHidden('sid', $this->session ? $this->session->getId() : 0);
            $frmTagList->addHidden('gid', 0);
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
    private function createFormStudentFilter(
        bool $listByUser = false,
        bool $listHighlighted = false,
        bool $listAlphabeticalOrder = false
    ): FormValidator {
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

        $slctUser = $frmStudentList->addSelectAjax(
            'user',
            get_lang('Select a learner portfolio'),
            [],
            [
                'url' => api_get_path(WEB_AJAX_PATH)."course.ajax.php?$urlParams",
                'placeholder' => get_lang('Search users'),
                'formatResult' => SelectAjax::templateResultForUsersInCourse(),
                'formatSelection' => SelectAjax::templateSelectionForUsersInCourse(),
            ]
        );

        if ($listByUser) {
            $slctUser->addOption(
                $this->owner->getFullName(),
                $this->owner->getId(),
                [
                    'data-avatarurl' => UserManager::getUserPicture($this->owner->getId()),
                    'data-username' => $this->owner->getUsername(),
                ]
            );

            $link = Display::url(
                get_lang('Back to the main course portfolio'),
                $this->baseUrl
            );
        } else {
            $link = Display::url(
                get_lang('See my portfolio in this course'),
                $this->baseUrl.http_build_query(['user' => api_get_user_id()])
            );
        }

        $frmStudentList->addHtml("<p>$link</p>");

        if ($listHighlighted) {
            $link = Display::url(
                get_lang('Back to the main course portfolio'),
                $this->baseUrl
            );
        } else {
            $link = Display::url(
                get_lang('See highlights'),
                $this->baseUrl.http_build_query(['list_highlighted' => true])
            );
        }

        $frmStudentList->addHtml("<p>$link</p>");

        if (true !== api_get_configuration_value('portfolio_order_post_by_alphabetical_order')) {
            if ($listAlphabeticalOrder) {
                $link = Display::url(
                    get_lang('View in chronological order'),
                    $this->baseUrl
                );
            } else {
                $link = Display::url(
                    get_lang('View in alphabetical order'),
                    $this->baseUrl.http_build_query(['list_alphabetical' => true])
                );
            }

            $frmStudentList->addHtml("<p>$link</p>");
        }

        return $frmStudentList;
    }

    private function getHighlightedItems()
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
            ->select('pi')
            ->from(Portfolio::class, 'pi')
            ->innerJoin('pi.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->where('links.course = :course')
            ->andWhere('pi.isHighlighted = TRUE')
            ->setParameter('course', $this->course)
        ;

        if ($this->session) {
            $queryBuilder->andWhere('links.session = :session');
            $queryBuilder->setParameter('session', $this->session->getId());
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        if ($this->advancedSharingEnabled) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq('links.user', ':current_user'))
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->eq('pi.visibility', PortfolioComment::VISIBILITY_PER_USER),
                        $queryBuilder->expr()->andX(
                            $queryBuilder->expr()
                                ->eq('pi.visibility', PortfolioComment::VISIBILITY_VISIBLE),
                            $queryBuilder->expr()->eq('node.creator', ':current_user')
                        )
                    )
                )
            ;
        } else {
            $visibilityCriteria = [Portfolio::VISIBILITY_VISIBLE];

            if (api_is_allowed_to_edit()) {
                $visibilityCriteria[] = Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER;
            }

            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        'node.creator = :current_user',
                        $queryBuilder->expr()->andX(
                            'node.creator != :current_user',
                            $queryBuilder->expr()->in('pi.visibility', $visibilityCriteria)
                        )
                    )
                )
            ;
        }

        $queryBuilder->setParameter('current_user', api_get_user_id());
        $queryBuilder->orderBy('node.createdAt', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    private function getItemsForIndex(
        bool $listByUser = false,
        ?FormValidator $frmFilterList = null,
        bool $alphabeticalOrder = false
    ) {
        $currentUserId = api_get_user_id();

        $portfolioRepo = Container::getPortfolioRepository();

        if ($this->course) {
            $showBaseContentInSession = $this->session
                && 'true' === api_get_setting('platform.portfolio_show_base_course_post_in_sessions');

            $portfolioCategoryHelper = Container::getPortfolioCategoryHelper();

            $filters = $frmFilterList && $frmFilterList->validate() ? $frmFilterList->exportValues() : [];

            $searchInCategories = [];

            if ($categoryId = $filters['categoryId'] ?? null) {
                $searchInCategories[] = $categoryId;

                foreach ($portfolioCategoryHelper->getListForIndex($categoryId) as $subCategory) {
                    $searchInCategories[] = $subCategory->getId();
                }
            }

            $searchNotInCategories = [];

            if ($subCategoryIdList = $filters['subCategoryIds'] ?? '') {
                $diff = [];

                if ('all' !== $subCategoryIdList) {
                    $subCategoryIds = explode(',', $subCategoryIdList);
                    $diff = array_diff($searchInCategories, $subCategoryIds);
                } elseif ('' === trim($subCategoryIdList)) {
                    $diff = $searchInCategories;
                }

                if (!empty($diff)) {
                    unset($diff[0]);

                    $searchNotInCategories = $diff;
                }
            }

            $items = $portfolioRepo->getIndexCourseItems(
                api_get_user_entity(),
                $this->owner,
                $this->course,
                $this->session,
                $showBaseContentInSession,
                $listByUser,
                $filters['date'] ?? null,
                $filters['tags'] ?? [],
                $filters['text'] ?? '',
                $searchInCategories,
                $searchNotInCategories,
                $this->advancedSharingEnabled
            );

            if ($showBaseContentInSession) {
                $items = array_filter(
                    $items,
                    function (Portfolio $item) {
                        $itemResourceLink = $item->getFirstResourceLink();

                        return !($this->session && !$itemResourceLink?->getSession()
                            && $item->isDuplicatedInSession($this->session));
                    }
                );
            }

            return $items;
        }
        $queryBuilder = $portfolioRepo->getResourcesByCreator($this->owner);
        $queryBuilder->andWhere($queryBuilder->expr()->isNull('resource.category'));

        if ($currentUserId !== $this->owner->getId()) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq('resource.visibility', ':visible'))
                ->setParameter('visible', Portfolio::VISIBILITY_VISIBLE)
            ;
        }

        return $queryBuilder
            ->orderBy('node.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    private function getCommentsForIndex(?FormValidator $frmFilterList = null): array
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

        $commentsRepo = Container::getPortfolioCommentRepository();
        $queryBuilder = $commentsRepo->getResources();

        if (!empty($values['date'])) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->gte('node.createdAt', ':date'))
                ->setParameter(':date', api_get_utc_datetime($values['date'], false, true))
            ;
        }

        if (!empty($values['text'])) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->like('resource.content', ':text'))
                ->setParameter('text', '%'.$values['text'].'%')
            ;
        }

        if ($this->advancedSharingEnabled) {
            // @todo change to left join with resource_link to get resources with advanced sharing
            if ($this->course) {
                $queryBuilder
                    ->andWhere($queryBuilder->expr()->eq('links.course', ':course'))
                    ->setParameter('course', $this->course->getId())
                ;
            }

            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq('links.user', ':current_user'))
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->eq('resource.visibility', Portfolio::VISIBILITY_VISIBLE),
                        $queryBuilder->expr()->andX(
                            $queryBuilder->expr()->eq('resource.visibility', Portfolio::VISIBILITY_PER_USER),
                            $queryBuilder->expr()->eq('node.creator', ':current_user')
                        )
                    )
                )
                ->setParameter('current_user', $this->owner->getId())
            ;
        }

        $queryBuilder->orderBy('node.createdAt', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param mixed $urlUser
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function view(Portfolio $item, $urlUser): void
    {
        global $interbreadcrumb;

        /** @var ResourceLinkRepository $resourceLinkRepo */
        $resourceLinkRepo = Database::getManager()->getRepository(ResourceLink::class);

        $courseLink = $item->getFirstResourceLinkFromCourseSession($this->course, $this->session);
        $itemCourse = $courseLink?->getCourse();
        $itemSession = $courseLink?->getSession();

        if (!$this->itemBelongToOwner($item)) {
            if ($this->advancedSharingEnabled) {
                $userLink = $resourceLinkRepo->findLinkForResourceInContext(
                    $item,
                    $this->course,
                    $this->session,
                    null,
                    null,
                    $this->owner
                );

                if (Portfolio::VISIBILITY_PER_USER === $item->getVisibility() && !$userLink) {
                    api_not_allowed(true);
                }
            } elseif (Portfolio::VISIBILITY_HIDDEN === $item->getVisibility()
                || (Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER === $item->getVisibility() && !api_is_allowed_to_edit())
            ) {
                api_not_allowed(true);
            }
        }

        Container::getEventDispatcher()->dispatch(
            new PortfolioItemViewedEvent(['portfolio' => $item]),
            Events::PORTFOLIO_ITEM_VIEWED
        );

        $form = $this->createCommentForm($item);

        $commentsRepo = Container::getPortfolioCommentRepository();

        if ($this->advancedSharingEnabled) {
            $commentsQueryBuilder = $commentsRepo->getResources();
            $commentsQueryBuilder
                ->andWhere($commentsQueryBuilder->expr()->eq('links.course', ':course'))
                ->andWhere($commentsQueryBuilder->expr()->eq('links.user', ':current_user'))
                ->andWhere(
                    $commentsQueryBuilder->expr()->orX(
                        $commentsQueryBuilder->expr()->eq('resource.visibility', PortfolioComment::VISIBILITY_PER_USER),
                        $commentsQueryBuilder->expr()->andX(
                            $commentsQueryBuilder->expr()
                                ->eq('resource.visibility', PortfolioComment::VISIBILITY_VISIBLE),
                            $commentsQueryBuilder->expr()->eq('node.creator', ':current_user')
                        )
                    )
                )
                ->setParameter('course', $this->course->getId())
                ->setParameter('current_user', $this->owner->getId())
            ;
        } else {
            $commentsQueryBuilder = $commentsRepo->getResourcesIgnoringLinks();
        }

        $commentsQueryBuilder
            ->andWhere($commentsQueryBuilder->expr()->eq('resource.item', ':item'))
            ->setParameter('item', $item->getId())
        ;

        if ('true' === api_get_setting('platform.portfolio_show_base_course_post_in_sessions')
            && $this->session
            && !$itemSession
            && !$item->isDuplicatedInSession($this->session)
        ) {
            $comments = [];
        } else {
            $comments = $commentsQueryBuilder
                ->orderBy('node.level', 'ASC')
                ->addOrderBy('node.createdAt', 'ASC')
                ->getQuery()
                ->getResult()
            ;
        }

        $clockIcon = Display::getMdiIcon('calendar-range', null, '', ICON_SIZE_TINY);

        $commentsHtml = $this->renderCommentsTree($comments, $item, $clockIcon);

        $context = [];
        $context['baseurl'] = $this->baseUrl;
        $context['item'] = $item;
        $context['course'] = $itemCourse;
        $context['session'] = $itemSession;
        $context['item_content'] = $this->generateItemContent($item);
        $context['count_comments'] = count($comments);
        $context['comments'] = $commentsHtml;
        $context['form'] = $form;
        $context['attachment_list'] = $this->generateAttachmentList($item);

        if ($itemCourse) {
            $context['last_edit'] = [
                'date' => $item->resourceNode->getUpdatedAt(),
                'user' => $item->resourceNode->getCreator()->getFullName(),
            ];
        }

        $content = Container::getTwig()->render('@ChamiloCore/Portfolio/view.html.twig', $context);

        $interbreadcrumb[] = ['name' => get_lang('Portfolio'), 'url' => $this->baseUrl];

        $editLink = Display::url(
            Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit')),
            $this->baseUrl.http_build_query(['action' => 'edit_item', 'id' => $item->getId()])
        );

        $urlUserString = '';
        if (!empty($urlUser)) {
            $urlUserString = 'user='.$urlUser;
        }

        $actions = [];
        $actions[] = Display::url(
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            $this->baseUrl.$urlUserString
        );

        if ($this->itemBelongToOwner($item)) {
            $actions[] = $editLink;

            $actions[] = Display::url(
                Display::getMdiIcon(
                    ActionIcon::FIX,
                    $item->isTemplate() ? 'ch-tool-icon' : 'ch-tool-icon-disabled',
                    null,
                    ICON_SIZE_MEDIUM,
                    $item->isTemplate() ? get_lang('Remove template') : get_lang('Add as a template'),
                ),
                $this->baseUrl.http_build_query(['action' => 'template', 'id' => $item->getId()])
            );

            if ($this->advancedSharingEnabled) {
                $actions[] = Display::url(
                    Display::getMdiIcon(ActionIcon::VISIBLE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Choose recipients')),
                    $this->baseUrl.http_build_query(['action' => 'item_visiblity_choose', 'id' => $item->getId()])
                );
            } else {
                $visibilityUrl = $this->baseUrl.http_build_query(['action' => 'visibility', 'id' => $item->getId()]);

                if (Portfolio::VISIBILITY_HIDDEN === $item->getVisibility()) {
                    $actions[] = Display::url(
                        Display::getMdiIcon(ActionIcon::INVISIBLE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Make visible')),
                        $visibilityUrl
                    );
                } elseif (Portfolio::VISIBILITY_VISIBLE === $item->getVisibility()) {
                    $actions[] = Display::url(
                        Display::getMdiIcon(ActionIcon::VISIBLE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Make visible for teachers')),
                        $visibilityUrl
                    );
                } elseif (Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER === $item->getVisibility()) {
                    $actions[] = Display::url(
                        Display::getMdiIcon(StateIcon::CLOSED_VISIBILITY, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Make invisible')),
                        $visibilityUrl
                    );
                }
            }

            $actions[] = Display::url(
                Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Delete')),
                $this->baseUrl.http_build_query(['action' => 'delete_item', 'id' => $item->getId()])
            );
        } else {
            $actions[] = Display::url(
                Display::getMdiIcon(ActionIcon::COPY_CONTENT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Copy to my portfolio')),
                $this->baseUrl.http_build_query(['action' => 'copy', 'copy' => 'item', 'id' => $item->getId()])
            );
        }

        if (api_is_allowed_to_edit()) {
            $actions[] = Display::url(
                Display::getMdiIcon(ActionIcon::COPY_CONTENT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Copy to student portfolio')),
                $this->baseUrl.http_build_query(['action' => 'teacher_copy', 'copy' => 'item', 'id' => $item->getId()])
            );
            $actions[] = $editLink;

            $highlightedUrl = $this->baseUrl.http_build_query(['action' => 'highlighted', 'id' => $item->getId()]);

            if ($item->isHighlighted()) {
                $actions[] = Display::url(
                    Display::getMdiIcon(ActionIcon::AWARD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Unmark as highlighted')),
                    $highlightedUrl
                );
            } else {
                $actions[] = Display::url(
                    Display::getMdiIcon(ActionIcon::AWARD, 'ch-tool-icon-disabled', null, ICON_SIZE_MEDIUM, get_lang('Mark as highlighted')),
                    $highlightedUrl
                );
            }

            if ($itemCourse && '1' === api_get_course_setting('qualify_portfolio_item')) {
                $actions[] = Display::url(
                    Display::getMdiIcon(ObjectIcon::TEST, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Grade this item')),
                    $this->baseUrl.http_build_query(['action' => 'qualify', 'item' => $item->getId()])
                );
            }
        }

        $this->renderView($content, $item->getTitle(true), $actions, false);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    private function createCommentForm(Portfolio $item): string
    {
        $formAction = $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()]);
        $templates = Container::getPortfolioCommentRepository()->findTemplatesByUser($this->owner);

        $form = new FormValidator('frm_comment', 'post', $formAction);
        $form->addHeader(get_lang('Add a new comment'));
        $form->addSelectFromCollection(
            'template',
            [
                get_lang('Template'),
                null,
                '<span id="portfolio-spinner" class="fa fa-fw fa-spinner fa-spin" style="display: none;" aria-hidden="true" aria-label="'.get_lang('Loading').'"></span>',
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

        $itemResourceLink = $item->getFirstResourceLink();

        if ($form->validate()) {
            if ($this->session
                && true === api_get_configuration_value('portfolio_show_base_course_post_in_sessions')
                && !$itemResourceLink->getSession()
            ) {
                $duplicate = $item->duplicateInSession($this->session);

                $this->em->persist($duplicate);
                $this->em->flush();

                $item = $duplicate;

                $formAction = $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()]);
            }

            $values = $form->exportValues();

            $parentComment = $this->em->find(PortfolioComment::class, $values['parent']);

            $comment = new PortfolioComment();
            $comment
                ->setCreator($this->owner)
                ->setParent($parentComment ?: $item)
                ->setContent($values['content'])
                ->setDate(api_get_utc_datetime(null, false, true))
                ->setItem($item)
            ;

            $this->em->persist($comment);
            $this->em->flush();

            $this->processAttachments(
                $form,
                $comment->getId(),
                Portfolio::TYPE_COMMENT
            );

            Container::getEventDispatcher()->dispatch(
                new PortfolioItemCommentedEvent(['comment' => $comment]),
                Events::PORTFOLIO_ITEM_COMMENTED
            );

            PortfolioNotifier::notifyTeachersAndAuthor($comment);

            Display::addFlash(
                Display::return_message(get_lang('You comment has been added'), 'success')
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

    /**
     * @param PortfolioComment[] $comments
     */
    private function renderCommentsTree(array $comments, Portfolio $item, string $clockIcon): string
    {
        if (empty($comments)) {
            return '';
        }

        $commentsByParentNodeId = [];
        $rootComments = [];
        $commentNodeIdMap = [];

        foreach ($comments as $comment) {
            $resourceNode = $comment->getResourceNode();
            if (!$resourceNode) {
                continue;
            }
            $commentNodeIdMap[$resourceNode->getId()] = $comment;
        }

        $itemNodeId = $item->getResourceNode()?->getId();

        foreach ($comments as $comment) {
            $resourceNode = $comment->getResourceNode();
            if (!$resourceNode) {
                continue;
            }

            $parentNode = $resourceNode->getParent();
            $parentNodeId = $parentNode?->getId();

            if (!$parentNodeId || $parentNodeId === $itemNodeId || !isset($commentNodeIdMap[$parentNodeId])) {
                $rootComments[] = $comment;
            } else {
                $commentsByParentNodeId[$parentNodeId][] = $comment;
            }
        }

        $html = '<div class="media-list">';
        foreach ($rootComments as $comment) {
            $html .= $this->renderCommentNode($comment, $commentsByParentNodeId, $commentNodeIdMap, $item, $clockIcon);
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param array<int, PortfolioComment[]> $commentsByParentNodeId
     * @param array<int, PortfolioComment>   $commentNodeIdMap
     */
    private function renderCommentNode(
        PortfolioComment $comment,
        array $commentsByParentNodeId,
        array $commentNodeIdMap,
        Portfolio $item,
        string $clockIcon
    ): string {
        $author = $comment->getCreator();

        $userPicture = UserManager::getUserPicture(
            $author->getId(),
            USER_IMAGE_SIZE_SMALL,
            null,
            [
                'picture_uri' => $author->getPictureUri(),
                'email' => $author->getEmail(),
            ]
        );

        $html = '<article class="media" id="comment-'.$comment->getId().'">'
            .'<div class="media-left"><img class="media-object thumbnail" src="'.$userPicture.'" alt="'
            .$author->getFullName().'"></div>'
            .'<div class="media-body">';

        $commentActions = [];

        if ($this->commentBelongsToOwner($comment)) {
            $commentActions[] = Display::url(
                Display::getMdiIcon(
                    ActionIcon::FIX,
                    $item->isTemplate() ? 'ch-tool-icon' : 'ch-tool-icon-disabled',
                    null,
                    ICON_SIZE_MEDIUM,
                    $item->isTemplate() ? get_lang('Remove as template') : get_lang('Add as a template'),
                ),
                $this->baseUrl.http_build_query(['action' => 'template_comment', 'id' => $comment->getId()])
            );
        }

        $commentActions[] = Display::url(
            Display::getMdiIcon(
                ActionIcon::COMMENT,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Reply to this comment')
            ),
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
            Display::getMdiIcon(
                ActionIcon::COPY_CONTENT,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Copy to my portfolio')
            ),
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
                Display::getMdiIcon(
                    ActionIcon::COPY_CONTENT,
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_MEDIUM,
                    get_lang('Copy to student portfolio')
                ),
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
                    Display::getMdiIcon(
                        ObjectIcon::PIN,
                        'ch-tool-icon',
                        null,
                        ICON_SIZE_MEDIUM,
                        get_lang('Unmark comment as important')
                    ),
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
                    Display::getMdiIcon(
                        ObjectIcon::PIN,
                        'ch-tool-icon',
                        null,
                        ICON_SIZE_MEDIUM,
                        get_lang('Mark comment as important')
                    ),
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
                    Display::getMdiIcon(
                        ObjectIcon::TEST,
                        'ch-tool-icon',
                        null,
                        ICON_SIZE_MEDIUM,
                        get_lang('Grade this comment')
                    ),
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
                    Display::getMdiIcon(
                        ActionIcon::VISIBLE,
                        'ch-tool-icon',
                        null,
                        ICON_SIZE_MEDIUM,
                        get_lang('Choose recipients')
                    ),
                    $this->baseUrl.http_build_query(['action' => 'comment_visiblity_choose', 'id' => $comment->getId()])
                );
            }

            $commentActions[] = Display::url(
                Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit')),
                $this->baseUrl.http_build_query(['action' => 'edit_comment', 'id' => $comment->getId()])
            );
            $commentActions[] = Display::url(
                Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Delete')),
                $this->baseUrl.http_build_query(['action' => 'delete_comment', 'id' => $comment->getId()])
            );
        }

        $html .= '<div class="pull-right">'.implode(\PHP_EOL, $commentActions).'</div>'.\PHP_EOL
            .'<footer class="media-heading h4">'.\PHP_EOL
            .'<p>'.$author->getFullName().'</p>'.\PHP_EOL;

        if ($comment->isImportant()
            && ($this->itemBelongToOwner($comment->getItem()) || $isAllowedToEdit)
        ) {
            $html .= '<span class="pull-right label label-warning origin-style">'
                .get_lang('Portfolio item marked as important')
                .'</span>'.\PHP_EOL;
        }

        $html .= '<small>'.$clockIcon.\PHP_EOL
            .$this->getLabelForCommentDate($comment).'</small>'.\PHP_EOL;

        $html .= '</footer>'.\PHP_EOL
            .Security::remove_XSS($comment->getContent()).\PHP_EOL;

        $html .= $this->generateAttachmentList($comment);

        $nodeId = $comment->getResourceNode()?->getId();
        if ($nodeId && !empty($commentsByParentNodeId[$nodeId])) {
            $html .= '<div class="media-list">';
            foreach ($commentsByParentNodeId[$nodeId] as $childComment) {
                $html .= $this->renderCommentNode(
                    $childComment,
                    $commentsByParentNodeId,
                    $commentNodeIdMap,
                    $item,
                    $clockIcon
                );
            }
            $html .= '</div>';
        }

        $html .= '</div></article>';

        return $html;
    }

    private function commentBelongsToOwner(PortfolioComment $comment): bool
    {
        return $comment->getCreator() === $this->owner;
    }

    private function getLabelForCommentDate(PortfolioComment $comment): string
    {
        $dateLabel = Display::dateToStringAgoAndLongDate($comment->getDate()).\PHP_EOL;

        if ($comment->getDate() < $comment->resourceNode->getUpdatedAt()) {
            $dateLabel .= '|'.\PHP_EOL
                .sprintf(
                    get_lang('Updated %s'),
                    Display::dateToStringAgoAndLongDate($comment->resourceNode->getUpdatedAt())
                );
        }

        return $dateLabel;
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
                $originContent = Security::remove_XSS($origin->getContent());
                $originContentFooter = vsprintf(
                    get_lang('Originally published as "%s" by %s'),
                    [
                        "<cite>{$origin->getTitle(true)}</cite>",
                        $origin->getUser()->getFullName(),
                    ]
                );
            }
        } elseif (Portfolio::TYPE_COMMENT === $item->getOriginType()) {
            $origin = $em->find(PortfolioComment::class, $item->getOrigin());

            if ($origin) {
                $originContent = Security::remove_XSS($origin->getContent());
                $originContentFooter = vsprintf(
                    get_lang('Originally commented by %s in "%s"'),
                    [
                        $origin->getCreator()->getFullName(),
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
                <div class=\"clearfix\">".Security::remove_XSS($item->getContent()).'</div>';
        }

        return Security::remove_XSS($item->getContent());
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function copyItem(Portfolio $originItem): void
    {
        $this->blockIsNotAllowed();

        $currentTime = api_get_utc_datetime(null, false, true);

        $portfolio = new Portfolio();
        $portfolio
            ->setVisibility(Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER)
            ->setTitle(
                sprintf(get_lang('Portfolio item by %s'), $originItem->getUser()->getFullName())
            )
            ->setContent('')
            ->setUser($this->owner)
            ->setOrigin($originItem->getId())
            ->setOriginType(Portfolio::TYPE_ITEM)
            ->setCourse($this->course)
            ->setSession($this->session)
            ->setCreationDate($currentTime)
            ->setUpdateDate($currentTime)
        ;

        $this->em->persist($portfolio);
        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('Portfolio item added'), 'success')
        );

        header("Location: $this->baseUrl".http_build_query(['action' => 'edit_item', 'id' => $portfolio->getId()]));

        exit;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function copyComment(PortfolioComment $originComment): void
    {
        $currentTime = api_get_utc_datetime(null, false, true);

        $portfolio = new Portfolio();
        $portfolio
            ->setVisibility(Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER)
            ->setTitle(
                sprintf(get_lang('Comment by %s'), $originComment->getCreator()->getFullName())
            )
            ->setContent('')
            ->setUser($this->owner)
            ->setOrigin($originComment->getId())
            ->setOriginType(Portfolio::TYPE_COMMENT)
            ->setCourse($this->course)
            ->setSession($this->session)
            ->setCreationDate($currentTime)
            ->setUpdateDate($currentTime)
        ;

        $this->em->persist($portfolio);
        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('Portfolio item added'), 'success')
        );

        header("Location: $this->baseUrl".http_build_query(['action' => 'edit_item', 'id' => $portfolio->getId()]));

        exit;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function teacherCopyItem(Portfolio $originItem): void
    {
        api_protect_teacher_script();

        $actionParams = http_build_query(['action' => 'teacher_copy', 'copy' => 'item', 'id' => $originItem->getId()]);

        $form = new FormValidator('teacher_copy_portfolio', 'post', $this->baseUrl.$actionParams);

        if ('true' === api_get_setting('editor.save_titles_as_html')) {
            $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
        } else {
            $form->addText('title', get_lang('Title'));
            $form->applyFilter('title', 'trim');
        }

        $form->addLabel(
            sprintf(get_lang('Portfolio item by %s'), $originItem->getCreator()->getFullName()),
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
            get_lang('Learners'),
            [],
            [
                'url' => api_get_path(WEB_AJAX_PATH)."course.ajax.php?$urlParams",
                'multiple' => true,
            ]
        );
        $form->addRule('students', get_lang('Required field'), 'required');
        $form->addButtonCreate(get_lang('Save'));

        $toolName = get_lang('Copy to student portfolio');
        $content = $form->returnForm();

        if ($form->validate()) {
            $values = $form->exportValues();

            foreach ($values['students'] as $studentId) {
                $owner = api_get_user_entity($studentId);

                $portfolio = new Portfolio();
                $portfolio
                    ->setVisibility(Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER)
                    ->setTitle($values['title'])
                    ->setContent($values['content'])
                    ->setCreator($owner)
                    ->setParent($owner)
                    ->setOrigin($originItem->getId())
                    ->setOriginType(Portfolio::TYPE_ITEM)
                ;

                if ($this->course) {
                    $portfolio->addCourseLink($this->course, $this->session);
                }

                $this->em->persist($portfolio);
            }

            $this->em->flush();

            Display::addFlash(
                Display::return_message(get_lang('Item added to students own portfolio'), 'success')
            );

            header("Location: $this->baseUrl");

            exit;
        }

        $this->renderView($content, $toolName);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function teacherCopyComment(PortfolioComment $originComment): void
    {
        $actionParams = http_build_query(
            [
                'action' => 'teacher_copy',
                'copy' => 'comment',
                'id' => $originComment->getId(),
            ]
        );

        $form = new FormValidator('teacher_copy_portfolio', 'post', $this->baseUrl.$actionParams);

        if ('true' === api_get_setting('editor.save_titles_as_html')) {
            $form->addHtmlEditor('title', get_lang('Title'), true, false, ['ToolbarSet' => 'TitleAsHtml']);
        } else {
            $form->addText('title', get_lang('Title'));
            $form->applyFilter('title', 'trim');
        }

        $form->addLabel(
            sprintf(get_lang('Comment from %s'), $originComment->getCreator()->getFullName()),
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
            get_lang('Learners'),
            [],
            [
                'url' => api_get_path(WEB_AJAX_PATH)."course.ajax.php?$urlParams",
                'multiple' => true,
            ]
        );
        $form->addRule('students', get_lang('Required field'), 'required');
        $form->addButtonCreate(get_lang('Save'));

        $toolName = get_lang('Copy to student portfolio');
        $content = $form->returnForm();

        if ($form->validate()) {
            $values = $form->exportValues();

            foreach ($values['students'] as $studentId) {
                $owner = api_get_user_entity($studentId);

                $portfolio = new Portfolio();
                $portfolio
                    ->setVisibility(Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER)
                    ->setTitle($values['title'])
                    ->setContent($values['content'])
                    ->setCreator($owner)
                    ->setParent($owner)
                    ->setOrigin($originComment->getId())
                    ->setOriginType(Portfolio::TYPE_COMMENT)
                ;

                if ($this->course) {
                    $portfolio->addCourseLink($this->course, $this->session);
                }

                $this->em->persist($portfolio);
            }

            $this->em->flush();

            Display::addFlash(
                Display::return_message(get_lang('Item added to students own portfolio'), 'success')
            );

            header("Location: $this->baseUrl");

            exit;
        }

        $this->renderView($content, $toolName);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function markImportantCommentInItem(Portfolio $item, PortfolioComment $comment): void
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
            Display::return_message(get_lang('Portfolio item marked as important'), 'success')
        );

        header("Location: $this->baseUrl".http_build_query(['action' => 'view', 'id' => $item->getId()]));

        exit;
    }

    /**
     * @throws Exception
     */
    public function details(HttpRequest $httpRequest): void
    {
        $this->blockIsNotAllowed();

        $currentUserId = api_get_user_id();
        $isAllowedToFilterStudent = $this->course && api_is_allowed_to_edit();

        $actions = [];
        $actions[] = Display::url(
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            $this->baseUrl
        );
        $actions[] = Display::url(
            Display::getMdiIcon(
                ObjectIcon::PDF,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Export my portfolio data in a PDF file')
            ),
            $this->baseUrl.http_build_query(['action' => 'export_pdf'])
        );
        $actions[] = Display::url(
            Display::getMdiIcon(
                ActionIcon::EXPORT_ARCHIVE,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Export my portfolio data in a ZIP file')
            ),
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
                    Display::getMdiIcon(
                        ObjectIcon::PDF,
                        'ch-tool-icon',
                        null,
                        ICON_SIZE_MEDIUM,
                        get_lang('Export my portfolio data in a PDF file')
                    ),
                    $this->baseUrl.http_build_query(['action' => 'export_pdf', 'user' => $this->owner->getId()])
                );
                $actions[2] = Display::url(
                    Display::getMdiIcon(
                        ActionIcon::EXPORT_ARCHIVE,
                        'ch-tool-icon',
                        null,
                        ICON_SIZE_MEDIUM,
                        get_lang('Export my portfolio data in a ZIP file')
                    ),
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
                    get_lang('Select a learner portfolio'),
                    [],
                    [
                        'url' => api_get_path(WEB_AJAX_PATH)."course.ajax.php?$urlParams",
                        'placeholder' => get_lang('Search users'),
                        'formatResult' => SelectAjax::templateResultForUsersInCourse(),
                        'formatSelection' => SelectAjax::templateSelectionForUsersInCourse(),
                    ]
                )
                ->addOption(
                    $this->owner->getFullName(),
                    $this->owner->getId(),
                    [
                        'data-avatarurl' => UserManager::getUserPicture($this->owner->getId()),
                        'data-username' => $this->owner->getUsername(),
                    ]
                )
            ;
            $frmStudent->setDefaults(['user' => $this->owner->getId()]);
            $frmStudent->addHidden('action', 'details');
            $frmStudent->addHidden('cid', $this->course->getId());
            $frmStudent->addHidden('sid', $this->session ? $this->session->getId() : 0);
            $frmStudent->addButtonFilter(get_lang('Filter'));
        }

        $itemsRepo = Container::getPortfolioRepository();
        $commentsRepo = $this->em->getRepository(PortfolioComment::class);

        $getItemsTotalNumber = function () use ($itemsRepo, $isAllowedToFilterStudent, $currentUserId) {
            $qb = $itemsRepo->createQueryBuilder('i');
            $qb
                ->select('COUNT(i)')
                ->where('i.user = :user')
                ->setParameter('user', $this->owner)
            ;

            if ($this->course) {
                $qb
                    ->andWhere('i.course = :course')
                    ->setParameter('course', $this->course)
                ;

                if ($this->session) {
                    $qb
                        ->andWhere('i.session = :session')
                        ->setParameter('session', $this->session)
                    ;
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
        $getItemsData = function ($from, $limit, $columnNo, $orderDirection) use (
            $itemsRepo,
            $isAllowedToFilterStudent,
            $currentUserId
        ) {
            $qb = $itemsRepo->createQueryBuilder('item')
                ->where('item.user = :user')
                ->leftJoin('item.category', 'category')
                ->leftJoin('item.course', 'course')
                ->leftJoin('item.session', 'session')
                ->setParameter('user', $this->owner)
            ;

            if ($this->course) {
                $qb
                    ->andWhere('item.course = :course_id')
                    ->setParameter('course_id', $this->course)
                ;

                if ($this->session) {
                    $qb
                        ->andWhere('item.session = :session')
                        ->setParameter('session', $this->session)
                    ;
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
        $tblItems->set_header(1, get_lang('Creation date'), true, [], ['class' => 'text-center']);
        $tblItems->set_column_filter(1, $convertFormatDateColumnFilter);
        $tblItems->set_header(2, get_lang('Last update'), true, [], ['class' => 'text-center']);
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
                ->innerJoin('c.resourceNode', 'cNode')
                ->where('cNode.creator = :author')
                ->setParameter('author', $this->owner)
            ;

            if ($this->course) {
                $qb
                    ->innerJoin('c.item', 'i')
                    ->andWhere('i.course = :course')
                    ->setParameter('course', $this->course)
                ;

                if ($this->session) {
                    $qb
                        ->andWhere('i.session = :session')
                        ->setParameter('session', $this->session)
                    ;
                } else {
                    $qb->andWhere('i.session IS NULL');
                }
            }

            return $qb->getQuery()->getSingleScalarResult();
        };
        $getCommentsData = function ($from, $limit, $columnNo, $orderDirection) use ($commentsRepo) {
            $qb = $commentsRepo->createQueryBuilder('comment');
            $qb
                ->innerJoin('comment.resourceNode', 'commentNode')
                ->where('commentNode.creator = :user')
                ->innerJoin('comment.item', 'item')
                ->setParameter('user', $this->owner)
            ;

            if ($this->course) {
                $qb
                    ->innerJoin('comment.item', 'i')
                    ->andWhere('item.course = :course')
                    ->setParameter('course', $this->course)
                ;

                if ($this->session) {
                    $qb
                        ->andWhere('item.session = :session')
                        ->setParameter('session', $this->session)
                    ;
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
        $tblComments->set_header(2, get_lang('Item title'));
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
                get_lang('%d added / %d required'),
                $totalNumberOfItems,
                $requiredNumberOfItems
            );
        }

        $content .= Display::page_subheader2(
            get_lang('Portfolio items'),
            $itemsSubtitle
        ).\PHP_EOL;

        if ($totalNumberOfItems > 0) {
            $content .= $tblItems->return_table().\PHP_EOL;
        } else {
            $content .= Display::return_message(get_lang('No items in your portfolio'), 'warning');
        }

        $commentsSubtitle = '';

        if ($requiredNumberOfComments > 0) {
            $commentsSubtitle = sprintf(
                get_lang('%d added / %d required'),
                $totalNumberOfComments,
                $requiredNumberOfComments
            );
        }

        $content .= Display::page_subheader2(
            get_lang('Comments made'),
            $commentsSubtitle
        ).\PHP_EOL;

        if ($totalNumberOfComments > 0) {
            $content .= $tblComments->return_table().\PHP_EOL;
        } else {
            $content .= Display::return_message(get_lang('You have not commented'), 'warning');
        }

        $this->renderView($content, get_lang('Portfolio details'), $actions);
    }

    /**
     * @throws MpdfException
     */
    public function exportPdf(HttpRequest $httpRequest): void
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

        $pdfContent = Display::page_header($this->owner->getFullName());

        if ($this->course) {
            $pdfContent .= '<p>'.get_lang('Course').': ';

            if ($this->session) {
                $pdfContent .= $this->session->getTitle().' ('.$this->course->getTitle().')';
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

        $items = Container::getPortfolioRepository()
            ->findItemsByUser(
                $this->owner,
                $this->course,
                $this->session,
                null,
                $visibility
            )
        ;
        $comments = $this->em
            ->getRepository(PortfolioComment::class)
            ->findCommentsByUser($this->owner, $this->course, $this->session)
        ;

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
                get_lang('%d added / %d required'),
                $totalNumberOfItems,
                $requiredNumberOfItems
            );
        }

        if ($requiredNumberOfComments > 0) {
            $commentsSubtitle = sprintf(
                get_lang('%d added / %d required'),
                $totalNumberOfComments,
                $requiredNumberOfComments
            );
        }

        $pdfContent .= Display::page_subheader2(
            get_lang('Portfolio items'),
            $itemsSubtitle
        );

        if ($totalNumberOfItems > 0) {
            $pdfContent .= implode(\PHP_EOL, $itemsHtml);
        } else {
            $pdfContent .= Display::return_message(get_lang('No items in your portfolio'), 'warning');
        }

        $pdfContent .= Display::page_subheader2(
            get_lang('Comments made'),
            $commentsSubtitle
        );

        if ($totalNumberOfComments > 0) {
            $pdfContent .= implode(\PHP_EOL, $commentsHtml);
        } else {
            $pdfContent .= Display::return_message(get_lang('You have not commented'), 'warning');
        }

        $pdfName = $this->owner->getFullName()
            .($this->course ? '_'.$this->course->getCode() : '')
            .'_'.get_lang('Portfolio');

        Container::getEventDispatcher()->dispatch(
            new PortfolioItemDownloadedEvent(['owner' => $this->owner]),
            Events::PORTFOLIO_DOWNLOADED,
        );

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

    private function getItemsInHtmlFormatted(array $items): array
    {
        $itemsHtml = [];

        /** @var Portfolio $item */
        foreach ($items as $item) {
            $courseLink = $item->getFirstResourceLinkFromCourseSession($this->course, $this->session);

            $creationDate = api_convert_and_format_date($item->resourceNode->getCreatedAt(), DATE_TIME_FORMAT_LONG);
            $updateDate = api_convert_and_format_date($item->resourceNode->getUpdatedAt(), DATE_TIME_FORMAT_LONG);

            $metadata = '<ul class="list-unstyled text-muted">';

            if ($courseLink && $this->session) {
                $metadata .= '<li>'.get_lang('Course').': '.$this->session->getTitle().' ('
                    .$this->course->getTitle().') </li>';
            } elseif ($courseLink) {
                $metadata .= '<li>'.get_lang('Course').': '.$this->course->getTitle().'</li>';
            }

            $metadata .= '<li>'.sprintf(get_lang('Creation date: %s'), $creationDate).'</li>';

            if ($courseLink) {
                if ($item->resourceNode->getUpdatedAt() > $item->resourceNode->getCreatedAt()) {
                    $metadata .= '<li>'
                        .sprintf(
                            get_lang('Updated on %s by %s'),
                            $updateDate,
                            $item->getCreator()->getFullName()
                        )
                        .'</li>';
                }
            } else {
                $metadata .= '<li>'.sprintf(get_lang('Update date: %s'), $updateDate).'</li>';
            }

            if ($item->getCategory()) {
                $metadata .= '<li>'.sprintf(get_lang('Category: %s'), $item->getCategory()->getTitle()).'</li>';
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
            $metadata .= '<li>'.sprintf(get_lang('Date: %s'), $date).'</li>';
            $metadata .= '<li>'.sprintf(get_lang('Item title: %s'), Security::remove_XSS($item->getTitle()))
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

    /**
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function exportZip(HttpRequest $httpRequest): void
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

        $commentsRepo = Container::getPortfolioCommentRepository();
        $resourceNodeRepo = Container::getResourceNodeRepository();

        $visibility = [];

        if ($isAllowedToFilterStudent && $currentUserId !== $this->owner->getId()) {
            $visibility[] = Portfolio::VISIBILITY_VISIBLE;
            $visibility[] = Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER;
        }

        $items = Container::getPortfolioRepository()->findItemsByUser(
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

        $tblItemsHeaders = [];
        $tblItemsHeaders[] = get_lang('Title');
        $tblItemsHeaders[] = get_lang('Creation date');
        $tblItemsHeaders[] = get_lang('Last update');
        $tblItemsHeaders[] = get_lang('Category');
        $tblItemsHeaders[] = get_lang('Category');
        $tblItemsHeaders[] = get_lang('Score');
        $tblItemsHeaders[] = get_lang('Course');
        $tblItemsHeaders[] = get_lang('Session');
        $tblItemsData = [];

        $tblCommentsHeaders = [];
        $tblCommentsHeaders[] = get_lang('Resume');
        $tblCommentsHeaders[] = get_lang('Date');
        $tblCommentsHeaders[] = get_lang('Item title');
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

            $itemDirectory = $item->resourceNode->getCreatedAt()->format('Y-m-d-H-i-s');

            $itemFilename = sprintf('%s/items/%s/item.html', $tempPortfolioDirectory, $itemDirectory);
            $imagePaths = [];
            $itemFileContent = $this->fixMediaSourcesToHtml($itemsHtml[$i], $imagePaths);

            $fs->dumpFile($itemFilename, $itemFileContent);

            $filenames[] = $itemFilename;

            foreach ($imagePaths as $imagePath) {
                $inlineFile = dirname($itemFilename).'/'.basename($imagePath);

                try {
                    $filenames[] = $inlineFile;
                    $fs->copy($imagePath, $inlineFile);
                } catch (FileNotFoundException) {
                    continue;
                }
            }

            $attachments = $item->resourceNode->getResourceFiles();

            foreach ($attachments as $attachment) {
                $attachmentFilename = sprintf(
                    '%s/items/%s/attachments/%s',
                    $tempPortfolioDirectory,
                    $itemDirectory,
                    $attachment->getTitle()
                );

                try {
                    $path = $resourceNodeRepo->getFilename($attachment);
                    $content = $resourceNodeRepo->getFileSystem()->read($path);

                    $fs->dumpFile(
                        $attachmentFilename,
                        $content
                    );
                    $filenames[] = $attachmentFilename;
                } catch (FileNotFoundException|FilesystemException) {
                    continue;
                }
            }

            $tblItemsData[] = [
                Display::url(
                    Security::remove_XSS($item->getTitle()),
                    sprintf('items/%s/item.html', $itemDirectory)
                ),
                api_convert_and_format_date($item->resourceNode->getCreatedAt()),
                api_convert_and_format_date($item->resourceNode->getUpdatedAt()),
                $itemCategory?->getTitle(),
                $item->getComments()->count(),
                $item->getScore(),
                $this->course->getTitle(),
                $this->session?->getTitle(),
            ];
        }

        /**
         * @var int              $i
         * @var PortfolioComment $comment
         */
        foreach ($comments as $i => $comment) {
            $commentDirectory = $comment->getDate()->format('Y-m-d-H-i-s');

            $imagePaths = [];
            $commentFileContent = $this->fixMediaSourcesToHtml($commentsHtml[$i], $imagePaths);
            $commentFilename = sprintf('%s/comments/%s/comment.html', $tempPortfolioDirectory, $commentDirectory);

            $fs->dumpFile($commentFilename, $commentFileContent);

            $filenames[] = $commentFilename;

            foreach ($imagePaths as $imagePath) {
                $inlineFile = dirname($commentFilename).'/'.basename($imagePath);

                try {
                    $filenames[] = $inlineFile;
                    $fs->copy($imagePath, $inlineFile);
                } catch (FileNotFoundException) {
                    continue;
                }
            }

            $attachments = $comment->resourceNode->getResourceFiles();

            foreach ($attachments as $attachment) {
                $attachmentFilename = sprintf(
                    '%s/comments/%s/attachments/%s',
                    $tempPortfolioDirectory,
                    $commentDirectory,
                    $attachment->getTitle()
                );

                try {
                    $path = $resourceNodeRepo->getFilename($attachment);
                    $content = $resourceNodeRepo->getFileSystem()->read($path);

                    $fs->dumpFile(
                        $attachmentFilename,
                        $content
                    );
                    $filenames[] = $attachmentFilename;
                } catch (FileNotFoundException|FilesystemException) {
                    continue;
                }
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

        $zipName = $this->owner->getFullName()
            .($this->course ? '_'.$this->course->getCode() : '')
            .'_'.get_lang('Portfolio');
        $tempZipFile = $sysArchivePath."portfolio/$zipName.zip";
        $zip = new PclZip($tempZipFile);

        foreach ($filenames as $filename) {
            $zip->add($filename, PCLZIP_OPT_REMOVE_PATH, $tempPortfolioDirectory);
        }

        Container::getEventDispatcher()->dispatch(
            new PortfolioItemDownloadedEvent(['owner' => $this->owner]),
            Events::PORTFOLIO_DOWNLOADED,
        );

        DocumentManager::file_send_for_download($tempZipFile, true, "$zipName.zip");

        $fs->remove($tempPortfolioDirectory);
        $fs->remove($tempZipFile);
    }

    /**
     * @param array $imagePaths Relative paths found in $htmlContent
     */
    private function fixMediaSourcesToHtml(string $htmlContent, array &$imagePaths): string
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($htmlContent);

        $tagsWithSrc = ['img', 'video', 'audio', 'source'];

        /** @var array<int, DOMElement> $elements */
        $elements = [];

        foreach ($tagsWithSrc as $tag) {
            foreach ($doc->getElementsByTagName($tag) as $element) {
                if ($element->hasAttribute('src')) {
                    $elements[] = $element;
                }
            }
        }

        if (empty($elements)) {
            return $htmlContent;
        }

        /** @var array<int, DOMElement> $anchorElements */
        $anchorElements = $doc->getElementsByTagName('a');

        $webPath = api_get_path(WEB_PATH);
        $sysPath = rtrim(api_get_path(SYS_PATH), '/');

        $paths = [
            '/app/upload/' => $sysPath,
            '/courses/' => $sysPath.'/app',
        ];

        foreach ($elements as $element) {
            $src = trim($element->getAttribute('src'));

            if (!str_starts_with($src, '/')
                && !str_starts_with($src, $webPath)
            ) {
                continue;
            }

            // to search anchors linking to files
            if ($anchorElements->length > 0) {
                foreach ($anchorElements as $anchorElement) {
                    if (!$anchorElement->hasAttribute('href')) {
                        continue;
                    }

                    if ($src === $anchorElement->getAttribute('href')) {
                        $anchorElement->setAttribute('href', basename($src));
                    }
                }
            }

            $src = str_replace($webPath, '/', $src);

            foreach ($paths as $prefix => $basePath) {
                if (str_starts_with($src, $prefix)) {
                    $imagePaths[] = $basePath.urldecode($src);
                    $element->setAttribute('src', basename($src));
                }
            }
        }

        return $doc->saveHTML();
    }

    private function formatZipIndexFile(HTML_Table $tblItems, HTML_Table $tblComments): string
    {
        $htmlContent = Display::page_header($this->owner->getFullNameWithUsername());
        $htmlContent .= Display::page_subheader2(get_lang('Portfolio items'));

        $htmlContent .= $tblItems->getRowCount() > 0
            ? $tblItems->toHtml()
            : Display::return_message(get_lang('No items in your portfolio'), 'warning');

        $htmlContent .= Display::page_subheader2(get_lang('Comments made'));

        $htmlContent .= $tblComments->getRowCount() > 0
            ? $tblComments->toHtml()
            : Display::return_message(get_lang('You have not commented'), 'warning');

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
        //$stylesheet3->setAttribute('href', ChamiloApi::getEditorDocStylePath());

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

    public function qualifyItem(Portfolio $item): void
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
            [get_lang('Score'), null, ' / '.api_get_course_setting('portfolio_max_score')]
        );
        $form->addButtonSave(get_lang('Grade this item'));

        if ($form->validate()) {
            $values = $form->exportValues();

            $item->setScore($values['score']);

            $em->persist($item);
            $em->flush();

            Container::getEventDispatcher()->dispatch(
                new PortfolioItemScoredEvent(['portfolio' => $item]),
                Events::PORTFOLIO_ITEM_SCORED
            );

            Display::addFlash(
                Display::return_message(get_lang('Portfolio item was graded'), 'success')
            );

            header("Location: $formAction");

            exit;
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
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()])
        );

        $this->renderView($form->returnForm(), get_lang('Qualify'), $actions);
    }

    public function qualifyComment(PortfolioComment $comment): void
    {
        global $interbreadcrumb;

        $em = Database::getManager();

        $item = $comment->getItem();
        $commentPath = $em->getRepository(PortfolioComment::class)->getPath($comment);

        $commentContext = Container::getTwig()->render(
            '@ChamiloCore/Portfolio/comment_context.html.twig',
            [
                'item' => $item,
                'comment_path' => $commentPath,
            ]
        );

        $formAction = $this->baseUrl.http_build_query(['action' => 'qualify', 'comment' => $comment->getId()]);

        $form = new FormValidator('frm_qualify', 'post', $formAction);
        $form->addHtml($commentContext);
        $form->addUserAvatar('user', get_lang('Author'));
        $form->addLabel(get_lang('Comment'), $comment->getContent());
        $form->addNumeric(
            'score',
            [get_lang('Score'), null, '/ '.api_get_course_setting('portfolio_max_score')]
        );
        $form->addButtonSave(get_lang('Grade this comment'));

        if ($form->validate()) {
            $values = $form->exportValues();

            $comment->setScore($values['score']);

            $em->persist($comment);
            $em->flush();

            Container::getEventDispatcher()->dispatch(
                new PortfolioCommentScoredEvent(['comment' => $comment]),
                Events::PORTFOLIO_COMMENT_SCORED
            );

            Display::addFlash(
                Display::return_message(get_lang('Portfolio comment was graded'), 'success')
            );

            header("Location: $formAction");

            exit;
        }

        $form->setDefaults(
            [
                'user' => $comment->getCreator(),
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
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()])
        );

        $this->renderView($form->returnForm(), get_lang('Qualify'), $actions);
    }

    public function downloadAttachment(HttpRequest $httpRequest): void
    {
        $nodeId = $httpRequest->query->getInt('node');
        $attachmentId = $httpRequest->query->getInt('attachment');

        $resourceNode = Container::getResourceNodeRepository()->find($nodeId);
        $attachment = Container::getResourceFileRepository()->find($attachmentId);

        $isGranted = Container::$container
            ->get('security.authorization_checker')
            ->isGranted(ResourceNodeVoter::VIEW, $resourceNode)
        ;

        if (!$isGranted) {
            api_not_allowed(true);
        }

        $fileName = $attachment->getOriginalName();
        $fileSize = $attachment->getSize();
        $mimeType = $attachment->getMimeType() ?: '';
        [$start, $end, $length] = $this->getRange($httpRequest, $fileSize);

        // Convert the file name to ASCII using iconv
        $fileName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $fileName);

        // MIME normalization for HTML
        $looksLikeHtmlByExt = (bool) preg_match('/\.x?html?$/i', (string) $fileName);
        if ('' === $mimeType || false === stripos($mimeType, 'html')) {
            if ($looksLikeHtmlByExt) {
                $mimeType = 'text/html; charset=UTF-8';
            }
        }

        $response = new StreamedResponse(
            function () use ($resourceNode, $attachment, $start, $length): void {
                $stream = $resourceNode->getResourceNodeFileStream(
                    $attachment->getResourceNode(),
                    $attachment
                );

                $this->echoBuffer($stream, $start, $length);
            }
        );

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $mimeType ?: 'application/octet-stream');
        $response->headers->set('Content-Length', (string) $length);
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('Content-Range', "bytes $start-$end/$fileSize");
        $response->setStatusCode(
            $start > 0 || $end < $fileSize - 1 ? Response::HTTP_PARTIAL_CONTENT : Response::HTTP_OK
        );

        $response->send();
    }

    public function deleteAttachment(HttpRequest $httpRequest): never
    {
        $nodeId = $httpRequest->query->getInt('node');
        $attachmentId = $httpRequest->query->getInt('attachment');

        $attachment = Container::getResourceFileRepository()->find($attachmentId);
        $resourceNode = Container::getResourceNodeRepository()->find($nodeId);
        $commentRepo = Container::getPortfolioCommentRepository();
        $itemRepo = Container::getPortfolioRepository();

        $isGranted = Container::$container
            ->get('security.authorization_checker')
            ->isGranted(ResourceNodeVoter::VIEW, $resourceNode)
        ;

        if (!$isGranted) {
            api_not_allowed(true);
        }

        $this->em->remove($attachment);
        $this->em->flush();

        $comment = $commentRepo->findOneBy(['resourceNode' => $nodeId]);

        if ($comment) {
            $item = $comment->getItem();
        } else {
            $item = $itemRepo->findOneBy(['resourceNode' => $nodeId]);
        }

        if ($httpRequest->isXmlHttpRequest()) {
            echo Display::return_message(get_lang('The attached file has been deleted'), 'success');
        } else {
            Display::addFlash(
                Display::return_message(get_lang('The attached file has been deleted'), 'success')
            );

            $url = $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item?->getId()]);

            if (Portfolio::TYPE_COMMENT === $attachment->getOriginType() && isset($comment)) {
                $url .= '#comment-'.$comment->getId();
            }

            header("Location: $url");
        }

        exit;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function markAsHighlighted(Portfolio $item): void
    {
        $courseLink = $item->getFirstResourceLinkFromCourseSession($this->course, $this->session);

        if (!$courseLink) {
            api_not_allowed(true);
        }

        $item->setIsHighlighted(
            !$item->isHighlighted()
        );

        Database::getManager()->flush();

        if ($item->isHighlighted()) {
            Container::getEventDispatcher()->dispatch(
                new PortfolioItemHighlightedEvent(['portfolio' => $item]),
                Events::PORTFOLIO_ITEM_HIGHLIGHTED
            );
        }

        Display::addFlash(
            Display::return_message(
                $item->isHighlighted() ? get_lang('Marked as highlighted') : get_lang('Unmarked as highlighted'),
                'success'
            )
        );

        header("Location: $this->baseUrl".http_build_query(['action' => 'view', 'id' => $item->getId()]));

        exit;
    }

    public function markAsTemplate(Portfolio $item): void
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
                $item->isTemplate() ? get_lang('Portfolio item set as a new template')
                    : get_lang('Portfolio item unset as template'),
                'success'
            )
        );

        header("Location: $this->baseUrl".http_build_query(['action' => 'view', 'id' => $item->getId()]));

        exit;
    }

    public function markAsTemplateComment(PortfolioComment $comment): void
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
                $comment->isTemplate() ? get_lang('Portfolio comment set as a new template')
                    : get_lang('Portfolio comment unset as template'),
                'success'
            )
        );

        header("Location: $this->baseUrl".http_build_query(['action' => 'view', 'id' => $comment->getItem()->getId()]));

        exit;
    }

    public function listTags(HttpRequest $request): void
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
                Display::return_message(get_lang('Tag saved'), 'success')
            );

            header('Location: '.$this->baseUrl.http_build_query($formAction));

            exit;
        }
        $form->protect();

        if ($tag) {
            $form->setDefaults(['name' => $tag->getTag()]);
        }

        $langTags = get_lang('Tags');
        $langEdit = get_lang('Edit');

        $deleteIcon = Display::getMdiIcon(
            ActionIcon::DELETE,
            'ch-tool-icon',
            null,
            ICON_SIZE_MEDIUM,
            get_lang('Delete')
        );
        $editIcon = Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, $langEdit);

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
                    ->getResult()
                ;

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

                return Display::url($editIcon, $this->baseUrl.$editParams).\PHP_EOL
                    .Display::url($deleteIcon, $this->baseUrl.$deleteParams).\PHP_EOL;
            }
        );
        $table->set_additional_parameters(
            [
                'action' => 'tags',
                'cid' => $this->course->getId(),
                'sid' => $this->session ? $this->session->getId() : 0,
                'gid' => 0,
            ]
        );

        $content = $form->returnForm().\PHP_EOL
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

    public function deleteTag(Tag $tag): void
    {
        api_protect_course_script();
        api_protect_teacher_script();

        $em = Database::getManager();
        $portfolioTagRepo = $em->getRepository(PortfolioRelTag::class);

        $portfolioTag = $portfolioTagRepo
            ->findOneBy(['tag' => $tag, 'course' => $this->course, 'session' => $this->session])
        ;

        if ($portfolioTag) {
            $em->remove($portfolioTag);
            $em->flush();

            Display::addFlash(
                Display::return_message(get_lang('Tag deleted'), 'success')
            );
        }

        header('Location: '.$this->baseUrl.http_build_query(['action' => 'tags']));

        exit;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function editComment(PortfolioComment $comment): void
    {
        global $interbreadcrumb;

        if (!$this->commentBelongsToOwner($comment)) {
            api_not_allowed(true);
        }

        $item = $comment->getItem();

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
            $values = $form->exportValues();

            $comment->setContent($values['content']);
            $comment->resourceNode->setUpdatedAt(new DateTime());

            $this->em->flush();

            $this->processAttachments(
                $form,
                $comment->getId(),
                Portfolio::TYPE_COMMENT
            );

            Container::getEventDispatcher()->dispatch(
                new PortfolioCommentEditedEvent(['comment' => $comment]),
                Events::PORTFOLIO_COMMENT_EDITED
            );

            Display::addFlash(
                Display::return_message(get_lang('Item updated'), 'success')
            );

            header(
                "Location: $this->baseUrl"
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
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            $this->baseUrl
        );

        $content = $form->returnForm()
            .\PHP_EOL
            .'<div class="row"> <div class="col-sm-8 col-sm-offset-2">'
            .$this->generateAttachmentList($comment)
            .'</div></div>';

        $this->renderView(
            $content,
            get_lang('Edit portfolio comment'),
            $actions
        );
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function deleteComment(PortfolioComment $comment): void
    {
        if (!$this->commentBelongsToOwner($comment)) {
            api_not_allowed(true);
        }

        $this->em->remove($comment);

        $this->em->flush();

        Display::addFlash(
            Display::return_message(get_lang('The comment has been deleted.'), 'success')
        );

        header("Location: $this->baseUrl");

        exit;
    }

    public function itemVisibilityChooser(Portfolio $item): void
    {
        global $interbreadcrumb;

        if (!$this->itemBelongToOwner($item)) {
            api_not_allowed(true);
        }

        $em = Database::getManager();

        $formAction = $this->baseUrl.http_build_query(['action' => 'item_visiblity_choose', 'id' => $item->getId()]);

        $form = new FormValidator('visibility', 'post', $formAction);
        CourseManager::addUserGroupMultiSelect($form, ['USER:'.$this->owner->getId()]);
        $form->addLabel(
            '',
            Display::return_message(
                get_lang('Only selected users will see the content')
                .'<br>'.get_lang('Leave empty to enable the content for everyone'),
                'info',
                false
            )
        );
        $form->addCheckBox('hidden', '', get_lang('Hidden but visible for me'));
        $form->addButtonSave(get_lang('Save'));

        if ($form->validate()) {
            $values = $form->exportValues();
            $values['users'] ??= [];
            ['users' => $recipients] = CourseManager::separateUsersGroups($values['users']);

            /** @var ResourceLinkRepository $resourceLinkRepo */
            $resourceLinkRepo = Database::getManager()->getRepository(ResourceLink::class);
            $resourceLinkRepo->removeUserLinks($item, $this->course, $this->session);

            if (empty($recipients) && empty($values['hidden'])) {
                $item
                    ->setVisibility(Portfolio::VISIBILITY_VISIBLE)
                    ->setParent($item->getCreator())
                    ->addCourseLink($this->course, $this->session)
                ;
            } else {
                if (empty($values['hidden'])) {
                    foreach ($recipients as $userId) {
                        $item->addUserLink(
                            api_get_user_entity($userId),
                            $this->course,
                            $this->session
                        );
                    }
                }

                $item->setVisibility(Portfolio::VISIBILITY_PER_USER);
            }

            $em->flush();

            Container::getEventDispatcher()->dispatch(
                new PortfolioItemVisibilityChangedEvent([
                    'portfolio' => $item,
                    'recipients' => array_values($recipients),
                ]),
                Events::PORTFOLIO_ITEM_VISIBILITY_CHANGED
            );

            Display::addFlash(
                Display::return_message(get_lang('Post visibility changed'), 'success')
            );

            header("Location: $formAction");

            exit;
        }

        ['users' => $recipients] = $item->getUsersAndGroupSubscribedToResource();

        $recipients = array_map(
            static fn (int $userId): string => 'USER:'.$userId,
            $recipients
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
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()])
        );

        $this->renderView(
            $form->returnForm(),
            get_lang('Choose recipients'),
            $actions
        );
    }

    public function commentVisibilityChooser(PortfolioComment $comment): void
    {
        global $interbreadcrumb;

        if (!$this->commentBelongsToOwner($comment)) {
            api_not_allowed(true);
        }

        $em = Database::getManager();

        $item = $comment->getItem();

        $formAction = $this->baseUrl.http_build_query(['action' => 'comment_visiblity_choose', 'id' => $comment->getId()]);

        $form = new FormValidator('visibility', 'post', $formAction);
        CourseManager::addUserGroupMultiSelect($form, ['USER:'.$this->owner->getId()]);
        $form->addLabel(
            '',
            Display::return_message(
                get_lang('Only selected users will see the content')
                    .'<br>'.get_lang('Leave empty to enable the content for everyone'),
                'info',
                false
            )
        );
        $form->addCheckBox('hidden', '', get_lang('Hidden but visible for me'));
        $form->addButtonSave(get_lang('Save'));

        if ($form->validate()) {
            $values = $form->exportValues();
            $values['users'] ??= [];
            ['users' => $recipients] = CourseManager::separateUsersGroups($values['users']);

            /** @var ResourceLinkRepository $resourceLinkRepo */
            $resourceLinkRepo = Database::getManager()->getRepository(ResourceLink::class);
            $resourceLinkRepo->removeUserLinks($comment, $this->course, $this->session);

            if (empty($recipients) && empty($values['hidden'])) {
                $comment->setVisibility(PortfolioComment::VISIBILITY_VISIBLE);
            } else {
                if (empty($values['hidden'])) {
                    foreach ($recipients as $userId) {
                        $comment->addUserLink(
                            api_get_user_entity($userId),
                            $this->course,
                            $this->session
                        );
                    }
                }

                $comment->setVisibility(PortfolioComment::VISIBILITY_PER_USER);
            }

            $em->flush();

            Display::addFlash(
                Display::return_message(get_lang('The visibility has been changed.'), 'success')
            );

            header("Location: $formAction");

            exit;
        }

        ['users' => $recipients] = $comment->getUsersAndGroupSubscribedToResource();

        $recipients = array_map(
            static fn (array $itemProperty): string => 'USER:'.$itemProperty['to_user_id'],
            $recipients
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
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
            $this->baseUrl.http_build_query(['action' => 'view', 'id' => $item->getId()])
        );

        $this->renderView(
            $form->returnForm(),
            get_lang('Choose recipients'),
            $actions
        );
    }

    /**
     * @return array<int, int>
     */
    protected function getRange(Request $request, int $fileSize): array
    {
        $range = $request->headers->get('Range');

        if ($range) {
            [, $range] = explode('=', $range, 2);
            [$start, $end] = explode('-', $range);

            $start = (int) $start;
            $end = ('' === $end) ? $fileSize - 1 : (int) $end;

            $length = $end - $start + 1;
        } else {
            $start = 0;
            $end = $fileSize - 1;
            $length = $fileSize;
        }

        return [$start, $end, $length];
    }

    /**
     * @param resource $stream
     */
    protected function echoBuffer($stream, int $start, int $length): void
    {
        fseek($stream, $start);

        $bytesSent = 0;

        while ($bytesSent < $length && !feof($stream)) {
            $buffer = fread($stream, min(1024 * 8, $length - $bytesSent));

            echo $buffer;

            $bytesSent += \strlen($buffer);
        }

        fclose($stream);
    }
}
