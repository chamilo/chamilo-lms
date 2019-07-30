<?php
/* For licensing terms, see /license.txt */

/**
 * VideoChat class.
 *
 * This class provides methods for video chat management.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class VideoChat
{
    /**
     * Get the video chat info by its users.
     *
     * @param int $user1 User id
     * @param int $user2 Other user id
     *
     * @return array The video chat info. Otherwise return false
     */
    public static function getChatRoomByUsers($user1, $user2)
    {
        $user1 = (int) $user1;
        $user2 = (int) $user2;

        if (empty($user1) || empty($user2)) {
            return false;
        }

        return Database::select(
            '*',
            Database::get_main_table(TABLE_MAIN_CHAT_VIDEO),
            [
                'where' => [
                    '(from_user = ? AND to_user = ?)' => [$user1, $user2],
                    'OR (from_user = ? AND to_user = ?)' => [$user2, $user1],
                ],
            ],
            'first'
        );
    }

    /**
     * Create a video chat.
     *
     * @param int $fromUser The sender user
     * @param int $toUser   The receiver user
     *
     * @return int The created video chat id. Otherwise return false
     */
    public static function createRoom($fromUser, $toUser)
    {
        $fromUserInfo = api_get_user_info($fromUser);
        $toUserInfo = api_get_user_info($toUser);

        $chatName = vsprintf(
            get_lang('VideoChatBetweenUserXAndUserY'),
            [$fromUserInfo['firstname'], $toUserInfo['firstname']]
        );

        return Database::insert(
            Database::get_main_table(TABLE_MAIN_CHAT_VIDEO),
            [
                'from_user' => $fromUser,
                'to_user' => $toUser,
                'room_name' => $chatName,
                'datetime' => api_get_utc_datetime(),
            ]
        );
    }

    /**
     * Check if the video chat exists by its room name.
     *
     * @param string $name The video chat name
     *
     * @return bool
     */
    public static function nameExists($name)
    {
        $resultData = Database::select(
            'COUNT(1) AS count',
            Database::get_main_table(TABLE_MAIN_CHAT_VIDEO),
            [
                'where' => ['room_name = ?' => $name],
            ],
            'first'
        );

        if ($resultData !== false) {
            return $resultData['count'] > 0;
        }

        return false;
    }
}
