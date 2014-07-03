<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Controller\Admin\QuestionManager;

use ChamiloLMS\CoreBundle\Controller\BaseController;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class QuestionManagerController
 * @todo reduce controller size
 * @package ChamiloLMS\CoreBundle\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class QuestionManagerController extends BaseController
{
    /**
     * Show the index page for the question manager
     *
     * @Route("/")
     * @Method({"GET"})
     * @return Response
     */
    public function indexAction()
    {
        $response = $this->renderTemplate('questionmanager.tpl');

        return new Response($response, 200, array());
    }

    /**
     * Edits a question for the question manager
     *
     * @Route("/edit")
     * @Method({"GET"})
     * @param int $id
     * @return Response
     */
    public function editQuestionAction($id)
    {
        // Setting exercise obj.
        $exercise = new \Exercise();
        $exercise->edit_exercise_in_lp = true;

        // Setting question obj.
        /** @var  \Question $question */
        $question = \Question::read($id, null, $exercise);
        $question->submitClass = "btn save";
        $question->submitText = get_lang('ModifyQuestion');
        $question->setDefaultValues = true;

        // Generating edit URL.
        $url = $this->generateControllerUrl('editQuestionAction', array('id' => $id));

        // Creating a new form
        $form = new \FormValidator('edit_question', 'post', $url);

        $extraFields = new \ExtraField('question');
        $extraFields->addElements($form, $id);
        // Validating if there are extra fields to modify.
        if (count($form->_elements) > 1) {
            $form->addElement('button', 'submit', get_lang('Update'));

            $this->getTemplate()->assign('question', $question);
            $this->getTemplate()->assign('form', $form->toHtml());
        } else {
            $this->addMessage(get_lang('ThereAreNotExtrafieldsAvailable'), 'warning');
        }

        // If form was submitted.
        if ($form->validate()) {
            $field_value = new \ExtraFieldValue('question');
            $params = $form->exportValues();
            $params['question_id'] = $id;
            $field_value->save_field_values($params);
            $this->addMessage(get_lang('ItemUpdated'), 'success');
            $url = $this->generateControllerUrl('editQuestionAction', array('id' => $id));

            return $this->redirect($url);
        }

        $response = $this->renderTemplate('edit_question.tpl');

        return new Response($response, 200, array());
    }

    /**
     * Get question categories per id
     *
     * @Route("/get_categories")
     * @Method({"GET"})
     * @param int $id
     * @return string
     */
    public function getCategoriesAction($id)
    {
        // Getting CQuizCategory repo.
        $repo = $this->getManager()->getRepository('ChamiloLMS\CoreBundle\Entity\CQuizCategory');

        $options  = array(
            'decorate'      => true,
            'rootOpen'      => '<ul class="nav nav-list">',
            'rootClose'     => '</ul>',
            'childOpen'     => '<li>',
            'childClose'    => '</li>',
            'nodeDecorator' => function ($row) {
                $url = $this->generateControllerUrl(
                    'getQuestionsByCategoryAction',
                    array('id' => $row['iid'])
                );

                return \Display::url(
                    $row['title'],
                    $url,
                    array('id' => $row['iid'])
                );
            }
            //'representationField' => 'slug',
            //'html' => true
        );
        $cats = $repo->findOneByIid($id);
        $htmlTree = $repo->childrenHierarchy(
            $cats, /* starting from root nodes */
            true, /* false: load all children, true: only direct */
            $options
        );

        return $htmlTree;
    }

    /**
     * Gets the question list per category
     * @Route("/get_questions_by_category")
     * @Method({"GET"})
     * @param $categoryId
     * @return Response
     */
    public function getQuestionsByCategoryAction($categoryId)
    {
        // Getting CQuizCategory repo.
        /** @var \Doctrine\ORM\EntityManager $em */
        $em   = $this->getManager();
        $repo = $em->getRepository('ChamiloLMS\CoreBundle\Entity\CQuizCategory');

        /** @var \ChamiloLMS\CoreBundle\Entity\CQuizCategory $category */
        $category = $repo->find($categoryId);

        $questionColumns = \Question::getQuestionColumns();
        $columnModel     = $questionColumns['column_model'];
        $columns         = $questionColumns['columns'];
        $rules           = $questionColumns['rules'];

        $grid = \Display::grid_html('questions');

        //jqgrid will use this URL to do the selects
        $url = $this->generateUrl('model_ajax').'?a=get_questions&categoryId='.$categoryId;

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
        $editUrl = $this->generateControllerUrl('getQuestionsAction');

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
        $this->getTemplate()->assign('category_children', $count);
        $this->getTemplate()->assign('category', $category);
        $this->getTemplate()->assign('grid', $grid);
        $this->getTemplate()->assign('js', $js);

        $response = $this->renderTemplate('questions.tpl');

        return new Response($response, 200, array());
    }

    /**
     * Index of the question manager
     * @Route("/questions")
     * @Method({"GET"})
     * @return Response
     *
     */
    public function getQuestionsAction($categoryId = null)
    {
        //$this->getTemplate()->addResource(api_get_jqgrid_js());

        // Getting CQuizCategory repo.
        /** @var \Gedmo\Tree\Entity\Repository\NestedTreeRepository $repo */

        $repo = $this->getManager()->getRepository('ChamiloLMS\CoreBundle\Entity\CQuizCategory');
        $categoryId = $this->getRequest()->get('categoryId');
        $subtree = null;

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
            'nodeDecorator' => function ($row) use ($categoryId, $subtree) {
                $url   = $this->generateUrl(
                    'question_manager.controller:getQuestionsByCategoryAction',
                    array('id' => $row['iid'])
                );
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

        $qb = $this->getManager()->createQueryBuilder()
            ->select('node')
            ->from('ChamiloLMS\CoreBundle\Entity\CQuizCategory', 'node')
            ->where('node.cId <> 0 AND node.lvl = 0')
            ->orderBy('node.root, node.lft', 'ASC');

        //$node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false
        //$qb = $repo->getChildrenQueryBuilder(null, true, 'title', 'ASC', true);
        $query = $qb->getQuery();
        $tree  = $repo->buildTree($query->getArrayResult(), $options);

        $this->getTemplate()->assign('category_tree', $tree);

        // Getting globals
        $query = $this->getManager()
            ->createQueryBuilder()
            ->select('node')
            ->from('ChamiloLMS\CoreBundle\Entity\CQuizCategory', 'node')
            ->where('node.cId = 0 AND node.lvl = 0')
            ->orderBy('node.root, node.lft', 'ASC')
            ->getQuery();

        $tree = $repo->buildTree($query->getArrayResult(), $options);
        $this->getTemplate()->assign('global_category_tree', $tree);

        $response = $this->renderTemplate('question_categories.tpl');

        return new Response($response, 200, array());
    }

    /**
     * New category
     * @Route("/new_category")
     * @Method({"GET"})
     * @return Response
     */
    public function newCategoryAction()
    {
        $url  = $this->generateUrl('question_manager.controller:newCategoryAction');
        $form = new \FormValidator('new', 'post', $url);

        $objcat = new \Testcategory();
        $objcat->getForm($form, 'new');
        $message = null;
        if ($form->validate()) {
            $values = $form->getSubmitValues();

            $parent_id = isset($values['parent_id']) && isset($values['parent_id'][0]) ? $values['parent_id'][0] : null;
            $category = new \Testcategory(
                0,
                $values['category_name'],
                $values['category_description'],
                $parent_id,
                'global'
            );
            $categoryId = $category->addCategoryInBDD();

            if ($categoryId) {
                $this->addMessage(get_lang('AddCategoryDone'), 'confirmation');
                //$message = \Display::return_message(get_lang('AddCategoryDone'), 'confirmation');
                //$url = $this->generateUrl('admin_category_show', array('id' => $categoryId));
                $url = $this->generateUrl('question_manager.controller:indexAction');
                return $this->redirect($url);
            } else {
                $this->addMessage(get_lang('AddCategoryNameAlreadyExists'), 'warning');
            }
        }
        $this->getTemplate()->assign('form', $form->toHtml());
        $response = $this->renderTemplate('edit_category.tpl');

        return new Response($response, 200, array());
    }

    /**
     * Edit category
     * @Route("/edit_category")
     * @Method({"GET"})
     * @param $id
     * @return Response
     */
    public function editCategoryAction($id)
    {
        $objcat = new \Testcategory($id);

        if (!empty($objcat->c_id) || empty($objcat->id)) {
            $this->abort(401);
        }

        $url  = $this->generateUrl('editCategoryAction', array('id' => $id));
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
                $this->addMessage(get_lang('MofidfyCategoryDone'), 'confirmation');
            } else {
                $this->addMessage(get_lang('ModifyCategoryError'), 'warning');
            }
            $url = $this->generateUrl('admin_questions');
            return $this->redirect($url);
        }
        $this->getTemplate()->assign('message', $message);
        $this->getTemplate()->assign('form', $form->toHtml());
        $response = $this->renderTemplate('edit_category.tpl');

        return new Response($response, 200, array());
    }

    /**
     * @Route("/delete_category")
     * @Method({"GET"})
     * @param int $id
     *
     * @return Response
     */
    public function deleteCategoryAction($id)
    {
        $repo = $this->getManager()->getRepository('ChamiloLMS\CoreBundle\Entity\CQuizCategory');
        $category = $repo->find($id);
        if (empty($category)) {
            $this->abort(404);
        }
        $count = $repo->childCount($category);

        if ($count == 0) {
            $testCategory = new \Testcategory($id);
            $count = $testCategory->getCategoryQuestionsNumber();
            if ($count == 0) {
                $objcat = new \Testcategory($id);
                $objcat->removeCategory();
                $url = $this->generateUrl('admin_questions');
            }
            return $this->redirect($url);
        } else {
            $this->abort(401);
        }
    }


    /**
     * Show category
     *
     * @param $id
     * @return Response
     */
    public function showCategoryAction($id)
    {
        $objcat = new \Testcategory($id);

        if (!empty($objcat->c_id)) {
            $this->abort(401);
        }

        $this->getTemplate()->assign('category', $objcat);

        $response = $this->renderTemplate('show_category.tpl');

        return new Response($response, 200, array());
    }
}
