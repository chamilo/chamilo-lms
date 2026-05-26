<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Event;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;

class CourseAccessCheckEvent extends AbstractEvent
{
    private bool $granted = true;
    private ?string $message = null;

    public function getUser(): ?User
    {
        return $this->data['user'] ?? null;
    }

    public function getCourse(): ?Course
    {
        return $this->data['course'] ?? null;
    }

    public function getSession(): ?Session
    {
        return $this->data['session'] ?? null;
    }

    public function deny(?string $message = null): void
    {
        $this->granted = false;
        $this->message = $message;
    }

    public function isGranted(): bool
    {
        return $this->granted;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
