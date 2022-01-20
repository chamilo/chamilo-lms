<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

class QuestionAndAnswer
{
    use JsonDeserializableTrait;

    public $enable;
    public $allow_anonymous_questions;
    public $answer_questions;
    public $attendees_can_upvote;
    public $attendees_can_comment;

    public function itemClass($propertyName)
    {
        throw new Exception("No such array property $propertyName");
    }
}
