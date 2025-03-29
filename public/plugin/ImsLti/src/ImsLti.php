<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\UserBundle\Entity\User;

/**
 * Class ImsLti.
 */
class ImsLti
{
    const V_1P1 = 'lti1p1';
    const V_1P3 = 'lti1p3';
    const LTI_RSA_KEY = 'rsa_key';
    const LTI_JWK_KEYSET = 'jwk_keyset';

    /**
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
        $ltiVersion = self::V_1P1,
        ImsLtiTool $tool
    ) {
        $isLti1p3 = $ltiVersion === self::V_1P3;

        return [
            '$User.id' => $user->getId(),
            '$User.image' => $isLti1p3 ? ['claim' => 'sub'] : ['user_image'],
            '$User.username' => $user->getUsername(),
            '$User.org' => false,
            '$User.scope.mentor' => $isLti1p3 ? ['claim' => '/claim/role_scope_mentor'] : ['role_scope_mentor'],

            '$Person.sourcedId' => $isLti1p3
                ? self::getPersonSourcedId($domain, $user)
                : "$domain:".ImsLtiPlugin::getLaunchUserIdClaim($tool, $user),
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
        $ltiVersion = self::V_1P1,
        ImsLtiTool $tool
    ) {
        $substitutables = self::getSubstitutableVariables($user, $course, $session, $domain, $ltiVersion, $tool);
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

                    $substitute = empty($substitute['property'])
                        ? $launchParams[$claim]
                        : $launchParams[$claim][$substitute['property']];
                } else {
                    continue;
                }
            }

            $customParams[$customKey] = $substitute;
        }

        array_walk_recursive(
            $customParams,
            function (&$value) {
                if (gettype($value) !== 'array') {
                    $value = (string) $value;
                }
            }
        );

        return $customParams;
    }

    /**
     * Generate a user sourced ID for LIS.
     *
     * @param string $domain
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
     * @param Session $session Optional.
     *
     * @return string
     */
    public static function getCourseSectionSourcedId($domain, Course $course, Session $session = null)
    {
        $sourceId = [$domain, $course->getId()];

        if ($session) {
            $sourceId[] = $session->getId();
        }

        return implode(':', $sourceId);
    }

    /**
     * Get instances for LTI Advantage services.
     *
     * @return array
     */
    public static function getAdvantageServices(ImsLtiTool $tool)
    {
        return [
            new LtiAssignmentGradesService($tool),
            new LtiNamesRoleProvisioningService($tool),
        ];
    }

    /**
     * @param int $length
     *
     * @return string
     */
    public static function generateClientId($length = 20)
    {
        $hash = md5(mt_rand().time());

        $clientId = '';

        for ($p = 0; $p < $length; $p++) {
            $op = mt_rand(1, 3);

            if ($op === 1) {
                $char = chr(mt_rand(97, 97 + 25));
            } elseif ($op === 2) {
                $char = chr(mt_rand(65, 65 + 25));
            } else {
                $char = substr($hash, mt_rand(0, strlen($hash) - 1), 1);
            }

            $clientId .= $char;
        }

        return $clientId;
    }

    /**
     * Validate the format ISO 8601 for date strings coming from JSON or JavaScript.
     *
     * @see https://www.myintervals.com/blog/2009/05/20/iso-8601-date-validation-that-doesnt-suck/ Pattern source.
     *
     * @param string $strDate
     *
     * @return bool
     */
    public static function validateFormatDateIso8601($strDate)
    {
        $pattern = '/^([\+-]?\d{4}(?!\d{2}\b))((-?)('
            .'(0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W'
            .'([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))('
            .'[T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?'
            .'([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/';

        return preg_match($pattern, $strDate) !== false;
    }
}
