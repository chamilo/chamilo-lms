<?php

/**
* Class to manage .htaccess file of Apache
* @author Fred Delaunay <fred@nemako.net>
*/

class htaccess{
    var $authType; // authentification type
    var $authName; // authentification name
    
    var $admin_files; // array of files limited to the administrators
    var $modo_files; // array of files limited to the moderators
    
    var $file_Htpasswd;   // .htpasswd file (AuthUserFile)
    var $file_Htgroup; // .htgroup file (AuthGroupFile)
    var $file_Htaccess; // .htaccess file


    
    function htaccess(){
       $this->authType = "Basic";
       $this->authName = "PhpFreeChat Admin Zone";
       
       $this->admin_files = array("admin.php", "admin2.php");
       $this->modo_files = array("mod.php", "mod2.php");
              

       $this->file_Htpasswd = dirname(__FILE__)."/.htpasswd";
	   if(!file_exists($this->file_Htpasswd))
		  touch($this->file_Htpasswd);
       $this->file_Htgroup = dirname(__FILE__)."/.htgroup";
	   if(!file_exists($this->file_Htgroup)) {
		  touch($this->file_Htgroup);
		  $this->addGroup("admin");
		  $this->addGroup("modo");
	   }
       $this->file_Htaccess = dirname(__FILE__)."/.htaccess";
       
    }

    /***************************************/
    /*            SET AND GET              */
    /***************************************/

    /**
    * Set the AuthType
    * @param string $authtype - AuthType is Basic or Digest (crypted)
    */
    function setAuthType($authtype){
        $this->authType=$authtype;
    }
    /**
    * Get the AuthType
    * @return string $authtype - AuthType is Basic or Digest (crypted)
    */
    function getAuthType(){
        return $this->authType;
    }
    
    /**
    * Set the AuthName message
    * @param string $authname - AuthName message is the text display in the dialog box
  	*/
    function setAuthName($authname){
        $this->authName=$authname;
    }
    /**
    * Get the AuthName message
    * @return string $authname - AuthName message is the text display in the dialog box
  	*/
    function getAuthName(){
        return $this->authName;
    }
    
    /**
    * Set the .htaccess file
    * @param string $filename - absolute file-path
    */
    function set_file_Htaccess($filename){
        $this->file_Htaccess=$filename;
    }
    /**
    * Get the .htaccess file
    * @return string $filename - absolute file-path
    */
    function get_file_Htaccess(){
        return $this->file_Htaccess;
    }
    
    /**
    * Set the .htpasswd file (AuthUserFile)
    * @param string $filename - absolute file-path
    */
    function set_file_Htpasswd($filename){
        $this->file_Htpasswd=$filename;
    }
    /**
    * Get the .htpasswd file (AuthUserFile)
    * @return string $filename - absolute file-path
    */
    function get_file_Htpasswd(){
        return $this->file_Htpasswd;
    }
        
    /**
    * Set the .htgroup file (AuthGroupFile)
    * @param string $filename - absolute file-path
    */
    function set_file_Htgroup($filename){
        $this->file_Htgroup=$filename;
    }
    /**
    * Get the .htgroup file (AuthGroupFile)
    * @return string $filename - absolute file-path
    */
    function get_file_Htgroup(){
        return $this->file_Htgroup;
    }   


    /***************************************/
    /*            USER METHODS             */
    /***************************************/


    /**
    * Check if the user exists
    * @param string $username - Username
    * @return boolean $UserExist - Returns true it the user exists, false if not
    */
    function isUser($username){
        $UserExist = false;
        $file = fopen($this->file_Htpasswd,"r");

        while($line = fgets($file)){
            $lineArr=explode(":",$line);
            if($username==$lineArr[0]){
                fclose($file);
                return true; // the user exists
             }
        }
        fclose($file);
     
        return false; // the user does not exist
    }

    /**
    * Add a user to the password file
    * @param string $username - Username
    * @param string $password - Password for Username
    * @return boolean $created - Returns true if ok, false if the user already exists
    */
    function addUser($username,$password){
        
        if($this->isUser($username)==false){
            $file=fopen($this->file_Htpasswd,"a");
			if(strtolower(substr(getenv("OS"),0,7))!="windows"){
				$password=crypt($password);
			}
            $newLine=$username.":".$password."\n";
            fputs($file,$newLine);
            fclose($file);
            return true;
        }
        else{
            return false; // the user already exists
        }
    }


    /**
    * Delete a user in the password file
    * @param string $username - Username to delete
    * @return boolean $deleted - Returns true if user have been deleted otherwise false
    */
    function delUser($username){
        // Read names from file
        $file=fopen($this->file_Htpasswd,"r");
        $i=0;
        $deleted = false;
        while($line=fgets($file)){
            $lineArr=explode(":",trim($line));
            if($username!=$lineArr[0]){
                $newUserlist[$i][0]=$lineArr[0];
                $newUserlist[$i][1]=$lineArr[1];
                $i++;
            }else{
                $deleted=true;
            }
        }
        fclose($file);
        
        if($deleted==true){        
           if($i==0) { // There are no more users
              unlink($this->file_Htpasswd);
              touch($this->file_Htpasswd);
           }
           else{ // Writing names back to file (without the user to delete)
              $file=fopen($this->file_Htpasswd,"w");
              for($i=0;$i<count($newUserlist);$i++){
                 fputs($file,$newUserlist[$i][0].":".$newUserlist[$i][1]."\n");
              }
              fclose($file);
           }
           return true;
        }
        else{
            return false;
        }
    }
    
    /**
    * Return an array of all users
    * @return array $users - 0 if the are no user
    */
    function getUsers() {
	    $file=fopen($this->file_Htpasswd,"r");
	    for($i=0;$line=fgets($file);$i++) {
	        $lineArr=explode(":",$line);
	        if($lineArr[0]!="") {
		        $userlist[$i]=$lineArr[0];
	        }
	    }
	    fclose($file);
        if (!empty($userlist)==0)
          return 0;
        else
	      return $userlist;
    }

    /**
    * Return the number of users
    * @return integer - the number of users
    */
    function getNumberOfUsers() {
	    $users=$this->getUsers();
	    if($users==0)
	      return 0;
	    else
	      return count($users);
    }
    
    /**
    * Sets a password to the given username
    * @param string $username - The name of the User for changing password
    * @param string $password - New Password for the User
    * @return boolean $isSet - Returns true if password have been set
    */    
    function setPasswd($username,$new_password){
       if($this->isUser($username)==true){
          $this->delUser($username);
          $this->addUser($username,$new_password);
          return true;
       }
       else{
          return false;
       }
    }


    /***************************************/
    /*            GROUP METHODS             */
    /***************************************/

    /**
    * Check if the group exists
    * @param string $groupname - Groupname
    * @return boolean $GroupExist - Returns true it the group exists, false otherwise
    */
    function isGroup($groupname){
        $GroupExist = false;
        $file = fopen($this->file_Htgroup,"r");

        while($line = fgets($file)){
            $lineArr=explode(":",trim($line));
            if($groupname==trim($lineArr[0])){
                fclose($file);
                return true; // the group exists
             }
        }
        fclose($file);
     
        return false; // the group does not exist
    }

    /**
    * Add a group to the group file
    * @param string $groupname - Groupname
    * @return boolean $created - Returns false if the group already exists
    */
    function addGroup($groupname){
        
        if($this->isGroup($groupname)==false){
            $file=fopen($this->file_Htgroup,"a");
            $newLine=$groupname.": "."\n"; // Take care, it should not have a space before :
            fputs($file,$newLine);
            fclose($file);
            return true;
        }
        else{
            return false; // the group already exists
        }
    }


    /**
    * Delete a group in the group file
    * @param string $groupname - Groupname to delete
    * @return boolean $deleted - Returns true if group have been deleted otherwise false
    */
    function delGroup($groupname){
        // Read names from file
        $file=fopen($this->file_Htgroup,"r");
        $i=0;
        $newGrouplist=0;
        while($line=fgets($file)){
            $lineArr=explode(":",trim($line));
            if($groupname!=trim($lineArr[0])){
                $newGrouplist[$i]=trim($line);
                $i++;
            }else{
                $deleted=true;
            }
        }
        fclose($file);

        if($deleted==true){
           if($i==0) { // There are no more users
              unlink($this->file_Htpasswd);
              touch($this->file_Htpasswd);
           }
           else{ // Writing names back to file (without the user to delete)
              $file=fopen($this->file_Htgroup,"w");
              for($i=0;$i<count($newGrouplist);$i++){
                 fputs($file,$newGrouplist[$i]."\n");
              }
              fclose($file);
           }
           return true;
        }
        else{
            return false;
        }
    }
    
    /**
    * Return an array of all groups
    * @return array $groups
    */
    function getGroups() {
	    $file=fopen($this->file_Htgroup,"r");
	    for($i=0;$line=fgets($file);$i++) {
	        $lineArr=explode(":",trim($line));
	        if($lineArr[0]!="") {
		        $grouplist[$i]=trim($lineArr[0]);
	        }
	    }
	    fclose($file);
        if (!empty($grouplist)==0)
          return 0;
        else
	      return $grouplist;
    }
    
    /***************************************/
    /*       USER AND GROUP METHODs        */
    /***************************************/
    
    /**
    * Check if the user is in the group
    * @param string $username - Username
    * @param string $groupname - Groupname
    * @return boolean $exist - Returns true it the user is in the group
    */
    function isUserInGroup($username,$groupname){
        $file = fopen($this->file_Htgroup,"r");

        while($line = fgets($file)){
            $lineArr=explode(":",trim($line));
            if($groupname==trim($lineArr[0])){
                
                $lineArrUser=explode(" ",trim($lineArr[1]));
                    for($i=0;$i<count($lineArrUser);$i++){
                        if(trim($lineArrUser[$i])==$username)
                          fclose($file);
                          return true; // the user is in the group
                    }
                
            }
        }
        fclose($file);
     
        return false; // the user in not in the group
    }

    /**
    * Add a user to the group file
    * @param string $username - Username
    * @param string $groupname - Groupname
    * @return boolean $created - Returns false if the user is already in the group
    */
    function addUserInGroup($username,$groupname){
        
        if($this->isGroup($groupname)==false){
			$this->addGroup($groupname);
		}
		
		if($this->isUserInGroup($username,$groupname)==false){
			// Read names from file
			$file = fopen($this->file_Htgroup,"r");
			$i=0;
			while($line = fgets($file)){
				$lineArr=explode(":",trim($line));
				if($groupname==trim($lineArr[0])){
					$newlist[$i]=trim($line)." ".$username;
				}
				else{
					$newlist[$i]=trim($line);
				}
				$i++;
			}
			fclose($file);

	
			// Writing names back to file (without the user to delete)
			$file=fopen($this->file_Htgroup,"w");
			for($i=0;$i<count($newlist);$i++){
				fputs($file,$newlist[$i]."\n");
			}
			fclose($file);
			return true;
        }
        else{
            return false; // the user is already in the group
        }
    }

    /**
    * Delete a user from the group file
    * @param string $username - Username
    * @param string $groupname - Groupname
    * @return boolean $created - Returns false if the user was not in the group
    */
    function delUserFromGroup($username,$groupname){
        
		if($this->isUserInGroup($username,$groupname)==true){
	
			// Read names from file
			$file = fopen($this->file_Htgroup,"r");
			$i=0;
			while($line = fgets($file)){
				$lineArr=explode(":",trim($line));
				if($groupname==trim($lineArr[0])){
					
					$lineArrUser=explode(" ",trim($lineArr[1]));
					$newlist[$i]=$groupname." : ";
					
						for($j=0;$j<count($lineArrUser);$j++){
							if(trim($lineArrUser[$j])!=$username)
							  $newlist[$i].=$lineArrUser[$j]." ";
						}
					
				}
				else{
					$newlist[$i]=trim($line);
					
				}
				$i++;
			}
			fclose($file);

	
			// Writing names back to file (without the user to delete)
			$file=fopen($this->file_Htgroup,"w");
			for($i=0;$i<count($newlist);$i++){
				fputs($file,$newlist[$i]."\n");
			}
			fclose($file);
			return true;

        }
        else{
            return false; // the user is not in the group
        }
    }

    /**
    * Return an array of groups from which the user belongs to
    *  -- Normaly, user belongs to only one group --
    * @return array $groups or 0 if the user is in none of the groups
    */
    function getGroupOfUser($username) {
	    $file=fopen($this->file_Htgroup,"r");
	    $i=0;
	    //$grouplist=0;
	    while($line = fgets($file)){
	       $lineArr=explode(":",trim($line));
		   $lineArrUser=explode(" ",trim($lineArr[1]));

		   for($j=0;$j<count($lineArrUser);$j++){
				if(trim($lineArrUser[$j])==$username){
				   $grouplist[$i]=trim($lineArr[0]);
				   $i++;
				   }
	       }
	    }
	    fclose($file);
        if(empty($grouplist))
	      return 0;
	    else
	      return $grouplist;
    }

    /***************************************/
    /*      WRITE the .htaccess file       */
    /***************************************/



    /**
    * Writes the .htaccess file
  	*/
    function printHtaccess(){
       $file=fopen($this->file_Htaccess,"w+");
       fputs($file,"AuthName        \"".$this->authName."\"\n");
       fputs($file,"AuthType        ".$this->authType."\n");
       fputs($file,"AuthUserFile    \"".$this->file_Htpasswd."\"\n");
       fputs($file,"AuthGroupFile    \"".$this->file_Htgroup."\"\n\n");

       fputs($file,"\nrequire valid-user\n");
       
       for($i=0;$i<count($this->admin_files);$i++){
          fputs($file,"\n<Files ".$this->admin_files[$i].">\n");
          fputs($file,"  require group admin\n");
          fputs($file,"</Files>\n");
       }

       for($i=0;$i<count($this->modo_files);$i++){
          fputs($file,"\n<Files ".$this->modo_files[$i].">\n");
          fputs($file,"  require group modo\n");
          fputs($file,"</Files>\n");
       }

       fclose($file);
    }

    /**
    * Deletes the protection of the given directory
    */
    function delHtaccess(){
        unlink($this->file_Htaccess);
    }
    
}
?>