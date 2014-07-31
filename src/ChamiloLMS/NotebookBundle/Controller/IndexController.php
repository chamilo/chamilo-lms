<?php

namespace ChamiloLMS\NotebookBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class IndexController extends Controller
{
    /**
     * @Route("/")
     * @Template("ChamiloLMSNotebookBundle::index.html.twig")
     */
    public function indexAction()
    {
        return array('dada' => 'great');
    }


}
