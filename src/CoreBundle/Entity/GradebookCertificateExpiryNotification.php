<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Enums\CertificateExpiryNotificationType;
use Chamilo\CoreBundle\Repository\GradebookCertificateExpiryNotificationRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * One reminder sent to a learner about a certificate's expiry. `expiryDateAtSend`
 * is the dedup key alongside `certificate` and `notificationType`: it stores the
 * certificate's expiry date at the moment the reminder went out, so a later
 * change to the expiry date (a teacher editing it) makes the certificate
 * eligible for a fresh reminder without any extra bookkeeping.
 */
#[ORM\Table(name: 'gradebook_certificate_expiry_notification')]
#[ORM\Index(columns: ['certificate_id', 'notification_type'], name: 'idx_gce_notification_cert_type')]
#[ORM\Index(columns: ['sent_at'], name: 'idx_gce_notification_sent_at')]
#[ORM\Entity(repositoryClass: GradebookCertificateExpiryNotificationRepository::class)]
class GradebookCertificateExpiryNotification
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GradebookCertificate::class)]
    #[ORM\JoinColumn(name: 'certificate_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected GradebookCertificate $certificate;

    #[ORM\Column(name: 'notification_type', type: 'string', length: 32, nullable: false, enumType: CertificateExpiryNotificationType::class)]
    protected CertificateExpiryNotificationType $notificationType;

    #[ORM\Column(name: 'expiry_date_at_send', type: 'date', nullable: false)]
    protected DateTime $expiryDateAtSend;

    #[ORM\Column(name: 'sent_at', type: 'datetime', nullable: false)]
    protected DateTime $sentAt;

    /**
     * Who triggered the send. Null means it was sent by the cron command.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sent_by_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $sentBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCertificate(): GradebookCertificate
    {
        return $this->certificate;
    }

    public function setCertificate(GradebookCertificate $certificate): self
    {
        $this->certificate = $certificate;

        return $this;
    }

    public function getNotificationType(): CertificateExpiryNotificationType
    {
        return $this->notificationType;
    }

    public function setNotificationType(CertificateExpiryNotificationType $notificationType): self
    {
        $this->notificationType = $notificationType;

        return $this;
    }

    public function getExpiryDateAtSend(): DateTime
    {
        return $this->expiryDateAtSend;
    }

    public function setExpiryDateAtSend(DateTime $expiryDateAtSend): self
    {
        $this->expiryDateAtSend = $expiryDateAtSend;

        return $this;
    }

    public function getSentAt(): DateTime
    {
        return $this->sentAt;
    }

    public function setSentAt(DateTime $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getSentBy(): ?User
    {
        return $this->sentBy;
    }

    public function setSentBy(?User $sentBy): self
    {
        $this->sentBy = $sentBy;

        return $this;
    }
}
