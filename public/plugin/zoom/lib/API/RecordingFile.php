<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class RecordingFile. A video, audio or text file, part of a past meeting instance recording.
 *
 * @see RecordingMeeting
 */
class RecordingFile
{
    use JsonDeserializableTrait;

    /** @var string The recording file ID. Included in the response of general query. */
    public $id;

    /** @var string The meeting ID. */
    public $meeting_id;

    /** @var string The recording start time. */
    public $recording_start;

    /** @var string The recording end time. Response in general query. */
    public $recording_end;

    /** @var string The recording file type. The value of this field could be one of the following:<br>
     *  `MP4`: Video file of the recording.<br>
     *  `M4A` Audio-only file of the recording.<br>
     *  `TIMELINE`: Timestamp file of the recording.
     * To get a timeline file, the "Add a timestamp to the recording" setting must be enabled in the recording settings
     * (https://support.zoom.us/hc/en-us/articles/203741855-Cloud-recording#h_3f14c3a4-d16b-4a3c-bbe5-ef7d24500048).
     * The time will display in the host's timezone, set on their Zoom profile.
     *  `TRANSCRIPT`: Transcription file of the recording.
     *  `CHAT`: A TXT file containing in-meeting chat messages that were sent during the meeting.
     *  `CC`: File containing closed captions of the recording.
     * A recording file object with file type of either `CC` or `TIMELINE` **does not have** the following properties:
     * `id`, `status`, `file_size`, `recording_type`, and `play_url`.
     */
    public $file_type;

    /** @var int The recording file size. */
    public $file_size;

    /** @var string The URL using which a recording file can be played. */
    public $play_url;

    /** @var string The URL using which the recording file can be downloaded.
     * To access a private or password protected cloud recording, you must use a [Zoom JWT App Type]
     * (https://marketplace.zoom.us/docs/guides/getting-started/app-types/create-jwt-app).
     * Use the generated JWT token as the value of the `access_token` query parameter
     * and include this query parameter at the end of the URL as shown in the example.
     * Example: `https://api.zoom.us/recording/download/{{ Download Path }}?access_token={{ JWT Token }}`
     */
    public $download_url;

    /** @var string The recording status. "completed". */
    public $status;

    /** @var string The time at which recording was deleted. Returned in the response only for trash query. */
    public $deleted_time;

    /** @var string The recording type. The value of this field can be one of the following:
     * `shared_screen_with_speaker_view(CC)`
     * `shared_screen_with_speaker_view`
     * `shared_screen_with_gallery_view`
     * `speaker_view`
     * `gallery_view`
     * `shared_screen`
     * `audio_only`
     * `audio_transcript`
     * `chat_file`
     * `TIMELINE`
     */
    public $recording_type;

    /**
     * Builds the recording file download URL with the access_token query parameter.
     *
     * @see RecordingFile::$download_url
     *
     * @param string $token
     *
     * @return string full URL
     */
    public function getFullDownloadURL($token)
    {
        return $this->download_url.'?access_token='.$token;
    }

    /**
     * Deletes the file.
     *
     * @throws Exception
     */
    public function delete()
    {
        Client::getInstance()->send(
            'DELETE',
            "/meetings/$this->meeting_id/recordings/$this->id",
            ['action' => 'delete']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        throw new Exception("No such array property $propertyName");
    }
}
