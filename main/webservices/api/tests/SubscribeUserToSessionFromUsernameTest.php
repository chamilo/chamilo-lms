<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/V2TestCase.php';

require_once __DIR__.'/../../../../vendor/autoload.php';


/**
 * Class SubscribeUserToSessionFromUsernameTest
 *
 * SUBSCRIBE_USER_TO_SESSION_FROM_USERNAME webservice unit tests
 */
class SubscribeUserToSessionFromUsernameTest extends V2TestCase
{
    public function action()
    {
        return 'subscribe_user_to_session_from_username';
    }

    /**
     * subscribes a test user to a test session that already has another user subscribed
     * asserts that the user was subscribed to the session
     * asserts that the other user was not unsubscribed from the session
     */
    public function testSubscribeWithoutUnsubscribe()
    {
        // create a test session
        $sessionId = SessionManager::create_session(
            'Session to subscribe'.time(),
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            null,
            null
        );

        // create a test user
        $loginName = 'tester'.time();
        $userId = UserManager::create_user('Tester', 'Tester', 5, 'tester@local', $loginName, 'xXxxXxxXX');

        // create another user and subscribe it to the session
        $anotherUserId = UserManager::create_user('Tester 2', 'Tester 2', 5, 'tester2@local', $loginName.'t2', 'xXxxX');
        SessionManager::subscribeUsersToSession($sessionId, [$anotherUserId]);

        // call the webservice to subscribe the first user to the session
        $subscribed = $this->boolean(['sessionId' => $sessionId, 'loginname' => $loginName]);
        $this->assertTrue($subscribed);

        // assert we now have two users subscribed to the session
        $sessionRelUsers = Database::getManager()
            ->getRepository('ChamiloCoreBundle:SessionRelUser')
            ->findBy(['session' => $sessionId]);
        $this->assertSame(2, count($sessionRelUsers));

        // clean up
        UserManager::delete_users([$userId, $anotherUserId]);
        SessionManager::delete($sessionId);
    }
}
