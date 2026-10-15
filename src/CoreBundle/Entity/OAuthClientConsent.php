<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\OAuthClientConsentRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Durable record that a user approved an OAuth client at least once.
 *
 * Token/code rows are intentionally pruned by the OAuth garbage collector;
 * this table is not, so the consent screen can keep distinguishing a new app
 * from one the user has approved before.
 */
#[ORM\Table(name: 'oauth_client_consent')]
#[ORM\UniqueConstraint(name: 'uniq_oauth_client_consent_user_client', columns: ['user_id', 'client_id'])]
#[ORM\Index(name: 'idx_oauth_client_consent_client_granted', columns: ['client_id', 'first_granted_at'])]
#[ORM\Entity(repositoryClass: OAuthClientConsentRepository::class)]
class OAuthClientConsent
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: OAuthClient::class)]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected OAuthClient $client;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\Column(name: 'first_granted_at', type: 'datetime', nullable: false)]
    protected DateTime $firstGrantedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): OAuthClient
    {
        return $this->client;
    }

    public function setClient(OAuthClient $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getFirstGrantedAt(): DateTime
    {
        return $this->firstGrantedAt;
    }

    public function setFirstGrantedAt(DateTime $firstGrantedAt): self
    {
        $this->firstGrantedAt = $firstGrantedAt;

        return $this;
    }
}
