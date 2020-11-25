<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CExerciseCategory;

/**
 * Class ExtraFieldValue
 * Declaration for the ExtraFieldValue class, managing the values in extra
 * fields for any data type.
 */
class ExerciseCategoryManager extends Model
{
    public $type = '';
    public $columns = [
        'id',
        'name',
        'c_id',
        'description',
        'created_at',
        'updated_at',
    ];

    /**
     * Formats the necessary elements for the given datatype.
     *
     * @assert (-1) === false
     */
    public function __construct()
    {
        parent::__construct();
        $this->is_course_model = true;
        $this->table = Database::get_course_table('exercise_category');
    }

    /**
     * Gets the number of values stored in the table (all fields together)
     * for this type of resource.
     *
     * @param int $courseId
     *
     * @return int Number of rows in the table
     */
    public function getCourseCount($courseId)
    {
        $em = Database::getManager();
        $query = $em->getRepository('ChamiloCourseBundle:CExerciseCategory')->createQueryBuilder('e');
        $query->select('count(e.id)');
        $query->where('e.cId = :cId');
        $query->setParameter('cId', $courseId);

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $courseId
     *
     * @return array
     */
    public function getCategories($courseId)
    {
        $em = Database::getManager();
        $query = $em->getRepository('ChamiloCourseBundle:CExerciseCategory')->createQueryBuilder('e');
        $query->where('e.cId = :cId');
        $query->setParameter('cId', $courseId);
        $query->orderBy('e.position');

        return $query->getQuery()->getResult();
    }

    /**
     * @param int $courseId
     *
     * @return array
     */
    public function getCategoriesForSelect($courseId)
    {
        $categories = $this->getCategories($courseId);
        $options = [];

        if (!empty($categories)) {
            /** @var CExerciseCategory $category */
            foreach ($categories as $category) {
                $options[$category->getId()] = $category->getName();
            }
        }

        return $options;
    }

    /**
     * @param int $id
     */
    public function delete($id)
    {
        $em = Database::getManager();
        $repo = Database::getManager()->getRepository('ChamiloCourseBundle:CExerciseCategory');
        $category = $repo->find($id);
        if ($category) {
            $em->remove($category);
            $em->flush();

            $courseId = api_get_course_int_id();
            $table = Database::get_course_table(TABLE_QUIZ_TEST);
            $id = (int) $id;

            $sql = "UPDATE $table SET exercise_category_id = 0
                    WHERE c_id = $courseId AND exercise_category_id = $id";
            Database::query($sql);
        }
    }

    /**
     * Save values in the *_field_values table.
     *
     * @param array $params    Structured array with the values to save
     * @param bool  $showQuery Whether to show the insert query (passed to the parent save() method)
     */
    public function save($params, $showQuery = false)
    {
        $em = Database::getManager();
        $category = new CExerciseCategory();
        $category
            ->setName($params['name'])
            ->setCId(api_get_course_int_id())
            ->setDescription($params['name'])
        ;
        /*
            // Update position
            $query = $em->getRepository('ChamiloCourseBundle:CExerciseCategory')->createQueryBuilder('e');
            $query
                ->where('e.cId = :cId')
                ->setParameter('cId', $courseId)
                ->setMaxResults(1)
                ->orderBy('e.position', 'DESC');
            $last = $query->getQuery()->getOneOrNullResult();
            $position = 0;
            if (!empty($last)) {
                $position = $last->getPosition() + 1;
            }
            $category->setPosition($position);
*/
        $em->persist($category);
        $em->flush();

        return $category;
    }

    /**
     * @param $token
     *
     * @return string
     */
    public function getJqgridActionLinks($token)
    {
        //With this function we can add actions to the jgrid (edit, delete, etc)
        $editIcon = Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL);
        $deleteIcon = Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL);
        $confirmMessage = addslashes(
            api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES)
        );

        $courseParams = api_get_cidreq();

        $editButton = <<<JAVASCRIPT
            <a href="?action=edit&{$courseParams}&id=' + options.rowId + '" class="btn btn-link btn-xs">\
                $editIcon\
            </a>
JAVASCRIPT;
        $deleteButton = <<<JAVASCRIPT
            <a \
                onclick="if (!confirm(\'$confirmMessage\')) {return false;}" \
                href="?sec_token=$token&{$courseParams}&id=' + options.rowId + '&action=delete" \
                class="btn btn-link btn-xs">\
                $deleteIcon\
            </a>
JAVASCRIPT;

        return "function action_formatter(cellvalue, options, rowObject) {
            return '$editButton $deleteButton';
        }";
    }

    /**
     * @param string $url
     * @param string $action
     *
     * @return FormValidator
     */
    public function return_form($url, $action)
    {
        $form = new FormValidator('category', 'post', $url);
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $form->addElement('hidden', 'id', $id);

        // Setting the form elements
        $header = get_lang('Add');
        $defaults = [];

        if ($action === 'edit') {
            $header = get_lang('Modify');
            // Setting the defaults
            $defaults = $this->get($id, false);
        }

        $form->addElement('header', $header);

        $form->addText(
            'name',
            get_lang('Name')
        );

        $form->addHtmlEditor('description', get_lang('Description'));

        if ($action === 'edit') {
            $form->addButtonUpdate(get_lang('Modify'));
        } else {
            $form->addButtonCreate(get_lang('Add'));
        }

        /*if (!empty($defaults['created_at'])) {
            $defaults['created_at'] = api_convert_and_format_date($defaults['created_at']);
        }
        if (!empty($defaults['updated_at'])) {
            $defaults['updated_at'] = api_convert_and_format_date($defaults['updated_at']);
        }*/
        $form->setDefaults($defaults);

        // Setting the rules
        $form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

        return $form;
    }

    /**
     * @return string
     */
    public function display()
    {
        // action links
        $content = '<div class="actions">';
        $content .= '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq().'">';
        $content .= Display::return_icon(
            'back.png',
            get_lang('BackTo').' '.get_lang('PlatformAdmin'),
            '',
            ICON_SIZE_MEDIUM
        );
        $content .= '</a>';
        $content .= '<a href="'.api_get_self().'?action=add&'.api_get_cidreq().'">';
        $content .= Display::return_icon(
            'add.png',
            get_lang('Add'),
            '',
            ICON_SIZE_MEDIUM
        );
        $content .= '</a>';
        $content .= '</div>';
        $content .= Display::grid_html('categories');

        return $content;
    }
}
