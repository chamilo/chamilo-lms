<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CDropboxFeedback
 *
 * @ORM\Table(name="c_dropbox_feedback", indexes={@ORM\Index(name="file_id", columns={"file_id"}), @ORM\Index(name="author_user_id", columns={"author_user_id"})})
 * @ORM\Entity
 */
class CDropboxFeedback
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
     * @ORM\Column(name="feedback_id", type="integer", nullable=false)
     */
    private $feedbackId;

    /**
     * @var integer
     *
     * @ORM\Column(name="file_id", type="integer", nullable=false)
     */
    private $fileId;

    /**
     * @var integer
     *
     * @ORM\Column(name="author_user_id", type="integer", nullable=false)
     */
    private $authorUserId;

    /**
     * @var string
     *
     * @ORM\Column(name="feedback", type="text", nullable=false)
     */
    private $feedback;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="feedback_date", type="datetime", nullable=false)
     */
    private $feedbackDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
