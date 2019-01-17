<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Block;

use Chamilo\CoreBundle\Entity\Course;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\SeoBundle\Block\Breadcrumb\BaseBreadcrumbMenuBlockService;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class BreadcrumbBlockService.
 *
 * @package Chamilo\CoreBundle\Block
 */
class BreadcrumbBlockService extends BaseBreadcrumbMenuBlockService
{
    protected $extraChildren;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'chamilo_core.block.breadcrumb';
    }

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        parent::configureSettings($resolver);

        $resolver->setDefaults([
            //'menu_template' => 'SonataSeoBundle:Block:breadcrumb.html.twig',
            'menu_template' => '@ChamiloTheme/Breadcrumb/breadcrumb_legacy.html.twig',
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

    /**
     * {@inheritdoc}
     */
    protected function getMenu(BlockContextInterface $blockContext)
    {
        $menu = $this->getRootMenu($blockContext);

        $menu->addChild('Home', ['route' => 'home']);

        // Add course
        /** @var Course $course */
        if ($course = $blockContext->getBlock()->getSetting('course')) {
            if (is_array($course)) {
                $title = $course['title'];
                $code = $course['code'];
            } else {
                $title = $course->getTitle();
                $code = $course->getCode();
            }

            $menu->addChild(
                $title,
                [
                    'route' => 'course_home',
                    'routeParameters' => [
                        'course' => $code,
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
                if ($url == '#') {
                    $menu->addChild($data['name']);
                } else {
                    $menu->addChild($data['name'], ['uri' => $url]);
                }
            }
        }

        // Set CSS classes for the items
        foreach ($menu->getChildren() as $child) {
            $child
                //->setLinkAttribute('class', 'nav-link')
                ->setAttribute('class', 'breadcrumb-item');
        }

        return $menu;
    }
}
