<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class LanguageRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(LanguageRepository::class);
        $defaultCount = $repo->count([]);
        $language = (new Language())
            ->setAvailable(true)
            ->setOriginalName('language')
            ->setEnglishName('language')
            ->setIsocode('lan')
        ;
        $this->assertHasNoEntityViolations($language);
        $em->persist($language);
        $em->flush();

        $this->assertSame('language', $language->getOriginalName());
        $this->assertSame('language', $language->getEnglishName());
        $this->assertSame('lan', $language->getIsocode());
        $this->assertIsInt($language->getId());
        $this->assertSame($defaultCount + 1, $repo->count([]));
    }
}
