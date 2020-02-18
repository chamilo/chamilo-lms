<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class PTestCategory.
 * Manage question categories within a personality test exercise.
 *
 * @author Jose Angel Ruiz (NOSOLORED)
 */
class PTestCategory
{
    public $id;
    public $name;
    public $description;
    public $exercise_id;
    public $color;
    public $position;

    /**
     * Constructor of the class Category.
     */
    public function __construct()
    {
        $this->name = '';
        $this->description = '';
        $this->exercise_id = 0;
        $this->color = '#000000';
        $this->position = 0;
    }

    /**
     * return the PTestCategory object with id=in_id.
     *
     * @param int $id
     * @param int $courseId
     *
     * @return PTestCategory
     */
    public function getCategory($id, $courseId = 0)
    {
        $table = Database::get_course_table(TABLE_QUIZ_CATEGORY_PTEST);
        $id = (int) $id;
        $exerciseId = (int) $exerciseId;
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;
        $sql = "SELECT * FROM $table
                WHERE id = $id AND c_id = ".$courseId;
        $res = Database::query($sql);

        if (Database::num_rows($res)) {
            $row = Database::fetch_array($res);

            $this->id = $row['id'];
            $this->name = $row['title'];
            $this->description = $row['description'];
            $this->exercise_id = $row['exercise_id'];
            $this->color = $row['color'];
            $this->position = $row['position'];

            return $this;
        }

        return false;
    }

    /**
     * Save PTestCategory in the database if name doesn't exists.
     *
     * @param int $exerciseId
     * @param int $courseId
     *
     * @return bool
     */
    public function save($exerciseId, $courseId = 0)
    {
        $exerciseId = (int) $exerciseId;
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;
        $courseInfo = api_get_course_info_by_id($courseId);
        if (empty($courseInfo)) {
            return false;
        }

        $table = Database::get_course_table(TABLE_QUIZ_CATEGORY_PTEST);

        // check if name already exists
        $sql = "SELECT count(*) AS nb FROM $table
                WHERE
                    title = '".Database::escape_string($this->name)."' AND
                    c_id = $courseId AND
                    exercise_id = $exerciseId";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        // lets add in BDD if not the same name
        if ($row['nb'] <= 0) {
            $params = [
                'c_id' => $courseId,
                'exercise_id' => $exerciseId,
                'title' => $this->name,
                'description' => $this->description,
                'session_id' => api_get_session_id(),
                'color' => $this->color,
                'position' => $this->position,
            ];
            $newId = Database::insert($table, $params);

            if ($newId) {
                api_item_property_update(
                    $courseInfo,
                    TOOL_PTEST_CATEGORY,
                    $newId,
                    'TestCategoryAdded',
                    api_get_user_id()
                );
            }

            return $newId;
        } else {
            return false;
        }
    }

    /**
     * Removes the category from the database
     * if there were question in this category, the link between question and category is removed.
     *
     * @param int $id
     *
     * @return bool
     */
    public function removeCategory($id)
    {
        $table = Database::get_course_table(TABLE_QUIZ_CATEGORY_PTEST);
        $id = (int) $id;
        $courseId = api_get_course_int_id();
        $category = $this->getCategory($id);

        if ($category) {
            $sql = "DELETE FROM $table
                    WHERE id= $id AND c_id=".$courseId;
            Database::query($sql);

            return true;
        }

        return false;
    }

    /**
     * Modify category name or description of category with id=in_id.
     *
     * @param int $courseId
     *
     * @return bool
     */
    public function modifyCategory($courseId = 0)
    {
        $table = Database::get_course_table(TABLE_QUIZ_CATEGORY_PTEST);
        $id = (int) $this->id;
        $name = Database::escape_string($this->name);
        $description = Database::escape_string($this->description);
        $color = Database::escape_string($this->color);
        $position = Database::escape_string($this->position);
        $cat = $this->getCategory($id, $courseId);
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;
        $courseInfo = api_get_course_info_by_id($courseId);
        if (empty($courseInfo)) {
            return false;
        }

        if ($cat) {
            $sql = "UPDATE $table SET
                        title = '$name',
                        description = '$description',
                        color = '$color',
                        position = $position
                    WHERE id = $id AND c_id = ".$courseId;
            Database::query($sql);

            // item_property update
            api_item_property_update(
                $courseInfo,
                TOOL_PTEST_CATEGORY,
                $this->id,
                'TestCategoryModified',
                api_get_user_id()
            );

            return true;
        }

        return false;
    }

    /**
     * Gets the number of categories of exercise id=in_id.
     *
     * @param int $exerciseId
     *
     * @return int
     */
    public function getCategoriesExerciseNumber($exerciseId)
    {
        $table = Database::get_course_table(TABLE_QUIZ_CATEGORY_PTEST);
        $exerciseId = (int) $exerciseId;
        $sql = "SELECT count(*) AS nb
                FROM $table
                WHERE exercise_id = $exerciseId AND c_id=".api_get_course_int_id();
        $res = Database::query($sql);
        $row = Database::fetch_array($res);

        return $row['nb'];
    }

    /**
     * Return an array of all Category objects of exercise in the database
     * If $field=="" Return an array of all category objects in the database
     * Otherwise, return an array of all in_field value
     * in the database (in_field = id or name or description).
     *
     * @param int    $exerciseId
     * @param string $field
     * @param int    $courseId
     *
     * @return array
     */
    public static function getCategoryListInfo($exerciseId, $field = '', $courseId = 0)
    {
        $exerciseId = (int) $exerciseId;
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;

        $table = Database::get_course_table(TABLE_QUIZ_CATEGORY_PTEST);
        $categories = [];
        if (empty($field)) {
            $sql = "SELECT id FROM $table
                    WHERE c_id = $courseId AND exercise_id = $exerciseId 
                    ORDER BY position ASC";
            $res = Database::query($sql);
            while ($row = Database::fetch_array($res)) {
                $category = new PTestCategory();
                $categories[] = $category->getCategory($row['id'], $courseId);
            }
        } else {
            $field = Database::escape_string($field);
            $sql = "SELECT $field FROM $table
                    WHERE c_id = $courseId AND exercise_id = $exerciseId 
                    ORDER BY $field ASC";
            $res = Database::query($sql);
            while ($row = Database::fetch_array($res)) {
                $categories[] = $row[$field];
            }
        }

        return $categories;
    }

    /**
     * @param FormValidator $form
     * @param string        $action
     */
    public function getForm(&$form, $action = 'new')
    {
        switch ($action) {
            case 'new':
                $header = get_lang('AddACategory');
                $submit = get_lang('AddTestCategory');
                break;
            case 'edit':
                $header = get_lang('EditCategory');
                $submit = get_lang('ModifyCategory');
                break;
        }

        // Setting the form elements
        $form->addElement('header', $header);
        $form->addElement('hidden', 'category_id');
        $form->addElement(
            'text',
            'category_name',
            get_lang('CategoryName'),
            ['class' => 'span6']
        );
        $form->add_html_editor(
            'category_description',
            get_lang('CategoryDescription'),
            false,
            false,
            [
                'ToolbarSet' => 'test_category',
                'Width' => '90%',
                'Height' => '200',
            ]
        );
        $categoryParentList = [];

        $options = [
                '1' => get_lang('Visible'),
                '0' => get_lang('Hidden'),
        ];
        $form->addElement(
            'select',
            'visibility',
            get_lang('Visibility'),
            $options
        );
        $script = null;
        if (!empty($this->parent_id)) {
            $parent_cat = new PTestCategory();
            $parent_cat = $parent_cat->getCategory($this->parent_id);
            $categoryParentList = [$parent_cat->id => $parent_cat->name];
            $script .= '<script>
                $(function() { 
                    $("#parent_id").trigger(
                        "addItem",
                        [{"title": "'.$parent_cat->name.'", "value": "'.$parent_cat->id.'"}]
                    );
                });
                </script>';
        }
        $form->addElement('html', $script);

        $form->addElement('select', 'parent_id', get_lang('Parent'), $categoryParentList, ['id' => 'parent_id']);
        $form->addElement('style_submit_button', 'SubmitNote', $submit, 'class="add"');

        // setting the defaults
        $defaults = [];
        $defaults["category_id"] = $this->id;
        $defaults["category_name"] = $this->name;
        $defaults["category_description"] = $this->description;
        $defaults["parent_id"] = $this->parent_id;
        $defaults["visibility"] = $this->visibility;
        $form->setDefaults($defaults);

        // setting the rules
        $form->addRule('category_name', get_lang('ThisFieldIsRequired'), 'required');
    }

    /**
     * Return true if a category already exists with the same name.
     *
     * @param string $name
     * @param int    $courseId
     *
     * @return bool
     */
    public static function categoryTitleExists($name, $courseId = 0)
    {
        $categories = self::getCategoryListInfo('title', $courseId);
        foreach ($categories as $title) {
            if ($title == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $exerciseId
     * @param int $courseId
     * @param int $sessionId
     *
     * @return array
     */
    public function getCategories($exerciseId, $courseId, $sessionId = 0)
    {
        $table = Database::get_course_table(TABLE_QUIZ_CATEGORY_PTEST);
        $itemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $sessionId = (int) $sessionId;
        $courseId = (int) $courseId;
        $exerciseId = (int) $exerciseId;

        $sessionCondition = api_get_session_condition(
            $sessionId
        );

        if (empty($courseId)) {
            return [];
        }

        $sql = "SELECT * FROM $table
                WHERE
                    exercise_id = $exerciseId AND
                    c_id = $courseId
                    $sessionCondition
                ORDER BY position ASC";
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     *
     * @return string
     */
    public function displayCategories($exerciseId, $courseId, $sessionId = 0)
    {
        $exerciseId = (int) $exerciseId;
        $sessionId = (int) $sessionId;
        $categories = $this->getCategories($exerciseId, $courseId, $sessionId);
        $html = '';
        foreach ($categories as $category) {
            $tmpobj = new PTestCategory();
            $tmpobj = $tmpobj->getCategory($category['id']);
            $rowname = self::protectJSDialogQuote($category['title']);
            $content = '';
            $content .= '<div class="sectioncomment">';
            $content .= '<table class="table">';
            $content .= '<tr>';
            $content .= '<td>'.get_lang('PtestCategoryPosition').'</td>';
            $content .= '<td>'.$category['position'].'</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td style="width:1px;white-space:nowrap;vertical-align:middle;">';
            $content .= get_lang('PtestCategoryColor');
            $content .= '</td>';
            $content .= '<td>';
            $content .= Display::tag(
                'span',
                null,
                [
                    'class' => 'form-control',
                    'style' => 'background:'.$category['color'].';
                        width:100px;
                        vertical-align:middle;
                        display:inline-block;
                        margin-right:20px;',
                ]
            );
            $content .= $category['color'];
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td>'.get_lang('Description').'</td>';
            $content .= '<td>'.$category['description'].'</td>';
            $content .= '</tr>';
            $content .= '</table>';
            $content .= '</div>';
            $links = '';

            $links .= '<a href="'.api_get_self().
                '?exerciseId='.$exerciseId.'&action=editcategory&category_id='.$category['id'].
                '&'.api_get_cidreq().'">'.
                Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>';
            $links .= ' <a href="'.api_get_self().'?exerciseId='.$exerciseId.'&'.api_get_cidreq().
                '&action=deletecategory&category_id='.$category['id'].'" ';
            $links .= 'onclick="return confirmDelete(\''.self::protectJSDialogQuote(
                get_lang('DeleteCategoryAreYouSure').'['.$rowname).'] ?\', \'id_cat'.$category['id'].'\');">';
            $links .= Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL).'</a>';

            $html .= Display::panel($content, $category['title'].$links);
        }

        return $html;
    }

    /**
     * To allowed " in javascript dialog box without bad surprises
     * replace " with two '.
     *
     * @param string $text
     *
     * @return mixed
     */
    public function protectJSDialogQuote($text)
    {
        $res = $text;
        $res = str_replace("'", "\'", $res);
        // super astuce pour afficher les " dans les boite de dialogue
        $res = str_replace('"', "\'\'", $res);

        return $res;
    }
}
