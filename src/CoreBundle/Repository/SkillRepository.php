<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelGradebook;
use Chamilo\CoreBundle\Entity\SkillRelSkill;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class SkillRepository extends ServiceEntityRepository
{
    private $assetRepository;

    public function __construct(ManagerRegistry $registry, AssetRepository $assetRepository)
    {
        parent::__construct($registry, Skill::class);
        $this->assetRepository = $assetRepository;
    }

    public function deleteAsset(Skill $skill): void
    {
        if ($skill->hasAsset()) {
            $asset = $skill->getAsset();
            $skill->setAsset(null);

            $this->getEntityManager()->persist($skill);
            $this->getEntityManager()->remove($asset);
            $this->getEntityManager()->flush();
        }
    }

    public function update(Skill $skill): void
    {
        $this->getEntityManager()->persist($skill);
        $this->getEntityManager()->flush();
    }

    public function delete(Skill $skill): void
    {
        $this->getEntityManager()->remove($skill);
        $this->getEntityManager()->flush();
    }

    /**
     * Get the last acquired skill by a user on course and/or session.
     */
    public function getLastByUser(User $user, ?Course $course = null, ?Session $session = null): ?Skill
    {
        $qb = $this->createQueryBuilder('s');

        $qb
            ->innerJoin(
                'ChamiloCoreBundle:SkillRelUser',
                'su',
                Join::WITH,
                's.id = su.skill'
            )
            ->where(
                $qb->expr()->eq('su.user', $user->getId())
            )
        ;

        if (null !== $course) {
            $qb->andWhere(
                $qb->expr()->eq('su.course', $course->getId())
            );
        }

        if (null !== $session) {
            $qb->andWhere(
                $qb->expr()->eq('su.session', $session->getId())
            );
        }

        $qb
            ->setMaxResults(1)
            ->orderBy('su.id', Criteria::DESC)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getAllSkills($loadUserData = false, $userId = null, $id = null, $parentId = null): array
    {
        $qb = $this->createQueryBuilder('s');

        // Filtrar por ID específico si se proporciona
        if (null !== $id) {
            $qb->andWhere('s.id = :id')
                ->setParameter('id', $id);
        }

        // Filtrar por habilidades relacionadas con un usuario específico si se proporciona
        if ($loadUserData && null !== $userId) {
            $qb->innerJoin('s.issuedSkills', 'isu')
                ->andWhere('isu.user = :userId')
                ->setParameter('userId', $userId);
        }

        // Filtrar por padre si se proporciona
        if (null !== $parentId) {
            $qb->innerJoin('s.skills', 'ss', 'WITH', 'ss.parent = :parentId')
                ->setParameter('parentId', $parentId);
        }

        // Ordenar por algún criterio si es necesario
        $qb->orderBy('s.id', 'ASC');

        $query = $qb->getQuery();
        $skills = $query->getResult();

        // Convertir cada Skill en un array de detalles
        $skillsWithDetails = array_map(function ($skill) {
            return $this->getSkillDetails($skill); // Asume que getSkillDetails está definido y funciona como se espera
        }, $skills);

        return $skillsWithDetails;
    }

    public function getSkillDetails(Skill $skill): array
    {
        $assetUrl = '/img/icons/64/badges-default.png';
        if ($skill->getAsset()) {
            $assetUrl = $this->assetRepository->getAssetUrl($skill->getAsset());
        }

        $skillDetails = [
            'id' => $skill->getId(),
            'title' => $this->translateName($skill->getTitle()),
            'shortCode' => $this->translateCode($skill->getShortCode()),
            'description' => $skill->getDescription(),
            'icon' => $assetUrl,
        ];
        $skillDetails['parents'] = $this->getSkillParents($skill);
        $skillDetails['gradebooks'] = $this->getGradebooksBySkill($skill);

        return $skillDetails;
    }

    public function getParentOptions(): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('s.id', 's.title')
            ->where('s.status = :status')
            ->setParameter('status', Skill::STATUS_ENABLED);

        return $qb->getQuery()->getArrayResult();
    }

    public function getGradebookOptions(): array
    {
        $gradebookRepo = $this->getEntityManager()->getRepository(GradebookCategory::class);
        $qb = $gradebookRepo->createQueryBuilder('g');
        $qb->select('g.id', 'g.title');

        return $qb->getQuery()->getArrayResult();
    }


    public function addSkill(array $params): ?Skill
    {
        $em = $this->getEntityManager();

        $skill = new Skill();
        $skill->setTitle($params['title'])
            ->setShortCode($params['short_code'] ?? '')
            ->setDescription($params['description'] ?? '')
            ->setCriteria($params['criteria'] ?? '')
            ->setAccessUrlId($params['access_url_id'])
            ->setIcon($params['icon'] ?? '');

        $em->persist($skill);

        // Relate with parent skills
        if (!empty($params['parent_id'])) {
            foreach ($params['parent_id'] as $parentId) {
                $parentSkill = $this->find($parentId);
                if ($parentSkill) {
                    $skillRelSkill = new SkillRelSkill();
                    $skillRelSkill->setSkill($skill)
                        ->setParent($parentSkill)
                        ->setLevel($params['level'] ?? 0)
                        ->setRelationType($params['relation_type'] ?? 0);

                    $em->persist($skillRelSkill);
                }
            }
        }

        // Relate with Gradebooks
        if (!empty($params['gradebook_id'])) {
            foreach ($params['gradebook_id'] as $gradebookId) {
                $gradebook = $em->getRepository(GradebookCategory::class)->find($gradebookId);
                if ($gradebook) {
                    $skillRelGradebook = new SkillRelGradebook();
                    $skillRelGradebook->setGradeBookCategory($gradebook)
                        ->setSkill($skill);

                    $em->persist($skillRelGradebook);
                }
            }
        }

        $em->flush();

        return $skill;
    }

    /**
     * Translates a skill name into the current user's language, if a translation is available.
     */
    private function translateName(string $name): string
    {
        $variable = ChamiloApi::getLanguageVar($name, 'Skill');
        return $GLOBALS[$variable] ?? $name;
    }

    /**
     * Translates a skill code into the current user's language, if a translation is available.
     *
     * This is useful for displaying skill codes in a user-friendly manner, especially when they have specific meanings or abbreviations that may not be immediately clear to all users.
     */
    private function translateCode(string $code): string
    {
        if (empty($code)) {
            return '';
        }

        $variable = ChamiloApi::getLanguageVar($code, 'SkillCode');
        return $GLOBALS[$variable] ?? $code;
    }


    /**
     * Retrieves the parent skills of a specific skill.
     */
    private function getSkillParents(Skill $skill): array
    {
        $parents = [];
        $currentSkill = $skill;

        // While the current skill has a parent, keep looking up the hierarchy
        while ($currentSkillRelSkill = $this->getEntityManager()->getRepository(SkillRelSkill::class)->findOneBy(['skill' => $currentSkill])) {
            $parentSkill = $currentSkillRelSkill->getParent();
            if ($parentSkill) {
                $parents[] = $parentSkill;
                $currentSkill = $parentSkill; // Move to the next level in the hierarchy
            } else {
                break; // No more parents
            }
        }

        return $parents;
    }


    /**
     * Fetches gradebook categories associated with a given skill.
     */
    private function getGradebooksBySkill(Skill $skill): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('g')
            ->from(GradebookCategory::class, 'g')
            ->innerJoin(SkillRelGradebook::class, 'sg', 'WITH', 'g.id = sg.gradeBookCategory')
            ->where('sg.skill = :skill')
            ->setParameter('skill', $skill);

        return $qb->getQuery()->getResult();
    }

}
