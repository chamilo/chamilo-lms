<?php
/* For licensing terms, see /license.txt */

use APY\DataGridBundle\Grid\Source\Entity;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Framework\Container;
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
     * @param string $type The type of data to which this extra field
     *                     applies (user, course, session, ...)
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
     * @param int $courseId
     *
     * @return array
     */
    public function getCategories($courseId)
    {
        return Container::getExerciseCategoryRepository()->getCategories($courseId);
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
        $repo = Container::getExerciseCategoryRepository();
        $category = $repo->find($id);
        $repo->hardDelete($category);
    }

    /**
     * @param array $params
     * @param bool  $showQuery
     *
     * @return bool
     */
    public function update($params, $showQuery = false)
    {
        $id = $params['id'];

        $repo = Container::getExerciseCategoryRepository();
        /** @var CExerciseCategory $category */
        $category = $repo->find($id);

        if ($category) {
            $category
                ->setName($params['name'])
                ->setDescription($params['description'])
            ;

            $repo->getEntityManager()->persist($category);
            $repo->getEntityManager()->flush();

            return true;
        }

        return false;
    }

    /**
     * Save values in the *_field_values table.
     *
     * @param array $params    Structured array with the values to save
     * @param bool  $showQuery Whether to show the insert query (passed to the parent save() method)
     */
    public function save($params, $showQuery = false)
    {
        $courseId = api_get_course_int_id();
        $course = api_get_course_entity($courseId);

        $repo = Container::getExerciseCategoryRepository();
        $em = $repo->getEntityManager();

        $category = new CExerciseCategory();
        $category
            ->setName($params['name'])
            ->setCourse($course)
            ->setDescription($params['description'])
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

        $repo->addResourceToCourse(
            $category,
            ResourceLink::VISIBILITY_PUBLISHED,
            api_get_user_entity(api_get_user_id()),
            $course,
            api_get_session_entity(),
            api_get_group_entity()
        );

        $em->flush();

        return $category;
    }

    /**
     * @param string $token
     *
     * @return string
     */
    public function getJqgridActionLinks($token)
    {
        //With this function we can add actions to the jgrid (edit, delete, etc)
        $editIcon = Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL);
        $deleteIcon = Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL);
        /*$editIcon = Display::returnFontAwesomeIcon('pencil');
        $deleteIcon = Display::returnFontAwesomeIcon('trash');*/
        $confirmMessage = addslashes(
            api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES)
        );

        $courseParams = api_get_cidreq();

        $editButton = <<<JAVASCRIPT
            <a href="?action=edit&{$courseParams}&id=' + options.rowId + '" class="">\
                $editIcon\
            </a>
JAVASCRIPT;
        $deleteButton = <<<JAVASCRIPT
            <a \
                onclick="if (!confirm(\'$confirmMessage\')) {return false;}" \
                href="?sec_token=$token&{$courseParams}&id=' + options.rowId + '&action=delete" \
                class="">\
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
            $header = get_lang('Edit');
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
            $form->addButtonUpdate(get_lang('Edit'));
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
        $form->addRule('name', get_lang('Required field'), 'required');

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
            get_lang('Back to').' '.get_lang('Administration'),
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
        ///$grid = Container::$container->get('grid');
        $source = new Entity('ChamiloCourseBundle:CExerciseCategory');
        $qb = Container::getExerciseCategoryRepository()->getResourcesByCourse(api_get_course_entity());
        $source-> initQueryBuilder($qb);

        $builder = Container::$container->get('apy_grid.factory');

        $grid = $builder->createBuilder(
            'grid',
            $source,
            [
                'persistence' => false,
                'route' => 'home',
                'filterable' => true,
                'sortable' => true,
                'max_per_page' => 10,
            ]
        )->add(
            'id',
            'number',
            [
                'title' => '#',
                'primary' => 'true',
            ]
        )->add(
                'name',
                'text',
                [
                    'title' => 'name',
                ]
            );

        $grid = $grid->getGrid();
        $grid->setRouteUrl(api_get_self().'?cidReq=GEORGIA');
        $grid->handleRequest(Container::getRequest());
        $content .= Container::$container->get('twig')->render('@ChamiloTheme/Resource/grid.html.twig', array('grid' => $grid));

        return $content;
    }
}
