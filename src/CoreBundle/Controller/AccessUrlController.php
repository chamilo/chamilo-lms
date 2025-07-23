<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/access-url')]
class AccessUrlController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $em,
    ) {}

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/users/import', name: 'chamilo_core_access_url_users_import', methods: ['GET', 'POST'])]
    public function importUsers(Request $request): Response
    {
        $report = [];

        if ($request->isMethod('POST') && $request->files->has('csv_file')) {
            $file = $request->files->get('csv_file')->getPathname();
            $handle = fopen($file, 'r');
            $lineNumber = 0;

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $lineNumber++;

                if ($lineNumber === 1 && strtolower(trim($data[0])) === 'username') {
                    continue; // Skip header
                }

                if (count($data) < 2) {
                    $report[] = $this->formatReport('alert-circle', 'Line %s: invalid format. Two columns expected.', [$lineNumber]);
                    continue;
                }

                [$username, $url] = array_map('trim', $data);

                if (!$username || !$url) {
                    $report[] = $this->formatReport('alert-circle', 'Line %s: missing username or URL.', [$lineNumber]);
                    continue;
                }

                // Normalize URL
                if (!str_starts_with($url, 'http')) {
                    $url = 'https://' . $url;
                }
                if (!str_ends_with($url, '/')) {
                    $url .= '/';
                }

                $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
                if (!$user) {
                    $report[] = $this->formatReport('close-circle', "Line %s: user '%s' not found.", [$lineNumber, $username]);
                    continue;
                }

                $accessUrl = $this->em->getRepository(AccessUrl::class)->findOneBy(['url' => $url]);
                if (!$accessUrl) {
                    $report[] = $this->formatReport('close-circle', "Line %s: URL '%s' not found.", [$lineNumber, $url]);
                    continue;
                }

                if ($accessUrl->hasUser($user)) {
                    $report[] = $this->formatReport('information-outline', "Line %s: user '%s' is already assigned to '%s'.", [$lineNumber, $username, $url]);
                } else {
                    $accessUrl->addUser($user);
                    $this->em->persist($accessUrl);
                    $report[] = $this->formatReport('check-circle', "Line %s: user '%s' successfully assigned to '%s'.", [$lineNumber, $username, $url]);
                }
            }

            fclose($handle);
            $this->em->flush();
        }

        return $this->render('@ChamiloCore/AccessUrl/import_users.html.twig', [
            'report' => $report,
            'title' => $this->translator->trans('Assign users to URLs from CSV'),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/users/remove', name: 'chamilo_core_access_url_users_remove', methods: ['GET', 'POST'])]
    public function removeUsers(Request $request): Response
    {
        $report = [];

        if ($request->isMethod('POST') && $request->files->has('csv_file')) {
            $file = $request->files->get('csv_file')->getPathname();
            $handle = fopen($file, 'r');
            $lineNumber = 0;

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $lineNumber++;

                if ($lineNumber === 1 && strtolower(trim($data[0])) === 'username') {
                    continue; // Skip header
                }

                [$username, $url] = array_map('trim', $data);

                if (!$username || !$url) {
                    $report[] = $this->formatReport('alert-circle', 'Line %s: empty fields.', [$lineNumber]);
                    continue;
                }

                if (!str_starts_with($url, 'http')) {
                    $url = 'https://' . $url;
                }
                if (!str_ends_with($url, '/')) {
                    $url .= '/';
                }

                $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
                if (!$user) {
                    $report[] = $this->formatReport('close-circle', "Line %s: user '%s' not found.", [$lineNumber, $username]);
                    continue;
                }

                $accessUrl = $this->em->getRepository(AccessUrl::class)->findOneBy(['url' => $url]);
                if (!$accessUrl) {
                    $report[] = $this->formatReport('close-circle', "Line %s: URL '%s' not found.", [$lineNumber, $url]);
                    continue;
                }

                foreach ($accessUrl->getUsers() as $rel) {
                    if ($rel->getUser()->getId() === $user->getId()) {
                        $this->em->remove($rel);
                        $report[] = $this->formatReport('account-remove-outline', "Line %s: user '%s' removed from '%s'.", [$lineNumber, $username, $url]);
                        continue 2;
                    }
                }

                $report[] = $this->formatReport('alert-circle', 'Line %s: no relation found between user and URL.', [$lineNumber]);
            }

            fclose($handle);
            $this->em->flush();
        }

        return $this->render('@ChamiloCore/AccessUrl/remove_users.html.twig', [
            'report' => $report,
            'title' => $this->translator->trans('Remove users from URLs from CSV')
        ]);
    }

    private function formatReport(string $icon, string $message, array $params): string
    {
        $text = vsprintf($this->translator->trans($message), $params);
        return sprintf('<i class="mdi mdi-%s text-base me-1"></i> %s', $icon, $text);
    }
}
