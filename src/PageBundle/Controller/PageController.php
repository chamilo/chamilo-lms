<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PageBundle\Controller;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\PageBundle\Entity\Block;
use Chamilo\PageBundle\Entity\Page;
use Chamilo\PageBundle\Entity\Snapshot;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sonata\PageBundle\Entity\BlockInteractor;
use Sonata\PageBundle\Entity\BlockManager;
use Sonata\PageBundle\Entity\PageManager;
use Sonata\PageBundle\Entity\SiteManager;
use Sonata\PageBundle\Page\TemplateManager;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     *
     * @return Response
     */
    public function getLatestPages($number, PageManager $pageManager): Response
    {
        $site = $this->get('sonata.page.site.selector')->retrieve();

        $criteria = ['enabled' => 1, 'site' => $site, 'decorate' => 1, 'routeName' => 'page_slug'];
        $order = ['createdAt' => 'desc'];
        // Get latest pages
        $pages = $pageManager->findBy($criteria, $order, $number);
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
            $snapshot = $this->get('sonata.page.manager.snapshot')->findEnableSnapshot($criteria);
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
     * Creates a site if needed checking the host and locale.
     * Creates a first root page.
     * Creates a page if it doesn't exists.
     * Updates the page if page exists.
     *
     * @param string              $pageSlug
     * @param bool                $redirect
     * @param Request             $request
     * @param SiteManager         $siteManager
     * @param PageManager         $pageManager
     * @param TemplateManager     $templateManager
     * @param TranslatorInterface $translator
     * @param BlockInteractor     $blockInteractor
     * @param BlockManager        $blockManager
     *
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function createPage(
        $pageSlug,
        $redirect,
        Request $request,
        SiteManager $siteManager,
        PageManager $pageManager,
        TemplateManager $templateManager,
        TranslatorInterface $translator,
        BlockInteractor $blockInteractor,
        BlockManager $blockManager
    ) {
        $host = $request->getHost();
        $criteria = [
            'locale' => $request->getLocale(),
            'host' => $host,
        ];

        $site = $siteManager->findOneBy($criteria);
        // If site doesn't exists or the site has a different locale from request, create new one.
        if (!$site || ($site && ($request->getLocale() !== $site->getLocale()))) {
            // Create new site for this host and language
            $site = $siteManager->create();
            $site->setHost($host);
            $site->setEnabled(true);
            $site->setName($host.' in language '.$request->getLocale());
            $site->setEnabledFrom(new \DateTime('now'));
            $site->setEnabledTo(new \DateTime('+20 years'));
            $site->setRelativePath('');
            $site->setIsDefault(false);
            $site->setLocale($request->getLocale());
            $site = $siteManager->save($site);

            // Create first root page
            /** @var \Sonata\PageBundle\Model\Page $page */
            $page = $pageManager->create();
            $page->setSlug('homepage');
            $page->setUrl('/');
            $page->setName('homepage');
            $page->setTitle('home');
            $page->setEnabled(true);
            $page->setDecorate(1);
            $page->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
            $page->setTemplateCode('default');
            $page->setRouteName('homepage');
            //$page->setParent($this->getReference('page-homepage'));
            $page->setSite($site);
            $pageManager->save($page);
        }

        $em = $this->getDoctrine()->getManager();
        $page = null;

        $form = $this->createFormBuilder()
            ->add('content', CKEditorType::class)
            ->add('save', SubmitType::class, ['label' => 'Update'])
            ->getForm();

        $blockToEdit = null;
        if ($site) {
            // Getting parent
            $criteria = ['site' => $site, 'enabled' => true, 'parent' => null];
            /** @var Page $page */
            $parent = $pageManager->findOneBy($criteria);

            // Check if a page exists for this site
            $criteria = ['site' => $site, 'enabled' => true, 'parent' => $parent, 'slug' => $pageSlug];

            /** @var Page $page */
            $page = $pageManager->findOneBy($criteria);
            if ($page) {
                $blocks = $page->getBlocks();
                /** @var Block $block */
                foreach ($blocks as $block) {
                    if ($block->getName() === 'Main content') {
                        $code = $block->getSetting('code');
                        if ($code === 'content') {
                            $children = $block->getChildren();
                            /** @var Block $child */
                            foreach ($children as $child) {
                                if ($child->getType() === 'sonata.formatter.block.formatter') {
                                    $blockToEdit = $child;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            } else {
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

                        if ($id === 'content' && $templateContainers[$id]['name'] === 'Main content') {
                            $parentBlock = $block;
                        }
                    }
                }

                // Create block in main content
                $myBlock = $blockManager->create();
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
            $this->addFlash('success', $translator->trans('Updated'));

            if (!empty($redirect)) {
                return $this->redirect($redirect);
            }

            return $this->redirectToRoute('home');
        }

        return $this->render(
            '@ChamiloTheme/Index/page_edit.html.twig',
            [
                'page' => $page,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * The Chamilo index home page.
     *
     * @Route("/internal_page/edit/{slug}", methods={"GET", "POST"}, name="edit_page")
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param string $slug
     *
     * @return Response
     */
    public function editPageAction($slug): Response
    {
        return $this->forward(
            'Chamilo\PageBundle\Controller\PageController:createPage',
            ['pageSlug' => $slug, 'redirect' => $this->generateUrl('edit_page', ['slug' => $slug])]
        );
    }

    /**
     * @Route("/internal_page/{slug}")
     *
     * @param string  $slug
     * @param Request $request
     * @param bool    $showEditPageLink
     *
     * @return Response
     */
    public function renderPageAction(
        string $slug,
        Request $request,
        $showEditPageLink = true,
        SiteManager $siteManager,
        PageManager $pageManager
    ) {
        $host = $request->getHost();
        $criteria = [
            'locale' => $request->getLocale(),
            'host' => $host,
        ];
        $site = $siteManager->findOneBy($criteria);

        $page = null;
        $blockToEdit = null;
        $contentText = null;

        if ($site) {
            // Parents only of homepage
            $criteria = ['site' => $site, 'enabled' => true, 'slug' => $slug];
            /** @var Page $page */
            $page = $pageManager->findOneBy($criteria);
            $blocks = $page->getBlocks();

            foreach ($blocks as $block) {
                if ($block->getName() !== 'Main content') {
                    continue;
                }

                $code = $block->getSetting('code');

                if ($code !== 'content') {
                    continue;
                }

                $children = $block->getChildren();

                /** @var Block $child */
                foreach ($children as $child) {
                    if ($child->getType() !== 'sonata.formatter.block.formatter') {
                        continue;
                    }

                    $contentText = $child->getSetting('content');
                    break 2;
                }
            }
        }

        return $this->render(
            '@ChamiloTheme/Index/page.html.twig',
            [
                'page' => $page,
                'slug' => $slug,
                'show_edit_page_link' => $showEditPageLink,
                'content' => $contentText,
            ]
        );
    }
}
