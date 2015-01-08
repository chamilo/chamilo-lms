<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\NotebookBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use APY\DataGridBundle\Grid\Mapping as GRID;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;

/**
 * CNotebook
 *
 * @ORM\Table(name="c_notebook")
 * @ORM\Entity(repositoryClass="Chamilo\NotebookBundle\Entity\NotebookRepository")
 * @GRID\Source(columns="id, name")
 *
 */
class CNotebook extends AbstractResource
{
    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=false, unique=false)
     */
    protected $description;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Set description
     *
     * @param string $description
     * @return CNotebook
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
