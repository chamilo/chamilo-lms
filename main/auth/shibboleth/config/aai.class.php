<?php

/**
 * Shibboleth configuration for the AAI federation.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class aai
{

    /**
     *
     * @return ShibbolethConfig 
     */
    public static function config()
    {
        $result = new ShibbolethConfig();
        $result->unique_id = 'Shib-SwissEP-UniqueID';
        $result->firstname = 'Shib-InetOrgPerson-givenName';
        $result->lastname = 'Shib-Person-surname';
        $result->email = 'Shib-InetOrgPerson-mail';
        $result->language = 'Shib-InetOrgPerson-preferredLanguage';
        $result->gender = 'Shib-SwissEP-Gender';
        $result->address = 'Shib-OrgPerson-postalAddress';
        $result->staff_category = 'Shib-SwissEP-StaffCategory';
        $result->home_organization_type = 'Shib-SwissEP-HomeOrganizationType';
        $result->home_organization = 'Shib-SwissEP-HomeOrganization';
        $result->affiliation = 'Shib-EP-Affiliation';
        $result->persistent_id = 'persistent-id';

        $result->default_status = Shibboleth::STUDENT_STATUS;

        $result->affiliation_status = array(
            'faculty' => Shibboleth::TEACHER_STATUS,
            'member'  => Shibboleth::STUDENT_STATUS,
            'staff'   => Shibboleth::STUDENT_STATUS,
            'student' => Shibboleth::STUDENT_STATUS,
        );

        $result->update_fields = array(
            'firstname'     => false,
            'lastname'      => false,
            'email'         => true,
            'status'        => false,
            'persistent_id' => true,
        );
        /*
         * Persistent id should never change but it was introduced after unique id. 
         * So we update persistent id on login for those users who are still missing it.
         */

        $result->is_email_mandatory = true;


        $result->affiliation_status_request = array(
            'faculty' => false,
            'member'  => false,
            'staff'   => true,
            'student' => false,
        );
        $result->admnistrator_email = '';

        return $result;
    }

}