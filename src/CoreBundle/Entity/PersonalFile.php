<?php

declare (strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use Chamilo\CoreBundle\Controller\Api\CreatePersonalFileAction;
use Chamilo\CoreBundle\Controller\Api\UpdatePersonalFileAction;
use Chamilo\CoreBundle\Entity\Listener\ResourceListener;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
/**
 */
#[ApiResource(operations: [new Put(controller: UpdatePersonalFileAction::class, deserialize: false, security: 'is_granted(\'EDIT\', object.resourceNode)'), new Get(security: 'is_granted(\'VIEW\', object.resourceNode)'), new Delete(security: 'is_granted(\'DELETE\', object.resourceNode)'), new Post(controller: CreatePersonalFileAction::class, deserialize: false, security: 'is_granted(\'ROLE_USER\')', validationContext: ['groups' => ['Default', 'media_object_create', 'personal_file:write']], openapiContext: ['requestBody' => ['content' => ['multipart/form-data' => ['schema' => ['type' => 'object', 'properties' => ['title' => ['type' => 'string'], 'comment' => ['type' => 'string'], 'contentFile' => ['type' => 'string'], 'uploadFile' => ['type' => 'string', 'format' => 'binary'], 'parentResourceNodeId' => ['type' => 'integer'], 'resourceLinkList' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['visibility' => ['type' => 'integer'], 'c_id' => ['type' => 'integer'], 'session_id' => ['type' => 'integer']]]]]]]]]]), new GetCollection(security: 'is_granted(\'ROLE_USER\')')], normalizationContext: ['groups' => ['personal_file:read', 'resource_node:read']], denormalizationContext: ['groups' => ['personal_file:write']])]
#[ORM\Table(name: 'personal_file')]
#[ORM\EntityListeners([ResourceListener::class])]
#[ORM\Entity(repositoryClass: PersonalFileRepository::class)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['title' => 'partial', 'resourceNode.parent' => 'exact'])]
#[ApiFilter(filterClass: PropertyFilter::class)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['id', 'resourceNode.title', 'resourceNode.createdAt', 'resourceNode.resourceFile.size', 'resourceNode.updatedAt'])]
class PersonalFile extends AbstractResource implements ResourceInterface, \Stringable
{
    use TimestampableEntity;
    #[Groups(['personal_file:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;
    #[Assert\NotBlank]
    #[Groups(['personal_file:read'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;
    public function __construct()
    {
    }
    public function __toString() : string
    {
        return $this->getTitle();
    }
    public function getId() : int
    {
        return $this->id;
    }
    public function getTitle() : string
    {
        return $this->title;
    }
    public function setTitle(string $title) : self
    {
        $this->title = $title;
        return $this;
    }
    public function getResourceIdentifier() : int
    {
        return $this->getId();
    }
    public function getResourceName() : string
    {
        return $this->getTitle();
    }
    public function setResourceName(string $name) : self
    {
        return $this->setTitle($name);
    }
}
