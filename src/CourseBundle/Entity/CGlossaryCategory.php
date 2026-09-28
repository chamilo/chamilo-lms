<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\Controller\Api\CreateCGlossaryCategoryAction;
use Chamilo\CoreBundle\Controller\Api\DeleteCGlossaryCategoryAction;
use Chamilo\CoreBundle\Controller\Api\GetGlossaryCategoryCollectionController;
use Chamilo\CoreBundle\Controller\Api\UpdateCGlossaryCategoryAction;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CourseBundle\Repository\CGlossaryCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'GlossaryCategory',
    operations: [
        new GetCollection(
            uriTemplate: '/glossary_categories',
            controller: GetGlossaryCategoryCollectionController::class,
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
            ],
        ),
        new Get(
            uriTemplate: '/glossary_categories/{iid}',
            security: "is_granted('VIEW', object.resourceNode)",
        ),
        new Post(
            uriTemplate: '/glossary_categories',
            controller: CreateCGlossaryCategoryAction::class,
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            deserialize: false,
        ),
        new Put(
            uriTemplate: '/glossary_categories/{iid}',
            controller: UpdateCGlossaryCategoryAction::class,
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false,
        ),
        new Delete(
            uriTemplate: '/glossary_categories/{iid}',
            controller: DeleteCGlossaryCategoryAction::class,
            security: "is_granted('DELETE', object.resourceNode)",
            deserialize: false,
            write: false,
        ),
    ],
    normalizationContext: ['groups' => ['glossary_category:read', 'resource_node:read']],
)]
#[ORM\Table(name: 'c_glossary_category')]
#[ORM\Entity(repositoryClass: CGlossaryCategoryRepository::class)]
class CGlossaryCategory extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[ApiProperty(identifier: true)]
    #[Groups(['glossary_category:read', 'glossary:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Groups(['glossary_category:read', 'glossary:read'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'text', nullable: false)]
    protected string $title = '';

    /**
     * @var Collection<int, CGlossary>
     */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: CGlossary::class)]
    protected Collection $terms;

    public function __construct()
    {
        $this->terms = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection<int, CGlossary>
     */
    public function getTerms(): Collection
    {
        return $this->terms;
    }

    public function getResourceIdentifier(): int|Uuid
    {
        return (int) $this->iid;
    }

    public function getResourceName(): string
    {
        return $this->title;
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
