<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLpItem;

/**
 * Class LearnPathItemForm.
 */
class LearnPathItemForm
{
    public static function setForm(FormValidator $form, $action, learnpath $lp, CLpItem $lpItem)
    {
        $arrLP = $lp->getItemsForForm();
        $lp->tree_array($arrLP);
        $arrLP = $lp->arrMenu ?? [];

        switch ($action) {
            case 'add':
                $form->addHeader(get_lang('Add'));
                self::setItemTitle($form);

                break;

            case 'edit':
                $form->addHeader(get_lang('Edit'));
                self::setItemTitle($form);

                break;

            case 'move':
                $form->addHeader(get_lang('Move'));

                break;
        }

        $itemId = $lpItem->getIid();
        $itemTitle = $lpItem->getTitle();
        $itemDescription = $lpItem->getDescription();
        $parentItemId = $lpItem->getParentItemId();
        $itemType = $lpItem->getItemType();
        $previousItemId = $lpItem->getPreviousItemId();

        $count = count($arrLP);
        // Parent
        $parentSelect = $form->addSelect(
            'parent',
            get_lang('Parent'),
            [],
            [
                'id' => 'idParent',
                'onchange' => 'javascript:load_cbo(this.value);',
            ]
        );

        $lpItemRepo = Container::getLpItemRepository();
        $itemRoot = $lpItemRepo->getItemRoot($lp->get_id());
        $parentSelect->addOption($lp->name, $itemRoot->getIid());
        /** @var CLpItem[] $sections */
        $sections = $lpItemRepo->findBy(['itemType' => 'dir', 'lp' => $lp->get_id()]);
        foreach ($sections as $key => $value) {
            $parentSelect->addOption(
                str_repeat('&nbsp;', $value->getLvl()).Security::remove_XSS($value->getTitle()),
                $value->getIid()
            );
        }

        $parentSelect->setSelected($parentItemId);

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $arrHide = [];
        // Position
        for ($i = 0; $i < $count; $i++) {
            if (($arrLP[$i]['parent_item_id'] == $parentItemId && $arrLP[$i]['id'] != $itemId) ||
                TOOL_LP_FINAL_ITEM == $arrLP[$i]['item_type']
            ) {
                $arrHide[$arrLP[$i]['id']]['value'] = get_lang('After').' "'.$arrLP[$i]['title'].'"';
            }
        }

        $position = $form->addSelect(
            'previous',
            get_lang('Position'),
            [],
            ['id' => 'previous']
        );

        $position->addOption(get_lang('First position'), 0);

        foreach ($arrHide as $key => $value) {
            $padding = $value['padding'] ?? 20;
            $position->addOption(
                $value['value'],
                $key,
                'style="padding-left:'.$padding.'px;"'
            );
        }

        $position->setSelected($previousItemId);

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        if (TOOL_LP_FINAL_ITEM == $itemType) {
            $parentSelect->freeze();
            $position->freeze();
        }

        // Content.
        if (in_array($itemType, [TOOL_DOCUMENT, TOOL_LP_FINAL_ITEM, TOOL_READOUT_TEXT], true)) {
            $document = null;
            if (!empty($lpItem->getPath())) {
                $repo = Container::getDocumentRepository();
                /** @var CDocument $document */
                $document = $repo->find($lpItem->getPath());
            }

            $editorConfig = [
                'ToolbarSet' => 'LearningPathDocuments',
                'Width' => '100%',
                'Height' => '500',
                'FullPage' => true,
                //   'CreateDocumentDir' => $relative_prefix,
                //'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/',
                //'BaseHref' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/'.$relative_path,
            ];

            if (($document && $document->getResourceNode()->hasEditableTextContent()) || 'add' === $action) {
                $renderer = $form->defaultRenderer();
                $renderer->setElementTemplate('&nbsp;{label}{element}', 'content_lp');
                $form->addElement('html', '<div class="editor-lp">');
                $form->addHtmlEditor('content_lp', null, null, true, $editorConfig, true);
                $form->addElement('html', '</div>');
                if ($document) {
                    $form->addHidden('document_id', $document->getIid());
                    $content = $lp->display_document(
                        $document,
                        false,
                        false
                    );
                    $form->setDefault('content_lp', $content);
                }
            }
        }

        if ($form->hasElement('title')) {
            $form->setDefault('title', $itemTitle);
        }
        if ($form->hasElement('description')) {
            $form->setDefault('description', $itemDescription);
        }

        $form->addHidden('id', $itemId);
        $form->addHidden('type', $itemType);
        $form->addHidden('post_time', time());
        $form->addHidden('path', $lpItem->getPath());
    }

    public static function setItemTitle(FormValidator $form)
    {
        if (api_get_configuration_value('save_titles_as_html')) {
            $form->addHtmlEditor(
                'title',
                get_lang('Title'),
                true,
                false,
                ['ToolbarSet' => 'TitleAsHtml', 'id' => uniqid('editor')]
            );
        } else {
            $form->addText('title', get_lang('Title'), true, ['id' => 'idTitle', 'class' => 'learnpath_item_form']);
            $form->applyFilter('title', 'trim');
            $form->applyFilter('title', 'html_filter');
        }
    }
}
