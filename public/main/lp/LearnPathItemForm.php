<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLpItem;
use ChamiloSession as Session;

class LearnPathItemForm
{
    public static function setForm(FormValidator $form, $action, learnpath $lp, CLpItem $lpItem)
    {
        $itemId = $lpItem->getIid();
        $item_title = $lpItem->getTitle();
        $item_description = $lpItem->getDescription();
        $parent = $lpItem->getParentItemId();
        $item_type = $lpItem->getItemType();

        $arrLP = $lp->getItemsForForm();
        $lp->tree_array($arrLP);
        $arrLP = isset($lp->arrMenu) ? $lp->arrMenu : [];

        switch ($action) {
            case 'add':
                $form->addHeader(get_lang('Add'));

                self::setItemTitle($form);

                break;

            case 'edit':
                $form->addHeader(get_lang('Edit'));
                /*if (isset($data['id'])) {
                    $defaults['directory_parent_id'] = $data['id'];
                }*/
                self::setItemTitle($form);

                break;

            case 'move':
                $form->addHeader(get_lang('Move'));

                break;
        }

        $id = $lpItem->getIid();
        $arrHide = [];
        $count = count($arrLP);
        $sections = [];
        for ($i = 0; $i < $count; $i++) {
            if ('add' !== $action) {
                if ('dir' === $arrLP[$i]['item_type'] &&
                    !in_array($arrLP[$i]['id'], $arrHide) &&
                    !in_array($arrLP[$i]['parent_item_id'], $arrHide)
                ) {
                    $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                    $arrHide[$arrLP[$i]['id']]['padding'] = 20 + $arrLP[$i]['depth'] * 20;
                }
            }

            if ('dir' === $arrLP[$i]['item_type']) {
                $sections[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
                $sections[$arrLP[$i]['id']]['padding'] = 20 + $arrLP[$i]['depth'] * 20;
            }
        }

        $parentSelect = $form->addSelect(
            'parent',
            get_lang('Parent'),
            [],
            [
                'id' => 'idParent',
                'onchange' => 'javascript:load_cbo(this.value);',
            ]
        );
        $parentSelect->addOption($lp->name, 0);

        $arrHide = [];
        for ($i = 0; $i < $count; $i++) {
            if ($arrLP[$i]['id'] != $id && 'dir' != $arrLP[$i]['item_type']) {
                $arrHide[$arrLP[$i]['id']]['value'] = $arrLP[$i]['title'];
            }
        }

        $sectionCount = 0;
        foreach ($sections as $key => $value) {
            if (0 != $sectionCount) {
                // The LP name is also the first section and is not in the same charset like the other sections.
                $value['value'] = Security::remove_XSS($value['value']);
                $parentSelect->addOption(
                    $value['value'],
                    $key
                    //,'style="padding-left:'.$value['padding'].'px;"'
                );
            } else {
                $value['value'] = Security::remove_XSS($value['value']);
                $parentSelect->addOption(
                    $value['value'],
                    $key
                    //'style="padding-left:'.$value['padding'].'px;"'
                );
            }
            $sectionCount++;
        }

        if (!empty($itemId)) {
            $parentSelect->setSelected($parent);
        } else {
            $parent_item_id = Session::read('parent_item_id', 0);
            $parentSelect->setSelected($parent_item_id);
        }

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $arrHide = [];
        // POSITION
        for ($i = 0; $i < $count; $i++) {
            if (($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $itemId) ||
                TOOL_LP_FINAL_ITEM == $arrLP[$i]['item_type']
            ) {
                $arrHide[$arrLP[$i]['id']]['value'] = get_lang('After').' "'.$arrLP[$i]['title'].'"';
            }
        }

        $selectedPosition = $lpItem ? $lpItem->getPreviousItemId() : 0;

        $position = $form->addSelect(
            'previous',
            get_lang('Position'),
            [],
            ['id' => 'previous']
        );

        $position->addOption(get_lang('First position'), 0);

        foreach ($arrHide as $key => $value) {
            $padding = isset($value['padding']) ? $value['padding'] : 20;
            $position->addOption(
                $value['value'],
                $key,
                'style="padding-left:'.$padding.'px;"'
            );
        }

        $position->setSelected($selectedPosition);

        if (is_array($arrLP)) {
            reset($arrLP);
        }

        $form->setDefault('title', $item_title);
        $form->setDefault('description', $item_description);

        $form->addHidden('id', $itemId);
        $form->addHidden('type', $item_type);
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
