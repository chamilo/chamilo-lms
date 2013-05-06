<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Class AdminController
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class AdminController
{
    /**
     * @param Application $app
     */
    public function indexAction(Application $app)
    {

    }

    /**
     * @param Application $app
     * @param int $id
     * @return mixed
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
                $url = $app['url_generator']->generate('admin_get_categories', array('id' => $row['iid']));
                return \Display::url($row['title'], $url);
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
     * @return int
     */
    public function getQuestionsByCategoryAction(Application $app, $categoryId)
    {
         // Getting CQuizCategory repo.
        $repo = $app['orm.em']->getRepository('Entity\CQuizCategory');

        /** @var \Entity\CQuizCategory $category */
        $category = $repo->find($categoryId);
        $questions = $category->getQuestions();

        $grid = \Display::grid_html('questions');

        //jqgrid will use this URL to do the selects
        $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_questions&categoryId='.$categoryId;

        //The order is important you need to check the the $column variable in the model.ajax.php file
        $columns = array('id', get_lang('Name'), get_lang('Description'), get_lang('Actions'));

        // Column config.
        $columnModel = array(
            array(
                'name' => 'iid',
                'index' => 'iid',
                'width' => '30',
                'align' => 'left'
            ),
            array(
                'name' => 'question',
                'index' => 'question',
                'width' => '150',
                'align' => 'left'
            ),
            array(
                'name'     => 'description',
                'index'    => 'description',
                'width'    => '150',
                'align'    => 'left',
                'sortable' => 'false'
            ),
            array(
                'name'      => 'actions',
                'index'     => 'actions',
                'width'     => '100',
                'align'     => 'left',
                'formatter' => 'action_formatter',
                'sortable'  => 'false'
            )
        );
        // Autowidth.
        $extraParams['autowidth'] = 'true';
        // height auto.
        $extraParams['height'] = 'auto';
        $token = null;

        $actionLinks = 'function action_formatter(cellvalue, options, rowObject) {
                         return \'<a href="?action=edit&id=\'+options.rowId+\'">'.\Display::return_icon('edit.png',get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>'.
            '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=copy&id=\'+options.rowId+\'">'.
            \Display::return_icon('copy.png',get_lang('Copy'),'',ICON_SIZE_SMALL).'</a>'.
            '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=delete&id=\'+options.rowId+\'">'.
            \Display::return_icon(          'delete.png',get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>'.'\';
        }';


        $js = \Display::grid_js('questions', $url, $columns, $columnModel, $extraParams, array(), $actionLinks, true);


        $app['template']->assign('grid', $grid);
        $app['template']->assign('js', $js);

        //$adapter = new DoctrineCollectionAdapter($questions);

        //$adapter    = new FixedAdapter($nbResults, array());
        /*$pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(10); // 10 by default
        $pagerfanta->setCurrentPage(1); // 1 by default
        */
        //$this->app['pagerfanta.view.router.name']   = 'userportal';
        /*$this->app['pagerfanta.view.router.params'] = array(
            'filter' => $filter,
            'type'   => 'courses',
            'page'   => $page
        );*/
        //$app['template']->assign('pagination', $pagerfanta);

        foreach ($questions as $question) {

        }
        $response = $app['template']->render_template('admin/questions.tpl');
        return new Response($response, 200, array());
    }

    /**
     *
     * @param Application $app
     */
    public function questionsAction(Application $app)
    {
        $extraJS = array();
        //@todo improve this JS includes should be added using twig
        $extraJS[] = api_get_jqgrid_js();
        $app['extraJS'] = $extraJS;

        // Getting CQuizCategory repo.
        $repo = $app['orm.em']->getRepository('Entity\CQuizCategory');

        $options = array(
            'decorate' => true,
            'rootOpen' => '<ul class="nav nav-list">',
            'rootClose' => '</ul>',
            'childOpen' => '<li>',
            'childClose' => '</li>',
            'nodeDecorator' => function ($row) use ($app) {
                $url = $app['url_generator']->generate('admin_get_categories', array('id' => $row['iid']));
                return \Display::url($row['title'], $url);
            }
            //'representationField' => 'slug',
            //'html' => true
        );

        // Getting all categories only first level lvl=1
        $query = $app['orm.em']
            ->createQueryBuilder()
            ->select('node')
            ->from('Entity\CQuizCategory', 'node')
            ->where('node.cId <> 0 AND node.lvl = 0')
            ->orderBy('node.root, node.lft', 'ASC')
            ->getQuery();

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

        $response = $app['template']->render_template('admin/question_categories.tpl');
        return new Response($response, 200, array());

    }
}