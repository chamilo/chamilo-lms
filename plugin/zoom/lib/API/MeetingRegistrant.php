<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class MeetingRegistrant.
 * Structure of the information to send the server in order to register someone to a meeting.
 */
class MeetingRegistrant
{
    use JsonDeserializableTrait;

    /** @var string */
    public $email;

    /** @var string */
    public $first_name;

    /** @var string */
    public $last_name;

    /** @var string */
    public $address;

    /** @var string */
    public $city;

    /** @var string */
    public $country;

    /** @var string */
    public $zip;

    /** @var string */
    public $state;

    /** @var string */
    public $phone;

    /** @var string */
    public $industry;

    /** @var string */
    public $org;

    /** @var string */
    public $job_title;

    /** @var string */
    public $purchasing_time_frame;

    /** @var string */
    public $role_in_purchase_process;

    /** @var string */
    public $no_of_employees;

    /** @var string */
    public $comments;

    /** @var object[] title => value */
    public $custom_questions;

    /**
     * MeetingRegistrant constructor.
     */
    public function __construct()
    {
        $this->custom_questions = [];
    }

    /**
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     *
     * @return MeetingRegistrant
     */
    public static function fromEmailAndFirstName($email, $firstName, $lastName = null)
    {
        $instance = new static();
        $instance->first_name = $firstName;
        $instance->email = $email;
        if (null !== $lastName) {
            $instance->last_name = $lastName;
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        if ('custom_questions' === $propertyName) {
            return CustomQuestion::class;
        }
        throw new Exception("no such array property $propertyName");
    }
}
