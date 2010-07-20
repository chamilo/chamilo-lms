<?php
require_once api_get_path(SYS_CODE_PATH).'permissions/permissions_functions.inc.php';
//require_once dirname(__FILE__).'/../main/permissions/permissions_functions.inc.php';

class TestPermissions extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('Permissions library - main/permissions/permissions_functions.inc.test.php');
    }
    /**
	* This function is called when we assign a role to a user or a group
	* @param string
	* @param string
	* @param int
	* @param int
	* @return string
	*/
	function testAssignRole(){
		$content='';
		$action='';
		$id=1;
		$role_id=1;
		$scope='course';
		$res = assign_role($content, $action, $id, $role_id, $scope);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	/**
	This function displays a checked or unchecked checkbox. The checkbox will be checked if the
	* user, group or role has the permission for the given tool, unchecked if the user, group or role
	* does not have the right
	* @param array
	* @param string
	* @param string
	* @param array
	* @return null
	*/
	function testDisplayCheckboxMatrix(){
		ob_start();
		$permission_array=array();
		$tool='';
		$permission=1;
		$inherited_permissions=array();
		$res = display_checkbox_matrix($permission_array, $tool, $permission, $inherited_permissions);
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}
	/**
	* This function displays a checked or unchecked image. The image will be checked if the
	* user, group or role has the permission for the given tool, unchecked if the user, group or role
	* does not have the right
	* @param $permission_array the array that contains all the permissions of the user, group, role
	* @param $tool the tool we want to check a permission for
	* @param $permission the permission we want to check for
	*/
	function testDisplayImageMatrix(){
		//ob_start();
		$permission_array=array();
		$tool=1;
		$permission=1;
		$inherited_permissions=array($tool => array());
		$course_admin=false;
		$editable=true;
		$res = display_image_matrix($permission_array, $tool, $permission,$inherited_permissions, $course_admin, $editable);
		$this->assertTrue(is_null($res));
		//ob_end_clean();
		//var_dump($res);
	}
	/**
	* Slightly modified:  Toon Keppens
	* This function displays a checked or unchecked image. The image will be checked if the
	* user, group or role has the permission for the given tool, unchecked if the user, group or role
	* does not have the right
	* @param int
	* @param string
	* @param string
	* @param array
	* @param bool
	*/
	function testDisplayImageMatrixForBlogs(){
		$permission_array=array();
		$user_id=1;
		$tool=1;
		$permission=1;
		$inherited_permissions=array();
		$course_admin=false;
		$editable=true;
		$res = display_image_matrix_for_blogs($permission_array, $user_id, $tool, $permission,$inherited_permissions, $course_admin, $editable);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	/**
	* This function displays a list off all the roles of the course (and those defined by the platform admin)
	* @param
	* @param
	*/
	function testDisplayRoleList(){
		$current_course_roles='';
		$current_platform_roles='';
		$res = display_role_list($current_course_roles, $current_platform_roles);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	/**
	* This function gets all the current roles of the user or group
	* @param string
	* @return array that contains the name of the roles the user has
	*/
	function testGetAllRoles(){
		$content='course';
		$res = get_all_roles($content);
		if (!is_null($res)){
			$this->assertTrue(is_array($res));
		}
		//var_dump($res);
	}
	/**
	* This function retrieves the existing permissions of a user, group or role.
	* @param $content are we retrieving the rights of a user, a group or a role (the database depends on it)
	* @param $id the id of the user, group or role
	*/
	function testGetPermissions(){
		$content='user';
		$id=1;
		$res = get_permissions($content, $id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	/**
	* This function gets all the current roles of the user or group
	* @param $content are we finding the roles for a user or a group (the database depends on it)
	* @param $id the id of the user or group
	* @return array that contains the name of the roles the user has
	*/
	function testGetRoles(){
		$content='user';
		$id=1;
		$scope='course';
		$res = get_roles($content, $id, $scope);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	/**
	* This function gets all the roles that are defined
	* @param $content are we finding the roles for a user or a group (the database depends on it)
	* @param $id the id of the user or group
	* @param string	Deprecated parameter allowing use of 'platform' scope - the corresponding tables don't exist anymore so the scope is always set to 'course'
	* @return array that contains the name of the roles the user has
	*/
	function testGetRolesPermissions(){
		$content='user';
		$id=1;
		$scope='course';
		$res = get_roles_permissions($content, $id, $scope);
		if (!is_null($res)){
			$this->assertTrue(is_array($res));
		}
		//var_dump($res);
	}
	/**
	* the array that contains the current permission a user, group or role has will now be changed depending on
	* the Dokeos Config Setting for the permissions (limited [add, edit, delete] or full [view, add, edit, delete, move, visibility]
	* @param $content are we retrieving the rights of a user, a group or a role (the database depends on it)
	* @param $id the id of the user, group or role
	* @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
	* @version 1.0
	* @todo currently there is a setting user_permissions and group_permissions. We should merge this in one config setting.
	*/
	function testLimitedOrFull(){
		$current_permissions=array();
		$res = limited_or_full($current_permissions);
		if (!is_null($res)){
			$this->assertTrue(is_array($res));
		}
		//var_dump($res);
	}

	function testMyPrintR(){
		ob_start();
		$array='';
		$res = my_print_r($array);
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}

	/**
	* This function merges permission arrays. Each permission array has the following structure
	* a permission array has a tool contanst as a key and an array as a value. This value array consists of all the permissions that are granted in that tool.
	*/
	function testPermissionArrayMerge(){
		$array1=array();
		$array2=array();
		$res = permission_array_merge($array1, $array2);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	/**
	* This function stores one permission in the correct table.
	* @param $content are we storing rights for a user, a group or a role (the database depends on it)
	* @param $action are we granting or revoking a permission?
	* @param $id the id of the user, group or role
	* @param $tool the tool
	* @param $permission the permission the user, group or role has been granted or revoked
	*/
	function testStoreOnePermission(){
		$res = store_one_permission('user', 'grant', 2, 'link','');
		if(!$res === NULL){
		$this->assertTrue(is_string($res));
		} else {
			$this->assertNull($res);
		}
		//var_dump($res);
	}
	/**
	* This function stores the permissions in the correct table.
	* Since Checkboxes are used we do not know which ones are unchecked.
	* That's why we first delete them all (for the given user/group/role
	* and afterwards we store the checked ones only.
	* @param $content are we storing rights for a user, a group or a role (the database depends on it)
	* @param $id the id of the user, group or role
	* @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
	* @version 1.0
	*/
	function testStorePermissions(){
		$content='user';
		$id=1;
		$res = store_permissions($content, $id);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
}
?>