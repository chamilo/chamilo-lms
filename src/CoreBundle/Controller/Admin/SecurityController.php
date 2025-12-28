<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Repository\TrackELoginRecordRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/security')]
final class SecurityController extends BaseController
{
    public function __construct(
        private readonly TrackELoginRecordRepository $repo
    ) {}

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/login-attempts', name: 'admin_security_login_attempts', methods: ['GET'])]
    public function loginAttempts(Request $r): Response
    {
        $page = max(1, $r->query->getInt('page', 1));
        $pageSize = min(100, max(1, $r->query->getInt('pageSize', 25)));
        $filters = [
            'username' => trim((string) $r->query->get('username', '')),
            'ip' => trim((string) $r->query->get('ip', '')),
            'from' => $r->query->get('from'),
            'to' => $r->query->get('to'),
        ];

        $list = $this->repo->findFailedPaginated($page, $pageSize, $filters);

        $stats = [
            'byDay' => $this->repo->failedByDay(7),
            'byMonth' => $this->repo->failedByMonth(12),
            'topUsernames' => $this->repo->topUsernames(30, 5),
            'topIps' => $this->repo->topIps(30, 5),
            'successVsFailed' => $this->repo->successVsFailedByDay(30),
            'byHour' => $this->repo->failedByHourOfDay(7),
            'uniqueIps' => $this->repo->uniqueIpsByDay(30),
        ];

        return $this->render('@ChamiloCore/Admin/Security/login_attempts.html.twig', [
            'items' => $list['items'],
            'total' => $list['total'],
            'page' => $list['page'],
            'pageSize' => $list['pageSize'],
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }
}
