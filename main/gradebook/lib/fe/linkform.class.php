<?php
/* For licensing terms, see /license.txt */

/**
 * Class LinkForm
 * Forms related to links
 * @author Stijn Konings
 * @author Bert Steppé (made more generic)
 * @package chamilo.gradebook
 */
class LinkForm extends FormValidator
{
    const TYPE_CREATE = 1;
    const TYPE_MOVE = 2;
    /** @var Category */
    private $category_object;
    private $link_object;
    private $extra;

    /**
     * Builds a form containing form items based on a given parameter
     * @param int form_type 1=choose link
     * @param obj cat_obj the category object
     * @param string form name
     * @param method
     * @param action
     */
    public function __construct(
        $form_type,
        $category_object,
        $link_object,
        $form_name,
        $method = 'post',
        $action = null,
        $extra = null
    ) {
        parent::__construct($form_name, $method, $action);

        if (isset ($category_object)) {
            $this->category_object = $category_object;
        } else {
            if (isset($link_object)) {
                $this->link_object = $link_object;
            }
        }

        if (isset($extra)) {
            $this->extra = $extra;
        }
        if ($form_type == self::TYPE_CREATE) {
            $this->build_create();
        } elseif ($form_type == self::TYPE_MOVE) {
            $this->build_move();
        }
    }

    protected function build_move()
    {
        $renderer = & $this->defaultRenderer();
        $renderer->setCustomElementTemplate('<span>{element}</span> ');
        $this->addElement(
            'static',
            null,
            null,
            '"'.$this->link_object->get_name().'" '
        );
        $this->addElement('static', null, null, get_lang('MoveTo').' : ');
        $select = $this->addElement('select', 'move_cat', null, null);
        $line = '';
        foreach ($this->link_object->get_target_categories() as $cat) {
            for ($i = 0; $i < $cat[2]; $i++) {
                $line .= '&mdash;';
            }
            $select->addoption($line.' '.$cat[1], $cat[0]);
            $line = '';
        }
        $this->addElement('submit', null, get_lang('Ok'));
    }

    /**
     * Builds the form
     */
    protected function build_create()
    {
        $this->addElement('header', get_lang('MakeLink'));
        $select = $this->addElement(
            'select',
            'select_link',
            get_lang('ChooseLink'),
            null,
            array('onchange' => 'document.create_link.submit()')
        );

        $linkTypes = LinkFactory::get_all_types();

        $select->addoption('['.get_lang('ChooseLink').']', 0);

        $courseCode = $this->category_object->get_course_code();

        foreach ($linkTypes as $linkType) {
            // The hot potatoe link will be added "inside" the exercise option.
            if ($linkType == LINK_HOTPOTATOES) {
                continue;
            }
            $link = $this->createLink($linkType, $courseCode);
            // disable this element if the link works with a dropdownlist
            // and if there are no links left
            if (!$link->needs_name_and_description() && count($link->get_all_links()) == '0') {
                $select->addoption($link->get_type_name(), $linkType, 'disabled');
            } else {
                $select->addoption($link->get_type_name(), $linkType);
            }

            if ($link->get_type() == LINK_EXERCISE) {
                // Adding hot potatoes
                $linkHot = $this->createLink(LINK_HOTPOTATOES, $courseCode);
                $linkHot->setHp(true);
                if ($linkHot->get_all_links(true)) {
                    $select->addoption(
                        '&nbsp;&nbsp;&nbsp;'.$linkHot->get_type_name(),
                        LINK_HOTPOTATOES
                    );
                } else {
                    $select->addoption(
                        '&nbsp;&nbsp;&nbsp;'.$linkHot->get_type_name(),
                        LINK_HOTPOTATOES,
                        'disabled'
                    );
                }
            }
        }

        if (isset($this->extra)) {
            $this->setDefaults(array('select_link' => $this->extra));
        }
    }

    /**
     * @param integer $link
     * @param null|string $courseCode
     * @return AttendanceLink|DropboxLink|ExerciseLink|ForumThreadLink|LearnpathLink|null|StudentPublicationLink|SurveyLink
     */
    private function createLink($link, $courseCode)
    {
        $link = LinkFactory::create($link);
        if (!empty($courseCode)) {
            $link->set_course_code($courseCode);
        } elseif (!empty($_GET['course_code'])) {
            $link->set_course_code(Database::escape_string($_GET['course_code'], null, false));
        }

        return $link;
    }
}
