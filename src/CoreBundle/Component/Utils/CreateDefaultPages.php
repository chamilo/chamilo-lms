<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Utils;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Page;
use Chamilo\CoreBundle\Entity\PageCategory;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\PageCategoryRepository;
use Chamilo\CoreBundle\Repository\PageRepository;

class CreateDefaultPages
{
    protected PageRepository $pageRepository;
    protected PageCategoryRepository $pageCategoryRepository;

    public function __construct(PageRepository $pageRepository, PageCategoryRepository $pageCategoryRepository)
    {
        $this->pageRepository = $pageRepository;
        $this->pageCategoryRepository = $pageCategoryRepository;
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

        $indexCategory = (new PageCategory())
            ->setTitle('faq')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($indexCategory);

        $indexCategory = (new PageCategory())
            ->setTitle('demo')
            ->setType('grid')
            ->setCreator($user)
        ;
        $this->pageCategoryRepository->update($indexCategory);

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

        // Categories for extra content in admin blocks

        foreach (self::getCategoriesForAdminBlocks() as $nameBlock) {
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

    public static function getCategoriesForAdminBlocks(): array
    {
        return [
            'block-admin-users',
            'block-admin-courses',
            'block-admin-sessions',
            'block-admin-gradebook',
            'block-admin-skills',
            'block-admin-privacy',
            'block-admin-settings',
            'block-admin-platform',
            'block-admin-chamilo',
        ];
    }
}
