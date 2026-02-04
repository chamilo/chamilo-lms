<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Page;
use Chamilo\CoreBundle\Entity\PageCategory;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\PageCategoryRepository;
use Chamilo\CoreBundle\Repository\PageRepository;
use Chamilo\CoreBundle\Repository\SysAnnouncementRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class PageHelper
{
    protected PageRepository $pageRepository;
    protected PageCategoryRepository $pageCategoryRepository;

    /**
     * Repository used to read system announcements (platform news).
     */
    protected SysAnnouncementRepository $sysAnnouncementRepository;

    /**
     * Helper used to retrieve the current AccessUrl.
     */
    protected AccessUrlHelper $accessUrlHelper;

    public function __construct(
        PageRepository $pageRepository,
        PageCategoryRepository $pageCategoryRepository,
        SysAnnouncementRepository $sysAnnouncementRepository,
        AccessUrlHelper $accessUrlHelper
    ) {
        $this->pageRepository = $pageRepository;
        $this->pageCategoryRepository = $pageCategoryRepository;
        $this->sysAnnouncementRepository = $sysAnnouncementRepository;
        $this->accessUrlHelper = $accessUrlHelper;
    }

    public function createDefaultPages(User $user, AccessUrl $url, string $locale): bool
    {
        $categories = $this->pageCategoryRepository->findAll();

        if (!empty($categories)) {
            return false;
        }

        $category = (new PageCategory())
            ->setTitle('home')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($category);

        $indexCategory = (new PageCategory())
            ->setTitle('index')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($indexCategory);

        $faqCategory = (new PageCategory())
            ->setTitle('faq')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($faqCategory);

        $demoCategory = (new PageCategory())
            ->setTitle('demo')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($demoCategory);

        $page = (new Page())
            ->setTitle('Welcome')
            ->setContent('Welcome to Chamilo')
            ->setCategory($category)
            ->setCreator($user)
            ->setLocale($locale)
            ->setEnabled(true)
            ->setUrl($url)
        ;
        $this->pageRepository->update($page);

        $indexPage = (new Page())
            ->setTitle('Welcome')
            ->setContent('<img src="/img/document/images/mr_chamilo/svg/teaching.svg" />')
            ->setCategory($indexCategory)
            ->setCreator($user)
            ->setLocale($locale)
            ->setEnabled(true)
            ->setUrl($url)
        ;
        $this->pageRepository->update($indexPage);

        $footerPublicCategory = (new PageCategory())
            ->setTitle('footer_public')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($footerPublicCategory);

        $footerPrivateCategory = (new PageCategory())
            ->setTitle('footer_private')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($footerPrivateCategory);

        $menuLinksCategory = (new PageCategory())
            ->setTitle('menu_links')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($menuLinksCategory);

        // Categories for extra content in admin blocks.
        foreach (PageCategory::ADMIN_BLOCKS_CATEGORIES as $nameBlock) {
            $usersAdminBlock = (new PageCategory())
                ->setTitle($nameBlock)
                ->setType('grid')
                ->setCreator($user)
            ;
            $this->pageCategoryRepository->update($usersAdminBlock);
        }

        $publicCategory = (new PageCategory())
            ->setTitle('public')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($publicCategory);

        $introductionCategory = (new PageCategory())
            ->setTitle('introduction')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($introductionCategory);

        return true;
    }

    /**
     * Checks if a document file URL is effectively exposed through a visible system announcement.
     *
     * This centralizes the logic used by different parts of the platform (e.g. voters, controllers)
     * to decide if a file coming from personal files can be considered "public" because it is
     * embedded inside a system announcement that is visible to the current user.
     *
     * @param string             $pathInfo   Full request path (e.g. /r/document/files/{uuid}/view)
     * @param string|null        $identifier File identifier extracted from the URL (usually a UUID)
     * @param UserInterface|null $user       Current user, or null to behave as anonymous
     * @param string             $locale     Current locale used to fetch announcements
     */
    public function isFilePathExposedByVisibleAnnouncement(
        string $pathInfo,
        ?string $identifier,
        ?UserInterface $user,
        string $locale
    ): bool {
        // Only relax security for the document file viewer route.
        if ('' === $pathInfo || !str_contains($pathInfo, '/r/document/files/')) {
            return false;
        }

        // Normalize user: if no authenticated user is provided, behave as anonymous.
        if (null === $user) {
            $anon = new User();
            $anon->setRoles(['ROLE_ANONYMOUS']);
            $user = $anon;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();

        // Fetch announcements that are visible for the given user, URL and locale.
        $announcements = $this->sysAnnouncementRepository->getAnnouncements(
            $user,
            $accessUrl,
            $locale
        );

        foreach ($announcements as $item) {
            $content = '';

            if (\is_array($item)) {
                $content = (string) ($item['content'] ?? '');
            } elseif (\is_object($item) && method_exists($item, 'getContent')) {
                $content = (string) $item->getContent();
            }

            if ('' === $content) {
                continue;
            }

            // Check if the announcement HTML contains the viewer path or the identifier.
            if (
                str_contains($content, $pathInfo)
                || ($identifier && str_contains($content, $identifier))
            ) {
                return true;
            }
        }

        return false;
    }
}
