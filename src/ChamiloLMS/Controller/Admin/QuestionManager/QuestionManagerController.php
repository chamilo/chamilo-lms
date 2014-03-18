<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\QuestionManager;

use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class QuestionManagerController
 * @todo reduce controller size
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class QuestionManagerController
{
    /**
     * @param Application $app
     */
    public function indexAction(Application $app)
    {

    }

    /**
     * Edits a question for the question manager
     *
     * @param Application $app
     * @param int $id
     * @return Response
     */
    public function editQuestionAction(Application $app, $id)
    {
        // Setting exercise obj.
        $exercise                      = new \Exercise();
        $exercise->edit_exercise_in_lp = true;

        // Setting question obj.
        /** @var  \Question $question */
        $question                   = \Question::read($id, null, $exercise);
        $question->submitClass      = "btn save";
        $question->submitText       = get_lang('ModifyQuestion');
        $question->setDefaultValues = true;

        // Generating edit URL.
        $url = $app['url_generator']->generate('admin_questions_edit', array('id' => $id));

        // Creating a new form
        $form = new \FormValidator('edit_question', 'post', $url);

        $extraFields = new \ExtraField('question');
        $extraFields->addElements($form, $id);
        // Validating if there are extra fields to modify.
        if (count($form->_elements) > 1) {
            $form->addElement('button', 'submit', get_lang('Update'));

            $app['template']->assign('question', $question);
            $app['template']->assign('form', $form->toHtml());
        } else {
            $app['template']->assign('message', \Display::return_message(get_lang('ThereAreNotExtrafieldsAvailable'), 'warning'));
        }

        // If form was submitted.
        if ($form->validate()) {
            $field_value = new \ExtraFieldValue('question');
            $params = $form->exportValues();
            $params['question_id'] = $id;
            $field_value->save_field_values($params);
            $app['template']->assign('message', \Display::return_message(get_lang('ItemUpdated'), 'success'));
            $url = $app['url_generator']->generate('admin_questions_edit', array('id' => $id));

            return $app->redirect($url);
        }

        $response = $app['template']->render_template('admin/questionmanager/edit_question.tpl');

        return new Response($response, 200, array());
    }

    /**
     * Show the index page for the question manager
     * @param Application $app
     * @return Response
     */
    public function questionManagerIndexAction(Application $app)
    {
        $response = $app['template']->render_template('admin/questionmanager/questionmanager.tpl');

        return new Response($response, 200, array());
    }

    /**
     * Get question categories per id
     * @param Application $app
     * @param int $id
     * @return string
     */
    public function getCategoriesAction(Application $app, $id)
    {
        // Getting CQuizCategory repo.
        $repo = $app['orm.em']->getRepository('Entity\CQuizCategory');

        $options  = array(
            'decorate'      => true,
            'rootOpen'      => '<ul class="nav nav-list">',
            'rootClose'     => '</ul>',
            'childOpen'     => '<li>',
            'childClose'    => '</li>',
            'nodeDecorator' => function ($row) use ($app) {
                $url = $app['url_generator']->generate('admin_questions_get_categories', array('id' => $row['iid']));

                return \Display::url($row['title'], $url, array('id' => $row['iid']));
            }
            //'representationField' => 'slug',
            //'html' => true
        );
        $cats     = $repo->findOneByIid($id);
        $htmlTree = $repo->childrenHierarchy(
            $cats, /* starting from root nodes */
            true, /* false: load all children, true: only direct */
            $options
        );

        return $htmlTree;
    }

    /**
     * Gets the question list per category
     * @param Application $app
     * @param $categoryId
     * @return Response
     */
    public function getQuestionsByCategoryAction(Application $app, $categoryId)
    {
        // Getting CQuizCategory repo.
        /** @var \Doctrine\ORM\EntityManager $em */
        $em   = $app['orm.em'];
        $repo = $em->getRepository('Entity\CQuizCategory');

        /** @var \Entity\CQuizCategory $category */
        $category = $repo->find($categoryId);

        $questionColumns = \Question::getQuestionColumns();
        $columnModel     = $questionColumns['column_model'];
        $columns         = $questionColumns['columns'];
        $rules           = $questionColumns['rules'];

        $grid = \Display::grid_html('questions');

        //jqgrid will use this URL to do the selects
        $url = $app['url_generator']->generate('model_ajax').'?a=get_questions&categoryId='.$categoryId;

        $extraParams['postData'] = array(
            'filters' => array(
                "groupOp" => "AND",
                "rules"   => $rules
            )
        );

        // Autowidth.
        $extraParams['autowidth'] = 'true';
        // Height auto.
        $extraParams['height'] = 'auto';
        $token                 = null;
        $editUrl               = $app['url_generator']->generate('admin_questions');

        $actionLinks = 'function action_formatter(cellvalue, options, rowObject) {
            return \'<a href="'.$editUrl.'/\'+rowObject[0]+\'/edit">'.\Display::return_icon(
            'edit.png',
            get_lang('Edit'),
            '',
            ICON_SIZE_SMALL
        ).'</a>'.'\';
        }';

        $testCategory = new \Testcategory($categoryId);
        $count = $testCategory->getCategoryQuestionsNumber();

        $js = \Display::grid_js(
            'questions',
            $url,
            $columns,
            $columnModel,
            $extraParams,
            array(),
            $actionLinks,
            true
        );
        //$count = $repo->childCount($category);
        $app['template']->assign('category_children', $count);
        $app['template']->assign('category', $category);
        $app['template']->assign('grid', $grid);
        $app['template']->assign('js', $js);

        $response = $app['template']->render_template('admin/questionmanager/questions.tpl');

        return new Response($response, 200, array());
    }

    /**
     * Index of the question manager
     * @param Application $app
     * @return Response
     *
     */
    public function questionsAction(Application $app)
    {
        $app['template']->addResource(api_get_jqgrid_js());

        // Getting CQuizCategory repo.
        /** @var \Gedmo\Tree\Entity\Repository\NestedTreeRepository $repo */
        $repo = $app['orm.em']->getRepository('Entity\CQuizCategory');

        $categoryId = $app['request']->get('categoryId');
        $subtree    = null;

        if (isset($categoryId)) {
            //$repo->getChildrenQueryBuilder();

            // Insert node.
            /*
            $options = array(
                'decorate' => true,
                'rootOpen' => '<ul class="nav nav-list">',
                'rootClose' => '</ul>',
                'childOpen' => '<li>',
                'childClose' => '</li>'
            );
            $node = $repo->find($categoryId);

            $qb = $repo->getChildrenQueryBuilder($node, true, 'title', 'ASC', true);
            $query = $qb->getQuery();
            $subtree = $repo->buildTree($query->getArrayResult(), $options);
            var_dump($subtree);*/
        }

        $options = array(
            'decorate'      => true,
            'rootOpen'      => '<ul class="nav nav-list">',
            'rootClose'     => '</ul>',
            'childOpen'     => '<li>',
            'childClose'    => '</li>',
            'nodeDecorator' => function ($row) use ($app, $categoryId, $subtree) {
                $url   = $app['url_generator']->generate('admin_questions_get_categories', array('id' => $row['iid']));
                $title = $row['title'];
                $url   = \Display::url($title, $url, array('id' => $row['iid']));
                if ($row['iid'] == $categoryId) {
                    $url .= $subtree;
                }

                return $url;
            }
            //'representationField' => 'slug',
            //'html' => true
        );

        // Getting all categories only first level lvl=1
        /** @var \Doctrine\ORM\QueryBuilder $qb */
        $qb = $app['orm.em']->createQueryBuilder()
            ->select('node')
            ->from('Entity\CQuizCategory', 'node')
            ->where('node.cId <> 0 AND node.lvl = 0')
            ->orderBy('node.root, node.lft', 'ASC');


        //$node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false
        //$qb = $repo->getChildrenQueryBuilder(null, true, 'title', 'ASC', true);
        $query = $qb->getQuery();
        $tree  = $repo->buildTree($query->getArrayResult(), $options);

        $app['template']->assign('category_tree', $tree);

        // Getting globals
        $query = $app['orm.em']
            ->createQueryBuilder()
            ->select('node')
            ->from('Entity\CQuizCategory', 'node')
            ->where('node.cId = 0 AND node.lvl = 0')
            ->orderBy('node.root, node.lft', 'ASC')
            ->getQuery();

        $tree = $repo->buildTree($query->getArrayResult(), $options);
        $app['template']->assign('global_category_tree', $tree);

        $response = $app['template']->render_template('admin/questionmanager/question_categories.tpl');

        return new Response($response, 200, array());

    }

    /**
     * New category
     *
     * @param Application $app
     * @return Response
     */
    public function newCategoryAction(Application $app)
    {
        $url  = $app['url_generator']->generate('admin_category_new');
        $form = new \FormValidator('new', 'post', $url);

        $objcat = new \Testcategory();
        $objcat->getForm($form, 'new');
        $message = null;
        if ($form->validate()) {
            $values     = $form->getSubmitValues();
            $parent_id  = isset($values['parent_id']) && isset($values['parent_id'][0]) ? $values['parent_id'][0] : null;
            $objcat     = new \Testcategory(0, $values['category_name'], $values['category_description'], $parent_id, 'global');
            $categoryId = $objcat->addCategoryInBDD();
            if ($categoryId) {
                $message = \Display::return_message(get_lang('AddCategoryDone'), 'confirmation');
                //$url = $app['url_generator']->generate('admin_category_show', array('id' => $categoryId));
                $url = $app['url_generator']->generate('admin_questions');
                return $app->redirect($url);
            } else {
                $message = \Display::return_message(get_lang('AddCategoryNameAlreadyExists'), 'warning');
            }
        }
        $app['template']->assign('form', $form->toHtml());
        $response = $app['template']->render_template('admin/questionmanager/edit_category.tpl');

        return new Response($response, 200, array());
    }

    /**
     * Edit category
     *
     * @param Application $app
     * @param $id
     * @return Response
     */
    public function editCategoryAction(Application $app, $id)
    {
        $objcat = new \Testcategory($id);

        if (!empty($objcat->c_id) || empty($objcat->id)) {
            $app->abort(401);
        }

        $url  = $app['url_generator']->generate('admin_category_edit', array('id' => $id));
        $form = new \FormValidator('edit', 'post', $url);

        $objcat->getForm($form, 'edit');
        $message = null;
        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $objcat = new \Testcategory(
                $id,
                $values['category_name'],
                $values['category_description'],
                $values['parent_id'],
                'global'
            );
            if ($objcat->modifyCategory()) {
                $message = \Display::return_message(get_lang('MofidfyCategoryDone'), 'confirmation');
            } else {
                $message = \Display::return_message(get_lang('ModifyCategoryError'), 'warning');
            }
            $url = $app['url_generator']->generate('admin_questions');
            return $app->redirect($url);
        }
        $app['template']->assign('message', $message);
        $app['template']->assign('form', $form->toHtml());
        $response = $app['template']->render_template('admin/questionmanager/edit_category.tpl');

        return new Response($response, 200, array());
    }

    /**
     * @param Application $app
     * @param int $id
     *
     * @return Response
     */
    public function deleteCategoryAction(Application $app, $id)
    {
        $repo     = $app['orm.ems']['db_write']->getRepository('Entity\CQuizCategory');
        $category = $repo->find($id);
        if (empty($category)) {
            $app->abort(404);
        }
        $count = $repo->childCount($category);

        if ($count == 0) {
            $testCategory = new \Testcategory($id);
            $count = $testCategory->getCategoryQuestionsNumber();
            if ($count == 0) {
                $objcat = new \Testcategory($id);
                $objcat->removeCategory();
                $url = $app['url_generator']->generate('admin_questions');
            }
            return $app->redirect($url);
        } else {
            $app->abort(401);
        }
    }


    /**
     * Show category
     *
     * @param Application $app
     * @param $id
     * @return Response
     */
    public function showCategoryAction(Application $app, $id)
    {
        $objcat = new \Testcategory($id);

        if (!empty($objcat->c_id)) {
            $app->abort(401);
        }

        $app['template']->assign('category', $objcat);

        $response = $app['template']->render_template('admin/questionmanager/show_category.tpl');

        return new Response($response, 200, array());
    }



}
