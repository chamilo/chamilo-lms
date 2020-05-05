<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Block;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\SeoBundle\Block\Breadcrumb\BaseBreadcrumbMenuBlockService;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class BreadcrumbBlockService.
 */
class BreadcrumbBlockService extends BaseBreadcrumbMenuBlockService
{
    protected $extraChildren;

    public function getName()
    {
        return 'chamilo_core.block.breadcrumb';
    }

    public function configureSettings(OptionsResolver $resolver)
    {
        parent::configureSettings($resolver);

        $resolver->setDefaults([
            //'menu_template' => 'SonataSeoBundle:Block:breadcrumb.html.twig',
            'menu_template' => '@ChamiloCore/Breadcrumb/breadcrumb_legacy.html.twig',
            'include_homepage_link' => false,
            'context' => false,
        ]);
    }

    /**
     * @param string $title
     * @param array  $params
     */
    public function addChild($title, $params = [])
    {
        $this->extraChildren[] = ['title' => $title, 'params' => $params];
    }

    protected function getMenu(BlockContextInterface $blockContext)
    {
        $menu = $this->getRootMenu($blockContext);
        $menu->addChild('', ['route' => 'home'])->setExtra('icon', 'fas fa-home');
        $sessionId = 0;
        // Course/Session block are set here src/ThemeBundle/Resources/views/Layout/breadcrumb.html.twig
        if ($blockContext->getBlock()->getSetting('session')) {
            /** @var Session $course */
            $session = $blockContext->getBlock()->getSetting('session');
            if ($session && $session instanceof Session) {
                $sessionId = $session->getId();
            }
        }

        // Add course
        /** @var Course $course */
        if ($course = $blockContext->getBlock()->getSetting('course')) {
            $title = $course->getTitle();
            $courseId = $course->getId();

            $menu->addChild(
                $title,
                [
                    'route' => 'chamilo_core_course_home',
                    'routeParameters' => [
                        'cid' => $courseId,
                        'sid' => $sessionId,
                    ],
                ]
            );
        }

        if (!empty($this->extraChildren)) {
            foreach ($this->extraChildren as $item) {
                $params = isset($item['params']) ? $item['params'] : [];
                $menu->addChild(
                    $item['title'],
                    $params
                );
            }
        }

        // Load legacy breadcrumbs
        $oldBreadCrumb = $blockContext->getBlock()->getSetting('legacy_breadcrumb');

        if ($oldBreadCrumb) {
            foreach ($oldBreadCrumb as $data) {
                if (empty($data['name'])) {
                    continue;
                }
                $url = $data['url'];
                if ('#' === $url) {
                    $menu->addChild($data['name']);
                } else {
                    $menu->addChild($data['name'], ['uri' => $url]);
                }
            }
        }

        // Set CSS classes for the items
        foreach ($menu->getChildren() as $child) {
            $child->setAttribute('class', 'breadcrumb-item');
        }

        return $menu;
    }
}
