<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class RecordingMeeting.
 * A meeting instance can be recorded, hence creating an instance of this class.
 * Contains a list of recording files.
 *
 * @see PastMeeting
 * @see RecordingFile
 */
class RecordingMeeting
{
    use JsonDeserializableTrait;

    /** @var string Unique Meeting Identifier. Each instance of the meeting will have its own UUID. */
    public $uuid;

    /** @var string Meeting ID - also known as the meeting number. */
    public $id;

    /** @var string Unique Identifier of the user account. */
    public $account_id;

    /** @var string ID of the user set as host of meeting. */
    public $host_id;

    /** @var string Meeting topic. */
    public $topic;

    /** @var int undocumented */
    public $type;

    /** @var string The time at which the meeting started. */
    public $start_time;

    /** @var string undocumented */
    public $timezone;

    /** @var int Meeting duration. */
    public $duration;

    /** @var string Total size of the recording. */
    public $total_size;

    /** @var string Number of recording files returned in the response of this API call. */
    public $recording_count;

    /** @var string undocumented */
    public $share_url;

    /** @var string */
    public $password;

    /** @var RecordingFile[] List of recording file. */
    public $recording_files;

    /**
     * RecordingMeeting constructor.
     */
    public function __construct()
    {
        $this->recording_files = [];
    }

    /**
     * Deletes the recording on the server.
     *
     * @throws Exception
     */
    public function delete()
    {
        Client::getInstance()->send(
            'DELETE',
            'meetings/'.htmlentities($this->uuid).'/recordings',
            ['action' => 'delete']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        if ('recording_files' === $propertyName) {
            return RecordingFile::class;
        }
        throw new Exception("No such array property $propertyName");
    }
}
