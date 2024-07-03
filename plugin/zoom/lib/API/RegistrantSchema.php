<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

abstract class RegistrantSchema
{
    use JsonDeserializableTrait;

    /** @var string */
    public $email;

    /**
     * @var string
     */
    public $status;

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
     * @var string
     */
    public $language;

    /**
     * MeetingRegistrant constructor.
     */
    public function __construct()
    {
        $this->status = 'approved';
        $this->custom_questions = [];
    }

    public static function fromEmailAndFirstName(string $email, string $firstName, string $lastName = null): RegistrantSchema
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
    public function itemClass($propertyName): string
    {
        if ('custom_questions' === $propertyName) {
            return CustomQuestion::class;
        }
        throw new Exception("no such array property $propertyName");
    }
}
