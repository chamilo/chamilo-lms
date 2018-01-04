<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Block;

use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Symfony\Component\HttpFoundation\Response;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\CoreBundle\Validator\ErrorElement;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CourseBlockService
 * @package Chamilo\CoreBundle\Block
 */
class CourseBlockService extends AbstractBlockService
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'Course block';
    }

    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'url'      => false,
            'title'    => 'Course block',
            'template' => 'ChamiloCoreBundle:Block:course.html.twig',
            'ttl' => 0
        ));
    }

    public function validateBlock(ErrorElement $errorElement, BlockInterface $block)
    {
    }

    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
    }

    /**
     * @param BlockContextInterface $blockContext
     * @param Response|null $response
     * @return Response
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        // merge settings
        $settings = $blockContext->getSettings();
        $hotCourses = \CourseManager::return_hot_courses();

        return $this->renderResponse(
            $blockContext->getTemplate(),
            array(
                'hot_courses' => $hotCourses,
                'block' => $blockContext->getBlock(),
                'settings' => $settings,
            ),
            $response
        );
    }
}
