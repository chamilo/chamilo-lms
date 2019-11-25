<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Block\BreadcrumbBlockService;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\ToolChain;
use Cocur\Slugify\SlugifyInterface;
use League\Flysystem\MountManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class AbstractResourceController.
 */
abstract class AbstractResourceController extends BaseController
{
    protected $mountManager;
    protected $toolChain;
    protected $translator;
    protected $breadcrumbBlockService;
    protected $fs;
    protected $slugify;

    public function __construct(
        MountManager $mountManager,
        ToolChain $toolChain,
        TranslatorInterface $translator,
        BreadcrumbBlockService $breadcrumbBlockService,
        SlugifyInterface $slugify
    ) {
        $this->mountManager = $mountManager;
        $this->fs = $mountManager->getFilesystem('resources_fs');
        $this->translator = $translator;
        $this->toolChain = $toolChain;
        $this->breadcrumbBlockService = $breadcrumbBlockService;
        $this->slugify = $slugify;
    }

    /**
     * @param string $variable
     *
     * @return string
     */
    public function trans($variable)
    {
        return $this->translator->trans($variable);
    }

    /**
     * @return ResourceRepository
     */
    public function getRepositoryFromRequest(Request $request)
    {
        $tool = $request->get('tool');
        $type = $request->get('type');

        return $this->getRepository($tool, $type);
    }

    /**
     * @param string $type
     *
     * @return ResourceRepository
     */
    public function getRepository($tool, $type)
    {
        $checker = $this->container->get('security.authorization_checker');
        $tool = $this->toolChain->getToolFromName($tool);

        $types = $tool->getTypes();
        if (!in_array($type, $types)) {
            return null;
        }

        $types = array_flip($types);
        $type = $types[$type];

        $resourceRepository = new ResourceRepository(
            $checker,
            $this->getDoctrine()->getManager(),
            $this->mountManager,
            $this->get('router'),
            $this->slugify,
            $type
        );

        return $resourceRepository;
    }
}
