<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Storage\StorageInterface;

#[Route('/admin')]
class AdminController extends BaseController
{
    private const ITEMS_PER_PAGE = 50;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResourceNodeRepository $resourceNodeRepository,
        private StorageInterface $storage,
        private AccessUrlHelper $accessUrlHelper
    ) {}

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/register-campus', name: 'admin_register_campus', methods: ['POST'])]
    public function registerCampus(Request $request, SettingsManager $settingsManager): Response
    {
        $requestData = $request->toArray();
        $doNotListCampus = (bool) $requestData['donotlistcampus'];

        $settingsManager->setUrl($this->accessUrlHelper->getCurrent());
        $settingsManager->updateSetting('platform.registered', 'true');

        $settingsManager->updateSetting(
            'platform.donotlistcampus',
            $doNotListCampus ? 'true' : 'false'
        );

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/files_info', name: 'admin_files_info', methods: ['GET'])]
    public function listFilesInfo(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search', '');
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;

        $queryBuilder = $this->entityManager->getRepository(ResourceFile::class)->createQueryBuilder('rf')
            ->leftJoin('rf.resourceNode', 'rn')
            ->leftJoin('rn.resourceLinks', 'rl')
            ->leftJoin('rl.course', 'c')
            ->leftJoin('rl.user', 'u')
            ->addSelect('rn', 'rl', 'c', 'u')
        ;

        if ($search) {
            $queryBuilder->where('rf.title LIKE :search')
                ->orWhere('rf.originalName LIKE :search')
                ->orWhere('c.title LIKE :search')
                ->orWhere('u.username LIKE :search')
                ->orWhere('rn.uuid LIKE :search')
                ->setParameter('search', '%'.$search.'%')
            ;
        }

        $queryBuilder->orderBy('rf.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(self::ITEMS_PER_PAGE)
        ;

        $files = $queryBuilder->getQuery()->getResult();

        $totalItemsQuery = $this->entityManager->getRepository(ResourceFile::class)
            ->createQueryBuilder('rf')
            ->leftJoin('rf.resourceNode', 'rn')
            ->leftJoin('rn.resourceLinks', 'rl')
            ->leftJoin('rl.course', 'c')
            ->leftJoin('rl.user', 'u')
            ->select('COUNT(rf.id)')
        ;

        if ($search) {
            $totalItemsQuery->where('rf.title LIKE :search')
                ->orWhere('rf.originalName LIKE :search')
                ->orWhere('c.title LIKE :search')
                ->orWhere('u.username LIKE :search')
                ->orWhere('rn.uuid LIKE :search')
                ->setParameter('search', '%'.$search.'%')
            ;
        }

        $totalItems = $totalItemsQuery->getQuery()->getSingleScalarResult();
        $totalPages = ceil($totalItems / self::ITEMS_PER_PAGE);

        $fileUrls = [];
        $filePaths = [];
        foreach ($files as $file) {
            $resourceNode = $file->getResourceNode();
            if ($resourceNode) {
                $fileUrls[$file->getId()] = $this->resourceNodeRepository->getResourceFileUrl($resourceNode);
                $creator = $resourceNode->getCreator();
            } else {
                $fileUrls[$file->getId()] = null;
                $creator = null;
            }
            $filePaths[$file->getId()] = $this->resourceNodeRepository->getFilename($file);
        }

        return $this->render('@ChamiloCore/Admin/files_info.html.twig', [
            'files' => $files,
            'fileUrls' => $fileUrls,
            'filePaths' => $filePaths,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
        ]);
    }
}
