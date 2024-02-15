<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Repository\LegalRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Serializer\UserToJsonNormalizer;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use DateTime;
use ExtraFieldValue;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use UserManager;

#[Route('/social-network')]
class SocialController extends AbstractController
{
    #[Route('/personal-data/{userId}', name: 'chamilo_core_social_personal_data')]
    public function getPersonalData(
        int $userId,
        SettingsManager $settingsManager,
        UserToJsonNormalizer $userToJsonNormalizer
    ): JsonResponse {
        $propertiesToJson = $userToJsonNormalizer->serializeUserData($userId);
        $properties = $propertiesToJson ? json_decode($propertiesToJson, true) : [];

        $officerData = [
            ['name' => $settingsManager->getSetting('profile.data_protection_officer_name')],
            ['role' => $settingsManager->getSetting('profile.data_protection_officer_role')],
            ['email' => $settingsManager->getSetting('profile.data_protection_officer_email')],
        ];
        $properties['officer_data'] = $officerData;

        $dataForVue = [
            'personalData' => $properties,
        ];

        return $this->json($dataForVue);
    }

    #[Route('/terms-and-conditions/{userId}', name: 'chamilo_core_social_terms')]
    public function getLegalTerms(
        int $userId,
        SettingsManager $settingsManager,
        TranslatorInterface $translator,
        LegalRepository $legalTermsRepo,
        UserRepository $userRepo,
        LanguageRepository $languageRepo
    ): JsonResponse {
        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found']);
        }

        $isoCode = $user->getLocale();
        $language = $languageRepo->findByIsoCode($isoCode);
        $languageId = (int) $language->getId();

        $term = $legalTermsRepo->getLastConditionByLanguage($languageId);

        if (!$term) {
            $defaultLanguage = $settingsManager->getSetting('platform.platform_language');
            $term = $legalTermsRepo->getLastConditionByLanguage((int) $defaultLanguage);
        }

        if (!$term) {
            return $this->json(['error' => 'Terms not found']);
        }

        $termExtraFields = new ExtraFieldValue('terms_and_condition');
        $values = $termExtraFields->getAllValuesByItem($term->getId());

        $termsContent = [];
        foreach ($values as $value) {
            if (!empty($value['value'])) {
                $termsContent[] = [
                    'title' => $translator->trans($value['display_text'], [], 'messages', $isoCode),
                    'content' => $value['value'],
                ];
            }
        }

        if (empty($termsContent)) {
            $termsContent[] = [
                'title' => $translator->trans('Terms and Conditions', [], 'messages', $isoCode),
                'content' => $term->getContent(),
            ];
        }

        $formattedDate = new DateTime('@'.$term->getDate());

        $dataForVue = [
            'terms' => $termsContent,
            'date_text' => $translator->trans('PublicationDate', [], 'messages', $isoCode).': '.$formattedDate->format('Y-m-d H:i:s'),
        ];

        return $this->json($dataForVue);
    }

    #[Route('/legal-status/{userId}', name: 'chamilo_core_social_legal_status')]
    public function getLegalStatus(
        int $userId,
        SettingsManager $settingsManager,
        TranslatorInterface $translator,
        UserRepository $userRepo,
    ): JsonResponse {
        $allowTermsConditions = 'true' === $settingsManager->getSetting('registration.allow_terms_conditions');

        if (!$allowTermsConditions) {
            return $this->json([
                'message' => $translator->trans('No terms and conditions available', [], 'messages'),
            ]);
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found']);
        }

        $extraFieldValue = new ExtraFieldValue('user');
        $value = $extraFieldValue->get_values_by_handler_and_field_variable($userId, 'legal_accept');

        if (empty($value['value'])) {
            return $this->json([
                'isAccepted' => false,
                'message' => $translator->trans('Send legal agreement', [], 'messages'),
            ]);
        }

        [$legalId, $legalLanguageId, $legalTime] = explode(':', $value['value']);
        $dateTime = new DateTime("@$legalTime");
        $response = [
            'isAccepted' => true,
            'acceptDate' => $dateTime->format('Y-m-d H:i:s'),
            'message' => '',
        ];

        return $this->json($response);
    }

    #[Route('/handle-privacy-request', name: 'chamilo_core_social_handle_privacy_request')]
    public function handlePrivacyRequest(
        Request $request,
        SettingsManager $settingsManager,
        UserRepository $userRepo,
        TranslatorInterface $translator,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ? (int) $data['userId'] : null;
        $explanation = $data['explanation'] ?? '';
        $requestType = $data['requestType'] ?? '';

        /** @var User $user */
        $user = $userRepo->find($userId);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not found']);
        }

        if ('delete_account' === $requestType) {
            $fieldToUpdate = 'request_for_delete_account';
            $justificationFieldToUpdate = 'request_for_delete_account_justification';
            $emailSubject = 'Request for account removal';
            $emailContent = sprintf($translator->trans('User %s asked for the deletion of his/her account, explaining that : ').$explanation, $user->getFullName());
        } elseif ('delete_legal' === $requestType) {
            $fieldToUpdate = 'request_for_legal_agreement_consent_removal';
            $justificationFieldToUpdate = 'request_for_legal_agreement_consent_removal_justification';
            $emailSubject = 'Request for consent withdrawal on legal terms';
            $emailContent = sprintf($translator->trans('User %s asked for the removal of his/her consent to our legal terms, explaining that: ').$explanation, $user->getFullName());
        } else {
            return $this->json(['success' => false, 'message' => 'Invalid action type']);
        }

        UserManager::createDataPrivacyExtraFields();
        UserManager::update_extra_field_value($userId, $fieldToUpdate, 1);
        UserManager::update_extra_field_value($userId, $justificationFieldToUpdate, $explanation);

        $emailPlatform = $settingsManager->getSetting('admin.administrator_email');

        $email = new Email();
        $email
            ->from($user->getEmail())
            ->to($emailPlatform)
            ->subject($emailSubject)
            ->html($emailContent)
        ;

        $mailer->send($email);

        return $this->json([
            'success' => true,
            'message' => $translator->trans('Your request has been received.'),
        ]);
    }

    #[Route('/groups/{userId}', name: 'chamilo_core_social_groups')]
    public function getGroups(
        int $userId,
        UsergroupRepository $usergroupRepository,
        CForumThreadRepository $forumThreadRepository,
        SettingsManager $settingsManager,
        RequestStack $requestStack
    ): JsonResponse {
        $baseUrl = $requestStack->getCurrentRequest()->getBaseUrl();
        $cid = (int) $settingsManager->getSetting('forum.global_forums_course_id');
        $groupsArray = [];
        $threadsArray = [];
        if (!empty($cid)) {
            $threads = $forumThreadRepository->getThreadsBySubscriptions($userId, $cid);
            foreach ($threads as $thread) {
                $threadId = $thread->getIid();
                $forumId = (int) $thread->getForum()->getIid();
                $threadsArray[] = [
                    'id' => $threadId,
                    'name' => $thread->getTitle(),
                    'description' => '',
                    'url' => $baseUrl.'/main/forum/viewthread.php?cid='.$cid.'&sid=0&gid=0&forum='.$forumId.'&thread='.$threadId,
                    'go_to' => $baseUrl.'/main/forum/index.php?cid='.$cid.'&sid=0&gid=0',
                ];
            }
        } else {
            $groups = $usergroupRepository->getGroupsByUser($userId);
            foreach ($groups as $group) {
                $groupsArray[] = [
                    'id' => $group->getId(),
                    'name' => $group->getTitle(),
                    'description' => $group->getDescription(),
                    'url' => $baseUrl.'/resources/usergroups/show/'.$group->getId(),
                ];
            }
        }

        if (!empty($threadsArray)) {
            return $this->json(['groups' => $threadsArray]);
        }

        return $this->json(['groups' => $groupsArray]);
    }

    #[Route('/get-forum-link', name: 'get_forum_link')]
    public function getForumLink(
        SettingsManager $settingsManager,
        RequestStack $requestStack
    ): JsonResponse {
        $baseUrl = $requestStack->getCurrentRequest()->getBaseUrl();
        $cid = (int) $settingsManager->getSetting('forum.global_forums_course_id');

        $goToLink = '';
        if (!empty($cid)) {
            $goToLink = $baseUrl.'/main/forum/index.php?cid='.$cid.'&sid=0&gid=0';
        }

        return $this->json(['go_to' => $goToLink]);
    }

    #[Route('/invite-friends/{userId}/{groupId}', name: 'chamilo_core_social_invite_friends')]
    public function inviteFriends(int $userId, int $groupId, UserRepository $userRepository, UsergroupRepository $usergroupRepository, IllustrationRepository $illustrationRepository): JsonResponse
    {
        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $group = $usergroupRepository->find($groupId);
        if (!$group) {
            return $this->json(['error' => 'Group not found'], Response::HTTP_NOT_FOUND);
        }

        $friends = $userRepository->getFriendsNotInGroup($userId, $groupId);

        $friendsList = array_map(function ($friend) use ($illustrationRepository) {
            return [
                'id' => $friend->getId(),
                'name' => $friend->getFirstName() . ' ' . $friend->getLastName(),
                'avatar' => $illustrationRepository->getIllustrationUrl($friend),
            ];
        }, $friends);

        return $this->json(['friends' => $friendsList]);
    }

    #[Route('/add-users-to-group/{groupId}', name: 'chamilo_core_social_add_users_to_group')]
    public function addUsersToGroup(Request $request, int $groupId, UsergroupRepository $usergroupRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userIds = $data['userIds'] ?? [];

        try {
            $usergroupRepository->addUserToGroup($userIds, $groupId);
            return $this->json(['success' => true, 'message' => 'Users added to group successfully.']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/group/{groupId}/invited-users', name: 'chamilo_core_social_group_invited_users')]
    public function groupInvitedUsers(int $groupId, UsergroupRepository $usergroupRepository, IllustrationRepository $illustrationRepository): JsonResponse
    {
        $invitedUsers = $usergroupRepository->getInvitedUsersByGroup($groupId);

        $invitedUsersList = array_map(function ($user) use ($illustrationRepository) {
            return [
                'id' => $user['id'],
                'name' => $user['username'],
               // 'avatar' => $illustrationRepository->getIllustrationUrl($user),
            ];
        }, $invitedUsers);

        return $this->json(['invitedUsers' => $invitedUsersList]);
    }
}
