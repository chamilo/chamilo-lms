<?php
/**
 * This function prints class=active_step $current_step=$param
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function step_active($param)
{
	global $current_step;

	if ($param==$current_step)
	{
		echo 'class="current_step" ';
	}
}

/**
 * This function displays the Step X of Y -
 * @return	string	String that says 'Step X of Y' with the right values
 */
function display_step_sequence()
{
	global $current_step;
	global $total_steps;

	return get_lang('Step'.$current_step).' &ndash; ';
}
/**
 * This function checks if a php extension exists or not and returns an HTML
 * status string.
 *
 * @param 	string  Name of the PHP extension to be checked
 * @param 	string  Text to show when extension is available (defaults to 'OK')
 * @param	string	Text to show when extension is available (defaults to 'KO')
 * @param	boolean	Whether this extension is optional (in this case show unavailable text in orange rather than red)
 * @return	string	HTML string reporting the status of this extension. Language-aware.
 * @author 	Christophe Gesche
 * @author 	Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author	Yannick Warnier <yannick.warnier@dokeos.com>
 * @version Dokeos 1.8.1, May 2007
 */
function check_extension($extension_name,$return_success='Yes',$return_failure='No',$optional=false)
{
	if(extension_loaded($extension_name))
	{
		return '<strong><font color="green">'.$return_success.'</font></strong>';
	}
	else
	{
		if($optional===true)
		{
			return '<strong><font color="#ff9900">'.$return_failure.'</font></strong>';
		}
		else
		{
			return '<strong><font color="red">'.$return_failure.'</font></strong>';
		}
	}
}


/**
 * This function checks whether a php setting matches the recommended value
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version Dokeos 1.8, august 2006
 */
function check_php_setting($php_setting, $recommended_value, $return_success=false, $return_failure=false)
{
	$current_php_value = get_php_setting($php_setting);
	if ( $current_php_value== $recommended_value)
	{
		return '<strong><font color="green">'.$current_php_value.' '.$return_success.'</font></strong>';
	}
	else
	{
		return '<strong><font color="red">'.$current_php_value.' '.$return_failure.'</font></strong>';
	}
}
/**
 * Enter description here...
 *
 * @param string $val a php ini value
 * @return boolean: ON or OFF
 * @author Joomla <http://www.joomla.org>
 */
function get_php_setting($val) {
	$r =  (ini_get($val) == '1' ? 1 : 0);
	return $r ? 'ON' : 'OFF';
}

/**
 * This function checks if the given folder is writable
 */
function check_writable($folder)
{
	if (is_writable('../'.$folder))
	{
		return '<strong><font color="green">'.get_lang('Writable').'</font></strong>';
	}
	else
	{
		return '<strong><font color="red">'.get_lang('NotWritable').'</font></strong>';
	}
}

/**
 * this function returns a string "FALSE" or "TRUE" according to the variable in parameter
 *
 * @param integer  $var  the variable to convert
 * @return  string  the string "FALSE" or "TRUE"
 * @author Christophe Gesche
 */
function trueFalse($var)
{
	return $var?'true':'false';
}

/**
 * This function is similar to the core file() function, except that it
 * works with line endings in Windows (which is not the case of file())
 * @param	string	File path
 * @return	array	The lines of the file returned as an array
 */
function file_to_array($filename)
{
	$fp = fopen($filename, "rb");
	$buffer = fread($fp, filesize($filename));
	fclose($fp);
	$result = explode('<br />',nl2br($buffer));
	return $result;
}

/**
 * This function returns the value of a parameter from the configuration file
 *
 * WARNING - this function relies heavily on global variables $updateFromConfigFile
 * and $configFile, and also changes these globals. This can be rewritten.
 *
 * @param 	string  $param  the parameter of which the value is returned
 * @param	string	If we want to give the path rather than take it from POST
 * @return  string  the value of the parameter
 * @author Olivier Brouckaert
 */
function get_config_param($param,$updatePath='')
{
	global $configFile, $updateFromConfigFile;
	//look if we already have the queried param
	if(is_array($configFile) && isset($configFile[$param]))
	{
		return $configFile[$param];
	}
	if(empty($updatePath) && !empty($_POST['updatePath']))
	{
		$updatePath = $_POST['updatePath'];
	}
	$updatePath = realpath($updatePath).'/';
	$updateFromInstalledVersionFile = '';

	if(empty($updateFromConfigFile)) //if update from previous install was requested
	{
		//try to recover old config file from dokeos 1.8.x
		if(file_exists($updatePath.'main/inc/conf/configuration.php'))
		{
			$updateFromConfigFile='main/inc/conf/configuration.php';
		}
		elseif(file_exists($updatePath.'claroline/inc/conf/claro_main.conf.php'))
		{
			$updateFromConfigFile='claroline/inc/conf/claro_main.conf.php';
		}
		//give up recovering
		else
		{
			error_log('Could not find config file in '.$updatePath.' in get_config_param()',0);
			return null;
		}
	}	
	if(file_exists($updatePath.'main/inc/installedVersion.inc.php'))
	{
		$updateFromInstalledVersionFile = $updatePath.'main/inc/installedVersion.inc.php';
	}
	//the param was not found in global vars, so look into the old config file
	elseif(file_exists($updatePath.$updateFromConfigFile))
	{
		//make sure the installedVersion file is read first so it is overwritten
		//by the config file if the config file contains the version (from 1.8.4)
		$temp2 = array();
		if(file_exists($updatePath.$updateFromInstalledVersionFile))
		{
			$temp2 = file_to_array($updatePath.$updateFromInstalledVersionFile);
		}		
		$configFile=array();
		$temp=file_to_array($updatePath.$updateFromConfigFile);
		$temp = array_merge($temp,$temp2);
		$val='';

		//parse the config file (TODO clarify why it has to be so complicated)
		foreach($temp as $enreg)
		{
			if(strstr($enreg,'='))
			{
				$enreg=explode('=',$enreg);
				$enreg[0] = trim($enreg[0]);
				if($enreg[0][0] == '$')
				{
					list($enreg[1])=explode(' //',$enreg[1]);

					$enreg[0]=trim(str_replace('$','',$enreg[0]));
					$enreg[1]=str_replace('\"','"',ereg_replace('(^"|"$)','',substr(trim($enreg[1]),0,-1)));
					$enreg[1]=str_replace('\'','"',ereg_replace('(^\'|\'$)','',$enreg[1]));
					if(strtolower($enreg[1]) == 'true')
					{
						$enreg[1]=1;
					}
					if(strtolower($enreg[1]) == 'false')
					{
						$enreg[1]=0;
					}
					else
					{
						$implode_string=' ';

						if(!strstr($enreg[1],'." ".') && strstr($enreg[1],'.$'))
						{
							$enreg[1]=str_replace('.$','." ".$',$enreg[1]);
							$implode_string='';
						}

						$tmp=explode('." ".',$enreg[1]);

						foreach($tmp as $tmp_key=>$tmp_val)
						{
							if(eregi('^\$[a-z_][a-z0-9_]*$',$tmp_val))
							{
								$tmp[$tmp_key]=get_config_param(str_replace('$','',$tmp_val));
							}
						}

						$enreg[1]=implode($implode_string,$tmp);
					}

					$configFile[$enreg[0]]=$enreg[1];

					$a=explode("'",$enreg[0]);
					$key_tmp=$a[1];							
					if($key_tmp== $param)					
					{					
						$val=$enreg[1];					
					} 
				}
			}
		}

		return $val;
	} 
	else 
	{
		error_log('Config array could not be found in get_config_param()',0);
		return null;
	}
}

/**
 * Get the config param from the database. If not found, return null
 * @param	string	DB Host
 * @param	string	DB login
 * @param	string	DB pass
 * @param	string	DB name
 * @param	string 	Name of param we want
 * @return	mixed	The parameter value or null if not found
 */
function get_config_param_from_db($host,$login,$pass,$db_name,$param='')
{
	$mydb = mysql_connect($host,$login,$pass);

	// The Dokeos system has not been designed to use special SQL modes that were introduced since MySQL 5
	@mysql_query("set session sql_mode='';");

	$myconnect = mysql_select_db($db_name);
	$sql = "SELECT * FROM settings_current WHERE variable = '$param'";
	$res = mysql_query($sql);
	if($res===false){return null;}
	if(mysql_num_rows($res)>0)
	{
		$row = mysql_fetch_array($res);
		$value = $row['selected_value'];
		return $value;
	}
	return null;
}

/**
*	Return a list of language directories.
*	@todo function does not belong here, move to code library,
*	also see infocours.php which contains similar function
*/
function get_language_folder_list($dirname)
{
	if ($dirname[strlen($dirname)-1] != '/') $dirname .= '/';
	$handle = opendir($dirname);
	$language_list = array();

	while ($entries = readdir($handle))
	{
		if ($entries=='.' || $entries=='..' || $entries=='CVS'  || $entries == '.svn') continue;
		if (is_dir($dirname.$entries))
		{
			$language_list[] = $entries;
		}
	}

	closedir($handle);

	return $language_list;
}

/*
==============================================================================
		DISPLAY FUNCTIONS
==============================================================================
*/


/**
*	Displays a form (drop down menu) so the user can select
*	his/her preferred language.
*/
function display_language_selection_box()
{
	//get language list
	$dirname = '../lang/';
	$language_list = get_language_folder_list($dirname);
	sort($language_list);
	//Reduce the number of languages shown to only show those with higher than 90% translation in DLTT
	//This option can be easily removed later on. The aim is to test people response to less choice
	//$language_to_display = $language_list;
	$language_to_display = array('asturian','english','italian','french','slovenian','slovenian_unicode','spanish');

	//display
	echo "\t\t<select name=\"language_list\">\n";

	$default_language = 'english';
	foreach ($language_to_display as $key => $value)
	{
		if ($value == $default_language) $option_end = ' selected="selected">';
		else $option_end = '>';
		echo "\t\t\t<option value=\"$value\"$option_end";

		echo api_ucfirst($value);
		echo "</option>\n";
	}

	echo "\t\t</select>\n";
}

/**
 * This function displays a language dropdown box so that the installatioin
 * can be done in the language of the user
 */
function display_language_selection()
{ ?>
	<h1><?php get_lang('WelcomeToTheDokeosInstaller');?></h1>
	<h2><?php echo display_step_sequence(); ?><?php echo get_lang('InstallationLanguage');?></h2>
	<p><?php echo get_lang('PleaseSelectInstallationProcessLanguage');?>:</p>
	<form id="lang_form" method="post" action="<?php echo api_get_self(); ?>">
<?php display_language_selection_box(); ?>
	<button type="submit" name="step1" class="next" value="<?php get_lang('Next');?> &gt;"><?php echo get_lang('Next');?></button>
	<input type="hidden" name="is_executable" id="is_executable" value="-" />
	</form>
<?php }

/**
 * This function displays the requirements for installing Dokeos.
 *
 * @param string $installType
 * @param boolean $badUpdatePath
 * @param string The updatePath given (if given)
 * @param array $update_from_version_8 The different subversions from version 1.8
 * @param array	$update_from_version_6 The different subversions from version 1.6
 *
 * @author unknow
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function display_requirements($installType, $badUpdatePath, $updatePath='', $update_from_version_8=array(), $update_from_version_6=array())
{
	echo '<h2>'.display_step_sequence().get_lang('Requirements')."</h2>\n";

	echo '<strong>'.get_lang('ReadThoroughly').'</strong><br />';
	echo get_lang('MoreDetails').' <a href="../../documentation/installation_guide.html" target="_blank">'.get_lang('ReadTheInstallGuide').'</a>.<br />'."\n";

	//	SERVER REQUIREMENTS
	echo '<div class="RequirementHeading"><h1>'.get_lang('ServerRequirements').'</h1>';
	echo '<div class="RequirementText">'.get_lang('ServerRequirementsInfo').'</div>';
	echo '<div class="RequirementContent">';
	echo '<table class="requirements">
			<tr>
				<td class="requirements-item">'.get_lang('PHPVersion').'>= 5.0</td>
				<td class="requirements-value">';
					if (phpversion() < '5.0')
					{
						echo '<strong><font color="red">'.get_lang('PHPVersionError').'</font></strong>';
					}
					else
					{
						echo '<strong><font color="green">'.get_lang('PHPVersionOK'). ' '.phpversion().'</font></strong>';
					}
	echo '		</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/en/book.session.php" target="_blank">Session</a> '.get_lang('support').'</td>
				<td class="requirements-value">'.check_extension('session', get_lang('Yes'), get_lang('ExtensionSessionsNotAvailable')).'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/en/book.mysql.php" target="_blank">MySQL</a> '.get_lang('support').'</td>
				<td class="requirements-value">'.check_extension('mysql', get_lang('Yes'), get_lang('ExtensionMySQLNotAvailable')).'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/en/book.zlib.php" target="_blank">Zlib</a> '.get_lang('support').'</td>
				<td class="requirements-value">'.check_extension('zlib', get_lang('Yes'), get_lang('ExtensionZlibNotAvailable')).'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/en/book.pcre.php" target="_blank">Perl-compatible regular expressions</a> '.get_lang('support').'</td>
				<td class="requirements-value">'.check_extension('pcre', get_lang('Yes'), get_lang('ExtensionPCRENotAvailable')).'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/en/book.xml.php" target="_blank">XML</a> '.get_lang('support').'</td>
				<td class="requirements-value">'.check_extension('xml', get_lang('Yes'), get_lang('No')).'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/en/book.mbstring.php" target="_blank">Multibyte string</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
				<td class="requirements-value">'.check_extension('mbstring', get_lang('Yes'), get_lang('ExtensionMBStringNotAvailable'), true).'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/en/book.iconv.php" target="_blank">Iconv</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
				<td class="requirements-value">'.check_extension('iconv', get_lang('Yes'), get_lang('No'), true).'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/en/book.intl.php" target="_blank">Internationalization</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
				<td class="requirements-value">'.check_extension('intl', get_lang('Yes'), get_lang('No'), true).'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/en/book.image.php" target="_blank">GD</a> '.get_lang('support').'</td>
				<td class="requirements-value">'.check_extension('gd', get_lang('Yes'), get_lang('ExtensionGDNotAvailable')).'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/en/book.json.php" target="_blank">JSON</a> '.get_lang('support').'</td>
				<td class="requirements-value">'.check_extension('json', get_lang('Yes'), get_lang('No')).'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/en/book.ldap.php" target="_blank">LDAP</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
				<td class="requirements-value">'.check_extension('ldap', get_lang('Yes'), get_lang('ExtensionLDAPNotAvailable'), true).'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://xapian.org/" target="_blank">Xapian</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
				<td class="requirements-value">'.check_extension('xapian', get_lang('Yes'), get_lang('No'), true).'</td>
			</tr>
				
		  </table>';
	echo '	</div>';
	echo '</div>';

	// RECOMMENDED SETTINGS
	// Note: these are the settings for Joomla, does this also apply for Dokeos?
	// Note: also add upload_max_filesize here so that large uploads are possible
	echo '<div class="RequirementHeading"><h1>'.get_lang('RecommendedSettings').'</h1>';
	echo '<div class="RequirementText">'.get_lang('RecommendedSettingsInfo').'</div>';
	echo '<div class="RequirementContent">';
	echo '<table class="requirements">
			<tr>
				<th>'.get_lang('Setting').'</th>
				<th>'.get_lang('Recommended').'</th>
				<th>'.get_lang('Actual').'</th>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/features.safe-mode.php">Safe Mode</a></td>
				<td class="requirements-recommended">OFF</td>
				<td class="requirements-value">'.check_php_setting('safe_mode','OFF').'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/ref.errorfunc.php#ini.display-errors">Display Errors</a></td>
				<td class="requirements-recommended">OFF</td>
				<td class="requirements-value">'.check_php_setting('display_errors','OFF').'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/ini.core.php#ini.file-uploads">File Uploads</a></td>
				<td class="requirements-recommended">ON</td>
				<td class="requirements-value">'.check_php_setting('file_uploads','ON').'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/ref.info.php#ini.magic-quotes-gpc">Magic Quotes GPC</a></td>
				<td class="requirements-recommended">OFF</td>
				<td class="requirements-value">'.check_php_setting('magic_quotes_gpc','OFF').'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/ref.info.php#ini.magic-quotes-runtime">Magic Quotes Runtime</a></td>
				<td class="requirements-recommended">OFF</td>
				<td class="requirements-value">'.check_php_setting('magic_quotes_runtime','OFF').'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/security.globals.php">Register Globals</a></td>
				<td class="requirements-recommended">OFF</td>
				<td class="requirements-value">'.check_php_setting('register_globals','OFF').'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/ref.session.php#ini.session.auto-start">Session auto start</a></td>
				<td class="requirements-recommended">OFF</td>
				<td class="requirements-value">'.check_php_setting('session.auto_start','OFF').'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/ini.core.php#ini.short-open-tag">Short Open Tag</a></td>
				<td class="requirements-recommended">OFF</td>
				<td class="requirements-value">'.check_php_setting('short_open_tag','OFF').'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/ini.core.php#ini.upload-max-filesize">Maximum upload file size</a></td>
				<td class="requirements-recommended">10M-100M</td>
				<td class="requirements-value">'.ini_get('upload_max_filesize').'</td>
			</tr>
			<tr>
				<td class="requirements-item"><a href="http://php.net/manual/ini.core.php#ini.post-max-size">Maximum post size</a></td>
				<td class="requirements-recommended">10M-100M</td>
				<td class="requirements-value">'.ini_get('post_max_size').'</td>
			</tr>
		  </table>';
	echo '	</div>';
	echo '</div>';

	// DIRECTORY AND FILE PERMISSIONS
	echo '<div class="RequirementHeading"><h1>'.get_lang('DirectoryAndFilePermissions').'</h1>';
	echo '<div class="RequirementText">'.get_lang('DirectoryAndFilePermissionsInfo').'</div>';
	echo '<div class="RequirementContent">';
	echo '<table class="requirements">
			<tr>
				<td class="requirements-item">dokeos/main/inc/conf/</td>
				<td class="requirements-value">'.check_writable('inc/conf/').'</td>
			</tr>
			<tr>
				<td class="requirements-item">dokeos/main/upload/users/</td>
				<td class="requirements-value">'.check_writable('upload/users/').'</td>
			</tr>
			<tr>
				<td class="requirements-item">dokeos/main/default_course_document/images/</td>
				<td class="requirements-value">'.check_writable('default_course_document/images/').'</td>
			</tr>
			<tr>
				<td class="requirements-item">dokeos/archive/</td>
				<td class="requirements-value">'.check_writable('../archive/').'</td>
			</tr>
			<tr>
				<td class="requirements-item">dokeos/courses/</td>
				<td class="requirements-value">'.check_writable('../courses/').'</td>
			</tr>
			<tr>
				<td class="requirements-item">dokeos/home/</td>
				<td class="requirements-value">'.check_writable('../home/').'</td>
			</tr>'.
            //'<tr>
            //    <td class="requirements-item">dokeos/searchdb/</td>
            //    <td class="requirements-value">'.check_writable('../searchdb/').'</td>
            //</tr>'.
            //'<tr>
            //    <td class="requirements-item">'.session_save_path().'</td>
            //    <td class="requirements-value">'.(is_writable(session_save_path()) 
			//		? '<strong><font color="green">'.get_lang('Writable').'</font></strong>'
			//		: '<strong><font color="red">'.get_lang('NotWritable').'</font></strong>').'</td>
            //</tr>'.
            '';
	echo '    </table>';
	echo '	</div>';
	echo '</div>';

	if($installType == 'update' && (empty($updatePath) || $badUpdatePath))
	{
		if($badUpdatePath)
		{ ?>
			<div style="color:red; background-color:white; font-weight:bold; text-align:center;">
				<?php echo get_lang('Error');?>!<br />
				Dokeos <?php echo (isset($_POST['step2_update_6'])?implode('|',$update_from_version_6):implode('|',$update_from_version_8)).' '.get_lang('HasNotBeenFoundInThatDir'); ?>.
			</div>
		<?php }
		else
		{
			echo '<br />';
		}
		?>
			<table border="0" cellpadding="5" align="center">
			<tr>
			<td><?php echo get_lang('OldVersionRootPath');?>:</td>
			<td><input type="text" name="updatePath" size="50" value="<?php echo ($badUpdatePath && !empty($updatePath))?htmlentities($updatePath):$_SERVER['DOCUMENT_ROOT'].'/old_version/'; ?>" /></td>
			</tr>
			<tr>
			<td colspan="2" align="center">
				<button type="submit" class="back" name="step1" value="&lt; <?php echo get_lang('Back');?>" ><?php echo get_lang('Back');?></button>
				<input type="hidden" name="is_executable" id="is_executable" value="-" />	
				<button type="submit" class="next" name="<?php echo (isset($_POST['step2_update_6'])?'step2_update_6':'step2_update_8');?>" value="<?php echo get_lang('Next');?> &gt;" ><?php echo get_lang('Next');?></button>
			</td>
			</tr>
			</table>
		<?php
	}
	else
	{
		$error=false;

		$perm = api_get_setting('permissions_for_new_directories');
		$perm = octdec(!empty($perm)?$perm:'0770');
		$perm_file = api_get_setting('permissions_for_new_files');
		$perm_file = octdec(!empty($perm_file)?$perm_file:'0660');

		//First, attempt to set writing permissions if we don't have them yet
		//0xxx is an octal number, this is the required format
		$notwritable = array();
        $curdir = getcwd();
		if(!is_writable('../inc/conf'))
		{
			$notwritable[] = realpath($curdir.'/../inc/conf');
			@chmod('../inc/conf',$perm);
		}

		if(!is_writable('../upload/users'))
		{
			$notwritable[] = realpath($curdir.'/../upload/users');
			@chmod('../upload/users', $perm);
		}

        if(!is_writable('../default_course_document/images/'))
        {
            $notwritable[] = realpath($curdir.'/../default_course_document/images/');
            @chmod('../default_course_document/images/', $perm);
        }

		if(!is_writable('../../archive'))
		{
			$notwritable[] = realpath($curdir.'/../../archive');
			@chmod('../../archive',$perm);
		}

		if(!is_writable('../../courses'))
		{
			$notwritable[] = realpath($curdir.'/../../courses');
			@chmod('../../courses',$perm);
		}

		if(!is_writable('../../home'))
		{
			$notwritable[] = realpath($curdir.'/../../home');
			@chmod('../../home',$perm);
		}

		if(file_exists('../inc/conf/configuration.php') && !is_writable('../inc/conf/configuration.php'))
		{
			$notwritable[]= realpath($curdir.'/../inc/conf/configuration.php');
			@chmod('../inc/conf/configuration.php',$perm_file);
		}

		//Second, if this fails, report an error
		//--> the user will have to adjust the permissions manually
		if(count($notwritable)>0)
		{
			$error=true;
			echo '<div style="color:red; background-color:white; font-weight:bold; text-align:center;">';
			echo get_lang('Warning').':<br />';
			printf(get_lang('NoWritePermissionPleaseReadInstallGuide'),'</font><a href="../../documentation/installation_guide.html" target="blank">','</a> <font color="red">');
			echo '<ul>';
			foreach ($notwritable as $value)
			{
				echo '<li>'.$value.'</li>';
			}
			echo '</ul>';
			echo '</div>';			
		}
		// check wether a Dokeos configuration file already exists.
		elseif(file_exists('../inc/conf/configuration.php'))
		{
				echo '<div style="color:red; background-color:white; font-weight:bold; text-align:center;">';
				echo get_lang('WarningExistingDokeosInstallationDetected');
				echo '</div>';
		}
		//and now display the choice buttons (go back or install)
		?>
		<p align="center">
		<button type="submit" name="step1" class="back" onclick="window.location='index.php';return false;" value="&lt; <?php echo get_lang('Previous'); ?>" ><?php echo get_lang('Previous'); ?></button>
		<button type="submit" name="step2_install" class="add" value="<?php echo get_lang("NewInstallation"); ?>" <?php if($error) if($error)echo 'disabled="disabled"'; ?> ><?php echo get_lang('NewInstallation'); ?></button>
		<input type="hidden" name="is_executable" id="is_executable" value="-" />
		<?php
		//real code
		echo '<button type="submit" class="save" name="step2_update_8" value="Upgrade from Dokeos 1.8.x"';
		if($error) echo ' disabled="disabled"';
		//temporary code for alpha version, disabling upgrade
		//echo '<input type="submit" name="step2_update" value="Upgrading is not possible in this beta version"';
		//echo ' disabled="disabled"';
		//end temp code
		echo ' >'.get_lang('UpgradeFromDokeos18x').'</button>';
		echo '<button type="submit" class="save" name="step2_update_6" value="Upgrade from Dokeos 1.6.x"';
		if($error) echo ' disabled="disabled"';
		echo ' >'.get_lang('UpgradeFromDokeos16x').'</button>';
		echo '</p>';
	}
}

/**
* Displays the license (GNU GPL) as step 2, with
* - an "I accept" button named step3 to proceed to step 3;
* - a "Back" button named step1 to go back to the first step.
*/
function display_license_agreement()
{
	echo '<h2>'.display_step_sequence().get_lang('Licence').'</h2>';
	echo '<p>'.get_lang('DokeosLicenseInfo').'</p>';
	echo '<p><a href="../../documentation/license.html" target="_blank">'.get_lang('PrintVers').'</a></p>';
	?>
	<table><tr><td>
		<p><textarea cols="75" rows="15" ><?php htmlentities(include('../../documentation/license.txt')); ?></textarea></p>
		</td>
		</tr>
		<tr>
		<td>
		<p><?php echo get_lang('DokeosArtLicense');?></p>
		</td>
		</tr>
		<td>
		<table width="100%">
			<tr>
				<td></td>
				<td align="center">
					<button type="submit" class="back" name="step1" value="&lt; <?php echo get_lang('Previous'); ?>" ><?php echo get_lang('Previous'); ?></button>
					<input type="hidden" name="is_executable" id="is_executable" value="-" />
					<button type="submit" class="next" name="step3" value="<?php echo get_lang('IAccept'); ?> &gt;" ><?php echo get_lang('IAccept'); ?></button>
				</td>
			</tr>
		</table>
	</td></tr></table>
	<?php
}

/**
* Displays a parameter in a table row.
* Used by the display_database_settings_form function.
* @param	string	Type of install
* @param	string	Name of parameter
* @param	string	Field name (in the HTML form)
* @param	string	Field value
* @param	string	Extra notice (to show on the right side)
* @param	boolean	Whether to display in update mode
* @param	string	Additional attribute for the <tr> element
* @return	void	Direct output	
*/
function display_database_parameter($install_type, $parameter_name, $form_field_name, $parameter_value, $extra_notice, $display_when_update = true, $tr_attribute='')
{
	echo "<tr ".$tr_attribute.">\n";
	echo "<td>$parameter_name&nbsp;&nbsp;</td>\n";
	if ($install_type == INSTALL_TYPE_UPDATE && $display_when_update)
	{
		echo '<td><input type="hidden" name="'.$form_field_name.'" id="'.$form_field_name.'" value="'.htmlentities($parameter_value).'" />'.$parameter_value."</td>\n";
	}
	else
	{
		if ($form_field_name=='dbPassForm') { 
			$inputtype = 'password';
		} else {
			$inputtype = 'text';
		}
		
		//Slightly limit the length of the database prefix to avoid
		//having to cut down the databases names later on
		if ($form_field_name=='dbPrefixForm') { 
			$maxlength = '15';
		} else {
			$maxlength = MAX_FORM_FIELD_LENGTH;
		}

		echo '<td><input type="'.$inputtype.'" size="'.DATABASE_FORM_FIELD_DISPLAY_LENGTH.'" maxlength="'.$maxlength.'" name="'.$form_field_name.'" id="'.$form_field_name.'" value="'.htmlentities($parameter_value).'" />'."</td>\n";
		echo "<td>$extra_notice</td>\n";
	}
	echo "</tr>\n";
}

/**
 * Displays step 3 - a form where the user can enter the installation settings
 * regarding the databases - login and password, names, prefixes, single
 * or multiple databases, tracking or not...
 */
function display_database_settings_form($installType, $dbHostForm, $dbUsernameForm, $dbPassForm, $dbPrefixForm, $enableTrackingForm, $singleDbForm, $dbNameForm, $dbStatsForm, $dbScormForm, $dbUserForm)
{
	if($installType == 'update')
	{
		global $_configuration, $update_from_version_6;
		if(in_array($_POST['old_version'],$update_from_version_6))
		{
	        $dbHostForm=get_config_param('dbHost');
            $dbUsernameForm=get_config_param('dbLogin');
            $dbPassForm=get_config_param('dbPass');
            $dbPrefixForm=get_config_param('dbNamePrefix');
            $enableTrackingForm=get_config_param('is_trackingEnabled');
            $singleDbForm=get_config_param('singleDbEnabled');
            $dbNameForm=get_config_param('mainDbName');
            $dbStatsForm=get_config_param('statsDbName');
            $dbScormForm=get_config_param('scormDbName');
            $dbUserForm=get_config_param('user_personal_database');
            $dbScormExists=true;
		}
		else
		{
			$dbHostForm=$_configuration['db_host'];
			$dbUsernameForm=$_configuration['db_user'];
			$dbPassForm=$_configuration['db_password'];
			$dbPrefixForm=$_configuration['db_prefix'];
			$enableTrackingForm=$_configuration['tracking_enabled'];
			$singleDbForm=$_configuration['single_database'];
			$dbNameForm=$_configuration['main_database'];
			$dbStatsForm=$_configuration['statistics_database'];
			$dbScormForm=$_configuration['scorm_database'];
			$dbUserForm=$_configuration['user_personal_database'];
	
			$dbScormExists=true;
		}

		if(empty($dbScormForm))
		{
			if($singleDbForm)
			{
				$dbScormForm=$dbNameForm;
			}
			else
			{
				$dbScormForm=$dbPrefixForm.'scorm';

				$dbScormExists=false;
			}
		}
		if(empty($dbUserForm))
		{
			if($singleDbForm)
			{
				$dbUserForm=$dbNameForm;
			}
			else
			{
				$dbUserForm=$dbPrefixForm.'dokeos_user';
			}
		}
		echo "<h2>" . display_step_sequence() .get_lang("DBSetting") . "</h2>";
		echo get_lang("DBSettingUpgradeIntro");
	}else{
		if(empty($dbPrefixForm)) //make sure there is a default value for db prefix
		{
			$dbPrefixForm = 'dokeos_';
		}
		echo "<h2>" . display_step_sequence() .get_lang("DBSetting") . "</h2>";
		echo get_lang("DBSettingIntro");
	}
	
	?>
	<br /><br />
	</td>
	</tr>
	<tr>
	<td>
	<table width="100%">
	<tr>
	  <td width="40%"><?php echo get_lang('DBHost'); ?> </td>

	  <?php if($installType == 'update'): ?>
	  <td width="30%"><input type="hidden" name="dbHostForm" value="<?php echo htmlentities($dbHostForm); ?>" /><?php echo $dbHostForm; ?></td>
	  <td width="30%">&nbsp;</td>
	  <?php else: ?>
	  <td width="30%"><input type="text" size="25" maxlength="50" name="dbHostForm" value="<?php echo htmlentities($dbHostForm); ?>" /></td>
	  <td width="30%"><?php echo get_lang('EG').' localhost'; ?></td>
	  <?php endif; ?>

	</tr>
	<?php
	//database user username
	$example_login = get_lang('EG').' root';
	display_database_parameter($installType, get_lang('DBLogin'), 'dbUsernameForm', $dbUsernameForm, $example_login);
	//database user password
	$example_password = get_lang('EG').' '.api_generate_password();
	display_database_parameter($installType, get_lang('DBPassword'), 'dbPassForm', $dbPassForm, $example_password);
	//database prefix
	display_database_parameter($installType, get_lang('DbPrefixForm'), 'dbPrefixForm', $dbPrefixForm, get_lang('DbPrefixCom'));
	//fields for the four standard Dokeos databases
	echo '<tr><td colspan="3"><a href="" onclick="javascript: show_hide_option();return false;" id="optionalparameters"><img style="vertical-align:middle;" src="../img/div_hide.gif" alt="show-hide" /> '.get_lang('OptionalParameters','').'</a></td></tr>';
	display_database_parameter($installType, get_lang('MainDB'), 'dbNameForm', $dbNameForm, '&nbsp;',null,'id="optional_param1" style="display:none;"');
	display_database_parameter($installType, get_lang('StatDB'), 'dbStatsForm', $dbStatsForm, '&nbsp;',null,'id="optional_param2" style="display:none;"');
	if($installType == 'update' && in_array($_POST['old_version'],$update_from_version_6))
	{
		display_database_parameter($installType, get_lang('ScormDB'), 'dbScormForm', $dbScormForm, '&nbsp;',null,'id="optional_param3" style="display:none;"');
	}
	display_database_parameter($installType, get_lang('UserDB'), 'dbUserForm', $dbUserForm, '&nbsp;',null,'id="optional_param4" style="display:none;"');
	?>
	<tr id="optional_param5" style="display:none;">
	  <td><?php echo get_lang('EnableTracking'); ?> </td>

	  <?php if($installType == 'update'): ?>
	  <td><input type="hidden" name="enableTrackingForm" value="<?php echo $enableTrackingForm; ?>" /><?php echo $enableTrackingForm? get_lang('Yes') : get_lang('No'); ?></td>
	  <?php else: ?>
	  <td>
		<input class="checkbox" type="radio" name="enableTrackingForm" value="1" id="enableTracking1" <?php echo $enableTrackingForm?'checked="checked" ':''; ?>/> <label for="enableTracking1"><?php echo get_lang('Yes'); ?></label>
		<input class="checkbox" type="radio" name="enableTrackingForm" value="0" id="enableTracking0" <?php echo $enableTrackingForm?'':'checked="checked" '; ?>/> <label for="enableTracking0"><?php echo get_lang('No'); ?></label>
	  </td>
	  <?php endif; ?>

	  <td>&nbsp;</td>
	</tr>
	<tr id="optional_param6" style="display:none;">
	  <td><?php echo get_lang('SingleDb'); ?> </td>

	  <?php if($installType == 'update'): ?>
	  <td><input type="hidden" name="singleDbForm" value="<?php echo $singleDbForm; ?>" /><?php echo $singleDbForm? get_lang('One') : get_lang('Several'); ?></td>
	  <?php else: ?>
	  <td>
		<input class="checkbox" type="radio" name="singleDbForm" value="1" id="singleDb1" <?php echo $singleDbForm?'checked="checked" ':''; ?> onclick="show_hide_tracking_and_user_db(this.id);" /> <label for="singleDb1"><?php echo get_lang('One'); ?></label>
		<input class="checkbox" type="radio" name="singleDbForm" value="0" id="singleDb0" <?php echo $singleDbForm?'':'checked="checked" '; ?> onclick="show_hide_tracking_and_user_db(this.id);" /> <label for="singleDb0"><?php echo get_lang('Several'); ?></label>
	  </td>
	  <?php endif; ?>

	  <td>&nbsp;</td>
	</tr>
	</div>
	<tr>
		<td><button type="submit" class="login" name="step3" value="<?php echo get_lang('CheckDatabaseConnection'); ?>" ><?php echo get_lang('CheckDatabaseConnection'); ?></button></td>
		<?php $dbConnect = test_db_connect ($dbHostForm, $dbUsernameForm, $dbPassForm, $singleDbForm, $dbPrefixForm);
		if($dbConnect==1): ?>
		<td colspan="2">
			<div class="confirmation-message">
				<!--<div  style="float:left; margin-right:10px;">
				<img src="../img/message_confirmation.png" alt="Confirmation" />
				</div>-->
				<!--<div style="float:left;">-->
				MySQL host info: <?php echo mysql_get_host_info(); ?><br />
				MySQL server version: <?php echo mysql_get_server_info(); ?><br />
				MySQL protocol version: <?php echo mysql_get_proto_info(); ?>
				<!--</div>-->
				<div style="clear:both;"></div>
			</div>
		</td>
		<?php else: ?>
		<td colspan="2">
			<div style="float:left;" class="error-message">
				<!--<div  style="float:left; margin-right:10px;">
				<img src="../img/message_error.png" alt="Error" />
				</div>-->
				<div style="float:left;">
				<strong>MySQL error: <?php echo mysql_errno(); ?></strong><br />
				<?php echo mysql_error().'<br/>'; ?>
				<strong><?php echo get_lang('Details').': '. get_lang('FailedConectionDatabase'); ?></strong><br />
				</div>
			</div>
		</td>
		<?php endif; ?>
	</tr>
	<tr>
	  <td><button type="submit" name="step2" class="back" value="&lt; <?php echo get_lang('Previous'); ?>" ><?php echo get_lang('Previous'); ?></button></td>
	  <td>&nbsp;</td>
	  <td align="right"><input type="hidden" name="is_executable" id="is_executable" value="-" /><button type="submit" class="next" name="step4" value="<?php echo get_lang('Next'); ?> &gt;" /><?php echo get_lang('Next'); ?></button></td>
	</tr>
	</table>
	<?php
}

/**
* Displays a parameter in a table row.
* Used by the display_configuration_settings_form function.
*/
function display_configuration_parameter($install_type, $parameter_name, $form_field_name, $parameter_value, $display_when_update = 'true')
{
	global $charset;

	echo "<tr>\n";
	echo "<td>$parameter_name&nbsp;&nbsp;</td>\n";
	if ($install_type == INSTALL_TYPE_UPDATE && $display_when_update)
	{
		echo '<td><input type="hidden" name="'.$form_field_name.'" value="'.api_htmlentities($parameter_value, ENT_QUOTES, $charset).'" />'.$parameter_value."</td>\n";
	}
	else
	{
		echo '<td><input type="text" size="'.FORM_FIELD_DISPLAY_LENGTH.'" maxlength="'.MAX_FORM_FIELD_LENGTH.'" name="'.$form_field_name.'" value="'.api_htmlentities($parameter_value, ENT_QUOTES, $charset).'" />'."</td>\n";
	}
	echo "</tr>\n";
}

/**
 * Displays step 4 of the installation - configuration settings about Dokeos itself.
 */
function display_configuration_settings_form($installType, $urlForm, $languageForm, $emailForm, $adminFirstName, $adminLastName, $adminPhoneForm, $campusForm, $institutionForm, $institutionUrlForm, $encryptPassForm, $allowSelfReg, $allowSelfRegProf, $loginForm, $passForm)
{
	global $charset;

	if($installType != 'update' && empty($languageForm))
	{
		$languageForm = $_SESSION['install_language'];
	}

	echo "<h2>" . display_step_sequence() . get_lang("CfgSetting") . "</h2>";
	echo '<p>'.get_lang('ConfigSettingsInfo').' <b>main/inc/conf/configuration.php</b></p>';

	echo "</td></tr>\n<tr><td>";
	echo "<table width=\"100%\">";

	//First parameter: language
	echo "<tr>\n";
	echo '<td>'.get_lang('MainLang')."&nbsp;&nbsp;</td>\n";
	if($installType == 'update')
	{		
		echo '<td><input type="hidden" name="languageForm" value="'.api_htmlentities($languageForm, ENT_QUOTES, $charset).'" />'.$languageForm."</td>\n";
	}
	else // new installation
	{
		
	echo '<td>';
	
	$array_lang = array('asturian','english','italian','french','slovenian','spanish');

	////Only display Language have 90% +
	echo "\t\t<select name=\"languageForm\">\n";	
				
	foreach ($array_lang as $key => $value)	{
		echo '<option value="'.$value.'"';
		if($value == $languageForm) echo ' selected="selected"';
		echo ">$value</option>\n";
	}

	echo "\t\t</select>\n";
	
	//Display all language
	/*echo "<select name=\"languageForm\">\n";
		$dirname='../lang/';
		
		if ($dir=@opendir($dirname)) {
			$lang_files = array();
				while (($file = readdir($dir)) !== false) {
					if($file != '.' && $file != '..' && $file != 'CVS' && $file != '.svn' && is_dir($dirname.$file)){
						array_push($lang_files, $file);
					}
				}
			closedir($dir);
		}
		sort($lang_files);
				
		
		foreach ($lang_files as $file) {
			echo '<option value="'.$file.'"';
				if($file == $languageForm) echo ' selected="selected"';
				echo ">$file</option>\n";
		}


		echo '</select>';*/
		echo "</td>\n";
	}
	echo "</tr>\n";

	//Second parameter: Dokeos URL
	echo "<tr>\n";
	echo '<td>'.get_lang('DokeosURL').' (<font color="red">'.get_lang('ThisFieldIsRequired')."</font>)&nbsp;&nbsp;</td>\n";
	
	if($installType == 'update') echo '<td>'.api_htmlentities($urlForm, ENT_QUOTES, $charset)."</td>\n";
	else echo '<td><input type="text" size="40" maxlength="100" name="urlForm" value="'.api_htmlentities($urlForm, ENT_QUOTES, $charset).'" />'."</td>\n";
	
	echo "</tr>\n";

	//Parameter 3: administrator's email
	display_configuration_parameter($installType, get_lang("AdminEmail"), "emailForm", $emailForm);

    //Parameter 4: administrator's first name
    display_configuration_parameter($installType, get_lang("AdminFirstName"), "adminFirstName", $adminFirstName);

	//Parameter 5: administrator's last name
	display_configuration_parameter($installType, get_lang("AdminLastName"), "adminLastName", $adminLastName);

	//Parameter 6: administrator's telephone
	display_configuration_parameter($installType, get_lang("AdminPhone"), "adminPhoneForm", $adminPhoneForm);

	//Parameter 7: administrator's login
	display_configuration_parameter($installType, get_lang("AdminLogin"), "loginForm", $loginForm, ($installType == 'update' ? true : false));

	//Parameter 8: administrator's password
	if($installType != 'update')
		display_configuration_parameter($installType, get_lang("AdminPass"), "passForm", $passForm, false);

	//Parameter 9: campus name
	display_configuration_parameter($installType, get_lang("CampusName"), "campusForm", $campusForm);

	//Parameter 10: institute (short) name
	display_configuration_parameter($installType, get_lang("InstituteShortName"), "institutionForm", $institutionForm);

	//Parameter 11: institute (short) name
	display_configuration_parameter($installType, get_lang("InstituteURL"), "institutionUrlForm", $institutionUrlForm);
	
	/*
	 //old method
	  	<tr>
	  <td><?php echo get_lang("EncryptUserPass"); ?> :</td>

	  <?php if($installType == 'update'): ?>
	  <td><input type="hidden" name="encryptPassForm" value="<?php echo $encryptPassForm; ?>" /><?php echo $encryptPassForm? get_lang("Yes") : get_lang("No"); ?></td>
	  <?php else: ?>
	  <td>
		<input class="checkbox" type="radio" name="encryptPassForm" value="1" id="encryptPass1" <?php echo $encryptPassForm?'checked="checked" ':''; ?>/> <label for="encryptPass1"><?php echo get_lang("Yes"); ?></label>
		<input class="checkbox" type="radio" name="encryptPassForm" value="0" id="encryptPass0" <?php echo $encryptPassForm?'':'checked="checked" '; ?>/> <label for="encryptPass0"><?php echo get_lang("No"); ?></label>
	  </td>
	  <?php endif; ?>
	</tr>
	
	 */
	
	?>
	<tr>
	  <td><?php echo get_lang("EncryptMethodUserPass"); ?> :</td>
 
	  <?php if($installType == 'update'): ?>
	  <td><input type="hidden" name="encryptPassForm" value="<?php echo $encryptPassForm; ?>" /><?php  echo $encryptPassForm; ?></td>
	  <?php else: ?>
	  <td>
		<input class="checkbox" type="radio" name="encryptPassForm" value="md5" id="encryptPass0" <?php echo $encryptPassForm?'checked="checked" ':''; ?>/> <label for="encryptPass0"><?php echo "md5"; ?></label>
		<input class="checkbox" type="radio" name="encryptPassForm" value="sha1" id="encryptPass1" <?php echo $encryptPassForm?'':'checked="checked" '; ?>/> <label for="encryptPass1"><?php echo "sha1"; ?></label>
		<input class="checkbox" type="radio" name="encryptPassForm" value="none" id="encryptPass2" <?php echo $encryptPassForm?'':'checked="checked" '; ?>/> <label for="encryptPass2"><?php echo get_lang("None"); ?></label>
	  </td>
	  <?php endif; ?>
	</tr>
	

	
	
	
	<tr>
	  <td><?php echo get_lang("AllowSelfReg"); ?> :</td>

	  <?php if($installType == 'update'): ?>
	  <td><input type="hidden" name="allowSelfReg" value="<?php echo $allowSelfReg; ?>" /><?php echo $allowSelfReg? get_lang("Yes") : get_lang("No"); ?></td>
	  <?php else: ?>
	  <td>
		<input class="checkbox" type="radio" name="allowSelfReg" value="1" id="allowSelfReg1" <?php echo $allowSelfReg?'checked="checked" ':''; ?>/> <label for="allowSelfReg1"><?php echo get_lang("Yes").' '.get_lang("Recommended"); ?></label>
		<input class="checkbox" type="radio" name="allowSelfReg" value="0" id="allowSelfReg0" <?php echo $allowSelfReg?'':'checked="checked" '; ?>/> <label for="allowSelfReg0"><?php echo get_lang("No"); ?></label>
	  </td>
	  <?php endif; ?>

	</tr>
	<tr>
	  <td><?php echo get_lang("AllowSelfRegProf"); ?> :</td>

	  <?php if($installType == 'update'): ?>
	  <td><input type="hidden" name="allowSelfRegProf" value="<?php echo $allowSelfRegProf; ?>" /><?php echo $allowSelfRegProf? get_lang("Yes") : get_lang("No"); ?></td>
	  <?php else: ?>
	  <td>
		<input class="checkbox" type="radio" name="allowSelfRegProf" value="1" id="allowSelfRegProf1" <?php echo $allowSelfRegProf?'checked="checked" ':''; ?>/> <label for="allowSelfRegProf1"><?php echo get_lang("Yes"); ?></label>
		<input class="checkbox" type="radio" name="allowSelfRegProf" value="0" id="allowSelfRegProf0" <?php echo $allowSelfRegProf?'':'checked="checked" '; ?>/> <label for="allowSelfRegProf0"><?php echo get_lang("No"); ?></label>
	  </td>
	  <?php endif; ?>

	</tr>
	<tr>
	  <td><button type="submit" class="back" name="step3" value="&lt; <?php echo get_lang('Previous'); ?>" /><?php echo get_lang('Previous'); ?></button></td>
	  <td align="right"><input type="hidden" name="is_executable" id="is_executable" value="-" /><button class="next" type="submit" name="step5" value="<?php echo get_lang('Next'); ?> &gt;" /><?php echo get_lang('Next'); ?></button></td>
	</tr>
	</table>
	<?php
}

/**
* After installation is completed (step 6), this message is displayed.
*/
function display_after_install_message($installType, $nbr_courses)
{
	?>
	<h2><?php echo display_step_sequence() . get_lang("CfgSetting"); ?></h2>

	<?php echo get_lang('FirstUseTip'); ?>

	<?php if($installType == 'update' && $nbr_courses > MAX_COURSE_TRANSFER): ?>
	<br /><br />
	<font color="red"><b><?php echo get_lang('Warning');?> :</b> <?php printf(get_lang('YouHaveMoreThanXCourses'),MAX_COURSE_TRANSFER,MAX_COURSE_TRANSFER,'<a href="update_courses.php"><font color="red">','</font></a>');?></font>
	<?php endif; ?>

	<br /><br />
	<?php
	echo '<div class="warning-message">';
	//echo '<img src="../img/message_warning.png" style="float:left; margin-right:10px;" alt="'.get_lang('Warning').'"/>';
	echo '<b>'.get_lang('SecurityAdvice').'</b>';
	echo ': ';
	printf(get_lang('ToProtectYourSiteMakeXAndYReadOnly'),'main/inc/conf/configuration.php','main/install/index.php');
	echo '</div>';
	?>


	</form>
	<a class="portal" href="../../index.php"><?php echo get_lang('GoToYourNewlyCreatedPortal'); ?></a>
	<?php
}

/**
* In step 3. Test the connection to the DB in case of single or multy DB.
* Return "1"if no problems, "0" if, in case of multiDB we can't create a new DB and "-1" if there is no connection.
*/
function test_db_connect ($dbHostForm, $dbUsernameForm, $dbPassForm, $singleDbForm, $dbPrefixForm) {
	$dbConnect = -1;
	if ($singleDbForm == 1) {
		if(@mysql_connect($dbHostForm, $dbUsernameForm, $dbPassForm) !== false) {
			$dbConnect = 1;
		} else {
			$dbConnect = -1;
		}
	} elseif ($singleDbForm == 0) {
		$res=@mysql_connect($dbHostForm, $dbUsernameForm, $dbPassForm);
		if ($res===false) {
			return $res;
		}
		if ($res !== false) {
			// The Dokeos system has not been designed to use special SQL modes that were introduced since MySQL 5
			@mysql_query("set session sql_mode='';");

			$multipleDbCheck = @mysql_query("CREATE DATABASE ".$dbPrefixForm."test_dokeos_connection");
			if ($multipleDbCheck !== false) {
				$multipleDbCheck = @mysql_query("DROP DATABASE IF EXISTS ".$dbPrefixForm."test_dokeos_connection");
				if ($multipleDbCheck !== false) {
					$dbConnect = 1;
				} else {
					$dbConnect = 0;
				}
			} else {
				$dbConnect = 0;
			}
		} else {
			$dbConnect = -1;
		}
	}
	return($dbConnect); //return "1"if no problems, "0" if, in case of multiDB we can't create a new DB and "-1" if there is no connection.
}
?>
