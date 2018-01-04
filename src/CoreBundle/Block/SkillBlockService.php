<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Block;

use Symfony\Component\HttpFoundation\Response;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\CoreBundle\Validator\ErrorElement;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Sonata\BlockBundle\Block\BlockContextInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CourseBlockService
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
            array(
                'template' => 'ChamiloCoreBundle:Block:skill.html.twig',
                'ttl' => 0,
            )
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
     * @param Response|null $response
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
            array(
                'block' => $blockContext->getBlock(),
                'settings' => $settings,
            ),
            $response
        );
    }
}
