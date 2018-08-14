<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PageBundle\Controller;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\PageBundle\Entity\Page;
use Chamilo\PageBundle\Entity\Snapshot;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PageController.
 *
 * @package Chamilo\PageBundle\Controller
 */
class PageController extends BaseController
{
    /**
     * @Route("/cms/page/latest/{number}")
     *
     * @param int $number
     */
    public function getLatestPages($number)
    {
        $site = $this->container->get('sonata.page.site.selector')->retrieve();

        $criteria = ['enabled' => 1, 'site' => $site, 'decorate' => 1, 'routeName' => 'page_slug'];
        $order = ['createdAt' => 'desc'];
        // Get latest pages
        $pages = $this->container->get('sonata.page.manager.page')->findBy($criteria, $order, $number);
        $pagesToShow = [];
        /** @var Page $page */
        foreach ($pages as $page) {
            // Skip homepage
            if ($page->getUrl() === '/') {
                continue;
            }
            $criteria = ['pageId' => $page];
            /** @var Snapshot $snapshot */
            // Check if page has a valid snapshot
            $snapshot = $this->container->get('sonata.page.manager.snapshot')->findEnableSnapshot($criteria);
            if ($snapshot) {
                $pagesToShow[] = $page;
            }
        }

        return $this->render(
            '@ChamiloPage/latest.html.twig',
            ['pages' => $pagesToShow]
        );
    }

    /**
     * @param string  $pageSlug
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function createPage($pageSlug, $redirect, Request $request)
    {
        $siteSelector = $this->container->get('sonata.page.site.selector');
        $site = $siteSelector->retrieve();
        $em = $this->getDoctrine()->getManager();
        $page = null;

        $form = $this->createFormBuilder()
            ->add('content', CKEditorType::class)
            ->add('save', SubmitType::class, ['label' => 'Update'])
            ->getForm();

        $blockToEdit = null;
        if ($site) {
            $pageManager = $this->get('sonata.page.manager.page');
            // Parents only of homepage
            $criteria = ['site' => $site, 'enabled' => true, 'parent' => 1, 'slug' => $pageSlug];
            /** @var Page $page */
            $page = $pageManager->findOneBy($criteria);
            if ($page) {
                $blocks = $page->getBlocks();
                /** @var Block $block */
                foreach ($blocks as $block) {
                    if ($block->getName() == 'Main content') {
                        $code = $block->getSetting('code');
                        if ($code == 'content') {
                            $children = $block->getChildren();
                            /** @var Block $child */
                            foreach ($children as $child) {
                                if ($child->getType() == 'sonata.formatter.block.formatter') {
                                    $blockToEdit = $child;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            } else {
                $pageManager = $this->get('sonata.page.manager.page');

                $criteria = ['site' => $site, 'enabled' => true, 'parent' => null, 'slug' => 'homepage'];
                /** @var Page $page */
                $parent = $pageManager->findOneBy($criteria);

                $page = $pageManager->create();
                $page->setSlug($pageSlug);
                $page->setUrl('/'.$pageSlug);
                $page->setName($pageSlug);
                $page->setTitle($pageSlug);
                $page->setEnabled(true);
                $page->setDecorate(1);
                $page->setRequestMethod('GET');
                $page->setTemplateCode('default');
                $page->setRouteName($pageSlug);
                $page->setParent($parent);
                $page->setSite($site);

                $pageManager->save($page);

                $templateManager = $this->get('sonata.page.template_manager');
                $template = $templateManager->get('default');
                $templateContainers = $template->getContainers();

                $containers = [];
                foreach ($templateContainers as $id => $area) {
                    $containers[$id] = [
                        'area' => $area,
                        'block' => false,
                    ];
                }

                // Create blocks for this page
                $blockInteractor = $this->get('sonata.page.block_interactor');
                $parentBlock = null;
                foreach ($containers as $id => $area) {
                    if (false === $area['block'] && $templateContainers[$id]['shared'] === false) {
                        $block = $blockInteractor->createNewContainer(
                            [
                                'page' => $page,
                                'name' => $templateContainers[$id]['name'],
                                'code' => $id,
                            ]
                        );

                        if ($id === 'content' && $templateContainers[$id]['name'] == 'Main content') {
                            $parentBlock = $block;
                        }
                    }
                }

                // Create block in main content
                $block = $this->get('sonata.page.manager.block');
                /** @var \Sonata\BlockBundle\Model\Block $myBlock */
                $myBlock = $block->create();
                $myBlock->setType('sonata.formatter.block.formatter');
                $myBlock->setSetting('format', 'richhtml');
                $myBlock->setSetting('content', '');
                $myBlock->setSetting('rawContent', '');
                $myBlock->setSetting('template', '@SonataFormatter/Block/block_formatter.html.twig');
                $myBlock->setParent($parentBlock);
                $page->addBlocks($myBlock);
                $pageManager->save($page);
            }
        }

        if ($blockToEdit) {
            $form->setData(['content' => $blockToEdit->getSetting('content')]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $blockToEdit) {
            $data = $form->getData();
            $content = $data['content'];
            /** @var Block $blockToEdit */
            $blockToEdit->setSetting('rawContent', $content);
            $blockToEdit->setSetting('content', $content);
            $em->merge($blockToEdit);
            $em->flush();

            $this->addFlash('success', $this->trans('Updated'));

            if (!empty($redirect)) {
                return $this->redirect($redirect);
            }

            return $this->redirectToRoute('home');
        }

        $template = $pageSlug.'_edit.html.twig';

        return $this->render(
            '@ChamiloCore/Index/'.$template,
            [
                'page' => $page,
                'form' => $form->createView(),
            ]
        );
    }
}
