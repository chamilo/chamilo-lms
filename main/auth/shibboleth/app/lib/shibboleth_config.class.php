<?php

/**
 * Shibboleth configuration. All configuration for the Shibboleth authentication
 * plugin: field names mapping, etc.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod
 */
class ShibbolethConfig
{    
    public $unique_id = '';
    public $firstname = '';
    public $lastname = '';
    public $email = '';
    public $language = '';
    public $gender = '';
    public $address = '';
    public $staff_category = '';
    public $home_organization_type = '';
    public $home_organization = '';
    public $affiliation = '';
    public $persistent_id = '';
    
    public $default_status = Shibboleth::UNKNOWN_STATUS;
    
    /**
     * Mapping of affiliation => right
     * @var array
     */
    public $affiliation_status = array();
    
    /**
     * Mapping of affiliation => bool. Display the request status form.
     * @var array
     */
    public $affiliation_status_request = array();
    
    /**
     * List of fields to update when the user already exists field_name => boolean.
     * @var array
     */
    public $update_fields = array();
    
    /*
     * True if email is mandatory. False otherwise.
     */
    public $is_email_mandatory = true;
    
    /**
     * The email of the shibboleth administrator.
     * 
     * @var string
     */
    public $admnistrator_email = '';
    
    
    
}