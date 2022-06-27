<?php

/* For licensing terms, see /license.txt */

/**
 * Form used to add or edit links.
 *
 * @author Stijn Konings
 * @author Bert Steppé
 */
class LinkAddEditForm extends FormValidator
{
    public const TYPE_ADD = 1;
    public const TYPE_EDIT = 2;

    /**
     * Constructor
     * To add link, define category_object and link_type
     * To edit link, define link_object.
     */
    public function __construct(
        $form_type,
        $category_object,
        $link_type,
        $link_object,
        $form_name,
        $action = null
    ) {
        parent::__construct($form_name, 'post', $action);

        // set or create link object
        if (isset($link_object)) {
            $link = $link_object;
        } elseif (isset($link_type) && isset($category_object)) {
            $link = LinkFactory::create($link_type);
            $link->set_course_code(api_get_course_id());
            $link->set_session_id(api_get_session_id());
            $link->set_category_id($category_object[0]->get_id());
        } else {
            exit('LinkAddEditForm error: define link_type/category_object or link_object');
        }

        $defaults = [];
        if (!empty($_GET['editlink'])) {
            $this->addElement('header', '', get_lang('EditLink'));
        }

        // ELEMENT: name
        if ($form_type == self::TYPE_ADD || $link->is_allowed_to_change_name()) {
            if ($link->needs_name_and_description()) {
                $this->addText('name', get_lang('Name'), true, ['size' => '40', 'maxlength' => '40']);
            } else {
                $select = $this->addElement('select', 'select_link', get_lang('ChooseItem'));
                foreach ($link->get_all_links() as $newlink) {
                    $name = strip_tags(Exercise::get_formated_title_variable($newlink[1]));
                    $select->addOption($name, $newlink[0]);
                }
            }
        } else {
            $this->addElement(
                'label',
                get_lang('Name'),
                '<span class="freeze">'.$link->get_name().' ['.$link->get_type_name().']</span>'
            );

            $this->addElement(
                'hidden',
                'name_link',
                $link->get_name(),
                ['id' => 'name_link']
            );
        }

        if (1 == count($category_object)) {
            $this->addElement('hidden', 'select_gradebook', $category_object[0]->get_id());
        } else {
            $select_gradebook = $this->addElement(
                'select',
                'select_gradebook',
                get_lang('SelectGradebook'),
                [],
                ['id' => 'hide_category_id']
            );
            $this->addRule('select_gradebook', get_lang('ThisFieldIsRequired'), 'nonzero');
            $default_weight = 0;
            if (!empty($category_object)) {
                foreach ($category_object as $my_cat) {
                    if ($my_cat->get_course_code() == api_get_course_id()) {
                        $grade_model_id = $my_cat->get_grade_model_id();
                        if (empty($grade_model_id)) {
                            if (0 == $my_cat->get_parent_id()) {
                                $default_weight = $my_cat->get_weight();
                                $select_gradebook->addOption(get_lang('Default'), $my_cat->get_id());
                            } else {
                                $select_gradebook->addOption(Security::remove_XSS($my_cat->get_name()), $my_cat->get_id());
                            }
                        } else {
                            $select_gradebook->addOption(get_lang('Select'), 0);
                        }
                        if ($link->get_category_id() == $my_cat->get_id()) {
                            $default_weight = $my_cat->get_weight();
                        }
                    }
                }
            }
        }

        $this->addFloat(
            'weight_mask',
            [
                get_lang('Weight'),
                null,
                ' [0 .. <span id="max_weight">'.$category_object[0]->get_weight(
                ).'</span>] ',
            ],
            true,
            [
                'size' => '4',
                'maxlength' => '5',
            ]
        );

        $this->addElement('hidden', 'weight');

        if (self::TYPE_EDIT == $form_type) {
            $parent_cat = Category::load($link->get_category_id());
            if (0 == $parent_cat[0]->get_parent_id()) {
                $values['weight'] = $link->get_weight();
            } else {
                $cat = Category::load($parent_cat[0]->get_parent_id());
                $values['weight'] = $link->get_weight();
            }
            $defaults['weight_mask'] = $values['weight'];
            $defaults['select_gradebook'] = $link->get_category_id();
        }
        // ELEMENT: max
        if ($link->needs_max()) {
            if ($form_type == self::TYPE_EDIT && $link->has_results()) {
                $this->addText(
                    'max',
                    get_lang('QualificationNumeric'),
                    false,
                    [
                        'size' => '4',
                        'maxlength' => '5',
                        'disabled' => 'disabled',
                    ]
                );
            } else {
                $this->addText('max', get_lang('QualificationNumeric'), true, ['size' => '4', 'maxlength' => '5']);
                $this->addRule('max', get_lang('OnlyNumbers'), 'numeric');
                $this->addRule(
                    'max',
                    get_lang('NegativeValue'),
                    'compare',
                    '>=',
                    'server',
                    false,
                    false,
                    0
                );
            }
            if ($form_type == self::TYPE_EDIT) {
                $defaults['max'] = $link->get_max();
            }
        }

        // ELEMENT: description
        if ($link->needs_name_and_description()) {
            $this->addElement(
                'textarea',
                'description',
                get_lang('Description'),
                ['rows' => '3', 'cols' => '34']
            );
            if ($form_type == self::TYPE_EDIT) {
                $defaults['description'] = $link->get_description();
            }
        }

        // ELEMENT: visible
        $visible = ($form_type == self::TYPE_EDIT && $link->is_visible()) ? '1' : '0';
        $this->addElement('checkbox', 'visible', null, get_lang('Visible'), $visible);
        if ($form_type == self::TYPE_EDIT) {
            $defaults['visible'] = $link->is_visible();
        }

        // ELEMENT: add results
        if ($form_type == self::TYPE_ADD && $link->needs_results()) {
            $this->addElement('checkbox', 'addresult', get_lang('AddResult'));
        }
        // submit button
        if ($form_type == self::TYPE_ADD) {
            $this->addButtonCreate(get_lang('CreateLink'));
        } else {
            $this->addButtonUpdate(get_lang('LinkMod'));
        }

        if ($form_type == self::TYPE_ADD) {
            $setting = api_get_setting('tool_visible_by_default_at_creation');
            $visibility_default = 1;
            if (isset($setting['gradebook']) && $setting['gradebook'] === 'false') {
                $visibility_default = 0;
            }
            $defaults['visible'] = $visibility_default;
        }

        // set default values
        $this->setDefaults($defaults);
    }
}
