<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CourseBundle\Entity\CGroup;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class ResourceVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const CREATE = 'CREATE';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';
    public const EXPORT = 'EXPORT';

    private RequestStack $requestStack;
    private Security $security;

    public function __construct(Security $security, RequestStack $requestStack)
    {
        $this->security = $security;
        $this->requestStack = $requestStack;
    }

    public static function getReaderMask(): int
    {
        $builder = new MaskBuilder();
        $builder
            ->add(self::VIEW)
        ;

        return $builder->get();
    }

    public static function getEditorMask(): int
    {
        $builder = new MaskBuilder();
        $builder
            ->add(self::VIEW)
            ->add(self::EDIT)
        ;

        return $builder->get();
    }

    protected function supports(string $attribute, $subject): bool
    {
        $options = [
            self::VIEW,
            self::CREATE,
            self::EDIT,
            self::DELETE,
            self::EXPORT,
        ];

        //error_log('resource supports');
        // if the attribute isn't one we support, return false
        if (!\in_array($attribute, $options, true)) {
            return false;
        }

        // Course/CGroup/ are AbstractResource but it's checked with the CourseVoter
        if ($subject instanceof Course ||
            $subject instanceof CGroup
        ) {
            return false;
        }

        // only vote on ResourceNode objects inside this voter
        return $subject instanceof AbstractResource;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /*error_log('resource voteOnAttribute');
        $user = $token->getUser();*/

        return true;
    }
}
