<?php

/**
 * Represent a Shibboleth user. Not to be missunderstand with a Chamilo user
 * since they don't have the same attributes.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod
 */
class ShibbolethUser
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

    public function is_empty()
    {
        return empty($this->unique_id);
    }

}