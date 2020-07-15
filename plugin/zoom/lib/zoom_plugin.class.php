<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\API\CreatedRegistration;
use Chamilo\PluginBundle\Zoom\API\JWTClient;
use Chamilo\PluginBundle\Zoom\API\MeetingSettings;
use Chamilo\PluginBundle\Zoom\API\ParticipantList;
use Chamilo\PluginBundle\Zoom\API\ParticipantListItem;
use Chamilo\PluginBundle\Zoom\API\PastMeeting;
use Chamilo\PluginBundle\Zoom\CourseMeeting;
use Chamilo\PluginBundle\Zoom\CourseMeetingInfoGet;
use Chamilo\PluginBundle\Zoom\CourseMeetingList;
use Chamilo\PluginBundle\Zoom\CourseMeetingListItem;
use Chamilo\PluginBundle\Zoom\File;
use Chamilo\PluginBundle\Zoom\Recording;
use Chamilo\PluginBundle\Zoom\RecordingList;
use Chamilo\PluginBundle\Zoom\UserMeetingRegistrant;
use Chamilo\PluginBundle\Zoom\UserMeetingRegistrantListItem;
use Doctrine\Common\Collections\Criteria;

/**
 * Class ZoomPlugin. Integrates Zoom meetings in courses.
 */
class ZoomPlugin extends Plugin
{
    public $isCoursePlugin = true;

    protected function __construct()
    {
        parent::__construct(
            '0.0.1',
            'Sébastien Ducoulombier',
            [
                'tool_enable' => 'boolean',
                'apiKey' => 'text',
                'apiSecret' => 'text',
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
            ]
        );

        $this->isAdminPlugin = true;
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
     * Creates this plugin's related data and data structure in the internal database.
     */
    public function install()
    {
        $this->install_course_fields_in_all_courses();
    }

    /**
     * Drops this plugins' related data from the internal database.
     */
    public function uninstall()
    {
        $this->uninstall_course_fields_in_all_courses();
    }

    /**
     * Generates the search form to include in the meeting list administration page.
     * The form has DatePickers 'start' and 'end' and a Radio 'type'.
     *
     * @return FormValidator
     */
    public function getAdminSearchForm()
    {
        $form = new FormValidator('search');
        $startDatePicker = $form->addDatePicker('start', get_lang('StartDate'));
        $endDatePicker = $form->addDatePicker('end', get_lang('EndDate'));
        $typeSelect = $form->addRadio(
            'type',
            get_lang('Type'),
            [
                CourseMeetingList::TYPE_SCHEDULED => get_lang('ScheduledMeetings'),
                CourseMeetingList::TYPE_LIVE => get_lang('LiveMeetings'),
                CourseMeetingList::TYPE_UPCOMING => get_lang('UpcomingMeetings'),
            ]
        );
        $form->addButtonSearch(get_lang('Search'));
        $oneMonth = new DateInterval('P1M');
        if ($form->validate()) {
            try {
                $start = new DateTime($startDatePicker->getValue());
            } catch (Exception $exception) {
                $start = new DateTime();
                $start->sub($oneMonth);
            }
            try {
                $end = new DateTime($endDatePicker->getValue());
            } catch (Exception $exception) {
                $end = new DateTime();
                $end->add($oneMonth);
            }
            $type = $typeSelect->getValue();
        } else {
            $start = new DateTime();
            $start->sub($oneMonth);
            $end = new DateTime();
            $end->add($oneMonth);
            $type = CourseMeetingList::TYPE_SCHEDULED;
        }
        try {
            $form->setDefaults([
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'type' => $type,
            ]);
        } catch (Exception $exception) {
            error_log(join(':', [__FILE__, __LINE__, $exception]));
        }

        return $form;
    }

    /**
     * Generates a meeting edit form and updates the meeting on validation.
     *
     * @param CourseMeetingInfoGet $meeting the meeting
     *
     * @return FormValidator
     */
    public function getEditMeetingForm(&$meeting)
    {
        $form = new FormValidator('edit', 'post', $_SERVER['REQUEST_URI']);
        $withTimeAndDuration = $meeting::TYPE_SCHEDULED === $meeting->type
            || $meeting::TYPE_RECURRING_WITH_FIXED_TIME === $meeting->type;
        if ($withTimeAndDuration) {
            $startTimeDatePicker = $form->addDateTimePicker('start_time', get_lang('StartTime'));
            $form->setRequired($startTimeDatePicker);
            $durationNumeric = $form->addNumeric('duration', get_lang('DurationInMinutes'));
            $form->setRequired($durationNumeric);
        }
        $topicText = $form->addText('topic', get_lang('Topic'));
        $agendaTextArea = $form->addTextarea('agenda', get_lang('Agenda'), ['maxlength' => 2000]);
        // $passwordText = $form->addText('password', get_lang('Password'), false, ['maxlength' => '10']);
        $form->addButtonUpdate(get_lang('UpdateMeeting'));
        if ($form->validate()) {
            if ($withTimeAndDuration) {
                $meeting->start_time = $startTimeDatePicker->getValue();
                $meeting->timezone = date_default_timezone_get();
                $meeting->duration = $durationNumeric->getValue();
            }
            $meeting->topic = $topicText->getValue();
            $meeting->agenda = $agendaTextArea->getValue();
            try {
                $this->updateMeeting($meeting);
                Display::addFlash(
                    Display::return_message(get_lang('MeetingUpdated'), 'confirm')
                );
            } catch (Exception $exception) {
                Display::addFlash(
                    Display::return_message($exception->getMessage(), 'error')
                );
            }
            $meeting = $this->getMeeting($meeting->id);
        }
        $defaults = [
            'topic' => $meeting->topic,
            'agenda' => $meeting->agenda,
        ];
        if ($withTimeAndDuration) {
            $defaults['start_time'] = $meeting->startDateTime->format('c');
            $defaults['duration'] = $meeting->duration;
        }
        $form->setDefaults($defaults);

        return $form;
    }

    /**
     * Generates a meeting delete form and deletes the meeting on validation.
     *
     * @param CourseMeetingInfoGet $meeting
     * @param string               $returnURL where to redirect to on successful deletion
     *
     * @return FormValidator
     */
    public function getDeleteMeetingForm($meeting, $returnURL)
    {
        $form = new FormValidator('delete', 'post', $_SERVER['REQUEST_URI']);
        $form->addButtonDelete(get_lang('DeleteMeeting'));
        if ($form->validate()) {
            try {
                $this->deleteMeeting($meeting);
                Display::addFlash(
                    Display::return_message(get_lang('MeetingDeleted'), 'confirm')
                );
                location($returnURL);
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
     * @param CourseMeetingInfoGet $meeting
     *
     * @return array a list of two elements:
     *               FormValidator the form
     *               UserMeetingRegistrantListItem[] the up-to-date list of registrants
     */
    public function getRegisterParticipantForm($meeting)
    {
        $form = new FormValidator('register', 'post', $_SERVER['REQUEST_URI']);
        $userIdSelect = $form->addSelect('userIds', get_lang('RegisteredUsers'));
        $userIdSelect->setMultiple(true);
        $form->addButtonSend(get_lang('UpdateRegisteredUserList'));

        $users = $meeting->getCourseAndSessionUsers();
        foreach ($users as $user) {
            $userIdSelect->addOption(api_get_person_name($user->getFirstname(), $user->getLastname()), $user->getId());
        }

        try {
            $registrants = $this->getRegistrants($meeting);
        } catch (Exception $exception) {
            Display::addFlash(
                Display::return_message($exception->getMessage(), 'error')
            );
            $registrants = [];
        }

        if ($form->validate()) {
            $selectedUserIds = $userIdSelect->getValue();
            $selectedUsers = [];
            foreach ($users as $user) {
                if (in_array($user->getId(), $selectedUserIds)) {
                    $selectedUsers[] = $user;
                }
            }
            try {
                $this->updateRegistrantList($meeting, $selectedUsers);
                Display::addFlash(
                    Display::return_message(get_lang('RegisteredUserListWasUpdated'), 'confirm')
                );
            } catch (Exception $exception) {
                Display::addFlash(
                    Display::return_message($exception->getMessage(), 'error')
                );
            }
            try {
                $registrants = $this->getRegistrants($meeting);
            } catch (Exception $exception) {
                Display::addFlash(
                    Display::return_message($exception->getMessage(), 'error')
                );
                $registrants = [];
            }
        }
        $registeredUserIds = [];
        foreach ($registrants as $registrant) {
            $registeredUserIds[] = $registrant->userId;
        }
        $userIdSelect->setSelected($registeredUserIds);


        return [ $form, $registrants ];
    }

    /**
     * Generates a meeting recording files management form.
     * Takes action on validation.
     *
     * @param CourseMeetingInfoGet $meeting
     *
     * @return array a list of two elements:
     *               FormValidator the form
     *               Recording[] the up-to-date list of recordings
     */
    public function getFileForm($meeting)
    {
        $form = new FormValidator('fileForm', 'post', $_SERVER['REQUEST_URI']);
        try {
            $recordings = $this->getMeetingRecordings($meeting);
        } catch (Exception $exception) {
            Display::addFlash(
                Display::return_message($exception->getMessage(), 'error')
            );
            $recordings = [];
        }
        if (!empty($recordings)) {
            $fileIdSelect = $form->addSelect('fileIds', get_lang('Files'));
            $fileIdSelect->setMultiple(true);
            foreach ($recordings as &$recording) {
                // $recording->instanceDetails = $plugin->getPastMeetingInstanceDetails($instance->uuid);
                $options = [];
                foreach ($recording->recording_files as $file) {
                    $options[] = [
                        'text' => sprintf(
                            '%s.%s (%s)',
                            $file->recording_type,
                            $file->file_type,
                            $file->formattedFileSize
                        ),
                        'value' => $file->id,
                    ];
                }
                $fileIdSelect->addOptGroup(
                    $options,
                    sprintf("%s (%s)", $recording->formattedStartTime, $recording->formattedDuration)
                );
            }
            $actionRadio = $form->addRadio(
                'action',
                get_lang('Action'),
                [
                    'CreateLinkInCourse' => get_lang('CreateLinkInCourse'),
                    'CopyToCourse' => get_lang('CopyToCourse'),
                    'DeleteFile' => get_lang('DeleteFile'),
                ]
            );
            $form->addButtonUpdate(get_lang('DoIt'));
            if ($form->validate()) {
                foreach ($recordings as $recording) {
                    foreach ($recording->recording_files as $file) {
                        if (in_array($file->id, $fileIdSelect->getValue())) {
                            $name = sprintf(
                                get_lang('XRecordingOfMeetingXFromXDurationXDotX'),
                                $file->recording_type,
                                $meeting->id,
                                $recording->formattedStartTime,
                                $recording->formattedDuration,
                                $file->file_type
                            );
                            if ('CreateLinkInCourse' === $actionRadio->getValue()) {
                                try {
                                    $this->createLinkToFileInCourse($meeting, $file, $name);
                                    Display::addFlash(
                                        Display::return_message(get_lang('LinkToFileWasCreatedInCourse'), 'success')
                                    );
                                } catch (Exception $exception) {
                                    Display::addFlash(
                                        Display::return_message($exception->getMessage(), 'error')
                                    );
                                }
                            } elseif ('CopyToCourse' === $actionRadio->getValue()) {
                                try {
                                    $this->copyFileToCourse($meeting, $file, $name);
                                    Display::addFlash(
                                        Display::return_message(get_lang('FileWasCopiedToCourse'), 'confirm')
                                    );
                                } catch (Exception $exception) {
                                    Display::addFlash(
                                        Display::return_message($exception->getMessage(), 'error')
                                    );
                                }
                            } elseif ('DeleteFile' === $actionRadio->getValue()) {
                                try {
                                    $this->deleteFile($file);
                                    Display::addFlash(
                                        Display::return_message(get_lang('FileWasDeleted'), 'confirm')
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
                try {
                    $recordings = $this->getMeetingRecordings($meeting);
                } catch (Exception $exception) {
                    Display::addFlash(
                        Display::return_message($exception->getMessage(), 'error')
                    );
                    $recordings = [];
                }
            }
        }

        return [$form, $recordings];
    }

    /**
     * Generates a form to fast and easily create and start an instant meeting.
     * On validation, create it then redirect to it and exit.
     *
     * @return FormValidator
     */
    public function getCreateInstantMeetingForm()
    {
        $form = new FormValidator('createInstantMeetingForm', 'post', '', '_blank');
        $form->addButton('startButton', get_lang('StartInstantMeeting'));

        if ($form->validate()) {
            try {
                $newInstantMeeting = $this->createInstantMeeting();
                location($newInstantMeeting->start_url);
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
     * On validation, creates it.
     *
     * @throws Exception
     *
     * @return FormValidator
     */
    public function getScheduleMeetingForm()
    {
        $form = new FormValidator('scheduleMeetingForm');
        $startTimeDatePicker = $form->addDateTimePicker('start_time', get_lang('StartTime'));
        $form->setRequired($startTimeDatePicker);
        $durationNumeric = $form->addNumeric('duration', get_lang('DurationInMinutes'));
        $form->setRequired($durationNumeric);
        $topicText = $form->addText('topic', get_lang('Topic'), true);
        $agendaTextArea = $form->addTextarea('agenda', get_lang('Agenda'), ['maxlength' => 2000]);
        // $passwordText = $form->addText('password', get_lang('Password'), false, ['maxlength' => '10']);
        $registrationOptions = [
            'RegisterAllCourseUsers' => get_lang('RegisterAllCourseUsers'),
        ];
        $groups = GroupManager::get_groups();
        if (!empty($groups)) {
            $registrationOptions['RegisterTheseGroupMembers'] = get_lang('RegisterTheseGroupMembers');
        }
        $registrationOptions['RegisterNoUser'] = get_lang('RegisterNoUser');
        $userRegistrationRadio = $form->addRadio(
            'userRegistration',
            get_lang('UserRegistration'),
            $registrationOptions
        );
        $groupOptions = [];
        foreach ($groups as $group) {
            $groupOptions[$group['id']] = $group['name'];
        }
        $groupIdsSelect = $form->addSelect(
            'groupIds',
            get_lang('RegisterTheseGroupMembers'),
            $groupOptions
        );
        $groupIdsSelect->setMultiple(true);
        if (!empty($groups)) {
            $jsCode = sprintf("getElementById('%s').parentNode.parentNode.parentNode.style.display = getElementById('%s').checked ? 'block' : 'none'",
                $groupIdsSelect->getAttribute('id'),
                $userRegistrationRadio->getelements()[1]->getAttribute('id')
            );

            $form->setAttribute('onchange', $jsCode);
        }
        $form->addButtonCreate(get_lang('ScheduleTheMeeting'));

        // meeting scheduling
        if ($form->validate()) {
            try {
                $newMeeting = $this->createScheduledMeeting(
                    new DateTime($startTimeDatePicker->getValue()),
                    $durationNumeric->getValue(),
                    $topicText->getValue(),
                    $agendaTextArea->getValue(),
                    '' // $passwordText->getValue()
                );
                Display::addFlash(
                    Display::return_message(get_lang('NewMeetingCreated'))
                );
                if ('RegisterAllCourseUsers' == $userRegistrationRadio->getValue()) {
                    $this->addRegistrants($newMeeting, $newMeeting->getCourseAndSessionUsers());
                    Display::addFlash(
                        Display::return_message(get_lang('AllCourseUsersWereRegistered'))
                    );
                } elseif ('RegisterTheseGroupMembers' == $userRegistrationRadio->getValue()) {
                    $userIds = [];
                    foreach ($groupIdsSelect->getValue() as $groupId) {
                        $userIds = array_unique(array_merge($userIds, GroupManager::get_users($groupId)));
                    }
                    $users = Database::getManager()->getRepository(
                        'ChamiloUserBundle:User'
                    )->matching(Criteria::create()->where(Criteria::expr()->in('id', $userIds)))->getValues();
                    $this->addRegistrants($newMeeting, $users);
                }
                location('meeting_from_start.php?meetingId='.$newMeeting->id);
            } catch (Exception $exception) {
                Display::addFlash(
                    Display::return_message($exception->getMessage(), 'error')
                );
            }
        } else {
            $form->setDefaults(
                [
                    'duration' => 60,
                    'topic' => api_get_course_info()['title'],
                    'userRegistration' => 'RegisterAllCourseUsers',
                ]
            );
        }

        return $form;
    }

    /**
     * Retrieves information about meetings having a start_time between two dates.
     *
     * @param string   $type      MeetingList::TYPE_LIVE, MeetingList::TYPE_SCHEDULED or MeetingList::TYPE_UPCOMING
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @throws Exception on API error
     *
     * @return CourseMeetingListItem[] matching meetings
     */
    public function getPeriodMeetings($type, $startDate, $endDate)
    {
        $matchingMeetings = [];
        /** @var CourseMeetingListItem $meeting */
        foreach (CourseMeetingList::loadMeetings($this->jwtClient(), $type) as $meeting) {
            if (property_exists($meeting, 'start_time')) {
                if ($startDate <= $meeting->startDateTime && $meeting->startDateTime <= $endDate) {
                    $meeting->loadCourse();
                    $meeting->loadSession();
                    $matchingMeetings[] = $meeting;
                }
            }
        }

        return $matchingMeetings;
    }

    /**
     * @return bool whether the logged-in user can manage conferences in this context, that is either
     *              the current course or session coach, the platform admin or the current course admin
     */
    public function userIsConferenceManager()
    {
        return api_is_coach()
            || api_is_platform_admin()
            || api_get_course_id() && api_is_course_admin();
    }

    /**
     * Retrieves a meeting.
     *
     * @param int $id the meeting numeric identifier
     *
     * @throws Exception
     *
     * @return CourseMeetingInfoGet
     */
    public function getMeeting($id)
    {
        return CourseMeetingInfoGet::fromId($this->jwtClient(), $id);
    }

    /**
     * Retrieves a past meeting instance details.
     *
     * @param string $instanceUUID
     *
     * @throws Exception
     *
     * @return PastMeeting
     */
    public function getPastMeetingInstanceDetails($instanceUUID)
    {
        return PastMeeting::fromUUID($this->jwtClient(), $instanceUUID);
    }

    /**
     * Retrieves all live meetings linked to current course and session.
     *
     * @throws Exception on API error
     *
     * @return CourseMeetingListItem[] matching meetings
     */
    public function getLiveMeetings()
    {
        return $this->getMeetings(CourseMeetingList::TYPE_LIVE);
    }

    /**
     * Retrieves all scheduled meetings linked to current course and session.
     *
     * @throws Exception on API error
     *
     * @return CourseMeetingListItem[] matching meetings
     */
    public function getScheduledMeetings()
    {
        return $this->getMeetings(CourseMeetingList::TYPE_SCHEDULED);
    }

    /**
     * Retrieves all upcoming meetings linked to current course and session.
     *
     * @throws Exception on API error
     *
     * @return CourseMeetingListItem[] matching meetings
     */
    public function getUpcomingMeetings()
    {
        return $this->getMeetings(CourseMeetingList::TYPE_UPCOMING);
    }

    /**
     * Creates an instant meeting and returns it.
     *
     * @throws Exception describing the error (message and code)
     *
     * @return CourseMeetingInfoGet meeting
     */
    public function createInstantMeeting()
    {
        // default meeting topic is based on session name, course title and current date
        $topic = '';
        $sessionName = api_get_session_name();
        if ($sessionName) {
            $topic = $sessionName.', ';
        }
        $courseInfo = api_get_course_info();
        $topic .= $courseInfo['title'].', '.date('yy-m-d H:i');
        $meeting = CourseMeeting::fromCourseSessionTopicAndType(
            api_get_course_int_id(),
            api_get_session_id(),
            $topic,
            CourseMeeting::TYPE_INSTANT
        );

        return $this->createMeeting($meeting);
    }

    /**
     * Schedules a meeting and returns it.
     *
     * @param DateTime $startTime meeting local start date-time (configure local timezone on your Zoom account)
     * @param int      $duration  in minutes
     * @param string   $topic     short title of the meeting, required
     * @param string   $agenda    ordre du jour
     * @param string   $password  meeting password
     *
     * @throws Exception describing the error (message and code)
     *
     * @return CourseMeetingInfoGet meeting
     */
    public function createScheduledMeeting($startTime, $duration, $topic, $agenda = '', $password = '')
    {
        $meeting = CourseMeeting::fromCourseSessionTopicAndType(
            api_get_course_int_id(),
            api_get_session_id(),
            $topic,
            CourseMeeting::TYPE_SCHEDULED
        );
        $meeting->duration = $duration;
        $meeting->start_time = $startTime->format(DateTimeInterface::ISO8601);
        $meeting->agenda = $agenda;
        $meeting->password = $password;
        $meeting->settings->approval_type = $this->get('enableParticipantRegistration')
            ? MeetingSettings::APPROVAL_TYPE_AUTOMATICALLY_APPROVE
            : MeetingSettings::APPROVAL_TYPE_NO_REGISTRATION_REQUIRED;

        return $this->createMeeting($meeting);
    }

    /**
     * Updates a meeting.
     *
     * @param CourseMeetingInfoGet $meeting the meeting with updated properties
     *
     * @throws Exception on API error
     */
    public function updateMeeting($meeting)
    {
        $meeting->update($this->jwtClient());
    }

    /**
     * Deletes a meeting.
     *
     * @param CourseMeetingInfoGet $meeting
     *
     * @throws Exception on API error
     */
    public function deleteMeeting($meeting)
    {
        $meeting->delete($this->jwtClient());
    }

    /**
     * Retrieves all recordings from a period of time.
     *
     * @param DateTime $startDate start date
     * @param DateTime $endDate   end date
     *
     * @throws Exception
     *
     * @return Recording[] all recordings
     */
    public function getRecordings($startDate, $endDate)
    {
        return RecordingList::loadRecordings($this->jwtClient(), $startDate, $endDate);
    }

    /**
     * Retrieves a meetings instances' recordings.
     *
     * @param CourseMeetingInfoGet $meeting
     *
     * @throws Exception
     *
     * @return Recording[] meeting instances' recordings
     */
    public function getMeetingRecordings($meeting)
    {
        $interval = new DateInterval('P1M');
        $startDate = clone $meeting->startDateTime;
        $startDate->sub($interval);
        $endDate = clone $meeting->startDateTime;
        $endDate->add($interval);
        $recordings = [];
        foreach ($this->getRecordings($startDate, $endDate) as $recording) {
            if ($recording->id == $meeting->id) {
                $recordings[] = $recording;
            }
        }

        return $recordings;
    }

    /**
     * Retrieves a meeting instance's participants.
     *
     * @param string $instanceUUID the meeting instance UUID
     *
     * @throws Exception
     *
     * @return ParticipantListItem[]
     */
    public function getParticipants($instanceUUID)
    {
        return ParticipantList::loadInstanceParticipants($this->jwtClient(), $instanceUUID);
    }

    /**
     * Retrieves a meeting's registrants.
     *
     * @param CourseMeetingInfoGet $meeting
     *
     * @throws Exception
     *
     * @return UserMeetingRegistrantListItem[] the meeting registrants
     */
    public function getRegistrants($meeting)
    {
        return $meeting->getUserRegistrants($this->jwtClient());
    }

    /**
     * Registers users to a meeting.
     *
     * @param CourseMeetingInfoGet              $meeting
     * @param \Chamilo\UserBundle\Entity\User[] $users
     *
     * @throws Exception
     *
     * @return CreatedRegistration[] the created registrations
     */
    public function addRegistrants($meeting, $users)
    {
        $createdRegistrations = [];
        foreach ($users as $user) {
            $registrant = UserMeetingRegistrant::fromUser($user);
            $registrant->tagEmail();
            $createdRegistrations[] = $meeting->addRegistrant($this->jwtClient(), $registrant);
        }

        return $createdRegistrations;
    }

    /**
     * Removes registrants from a meeting.
     *
     * @param CourseMeetingInfoGet    $meeting
     * @param UserMeetingRegistrant[] $registrants
     *
     * @throws Exception
     */
    public function removeRegistrants($meeting, $registrants)
    {
        $meeting->removeRegistrants($this->jwtClient(), $registrants);
    }

    /**
     * Updates meeting registrants list. Adds the missing registrants and removes the extra.
     *
     * @param CourseMeetingInfoGet              $meeting
     * @param \Chamilo\UserBundle\Entity\User[] $users   list of users to be registred
     *
     * @throws Exception
     */
    public function updateRegistrantList($meeting, $users)
    {
        $registrants = $this->getRegistrants($meeting);
        $usersToAdd = [];
        foreach ($users as $user) {
            $found = false;
            foreach ($registrants as $registrant) {
                if ($registrant->matches($user->getId())) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $usersToAdd[] = $user;
            }
        }
        $registrantsToRemove = [];
        foreach ($registrants as $registrant) {
            $found = false;
            foreach ($users as $user) {
                if ($registrant->matches($user->getId())) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $registrantsToRemove[] = $registrant;
            }
        }
        $this->addRegistrants($meeting, $usersToAdd);
        $this->removeRegistrants($meeting, $registrantsToRemove);
    }

    /**
     * Adds to the meeting course documents a link to a meeting instance recording file.
     *
     * @param CourseMeetingInfoGet $meeting
     * @param File                 $file
     * @param string               $name
     *
     * @throws Exception
     */
    public function createLinkToFileInCourse($meeting, $file, $name)
    {
        $courseInfo = api_get_course_info_by_id($meeting->courseId);
        if (empty($courseInfo)) {
            throw new Exception('This meeting is not linked to a valid course');
        }
        $path = '/zoom_meeting_recording_file_'.$file->id.'.'.$file->file_type;
        $docId = DocumentManager::addCloudLink($courseInfo, $path, $file->play_url, $name);
        if (!$docId) {
            throw new Exception(
                get_lang(
                    DocumentManager::cloudLinkExists(
                        $courseInfo,
                        $path,
                        $file->play_url
                    ) ? 'UrlAlreadyExists' : 'ErrorAddCloudLink'
                )
            );
        }
    }

    /**
     * Copies a recording file to a meeting's course.
     *
     * @param CourseMeetingInfoGet $meeting
     * @param File                 $file
     * @param string               $name
     *
     * @throws Exception
     */
    public function copyFileToCourse($meeting, $file, $name)
    {
        $courseInfo = api_get_course_info_by_id($meeting->courseId);
        if (empty($courseInfo)) {
            throw new Exception('This meeting is not linked to a valid course');
        }
        $tmpFile = tmpfile();
        if (false === $tmpFile) {
            throw new Exception('tmpfile() returned false');
        }
        $curl = curl_init($file->getFullDownloadURL($this->jwtClient()->token));
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
            $meeting->sessionId,
            true
        );
        fclose($tmpFile);
        if (false === $newPath) {
            throw new Exception('could not handle uploaded document');
        }
    }

    /**
     * Deletes a meeting instance's recordings.
     *
     * @param Recording $recording
     *
     * @throws Exception
     */
    public function deleteRecordings($recording)
    {
        $recording->delete($this->jwtClient());
    }

    /**
     * Deletes a meeting instance recording file.
     *
     * @param File $file
     *
     * @throws Exception
     */
    public function deleteFile($file)
    {
        $file->delete($this->jwtClient());
    }

    /**
     * Caches and returns the JWT client instance, initialized with plugin settings.
     *
     * @return JWTClient object that provides means of communications with the Zoom servers
     */
    protected function jwtClient()
    {
        static $jwtClient = null;
        if (is_null($jwtClient)) {
            $jwtClient = new JWTClient($this->get('apiKey'), $this->get('apiSecret'));
        }

        return $jwtClient;
    }

    /**
     * Retrieves all meetings of a specific type and linked to current course and session.
     *
     * @param string $type MeetingList::TYPE_LIVE, MeetingList::TYPE_SCHEDULED or MeetingList::TYPE_UPCOMING
     *
     * @throws Exception on API error
     *
     * @return CourseMeetingListItem[] matching meetings
     */
    private function getMeetings($type)
    {
        $matchingMeetings = [];
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();
        /** @var CourseMeetingListItem $candidateMeeting */
        foreach (CourseMeetingList::loadMeetings($this->jwtClient(), $type) as $candidateMeeting) {
            if ($candidateMeeting->matches($courseId, $sessionId)) {
                $matchingMeetings[] = $candidateMeeting;
            }
        }

        return $matchingMeetings;
    }

    /**
     * Creates a meeting on the server and returns it.
     *
     * @param CourseMeeting $meeting a meeting with at least a type and a topic
     *
     * @throws Exception describing the error (message and code)
     *
     * @return CourseMeetingInfoGet the new meeting
     */
    private function createMeeting($meeting)
    {
        $meeting->settings->auto_recording = $this->get('enableCloudRecording')
            ? 'cloud'
            : 'local';
        $meeting->settings->registrants_email_notification = false;

        return $meeting->create($this->jwtClient());
    }
}
