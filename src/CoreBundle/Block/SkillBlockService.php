<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Block;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\CoreBundle\Validator\ErrorElement;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CourseBlockService.
 *
 * @package Chamilo\CoreBundle\Block
 */
class SkillBlockService extends AbstractBlockService
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'Skill block';
    }

    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'template' => '@ChamiloTheme/Block/skill.html.twig',
                'ttl' => 0,
            ]
        );
    }

    public function validateBlock(
        ErrorElement $errorElement,
        BlockInterface $block
    ) {
    }

    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
    }

    /**
     * @param BlockContextInterface $blockContext
     * @param Response|null         $response
     *
     * @return Response
     */
    public function execute(
        BlockContextInterface $blockContext,
        Response $response = null
    ) {
        // merge settings
        $settings = $blockContext->getSettings();

        return $this->renderResponse(
            $blockContext->getTemplate(),
            [
                'block' => $blockContext->getBlock(),
                'settings' => $settings,
            ],
            $response
        );
    }
}
