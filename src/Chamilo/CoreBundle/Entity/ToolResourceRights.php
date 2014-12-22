<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sonata\AdminBundle\Security\Acl\Permission\AdminPermissionMap;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * Tool
 *
 * @ORM\Table(name="tool_resource_rights")
 * @ORM\Entity
 */
class ToolResourceRights
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=255, nullable=false)
     */
    protected $role;

    /**
     * @var string
     *
     * @ORM\Column(name="mask", type="integer", nullable=false)
     */
    protected $mask;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Tool", inversedBy="toolResourceRights", cascade={"persist"})
     * @ORM\JoinColumn(name="tool_id", referencedColumnName="id")
     **/
    protected $tool;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getMask();
    }

    /**
     * @return Tool
     */
    public function getTool()
    {
        return $this->tool;
    }

    /**
     * @param Tool $tool
     *
     * @return $this
     */
    public function setTool($tool)
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $role
     *
     * @return $this
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return int
     */
    public function getMask()
    {
        return $this->mask;
    }

    /**
     * @param mixed $mask
     *
     * @return $this
     */
    public function setMask($mask)
    {
        $this->mask = $mask;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public static function getDefaultRoles()
    {
        return array(
            'ROLE_STUDENT' => 'student',
            'ROLE_TEACHER' => 'teacher'
        );
    }

    /**
     * @return array
     */
    public static function getMaskList()
    {
        $builder = new MaskBuilder();
        $builder
            ->add('view')
            ->add('edit')
        ;

        $readerMask = $builder->get();

        $builder = new MaskBuilder();
        $builder
            ->add('view')
            ->add('edit')
        ;
        $editorMask = $builder->get();

        $builder = new MaskBuilder();
        $builder
            ->add('view')
            ->add('edit')
            ->add('delete')
        ;
        $ownerMask = $builder->get();

        return array(
            $readerMask => 'reader',
            $editorMask => 'editor',
            $ownerMask => 'owner'
        );
    }
}
