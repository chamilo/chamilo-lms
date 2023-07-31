<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\PluginBundle\Zoom\API\BaseMeetingTrait;
use Chamilo\PluginBundle\Zoom\API\JWTClient;
use Chamilo\PluginBundle\Zoom\API\MeetingInfoGet;
use Chamilo\PluginBundle\Zoom\API\MeetingRegistrant;
use Chamilo\PluginBundle\Zoom\API\MeetingSettings;
use Chamilo\PluginBundle\Zoom\API\RecordingFile;
use Chamilo\PluginBundle\Zoom\API\RecordingList;
use Chamilo\PluginBundle\Zoom\API\ServerToServerOAuthClient;
use Chamilo\PluginBundle\Zoom\API\WebinarRegistrantSchema;
use Chamilo\PluginBundle\Zoom\API\WebinarSchema;
use Chamilo\PluginBundle\Zoom\API\WebinarSettings;
use Chamilo\PluginBundle\Zoom\Meeting;
use Chamilo\PluginBundle\Zoom\MeetingActivity;
use Chamilo\PluginBundle\Zoom\MeetingRepository;
use Chamilo\PluginBundle\Zoom\Recording;
use Chamilo\PluginBundle\Zoom\RecordingRepository;
use Chamilo\PluginBundle\Zoom\Registrant;
use Chamilo\PluginBundle\Zoom\RegistrantRepository;
use Chamilo\PluginBundle\Zoom\Signature;
use Chamilo\PluginBundle\Zoom\Webinar;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;

/**
 * Class ZoomPlugin. Integrates Zoom meetings in courses.
 */
class ZoomPlugin extends Plugin
{
    public const RECORDING_TYPE_CLOUD = 'cloud';
    public const RECORDING_TYPE_LOCAL = 'local';
    public const RECORDING_TYPE_NONE = 'none';
    public const SETTING_ACCOUNT_ID = 'account_id';
    public const SETTING_CLIENT_ID = 'client_id';
    public const SETTING_CLIENT_SECRET = 'client_secret';
    public const SETTING_SECRET_TOKEN = 'secret_token';

    public $isCoursePlugin = true;

    /**
     * @var JWTClient
     */
    private $jwtClient;

    /**
     * ZoomPlugin constructor.
     * {@inheritdoc}
     * Initializes the API JWT client and the entity repositories.
     */
    public function __construct()
    {
        parent::__construct(
            '0.5',
            'Sébastien Ducoulombier, Julio Montoya, Angel Fernando Quiroz Campos',
            [
                'tool_enable' => 'boolean',
                'apiKey' => 'text',
                'apiSecret' => 'text',
                'verificationToken' => 'text',
                self::SETTING_ACCOUNT_ID => 'text',
                self::SETTING_CLIENT_ID => 'text',
                self::SETTING_CLIENT_SECRET => 'text',
                self::SETTING_SECRET_TOKEN => 'text',
                'enableParticipantRegistration' => 'boolean',
                'enableCloudRecording' => [
                    'type' => 'select',
                    'options' => [
                        self::RECORDING_TYPE_CLOUD => 'Cloud',
                        self::RECORDING_TYPE_LOCAL => 'Local',
                        self::RECORDING_TYPE_NONE => get_lang('None'),
                    ],
                ],
                'enableGlobalConference' => 'boolean',
                'globalConferenceAllowRoles' => [
                    'type' => 'select',
                    'options' => [
                        PLATFORM_ADMIN => get_lang('Administrator'),
                        COURSEMANAGER => get_lang('Teacher'),
                        STUDENT => get_lang('Student'),
                        STUDENT_BOSS => get_lang('StudentBoss'),
                        SESSIONADMIN => get_lang('SessionsAdmin'),
                    ],
                    'attributes' => ['multiple' => 'multiple'],
                ],
                'accountSelector' => 'text',
            ]
        );

        $this->isAdminPlugin = true;

        $accountId = $this->get(self::SETTING_ACCOUNT_ID);
        $clientId = $this->get(self::SETTING_CLIENT_ID);
        $clientSecret = $this->get(self::SETTING_CLIENT_SECRET);

        if (!empty($accountId) && !empty($clientId) && !empty($clientSecret)) {
            $this->jwtClient = new ServerToServerOAuthClient($accountId, $clientId, $clientSecret);
        } else {
            $this->jwtClient = new JWTClient($this->get('apiKey'), $this->get('apiSecret'));
        }
    }

    /**
     * Caches and returns an instance of this class.
     *
     * @return ZoomPlugin the instance to use
     */
    public static function create(): ZoomPlugin
    {
        static $instance = null;

        return $instance ? $instance : $instance = new self();
    }

    /**
     * @return bool
     */
    public static function currentUserCanJoinGlobalMeeting()
    {
        $user = api_get_user_entity(api_get_user_id());

        if (null === $user) {
            return false;
        }

        //return 'true' === api_get_plugin_setting('zoom', 'enableGlobalConference') && api_user_is_login();
        return
            'true' === api_get_plugin_setting('zoom', 'enableGlobalConference')
            && in_array(
                (api_is_platform_admin() ? PLATFORM_ADMIN : $user->getStatus()),
                (array) api_get_plugin_setting('zoom', 'globalConferenceAllowRoles')
            );
    }

    /**
     * @return array
     */
    public function getProfileBlockItems()
    {
        $elements = $this->meetingsToWhichCurrentUserIsRegisteredComingSoon();
        $addMeetingLink = false;
        if (self::currentUserCanJoinGlobalMeeting()) {
            $addMeetingLink = true;
        }

        if ($addMeetingLink) {
            $elements[$this->get_lang('Meetings')] = api_get_path(WEB_PLUGIN_PATH).'zoom/meetings.php';
        }

        $items = [];
        foreach ($elements as $title => $link) {
            $items[] = [
                'class' => 'video-conference',
                'icon' => Display::return_icon(
                    'bbb.png',
                    get_lang('VideoConference')
                ),
                'link' => $link,
                'title' => $title,
            ];
        }

        return $items;
    }

    /**
     * @return array [ $title => $link ]
     */
    public function meetingsToWhichCurrentUserIsRegisteredComingSoon()
    {
        $linkTemplate = api_get_path(WEB_PLUGIN_PATH).'zoom/join_meeting.php?meetingId=%s';
        $user = api_get_user_entity(api_get_user_id());
        $meetings = self::getRegistrantRepository()->meetingsComingSoonRegistrationsForUser($user);
        $items = [];
        foreach ($meetings as $registrant) {
            $meeting = $registrant->getMeeting();

            $items[sprintf(
                $this->get_lang('DateMeetingTitle'),
                $meeting->formattedStartTime,
                $meeting->getTopic()
            )] = sprintf($linkTemplate, $meeting->getMeetingId());
        }

        return $items;
    }

    /**
     * @return RegistrantRepository|EntityRepository
     */
    public static function getRegistrantRepository()
    {
        return Database::getManager()->getRepository(Registrant::class);
    }

    /**
     * Creates this plugin's related tables in the internal database.
     * Installs course fields in all courses.
     *
     * @throws ToolsException
     */
    public function install()
    {
        $schemaManager = Database::getManager()->getConnection()->getSchemaManager();

        $tablesExists = $schemaManager->tablesExist(
            [
                'plugin_zoom_meeting',
                'plugin_zoom_meeting_activity',
                'plugin_zoom_recording',
                'plugin_zoom_registrant',
                'plugin_zoom_signature',
            ]
        );

        if ($tablesExists) {
            return;
        }

        $em = Database::getManager();

        (new SchemaTool($em))->createSchema(
            [
                $em->getClassMetadata(Meeting::class),
                $em->getClassMetadata(Webinar::class),
                $em->getClassMetadata(MeetingActivity::class),
                $em->getClassMetadata(Recording::class),
                $em->getClassMetadata(Registrant::class),
                $em->getClassMetadata(Signature::class),
            ]
        );

        // Copy icons into the main/img/icons folder
        $iconName = 'zoom_meet';
        $iconsList = [
            '64/'.$iconName.'.png',
            '64/'.$iconName.'_na.png',
            '32/'.$iconName.'.png',
            '32/'.$iconName.'_na.png',
            '22/'.$iconName.'.png',
            '22/'.$iconName.'_na.png',
        ];
        $sourceDir = api_get_path(SYS_PLUGIN_PATH).'zoom/resources/img/';
        $destinationDir = api_get_path(SYS_CODE_PATH).'img/icons/';
        foreach ($iconsList as $icon) {
            $src = $sourceDir.$icon;
            $dest = $destinationDir.$icon;
            copy($src, $dest);
        }

        $this->install_course_fields_in_all_courses(true, 'zoom_meet.png');
    }

    /**
     * Drops this plugins' related tables from the internal database.
     * Uninstalls course fields in all courses().
     */
    public function uninstall()
    {
        $em = Database::getManager();

        (new SchemaTool($em))->dropSchema(
            [
                $em->getClassMetadata(Meeting::class),
                $em->getClassMetadata(Webinar::class),
                $em->getClassMetadata(MeetingActivity::class),
                $em->getClassMetadata(Recording::class),
                $em->getClassMetadata(Registrant::class),
                $em->getClassMetadata(Signature::class),
            ]
        );
        $this->uninstall_course_fields_in_all_courses();

        // Remove icons from the main/img/icons folder
        $iconName = 'zoom_meet';
        $iconsList = [
            '64/'.$iconName.'.png',
            '64/'.$iconName.'_na.png',
            '32/'.$iconName.'.png',
            '32/'.$iconName.'_na.png',
            '22/'.$iconName.'.png',
            '22/'.$iconName.'_na.png',
        ];
        $destinationDir = api_get_path(SYS_CODE_PATH).'img/icons/';
        foreach ($iconsList as $icon) {
            $dest = $destinationDir.$icon;
            if (is_file($dest)) {
                @unlink($dest);
            }
        }
    }

    /**
     * Generates the search form to include in the meeting list administration page.
     * The form has DatePickers 'start' and 'end' and Checkbox 'reloadRecordingLists'.
     *
     * @return FormValidator the form
     */
    public function getAdminSearchForm()
    {
        $form = new FormValidator('search');
        $form->addHeader($this->get_lang('SearchMeeting'));
        $form->addDatePicker('start', get_lang('StartDate'));
        $form->addDatePicker('end', get_lang('EndDate'));
        $form->addButtonSearch(get_lang('Search'));
        $oneMonth = new DateInterval('P1M');
        if ($form->validate()) {
            try {
                $start = new DateTime($form->getSubmitValue('start'));
            } catch (Exception $exception) {
                $start = new DateTime();
                $start->sub($oneMonth);
            }
            try {
                $end = new DateTime($form->getSubmitValue('end'));
            } catch (Exception $exception) {
                $end = new DateTime();
                $end->add($oneMonth);
            }
        } else {
            $start = new DateTime();
            $start->sub($oneMonth);
            $end = new DateTime();
            $end->add($oneMonth);
        }
        try {
            $form->setDefaults(
                [
                    'start' => $start->format('Y-m-d'),
                    'end' => $end->format('Y-m-d'),
                ]
            );
        } catch (Exception $exception) {
            error_log(join(':', [__FILE__, __LINE__, $exception]));
        }

        return $form;
    }

    /**
     * @throws Exception
     */
    public function getEditConferenceForm(Meeting $conference): FormValidator
    {
        $isWebinar = $conference instanceof Webinar;
        $requiresDateAndDuration = $conference->requiresDateAndDuration();

        /** @var BaseMeetingTrait $schema */
        $schema = $isWebinar ? $conference->getWebinarSchema() : $conference->getMeetingInfoGet();

        $form = new FormValidator('edit', 'post', $_SERVER['REQUEST_URI']);
        $form->addHeader(
            $isWebinar ? $this->get_lang('UpdateWebinar') : $this->get_lang('UpdateMeeting')
        );
        $form->addLabel(get_lang('Type'), $conference->typeName);
        if ($conference->getAccountEmail()) {
            $form->addLabel(
                $this->get_lang('AccountEmail'),
                $conference->getAccountEmail()
            );
        }
        $form->addText('topic', $this->get_lang('Topic'));

        if ($requiresDateAndDuration) {
            $startTimeDatePicker = $form->addDateTimePicker('startTime', get_lang('StartTime'));
            $durationNumeric = $form->addNumeric('duration', $this->get_lang('DurationInMinutes'));

            $form->setRequired($startTimeDatePicker);
            $form->setRequired($durationNumeric);
        }

        $form->addTextarea('agenda', get_lang('Agenda'), ['maxlength' => 2000]);
        $form->addCheckBox('sign_attendance', $this->get_lang('SignAttendance'), get_lang('Yes'));
        $form->addTextarea('reason_to_sign', $this->get_lang('ReasonToSign'), ['rows' => 5]);
        $form->addButtonUpdate(get_lang('Update'));

        if ($form->validate()) {
            $formValues = $form->exportValues();

            $em = Database::getManager();

            if ($requiresDateAndDuration) {
                $schema->start_time = (new DateTime($formValues['startTime']))->format(DATE_ATOM);
                $schema->timezone = date_default_timezone_get();
                $schema->duration = (int) $formValues['duration'];
            }

            $schema->topic = $formValues['topic'];
            $schema->agenda = $formValues['agenda'];

            $conference
                ->setSignAttendance(isset($formValues['sign_attendance']))
                ->setReasonToSignAttendance($formValues['reason_to_sign']);

            try {
                $schema->update();

                if ($isWebinar) {
                    $conference->setWebinarSchema($schema);
                } else {
                    $conference->setMeetingInfoGet($schema);
                }

                $em->persist($conference);
                $em->flush();

                Display::addFlash(
                    Display::return_message(
                        $isWebinar ? $this->get_lang('WebinarUpdated') : $this->get_lang('MeetingUpdated'),
                        'confirm'
                    )
                );
            } catch (Exception $exception) {
                Display::addFlash(
                    Display::return_message($exception->getMessage(), 'error')
                );
            }
        }

        $defaults = [
            'topic' => $schema->topic,
            'agenda' => $schema->agenda,
        ];

        if ($requiresDateAndDuration) {
            $defaults['startTime'] = $conference->startDateTime->format('Y-m-d H:i');
            $defaults['duration'] = $schema->duration;
        }

        $defaults['sign_attendance'] = $conference->isSignAttendance();
        $defaults['reason_to_sign'] = $conference->getReasonToSignAttendance();

        $form->setDefaults($defaults);

        return $form;
    }

    /**
     * Generates a meeting delete form and deletes the meeting on validation.
     *
     * @param Meeting $meeting
     * @param string  $returnURL where to redirect to on successful deletion
     *
     * @throws Exception
     *
     * @return FormValidator
     */
    public function getDeleteMeetingForm($meeting, $returnURL)
    {
        $id = $meeting->getMeetingId();
        $form = new FormValidator('delete', 'post', api_get_self().'?meetingId='.$id);
        $form->addButtonDelete($this->get_lang('DeleteMeeting'));
        if ($form->validate()) {
            $this->deleteMeeting($meeting, $returnURL);
        }

        return $form;
    }

    public function getDeleteWebinarForm(Webinar $webinar, string $returnURL): FormValidator
    {
        $id = $webinar->getMeetingId();
        $form = new FormValidator('delete', 'post', api_get_self()."?meetingId=$id");
        $form->addButtonDelete($this->get_lang('DeleteWebinar'));

        if ($form->validate()) {
            $this->deleteWebinar($webinar, $returnURL);
        }

        return $form;
    }

    /**
     * @param Meeting $meeting
     * @param string  $returnURL
     */
    public function deleteMeeting($meeting, $returnURL): bool
    {
        if (null === $meeting) {
            return false;
        }

        // No need to delete a instant meeting.
        if (\Chamilo\PluginBundle\Zoom\API\Meeting::TYPE_INSTANT == $meeting->getMeetingInfoGet()->type) {
            return false;
        }

        try {
            $meeting->getMeetingInfoGet()->delete();
        } catch (Exception $exception) {
            $this->handleException($exception);
        }

        $em = Database::getManager();
        $em->remove($meeting);
        $em->flush();

        Display::addFlash(
            Display::return_message($this->get_lang('MeetingDeleted'), 'confirm')
        );
        api_location($returnURL);

        return true;
    }

    public function deleteWebinar(Webinar $webinar, string $returnURL)
    {
        try {
            $webinar->getWebinarSchema()->delete();
        } catch (Exception $exception) {
            $this->handleException($exception);
        }

        $em = Database::getManager();
        $em->remove($webinar);
        $em->flush();

        Display::addFlash(
            Display::return_message($this->get_lang('WebinarDeleted'), 'success')
        );

        api_location($returnURL);
    }

    /**
     * @param Exception $exception
     */
    public function handleException($exception)
    {
        if ($exception instanceof Exception) {
            $error = json_decode($exception->getMessage());
            $message = $exception->getMessage();
            if ($error->message) {
                $message = $error->message;
            }
            Display::addFlash(
                Display::return_message($message, 'error')
            );
        }
    }

    /**
     * Generates a registrant list update form listing course and session users.
     * Updates the list on validation.
     *
     * @param Meeting $meeting
     *
     * @throws Exception
     *
     * @return FormValidator
     */
    public function getRegisterParticipantForm($meeting)
    {
        $form = new FormValidator('register', 'post', $_SERVER['REQUEST_URI']);
        $userIdSelect = $form->addSelect('userIds', $this->get_lang('RegisteredUsers'));
        $userIdSelect->setMultiple(true);
        $form->addButtonSend($this->get_lang('UpdateRegisteredUserList'));

        $selfRegistrationUrl = api_get_path(WEB_PLUGIN_PATH)
            .'zoom/subscription.php?meetingId='.$meeting->getMeetingId();

        $form->addHtml(
            '<div class="form-group"><div class="col-sm-8 col-sm-offset-2">
                <hr style="margin-top: 0;">
                <label for="frm-registration__txt-self-registration">'
            .$this->get_lang('UrlForSelfRegistration').'</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="frm-registration__txt-self-registration" value="'
            .$selfRegistrationUrl.'">
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="button"
                         onclick="copyTextToClipBoard(\'frm-registration__txt-self-registration\');">'
            .$this->get_lang('CopyTextToClipboard').'</button>
                    </span>
                </div>
            </div></div>'
        );

        $users = $meeting->getRegistrableUsers();
        foreach ($users as $user) {
            $userIdSelect->addOption(
                api_get_person_name($user->getFirstname(), $user->getLastname()),
                $user->getId()
            );
        }

        if ($form->validate()) {
            $selectedUserIds = $form->getSubmitValue('userIds');
            $selectedUsers = [];
            if (!empty($selectedUserIds)) {
                foreach ($users as $user) {
                    if (in_array($user->getId(), $selectedUserIds)) {
                        $selectedUsers[] = $user;
                    }
                }
            }

            try {
                $this->updateRegistrantList($meeting, $selectedUsers);
                Display::addFlash(
                    Display::return_message($this->get_lang('RegisteredUserListWasUpdated'), 'confirm')
                );
            } catch (Exception $exception) {
                Display::addFlash(
                    Display::return_message($exception->getMessage(), 'error')
                );
            }
        }
        $registeredUserIds = [];
        foreach ($meeting->getRegistrants() as $registrant) {
            $registeredUserIds[] = $registrant->getUser()->getId();
        }
        $userIdSelect->setSelected($registeredUserIds);

        return $form;
    }

    /**
     * Generates a meeting recording files management form.
     * Takes action on validation.
     *
     * @param Meeting $meeting
     *
     * @throws Exception
     *
     * @return FormValidator
     */
    public function getFileForm($meeting, $returnURL)
    {
        $form = new FormValidator('fileForm', 'post', $_SERVER['REQUEST_URI']);
        if (!$meeting->getRecordings()->isEmpty()) {
            $fileIdSelect = $form->addSelect('fileIds', get_lang('Files'));
            $fileIdSelect->setMultiple(true);
            $recordingList = $meeting->getRecordings();
            foreach ($recordingList as &$recording) {
                // $recording->instanceDetails = $plugin->getPastMeetingInstanceDetails($instance->uuid);
                $options = [];
                $recordings = $recording->getRecordingMeeting()->recording_files;
                foreach ($recordings as $file) {
                    $options[] = [
                        'text' => sprintf(
                            '%s.%s (%s)',
                            $file->recording_type,
                            $file->file_type,
                            $file->file_size
                        ),
                        'value' => $file->id,
                    ];
                }
                $fileIdSelect->addOptGroup(
                    $options,
                    sprintf("%s (%s)", $recording->formattedStartTime, $recording->formattedDuration)
                );
            }
            $actions = [];
            if ($meeting->isCourseMeeting()) {
                $actions['CreateLinkInCourse'] = $this->get_lang('CreateLinkInCourse');
                $actions['CopyToCourse'] = $this->get_lang('CopyToCourse');
            }
            $actions['DeleteFile'] = $this->get_lang('DeleteFile');
            $form->addRadio(
                'action',
                get_lang('Action'),
                $actions
            );
            $form->addButtonUpdate($this->get_lang('DoIt'));
            if ($form->validate()) {
                $action = $form->getSubmitValue('action');
                $idList = $form->getSubmitValue('fileIds');

                foreach ($recordingList as $recording) {
                    $recordings = $recording->getRecordingMeeting()->recording_files;

                    foreach ($recordings as $file) {
                        if (in_array($file->id, $idList)) {
                            $name = sprintf(
                                $this->get_lang('XRecordingOfMeetingXFromXDurationXDotX'),
                                $file->recording_type,
                                $meeting->getId(),
                                $recording->formattedStartTime,
                                $recording->formattedDuration,
                                $file->file_type
                            );
                            if ('CreateLinkInCourse' === $action && $meeting->isCourseMeeting()) {
                                try {
                                    $this->createLinkToFileInCourse($meeting, $file, $name);
                                    Display::addFlash(
                                        Display::return_message(
                                            $this->get_lang('LinkToFileWasCreatedInCourse'),
                                            'success'
                                        )
                                    );
                                } catch (Exception $exception) {
                                    Display::addFlash(
                                        Display::return_message($exception->getMessage(), 'error')
                                    );
                                }
                            } elseif ('CopyToCourse' === $action && $meeting->isCourseMeeting()) {
                                try {
                                    $this->copyFileToCourse($meeting, $file, $name);
                                    Display::addFlash(
                                        Display::return_message($this->get_lang('FileWasCopiedToCourse'), 'confirm')
                                    );
                                } catch (Exception $exception) {
                                    Display::addFlash(
                                        Display::return_message($exception->getMessage(), 'error')
                                    );
                                }
                            } elseif ('DeleteFile' === $action) {
                                try {
                                    $name = $file->recording_type;
                                    $file->delete();
                                    Display::addFlash(
                                        Display::return_message($this->get_lang('FileWasDeleted').': '.$name, 'confirm')
                                    );
                                } catch (Exception $exception) {
                                    Display::addFlash(
                                        Display::return_message($exception->getMessage(), 'error')
                                    );
                                }
                            }
                        }
                    }
                }
                api_location($returnURL);
            }
        }

        return $form;
    }

    /**
     * Adds to the meeting course documents a link to a meeting instance recording file.
     *
     * @param Meeting       $meeting
     * @param RecordingFile $file
     * @param string        $name
     *
     * @throws Exception
     */
    public function createLinkToFileInCourse($meeting, $file, $name)
    {
        $course = $meeting->getCourse();
        if (null === $course) {
            throw new Exception('This meeting is not linked to a course');
        }
        $courseInfo = api_get_course_info_by_id($course->getId());
        if (empty($courseInfo)) {
            throw new Exception('This meeting is not linked to a valid course');
        }
        $path = '/zoom_meeting_recording_file_'.$file->id.'.'.$file->file_type;
        $docId = DocumentManager::addCloudLink($courseInfo, $path, $file->play_url, $name);
        if (!$docId) {
            throw new Exception(get_lang(DocumentManager::cloudLinkExists($courseInfo, $path, $file->play_url) ? 'UrlAlreadyExists' : 'ErrorAddCloudLink'));
        }
    }

    /**
     * Copies a recording file to a meeting's course.
     *
     * @param Meeting       $meeting
     * @param RecordingFile $file
     * @param string        $name
     *
     * @throws Exception
     */
    public function copyFileToCourse($meeting, $file, $name)
    {
        $course = $meeting->getCourse();
        if (null === $course) {
            throw new Exception('This meeting is not linked to a course');
        }
        $courseInfo = api_get_course_info_by_id($course->getId());
        if (empty($courseInfo)) {
            throw new Exception('This meeting is not linked to a valid course');
        }
        $tmpFile = tmpfile();
        if (false === $tmpFile) {
            throw new Exception('tmpfile() returned false');
        }
        $curl = curl_init($file->getFullDownloadURL($this->jwtClient->token));
        if (false === $curl) {
            throw new Exception('Could not init curl: '.curl_error($curl));
        }
        if (!curl_setopt_array(
            $curl,
            [
                CURLOPT_FILE => $tmpFile,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 120,
            ]
        )) {
            throw new Exception("Could not set curl options: ".curl_error($curl));
        }
        if (false === curl_exec($curl)) {
            throw new Exception("curl_exec failed: ".curl_error($curl));
        }

        $sessionId = 0;
        $session = $meeting->getSession();
        if (null !== $session) {
            $sessionId = $session->getId();
        }

        $groupId = 0;
        $group = $meeting->getGroup();
        if (null !== $group) {
            $groupId = $group->getIid();
        }

        $newPath = handle_uploaded_document(
            $courseInfo,
            [
                'name' => $name,
                'tmp_name' => stream_get_meta_data($tmpFile)['uri'],
                'size' => filesize(stream_get_meta_data($tmpFile)['uri']),
                'from_file' => true,
                'move_file' => true,
                'type' => $file->file_type,
            ],
            api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document',
            '/',
            api_get_user_id(),
            $groupId,
            null,
            0,
            'overwrite',
            true,
            false,
            null,
            $sessionId,
            true
        );

        fclose($tmpFile);
        if (false === $newPath) {
            throw new Exception('Could not handle uploaded document');
        }
    }

    /**
     * Generates a form to fast and easily create and start an instant meeting.
     * On validation, create it then redirect to it and exit.
     *
     * @return FormValidator
     */
    public function getCreateInstantMeetingForm(
        User $user,
        Course $course,
        CGroupInfo $group = null,
        Session $session = null
    ) {
        $extraUrl = '';
        if (!empty($course)) {
            $extraUrl = api_get_cidreq();
        }
        $form = new FormValidator('createInstantMeetingForm', 'post', api_get_self().'?'.$extraUrl, '_blank');
        $form->addButton('startButton', $this->get_lang('StartInstantMeeting'), 'video-camera', 'primary');
        if ($form->validate()) {
            try {
                $this->startInstantMeeting($this->get_lang('InstantMeeting'), $user, $course, $group, $session);
            } catch (Exception $exception) {
                Display::addFlash(
                    Display::return_message($exception->getMessage(), 'error')
                );
            }
        }

        return $form;
    }

    /**
     * Generates a form to schedule a meeting.
     * On validation, creates it and redirects to its page.
     *
     * @throws Exception
     *
     * @return FormValidator
     */
    public function getScheduleMeetingForm(User $user, Course $course = null, CGroupInfo $group = null, Session $session = null)
    {
        $extraUrl = '';
        if (!empty($course)) {
            $extraUrl = api_get_cidreq();
        }
        $form = new FormValidator('scheduleMeetingForm', 'post', api_get_self().'?'.$extraUrl);
        $form->addHeader($this->get_lang('ScheduleAMeeting'));

        $form->addSelect(
            'conference_type',
            $this->get_lang('ConferenceType'),
            [
                'meeting' => $this->get_lang('Meeting'),
                'webinar' => $this->get_lang('Webinar'),
            ]
        );
        $form->addRule('conference_type', get_lang('ThisFieldIsRequired'), 'required');

        $startTimeDatePicker = $form->addDateTimePicker('startTime', get_lang('StartTime'));
        $form->setRequired($startTimeDatePicker);

        $form->addText('topic', $this->get_lang('Topic'), true);
        $form->addTextarea('agenda', get_lang('Agenda'), ['maxlength' => 2000]);

        $durationNumeric = $form->addNumeric('duration', $this->get_lang('DurationInMinutes'));
        $form->setRequired($durationNumeric);

        if (null === $course && 'true' === $this->get('enableGlobalConference')) {
            $options = [];
            $options['everyone'] = $this->get_lang('ForEveryone');
            $options['registered_users'] = $this->get_lang('SomeUsers');
            if (!empty($options)) {
                if (1 === count($options)) {
                    $form->addHidden('type', key($options));
                } else {
                    $form->addSelect('type', $this->get_lang('AudienceType'), $options);
                }
            }
        } else {
            // To course
            $form->addHidden('type', 'course');
        }

        /*
       // $passwordText = $form->addText('password', get_lang('Password'), false, ['maxlength' => '10']);
       if (null !== $course) {
           $registrationOptions = [
               'RegisterAllCourseUsers' => $this->get_lang('RegisterAllCourseUsers'),
           ];
           $groups = GroupManager::get_groups();
           if (!empty($groups)) {
               $registrationOptions['RegisterTheseGroupMembers'] = get_lang('RegisterTheseGroupMembers');
           }
           $registrationOptions['RegisterNoUser'] = $this->get_lang('RegisterNoUser');
           $userRegistrationRadio = $form->addRadio(
               'userRegistration',
               $this->get_lang('UserRegistration'),
               $registrationOptions
           );
           $groupOptions = [];
           foreach ($groups as $group) {
               $groupOptions[$group['id']] = $group['name'];
           }
           $groupIdsSelect = $form->addSelect(
               'groupIds',
               $this->get_lang('RegisterTheseGroupMembers'),
               $groupOptions
           );
           $groupIdsSelect->setMultiple(true);
           if (!empty($groups)) {
               $jsCode = sprintf(
                   "getElementById('%s').parentNode.parentNode.parentNode.style.display = getElementById('%s').checked ? 'block' : 'none'",
                   $groupIdsSelect->getAttribute('id'),
                   $userRegistrationRadio->getelements()[1]->getAttribute('id')
               );

               $form->setAttribute('onchange', $jsCode);
           }
       }*/

        $form->addCheckBox('sign_attendance', $this->get_lang('SignAttendance'), get_lang('Yes'));
        $form->addTextarea('reason_to_sign', $this->get_lang('ReasonToSign'), ['rows' => 5]);

        $accountEmails = $this->getAccountEmails();

        if (!empty($accountEmails)) {
            $form->addSelect('account_email', $this->get_lang('AccountEmail'), $accountEmails);
        }

        $form->addButtonCreate(get_lang('Save'));

        if ($form->validate()) {
            $formValues = $form->exportValues();
            $conferenceType = $formValues['conference_type'];
            $password = substr(uniqid('z', true), 0, 10);

            switch ($formValues['type']) {
                case 'everyone':
                    $user = null;
                    $group = null;
                    $course = null;
                    $session = null;

                    break;
                case 'registered_users':
                    //$user = null;
                    $course = null;
                    $session = null;

                    break;
                case 'course':
                    $user = null;
                    //$course = null;
                    //$session = null;

                    break;
            }

            $accountEmail = $formValues['account_email'] ?? null;
            $accountEmail = $accountEmail && in_array($accountEmail, $accountEmails) ? $accountEmail : null;

            try {
                $startTime = new DateTime($formValues['startTime']);

                if ('meeting' === $conferenceType) {
                    $newMeeting = $this->createScheduleMeeting(
                        $user,
                        $course,
                        $group,
                        $session,
                        $startTime,
                        $formValues['duration'],
                        $formValues['topic'],
                        $formValues['agenda'],
                        $password,
                        isset($formValues['sign_attendance']),
                        $formValues['reason_to_sign'],
                        $accountEmail
                    );

                    Display::addFlash(
                        Display::return_message($this->get_lang('NewMeetingCreated'))
                    );
                } elseif ('webinar' === $conferenceType) {
                    $newMeeting = $this->createScheduleWebinar(
                        $user,
                        $course,
                        $group,
                        $session,
                        $startTime,
                        $formValues['duration'],
                        $formValues['topic'],
                        $formValues['agenda'],
                        $password,
                        isset($formValues['sign_attendance']),
                        $formValues['reason_to_sign'],
                        $accountEmail
                    );

                    Display::addFlash(
                        Display::return_message($this->get_lang('NewWebinarCreated'))
                    );
                } else {
                    throw new Exception('Invalid conference type');
                }

                if ($newMeeting->isCourseMeeting()) {
                    if ('RegisterAllCourseUsers' === $form->getSubmitValue('userRegistration')) {
                        $this->registerAllCourseUsers($newMeeting);
                        Display::addFlash(
                            Display::return_message($this->get_lang('AllCourseUsersWereRegistered'))
                        );
                    } elseif ('RegisterTheseGroupMembers' === $form->getSubmitValue('userRegistration')) {
                        $userIds = [];
                        foreach ($form->getSubmitValue('groupIds') as $groupId) {
                            $userIds = array_unique(array_merge($userIds, GroupManager::get_users($groupId)));
                        }
                        $users = Database::getManager()->getRepository('ChamiloUserBundle:User')->findBy(
                            ['id' => $userIds]
                        );
                        $this->registerUsers($newMeeting, $users);
                        Display::addFlash(
                            Display::return_message($this->get_lang('GroupUsersWereRegistered'))
                        );
                    }
                }
                api_location('meeting.php?meetingId='.$newMeeting->getMeetingId().'&'.$extraUrl);
            } catch (Exception $exception) {
                Display::addFlash(
                    Display::return_message($exception->getMessage(), 'error')
                );
            }
        } else {
            $form->setDefaults(
                [
                    'duration' => 60,
                    'userRegistration' => 'RegisterAllCourseUsers',
                ]
            );
        }

        return $form;
    }

    /**
     * Return the current global meeting (create it if needed).
     *
     * @throws Exception
     *
     * @return string
     */
    public function getGlobalMeeting()
    {
        foreach ($this->getMeetingRepository()->unfinishedGlobalMeetings() as $meeting) {
            return $meeting;
        }

        return $this->createGlobalMeeting();
    }

    /**
     * @return MeetingRepository|EntityRepository
     */
    public static function getMeetingRepository()
    {
        return Database::getManager()->getRepository(Meeting::class);
    }

    /**
     * Returns the URL to enter (start or join) a meeting or null if not possible to enter the meeting,
     * The returned URL depends on the meeting current status (waiting, started or finished) and the current user.
     *
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return string|null
     */
    public function getStartOrJoinMeetingURL(Meeting $meeting)
    {
        if ($meeting instanceof Webinar) {
            $status = 'started';
        } else {
            $status = $meeting->getMeetingInfoGet()->status;
        }

        $userId = api_get_user_id();
        $currentUser = api_get_user_entity($userId);
        $isGlobal = 'true' === $this->get('enableGlobalConference') && $meeting->isGlobalMeeting();

        switch ($status) {
            case 'ended':
                if ($this->userIsConferenceManager($meeting)) {
                    return $meeting->getMeetingInfoGet()->start_url;
                }
                break;
            case 'waiting':
                // Zoom does not allow for a new meeting to be started on first participant join.
                // It requires the host to start the meeting first.
                // Therefore for global meetings we must make the first participant the host
                // that is use start_url rather than join_url.
                // the participant will not be registered and will appear as the Zoom user account owner.
                // For course and user meetings, only the host can start the meeting.
                if ($this->userIsConferenceManager($meeting)) {
                    return $meeting->getMeetingInfoGet()->start_url;
                }

                break;
            case 'started':
                // User per conference.
                if ($currentUser === $meeting->getUser()) {
                    return $meeting instanceof Webinar
                        ? $meeting->getWebinarSchema()->start_url
                        : $meeting->getMeetingInfoGet()->join_url;
                }

                // The participant is not registered, he can join only the global meeting (automatic registration).
                if ($isGlobal) {
                    return $this->registerUser($meeting, $currentUser)->getCreatedRegistration()->join_url;
                }

                if ($meeting->isCourseMeeting()) {
                    if ($this->userIsCourseConferenceManager()) {
                        return $meeting instanceof Webinar
                            ? $meeting->getWebinarSchema()->start_url
                            : $meeting->getMeetingInfoGet()->start_url;
                    }

                    $sessionId = api_get_session_id();
                    $courseCode = api_get_course_id();

                    if (empty($sessionId)) {
                        $isSubscribed = CourseManager::is_user_subscribed_in_course(
                            $userId,
                            $courseCode,
                            false
                        );
                    } else {
                        $isSubscribed = CourseManager::is_user_subscribed_in_course(
                            $userId,
                            $courseCode,
                            true,
                            $sessionId
                        );
                    }

                    if ($isSubscribed) {
                        if ($meeting->isCourseGroupMeeting()) {
                            $groupInfo = GroupManager::get_group_properties($meeting->getGroup()->getIid(), true);
                            $isInGroup = GroupManager::is_user_in_group($userId, $groupInfo);
                            if (false === $isInGroup) {
                                throw new Exception($this->get_lang('YouAreNotRegisteredToThisMeeting'));
                            }
                        }

                        if (!$meeting instanceof Webinar
                            && \Chamilo\PluginBundle\Zoom\API\Meeting::TYPE_INSTANT == $meeting->getMeetingInfoGet()->type
                        ) {
                            return $meeting->getMeetingInfoGet()->join_url;
                        }

                        return $this->registerUser($meeting, $currentUser)->getCreatedRegistration()->join_url;
                    }

                    throw new Exception($this->get_lang('YouAreNotRegisteredToThisMeeting'));
                }

                //if ('true' === $this->get('enableParticipantRegistration')) {
                    //if ('true' === $this->get('enableParticipantRegistration') && $meeting->requiresRegistration()) {
                    // the participant must be registered
                    $registrant = $meeting->getRegistrantByUser($currentUser);
                    if (null == $registrant) {
                        throw new Exception($this->get_lang('YouAreNotRegisteredToThisMeeting'));
                    }

                    // the participant is registered
                    return $registrant->getCreatedRegistration()->join_url;
                //}
                break;
        }

        return null;
    }

    /**
     * @param Meeting $meeting
     *
     * @return bool whether the logged-in user can manage conferences in this context, that is either
     *              the current course or session coach, the platform admin or the current course admin
     */
    public function userIsConferenceManager($meeting)
    {
        if (null === $meeting) {
            return false;
        }

        if (api_is_coach() || api_is_platform_admin()) {
            return true;
        }

        if ($meeting->isCourseMeeting() && api_get_course_id() && api_is_course_admin()) {
            return true;
        }

        return $meeting->isUserMeeting() && $meeting->getUser()->getId() == api_get_user_id();
    }

    /**
     * @return bool whether the logged-in user can manage conferences in this context, that is either
     *              the current course or session coach, the platform admin or the current course admin
     */
    public function userIsCourseConferenceManager()
    {
        if (api_is_coach() || api_is_platform_admin()) {
            return true;
        }

        if (api_get_course_id() && api_is_course_admin()) {
            return true;
        }

        return false;
    }

    /**
     * Update local recording list from remote Zoom server's version.
     * Kept to implement a future administration button ("import existing data from zoom server").
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function reloadPeriodRecordings($startDate, $endDate)
    {
        $em = Database::getManager();
        $recordingRepo = $this->getRecordingRepository();
        $meetingRepo = $this->getMeetingRepository();
        $recordings = RecordingList::loadPeriodRecordings($startDate, $endDate);

        foreach ($recordings as $recordingMeeting) {
            $recordingEntity = $recordingRepo->findOneBy(['uuid' => $recordingMeeting->uuid]);
            if (null === $recordingEntity) {
                $recordingEntity = new Recording();
                $meeting = $meetingRepo->findOneBy(['meetingId' => $recordingMeeting->id]);
                if (null === $meeting) {
                    try {
                        $meetingInfoGet = MeetingInfoGet::fromId($recordingMeeting->id);
                    } catch (Exception $exception) {
                        $meetingInfoGet = null; // deleted meeting with recordings
                    }
                    if (null !== $meetingInfoGet) {
                        $meeting = $this->createMeetingFromMeeting(
                            (new Meeting())->setMeetingInfoGet($meetingInfoGet)
                        );
                        $em->persist($meeting);
                    }
                }
                if (null !== $meeting) {
                    $recordingEntity->setMeeting($meeting);
                }
            }
            $recordingEntity->setRecordingMeeting($recordingMeeting);
            $em->persist($recordingEntity);
        }
        $em->flush();
    }

    /**
     * @return RecordingRepository|EntityRepository
     */
    public static function getRecordingRepository()
    {
        return Database::getManager()->getRepository(Recording::class);
    }

    public function getToolbar(string $returnUrl = ''): string
    {
        $isPlatformOrSessionAdmin = api_is_platform_admin(true);
        $isSessionAdmin = api_is_session_admin();

        if (!$isPlatformOrSessionAdmin) {
            return '';
        }

        $actionsLeft = '';
        $back = '';
        $courseId = api_get_course_id();
        if (empty($courseId)) {
            $actionsLeft .=
                Display::url(
                    Display::return_icon('bbb.png', $this->get_lang('Meetings'), null, ICON_SIZE_MEDIUM),
                    api_get_path(WEB_PLUGIN_PATH).'zoom/meetings.php'
                );
        } else {
            $actionsLeft .=
                Display::url(
                    Display::return_icon('bbb.png', $this->get_lang('Meetings'), null, ICON_SIZE_MEDIUM),
                    api_get_path(WEB_PLUGIN_PATH).'zoom/start.php?'.api_get_cidreq()
                );
        }

        if (!empty($returnUrl)) {
            $back = Display::url(
                Display::return_icon('back.png', get_lang('Back'), null, ICON_SIZE_MEDIUM),
                $returnUrl
            );
        }

        if (!$isSessionAdmin) {
            $actionsLeft .= Display::url(
                Display::return_icon('agenda.png', get_lang('Calendar'), [], ICON_SIZE_MEDIUM),
                'calendar.php'
            );

            $actionsLeft .= Display::url(
                Display::return_icon('settings.png', get_lang('Settings'), null, ICON_SIZE_MEDIUM),
                api_get_path(WEB_CODE_PATH).'admin/configure_plugin.php?name=zoom'
            );
        }

        return Display::toolbarAction('toolbar', [$back.PHP_EOL.$actionsLeft]);
    }

    public function getRecordingSetting()
    {
        $recording = (string) $this->get('enableCloudRecording');

        if (in_array($recording, [self::RECORDING_TYPE_LOCAL, self::RECORDING_TYPE_CLOUD], true)) {
            return $recording;
        }

        return self::RECORDING_TYPE_NONE;
    }

    public function hasRecordingAvailable()
    {
        $recording = $this->getRecordingSetting();

        return self::RECORDING_TYPE_NONE !== $recording;
    }

    /**
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     */
    public function saveSignature(Registrant $registrant, string $file): bool
    {
        if (empty($file)) {
            return false;
        }

        $signature = $registrant->getSignature();

        if (null !== $signature) {
            return false;
        }

        $signature = new Signature();
        $signature
            ->setFile($file)
            ->setRegisteredAt(api_get_utc_datetime(null, false, true))
        ;

        $registrant->setSignature($signature);

        $em = Database::getManager();
        $em->persist($signature);
        $em->flush();

        return true;
    }

    public function getSignature(int $userId, Meeting $meeting): ?Signature
    {
        $signatureRepo = Database::getManager()
            ->getRepository(Signature::class)
        ;

        return $signatureRepo->findOneBy(['user' => $userId, 'meeting' => $meeting]);
    }

    public function exportSignatures(Meeting $meeting, $formatToExport)
    {
        $signatures = array_map(
            function (Registrant $registrant) use ($formatToExport) {
                $signature = $registrant->getSignature();

                $item = [
                    $registrant->getUser()->getLastname(),
                    $registrant->getUser()->getFirstname(),
                    $signature
                        ? api_convert_and_format_date($signature->getRegisteredAt(), DATE_TIME_FORMAT_LONG)
                        : '-',
                ];

                if ('pdf' === $formatToExport) {
                    $item[] = $signature
                        ? Display::img($signature->getFile(), '', ['style' => 'width: 150px;'], false)
                        : '-';
                }

                return $item;
            },
            $meeting->getRegistrants()->toArray()
        );

        $data = array_merge(
            [
                [
                    get_lang('LastName'),
                    get_lang('FirstName'),
                    get_lang('DateTime'),
                    'pdf' === $formatToExport ? get_lang('File') : null,
                ],
            ],
            $signatures
        );

        if ('pdf' === $formatToExport) {
            $params = [
                'filename' => get_lang('Attendance'),
                'pdf_title' => get_lang('Attendance'),
                'pdf_description' => $meeting->getIntroduction(),
                'show_teacher_as_myself' => false,
            ];

            Export::export_table_pdf($data, $params);
        }

        if ('xls' === $formatToExport) {
            $introduction = array_map(
                function ($line) {
                    return [
                        strip_tags(trim($line)),
                    ];
                },
                explode(PHP_EOL, $meeting->getIntroduction())
            );

            Export::arrayToXls(
                array_merge($introduction, $data),
                get_lang('Attendance')
            );
        }
    }

    /**
     * @throws Exception
     */
    public function createWebinarFromSchema(Webinar $webinar, WebinarSchema $schema): Webinar
    {
        $currentUser = api_get_user_entity(api_get_user_id());

        $schema->settings->contact_email = $currentUser->getEmail();
        $schema->settings->contact_name = $currentUser->getFullname();
        $schema->settings->auto_recording = $this->getRecordingSetting();
        $schema->settings->registrants_email_notification = false;
        $schema->settings->attendees_and_panelists_reminder_email_notification->enable = false;
        $schema->settings->follow_up_attendees_email_notification->enable = false;
        $schema->settings->follow_up_absentees_email_notification->enable = false;

        $schema = $schema->create($webinar->getAccountEmail());

        $webinar->setWebinarSchema($schema);

        $em = Database::getManager();
        $em->persist($webinar);
        $em->flush();

        return $webinar;
    }

    public function getAccountEmails(): array
    {
        $currentValue = $this->get('accountSelector');

        if (empty($currentValue)) {
            return [];
        }

        $emails = explode(';', $currentValue);
        $trimmed = array_map('trim', $emails);
        $filtered = array_filter($trimmed);

        return array_combine($filtered, $filtered);
    }

    /**
     * Register users to a meeting.
     *
     * @param User[] $users
     *
     * @throws OptimisticLockException
     *
     * @return User[] failed registrations [ user id => errorMessage ]
     */
    public function registerUsers(Meeting $meeting, array $users)
    {
        $failedUsers = [];
        foreach ($users as $user) {
            try {
                $this->registerUser($meeting, $user, false);
            } catch (Exception $exception) {
                $failedUsers[$user->getId()] = $exception->getMessage();
            }
        }
        Database::getManager()->flush();

        return $failedUsers;
    }

    /**
     * Removes registrants from a meeting.
     *
     * @param Registrant[] $registrants
     *
     * @throws Exception
     */
    public function unregister(Meeting $meeting, array $registrants)
    {
        $meetingRegistrants = [];
        foreach ($registrants as $registrant) {
            $meetingRegistrants[] = $registrant->getMeetingRegistrant();
        }

        if ($meeting instanceof Webinar) {
            $meeting->getWebinarSchema()->removeRegistrants($meetingRegistrants);
        } else {
            $meeting->getMeetingInfoGet()->removeRegistrants($meetingRegistrants);
        }

        $em = Database::getManager();
        foreach ($registrants as $registrant) {
            $em->remove($registrant);
        }
        $em->flush();
    }

    /**
     * Updates meeting registrants list. Adds the missing registrants and removes the extra.
     *
     * @param Meeting $meeting
     * @param User[]  $users   list of users to be registered
     *
     * @throws Exception
     */
    private function updateRegistrantList($meeting, $users)
    {
        $usersToAdd = [];
        foreach ($users as $user) {
            $found = false;
            foreach ($meeting->getRegistrants() as $registrant) {
                if ($registrant->getUser() === $user) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $usersToAdd[] = $user;
            }
        }
        $registrantsToRemove = [];
        foreach ($meeting->getRegistrants() as $registrant) {
            $found = false;
            foreach ($users as $user) {
                if ($registrant->getUser() === $user) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $registrantsToRemove[] = $registrant;
            }
        }
        $this->registerUsers($meeting, $usersToAdd);
        $this->unregister($meeting, $registrantsToRemove);
    }

    /**
     * @throws Exception
     * @throws OptimisticLockException
     *
     * @return Registrant
     */
    private function registerUser(Meeting $meeting, User $user, $andFlush = true)
    {
        if (empty($user->getEmail())) {
            throw new Exception($this->get_lang('CannotRegisterWithoutEmailAddress'));
        }

        if ($meeting instanceof Webinar) {
            $meetingRegistrant = WebinarRegistrantSchema::fromEmailAndFirstName(
                $user->getEmail(),
                $user->getFirstname(),
                $user->getLastname()
            );
        } else {
            $meetingRegistrant = MeetingRegistrant::fromEmailAndFirstName(
                $user->getEmail(),
                $user->getFirstname(),
                $user->getLastname()
            );
        }

        $registrantEntity = (new Registrant())
            ->setMeeting($meeting)
            ->setUser($user)
            ->setMeetingRegistrant($meetingRegistrant)
        ;

        if ($meeting instanceof Webinar) {
            $registrantEntity->setCreatedRegistration($meeting->getWebinarSchema()->addRegistrant($meetingRegistrant));
        } else {
            $registrantEntity->setCreatedRegistration($meeting->getMeetingInfoGet()->addRegistrant($meetingRegistrant));
        }

        Database::getManager()->persist($registrantEntity);

        if ($andFlush) {
            Database::getManager()->flush($registrantEntity);
        }

        return $registrantEntity;
    }

    /**
     * Starts a new instant meeting and redirects to its start url.
     *
     * @param string          $topic
     * @param User|null       $user
     * @param Course|null     $course
     * @param CGroupInfo|null $group
     * @param Session|null    $session
     *
     * @throws Exception
     */
    private function startInstantMeeting($topic, $user = null, $course = null, $group = null, $session = null)
    {
        $meetingInfoGet = MeetingInfoGet::fromTopicAndType($topic, MeetingInfoGet::TYPE_INSTANT);
        //$meetingInfoGet->settings->approval_type = MeetingSettings::APPROVAL_TYPE_AUTOMATICALLY_APPROVE;
        $meeting = $this->createMeetingFromMeeting(
            (new Meeting())
                ->setMeetingInfoGet($meetingInfoGet)
                ->setUser($user)
                ->setGroup($group)
                ->setCourse($course)
                ->setSession($session)
        );
        api_location($meeting->getMeetingInfoGet()->start_url);
    }

    /**
     * Creates a meeting on Zoom servers and stores it in the local database.
     *
     * @param Meeting $meeting a new, unsaved meeting with at least a type and a topic
     *
     * @throws Exception
     *
     * @return Meeting
     */
    private function createMeetingFromMeeting($meeting)
    {
        $currentUser = api_get_user_entity(api_get_user_id());

        $meeting->getMeetingInfoGet()->settings->contact_email = $currentUser->getEmail();
        $meeting->getMeetingInfoGet()->settings->contact_name = $currentUser->getFullname();
        $meeting->getMeetingInfoGet()->settings->auto_recording = $this->getRecordingSetting();
        $meeting->getMeetingInfoGet()->settings->registrants_email_notification = false;

        //$meeting->getMeetingInfoGet()->host_email = $currentUser->getEmail();
        //$meeting->getMeetingInfoGet()->settings->alternative_hosts = $currentUser->getEmail();

        // Send create to Zoom.
        $meeting->setMeetingInfoGet(
            $meeting->getMeetingInfoGet()->create(
                $meeting->getAccountEmail()
            )
        );

        Database::getManager()->persist($meeting);
        Database::getManager()->flush();

        return $meeting;
    }

    /**
     * @throws Exception
     *
     * @return Meeting
     */
    private function createGlobalMeeting()
    {
        $meetingInfoGet = MeetingInfoGet::fromTopicAndType(
            $this->get_lang('GlobalMeeting'),
            MeetingInfoGet::TYPE_SCHEDULED
        );
        $meetingInfoGet->start_time = (new DateTime())->format(DATE_ATOM);
        $meetingInfoGet->duration = 60;
        $meetingInfoGet->settings->approval_type =
            ('true' === $this->get('enableParticipantRegistration'))
                ? MeetingSettings::APPROVAL_TYPE_AUTOMATICALLY_APPROVE
                : MeetingSettings::APPROVAL_TYPE_NO_REGISTRATION_REQUIRED;
        // $meetingInfoGet->settings->host_video = true;
        $meetingInfoGet->settings->participant_video = true;
        $meetingInfoGet->settings->join_before_host = true;
        $meetingInfoGet->settings->registrants_email_notification = false;

        return $this->createMeetingFromMeeting((new Meeting())->setMeetingInfoGet($meetingInfoGet));
    }

    /**
     * Schedules a meeting and returns it.
     * set $course, $session and $user to null in order to create a global meeting.
     *
     * @param DateTime $startTime meeting local start date-time (configure local timezone on your Zoom account)
     * @param int      $duration  in minutes
     * @param string   $topic     short title of the meeting, required
     * @param string   $agenda    ordre du jour
     * @param string   $password  meeting password
     *
     * @throws Exception
     *
     * @return Meeting meeting
     */
    private function createScheduleMeeting(
        User $user = null,
        Course $course = null,
        CGroupInfo $group = null,
        Session $session = null,
        $startTime,
        $duration,
        $topic,
        $agenda,
        $password,
        bool $signAttendance = false,
        string $reasonToSignAttendance = '',
        string $accountEmail = null
    ) {
        $meetingInfoGet = MeetingInfoGet::fromTopicAndType($topic, MeetingInfoGet::TYPE_SCHEDULED);
        $meetingInfoGet->duration = $duration;
        $meetingInfoGet->start_time = $startTime->format(DATE_ATOM);
        $meetingInfoGet->agenda = $agenda;
        $meetingInfoGet->password = $password;
        $meetingInfoGet->settings->approval_type = MeetingSettings::APPROVAL_TYPE_NO_REGISTRATION_REQUIRED;
        if ('true' === $this->get('enableParticipantRegistration')) {
            $meetingInfoGet->settings->approval_type = MeetingSettings::APPROVAL_TYPE_AUTOMATICALLY_APPROVE;
        }

        return $this->createMeetingFromMeeting(
            (new Meeting())
                ->setMeetingInfoGet($meetingInfoGet)
                ->setUser($user)
                ->setCourse($course)
                ->setGroup($group)
                ->setSession($session)
                ->setSignAttendance($signAttendance)
                ->setReasonToSignAttendance($reasonToSignAttendance)
                ->setAccountEmail($accountEmail)
        );
    }

    /**
     * @throws Exception
     */
    private function createScheduleWebinar(
        ?User $user,
        ?Course $course,
        ?CGroupInfo $group,
        ?Session $session,
        DateTime $startTime,
        $duration,
        $topic,
        $agenda,
        $password,
        bool $signAttendance = false,
        string $reasonToSignAttendance = '',
        string $accountEmail = null
    ): Webinar {
        $webinarSchema = WebinarSchema::fromTopicAndType($topic);
        $webinarSchema->duration = $duration;
        $webinarSchema->start_time = $startTime->format(DATE_ATOM);
        $webinarSchema->agenda = $agenda;
        $webinarSchema->password = $password;

        if ('true' === $this->get('enableParticipantRegistration')) {
            $webinarSchema->settings->approval_type = WebinarSettings::APPROVAL_TYPE_AUTOMATICALLY_APPROVE;
        }

        $webinar = (new Webinar())
            ->setUser($user)
            ->setCourse($course)
            ->setGroup($group)
            ->setSession($session)
            ->setSignAttendance($signAttendance)
            ->setReasonToSignAttendance($reasonToSignAttendance)
            ->setAccountEmail($accountEmail)
        ;

        return $this->createWebinarFromSchema($webinar, $webinarSchema);
    }

    /**
     * Registers all the course users to a course meeting.
     *
     * @param Meeting $meeting
     *
     * @throws OptimisticLockException
     */
    private function registerAllCourseUsers($meeting)
    {
        $this->registerUsers($meeting, $meeting->getRegistrableUsers());
    }
}
