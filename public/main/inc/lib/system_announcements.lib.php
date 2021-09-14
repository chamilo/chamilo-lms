<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SysAnnouncement;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;

class SystemAnnouncementManager
{
    /**
     * Adds an announcement to the database.
     *
     * @param string $title           Title of the announcement
     * @param string $content         Content of the announcement
     * @param string $date_start      Start date (YYYY-MM-DD HH:II: SS)
     * @param string $date_end        End date (YYYY-MM-DD HH:II: SS)
     * @param array  $visibility
     * @param string $lang            The language for which the announvement should be shown. Leave null for all
     *                                langages
     * @param int    $send_mail       Whether to send an e-mail to all users (1) or not (0)
     * @param bool   $add_to_calendar
     * @param bool   $sendEmailTest
     * @param int    $careerId
     * @param int    $promotionId
     *
     * @return mixed insert_id on success, false on failure
     */
    public static function add_announcement(
        $title,
        $content,
        $date_start,
        $date_end,
        $visibility,
        $lang = '',
        $send_mail = 0,
        $add_to_calendar = false,
        $sendEmailTest = false,
        $careerId = 0,
        $promotionId = 0
    ) {
        $original_content = $content;
        $a_dateS = explode(' ', $date_start);
        $a_arraySD = explode('-', $a_dateS[0]);
        $a_arraySH = explode(':', $a_dateS[1]);
        $date_start_to_compare = array_merge($a_arraySD, $a_arraySH);

        $a_dateE = explode(' ', $date_end);
        $a_arrayED = explode('-', $a_dateE[0]);
        $a_arrayEH = explode(':', $a_dateE[1]);
        $date_end_to_compare = array_merge($a_arrayED, $a_arrayEH);

        if (!checkdate($date_start_to_compare[1], $date_start_to_compare[2], $date_start_to_compare[0])) {
            Display::addFlash(
                Display::return_message(get_lang('Invalid start date was given.'), 'warning')
            );

            return false;
        }

        if (($date_end_to_compare[1] ||
                $date_end_to_compare[2] ||
                $date_end_to_compare[0]) &&
            !checkdate($date_end_to_compare[1], $date_end_to_compare[2], $date_end_to_compare[0])
        ) {
            Display::addFlash(
                Display::return_message(get_lang('Invalid end date was given.'), 'warning')
            );

            return false;
        }

        if (0 == strlen(trim($title))) {
            Display::addFlash(
                Display::return_message(get_lang('Please enter a title'), 'warning')
            );

            return false;
        }

        $start = api_get_utc_datetime($date_start, null, true);
        $end = api_get_utc_datetime($date_end, null, true);

        //Fixing urls that are sent by email
        //$content = str_replace('src=\"/home/', 'src=\"'.api_get_path(WEB_PATH).'home/', $content);
        //$content = str_replace('file=/home/', 'file='.api_get_path(WEB_PATH).'home/', $content);
        $content = str_replace(
            'src=\"'.api_get_path(REL_HOME_PATH),
            'src=\"'.api_get_path(WEB_PATH).api_get_path(REL_HOME_PATH),
            $content
        );
        $content = str_replace(
            'file='.api_get_path(REL_HOME_PATH),
            'file='.api_get_path(WEB_PATH).api_get_path(REL_HOME_PATH),
            $content
        );
        $lang = is_null($lang) ? '' : $lang;

        $sysRepo = Container::getSysAnnouncementRepository();

        $sysAnnouncement = (new SysAnnouncement())
            ->setTitle($title)
            ->setContent($content)
            ->setDateStart($start)
            ->setDateEnd($end)
            ->setLang($lang)
            ->setUrl(api_get_url_entity())
            ->setRoles($visibility);

        if (api_get_configuration_value('allow_careers_in_global_announcements') && !empty($careerId)) {
            $careerRepo = Container::getCareerRepository();
            $sysAnnouncement->setCareer($careerRepo->find($careerId));

            $promotionRepo = Container::getPromotionRepository();
            $sysAnnouncement->setPromotion($promotionRepo->find($promotionId));
        }

        $sysRepo->update($sysAnnouncement);
        $resultId = $sysAnnouncement->getId();

        if ($resultId) {
            if ($sendEmailTest) {
                self::send_system_announcement_by_email($sysAnnouncement, true);
            } else {
                if (1 == $send_mail) {
                    self::send_system_announcement_by_email($sysAnnouncement);
                }
            }

            if ($add_to_calendar) {
                $agenda = new Agenda('admin');
                $agenda->addEvent(
                    $date_start,
                    $date_end,
                    false,
                    $title,
                    $original_content
                );
            }

            return $resultId;
        }

        return false;
    }

    /**
     * Send a system announcement by e-mail to all teachers/students depending on parameters.
     *
     * @return bool True if the message was sent or there was no destination matching.
     *              False on database or e-mail sending error.
     */
    public static function send_system_announcement_by_email(SysAnnouncement $announcement, bool $sendEmailTest = false)
    {
        $title = $announcement->getTitle();
        $content = $announcement->getContent();
        $language = $announcement->getLang();

        $content = str_replace(['\r\n', '\n', '\r'], '', $content);

        if ($sendEmailTest) {
            MessageManager::send_message_simple(api_get_user_id(), $title, $content);

            return true;
        }

        $repo = Container::getUserRepository();
        $qb = $repo->addRoleListQueryBuilder($announcement->getRoles());
        $repo->addAccessUrlQueryBuilder(api_get_current_access_url_id(), $qb);

        if (!empty($language)) {
            $qb
                ->andWhere('u.locale = :lang')
                ->setParameter('lang', $language);
        }

        $repo->addActiveAndNotAnonUserQueryBuilder($qb);
        $repo->addExpirationDateQueryBuilder($qb);

        // Sent to active users.
        //$sql .= " AND email <>'' AND active = 1 ";

        // Expiration date
        //$sql .= " AND (expiration_date = '' OR expiration_date IS NULL OR expiration_date > '$now') ";

        $userListToFilter = [];
        // @todo check if other filters will apply for the career/promotion option.
        if (null !== $announcement->getCareer()) {
            $promotion = new Promotion();
            $promotionList = $promotion->get_all_promotions_by_career_id($announcement->getCareer()->getId());
            if (null !== $announcement->getPromotion()) {
                $promotionList = [];
                $promotionList[] = $promotion->get($announcement->getPromotion()->getId());
            }

            if (!empty($promotionList)) {
                foreach ($promotionList as $promotion) {
                    $sessionList = SessionManager::get_all_sessions_by_promotion($promotion['id']);
                    foreach ($sessionList as $session) {
                        if (in_array('ROLE_TEACHER', $announcement->getRoles(), true)) {
                            $users = SessionManager::get_users_by_session($session['id'], 2);
                            if (!empty($users)) {
                                $userListToFilter = array_merge($users, $userListToFilter);
                            }
                        }

                        if (in_array('ROLE_STUDENT', $announcement->getRoles(), true)) {
                            $users = SessionManager::get_users_by_session($session['id'], 0);
                            if (!empty($users)) {
                                $userListToFilter = array_merge($users, $userListToFilter);
                            }
                        }
                    }
                }
            }
        }

        if (!empty($userListToFilter)) {
            $userListToFilter = array_column($userListToFilter, 'user_id');
            //$userListToFilterToString = implode("', '", $userListToFilter);
            $qb
                ->andWhere('u.id IN (:users)')
                ->setParameter('users', $userListToFilter);
            //$sql .= " AND (u.user_id IN ('$userListToFilterToString') ) ";
        }
        $users = $qb->getQuery()->getResult();

        $message_sent = false;
        /** @var User $user */
        foreach ($users as $user) {
            MessageManager::send_message_simple($user->getId(), $title, $content);
            $message_sent = true;
        }

        // Minor validation to clean up the attachment files in the announcement
        /*if (!empty($_FILES)) {
            $attachments = $_FILES;
            foreach ($attachments as $attachment) {
                unlink($attachment['tmp_name']);
            }
        }*/

        return $message_sent; //true if at least one e-mail was sent
    }

    /**
     * Makes the announcement id visible only for groups in groups_array.
     *
     * @param int   $announcement_id
     * @param array $group_array     array of group id
     *
     * @return bool
     */
    public static function announcement_for_groups($announcement_id, $group_array)
    {
        $tbl_announcement_group = Database::get_main_table(
            TABLE_MAIN_SYSTEM_ANNOUNCEMENTS_GROUPS
        );
        //first delete all group associations for this announcement
        $res = Database::query(
            "DELETE FROM $tbl_announcement_group
             WHERE announcement_id=".intval($announcement_id)
        );

        if (false === $res) {
            return false;
        }

        foreach ($group_array as $group_id) {
            if (0 != intval($group_id)) {
                $sql = "INSERT INTO $tbl_announcement_group SET
                        announcement_id=".intval($announcement_id).",
                        group_id=".intval($group_id);
                $res = Database::query($sql);
                if (false === $res) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Gets the groups of this announce.
     *
     * @param int announcement id
     *
     * @return array array of group id
     */
    public static function get_announcement_groups($announcement_id)
    {
        $tbl_announcement_group = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS_GROUPS);
        $tbl_group = Database::get_main_table(TABLE_USERGROUP);
        //first delete all group associations for this announcement
        $sql = "SELECT
                    g.id as group_id,
                    g.name as group_name
                FROM $tbl_group g , $tbl_announcement_group ag
                WHERE
                    announcement_id =".intval($announcement_id)." AND
                    ag.group_id = g.id";
        $res = Database::query($sql);

        return Database::fetch_array($res);
    }

    /**
     * Updates an announcement to the database.
     *
     * @param int    $id            of the announcement
     * @param string $title         title of the announcement
     * @param string $content       content of the announcement
     * @param array  $date_start    start date (0 => day ; 1 => month ; 2 => year ; 3 => hour ; 4 => minute)
     * @param array  $date_end      end date of (0 => day ; 1 => month ; 2 => year ; 3 => hour ; 4 => minute)
     * @param array  $visibility
     * @param array  $lang
     * @param int    $send_mail
     * @param bool   $sendEmailTest
     * @param int    $careerId
     * @param int    $promotionId
     *
     * @return bool True on success, false on failure
     */
    public static function update_announcement(
        $id,
        $title,
        $content,
        $date_start,
        $date_end,
        $visibility,
        $lang = null,
        $send_mail = 0,
        $sendEmailTest = false,
        $careerId = 0,
        $promotionId = 0
    ) {
        $sysRepo = Container::getSysAnnouncementRepository();
        /** @var SysAnnouncement|null $announcement */
        $announcement = $sysRepo->find($id);
        if (null === $announcement) {
            return false;
        }
        $a_dateS = explode(' ', $date_start);
        $a_arraySD = explode('-', $a_dateS[0]);
        $a_arraySH = explode(':', $a_dateS[1]);
        $date_start_to_compare = array_merge($a_arraySD, $a_arraySH);

        $a_dateE = explode(' ', $date_end);
        $a_arrayED = explode('-', $a_dateE[0]);
        $a_arrayEH = explode(':', $a_dateE[1]);
        $date_end_to_compare = array_merge($a_arrayED, $a_arrayEH);

        $lang = is_null($lang) ? '' : $lang;

        if (!checkdate($date_start_to_compare[1], $date_start_to_compare[2], $date_start_to_compare[0])) {
            echo Display::return_message(get_lang('Invalid start date was given.'));

            return false;
        }

        if (($date_end_to_compare[1] ||
                $date_end_to_compare[2] ||
                $date_end_to_compare[0]) &&
            !checkdate($date_end_to_compare[1], $date_end_to_compare[2], $date_end_to_compare[0])
        ) {
            echo Display::return_message(get_lang('Invalid end date was given.'));

            return false;
        }

        if (0 == strlen(trim($title))) {
            echo Display::return_message(get_lang('Please enter a title'));

            return false;
        }

        $start = api_get_utc_datetime($date_start);
        $end = api_get_utc_datetime($date_end);

        //Fixing urls that are sent by email
        //$content = str_replace('src=\"/home/', 'src=\"'.api_get_path(WEB_PATH).'home/', $content);
        //$content = str_replace('file=/home/', 'file='.api_get_path(WEB_PATH).'home/', $content);
        $content = str_replace(
            'src=\"'.api_get_path(REL_HOME_PATH),
            'src=\"'.api_get_path(WEB_PATH).api_get_path(REL_HOME_PATH),
            $content
        );
        $content = str_replace(
            'file='.api_get_path(REL_HOME_PATH),
            'file='.api_get_path(WEB_PATH).api_get_path(REL_HOME_PATH),
            $content
        );

        $dateStart = new DateTime($start, new DateTimeZone('UTC'));
        $dateEnd = new DateTime($end, new DateTimeZone('UTC'));

        $announcement
            ->setLang($lang)
            ->setTitle($title)
            ->setContent($content)
            ->setDateStart($dateStart)
            ->setDateEnd($dateEnd)
            ->setRoles($visibility);

        $sysRepo->update($announcement);

        // Update visibility
        //$list = self::getVisibilityList();
        $table = Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);

        if (api_get_configuration_value('allow_careers_in_global_announcements') && !empty($careerId)) {
            $params = [];
            $params['career_id'] = (int) $careerId;
            $params['promotion_id'] = (int) $promotionId;
            Database::update(
                $table,
                $params,
                ['id = ? ' => $id]
            );
        }

        /*foreach ($list as $key => $title) {
            $value = isset($visibility[$key]) && $visibility[$key] ? 1 : 0;
            $sql = "UPDATE $table SET $key = '$value' WHERE id = $id";
            Database::query($sql);
        }*/

        if ($sendEmailTest) {
            self::send_system_announcement_by_email($announcement, true);
        } else {
            if (1 == $send_mail) {
                self::send_system_announcement_by_email($announcement);
            }
        }

        return true;
    }
}
