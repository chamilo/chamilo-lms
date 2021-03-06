<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Traits\TimestampableAgoTrait;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Traits\NestedSetEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ApiResource(
 *      attributes={"security"="is_granted('ROLE_ADMIN')"},
 *      normalizationContext={"groups"={"comment:read"}}
 * ).
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @ORM\Table(name="resource_comment")
 */
class ResourceComment
{
    use TimestampableTypedEntity;
    use TimestampableAgoTrait;
    use NestedSetEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"comment:read"})
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\ResourceNode", inversedBy="comments")
     * @ORM\JoinColumn(name="resource_node_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected ResourceNode $resourceNode;

    /**
     * @Groups({"comment:read"})
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected User $author;

    /**
     * @Groups({"comment:read"})
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="content", type="string", nullable=false)
     */
    protected string $content;

    /**
     * @Gedmo\TreeParent
     *
     * @ORM\ManyToOne(
     *     targetEntity="ResourceComment",
     *     inversedBy="children"
     * )
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(onDelete="CASCADE")
     * })
     */
    protected ?ResourceComment $parent = null;

    /**
     * @Groups({"comment:read"})
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected DateTime $createdAt;

    /**
     * @Groups({"comment:read"})
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    protected DateTime $updatedAt;

    /**
     * @var ArrayCollection|ResourceComment[]
     *
     * @ORM\OneToMany(
     *     targetEntity="ResourceComment",
     *     mappedBy="parent"
     * )
     * @ORM\OrderBy({"id"="ASC"})
     */
    protected Collection $children;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->content = '';
        $this->children = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getResourceNode()
    {
        return $this->resourceNode;
    }

    public function setResourceNode(ResourceNode $resourceNode): self
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function setChildren($children): self
    {
        $this->children = $children;

        return $this;
    }
}
