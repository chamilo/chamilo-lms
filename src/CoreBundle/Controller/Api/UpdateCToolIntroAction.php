<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CToolIntro;
use Chamilo\CourseBundle\Repository\CToolIntroRepository;
use Symfony\Component\HttpFoundation\Request;

class UpdateCToolIntroAction extends BaseResourceFileAction
{
    public function __invoke(
        CToolIntro $toolIntro,
        Request $request,
        CToolIntroRepository $repo,
        EntityManager $em
    ): CToolIntro {
        $this->handleUpdateRequest($toolIntro, $repo, $request, $em);

        $result = json_decode($request->getContent(), true);

        var_dump($result);

        //$toolIntro = $repo->updateToolIntro($em, $result['introText']);

        return $toolIntro;
    }
}
