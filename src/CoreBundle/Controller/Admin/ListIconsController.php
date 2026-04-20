<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ListIconsController extends AbstractController
{
    private const ICON_ENUMS = [
        'ActionIcon' => ActionIcon::class,
        'ToolIcon' => ToolIcon::class,
        'ObjectIcon' => ObjectIcon::class,
        'StateIcon' => StateIcon::class,
    ];

    #[Route('/admin/list-icons-data', name: 'admin_list_icons_data', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $groups = [];

        foreach (self::ICON_ENUMS as $shortName => $enumClass) {
            $icons = [];
            foreach ($enumClass::cases() as $case) {
                $icons[] = [
                    'name' => $case->name,
                    'value' => $case->value,
                ];
            }

            $groups[] = [
                'class' => $shortName,
                'fqcn' => $enumClass,
                'icons' => $icons,
            ];
        }

        return $this->json(['groups' => $groups]);
    }
}
