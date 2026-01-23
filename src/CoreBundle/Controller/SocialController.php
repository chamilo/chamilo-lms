<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Chamilo\CoreBundle\Entity\Legal;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageAttachment;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Helpers\DateTimeHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldOptionsRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Repository\LegalRepository;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\MessageAttachmentRepository;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\TrackEOnlineRepository;
use Chamilo\CoreBundle\Serializer\UserToJsonNormalizer;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ExtraFieldValue;
use MessageManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use UserManager;

#[IsGranted('ROLE_USER')]
#[Route('/social-network')]
class SocialController extends AbstractController
{
    private const TERMS_SECTIONS = [
        0 => ['title' => 'Terms and Conditions', 'subtitle' => null],
        1 => ['title' => 'Personal data collection', 'subtitle' => 'Why do we collect this data?'],
        2 => ['title' => 'Personal data recording', 'subtitle' => 'Where do we record the data?'],
        3 => ['title' => 'Personal data organization', 'subtitle' => 'How is the data structured?'],
        4 => ['title' => 'Personal data structure', 'subtitle' => 'How is the data structured in this software?'],
        5 => ['title' => 'Personal data conservation', 'subtitle' => 'How long do we save the data?'],
        6 => ['title' => 'Personal data adaptation or modification', 'subtitle' => 'What changes can we make to the data?'],
        7 => ['title' => 'Personal data extraction', 'subtitle' => 'What do we extract data for and which data is it?'],
        8 => ['title' => 'Personal data queries', 'subtitle' => 'Who can consult the personal data? For what purpose?'],
        9 => ['title' => 'Personal data use', 'subtitle' => 'How and for what can we use the personal data?'],
        10 => ['title' => 'Personal data communication and sharing', 'subtitle' => 'With whom can we share them?'],
        11 => ['title' => 'Personal data interconnection', 'subtitle' => 'Do we have another system with which Chamilo interacts?'],
        12 => ['title' => 'Personal data limitation', 'subtitle' => 'What are the limits that we will always respect?'],
        13 => ['title' => 'Personal data deletion', 'subtitle' => 'After how long do we erase the data?'],
        14 => ['title' => 'Personal data destruction', 'subtitle' => 'What happens if the data is destroyed?'],
        15 => ['title' => 'Personal data profiling', 'subtitle' => 'For what purpose do we process personal data?'],
    ];

    public function __construct(
        private readonly UserHelper $userHelper,
        private readonly DateTimeHelper $dateTimeHelper,
    ) {}

    #[Route('/personal-data/{userId}', name: 'chamilo_core_social_personal_data')]
    public function getPersonalData(
        int $userId,
        SettingsManager $settingsManager,
        UserToJsonNormalizer $userToJsonNormalizer
    ): JsonResponse {
        $propertiesToJson = $userToJsonNormalizer->serializeUserData($userId);
        $properties = $propertiesToJson ? json_decode($propertiesToJson, true) : [];

        $officerData = [
            ['name' => $settingsManager->getSetting('privacy.data_protection_officer_name')],
            ['role' => $settingsManager->getSetting('privacy.data_protection_officer_role')],
            ['email' => $settingsManager->getSetting('privacy.data_protection_officer_email')],
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
        Request $request,
        SettingsManager $settingsManager,
        TranslatorInterface $translator,
        LegalRepository $legalTermsRepo,
        UserRepository $userRepo,
        LanguageRepository $languageRepo
    ): JsonResponse {
        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $isoCode = $user->getLocale();
        $showAccepted = '1' === (string) $request->query->get('accepted', '0');

        $languageId = 0;
        $version = 0;

        if ($showAccepted) {
            $extraFieldValue = new ExtraFieldValue('user');
            $value = $extraFieldValue->get_values_by_handler_and_field_variable($userId, 'legal_accept');

            if (!empty($value['value'])) {
                [$acceptedVersion, $acceptedLanguageId] = explode(':', $value['value']);
                $languageId = (int) $acceptedLanguageId;
                $version = (int) $acceptedVersion;
            } else {
                return $this->json(['error' => 'No accepted terms found'], Response::HTTP_NOT_FOUND);
            }
        } else {
            $resolved = $this->resolveLatestTermsVersion($isoCode, $languageRepo, $legalTermsRepo, $settingsManager);
            if (!$resolved) {
                return $this->json(['error' => 'Terms not found'], Response::HTTP_NOT_FOUND);
            }
            $languageId = (int) $resolved['languageId'];
            $version = (int) $resolved['version'];
        }

        $rows = $legalTermsRepo->findByLanguageAndVersion($languageId, $version);
        if (empty($rows)) {
            return $this->json(['error' => 'Terms not found'], Response::HTTP_NOT_FOUND);
        }

        $terms = [];
        foreach ($rows as $row) {
            $type = $row->getType();
            $section = self::TERMS_SECTIONS[$type] ?? self::TERMS_SECTIONS[0];

            $content = $row->getContent() ?? '';
            if ('' === trim($content)) {
                continue;
            }

            $terms[] = [
                'title' => $translator->trans($section['title'], [], 'messages', $isoCode),
                'subtitle' => $section['subtitle']
                    ? $translator->trans($section['subtitle'], [], 'messages', $isoCode)
                    : null,
                'content' => $content,
            ];
        }

        $pubDate = $this->dateTimeHelper->localTimeYmdHis($rows[0]->getDate(), null, 'UTC');

        return $this->json([
            'terms' => $terms,
            'date_text' => $translator->trans('Publication date', [], 'messages', $isoCode).': '.$pubDate,
            'version' => $version,
            'language_id' => $languageId,
            'showing_accepted' => $showAccepted,
        ]);
    }

    #[Route('/legal-status/{userId}', name: 'chamilo_core_social_legal_status')]
    public function getLegalStatus(
        int $userId,
        #[CurrentUser]
        User $currentUser,
        SettingsManager $settingsManager,
        TranslatorInterface $translator,
        UserRepository $userRepo,
        LanguageRepository $languageRepo,
        LegalRepository $legalTermsRepo
    ): JsonResponse {
        $allowTermsConditions = 'true' === $settingsManager->getSetting('registration.allow_terms_conditions');
        if (!$allowTermsConditions) {
            throw $this->createAccessDeniedException($translator->trans('No terms and conditions available'));
        }

        /*if (!$currentUser) {
            throw $this->createAccessDeniedException(
                $translator->trans('User not found')
            );
        }*/

        if ($userId !== $currentUser->getId()) {
            throw $this->createAccessDeniedException();
        }

        $isoCode = $currentUser->getLocale();

        $resolved = $this->resolveLatestTermsVersion($isoCode, $languageRepo, $legalTermsRepo, $settingsManager);
        if (!$resolved) {
            return $this->json([
                'isAccepted' => false,
                'message' => $translator->trans('No terms and conditions available', [], 'messages'),
            ], Response::HTTP_NOT_FOUND);
        }

        $latestLanguageId = (int) $resolved['languageId'];
        $latestVersion = (int) $resolved['version'];

        $extraFieldValue = new ExtraFieldValue('user');
        $value = $extraFieldValue->get_values_by_handler_and_field_variable($userId, 'legal_accept');

        if (empty($value['value'])) {
            return $this->json([
                'isAccepted' => false,
                'message' => $translator->trans('Send legal agreement', [], 'messages'),
            ]);
        }

        [$acceptedVersion, $acceptedLanguageId, $acceptedTime] = explode(':', $value['value']);
        $dateTime = new DateTime('@'.(int) $acceptedTime);

        $isLatestAccepted = null !== $latestVersion
            && (int) $acceptedLanguageId === (int) $latestLanguageId
            && (int) $acceptedVersion === (int) $latestVersion;

        if (!$isLatestAccepted) {
            return $this->json([
                'isAccepted' => false,
                'acceptDate' => $dateTime->format('Y-m-d H:i:s'),
                'message' => $translator->trans('Please accept the latest version of the terms and conditions.', [], 'messages'),
            ]);
        }

        return $this->json([
            'isAccepted' => true,
            'acceptDate' => $dateTime->format('Y-m-d H:i:s'),
            'message' => '',
        ]);
    }

    #[Route('/send-legal-term', name: 'chamilo_core_social_send_legal_term', methods: ['POST'])]
    public function sendLegalTerm(
        Request $request,
        #[CurrentUser]
        User $currentUser,
        SettingsManager $settingsManager,
        TranslatorInterface $translator,
        LegalRepository $legalTermsRepo,
        UserRepository $userRepo,
        LanguageRepository $languageRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $requestedUserId = isset($data['userId']) ? (int) $data['userId'] : 0;

        // Non-admin users can only accept terms for themselves.
        if (!$this->isGranted('ROLE_ADMIN')) {
            $targetUserId = $currentUser->getId();
            $user = $currentUser;
        } else {
            $targetUserId = $requestedUserId > 0 ? $requestedUserId : 0;
            if (0 === $targetUserId) {
                return $this->json(['error' => 'Missing or invalid userId'], Response::HTTP_BAD_REQUEST);
            }
            $user = $userRepo->find($targetUserId);
            if (!$user) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }
        }

        $isoCode = $user->getLocale();
        $resolved = $this->resolveLatestTermsVersion($isoCode, $languageRepo, $legalTermsRepo, $settingsManager);
        if (!$resolved) {
            return $this->json(['error' => 'Terms not found'], Response::HTTP_NOT_FOUND);
        }
        $languageId = (int) $resolved['languageId'];
        $version = (int) $resolved['version'];

        $legalAcceptType = $version.':'.$languageId.':'.time();
        UserManager::update_extra_field_value($targetUserId, 'legal_accept', $legalAcceptType);

        // Notify bosses (kept as-is, now based on $targetUserId)
        $bossList = UserManager::getStudentBossList($targetUserId);
        if (!empty($bossList)) {
            $bossList = array_column($bossList, 'boss_id');
            foreach ($bossList as $bossId) {
                $subjectEmail = \sprintf(
                    $translator->trans('User %s signed the agreement.', [], 'messages'),
                    $user->getFullName()
                );
                $contentEmail = \sprintf(
                    $translator->trans('User %s signed the agreement the %s.', [], 'messages'),
                    $user->getFullName(),
                    $this->dateTimeHelper->localTimeYmdHis(null, null, 'UTC')
                );

                MessageManager::send_message_simple(
                    (int) $bossId,
                    $subjectEmail,
                    $contentEmail,
                    $targetUserId
                );
            }
        }

        return $this->json([
            'success' => true,
            'message' => $translator->trans('Terms accepted successfully.', [], 'messages'),
        ]);
    }

    #[Route('/delete-legal', name: 'chamilo_core_social_delete_legal', methods: ['POST'])]
    public function deleteLegal(
        #[CurrentUser]
        User $currentUser,
        Request $request,
        TranslatorInterface $translator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        // Backward compatible: if userId is not provided, default to current user.
        $requestedUserId = isset($data['userId']) ? (int) $data['userId'] : 0;

        // Non-admin users can only revoke their own consent (ignore provided userId if different).
        if (!$this->isGranted('ROLE_ADMIN')) {
            $targetUserId = $currentUser->getId();
        } else {
            // Admin must explicitly provide a valid userId.
            $targetUserId = $requestedUserId > 0 ? $requestedUserId : 0;
            if (0 === $targetUserId) {
                return $this->json(['error' => 'Missing or invalid userId'], Response::HTTP_BAD_REQUEST);
            }
        }

        $extraFieldValue = new ExtraFieldValue('user');

        $value = $extraFieldValue->get_values_by_handler_and_field_variable($targetUserId, 'legal_accept');
        if (!empty($value['id'])) {
            $extraFieldValue->delete($value['id']);
        }

        $value = $extraFieldValue->get_values_by_handler_and_field_variable($targetUserId, 'termactivated');
        if (!empty($value['id'])) {
            $extraFieldValue->delete($value['id']);
        }

        return $this->json([
            'success' => true,
            'message' => $translator->trans('Legal consent revoked successfully.'),
        ]);
    }

    #[Route('/handle-privacy-request', name: 'chamilo_core_social_handle_privacy_request', methods: ['POST'])]
    public function handlePrivacyRequest(
        Request $request,
        #[CurrentUser]
        User $currentUser,
        SettingsManager $settingsManager,
        UserRepository $userRepo,
        TranslatorInterface $translator,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['success' => false, 'message' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $requestedUserId = isset($data['userId']) ? (int) $data['userId'] : 0;
        $explanation = (string) ($data['explanation'] ?? '');
        $requestType = (string) ($data['requestType'] ?? '');

        // Non-admin users can only create requests for themselves
        if (!$this->isGranted('ROLE_ADMIN')) {
            $targetUserId = $currentUser->getId();
            $user = $currentUser;
        } else {
            // Admin can create requests for another user if explicitly provided
            $targetUserId = $requestedUserId > 0 ? $requestedUserId : 0;
            if (0 === $targetUserId) {
                return $this->json(['success' => false, 'message' => 'Missing or invalid userId'], Response::HTTP_BAD_REQUEST);
            }
            $user = $userRepo->find($targetUserId);
            if (!$user) {
                return $this->json(['success' => false, 'message' => 'User not found'], Response::HTTP_NOT_FOUND);
            }
        }

        $baseUrl = $request->getSchemeAndHttpHost().$request->getBasePath();
        $link = $baseUrl.'/main/admin/user_list_consent.php';

        if ('delete_account' === $requestType) {
            $fieldToUpdate = 'request_for_delete_account';
            $justificationFieldToUpdate = 'request_for_delete_account_justification';
            $emailSubject = $translator->trans('Request for account removal');
            $emailContent = \sprintf(
                $translator->trans('User %s asked for the deletion of his/her account, explaining that "%s". You can process the request here: %s'),
                $user->getFullName(),
                $explanation,
                '<a href="'.$link.'">'.$link.'</a>'
            );
        } elseif ('delete_legal' === $requestType) {
            $fieldToUpdate = 'request_for_legal_agreement_consent_removal';
            $justificationFieldToUpdate = 'request_for_legal_agreement_consent_removal_justification';
            $emailSubject = $translator->trans('Request for consent withdrawal on legal terms');
            $emailContent = \sprintf(
                $translator->trans('User %s asked for the removal of his/her consent to our legal terms, explaining that "%s". You can process the request here: %s'),
                $user->getFullName(),
                $explanation,
                '<a href="'.$link.'">'.$link.'</a>'
            );
        } else {
            return $this->json(['success' => false, 'message' => 'Invalid action type'], Response::HTTP_BAD_REQUEST);
        }

        UserManager::createDataPrivacyExtraFields();
        UserManager::update_extra_field_value($targetUserId, $fieldToUpdate, 1);
        UserManager::update_extra_field_value($targetUserId, $justificationFieldToUpdate, $explanation);

        $emailOfficer = (string) $settingsManager->getSetting('profile.data_protection_officer_email');

        if ('' !== trim($emailOfficer)) {
            $email = (new Email())
                ->from($user->getEmail())
                ->to($emailOfficer)
                ->subject($emailSubject)
                ->html($emailContent)
            ;

            $mailer->send($email);
        } else {
            MessageManager::sendMessageToAllAdminUsers($user->getId(), $emailSubject, $emailContent);
        }

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
        $items = [];
        $goToUrl = '';

        if (!empty($cid)) {
            $threads = $forumThreadRepository->getThreadsBySubscriptions($userId, $cid);
            foreach ($threads as $thread) {
                $threadId = $thread->getIid();
                $forumId = (int) $thread->getForum()->getIid();
                $items[] = [
                    'id' => $threadId,
                    'name' => $thread->getTitle(),
                    'description' => '',
                    'url' => $baseUrl.'/main/forum/viewthread.php?cid='.$cid.'&sid=0&gid=0&forum='.$forumId.'&thread='.$threadId,
                ];
            }
            $goToUrl = $baseUrl.'/main/forum/index.php?cid='.$cid.'&sid=0&gid=0';
        } else {
            $groups = $usergroupRepository->getGroupsByUser($userId);
            foreach ($groups as $group) {
                $items[] = [
                    'id' => $group->getId(),
                    'name' => $group->getTitle(),
                    'description' => $group->getDescription(),
                    'url' => $baseUrl.'/resources/usergroups/show/'.$group->getId(),
                ];
            }
        }

        return $this->json([
            'items' => $items,
            'go_to' => $goToUrl,
        ]);
    }

    #[Route('/group/{groupId}/discussion/{discussionId}/messages', name: 'chamilo_core_social_group_discussion_messages')]
    public function getDiscussionMessages(
        $groupId,
        $discussionId,
        MessageRepository $messageRepository,
        UserRepository $userRepository,
        MessageAttachmentRepository $attachmentRepository
    ): JsonResponse {
        $messages = $messageRepository->getMessagesByGroupAndMessage((int) $groupId, (int) $discussionId);

        $formattedMessages = $this->formatMessagesHierarchy($messages, $userRepository, $attachmentRepository);

        return $this->json($formattedMessages);
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
            throw $this->createNotFoundException('User not found');
        }

        $baseUrl = $requestStack->getCurrentRequest()->getBaseUrl();
        $profileFieldsVisibilityJson = $settingsManager->getSetting('profile.profile_fields_visibility');
        $decoded = json_decode($profileFieldsVisibilityJson, true);
        $profileFieldsVisibility = (\is_array($decoded) && isset($decoded['options']))
            ? $decoded['options']
            : [];

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
        $decoded = json_decode($fieldVisibilityConfig, true);
        $fieldVisibility = (\is_array($decoded) && isset($decoded['options']))
            ? $decoded['options']
            : [];

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
                    $extraFieldOptions = $extraFieldOptionsRepository->getFieldOptionByFieldAndOption(
                        $extraField['id'],
                        $fieldValue,
                        ExtraField::USER_FIELD_TYPE
                    );
                    if (!empty($extraFieldOptions)) {
                        $fieldValue = implode(
                            ', ',
                            array_map(
                                fn (ExtraFieldOptions $opt) => $opt->getDisplayText(),
                                $extraFieldOptions
                            )
                        );
                    }

                    break;

                case ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES:
                case ExtraField::FIELD_TYPE_GEOLOCALIZATION:
                    $geoData = explode('::', $fieldValue ?: '');
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
        UserRepository $userRepository,
        TranslatorInterface $translator
    ): JsonResponse {
        $user = $this->userHelper->getCurrent();
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

        /** @var Usergroup $group */
        foreach ($pendingGroups as $group) {
            $isGroupVisible = 1 === (int) $group->getVisibility();
            $infoVisibility = !$isGroupVisible ? ' - '.$translator->trans('This group is closed.') : '';
            $pendingGroupInvitations[] = [
                'id' => $group->getId(),
                'itemId' => $group->getId(),
                'itemName' => $group->getTitle().$infoVisibility,
                'itemPicture' => $usergroupRepository->getUsergroupPicture($group->getId()),
                'content' => $group->getDescription(),
                'date' => $group->getCreatedAt()->format('Y-m-d H:i:s'),
                'canAccept' => $isGroupVisible,
                'canDeny' => true,
            ];
        }

        return $this->json([
            'receivedInvitations' => $receivedInvitations,
            'sentInvitations' => $sentInvitations,
            'pendingGroupInvitations' => $pendingGroupInvitations,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/invitations/count/{userId}', name: 'chamilo_core_social_invitations_count')]
    public function getInvitationsCount(
        int $userId,
        MessageRepository $messageRepository,
        UsergroupRepository $usergroupRepository
    ): JsonResponse {
        $user = $this->userHelper->getCurrent();
        if ($userId !== $user->getId()) {
            return $this->json(['error' => 'Unauthorized']);
        }

        $receivedMessagesCount = \count($messageRepository->findReceivedInvitationsByUser($user));
        $pendingGroupInvitationsCount = \count($usergroupRepository->getGroupsByUser($userId, Usergroup::GROUP_USER_PERMISSION_PENDING_INVITATION));
        $totalInvitationsCount = $receivedMessagesCount + $pendingGroupInvitationsCount;

        return $this->json(['totalInvitationsCount' => $totalInvitationsCount]);
    }

    #[Route('/search', name: 'chamilo_core_social_search')]
    public function search(
        Request $request,
        UserRepository $userRepository,
        UsergroupRepository $usergroupRepository,
        TrackEOnlineRepository $trackOnlineRepository,
        MessageRepository $messageRepository
    ): JsonResponse {
        $query = $request->query->get('query', '');
        $type = $request->query->get('type', 'user');
        $from = $request->query->getInt('from', 0);
        $numberOfItems = $request->query->getInt('number_of_items', 1000);

        $formattedResults = [];
        if ('user' === $type) {
            $user = $this->userHelper->getCurrent();
            $results = $userRepository->searchUsersByTags($query, $user->getId(), 0, $from, $numberOfItems);
            foreach ($results as $item) {
                $isUserOnline = $trackOnlineRepository->isUserOnline($item['id']);
                $relation = $userRepository->getUserRelationWithType($user->getId(), $item['id']);
                $userReceiver = $userRepository->find($item['id']);
                $existingInvitations = $messageRepository->existingInvitations($user, $userReceiver);
                $formattedResults[] = [
                    'id' => $item['id'],
                    'name' => $item['firstname'].' '.$item['lastname'],
                    'avatar' => $userRepository->getUserPicture($item['id']),
                    'role' => 5 === $item['status'] ? 'student' : 'teacher',
                    'status' => $isUserOnline ? 'online' : 'offline',
                    'url' => '/social?id='.$item['id'],
                    'relationType' => $relation['relationType'] ?? null,
                    'existingInvitations' => $existingInvitations,
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
        $user = $this->userHelper->getCurrent();
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
            'url' => $group->getUrl(),
            'image' => $usergroupRepository->getUsergroupPicture($group->getId()),
            'visibility' => (int) $group->getVisibility(),
            'allowMembersToLeaveGroup' => $group->getAllowMembersToLeaveGroup(),
            'isMember' => $isMember,
            'isModerator' => $isModerator,
            'role' => $role,
            'isUserOnline' => $isUserOnline,
            'isAllowedToLeave' => 1 === $group->getAllowMembersToLeaveGroup(),
        ];

        return $this->json($groupDetails);
    }

    #[Route('/group-action', name: 'chamilo_core_social_group_action')]
    public function group(
        Request $request,
        UsergroupRepository $usergroupRepository,
        EntityManagerInterface $em,
        MessageRepository $messageRepository
    ): JsonResponse {
        if (str_starts_with($request->headers->get('Content-Type'), 'multipart/form-data')) {
            $userId = $request->request->get('userId');
            $groupId = $request->request->get('groupId');
            $action = $request->request->get('action');
            $title = $request->request->get('title', '');
            $content = $request->request->get('content', '');
            $parentId = $request->request->get('parentId', 0);
            $editMessageId = $request->request->get('messageId', 0);

            $structuredFiles = [];
            if ($request->files->has('files')) {
                $files = $request->files->get('files');
                foreach ($files as $file) {
                    $structuredFiles[] = [
                        'name' => $file->getClientOriginalName(),
                        'full_path' => $file->getRealPath(),
                        'type' => $file->getMimeType(),
                        'tmp_name' => $file->getPathname(),
                        'error' => $file->getError(),
                        'size' => $file->getSize(),
                    ];
                }
            }
        } else {
            $data = json_decode($request->getContent(), true);
            $userId = $data['userId'] ?? null;
            $groupId = $data['groupId'] ?? null;
            $action = $data['action'] ?? null;
        }

        if (!$userId || !$groupId || !$action) {
            return $this->json(['error' => 'Missing parameters'], Response::HTTP_BAD_REQUEST);
        }

        try {
            switch ($action) {
                case 'accept':
                    $userRole = $usergroupRepository->getUserGroupRole($groupId, $userId);
                    if (\in_array(
                        $userRole,
                        [
                            Usergroup::GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER,
                            Usergroup::GROUP_USER_PERMISSION_PENDING_INVITATION,
                        ]
                    )) {
                        $usergroupRepository->updateUserRole($userId, $groupId, Usergroup::GROUP_USER_PERMISSION_READER);
                    }

                    break;

                case 'join':
                    $usergroupRepository->addUserToGroup($userId, $groupId);

                    break;

                case 'deny':
                    $usergroupRepository->removeUserFromGroup($userId, $groupId, false);

                    break;

                case 'leave':
                    $usergroupRepository->removeUserFromGroup($userId, $groupId);

                    break;

                case 'reply_message_group':
                    $title = $title ?: substr(strip_tags($content), 0, 50);

                    // no break
                case 'edit_message_group':
                case 'add_message_group':
                    $res = MessageManager::send_message(
                        $userId,
                        $title,
                        $content,
                        $structuredFiles,
                        [],
                        $groupId,
                        $parentId,
                        $editMessageId,
                        0,
                        $userId,
                        false,
                        0,
                        false,
                        false,
                        Message::MESSAGE_TYPE_GROUP
                    );

                    break;

                case 'delete_message_group':
                    $messageId = $data['messageId'] ?? null;

                    if (!$messageId) {
                        return $this->json(['error' => 'Missing messageId parameter'], Response::HTTP_BAD_REQUEST);
                    }

                    $messageRepository->deleteTopicAndChildren($groupId, $messageId);

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
    public function user(
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
            return $this->json(['error' => 'Missing parameters']);
        }

        $currentUser = $userRepository->find($userId);
        $friendUser = $userRepository->find($targetUserId);

        if (null === $currentUser || null === $friendUser) {
            return $this->json(['error' => 'User not found']);
        }

        try {
            switch ($action) {
                case 'send_invitation':
                    $result = $messageRepository->sendInvitationToFriend($currentUser, $friendUser, $subject, $content);
                    if (!$result) {
                        return $this->json(['error' => 'Invitation already exists or could not be sent']);
                    }

                    break;

                case 'send_message':
                    $result = MessageManager::send_message($friendUser->getId(), $subject, $content);

                    break;

                case 'add_friend':
                    $relationType = $isMyFriend ? UserRelUser::USER_RELATION_TYPE_FRIEND : UserRelUser::USER_UNKNOWN;

                    $userRepository->relateUsers($currentUser, $friendUser, $relationType);
                    $userRepository->relateUsers($friendUser, $currentUser, $relationType);

                    $messageRepository->invitationAccepted($friendUser, $currentUser);

                    break;

                case 'deny_friend':
                    $messageRepository->invitationDenied($friendUser, $currentUser);

                    break;

                default:
                    return $this->json(['error' => 'Invalid action']);
            }

            $em->flush();

            return $this->json(['success' => 'Action completed successfully']);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/user-relation/{currentUserId}/{profileUserId}', name: 'chamilo_core_social_get_user_relation')]
    public function getUserRelation(int $currentUserId, int $profileUserId, EntityManagerInterface $em): JsonResponse
    {
        $isAllowed = $this->checkUserRelationship($currentUserId, $profileUserId, $em);

        return $this->json([
            'isAllowed' => $isAllowed,
        ]);
    }

    #[Route('/online-status', name: 'chamilo_core_social_get_online_status', methods: ['POST'])]
    public function getOnlineStatus(Request $request, TrackEOnlineRepository $trackOnlineRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userIds = $data['userIds'] ?? [];

        $onlineStatuses = [];
        foreach ($userIds as $userId) {
            $onlineStatuses[$userId] = $trackOnlineRepository->isUserOnline($userId);
        }

        return $this->json($onlineStatuses);
    }

    #[Route('/upload-group-picture/{groupId}', name: 'chamilo_core_social_upload_group_picture')]
    public function uploadGroupPicture(
        Request $request,
        int $groupId,
        UsergroupRepository $usergroupRepository,
        IllustrationRepository $illustrationRepository
    ): JsonResponse {
        $file = $request->files->get('picture');
        if ($file instanceof UploadedFile) {
            $userGroup = $usergroupRepository->find($groupId);
            $illustrationRepository->addIllustration($userGroup, $this->userHelper->getCurrent(), $file);
        }

        return new JsonResponse(['success' => 'Group and image saved successfully'], Response::HTTP_OK);
    }

    #[Route('/terms-restrictions/{userId}', name: 'chamilo_core_social_terms_restrictions')]
    public function checkTermsRestrictions(
        int $userId,
        UserRepository $userRepo,
        ExtraFieldRepository $extraFieldRepository,
        TranslatorInterface $translator,
        SettingsManager $settingsManager
    ): JsonResponse {
        /** @var User $user */
        $user = $userRepo->find($userId);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $isAdmin = $user->isAdmin() || $user->isSuperAdmin();

        $termActivated = false;
        $blockButton = false;
        $infoMessage = '';

        if (!$isAdmin) {
            if ('true' === $settingsManager->getSetting('profile.show_terms_if_profile_completed')) {
                $extraFieldValue = new ExtraFieldValue('user');
                $value = $extraFieldValue->get_values_by_handler_and_field_variable($userId, 'termactivated');
                if (isset($value['value'])) {
                    $termActivated = !empty($value['value']) && 1 === (int) $value['value'];
                }

                if (false === $termActivated) {
                    $blockButton = true;
                    $infoMessage .= $translator->trans('The terms and conditions have not yet been validated by your tutor.').'&nbsp;';
                }

                if (!$user->isProfileCompleted()) {
                    $blockButton = true;
                    $infoMessage .= $translator->trans('You must first fill your profile to enable the terms and conditions validation.');
                }
            }
        }

        return $this->json([
            'blockButton' => $blockButton,
            'infoMessage' => $infoMessage,
        ]);
    }

    /**
     * Formats a hierarchical structure of messages for display.
     *
     * This function takes an array of Message entities and recursively formats them into a hierarchical structure.
     * Each message is formatted with details such as user information, creation date, content, and attachments.
     * The function also assigns a level to each message based on its depth in the hierarchy for display purposes.
     */
    private function formatMessagesHierarchy(array $messages, UserRepository $userRepository, MessageAttachmentRepository $attachmentRepository, ?int $parentId = null, int $level = 0): array
    {
        $formattedMessages = [];

        /** @var Message $message */
        foreach ($messages as $message) {
            if (($message->getParent() ? $message->getParent()->getId() : null) === $parentId) {
                $attachments = $message->getAttachments();
                $attachmentsUrls = [];
                $attachmentSize = 0;
                if ($attachments) {
                    /** @var MessageAttachment $attachment */
                    foreach ($attachments as $attachment) {
                        $attachmentsUrls[] = [
                            'link' => $attachmentRepository->getResourceFileDownloadUrl($attachment),
                            'filename' => $attachment->getFilename(),
                            'size' => $attachment->getSize(),
                        ];
                        $attachmentSize += $attachment->getSize();
                    }
                }
                $formattedMessage = [
                    'id' => $message->getId(),
                    'user' => $message->getSender()->getFullName(),
                    'created' => $message->getSendDate()->format(DateTimeInterface::ATOM),
                    'title' => $message->getTitle(),
                    'content' => $message->getContent(),
                    'parentId' => $message->getParent() ? $message->getParent()->getId() : null,
                    'avatar' => $userRepository->getUserPicture($message->getSender()->getId()),
                    'senderId' => $message->getSender()->getId(),
                    'attachment' => $attachmentsUrls ?? null,
                    'attachmentSize' => $attachmentSize > 0 ? $attachmentSize : null,
                    'level' => $level,
                ];

                $children = $this->formatMessagesHierarchy($messages, $userRepository, $attachmentRepository, $message->getId(), $level + 1);
                if (!empty($children)) {
                    $formattedMessage['children'] = $children;
                }

                $formattedMessages[] = $formattedMessage;
            }
        }

        return $formattedMessages;
    }

    /**
     * Checks the relationship between the current user and another user.
     *
     * This method first checks for a direct relationship between the two users. If no direct relationship is found,
     * it then checks for indirect relationships through common friends (friends of friends).
     */
    private function checkUserRelationship(int $currentUserId, int $otherUserId, EntityManagerInterface $em): bool
    {
        if ($currentUserId === $otherUserId) {
            return true;
        }

        $relation = $em->getRepository(UserRelUser::class)
            ->findOneBy([
                'relationType' => [
                    UserRelUser::USER_RELATION_TYPE_FRIEND,
                    UserRelUser::USER_RELATION_TYPE_GOODFRIEND,
                ],
                'friend' => $otherUserId,
                'user' => $currentUserId,
            ])
        ;

        if (null !== $relation) {
            return true;
        }

        $friendsOfCurrentUser = $em->getRepository(UserRelUser::class)
            ->findBy([
                'relationType' => [
                    UserRelUser::USER_RELATION_TYPE_FRIEND,
                    UserRelUser::USER_RELATION_TYPE_GOODFRIEND,
                ],
                'user' => $currentUserId,
            ])
        ;

        foreach ($friendsOfCurrentUser as $friendRelation) {
            $friendId = $friendRelation->getFriend()->getId();
            $relationThroughFriend = $em->getRepository(UserRelUser::class)
                ->findOneBy([
                    'relationType' => [
                        UserRelUser::USER_RELATION_TYPE_FRIEND,
                        UserRelUser::USER_RELATION_TYPE_GOODFRIEND,
                    ],
                    'friend' => $otherUserId,
                    'user' => $friendId,
                ])
            ;

            if (null !== $relationThroughFriend) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks the chat status of a user based on their user ID. It verifies if the user's chat status
     * is active (indicated by a status of 1).
     */
    private function checkUserStatus(int $userId, UserRepository $userRepository): bool
    {
        $userStatus = $userRepository->getExtraUserDataByField($userId, 'user_chat_status');

        return !empty($userStatus) && isset($userStatus['user_chat_status']) && 1 === (int) $userStatus['user_chat_status'];
    }

    /**
     * Retrieves the most recent legal terms for a specified language. If no terms are found for the given language,
     * the function attempts to retrieve terms for the platform's default language. If terms are still not found,
     * it defaults to English ('en_US').
     */
    private function getLastConditionByLanguage(LanguageRepository $languageRepo, string $isoCode, LegalRepository $legalTermsRepo, SettingsManager $settingsManager): ?Legal
    {
        $language = $languageRepo->findByIsoCode($isoCode);
        $languageId = (int) $language->getId();
        $term = $legalTermsRepo->getLastConditionByLanguage($languageId);
        if (!$term) {
            $defaultLanguage = $settingsManager->getSetting('language.platform_language');
            $language = $languageRepo->findByIsoCode($defaultLanguage);
            $languageId = (int) $language->getId();
            $term = $legalTermsRepo->getLastConditionByLanguage((int) $languageId);
            if (!$term) {
                $language = $languageRepo->findByIsoCode('en_US');
                $languageId = (int) $language->getId();
                $term = $legalTermsRepo->getLastConditionByLanguage((int) $languageId);
            }
        }

        return $term;
    }

    private function resolveLegalLanguageId(
        LanguageRepository $languageRepo,
        string $isoCode,
        SettingsManager $settingsManager
    ): int {
        $candidates = [
            $isoCode,
            (string) $settingsManager->getSetting('language.platform_language'),
            'en_US',
        ];

        foreach ($candidates as $code) {
            if ('' === trim((string) $code)) {
                continue;
            }

            $language = $languageRepo->findByIsoCode($code);
            if ($language) {
                return (int) $language->getId();
            }
        }

        return 0;
    }

    private function resolveLatestTermsVersion(
        string $isoCode,
        LanguageRepository $languageRepo,
        LegalRepository $legalRepo,
        SettingsManager $settingsManager
    ): ?array {
        $candidates = [];

        $lang = $languageRepo->findByIsoCode($isoCode);
        if ($lang) {
            $candidates[] = (int) $lang->getId();
        }

        $platformIso = (string) $settingsManager->getSetting('language.platform_language');
        if ('' !== $platformIso) {
            $platformLang = $languageRepo->findByIsoCode($platformIso);
            if ($platformLang) {
                $candidates[] = (int) $platformLang->getId();
            }
        }

        $en = $languageRepo->findByIsoCode('en_US');
        if ($en) {
            $candidates[] = (int) $en->getId();
        }

        $candidates = array_values(array_unique(array_filter($candidates)));

        foreach ($candidates as $languageId) {
            $version = $legalRepo->findLatestVersionByLanguage($languageId);
            if ($version > 0) {
                return ['languageId' => $languageId, 'version' => $version];
            }
        }

        return null;
    }
}
