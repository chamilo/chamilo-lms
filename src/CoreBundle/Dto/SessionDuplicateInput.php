<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Dto;

use DateTime;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Body of POST /api/sessions/{id}/duplicate, the port of the 1.11.x
 * create_session_from_model webservice.
 */
final class SessionDuplicateInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[Groups(['session:duplicate'])]
    public string $title = '';

    /**
     * Applied to the access, display and coach access start dates, as the legacy webservice did.
     */
    #[Assert\NotNull]
    #[Groups(['session:duplicate'])]
    public ?DateTime $startDate = null;

    /**
     * Applied to the access, display and coach access end dates, as the legacy webservice did.
     */
    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(propertyPath: 'startDate')]
    #[Groups(['session:duplicate'])]
    public ?DateTime $endDate = null;

    /**
     * Session extra field values keyed by variable, applied over the model session's own values.
     *
     * @var array<string, bool|float|int|string|null>
     */
    #[Assert\All([new Assert\Type('scalar')])]
    #[Groups(['session:duplicate'])]
    public array $extraFields = [];

    /**
     * Also duplicate the session-specific course content (documents, learning paths, tests, agenda...).
     */
    #[Groups(['session:duplicate'])]
    public bool $copySessionContent = false;
}
