<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\UserRemoteService;

use Doctrine\ORM\Mapping as ORM;
use Exception;

#[ORM\Table(name: 'plugin_user_remote_service')]
#[ORM\Entity]
class UserRemoteService
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title = '';

    #[ORM\Column(name: 'url', type: 'string', length: 255, nullable: false)]
    protected string $url = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
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

    public function getURL(): string
    {
        return $this->url;
    }

    public function setURL(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getAccessURL(string $pluginName): string
    {
        return api_get_path(WEB_PLUGIN_PATH).$pluginName.'/redirect.php?serviceId='.(int) $this->getId();
    }

    /**
     * Returns a user-specific URL with username and hash parameters.
     *
     * @throws Exception
     */
    public function getCustomUserURL(string $username, int $userId, string $salt): string
    {
        $hash = password_hash($salt.$userId, PASSWORD_BCRYPT);

        if (false === $hash) {
            throw new Exception('Hash generation failed');
        }

        return sprintf(
            '%s%s%s',
            $this->url,
            false === strpos($this->url, '?') ? '?' : '&',
            http_build_query(
                [
                    'username' => $username,
                    'hash' => $hash,
                ]
            )
        );
    }

    /**
     * Returns a user-specific URL with uid and hash parameters.
     *
     * @throws Exception
     */
    public function getCustomUserRedirectURL(int $userId, string $salt): string
    {
        $hash = password_hash($salt.$userId, PASSWORD_BCRYPT);

        if (false === $hash) {
            throw new Exception('Hash generation failed');
        }

        return sprintf(
            '%s%s%s',
            $this->url,
            false === strpos($this->url, '?') ? '?' : '&',
            http_build_query(
                [
                    'uid' => $userId,
                    'hash' => $hash,
                ]
            )
        );
    }
}
