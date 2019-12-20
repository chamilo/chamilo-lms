<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\UserBundle\Entity\User;

/**
 * Class ImsLti.
 */
class ImsLti
{
    const V_1P1 = 'lti1p1';
    const V_1P3 = 'lti1p3';

    /**
     * @param User         $user
     * @param Course       $course
     * @param Session|null $session    Optional.
     * @param string       $domain     Optional. Institution domain.
     * @param string       $ltiVersion Optional. Default is lti1p1.
     *
     * @return array
     */
    public static function getSubstitutableVariables(
        User $user,
        Course $course,
        Session $session = null,
        $domain = '',
        $ltiVersion = self::V_1P1
    ) {
        $isLti1p3 = $ltiVersion === self::V_1P3;

        return [
            '$User.id' => $user->getId(),
            '$User.image' => $isLti1p3 ? ['claim' => 'sub'] : ['user_image'],
            '$User.username' => $user->getUsername(),
            '$User.org' => false,
            'User.scope.mentor' => $isLti1p3 ? ['claim' => '/claim/role_scope_mentor'] : ['role_scope_mentor'],

            '$Person.sourcedId' => $isLti1p3
                ? self::getPersonSourcedId($domain, $user)
                : "$domain:".ImsLtiPlugin::generateToolUserId($user->getId()),
            '$Person.name.full' => $user->getFullname(),
            '$Person.name.family' => $user->getLastname(),
            '$Person.name.given' => $user->getFirstname(),
            '$Person.address.street1' => $user->getAddress(),
            '$Person.phone.primary' => $user->getPhone(),
            '$Person.email.primary' => $user->getEmail(),

            '$CourseSection.sourcedId' => $isLti1p3
                ? ['claim' => '/claim/lis', 'property' => 'course_section_sourcedid']
                : ['lis_course_section_sourcedid'],
            '$CourseSection.label' => $course->getCode(),
            '$CourseSection.title' => $course->getTitle(),
            '$CourseSection.longDescription' => $session && $session->getShowDescription()
                ? $session->getDescription()
                : false,
            '$CourseSection.timeFrame.begin' => $session && $session->getDisplayStartDate()
                ? $session->getDisplayStartDate()->format(DateTime::ATOM)
                : '$CourseSection.timeFrame.begin',
            '$CourseSection.timeFrame.end' => $session && $session->getDisplayEndDate()
                ? $session->getDisplayEndDate()->format(DateTime::ATOM)
                : '$CourseSection.timeFrame.end',

            '$Membership.role' => $isLti1p3 ? ['claim' => '/claim/roles'] : ['roles'],

            '$Result.sourcedGUID' => $isLti1p3 ? ['claim' => 'sub'] : ['lis_result_sourcedid'],
            '$Result.sourcedId' => $isLti1p3 ? ['claim' => 'sub'] : ['lis_result_sourcedid'],

            '$ResourceLink.id' => $isLti1p3
                ? ['claim' => '/claim/resource_link', 'property' => 'id']
                : ['resource_link_id'],
            '$ResourceLink.title' => $isLti1p3
                ? ['claim' => '/claim/resource_link', 'property' => 'title']
                : ['resource_link_title'],
            '$ResourceLink.description' => $isLti1p3
                ? ['claim' => '/claim/resource_link', 'property' => 'description']
                : ['resource_link_description'],
        ];
    }

    /**
     * @param array        $launchParams All params for launch.
     * @param array        $customParams Custom params where search variables to substitute.
     * @param User         $user
     * @param Course       $course
     * @param Session|null $session      Optional.
     * @param string       $domain       Optional. Institution domain.
     * @param string       $ltiVersion   Optional. Default is lti1p1.
     *
     * @return array
     */
    public static function substituteVariablesInCustomParams(
        array $launchParams,
        array $customParams,
        User $user,
        Course $course,
        Session $session = null,
        $domain = '',
        $ltiVersion = self::V_1P1
    ) {
        $substitutables = self::getSubstitutableVariables($user, $course, $session, $domain, $ltiVersion);
        $variables = array_keys($substitutables);

        foreach ($customParams as $customKey => $customValue) {
            if (!in_array($customValue, $variables)) {
                continue;
            }

            $substitute = $substitutables[$customValue];

            if (is_array($substitute)) {
                if ($ltiVersion === self::V_1P1) {
                    $substitute = current($substitute);

                    $substitute = $launchParams[$substitute];
                } elseif ($ltiVersion === self::V_1P3) {
                    $claim = array_key_exists($substitute['claim'], $launchParams)
                        ? $substitute['claim']
                        : "https://purl.imsglobal.org/spec/lti{$substitute['claim']}";

                    $substitute = $launchParams[$claim][$substitute['property']];
                } else {
                    continue;
                }
            }

            $customParams[$customKey] = $substitute;
        }

        return array_map(
            function ($value) {
                return (string) $value;
            },
            $customParams
        );
    }

    /**
     * Generate a user sourced ID for LIS.
     *
     * @param string $domain
     * @param User   $user
     *
     * @return string
     */
    public static function getPersonSourcedId($domain, User $user)
    {
        $sourceId = [$domain, $user->getId()];

        return implode(':', $sourceId);
    }

    /**
     * Generate a course sourced ID for LIS.
     *
     * @param string  $domain
     * @param Course  $course
     * @param Session $session
     *
     * @return string
     */
    public static function getCourseSectionSourcedId($domain, Course $course, Session $session)
    {
        $sourceId = [$domain, $course->getId()];

        if ($session) {
            $sourceId[] = $session->getId();
        }

        return implode(':', $sourceId);
    }
}
