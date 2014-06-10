<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CWikiDiscuss
 *
 * @ORM\Table(name="c_wiki_discuss")
 * @ORM\Entity
 */
class CWikiDiscuss
{
    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="publication_id", type="integer", nullable=false)
     */
    private $publicationId;

    /**
     * @var integer
     *
     * @ORM\Column(name="userc_id", type="integer", nullable=false)
     */
    private $usercId;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=false)
     */
    private $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="p_score", type="string", length=255, nullable=true)
     */
    private $pScore;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dtime", type="datetime", nullable=false)
     */
    private $dtime;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
