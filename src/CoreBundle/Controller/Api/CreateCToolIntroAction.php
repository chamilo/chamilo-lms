<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Chamilo\CourseBundle\Repository\CToolIntroRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Component\HttpFoundation\Request;

class CreateCToolIntroAction extends BaseResourceFileAction
{
    public function __invoke(Request $request, CToolIntroRepository $repo, EntityManager $em): CToolIntro
    {
        $result = $this->handleCreateRequest(new CToolIntro(), $repo, $request);
        $toolIntro = $repo->updateToolIntro($em, $result['introText']);

        return $toolIntro;
    }
}
