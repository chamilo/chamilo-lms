<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

class MeetingRegistrant
{
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
    public $custom_question;
}
