<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Controller\Admin\QuestionManager;

use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Class QuestionManagerController
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
     * Edits a question
     *
     * @param Application $app
     * @param int $id
     * @return Response
     */
    public function editQuestionAction(Application $app, $id)
    {
        $extraJS = array();
        //@todo improve this JS includes should be added using twig
        $extraJS[] = '<link href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/style.css" rel="stylesheet" type="text/css" />';
        $extraJS[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/jquery.fcbkcomplete.js" type="text/javascript" language="javascript"></script>';
        $app['extraJS'] = $extraJS;

        // Setting exercise obj.
        $exercise = new \Exercise();
        $exercise->edit_exercise_in_lp = true;

        // Setting question obj.
        /** @var  \Question $question */
        $question = \Question::read($id, null, $exercise);
        $question->submitClass = "btn save";
        $question->submitText  = get_lang('ModifyQuestion');
        $question->setDefaultValues = true;

        // Generating edit URL.
        $url = $app['url_generator']->generate('admin_questions_edit', array('id' => $id));

        // Creating a new form
        $form = new \FormValidator('edit_question', 'post', $url);
        $question->createForm($form);
        $question->createAnswersForm($form);

        $submitQuestion = $app['request']->get('submitQuestion');

        // If form was submitted.
        if ($form->validate() && isset($submitQuestion)) {
            // Save question.
            $question->processCreation($form, $exercise);

            // Save answers.
            $question->processAnswersCreation($form);
        }

        $app['template']->assign('form', $form->toHtml());
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

        $options = array(
            'decorate' => true,
            'rootOpen' => '<ul class="nav nav-list">',
            'rootClose' => '</ul>',
            'childOpen' => '<li>',
            'childClose' => '</li>',
            'nodeDecorator' => function ($row) use ($app) {
                $url = $app['url_generator']->generate('admin_questions_get_categories', array('id' => $row['iid']));
                return \Display::url($row['title'], $url, array('id' => $row['iid']));
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
     * @param Application $app
     * @param $categoryId
     * @return Response
     */
    public function getQuestionsByCategoryAction(Application $app, $categoryId)
    {
         // Getting CQuizCategory repo.
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $app['orm.em'];
        $repo = $em->getRepository('Entity\CQuizCategory');

        /** @var \Entity\CQuizCategory $category */
        $category = $repo->find($categoryId);
        //$questions = $category->getQuestions();

        /*$questionFields = $em->getRepository('Entity\QuestionField')->findAll();
        $rules = array();
        foreach ($questionFields as $extraField) {
            $extraField->getFieldVariable();
            $rules[] = ;
        }*/

        $questionColumns = \Question::getQuestionColumns();
        $columnModel = $questionColumns['column_model'];
        $columns = $questionColumns['columns'];
        $rules = $questionColumns['rules'];

        $grid = \Display::grid_html('questions');

        //jqgrid will use this URL to do the selects
        $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_questions&categoryId='.$categoryId;

        $extraParams['postData'] =array(
            'filters' => array(
                "groupOp" => "AND",
                "rules" => $rules
            )
        );

        // Autowidth.
        $extraParams['autowidth'] = 'true';
        // Height auto.
        $extraParams['height'] = 'auto';
        $token = null;
        $editUrl = $app['url_generator']->generate('admin_questions');

        /* '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=copy&id=\'+options.rowId+\'">'.
            \Display::return_icon('copy.png',get_lang('Copy'),'',ICON_SIZE_SMALL).'</a>'. */
        $actionLinks = 'function action_formatter(cellvalue, options, rowObject) {
            return \'<a target="_blank" href="'.$editUrl.'/\'+rowObject[0]+\'/edit">'.\Display::return_icon('edit.png',get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>'.'\';
        }';

        $js = \Display::grid_js('questions', $url, $columns, $columnModel, $extraParams, array(), $actionLinks, true);

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
        $extraJS = array();
        //@todo improve this JS includes should be added using twig
        $extraJS[] = api_get_jqgrid_js();
        $app['extraJS'] = $extraJS;

        // Getting CQuizCategory repo.
        /** @var \Gedmo\Tree\Entity\Repository\NestedTreeRepository  $repo */
        $repo = $app['orm.em']->getRepository('Entity\CQuizCategory');

        $categoryId = $app['request']->get('categoryId');
        $subtree = null;

        if (isset($categoryId)) {
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
            'decorate' => true,
            'rootOpen' => '<ul class="nav nav-list">',
            'rootClose' => '</ul>',
            'childOpen' => '<li>',
            'childClose' => '</li>',
            'nodeDecorator' => function ($row) use ($app, $categoryId, $subtree) {
                $url = $app['url_generator']->generate('admin_questions_get_categories', array('id' => $row['iid']));
                $url = \Display::url($row['title'], $url, array('id' => $row['iid']));
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
        $tree = $repo->buildTree($query->getArrayResult(), $options);


        $app['template']->assign('category_tree', $tree);

        // Getting globals
        $query = $app['orm.em']
            ->createQueryBuilder()
            ->select('node')
            ->from('Entity\CQuizCategory', 'node')
            ->where('node.cId = 0')
            ->orderBy('node.root, node.lft', 'ASC')
            ->getQuery();

        $tree = $repo->buildTree($query->getArrayResult(), $options);
        $app['template']->assign('global_category_tree', $tree);

        $response = $app['template']->render_template('admin/questionmanager/question_categories.tpl');
        return new Response($response, 200, array());

    }
}
