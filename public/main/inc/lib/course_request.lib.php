<?php
/* For licensing terms, see /license.txt */

/**
 * Course request manager.
 *
 * @author José Manuel Abuin Mosquera <chema@cesga.es>, 2010
 * @author Bruno Rubio Gayo <brubio@cesga.es>, 2010
 * Centro de Supercomputacion de Galicia (CESGA)
 * @author Ivan Tcholakov <ivantcholakov@gmail.com> (technical adaptation for Chamilo 1.8.8), 2010
 */
class CourseRequestManager
{
    /**
     * Checks whether a given course code has been already occupied.
     *
     * @param string $wanted_course_code the code to be checked
     *
     * @return bool
     *              Returns TRUE if there is created:
     *              - a course with the same code OR visual_code (visualcode).
     *              - a course request with the same code as the given one, or
     *              Othewise returns FALSE.
     */
    public static function course_code_exists($wanted_course_code)
    {
        if ($code_exists = CourseManager::course_code_exists($wanted_course_code)) {
            return $code_exists;
        }
        $table_course_request = Database::get_main_table(TABLE_MAIN_COURSE_REQUEST);
        $wanted_course_code = Database::escape_string($wanted_course_code);
        $sql = sprintf(
            'SELECT COUNT(id) AS number FROM %s WHERE visual_code = "%s"',
            $table_course_request,
            $wanted_course_code
        );
        $result = Database::fetch_array(Database::query($sql));

        return $result['number'] > 0;
    }

    /**
     * Creates a new course request within the database.
     *
     * @param string $wanted_code     the code for the created in the future course
     * @param string $title
     * @param string $description
     * @param string $category_code
     * @param string $course_language
     * @param string $objectives
     * @param string $target_audience
     * @param int    $user_id
     *
     * @return mixed the database id of the newly created course request or FALSE on failure
     */
    public static function create_course_request(
        $wanted_code,
        $title,
        $description,
        $category_code,
        $course_language,
        $objectives,
        $target_audience,
        $user_id,
        $exemplary_content = 0
    ) {
        $wanted_code = trim($wanted_code);
        $user_id = (int) $user_id;
        $exemplary_content = (int) $exemplary_content;

        if ('' == $wanted_code) {
            return false;
        }

        if (self::course_code_exists($wanted_code)) {
            return false;
        }

        if ($user_id <= 0) {
            return false;
        }

        $user_info = api_get_user_info($user_id);
        if (!is_array($user_info)) {
            return false;
        }

        $tutor_name = api_get_person_name($user_info['firstname'], $user_info['lastname'], null, null, $course_language);

        $request_date = api_get_utc_datetime();
        $status = COURSE_REQUEST_PENDING;
        $info = 0;
        $keys = AddCourse::define_course_keys($wanted_code, '');
        if (!count($keys)) {
            return false;
        }
        $visual_code = $keys['currentCourseCode'];
        $code = $keys['currentCourseId'];
        $db_name = isset($keys['currentCourseDbName']) ? $keys['currentCourseDbName'] : null;
        $directory = $keys['currentCourseRepository'];
        // @todo user entity
        $sql = sprintf(
            'INSERT INTO %s (
                code, user_id,
                course_language, title, description, category_code,
                tutor_name, visual_code, request_date,
                objetives, target_audience, status, info, exemplary_content)
            VALUES (
                "%s", "%d", "%s", "%s",
                "%s", "%s", "%s", "%s",
                "%s", "%s", "%s",
                "%s", "%s", "%d");',
            Database::get_main_table(TABLE_MAIN_COURSE_REQUEST),
            Database::escape_string($code),
            $user_id,
            Database::escape_string($course_language),
            Database::escape_string($title),
            Database::escape_string($description),
            Database::escape_string($category_code),
            Database::escape_string($tutor_name),
            Database::escape_string($visual_code),
            Database::escape_string($request_date),
            Database::escape_string($objectives),
            Database::escape_string($target_audience),
            Database::escape_string($status),
            Database::escape_string($info),
            $exemplary_content
        );

        $result_sql = Database::query($sql);

        if (!$result_sql) {
            return false;
        }

        $last_insert_id = Database::insert_id();

        // E-mail notifications.

        // E-mail language: The platform language seems to be the best choice.
        $email_language = api_get_setting('platformLanguage');

        $email_subject = sprintf(get_lang('%s A request for a new course %s', $email_language), '['.api_get_setting('siteName').']', $code);

        $email_body = get_lang('We registered the following request for a new course:', $email_language)."\n\n";
        $email_body .= get_lang('Course name', $email_language).': '.$title."\n";
        $email_body .= get_lang('Category', $email_language).': '.$category_code."\n";
        $email_body .= get_lang('Course code', $email_language).': '.$code."\n";
        $email_body .= get_lang('Teacher', $email_language).': '.api_get_person_name($user_info['firstname'], $user_info['lastname'], null, null, $email_language)."\n";
        $email_body .= get_lang('E-mail', $email_language).': '.$user_info['mail']."\n";
        $email_body .= get_lang('Description', $email_language).': '.$description."\n";
        $email_body .= get_lang('Objectives', $email_language).': '.$objectives."\n";
        $email_body .= get_lang('Target audience', $email_language).': '.$target_audience."\n";
        $email_body .= get_lang('Language', $email_language).': '.$course_language."\n";
        $email_body .= get_lang('Fill with demo content', $email_language).': '.($exemplary_content ? get_lang('Yes', $email_language) : get_lang('No', $email_language))."\n";

        // Sending an e-mail to the platform administrator.
        $email_body_admin = $email_body;
        $email_body_admin .= "\n".get_lang('This course request can be approved on the following page:', $email_language).' '.api_get_path(WEB_CODE_PATH).'admin/course_request_edit.php?id='.$last_insert_id."\n";
        $email_body_admin .= "\n".get_lang('The information about this course request is considered protected; it can be used only to open a new course within our e-learning portal; it should not be revealed to third parties.', $email_language)."\n";

        $sender_name_teacher = api_get_person_name($user_info['firstname'], $user_info['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
        $sender_email_teacher = $user_info['mail'];
        $recipient_name_admin = api_get_person_name(
            api_get_setting('administratorName'),
            api_get_setting('administratorSurname'),
            null,
            PERSON_NAME_EMAIL_ADDRESS
        );
        $recipient_email_admin = api_get_setting('emailAdministrator');

        api_mail_html(
            $recipient_name_admin,
            $recipient_email_admin,
            $email_subject,
            $email_body_admin,
            $sender_name_teacher,
            $sender_email_teacher
        );

        // Sending an e-mail to the request.
        $email_body_teacher = get_lang('Dear', $email_language).' ';
        $email_body_teacher .= api_get_person_name($user_info['firstname'], $user_info['lastname'], null, null, $email_language).",\n\n";
        $email_body_teacher .= $email_body;
        $email_body_teacher .= "\n".get_lang('Formula', $email_language)."\n";
        $email_body_teacher .= api_get_person_name(
                api_get_setting('administratorName'),
                api_get_setting('administratorSurname'),
                null,
                null,
                $email_language
            )."\n";
        $email_body_teacher .= get_lang('Teacher', $email_language).' '.api_get_setting('siteName')."\n";
        $email_body_teacher .= get_lang('Phone', $email_language).': '.api_get_setting('administratorTelephone')."\n";
        $email_body_teacher .= get_lang('E-mail', $email_language).': '.api_get_setting('emailAdministrator', null, $email_language)."\n";
        $email_body_teacher .= "\n".get_lang('The information about this course request is considered protected; it can be used only to open a new course within our e-learning portal; it should not be revealed to third parties.', $email_language)."\n";

        // Swap the sender and the recipient.
        $sender_name_admin = $recipient_name_admin;
        $sender_email_admin = $recipient_email_admin;
        $recipient_name_teacher = $sender_name_teacher;
        $recipient_email_teacher = $sender_email_teacher;

        api_mail_html(
            $recipient_name_teacher,
            $recipient_email_teacher,
            $email_subject,
            $email_body_teacher,
            $sender_name_admin,
            $sender_email_admin,
            null,
            null,
            null
        );

        return $last_insert_id;
    }

    /**
     * Updates a given course request in the database.
     *
     * @param int    $id              the id (an integer number) of the corresponding database record
     * @param string $wanted_code     the code for the created in the future course
     * @param string $title
     * @param string $description
     * @param string $category_code
     * @param string $course_language
     * @param string $objectives
     * @param string $target_audience
     * @param int    $user_id
     *
     * @return bool returns TRUE on success or FALSE on failure
     */
    public static function update_course_request(
        $id,
        $wanted_code,
        $title,
        $description,
        $category_code,
        $course_language,
        $objectives,
        $target_audience,
        $user_id,
        $exemplary_content
    ) {
        $id = (int) $id;
        $wanted_code = trim($wanted_code);
        $user_id = (int) $user_id;
        $exemplary_content = (bool) $exemplary_content ? 1 : 0;

        if ('' == $wanted_code) {
            return false;
        }

        if ($user_id <= 0) {
            return false;
        }

        // Retrieve request data
        $course_request_info = self::get_course_request_info($id);
        if (!is_array($course_request_info)) {
            return false;
        }

        $code = $wanted_code;
        $tutor_name = $course_request_info['tutor_name'];
        $directory = $course_request_info['directory'];
        $visual_code = $course_request_info['visual_code'];
        $request_date = $course_request_info['request_date'];
        $status = $course_request_info['status'];
        $info = $course_request_info['info'];

        if ($wanted_code != $course_request_info['code']) {
            if (self::course_code_exists($wanted_code)) {
                return false;
            }
            $keys = AddCourse::define_course_keys($wanted_code, '');
            if (count($keys)) {
                $visual_code = $keys['currentCourseCode'];
                $code = $keys['currentCourseId'];
                $db_name = $keys['currentCourseDbName'];
                $directory = $keys['currentCourseRepository'];
            } else {
                return false;
            }
        }

        if ($user_id != $course_request_info['code']) {
            $user_info = api_get_user_info($user_id);
            if (is_array($user_info)) {
                $tutor_name = api_get_person_name(
                    $user_info['firstname'],
                    $user_info['lastname'],
                    null,
                    null,
                    $course_language
                );
            } else {
                $user_id = $course_request_info['code'];
            }
        }

        if ($course_language != $course_request_info['course_language']) {
            $user_info = api_get_user_info($user_id);
            if (is_array($user_info)) {
                $tutor_name = api_get_person_name(
                    $user_info['firstname'],
                    $user_info['lastname'],
                    null,
                    null,
                    $course_language
                );
            }
        }

        // @todo use entity
        $sql = sprintf(
            'UPDATE %s SET
                code = "%s", user_id = "%s", directory = "%s", db_name = "%s",
                course_language = "%s", title = "%s", description = "%s", category_code = "%s",
                tutor_name = "%s", visual_code = "%s", request_date = "%s",
                objetives = "%s", target_audience = "%s", status = "%s", info = "%s", exemplary_content = "%s"
            WHERE id = '.$id,
            Database::get_main_table(TABLE_MAIN_COURSE_REQUEST),
            Database::escape_string($code),
            intval($user_id),
            Database::escape_string($directory),
            Database::escape_string($db_name),
            Database::escape_string($course_language),
            Database::escape_string($title),
            Database::escape_string($description),
            Database::escape_string($category_code),
            Database::escape_string($tutor_name),
            Database::escape_string($visual_code),
            Database::escape_string($request_date),
            Database::escape_string($objectives),
            Database::escape_string($target_audience),
            Database::escape_string($status),
            Database::escape_string($info),
            Database::escape_string($exemplary_content)
        );
        $result_sql = Database::query($sql);

        return false !== $result_sql;
    }

    /**
     * Deletes a given course request.
     *
     * @param int $id the id (an integer number) of the corresponding database record
     *
     * @return bool returns TRUE on success or FALSE on failure
     */
    public static function delete_course_request($id)
    {
        $id = (int) $id;
        $sql = "DELETE FROM ".Database::get_main_table(TABLE_MAIN_COURSE_REQUEST)."
                WHERE id = ".$id;
        $result = Database::query($sql);

        return false !== $result;
    }

    /**
     * Returns the number of course requests in the course_request table (optionally matching a status).
     *
     * @param int $status
     *
     * @return bool
     */
    public static function count_course_requests($status = null)
    {
        $course_table = Database::get_main_table(TABLE_MAIN_COURSE_REQUEST);
        if (is_null($status)) {
            $sql = "SELECT COUNT(id) AS number FROM ".$course_table;
        } else {
            $status = (int) $status;
            $sql = "SELECT COUNT(id) AS number FROM ".$course_table."
                    WHERE status = ".$status;
        }
        $result = Database::fetch_array(Database::query($sql));
        if (is_array($result)) {
            return $result['number'];
        }

        return false;
    }

    /**
     * Gets all the information about a course request using its database id as an access key.
     *
     * @param int $id the id (an integer number) of the corresponding database record
     *
     * @return string|bool returns the requested data as an array or FALSE on failure
     */
    public static function get_course_request_info($id)
    {
        $id = (int) $id;
        $sql = "SELECT *
                FROM ".Database::get_main_table(TABLE_MAIN_COURSE_REQUEST)."
                WHERE id = ".$id;
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return Database::fetch_array($result);
        }

        return false;
    }

    /**
     * Gets the code of a given course request using its database id as an access key.
     *
     * @param int $id the id (an integer number) of the corresponding database record
     *
     * @return string|bool returns the requested requested code or FALSE on failure
     */
    public static function get_course_request_code($id)
    {
        $id = (int) $id;
        $sql = "SELECT code
                FROM ".Database::get_main_table(TABLE_MAIN_COURSE_REQUEST)."
                WHERE id = ".$id;
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $result_array = Database::fetch_array($result, 'NUM');
            if (is_array($result_array)) {
                return $result_array[0];
            }
        }

        return false;
    }

    /**
     * Accepts a given by its id course request. The requested course gets created immediately after the request acceptance.
     *
     * @param int $id the id (an integer number) of the corresponding database record
     *
     * @return string|bool returns the code of the newly created course or FALSE on failure
     */
    public static function accept_course_request($id)
    {
        $id = (int) $id;

        // Retrieve request's data
        $course_request_info = self::get_course_request_info($id);
        if (!is_array($course_request_info)) {
            return false;
        }

        // Make all the checks again before the new course creation.
        /*if (CourseManager::course_code_exists($wanted_code)) {
            return false;
        }*/

        $user_id = (int) $course_request_info['user_id'];
        if ($user_id <= 0) {
            return false;
        }

        $user_info = api_get_user_info($user_id);
        if (!is_array($user_info)) {
            return false;
        }

        // Create the requested course
        $params = [];

        $params['title'] = $course_request_info['title'];
        $params['course_language'] = $course_request_info['course_language'];
        $params['exemplary_content'] = intval($course_request_info['exemplary_content']) > 0;
        $params['wanted_code'] = $course_request_info['code'];
        $params['user_id'] = $course_request_info['user_id'];
        $params['tutor_name'] = api_get_person_name($user_info['firstname'], $user_info['lastname']);

        if (!empty($course_request_info['category_code'])) {
            $category = CourseCategory::getCategory($course_request_info['category_code']);
            $categoryId = (int) $category['id'];
            $params['course_categories'] = [$categoryId];
        }

        $course = CourseManager::create_course($params);
        if (null !== $course) {
            // Mark the request as accepted.
            $sql = "UPDATE ".Database::get_main_table(TABLE_MAIN_COURSE_REQUEST)."
                    SET status = ".COURSE_REQUEST_ACCEPTED."
                    WHERE id = ".$id;
            Database::query($sql);

            // E-mail notification.

            // E-mail language: The platform language seems to be the best choice
            $email_language = api_get_setting('platformLanguage');
            $email_subject = sprintf(
                get_lang('%s The course request %s has been approved', $email_language),
                '['.api_get_setting('siteName').']',
                $course->getCode()
            );

            $email_body = get_lang('Dear', $email_language).' ';
            $email_body .= api_get_person_name(
                    $user_info['firstname'],
                    $user_info['lastname'],
                    null,
                    null,
                    $email_language
                ).",\n\n";
            $email_body .= sprintf(
                    get_lang(
                        'Your course request %s has been approved. A new course %s has been created and you are registered in it as a teacher.\n\nYou can access your newly created course from: %s',
                        $email_language
                    ),
                    $course->getCode(),
                    $course->getCode(),
                    api_get_course_url($course->getId())
                )."\n";
            $email_body .= "\n".get_lang('Formula', $email_language)."\n";
            $email_body .= api_get_person_name(
                    api_get_setting('administratorName'),
                    api_get_setting('administratorSurname'),
                    null,
                    null,
                    $email_language
                )."\n";
            $email_body .= get_lang('Teacher', $email_language).' '.api_get_setting('siteName')."\n";
            $email_body .= get_lang('Phone', $email_language).': '.api_get_setting('administratorTelephone')."\n";
            $email_body .= get_lang('E-mail', $email_language).': '.api_get_setting('emailAdministrator', null, $email_language)."\n";
            $email_body .= "\n".get_lang('The information about this course request is considered protected; it can be used only to open a new course within our e-learning portal; it should not be revealed to third parties.', $email_language)."\n";

            $sender_name = api_get_person_name(
                api_get_setting('administratorName'),
                api_get_setting('administratorSurname'),
                null,
                PERSON_NAME_EMAIL_ADDRESS
            );
            $sender_email = api_get_setting('emailAdministrator');
            $recipient_name = api_get_person_name(
                $user_info['firstname'],
                $user_info['lastname'],
                null,
                PERSON_NAME_EMAIL_ADDRESS
            );
            $recipient_email = $user_info['mail'];

            api_mail_html(
                $recipient_name,
                $recipient_email,
                $email_subject,
                $email_body,
                $sender_name,
                $sender_email
            );

            return $course->getCode();
        }

        return false;
    }

    /**
     * Rejects a given course request.
     *
     * @param int $id the id (an integer number) of the corresponding database record
     *
     * @return bool returns TRUE on success or FALSE on failure
     */
    public static function reject_course_request($id)
    {
        $id = (int) $id;
        // Retrieve request's data
        $course_request_info = self::get_course_request_info($id);
        if (!is_array($course_request_info)) {
            return false;
        }

        $user_id = intval($course_request_info['user_id']);
        if ($user_id <= 0) {
            return false;
        }

        $user_info = api_get_user_info($user_id);
        if (!is_array($user_info)) {
            return false;
        }

        $code = $course_request_info['code'];

        $sql = "UPDATE ".Database::get_main_table(TABLE_MAIN_COURSE_REQUEST)."
                SET status = ".COURSE_REQUEST_REJECTED."
                WHERE id = ".$id;
        if (false === Database::query($sql)) {
            return false;
        }

        // E-mail notification.

        // E-mail language: The platform language seems to be the best choice.
        $email_language = api_get_setting('platformLanguage');

        $email_subject = sprintf(get_lang('%s The course request %s has been rejected', $email_language), '['.api_get_setting('siteName').']', $code);

        $email_body = get_lang('Dear', $email_language).' ';
        $email_body .= api_get_person_name($user_info['firstname'], $user_info['lastname'], null, null, $email_language).",\n\n";
        $email_body .= sprintf(get_lang('To our regret we have to inform you that your course request %s has been rejected due to not fulfilling the requirements of our Terms and Conditions.', $email_language), $code)."\n";
        $email_body .= "\n".get_lang('Formula', $email_language)."\n";
        $email_body .= api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, null, $email_language)."\n";
        $email_body .= get_lang('Teacher', $email_language).' '.api_get_setting('siteName')."\n";
        $email_body .= get_lang('Phone', $email_language).': '.api_get_setting('administratorTelephone')."\n";
        $email_body .= get_lang('E-mail', $email_language).': '.api_get_setting('emailAdministrator', null, $email_language)."\n";
        $email_body .= "\n".get_lang('The information about this course request is considered protected; it can be used only to open a new course within our e-learning portal; it should not be revealed to third parties.', $email_language)."\n";

        $sender_name = api_get_person_name(
            api_get_setting('administratorName'),
            api_get_setting('administratorSurname'),
            null,
            PERSON_NAME_EMAIL_ADDRESS
        );
        $sender_email = api_get_setting('emailAdministrator');
        $recipient_name = api_get_person_name(
            $user_info['firstname'],
            $user_info['lastname'],
            null,
            PERSON_NAME_EMAIL_ADDRESS
        );
        $recipient_email = $user_info['mail'];

        api_mail_html(
            $recipient_name,
            $recipient_email,
            $email_subject,
            $email_body,
            $sender_name,
            $sender_email
        );

        return true;
    }

    /**
     * Asks the author (through e-mail) for additional information about the given course request.
     *
     * @param int $id the database primary id of the given request
     *
     * @return bool returns TRUE on success or FALSE on failure
     */
    public static function ask_for_additional_info($id)
    {
        $id = (int) $id;

        // Retrieve request's data
        $course_request_info = self::get_course_request_info($id);
        if (!is_array($course_request_info)) {
            return false;
        }

        $user_id = intval($course_request_info['user_id']);
        if ($user_id <= 0) {
            return false;
        }

        $user_info = api_get_user_info($user_id);
        if (!is_array($user_info)) {
            return false;
        }

        $code = $course_request_info['code'];
        $info = intval($course_request_info['info']);

        // Error is to be returned on a repeated attempt for asking additional information.
        if (!empty($info)) {
            return false;
        }

        // E-mail notification.

        // E-mail language: The platform language seems to be the best choice.
        $email_language = api_get_setting('platformLanguage');
        $email_subject = sprintf(get_lang('%s A request for additional information about the course request %s', $email_language), '['.api_get_setting('siteName').']', $code);

        $email_body = get_lang('Dear', $email_language).' ';
        $email_body .= api_get_person_name($user_info['firstname'], $user_info['lastname'], null, null, $email_language).",\n\n";
        $email_body .= sprintf(get_lang('We have received your request for a new course with code %s. Before we consider it for approval, we need some additional information.\n\nPlease, provide brief information about the course content (description), the objectives, the learners or the users that are to be involved in the proposed course. If it is applicable, mention the name of the institution or the unit on which behalf you made the course request.', $email_language), $code)."\n";
        $email_body .= "\n".get_lang('Formula', $email_language)."\n";
        $email_body .= api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n";
        $email_body .= get_lang('Teacher', $email_language).' '.api_get_setting('siteName')."\n";
        $email_body .= get_lang('Phone', $email_language).': '.api_get_setting('administratorTelephone')."\n";
        $email_body .= get_lang('E-mail', $email_language).': '.api_get_setting('emailAdministrator')."\n";
        $email_body .= "\n".get_lang('The information about this course request is considered protected; it can be used only to open a new course within our e-learning portal; it should not be revealed to third parties.', $email_language)."\n";

        $sender_name = api_get_person_name(
            api_get_setting('administratorName'),
            api_get_setting('administratorSurname'),
            null,
            PERSON_NAME_EMAIL_ADDRESS
        );
        $sender_email = api_get_setting('emailAdministrator');
        $recipient_name = api_get_person_name(
            $user_info['firstname'],
            $user_info['lastname'],
            null,
            PERSON_NAME_EMAIL_ADDRESS
        );
        $recipient_email = $user_info['mail'];

        $result = api_mail_html(
            $recipient_name,
            $recipient_email,
            $email_subject,
            $email_body,
            $sender_name,
            $sender_email
        );

        if (!$result) {
            return false;
        }

        // Marking the fact that additional information about the request has been asked.
        $sql = "UPDATE ".Database::get_main_table(TABLE_MAIN_COURSE_REQUEST)."
                SET info = 1 WHERE id = ".$id;
        $result = false !== Database::query($sql);

        return $result;
    }

    /**
     * Checks whether additional information about the given course request has been asked.
     *
     * @param int $id the database primary id of the given request
     *
     * @return bool returns TRUE if additional information has been asked or FALSE otherwise
     */
    public static function additional_info_asked($id)
    {
        $id = (int) $id;
        $sql = "SELECT id FROM ".Database::get_main_table(TABLE_MAIN_COURSE_REQUEST)."
                WHERE (id = ".$id." AND info > 0)";
        $result = Database::num_rows(Database::query($sql));

        return !empty($result);
    }
}
