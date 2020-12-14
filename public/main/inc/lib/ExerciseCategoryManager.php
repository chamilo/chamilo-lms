<?php
/* For licensing terms, see /license.txt */

use APY\DataGridBundle\Grid\Action\MassAction;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Row;
use APY\DataGridBundle\Grid\Source\Entity;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
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

        return true;
    }

    /**
     * @param                                                   $primaryKeys
     * @param                                                   $allPrimaryKeys
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     * @param                                                   $parameters
     */
    public function deleteResource(
        $primaryKeys,
        $allPrimaryKeys,
        Symfony\Component\HttpFoundation\Session\Session $session,
        $parameters
    ) {
        $repo = Container::getExerciseCategoryRepository();
        $translator = Container::getTranslator();
        foreach ($primaryKeys as $id) {
            $category = $repo->find($id);
            $repo->hardDelete($category);
        }

        Display::addFlash(Display::return_message($translator->trans('Deleted')));
        header('Location:'.api_get_self().'?'.api_get_cidreq());
        exit;
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

            $repo->update($category);

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
        $category = new CExerciseCategory();
        $category
            ->setName($params['name'])
            ->setCourse($course)
            ->setDescription($params['description'])
            ->setParent($course)
            ->addCourseLink($course, api_get_session_entity())
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
        $repo->create($category);

        return $category;
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

        if ('edit' === $action) {
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

        if ('edit' === $action) {
            $form->addButtonUpdate(get_lang('Edit'));
        } else {
            $form->addButtonCreate(get_lang('Add'));
        }
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
        $session = api_get_session_entity();
        $course = api_get_course_entity();

        // Action links
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

        // 1. Set entity
        $source = new Entity('ChamiloCourseBundle:CExerciseCategory');
        $repo = Container::getExerciseCategoryRepository();
        // 2. Get query builder from repo.
        $qb = $repo->getResourcesByCourse($course, $session);

        // 3. Set QueryBuilder to the source.
        $source->initQueryBuilder($qb);

        // 4. Get the grid builder.
        $builder = Container::$container->get('apy_grid.factory');

        // 5. Set parameters and properties.
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
                'primary' => true,
                'visible' => false,
            ]
        )->add(
            'name',
            'text',
            [
                'title' => get_lang('Name'),
            ]
        );
        $grid = $grid->getGrid();

        if (Container::getAuthorizationChecker()->isGranted(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER)) {
            // Add row actions
            $myRowAction = new RowAction(
                get_lang('Edit'),
                'legacy_main',
                false,
                '_self',
                ['class' => 'btn btn-secondary']
            );
            $myRowAction->setRouteParameters(
                ['id', 'name' => 'exercise/category.php', 'cidReq' => api_get_course_id(), 'action' => 'edit']
            );

            $myRowAction->addManipulateRender(
                function (RowAction $action, Row $row) use ($session, $repo) {
                    return $repo->rowCanBeEdited($action, $row, $session);
                }
            );

            $grid->addRowAction($myRowAction);

            $myRowAction = new RowAction(
                get_lang('Delete'),
                'legacy_main',
                true,
                '_self',
                ['class' => 'btn btn-danger', 'form_delete' => true]
            );
            $myRowAction->setRouteParameters(
                ['id', 'name' => 'exercise/category.php', 'cidReq' => api_get_course_id(), 'action' => 'delete']
            );
            $myRowAction->addManipulateRender(
                function (RowAction $action, Row $row) use ($session, $repo) {
                    return $repo->rowCanBeEdited($action, $row, $session);
                }
            );

            $grid->addRowAction($myRowAction);

            if (empty($session)) {
                // Add mass actions
                $deleteMassAction = new MassAction(
                    'Delete',
                    ['ExerciseCategoryManager', 'deleteResource'],
                    true,
                    []
                );
                $grid->addMassAction($deleteMassAction);
            }
        }

        // 8. Set route and request
        $grid
            ->setRouteUrl(api_get_self().'?'.api_get_cidreq())
            ->handleRequest(Container::getRequest())
        ;

        $content .= Container::$container->get('twig')->render(
            '@ChamiloCore/Resource/grid.html.twig',
            ['grid' => $grid]
        );

        return $content;
    }
}
