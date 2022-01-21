<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * Class LtiContextMembershipResource.
 */
class LtiContextMembershipResource extends LtiAdvantageServiceResource
{
    const URL_TEMPLATE = '/context_id/memberships';

    /**
     * @var bool
     */
    private $inSession = false;
    /**
     * @var Session
     */
    private $session;

    /**
     * LtiContextMembershipResorce constructor.
     *
     * @param int $toolId
     * @param int $courseId
     * @param int $sessionId
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function __construct($toolId, $courseId, $sessionId = 0)
    {
        $this->session = api_get_session_entity($sessionId);

        parent::__construct($toolId, $courseId);
    }

    /**
     * {@inheritDoc}
     */
    public function validate()
    {
        if ($this->request->server->get('HTTP_ACCEPT') !== LtiNamesRoleProvisioningService::TYPE_MEMBERSHIP_CONTAINER) {
            throw new UnsupportedMediaTypeHttpException('Unsupported media type.');
        }

        if (!$this->course) {
            throw new BadRequestHttpException('Course not found.');
        }

        if (!$this->tool) {
            throw new BadRequestHttpException('Tool not found.');
        }

        if (null === $this->tool->getCourse()) {
            throw new BadRequestHttpException('Tool not enabled.');
        }

        if ($this->tool->getCourse()->getId() !== $this->course->getId()) {
            throw new AccessDeniedHttpException('Tool not found in course.');
        }

        $sessionId = (int) $this->request->query->get('s');
        $this->inSession = $sessionId > 0;

        if ($this->inSession && !$this->session) {
            throw new BadRequestHttpException('Session not found');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function process()
    {
        switch ($this->request->getMethod()) {
            case Request::METHOD_GET:
                $this->validateToken(
                    [LtiNamesRoleProvisioningService::SCOPE_CONTEXT_MEMBERSHIP_READ]
                );
                $this->processGet();
                break;
            default:
                throw new MethodNotAllowedHttpException([Request::METHOD_GET]);
        }
    }

    private function processGet()
    {
        $role = str_replace(
            'http://purl.imsglobal.org/vocab/lis/v2/membership#',
            '',
            $this->request->query->get('role')
        );
        $limit = $this->request->query->getInt('limit');
        $page = $this->request->query->getInt('page');
        $status = -1;

        if ('Instructor' === $role) {
            $status = $this->session ? Session::COACH : User::COURSE_MANAGER;
        } elseif ('Learner' === $role) {
            $status = $this->session ? Session::STUDENT : User::STUDENT;
        }

        $members = $this->getMembers($status, $limit, $page);

        $data = $this->getGetData($members);

        $this->setLinkHeaderToGet($status, $limit, $page);

        $this->response->headers->set('Content-Type', LtiNamesRoleProvisioningService::TYPE_MEMBERSHIP_CONTAINER);
        $this->response->setData($data);
    }

    /**
     * @param int $status If $status = -1 then get all statuses.
     * @param int $limit
     * @param int $page
     *
     * @return array
     */
    private function getMembers($status, $limit, $page = 0)
    {
        if ($this->session) {
            $subscriptions = $this->session->getUsersSubscriptionsInCourse($this->course);

            // Add session admin as teacher in course
            $adminSubscription = new SessionRelCourseRelUser();
            $adminSubscription->setCourse($this->course);
            $adminSubscription->setSession($this->session);
            $adminSubscription->setStatus(Session::COACH);
            $adminSubscription->setUser(
                api_get_user_entity($this->session->getSessionAdminId())
            );

            $subscriptions->add($adminSubscription);
        } else {
            $subscriptions = $this->course->getUsers();
        }

        $criteria = Criteria::create();

        if ($status > -1) {
            $criteria->where(
                Criteria::expr()->eq('status', $status)
            );
        }

        if ($limit > 0) {
            $criteria->setMaxResults($limit);

            if ($page > 0) {
                $criteria->setFirstResult($page * $limit);
            }
        }

        return $subscriptions->matching($criteria)->toArray();
    }

    /**
     * @return array
     */
    private function getGetData(array $members)
    {
        $platformDomain = str_replace(['https://', 'http://'], '', api_get_setting('InstitutionUrl'));
        $dataMembers = [];

        $isSharingName = $this->tool->isSharingName();
        $isSharingEmail = $this->tool->isSharingEmail();
        $isSharingPicture = $this->tool->isSharingPicture();

        foreach ($members as $member) {
            /** @var User $user */
            $user = $member->getUser();

            $dataMember = [
                'status' => $user->isActive()
                    ? LtiNamesRoleProvisioningService::USER_STATUS_ACTIVE
                    : LtiNamesRoleProvisioningService::USER_STATUS_INACTIVE,
                'user_id' => ImsLtiPlugin::getLaunchUserIdClaim($this->tool, $user),
                'lis_person_sourcedid' => ImsLti::getPersonSourcedId($platformDomain, $user),
                'lti11_legacy_user_id' => ImsLtiPlugin::generateToolUserId($user->getId()),
            ];

            if ($isSharingName) {
                $dataMember['name'] = $user->getFullname();
                $dataMember['given_name'] = $user->getFirstname();
                $dataMember['family_name'] = $user->getLastname();
            }

            if ($isSharingEmail) {
                $dataMember['email'] = $user->getEmail();
            }

            if ($isSharingPicture) {
                $dataMember['picture'] = UserManager::getUserPicture($user->getId());
            }

            if ($member instanceof CourseRelUser) {
                $dataMember['roles'] = $member->getStatus() === User::COURSE_MANAGER
                    ? ['http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor']
                    : ['http://purl.imsglobal.org/vocab/lis/v2/membership#Learner'];
            } elseif ($member instanceof SessionRelCourseRelUser) {
                $dataMember['roles'] = $member->getStatus() === Session::STUDENT
                    ? ['http://purl.imsglobal.org/vocab/lis/v2/membership#Learner']
                    : ['http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor'];
            }

            $dataMembers[] = $dataMember;
        }

        return [
            'id' => api_get_path(WEB_PLUGIN_PATH)
                ."ims_lti/nrps2.php/{$this->course->getId()}/memberships?"
                .http_build_query(
                    [
                        't' => $this->tool->getId(),
                        's' => $this->session ? $this->session->getId() : null,
                    ]
                ),
            'context' => [
                'id' => (string) $this->course->getId(),
                'label' => $this->course->getCode(),
                'title' => $this->course->getTitle(),
            ],
            'members' => $dataMembers,
        ];
    }

    /**
     * @param int $status
     * @param int $limit
     * @param int $page
     */
    private function setLinkHeaderToGet($status, $limit, $page = 0)
    {
        if (!$limit) {
            return;
        }

        if ($this->session) {
            $subscriptions = $this->session->getUsersSubscriptionsInCourse($this->course);
        } else {
            $subscriptions = $this->course->getUsers();
        }

        $criteria = Criteria::create();

        if ($status > -1) {
            $criteria->where(
                Criteria::expr()->eq('status', $status)
            );
        }

        $count = $subscriptions->matching($criteria)->count();

        if ($this->session) {
            // +1 for session admin
            $count++;
        }

        if ($page + 1 < ceil($count / $limit)) {
            $url = LtiNamesRoleProvisioningService::getUrl(
                $this->tool->getId(),
                $this->course->getId(),
                $this->session ? $this->session->getId() : 0,
                [
                    'role' => $this->request->query->get('role'),
                    'limit' => $limit,
                    'page' => $page + 1,
                ]
            );

            $this->response->headers->set(
                'Link',
                '<'.$url.'>; rel="next"'
            );
        }
    }
}
