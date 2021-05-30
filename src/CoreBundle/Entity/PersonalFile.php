<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Chamilo\CoreBundle\Controller\Api\CreatePersonalFileAction;
use Chamilo\CoreBundle\Controller\Api\UpdatePersonalFileAction;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"personal_file:read", "resource_node:read"}},
 *     denormalizationContext={"groups"={"personal_file:write"}},
 *     itemOperations={
 *         "put"={
 *             "controller"=UpdatePersonalFileAction::class,
 *             "deserialize"=false,
 *             "security"="is_granted('EDIT', object.resourceNode)",
 *         },
 *         "get"={
 *             "security"="is_granted('VIEW', object.resourceNode)",
 *         },
 *         "delete"={
 *             "security"="is_granted('DELETE', object.resourceNode)",
 *         },
 *     },
 *     collectionOperations={
 *         "post"={
 *             "controller"=CreatePersonalFileAction::class,
 *             "deserialize"=false,
 *             "security"="is_granted('ROLE_USER')",
 *             "validation_groups"={"Default", "media_object_create", "personal_file:write"},
 *             "openapi_context"={
 *                 "requestBody"={
 *                     "content"={
 *                         "multipart/form-data"={
 *                             "schema"={
 *                                 "type"="object",
 *                                 "properties"={
 *                                     "title"={
 *                                         "type"="string",
 *                                     },
 *                                     "comment"={
 *                                         "type"="string",
 *                                     },
 *                                     "contentFile"={
 *                                         "type"="string",
 *                                     },
 *                                     "uploadFile"={
 *                                         "type"="string",
 *                                         "format"="binary"
 *                                     },
 *                                     "parentResourceNodeId"={
 *                                         "type"="integer",
 *                                     },
 *                                     "resourceLinkList"={
 *                                         "type"="array",
 *                                         "items"={
 *                                             "type"="object",
 *                                             "properties"={
 *                                                 "visibility"={
 *                                                     "type"="integer",
 *                                                 },
 *                                                 "c_id"={
 *                                                     "type"="integer",
 *                                                 },
 *                                                 "session_id"={
 *                                                     "type"="integer",
 *                                                 },
 *                                             }
 *                                         }
 *                                     },
 *                                 }
 *                             }
 *                         }
 *                     }
 *                 }
 *             }
 *         },
 *         "get"={
 *             "security"="is_granted('ROLE_USER')",
 *         },
 *     },
 * )
 * @ApiFilter(SearchFilter::class, properties={"title":"partial", "resourceNode.parent":"exact"})
 * @ApiFilter(PropertyFilter::class)
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={
 *         "id",
 *         "resourceNode.title",
 *         "resourceNode.createdAt",
 *         "resourceNode.resourceFile.size",
 *         "resourceNode.updatedAt"
 *     }
 * )
 *
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\ResourceListener"})
 * @ORM\Table(name="personal_file")
 * @ORM\Entity
 */
class PersonalFile extends AbstractResource implements ResourceInterface
{
    use TimestampableEntity;

    /**
     * @Groups({"personal_file:read"})
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
     * @Groups({"personal_file:read"})
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected string $title;

    public function __construct()
    {
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getResourceIdentifier(): int
    {
        return $this->getId();
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
