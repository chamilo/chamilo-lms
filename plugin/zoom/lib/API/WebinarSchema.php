<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;
use stdClass;

class WebinarSchema
{
    use BaseMeetingTrait;
    use JsonDeserializableTrait;

    public const TYPE_WEBINAR = 5;
    public const TYPE_RECURRING_NO_FIXED_TIME = 6;
    public const TYPE_RECURRING_FIXED_TIME = 9;

    public $uuid;
    public $id;
    public $host_id;
    public $created_at;
    public $start_url;
    public $join_url;
    public $registration_url;
    public $password;
    /**
     * @var WebinarSettings
     */
    public $settings;
    public $registrants_confirmation_email;
    /**
     * @var array<int, TrackingField>
     */
    public $tracking_fields;
    public $recurrence;
    public $template_id;
    /**
     * @var array<int, Ocurrence>
     */
    public $ocurrences;

    protected function __construct()
    {
        $this->tracking_fields = [];
        $this->settings = new WebinarSettings();
        $this->ocurrences = [];
    }

    public function itemClass($propertyName): string
    {
        if ('tracking_fields' === $propertyName) {
            return TrackingField::class;
        }

        if ('ocurrences' === $propertyName) {
            return Ocurrence::class;
        }

        throw new Exception("no such array property $propertyName");
    }

    public static function fromTopicAndType($topic, $type = self::TYPE_WEBINAR): WebinarSchema
    {
        $instance = new static();
        $instance->topic = $topic;
        $instance->type = $type;

        return $instance;
    }

    /**
     * @throws Exception
     */
    public function create($userId = null): WebinarSchema
    {
        $client = Client::getInstance();

        $userId = empty($userId) ? 'me' : $userId;

        return self::fromJson(
            $client->send('POST', "users/$userId/webinars", [], $this)
        );
    }

    /**
     * @throws Exception
     */
    public function update(): void
    {
        Client::getInstance()->send('PATCH', 'webinars/'.$this->id, [], $this);
    }

    /**
     * @throws Exception
     */
    public function delete()
    {
        Client::getInstance()->send('DELETE', "webinars/$this->id");
    }

    /**
     * @throws Exception
     */
    public function addRegistrant(RegistrantSchema $registrant, string $occurrenceIds = ''): CreatedRegistration
    {
        return CreatedRegistration::fromJson(
            Client::getInstance()->send(
                'POST',
                "webinars/$this->id/registrants",
                empty($occurrenceIds) ? [] : ['occurrence_ids' => $occurrenceIds],
                $registrant
            )
        );
    }

    /**
     * @throws Exception
     */
    public function removeRegistrants(array $registrants, string $occurrenceIds = '')
    {
        if (empty($registrants)) {
            return;
        }

        $requestBody = new stdClass();
        $requestBody->action = 'cancel';
        $requestBody->registrants = $registrants;

        Client::getInstance()->send(
            'PUT',
            "webinars/$this->id/registrants/status",
            empty($occurrenceIds) ? [] : ['occurrence_ids' => $occurrenceIds],
            $requestBody
        );
    }
}
