<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Repository\ExtraFieldOptionsRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Repository\LegalRepository;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\TrackEOnlineRepository;
use Chamilo\CoreBundle\Serializer\UserToJsonNormalizer;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ExtraFieldValue;
use MessageManager;
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
    public function inviteFriends(
        int $userId,
        int $groupId,
        UserRepository $userRepository,
        UsergroupRepository $usergroupRepository,
        IllustrationRepository $illustrationRepository
    ): JsonResponse {
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
                'name' => $friend->getFirstName().' '.$friend->getLastName(),
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
        } catch (Exception $e) {
            return $this->json(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/group/{groupId}/invited-users', name: 'chamilo_core_social_group_invited_users')]
    public function groupInvitedUsers(int $groupId, UsergroupRepository $usergroupRepository, IllustrationRepository $illustrationRepository): JsonResponse
    {
        $invitedUsers = $usergroupRepository->getInvitedUsersByGroup($groupId);

        $invitedUsersList = array_map(function ($user) {
            return [
                'id' => $user['id'],
                'name' => $user['username'],
                // 'avatar' => $illustrationRepository->getIllustrationUrl($user),
            ];
        }, $invitedUsers);

        return $this->json(['invitedUsers' => $invitedUsersList]);
    }

    #[Route('/user-profile/{userId}', name: 'chamilo_core_social_user_profile')]
    public function getUserProfile(
        int $userId,
        SettingsManager $settingsManager,
        LanguageRepository $languageRepository,
        UserRepository $userRepository,
        RequestStack $requestStack,
        TrackEOnlineRepository $trackOnlineRepository,
        ExtraFieldRepository $extraFieldRepository,
        ExtraFieldOptionsRepository $extraFieldOptionsRepository
    ): JsonResponse {
        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $baseUrl = $requestStack->getCurrentRequest()->getBaseUrl();
        $profileFieldsVisibilityJson = $settingsManager->getSetting('profile.profile_fields_visibility');
        $profileFieldsVisibility = json_decode($profileFieldsVisibilityJson, true)['options'] ?? [];

        $vCardUserLink = $profileFieldsVisibility['vcard'] ?? true ? $baseUrl.'/main/social/vcard_export.php?userId='.(int) $userId : '';

        $languageInfo = null;
        if ($profileFieldsVisibility['language'] ?? true) {
            $language = $languageRepository->findByIsoCode($user->getLocale());
            if ($language) {
                $languageInfo = [
                    'label' => $language->getOriginalName(),
                    'value' => $language->getEnglishName(),
                    'code' => $language->getIsocode(),
                ];
            }
        }

        $isUserOnline = $trackOnlineRepository->isUserOnline($userId);
        $userOnlyInChat = $this->checkUserStatus($userId, $userRepository);
        $extraFields = $this->getExtraFieldBlock($userId, $userRepository, $settingsManager, $extraFieldRepository, $extraFieldOptionsRepository);

        $response = [
            'vCardUserLink' => $vCardUserLink,
            'language' => $languageInfo,
            'visibility' => $profileFieldsVisibility,
            'isUserOnline' => $isUserOnline,
            'userOnlyInChat' => $userOnlyInChat,
            'extraFields' => $extraFields,
        ];

        return $this->json($response);
    }

    private function getExtraFieldBlock(
        int $userId,
        UserRepository $userRepository,
        SettingsManager $settingsManager,
        ExtraFieldRepository $extraFieldRepository,
        ExtraFieldOptionsRepository $extraFieldOptionsRepository
    ): array {
        $user = $userRepository->find($userId);
        if (!$user) {
            return [];
        }

        $fieldVisibilityConfig = $settingsManager->getSetting('profile.profile_fields_visibility');
        $fieldVisibility = $fieldVisibilityConfig ? json_decode($fieldVisibilityConfig, true)['options'] : [];

        $extraUserData = $userRepository->getExtraUserData($userId);
        $extraFieldsFormatted = [];
        foreach ($extraUserData as $key => $value) {
            $fieldVariable = str_replace('extra_', '', $key);

            $extraField = $extraFieldRepository->getHandlerFieldInfoByFieldVariable($fieldVariable, ExtraField::USER_FIELD_TYPE);
            if (!$extraField || !isset($fieldVisibility[$fieldVariable]) || !$fieldVisibility[$fieldVariable]) {
                continue;
            }

            $fieldValue = \is_array($value) ? implode(', ', $value) : $value;

            switch ($extraField['type']) {
                case ExtraField::FIELD_TYPE_RADIO:
                case ExtraField::FIELD_TYPE_SELECT:
                    $extraFieldOptions = $extraFieldOptionsRepository->getFieldOptionByFieldAndOption($extraField['id'], $fieldValue, ExtraField::USER_FIELD_TYPE);
                    if (!empty($extraFieldOptions)) {
                        $optionTexts = array_map(function ($option) {
                            return $option['display_text'];
                        }, $extraFieldOptions);
                        $fieldValue = implode(', ', $optionTexts);
                    }

                    break;

                case ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES:
                case ExtraField::FIELD_TYPE_GEOLOCALIZATION:
                    $geoData = explode('::', $fieldValue);
                    $locationName = $geoData[0];
                    $coordinates = $geoData[1] ?? '';
                    $fieldValue = $locationName;

                    break;
            }

            $extraFieldsFormatted[] = [
                'variable' => $fieldVariable,
                'label' => $extraField['display_text'],
                'value' => $fieldValue,
            ];
        }

        return $extraFieldsFormatted;
    }

    #[Route('/invitations/{userId}', name: 'chamilo_core_social_invitations')]
    public function getInvitations(
        int $userId,
        MessageRepository $messageRepository,
        UsergroupRepository $usergroupRepository,
        UserRepository $userRepository
    ): JsonResponse {
        $user = $this->getUser();
        if ($userId !== $user->getId()) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $receivedMessages = $messageRepository->findReceivedInvitationsByUser($user);
        $receivedInvitations = [];
        foreach ($receivedMessages as $message) {
            $sender = $message->getSender();
            $receivedInvitations[] = [
                'id' => $message->getId(),
                'itemId' => $sender->getId(),
                'itemName' => $sender->getFirstName().' '.$sender->getLastName(),
                'itemPicture' => $userRepository->getUserPicture($sender->getId()),
                'content' => $message->getContent(),
                'date' => $message->getSendDate()->format('Y-m-d H:i:s'),
                'canAccept' => true,
                'canDeny' => true,
            ];
        }

        $sentMessages = $messageRepository->findSentInvitationsByUser($user);
        $sentInvitations = [];
        foreach ($sentMessages as $message) {
            foreach ($message->getReceivers() as $receiver) {
                $receiverUser = $receiver->getReceiver();
                $sentInvitations[] = [
                    'id' => $message->getId(),
                    'itemId' => $receiverUser->getId(),
                    'itemName' => $receiverUser->getFirstName().' '.$receiverUser->getLastName(),
                    'itemPicture' => $userRepository->getUserPicture($receiverUser->getId()),
                    'content' => $message->getContent(),
                    'date' => $message->getSendDate()->format('Y-m-d H:i:s'),
                    'canAccept' => false,
                    'canDeny' => false,
                ];
            }
        }

        $pendingGroupInvitations = [];
        $pendingGroups = $usergroupRepository->getGroupsByUser($userId, Usergroup::GROUP_USER_PERMISSION_PENDING_INVITATION);
        foreach ($pendingGroups as $group) {
            $pendingGroupInvitations[] = [
                'id' => $group->getId(),
                'itemId' => $group->getId(),
                'itemName' => $group->getTitle(),
                'itemPicture' => $usergroupRepository->getUsergroupPicture($group->getId()),
                'content' => $group->getDescription(),
                'date' => $group->getCreatedAt()->format('Y-m-d H:i:s'),
                'canAccept' => true,
                'canDeny' => true,
            ];
        }

        return $this->json([
            'receivedInvitations' => $receivedInvitations,
            'sentInvitations' => $sentInvitations,
            'pendingGroupInvitations' => $pendingGroupInvitations,
        ]);
    }

    #[Route('/search', name: 'chamilo_core_social_search')]
    public function search(
        Request $request,
        UserRepository $userRepository,
        UsergroupRepository $usergroupRepository,
        TrackEOnlineRepository $trackOnlineRepository
    ): JsonResponse {
        $query = $request->query->get('query', '');
        $type = $request->query->get('type', 'user');
        $from = $request->query->getInt('from', 0);
        $numberOfItems = $request->query->getInt('number_of_items', 1000);

        $formattedResults = [];
        if ('user' === $type) {
            /** @var User $user */
            $user = $this->getUser();
            $results = $userRepository->searchUsersByTags($query, $user->getId(), 0, $from, $numberOfItems);
            foreach ($results as $item) {
                $isUserOnline = $trackOnlineRepository->isUserOnline($item['id']);
                $relation = $userRepository->getUserRelationWithType($user->getId(), $item['id']);
                $formattedResults[] = [
                    'id' => $item['id'],
                    'name' => $item['firstname'].' '.$item['lastname'],
                    'avatar' => $userRepository->getUserPicture($item['id']),
                    'role' => 5 === $item['status'] ? 'student' : 'teacher',
                    'status' => $isUserOnline ? 'online' : 'offline',
                    'url' => '/social?id='.$item['id'],
                    'relationType' => $relation['relationType'] ?? null,
                ];
            }
        } elseif ('group' === $type) {
            // Perform group search
            $results = $usergroupRepository->searchGroupsByTags($query, $from, $numberOfItems);
            foreach ($results as $item) {
                $formattedResults[] = [
                    'id' => $item['id'],
                    'name' => $item['title'],
                    'description' => $item['description'] ?? '',
                    'image' => $usergroupRepository->getUsergroupPicture($item['id']),
                    'url' => '/resources/usergroups/show/'.$item['id'],
                ];
            }
        }

        return $this->json(['results' => $formattedResults]);
    }

    #[Route('/group-details/{groupId}', name: 'chamilo_core_social_group_details')]
    public function groupDetails(
        int $groupId,
        UsergroupRepository $usergroupRepository,
        TrackEOnlineRepository $trackOnlineRepository
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        /** @var Usergroup $group */
        $group = $usergroupRepository->find($groupId);
        if (!$group) {
            return $this->json(['error' => 'Group not found'], Response::HTTP_NOT_FOUND);
        }

        $isMember = $usergroupRepository->isGroupMember($groupId, $user);
        $role = $usergroupRepository->getUserGroupRole($groupId, $user->getId());
        $isUserOnline = $trackOnlineRepository->isUserOnline($user->getId());
        $isModerator = $usergroupRepository->isGroupModerator($groupId, $user->getId());

        $groupDetails = [
            'id' => $group->getId(),
            'title' => $group->getTitle(),
            'description' => $group->getDescription(),
            'image' => $usergroupRepository->getUsergroupPicture($group->getId()),
            'isMember' => $isMember,
            'isModerator' => $isModerator,
            'role' => $role,
            'isUserOnline' => $isUserOnline,
            'visibility' => (int) $group->getVisibility(),
        ];

        return $this->json($groupDetails);
    }

    #[Route('/group-action', name: 'chamilo_core_social_group_action')]
    public function groupAction(Request $request, UsergroupRepository $usergroupRepository, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $userId = $data['userId'] ?? null;
        $groupId = $data['groupId'] ?? null;
        $action = $data['action'] ?? null;

        if (!$userId || !$groupId || !$action) {
            return $this->json(['error' => 'Missing parameters'], Response::HTTP_BAD_REQUEST);
        }

        try {
            switch ($action) {
                case 'join':
                    $usergroupRepository->addUserToGroup($userId, $groupId);

                    break;

                case 'leave':
                    $usergroupRepository->removeUserFromGroup($userId, $groupId);

                    break;

                default:
                    return $this->json(['error' => 'Invalid action'], Response::HTTP_BAD_REQUEST);
            }

            $em->flush();

            return $this->json(['success' => 'Action completed successfully']);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/user-action', name: 'chamilo_core_social_user_action')]
    public function userAction(
        Request $request,
        UserRepository $userRepository,
        MessageRepository $messageRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $userId = $data['userId'] ?? null;
        $targetUserId = $data['targetUserId'] ?? null;
        $action = $data['action'] ?? null;
        $isMyFriend = $data['is_my_friend'] ?? false;
        $subject = $data['subject'] ?? '';
        $content = $data['content'] ?? '';

        if (!$userId || !$targetUserId || !$action) {
            return $this->json(['error' => 'Missing parameters'], Response::HTTP_BAD_REQUEST);
        }

        $userSender = $userRepository->find($userId);
        $userReceiver = $userRepository->find($targetUserId);

        if (null === $userSender || null === $userReceiver) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            switch ($action) {
                case 'send_invitation':
                    $result = $messageRepository->sendInvitationToFriend($userSender, $userReceiver, $subject, $content);
                    if (!$result) {
                        return $this->json(['error' => 'Invitation already exists or could not be sent'], Response::HTTP_BAD_REQUEST);
                    }

                    break;

                case 'send_message':
                    $result = MessageManager::send_message($targetUserId, $subject, $content);

                    break;

                case 'add_friend':
                    $relationType = $isMyFriend ? UserRelUser::USER_RELATION_TYPE_FRIEND : UserRelUser::USER_UNKNOWN;

                    $userRepository->relateUsers($userSender, $userReceiver, $relationType);
                    $userRepository->relateUsers($userReceiver, $userSender, $relationType);

                    $messageRepository->invitationAccepted($userSender, $userReceiver);

                    break;

                case 'deny_friend':
                    $messageRepository->invitationDenied($userSender, $userReceiver);

                    break;

                default:
                    return $this->json(['error' => 'Invalid action'], Response::HTTP_BAD_REQUEST);
            }

            $em->flush();

            return $this->json(['success' => 'Action completed successfully']);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function checkUserStatus(int $userId, UserRepository $userRepository): bool
    {
        $userStatus = $userRepository->getExtraUserDataByField($userId, 'user_chat_status');

        return !empty($userStatus) && isset($userStatus['user_chat_status']) && 1 === (int) $userStatus['user_chat_status'];
    }
}
