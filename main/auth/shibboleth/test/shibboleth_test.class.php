<?php

/**
 * Various Unit Tests. Note that those tests create users in the database but
 * don't delete them.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
class ShibbolethTest
{

    static function is_enabled()
    {
        return api_get_setting('server_type') == 'test';
    }

    /**
     * @return ShibbolethTestHelper 
     */
    static function helper()
    {
        return ShibbolethTestHelper::instance();
    }

    static function init()
    {
        if (!self::is_enabled())
        {
            die;
        }
    }

    static function test_new_teacher()
    {
        self::init();

        self::helper()->setup_new_teacher();
        $shib_user = Shibboleth::store()->get_user();
        self::assert(!User::store()->shibboleth_id_exists($shib_user->unique_id));

        Shibboleth::save($shib_user);

        $user = User::store()->get_by_shibboleth_id($shib_user->unique_id);
        self::assert($user->email == $shib_user->email);
        self::assert($user->firstname == $shib_user->firstname);
        self::assert($user->lastname == $shib_user->lastname);
        self::assert($user->persistent_id == $shib_user->persistent_id);
        self::assert($user->status == Shibboleth::TEACHER_STATUS);
        self::assert(!empty($user->password));
        self::assert(!empty($user->username));
    }

    static function test_new_student()
    {
        self::init();

        self::helper()->setup_new_student();

        $shib_user = Shibboleth::store()->get_user();
        self::assert(!User::store()->shibboleth_id_exists($shib_user->unique_id));

        Shibboleth::save($shib_user);

        $user = User::store()->get_by_shibboleth_id($shib_user->unique_id);
        self::assert($user->email == $shib_user->email);
        self::assert($user->firstname == $shib_user->firstname);
        self::assert($user->lastname == $shib_user->lastname);
        self::assert($user->persistent_id == $shib_user->persistent_id);
        self::assert($user->status == Shibboleth::STUDENT_STATUS);
        self::assert(!empty($user->password));
        self::assert(!empty($user->username));
    }

    static function test_new_staff()
    {
        self::init();

        self::helper()->setup_new_staff();

        $shib_user = Shibboleth::store()->get_user();
        self::assert(!User::store()->shibboleth_id_exists($shib_user->unique_id));

        Shibboleth::save($shib_user);

        $user = User::store()->get_by_shibboleth_id($shib_user->unique_id);
        self::assert($user->email == $shib_user->email);
        self::assert($user->firstname == $shib_user->firstname);
        self::assert($user->lastname == $shib_user->lastname);
        self::assert($user->persistent_id == $shib_user->persistent_id);
        self::assert($user->status == Shibboleth::STUDENT_STATUS);
        self::assert(!empty($user->password));
        self::assert(!empty($user->username));
    }

    static function test_new_infer_status_request()
    {
        self::init();

        self::helper()->setup_new_staff();
        $shib_user = Shibboleth::store()->get_user();
        Shibboleth::save($shib_user);
        self::assert($shib_user->status_request);

        self::helper()->setup_new_teacher();
        $shib_user = Shibboleth::store()->get_user();
        Shibboleth::save($shib_user);

        self::assert(!$shib_user->status_request);

        self::helper()->setup_new_student();
        $shib_user = Shibboleth::store()->get_user();
        Shibboleth::save($shib_user);

        self::assert(!$shib_user->status_request);
    }

    static function test_update_teacher()
    {
        self::init();

        $fields = Shibboleth::config()->update_fields;
        self::assert($fields['email']);
        self::assert($fields['persistent_id']);
        self::assert(!$fields['firstname']);
        self::assert(!$fields['lastname']);
        self::assert(!$fields['status']);

        self::helper()->setup_teacher();
        $shib_user = Shibboleth::store()->get_user();
        Shibboleth::save($shib_user);

        $new_shib_user = clone($shib_user);

        $new_shib_user->firstname = 'frs';
        $new_shib_user->lastname = 'ls';
        $new_shib_user->email = 'em';
        $new_shib_user->status = 10;
        $new_shib_user->persistent_id = 'per';

        Shibboleth::save($new_shib_user);
        $user = User::store()->get_by_shibboleth_id($shib_user->unique_id);

        self::assert($user->email == $new_shib_user->email);
        self::assert($user->shibb_persistent_id == $new_shib_user->persistent_id);

        self::assert($user->firstname == $shib_user->firstname);
        self::assert($user->lastname == $shib_user->lastname);
        self::assert($user->status == $shib_user->status);
        self::assert(!empty($user->password));
        self::assert(!empty($user->username));
    }

    static function test_new_student_multiple_givenname()
    {
        self::init();

        self::helper()->setup_new_student_multiple_givenname();

        $shib_user = Shibboleth::store()->get_user();
        self::assert(!User::store()->shibboleth_id_exists($shib_user->unique_id));

        Shibboleth::save($shib_user);

        $user = User::store()->get_by_shibboleth_id($shib_user->unique_id);

        self::assert($user->email == $shib_user->email);
        self::assert($user->firstname == 'John');
        self::assert($user->lastname == $shib_user->lastname);
        self::assert($user->persistent_id == $shib_user->persistent_id);
        self::assert($user->status == Shibboleth::STUDENT_STATUS);
        self::assert(!empty($user->password));
        self::assert(!empty($user->username));
    }

    static function test_new_no_affiliation_default()
    {
        self::init();

        self::helper()->setup_new_no_affiliation();
        $shib_user = Shibboleth::store()->get_user();
        self::assert($config = Shibboleth::config()->default_status == Shibboleth::STUDENT_STATUS);
        self::assert(!User::store()->shibboleth_id_exists($shib_user->unique_id));
        self::assert($shib_user->affiliation == '');

        Shibboleth::save($shib_user);

        $user = User::store()->get_by_shibboleth_id($shib_user->unique_id);

        self::assert($user->email == $shib_user->email);
        self::assert($user->firstname == 'John');
        self::assert($user->lastname == $shib_user->lastname);
        self::assert($user->persistent_id == $shib_user->persistent_id);
        self::assert($user->status == Shibboleth::STUDENT_STATUS);
        self::assert(!empty($user->password));
        self::assert(!empty($user->username));
    }

    static function assert($assertion, $message = '')
    {
        if (!$assertion)
        {
            $message = "Assert failed $message <br/>";
            echo $message;
            var_dump(debug_backtrace());
            die;
        }
        else
        {
            $message = "Assert successful $message <br/>";
            echo $message;
        }
    }

}