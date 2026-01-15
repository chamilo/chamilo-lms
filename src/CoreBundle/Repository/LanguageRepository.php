<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @extends ServiceEntityRepository<Language>
 */
class LanguageRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ParameterBagInterface $parameterBag,
        private readonly SettingsManager $settingsManager
    ) {
        parent::__construct($registry, Language::class);
    }

    public function getAllAvailable($excludeDefaultLocale = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('l');

        $qb->where($qb->expr()->eq('l.available', ':avail'))
            ->setParameter('avail', true)
            ->orderBy('l.englishName', 'ASC')
        ;

        if ($excludeDefaultLocale) {
            $qb->andWhere($qb->expr()->neq('l.isocode', ':iso_base'))
                ->setParameter('iso_base', $this->parameterBag->get('locale'))
            ;
        }

        return $qb;
    }

    /**
     * @return array<string,string> [isocode => original_name]
     */
    public function getAllAvailableToArray(
        bool $onlyActive = true,
        bool $includePlatformDefault = false
    ): array {
        $qb = $this->getAllAvailable();

        if (!$onlyActive) {
            $qb->resetDQLPart('where');
            $qb->orderBy('l.englishName', 'ASC');
        }

        /** @var Language[] $languages */
        $languages = $qb->getQuery()->getResult();

        $list = [];

        /** @var Language $language */
        foreach ($languages as $language) {
            $list[$language->getIsocode()] = $language->getOriginalName();
        }

        if ($includePlatformDefault) {
            $defaultIso = $this->getPlatformDefaultIso();
            if ($defaultIso && !isset($list[$defaultIso])) {
                $default = $this->findOneBy(['isocode' => $defaultIso]);
                if ($default instanceof Language) {
                    $list[$default->getIsocode()] = $default->getOriginalName();
                }
            }
        }

        return $list;
    }

    public function getPlatformDefaultIso(): ?string
    {
        $iso = trim($this->settingsManager->getSetting('language.platform_language', true));
        if ('' !== $iso) {
            return $iso;
        }

        $englishName = trim($this->settingsManager->getSetting('language.platformLanguage'));
        if ('' === $englishName) {
            return null;
        }

        $lang = $this->findOneBy(['englishName' => $englishName]);

        return $lang?->getIsocode();
    }

    /**
     * Convenience for forms: ordered by englishName, labels = original_name,
     * and includes platform default ISO if it is not active.
     *
     * @return array<string,string> [isocode => original_name]
     */
    public function getAllForSelectIncludePlatformDefault(): array
    {
        return $this->getAllAvailableToArray(true, true);
    }

    /**
     * Get all the sub languages that are made available by the admin.
     *
     * @return Collection|Language[]
     */
    public function findAllSubLanguages()
    {
        $qb = $this->createQueryBuilder('l');
        $qb->select('l')
            ->where(
                $qb->expr()->eq('l.available', true)
            )
            ->andWhere(
                $qb->expr()->isNotNull('l.parent')
            )
        ;

        return $qb->getQuery()->getResult();
    }

    public function findByIsoCode(string $isoCode): ?Language
    {
        $qb = $this->createQueryBuilder('l');
        $qb->where('l.isocode = :isoCode')
            ->setParameter('isoCode', $isoCode)
            ->setMaxResults(1)
        ;

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NonUniqueResultException|NoResultException) {
            return null;
        }
    }
}
