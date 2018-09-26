<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity\Manager;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use Sonata\CoreBundle\Model\BaseEntityManager;

/**
 * Class AnnouncementManager.
 *
 * @package Chamilo\CourseBundle\Entity\Manager
 */
class AnnouncementManager extends BaseEntityManager
{
    /**
     * @param User            $user
     * @param Course          $course
     * @param CGroupInfo|null $group
     * @param Session|null    $session
     * @param bool            $allowUserEditSetting
     * @param bool            $allowOnlyGroup
     * @param bool            $getCount
     * @param int|null        $start
     * @param int|null        $limit
     * @param string          $titleToSearch
     * @param User|null       $userToSearch
     *
     * @return mixed
     */
    public function getAnnouncements(
        User $user,
        Course $course,
        CGroupInfo $group = null,
        Session $session = null,
        $allowUserEditSetting,
        $allowOnlyGroup,
        $getCount = false,
        $start = null,
        $limit = null,
        $titleToSearch = '',
        User $userToSearch = null
    ) {
        $sessionId = $session ? $session->getId() : 0;

        $conditionSession = api_get_session_condition(
            $sessionId,
            true,
            true,
            'announcement.sessionId'
        );

        $select = 'DISTINCT announcement, ip';
        $groupBy = 'GROUP BY announcement.iid';

        if ($getCount) {
            $groupBy = '';
            $select = 'COUNT(DISTINCT announcement) AS count';
        }

        $parameters = [];
        $searchCondition = '';

        if (!empty($titleToSearch)) {
            $parameters['search_title'] = "%$titleToSearch%";
            $searchCondition .= " AND (title LIKE :search_title) ";
        }

        if (!empty($userToSearch)) {
            $searchCondition .= " AND (ip.insertUser = ".$userToSearch->getId().") ";
        }

        $extraGroupCondition = '';

        if ($allowOnlyGroup) {
            $extraGroupCondition = ' AND ip.group = '.$group->getId().' ';
        }

        if (api_is_allowed_to_edit(false, true)
            || ($allowUserEditSetting && !api_is_anonymous())
        ) {
            $dqlCondition = "AND (ip.visibility = 0 OR ip.visibility = 1)";

            if (!empty($group)) {
                $dqlCondition = "AND ip.visibility != 2 AND
                    (ip.group = ".$group->getId()." OR ip.group IS NULL )
                    $extraGroupCondition";
            }
        } else {
            $groupMemberships = $user->getCourseGroupsAsMemberFromCourse($course);
            $tutoredGroups = $user->getCourseGroupsAsTutorFromCourse($course);
            $memberships = array_merge($groupMemberships->toArray(), $tutoredGroups->toArray());

            if (!empty($memberships)) {
                if ($allowUserEditSetting && !api_is_anonymous()) {
                    $parameters['memberships'] = $memberships;
                    $condUserId = " AND (
                            ip.lasteditUserId = ".$user->getId()." OR(
                                (ip.toUser = ".$user->getId()." OR ip.toUser IS NULL) OR
                                (ip.group IS NULL OR ip.group = 0 OR ip.group IN :memberships)
                            )
                        ) ";

                    if (!empty($group)) {
                        unset($parameters['memberships']);
                        $condUserId = " AND (
                                ip.lasteditUserId = ".$user->getId()." OR ip.group IS NULL OR ip.group IN (0, ".$group->getId()
                            .")
                            ) ".$extraGroupCondition;
                    }
                } else {
                    $parameters['memberships'] = $memberships;
                    $condUserId = " AND (
                            (ip.toUser = ".$user->getId().") OR ip.toUser IS NULL) AND
                            (ip.group IS NULL OR ip.group = 0 OR ip.group IN :memberships)
                        ) ";

                    if (!empty($group)) {
                        unset($parameters['memberships']);
                        $condUserId = " AND (
                            (ip.toUser = ".$user->getId().") OR ip.toUser IS NULL) AND
                            (ip.group IS NULL OR ip.group IN (0, ".$group->getId()."))
                        ) ".$extraGroupCondition;
                    }
                }

                $dqlCondition = "$condUserId AND ip.visibility = 1";
            } else {
                if (!empty($user->getId())) {
                    $condUserId = " AND (
                            (ip.toUser = ".$user->getId()." OR ip.toUser IS NULL) AND
                            (ip.group = 0 OR ip.group IS NULL)
                        ) ";

                    if ($allowUserEditSetting && !api_is_anonymous()) {
                        $condUserId = " AND (
                                ip.lasteditUserId = ".$user->getId()." OR
                                (
                                    (ip.toUser = ".$user->getId()." OR ip.toUser IS NULL) AND
                                    (ip.group = 0 OR ip.group IS NULL)
                                )
                            ) ";
                    }

                    $dqlCondition = "$condUserId
                        AND ip.visibility = 1
                        AND announcement.sessionId IN (0, $sessionId)";
                } else {
                    $condUserId = " AND ip.group = 0 OR ip.group IS NULL ";

                    if ($allowUserEditSetting && !api_is_anonymous()) {
                        $condUserId = " AND (
                                ip.lastEditUserId = ".$user->getId()." OR ip.group = 0 OR ip.group IS NULL
                            ) ";
                    }

                    $dqlCondition = "$condUserId
                        AND ip.visibility = 1
                        AND announcement.sessionId IN (0, $sessionId)";
                }
            }
        }

        $dql = "SELECT $select
            FROM ChamiloCourseBundle:CAnnouncement announcement
            INNER JOIN ChamiloCourseBundle:CItemProperty AS ip
                WITH (announcement.id = ip.ref AND announcement.cId = ip.course)
            WHERE
                ip.tool = 'announcement' AND
                announcement.cId = ".$course->getId()."
                $dqlCondition
                $conditionSession
                $searchCondition
            $groupBy
            ORDER BY announcement.displayOrder DESC";

        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameters($parameters);

        if (!is_null($start) && !is_null($limit)) {
            $query
                ->setFirstResult($start)
                ->setMaxResults($limit);
        }

        if ($getCount) {
            return $query->getResult();
        }

        return $query->getResult();
    }
}
