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
    /**
     * @param User         $user
     * @param Course       $course
     * @param Session|null $session
     *
     * @return array
     */
    public static function getSubstitutableParams(User $user, Course $course, Session $session = null)
    {
        return [
            '$User.id' => $user->getId(),
            '$User.image' => ['user_image'],
            '$User.username' => $user->getUsername(),

            '$Person.sourcedId' => false,
            '$Person.name.full' => $user->getFullname(),
            '$Person.name.family' => $user->getLastname(),
            '$Person.name.given' => $user->getFirstname(),
            '$Person.name.middle' => false,
            '$Person.name.prefix' => false,
            '$Person.name.suffix' => false,
            '$Person.address.street1' => $user->getAddress(),
            '$Person.address.street2' => false,
            '$Person.address.street3' => false,
            '$Person.address.street4' => false,
            '$Person.address.locality' => false,
            '$Person.address.statepr' => false,
            '$Person.address.country' => false,
            '$Person.address.postcode' => false,
            '$Person.address.timezone' => false, //$user->getTimezone(),
            '$Person.phone.mobile' => false,
            '$Person.phone.primary' => $user->getPhone(),
            '$Person.phone.home' => false,
            '$Person.phone.work' => false,
            '$Person.email.primary' => $user->getEmail(),
            '$Person.email.personal' => false,
            '$Person.webaddress' => false, //$user->getWebsite(),
            '$Person.sms' => false,

            '$CourseTemplate.sourcedId' => false,
            '$CourseTemplate.label' => false,
            '$CourseTemplate.title' => false,
            '$CourseTemplate.shortDescription' => false,
            '$CourseTemplate.longDescription' => false,
            '$CourseTemplate.courseNumber' => false,
            '$CourseTemplate.credits' => false,

            '$CourseOffering.sourcedId' => false,
            '$CourseOffering.label' => false,
            '$CourseOffering.title' => false,
            '$CourseOffering.shortDescription' => false,
            '$CourseOffering.longDescription' => false,
            '$CourseOffering.courseNumber' => false,
            '$CourseOffering.credits' => false,
            '$CourseOffering.academicSession' => false,

            '$CourseSection.sourcedId' => ['lis_course_section_sourcedid'],
            '$CourseSection.label' => $course->getCode(),
            '$CourseSection.title' => $course->getTitle(),
            '$CourseSection.shortDescription' => false,
            '$CourseSection.longDescription' => $session && $session->getShowDescription()
                ? $session->getDescription()
                : false,
            '$CourseSection.courseNumber' => false,
            '$CourseSection.credits' => false,
            '$CourseSection.maxNumberofStudents' => false,
            '$CourseSection.numberofStudents' => false,
            '$CourseSection.dept' => false,
            '$CourseSection.timeFrame.begin' => $session && $session->getDisplayStartDate()
                ? $session->getDisplayStartDate()->format(DateTime::ATOM)
                : false,
            '$CourseSection.timeFrame.end' => $session && $session->getDisplayEndDate()
                ? $session->getDisplayEndDate()->format(DateTime::ATOM)
                : false,
            '$CourseSection.enrollControl.accept' => false,
            '$CourseSection.enrollControl.allowed' => false,
            '$CourseSection.dataSource' => false,
            '$CourseSection.sourceSectionId' => false,

            '$Group.sourcedId' => false,
            '$Group.grouptype.scheme' => false,
            '$Group.grouptype.typevalue' => false,
            '$Group.grouptype.level' => false,
            '$Group.email' => false,
            '$Group.url' => false,
            '$Group.timeFrame.begin' => false,
            '$Group.timeFrame.end' => false,
            '$Group.enrollControl.accept' => false,
            '$Group.enrollControl.allowed' => false,
            '$Group.shortDescription' => false,
            '$Group.longDescription' => false,
            '$Group.parentId' => false,

            '$Membership.sourcedId' => false,
            '$Membership.collectionSourcedId' => false,
            '$Membership.personSourcedId' => false,
            '$Membership.status' => false,
            '$Membership.role' => ['roles'],
            '$Membership.createdTimestamp' => false,
            '$Membership.dataSource' => false,

            '$LineItem.sourcedId' => false,
            '$LineItem.type' => false,
            '$LineItem.type.displayName' => false,
            '$LineItem.resultValue.max' => false,
            '$LineItem.resultValue.list' => false,
            '$LineItem.dataSource' => false,

            '$Result.sourcedGUID' => ['lis_result_sourcedid'],
            '$Result.sourcedId' => ['lis_result_sourcedid'],
            '$Result.createdTimestamp' => false,
            '$Result.status' => false,
            '$Result.resultScore' => false,
            '$Result.dataSource' => false,

            '$ResourceLink.title' => ['resource_link_title'],
            '$ResourceLink.description' => ['resource_link_description'],
        ];
    }
}
