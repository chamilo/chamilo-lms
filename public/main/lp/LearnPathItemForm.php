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
        //$previousItemId = $lpItem->getPreviousItemId();

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
        $rootItem = $lpItemRepo->getRootItem($lp->get_id());
        $parentSelect->addOption($lp->name, $rootItem->getIid());
        /** @var CLpItem[] $sections */
        $sections = $lpItemRepo->findBy(['itemType' => 'dir', 'lp' => $lp->get_id()]);
        foreach ($sections as $value) {
            $parentSelect->addOption(
                str_repeat('&nbsp;', $value->getLvl()).Security::remove_XSS($value->getTitle()),
                $value->getIid()
            );
        }

        $parentSelect->setSelected($parentItemId);

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        if (TOOL_LP_FINAL_ITEM == $itemType) {
            $parentSelect->freeze();
        }

        if (in_array($itemType, [TOOL_DOCUMENT, TOOL_LP_FINAL_ITEM, TOOL_READOUT_TEXT], true)) {
            $document = null;
            if (!empty($lpItem->getPath())) {
                $repo = Container::getDocumentRepository();
                /** @var CDocument $document */
                $document = $repo->find($lpItem->getPath());
            }

            $editorConfig = [
                'ToolbarSet' => 'Basic',
                'Width' => '100%',
                'Height' => '500',
            ];

            $isAdd = ($action === 'add');
            $isEdit = ($action === 'edit');
            $isDoc = ($lpItem->getItemType() === TOOL_DOCUMENT);
            if (($document && $document->getResourceNode()->hasEditableTextContent()) || $isAdd) {
                $renderer = $form->defaultRenderer();
                $renderer->setElementTemplate('&nbsp;{label}{element}', 'content_lp');
                $form->addHtml('<div class="editor-lp">');
                $form->addHtmlEditor('content_lp', null, null, true, $editorConfig);
                $form->addHtml('</div>');
                if ($document) {
                    $form->addHidden('document_id', $document->getIid());
                    $content = $lp->display_document($document, false, false);
                    $form->setDefault('content_lp', $content);
                }
            }

            $canShowExportFlag = false;
            if ($isDoc) {
                if ($isAdd) {
                    $canShowExportFlag = true;
                } elseif ($isEdit && $document) {
                    $node = $document->getResourceNode();
                    $file = $node?->getFirstResourceFile();
                    $mime = (string) $file?->getMimeType();
                    $isHtmlEditable = $node->hasEditableTextContent()
                        || in_array($mime, ['text/html', 'application/xhtml+xml'], true);
                    $canShowExportFlag = $isHtmlEditable;
                }
            }

            if ($canShowExportFlag) {
                $form->addElement('checkbox', 'export_allowed', get_lang('Allow PDF export for this item'));
                $form->setDefaults([
                    'export_allowed' => $isEdit ? ($lpItem->isExportAllowed() ? 1 : 0) : 1,
                ]);
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
        if ('true' === api_get_setting('editor.save_titles_as_html')) {
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
