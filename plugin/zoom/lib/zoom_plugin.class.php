<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PluginBundle\Zoom\API\JWTClient;
use Chamilo\PluginBundle\Zoom\API\MeetingInfoGet;
use Chamilo\PluginBundle\Zoom\API\MeetingRegistrant;
use Chamilo\PluginBundle\Zoom\API\MeetingSettings;
use Chamilo\PluginBundle\Zoom\API\RecordingFile;
use Chamilo\PluginBundle\Zoom\API\RecordingList;
use Chamilo\PluginBundle\Zoom\MeetingEntity;
use Chamilo\PluginBundle\Zoom\MeetingEntityRepository;
use Chamilo\PluginBundle\Zoom\RecordingEntity;
use Chamilo\PluginBundle\Zoom\RecordingEntityRepository;
use Chamilo\PluginBundle\Zoom\RegistrantEntity;
use Chamilo\PluginBundle\Zoom\RegistrantEntityRepository;
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
            '0.2',
            'Sébastien Ducoulombier, Julio Montoya',
            [
                'tool_enable' => 'boolean',
                'apiKey' => 'text',
                'apiSecret' => 'text',
                'verificationToken' => 'text',
                'enableParticipantRegistration' => 'boolean',
                'enableCloudRecording' => 'boolean',
                'enableGlobalConference' => 'boolean',
                'enableGlobalConferencePerUser' => 'boolean',
                'globalConferenceAllowRoles' => [
                    'type' => 'select',
                    'options' => [
                        PLATFORM_ADMIN => get_lang('Administrator'),
                        COURSEMANAGER => get_lang('Teacher'),
                        STUDENT => get_lang('Student'),
                        STUDENT_BOSS => get_lang('StudentBoss'),
                    ],
                    'attributes' => ['multiple' => 'multiple'],
                ],
                'globalConferencePerUserAllowRoles' => [
                    'type' => 'select',
                    'options' => [
                        PLATFORM_ADMIN => get_lang('Administrator'),
                        COURSEMANAGER => get_lang('Teacher'),
                        STUDENT => get_lang('Student'),
                        STUDENT_BOSS => get_lang('StudentBoss'),
                    ],
                    'attributes' => ['multiple' => 'multiple'],
                ],
            ]
        );

        $this->isAdminPlugin = true;
        $this->jwtClient = new JWTClient($this->get('apiKey'), $this->get('apiSecret'));
    }

    /**
     * Caches and returns an instance of this class.
     *
     * @return ZoomPlugin the instance to use
     */
    public static function create()
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
            )
            ;
    }

    /**
     * @return bool
     */
    public static function currentUserCanCreateUserMeeting()
    {
        $user = api_get_user_entity(api_get_user_id());

        if (null === $user) {
            return false;
        }

        return
            'true' === api_get_plugin_setting('zoom', 'enableGlobalConferencePerUser')
            && in_array(
                (api_is_platform_admin() ? PLATFORM_ADMIN : $user->getStatus()),
                (array) api_get_plugin_setting('zoom', 'globalConferencePerUserAllowRoles')
            )
        ;
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
            $items[
                sprintf(
                    $this->get_lang('DateMeetingTitle'),
                    $meeting->formattedStartTime,
                    $meeting->getMeetingInfoGet()->topic
                )
            ] = sprintf($linkTemplate, $meeting->getId());
        }

        return $items;
    }

    /**
     * @return array
     */
    public function getProfileBlockItems()
    {
        $elements = $this->meetingsToWhichCurrentUserIsRegisteredComingSoon();
        if (self::currentUserCanJoinGlobalMeeting()) {
            $elements[$this->get_lang('CreateGlobalVideoConference')] = api_get_path(WEB_PLUGIN_PATH).'zoom/global.php';
        }

        if (self::currentUserCanCreateUserMeeting()) {
            $elements[$this->get_lang('CreateUserVideoConference')] = api_get_path(WEB_PLUGIN_PATH).'zoom/user.php';
        }
        $items = [];
        foreach ($elements as $title => $link) {
            $items[] = [
                'class' => 'video-conference',
                'icon' => Display::return_icon(
                    'zoom.png',
                    get_lang('VideoConference')
                ),
                'link' => $link,
                'title' => $title,
            ];
        }

        return $items;
    }

    /**
     * @return MeetingEntityRepository|EntityRepository
     */
    public static function getMeetingRepository()
    {
        return Database::getManager()->getRepository(MeetingEntity::class);
    }

    /**
     * @return RecordingEntityRepository|EntityRepository
     */
    public static function getRecordingRepository()
    {
        return Database::getManager()->getRepository(RecordingEntity::class);
    }

    /**
     * @return RegistrantEntityRepository|EntityRepository
     */
    public static function getRegistrantRepository()
    {
        return Database::getManager()->getRepository(RegistrantEntity::class);
    }

    /**
     * Creates this plugin's related tables in the internal database.
     * Installs course fields in all courses.
     *
     * @throws ToolsException
     */
    public function install()
    {
        (new SchemaTool(Database::getManager()))->createSchema([
            Database::getManager()->getClassMetadata(MeetingEntity::class),
            Database::getManager()->getClassMetadata(RecordingEntity::class),
            Database::getManager()->getClassMetadata(RegistrantEntity::class),
        ]);
        $this->install_course_fields_in_all_courses();
    }

    /**
     * Drops this plugins' related tables from the internal database.
     * Uninstalls course fields in all courses().
     */
    public function uninstall()
    {
        (new SchemaTool(Database::getManager()))->dropSchema([
            Database::getManager()->getClassMetadata(MeetingEntity::class),
            Database::getManager()->getClassMetadata(RecordingEntity::class),
            Database::getManager()->getClassMetadata(RegistrantEntity::class),
        ]);
        $this->uninstall_course_fields_in_all_courses();
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
            $form->setDefaults([
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
            ]);
        } catch (Exception $exception) {
            error_log(join(':', [__FILE__, __LINE__, $exception]));
        }

        return $form;
    }

    /**
     * Generates a meeting edit form and updates the meeting on validation.
     *
     * @param MeetingEntity $meetingEntity the meeting
     *
     * @throws Exception
     *
     * @return FormValidator
     */
    public function getEditMeetingForm($meetingEntity)
    {
        $meetingInfoGet = $meetingEntity->getMeetingInfoGet();
        $form = new FormValidator('edit', 'post', $_SERVER['REQUEST_URI']);
        $form->addHeader($this->get_lang('UpdateMeeting'));
        $form->addText('topic', $this->get_lang('Topic'));
        if ($meetingEntity->requiresDateAndDuration()) {
            $startTimeDatePicker = $form->addDateTimePicker('startTime', get_lang('StartTime'));
            $form->setRequired($startTimeDatePicker);
            $durationNumeric = $form->addNumeric('duration', $this->get_lang('DurationInMinutes'));
            $form->setRequired($durationNumeric);
        }
        $form->addTextarea('agenda', get_lang('Agenda'), ['maxlength' => 2000]);
        // $form->addText('password', get_lang('Password'), false, ['maxlength' => '10']);
        $form->addButtonUpdate(get_lang('Update'));
        if ($form->validate()) {
            if ($meetingEntity->requiresDateAndDuration()) {
                $meetingInfoGet->start_time = (new DateTime($form->getSubmitValue('startTime')))->format(
                    DateTimeInterface::ISO8601
                );
                $meetingInfoGet->timezone = date_default_timezone_get();
                $meetingInfoGet->duration = (int) $form->getSubmitValue('duration');
            }
            $meetingInfoGet->topic = $form->getSubmitValue('topic');
            $meetingInfoGet->agenda = $form->getSubmitValue('agenda');
            try {
                $meetingInfoGet->update();
                $meetingEntity->setMeetingInfoGet($meetingInfoGet);
                Database::getManager()->persist($meetingEntity);
                Database::getManager()->flush();
                Display::addFlash(
                    Display::return_message($this->get_lang('MeetingUpdated'), 'confirm')
                );
            } catch (Exception $exception) {
                Display::addFlash(
                    Display::return_message($exception->getMessage(), 'error')
                );
            }
        }
        $defaults = [
            'topic' => $meetingInfoGet->topic,
            'agenda' => $meetingInfoGet->agenda,
        ];
        if ($meetingEntity->requiresDateAndDuration()) {
            $defaults['startTime'] = $meetingEntity->startDateTime->format('Y-m-d H:i');
            $defaults['duration'] = $meetingInfoGet->duration;
        }
        $form->setDefaults($defaults);

        return $form;
    }

    /**
     * Generates a meeting delete form and deletes the meeting on validation.
     *
     * @param MeetingEntity $meetingEntity
     * @param string        $returnURL     where to redirect to on successful deletion
     *
     * @throws Exception
     *
     * @return FormValidator
     */
    public function getDeleteMeetingForm($meetingEntity, $returnURL)
    {
        $form = new FormValidator('delete', 'post', $_SERVER['REQUEST_URI']);
        $form->addButtonDelete($this->get_lang('DeleteMeeting'));
        if ($form->validate()) {
            try {
                $meetingEntity->getMeetingInfoGet()->delete();
                Database::getManager()->remove($meetingEntity);
                Database::getManager()->flush();

                Display::addFlash(
                    Display::return_message($this->get_lang('MeetingDeleted'), 'confirm')
                );
                api_location($returnURL);
            } catch (Exception $exception) {
                Display::addFlash(
                    Display::return_message($exception->getMessage(), 'error')
                );
            }
        }

        return $form;
    }

    /**
     * Generates a registrant list update form listing course and session users.
     * Updates the list on validation.
     *
     * @param MeetingEntity $meetingEntity
     *
     * @throws Exception
     *
     * @return FormValidator
     */
    public function getRegisterParticipantForm($meetingEntity)
    {
        $form = new FormValidator('register', 'post', $_SERVER['REQUEST_URI']);
        $userIdSelect = $form->addSelect('userIds', $this->get_lang('RegisteredUsers'));
        $userIdSelect->setMultiple(true);
        $form->addButtonSend($this->get_lang('UpdateRegisteredUserList'));

        $users = $meetingEntity->getRegistrableUsers();
        foreach ($users as $user) {
            $userIdSelect->addOption(
                api_get_person_name($user->getFirstname(), $user->getLastname()),
                $user->getId()
            );
        }

        if ($form->validate()) {
            $selectedUserIds = $form->getSubmitValue('userIds');
            $selectedUsers = [];
            foreach ($users as $user) {
                if (in_array($user->getId(), $selectedUserIds)) {
                    $selectedUsers[] = $user;
                }
            }
            try {
                $this->updateRegistrantList($meetingEntity, $selectedUsers);
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
        foreach ($meetingEntity->getRegistrants() as $registrant) {
            $registeredUserIds[] = $registrant->getUser()->getId();
        }
        $userIdSelect->setSelected($registeredUserIds);

        return $form;
    }

    /**
     * Generates a meeting recording files management form.
     * Takes action on validation.
     *
     * @param MeetingEntity $meeting
     *
     * @throws Exception
     *
     * @return FormValidator
     */
    public function getFileForm($meeting)
    {
        $form = new FormValidator('fileForm', 'post', $_SERVER['REQUEST_URI']);
        if (!$meeting->getRecordings()->isEmpty()) {
            $fileIdSelect = $form->addSelect('fileIds', get_lang('Files'));
            $fileIdSelect->setMultiple(true);
            foreach ($meeting->getRecordings() as &$recording) {
                // $recording->instanceDetails = $plugin->getPastMeetingInstanceDetails($instance->uuid);
                $options = [];
                foreach ($recording->getRecordingMeeting()->recording_files as $file) {
                    $options[] = [
                        'text' => sprintf(
                            '%s.%s (%s)',
                            $file->recording_type,
                            $file->file_type,
                            //$file->formattedFileSize
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
                foreach ($meeting->getRecordings() as $recording) {
                    foreach ($recording->files as $file) {
                        if (in_array($file->id, $form->getSubmitValue('fileIds'))) {
                            $name = sprintf(
                                $this->get_lang('XRecordingOfMeetingXFromXDurationXDotX'),
                                $file->recording_type,
                                $meeting->getId(),
                                $recording->formattedStartTime,
                                $recording->formattedDuration,
                                $file->file_type
                            );
                            $action = $form->getSubmitValue('action');
                            if ('CreateLinkInCourse' === $action && $meeting->isCourseMeeting()) {
                                try {
                                    $this->createLinkToFileInCourse($meeting, $file, $name);
                                    Display::addFlash(
                                        Display::return_message($this->get_lang('LinkToFileWasCreatedInCourse'), 'success')
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
                                    $file->delete();
                                    Display::addFlash(
                                        Display::return_message($this->get_lang('FileWasDeleted'), 'confirm')
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
            }
        }

        return $form;
    }

    /**
     * Generates a form to fast and easily create and start an instant meeting.
     * On validation, create it then redirect to it and exit.
     *
     * @param User    $user
     * @param Course  $course
     * @param Session $session
     *
     * @return FormValidator
     */
    public function getCreateInstantMeetingForm($user, $course, $session)
    {
        $form = new FormValidator('createInstantMeetingForm', 'post', '', '_blank');
        $form->addButton('startButton', $this->get_lang('StartInstantMeeting'));
        if ($form->validate()) {
            try {
                $this->startInstantMeeting($this->get_lang('InstantMeeting'), $user, $course, $session);
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
     * @param User|null    $user
     * @param Course|null  $course
     * @param Session|null $session
     *
     * @throws Exception
     *
     * @return FormValidator
     */
    public function getScheduleMeetingForm($user, $course = null, $session = null)
    {
        $form = new FormValidator('scheduleMeetingForm');
        $form->addHeader($this->get_lang('ScheduleTheMeeting'));
        $startTimeDatePicker = $form->addDateTimePicker('startTime', get_lang('StartTime'));
        $form->setRequired($startTimeDatePicker);

        $form->addText('topic', $this->get_lang('Topic'), true);
        $form->addTextarea('agenda', get_lang('Agenda'), ['maxlength' => 2000]);

        $durationNumeric = $form->addNumeric('duration', $this->get_lang('DurationInMinutes'));
        $form->setRequired($durationNumeric);

        // $passwordText = $form->addText('password', get_lang('Password'), false, ['maxlength' => '10']);
        if (!is_null($course)) {
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
        }
        $form->addButtonCreate(get_lang('Save'));

        if ($form->validate()) {
            try {
                $newMeeting = $this->scheduleMeeting(
                    $user,
                    $course,
                    $session,
                    new DateTime($form->getSubmitValue('startTime')),
                    $form->getSubmitValue('duration'),
                    $form->getSubmitValue('topic'),
                    $form->getSubmitValue('agenda'),
                    ''
                );
                Display::addFlash(
                    Display::return_message($this->get_lang('NewMeetingCreated'))
                );
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
                    api_location('meeting_from_start.php?meetingId='.$newMeeting->getId());
                } elseif (!is_null($user)) {
                    api_location('meeting_from_user.php?meetingId='.$newMeeting->getId());
                }
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
     * @param MeetingEntity $meetingEntity
     *
     * @return bool whether the logged-in user can manage conferences in this context, that is either
     *              the current course or session coach, the platform admin or the current course admin
     */
    public function userIsConferenceManager($meetingEntity)
    {
        if (null === $meetingEntity) {
            return false;
        }

        return api_is_coach()
            || api_is_platform_admin()
            || $meetingEntity->isCourseMeeting() && api_get_course_id() && api_is_course_admin()
            || $meetingEntity->isUserMeeting() && $meetingEntity->getUser()->getId() == api_get_user_id();
    }

    /**
     * @param Course $course
     *
     * @return bool whether the logged-in user can manage conferences in this context, that is either
     *              the current course or session coach, the platform admin or the current course admin
     */
    public function userIsCourseConferenceManager($course)
    {
        return api_is_coach()
            || api_is_platform_admin()
            || api_get_course_id() && api_is_course_admin();
    }

    /**
     * Adds to the meeting course documents a link to a meeting instance recording file.
     *
     * @param MeetingEntity $meeting
     * @param RecordingFile $file
     * @param string        $name
     *
     * @throws Exception
     */
    public function createLinkToFileInCourse($meeting, $file, $name)
    {
        $course = $meeting->getCourse();
        if (is_null($course)) {
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
     * @param MeetingEntity $meeting
     * @param RecordingFile $file
     * @param string        $name
     *
     * @throws Exception
     */
    public function copyFileToCourse($meeting, $file, $name)
    {
        $course = $meeting->getCourse();
        if (is_null($course)) {
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
        $newPath = handle_uploaded_document(
            $courseInfo,
            [
                'name' => $name,
                'tmp_name' => stream_get_meta_data($tmpFile)['uri'],
                'size' => filesize(stream_get_meta_data($tmpFile)['uri']),
                'from_file' => true,
                'type' => $file->file_type,
            ],
            '/',
            api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document',
            api_get_user_id(),
            0,
            null,
            0,
            '',
            true,
            false,
            null,
            $meeting->getSession()->getId(),
            true
        );
        fclose($tmpFile);
        if (false === $newPath) {
            throw new Exception('could not handle uploaded document');
        }
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
     * Returns the URL to enter (start or join) a meeting or null if not possible to enter the meeting,
     * The returned URL depends on the meeting current status (waiting, started or finished) and the current user.
     *
     * @param MeetingEntity $meeting
     *
     * @throws Exception
     * @throws OptimisticLockException
     *
     * @return string|null
     */
    public function getStartOrJoinMeetingURL($meeting)
    {
        $status = $meeting->getMeetingInfoGet()->status;

        switch ($status) {
            case 'waiting':
                // Zoom does not allow for a new meeting to be started on first participant join.
                // It requires the host to start the meeting first.
                // Therefore for global meetings we must make the first participant the host
                // that is use start_url rather than join_url.
                // the participant will not be registered and will appear as the Zoom user account owner.
                // For course and user meetings, only the host can start the meeting.
                if ($meeting->isGlobalMeeting() && $this->get('enableGlobalConference')
                    || $meeting->getUser() === api_get_user_entity(api_get_user_id())) {
                    return $meeting->getMeetingInfoGet()->start_url;
                }
                break;
            case 'started':
                if ('true' === $this->get('enableParticipantRegistration') && $meeting->requiresRegistration()) {
                    // the participant must be registered
                    $participant = api_get_user_entity(api_get_user_id());
                    $registrant = $meeting->getRegistrant($participant);
                    if (null != $registrant) {
                        // the participant is registered
                        return $registrant->getCreatedRegistration()->join_url;
                    }
                    // the participant is not registered, he can join only the global meeting (automatic registration)
                    if ($meeting->isGlobalMeeting() && $this->get('enableGlobalConference')) {
                        return $this->registerUser($meeting, $participant)->getCreatedRegistration()->join_url;
                    }
                }
                break;
        }

        return null;
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
        foreach (RecordingList::loadPeriodRecordings($startDate, $endDate) as $recordingMeeting) {
            $recordingEntity = $this->getRecordingRepository()->find($recordingMeeting->uuid);
            if (is_null($recordingEntity)) {
                $recordingEntity = new RecordingEntity();
                $meetingEntity = $this->getMeetingRepository()->find($recordingMeeting->id);
                if (is_null($meetingEntity)) {
                    try {
                        $meetingInfoGet = MeetingInfoGet::fromId($recordingMeeting->id);
                    } catch (Exception $exception) {
                        $meetingInfoGet = null; // deleted meeting with recordings
                    }
                    if (!is_null($meetingInfoGet)) {
                        $meetingEntity = $this->createMeetingFromMeetingEntity(
                            (new MeetingEntity())->setMeetingInfoGet($meetingInfoGet)
                        );
                        Database::getManager()->persist($meetingEntity);
                    }
                }
                if (!is_null($meetingEntity)) {
                    $recordingEntity->setMeeting($meetingEntity);
                }
            }
            $recordingEntity->setRecordingMeeting($recordingMeeting);
            Database::getManager()->persist($recordingEntity);
        }
        Database::getManager()->flush();
    }

    public function getToolbar($returnUrl = '')
    {
        if (!api_is_platform_admin()) {
            return '';
        }

        $actionsLeft = '';
        $back = '';

        if ('true' === api_get_plugin_setting('zoom', 'enableGlobalConference')) {
            $actionsLeft .=
                Display::url(
                    Display::return_icon('links.png', $this->get_lang('GlobalMeeting'), null, ICON_SIZE_MEDIUM),
                    api_get_path(WEB_PLUGIN_PATH).'zoom/admin.php'
                )
            ;
        }

        if ('true' === api_get_plugin_setting('zoom', 'enableGlobalConferencePerUser')) {
            $actionsLeft .=
                Display::url(
                    Display::return_icon('user.png', $this->get_lang('GlobalMeetingPerUser'), null, ICON_SIZE_MEDIUM),
                    api_get_path(WEB_PLUGIN_PATH).'zoom/user.php'
                )
            ;
        }

        if (!empty($returnUrl)) {
            $back = Display::url(
                Display::return_icon('back.png', get_lang('Back'), null, ICON_SIZE_MEDIUM),
                $returnUrl
            );
        }

        $actionsLeft .=
            Display::url(
            Display::return_icon('settings.png', get_lang('Settings'), null, ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'admin/configure_plugin.php?name=zoom'
            ).$back
        ;

        return Display::toolbarAction('toolbar', [$actionsLeft]);
    }

    /**
     * Creates a meeting on Zoom servers and stores it in the local database.
     *
     * @param MeetingEntity $meeting a new, unsaved meeting with at least a type and a topic
     *
     * @throws Exception
     *
     * @return MeetingEntity
     */
    private function createMeetingFromMeetingEntity($meeting)
    {
        $approvalType = $meeting->getMeetingInfoGet()->settings->approval_type;
        $meeting->getMeetingInfoGet()->settings->auto_recording = 'true' === $this->get('enableCloudRecording')
            ? 'cloud'
            : 'local';
        $meeting->getMeetingInfoGet()->settings->registrants_email_notification = false;
        $meeting->setMeetingInfoGet($meeting->getMeetingInfoGet()->create());

        $meeting->getMeetingInfoGet()->settings->approval_type = $approvalType;

        Database::getManager()->persist($meeting);
        Database::getManager()->flush();

        return $meeting;
    }

    /**
     * @throws Exception
     *
     * @return MeetingEntity
     */
    private function createGlobalMeeting()
    {
        $meetingInfoGet = MeetingInfoGet::fromTopicAndType(
            $this->get_lang('GlobalMeeting'),
            MeetingInfoGet::TYPE_SCHEDULED
        );
        $meetingInfoGet->start_time = (new DateTime())->format(DateTimeInterface::ISO8601);
        $meetingInfoGet->duration = 60;
        $meetingInfoGet->settings->approval_type =
            ('true' === $this->get('enableParticipantRegistration'))
                ? MeetingSettings::APPROVAL_TYPE_AUTOMATICALLY_APPROVE
                : MeetingSettings::APPROVAL_TYPE_NO_REGISTRATION_REQUIRED;
        // $meetingInfoGet->settings->host_video = true;
        $meetingInfoGet->settings->participant_video = true;
        $meetingInfoGet->settings->join_before_host = true;
        $meetingInfoGet->settings->registrants_email_notification = false;

        return $this->createMeetingFromMeetingEntity((new MeetingEntity())->setMeetingInfoGet($meetingInfoGet));
    }

    /**
     * Schedules a meeting and returns it.
     * set $course, $session and $user to null in order to create a global meeting.
     *
     * @param User|null    $user      the current user, for a course meeting or a user meeting
     * @param Course|null  $course    the course, for a course meeting
     * @param Session|null $session   the session, for a course meeting
     * @param DateTime     $startTime meeting local start date-time (configure local timezone on your Zoom account)
     * @param int          $duration  in minutes
     * @param string       $topic     short title of the meeting, required
     * @param string       $agenda    ordre du jour
     * @param string       $password  meeting password
     *
     * @throws Exception
     *
     * @return MeetingEntity meeting
     */
    private function scheduleMeeting($user, $course, $session, $startTime, $duration, $topic, $agenda, $password)
    {
        $meetingInfoGet = MeetingInfoGet::fromTopicAndType($topic, MeetingInfoGet::TYPE_SCHEDULED);
        $meetingInfoGet->duration = $duration;
        $meetingInfoGet->start_time = $startTime->format(DateTimeInterface::ISO8601);
        $meetingInfoGet->agenda = $agenda;
        $meetingInfoGet->password = $password;
        $meetingInfoGet->settings->approval_type = MeetingSettings::APPROVAL_TYPE_NO_REGISTRATION_REQUIRED;
        if ('true' === $this->get('enableParticipantRegistration')) {
            $meetingInfoGet->settings->approval_type = MeetingSettings::APPROVAL_TYPE_AUTOMATICALLY_APPROVE;
        }

        return $this->createMeetingFromMeetingEntity(
            (new MeetingEntity())
                ->setMeetingInfoGet($meetingInfoGet)
                ->setUser($user)
                ->setCourse($course)
                ->setSession($session)
        );
    }

    /**
     * Starts a new instant meeting and redirects to its start url.
     *
     * @param string       $topic
     * @param User|null    $user
     * @param Course|null  $course
     * @param Session|null $session
     *
     * @throws Exception
     */
    private function startInstantMeeting($topic, $user = null, $course = null, $session = null)
    {
        $meeting = $this->createMeetingFromMeetingEntity(
            (new MeetingEntity())
                ->setMeetingInfoGet(MeetingInfoGet::fromTopicAndType($topic, MeetingInfoGet::TYPE_INSTANT))
                ->setUser($user)
                ->setCourse($course)
                ->setSession($session)
        );
        api_location($meeting->getMeetingInfoGet()->start_url);
    }

    /**
     * @param MeetingEntity $meetingEntity
     * @param User          $user
     * @param bool          $andFlush
     *
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return RegistrantEntity
     */
    private function registerUser($meetingEntity, $user, $andFlush = true)
    {
        if (empty($user->getEmail())) {
            throw new Exception($this->get_lang('CannotRegisterWithoutEmailAddress'));
        }
        $meetingRegistrant = MeetingRegistrant::fromEmailAndFirstName(
            $user->getEmail(),
            $user->getFirstname(),
            $user->getLastname()
        );

        $registrantEntity = (new RegistrantEntity())
            ->setMeeting($meetingEntity)
            ->setUser($user)
            ->setMeetingRegistrant($meetingRegistrant)
            ->setCreatedRegistration($meetingEntity->getMeetingInfoGet()->addRegistrant($meetingRegistrant));
        Database::getManager()->persist($registrantEntity);
        if ($andFlush) {
            Database::getManager()->flush($registrantEntity);
        }

        return $registrantEntity;
    }

    /**
     * Register users to a meeting.
     *
     * @param MeetingEntity $meetingEntity
     * @param User[]        $users
     *
     * @throws OptimisticLockException
     *
     * @return User[] failed registrations [ user id => errorMessage ]
     */
    private function registerUsers($meetingEntity, $users)
    {
        $failedUsers = [];
        foreach ($users as $user) {
            try {
                $this->registerUser($meetingEntity, $user, false);
            } catch (Exception $exception) {
                $failedUsers[$user->getId()] = $exception->getMessage();
            }
        }
        Database::getManager()->flush();

        return $failedUsers;
    }

    /**
     * Registers all the course users to a course meeting.
     *
     * @param MeetingEntity $meetingEntity
     *
     * @throws OptimisticLockException
     */
    private function registerAllCourseUsers($meetingEntity)
    {
        $this->registerUsers($meetingEntity, $meetingEntity->getRegistrableUsers());
    }

    /**
     * Removes registrants from a meeting.
     *
     * @param MeetingEntity      $meetingEntity
     * @param RegistrantEntity[] $registrants
     *
     * @throws Exception
     */
    private function unregister($meetingEntity, $registrants)
    {
        $meetingRegistrants = [];
        foreach ($registrants as $registrant) {
            $meetingRegistrants[] = $registrant->getMeetingRegistrant();
        }
        $meetingEntity->getMeetingInfoGet()->removeRegistrants($meetingRegistrants);
        foreach ($registrants as $registrant) {
            Database::getManager()->remove($registrant);
        }
        Database::getManager()->flush();
    }

    /**
     * Updates meeting registrants list. Adds the missing registrants and removes the extra.
     *
     * @param MeetingEntity $meetingEntity
     * @param User[]        $users         list of users to be registered
     *
     * @throws Exception
     */
    private function updateRegistrantList($meetingEntity, $users)
    {
        $usersToAdd = [];
        foreach ($users as $user) {
            $found = false;
            foreach ($meetingEntity->getRegistrants() as $registrant) {
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
        foreach ($meetingEntity->getRegistrants() as $registrant) {
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
        $this->registerUsers($meetingEntity, $usersToAdd);
        $this->unregister($meetingEntity, $registrantsToRemove);
    }
}
