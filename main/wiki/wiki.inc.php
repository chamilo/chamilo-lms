<?php
/* For licensing terms, see /license.txt */
/**
 * Functions library for the wiki tool
 * @author Juan Carlos Raña <herodoto@telefonica.net>
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @author Julio Montoya <gugli100@gmail.com> using the pdf.lib.php library
 * @package chamilo.wiki
 */

use \ChamiloSession as Session;

class Wiki
{
    public $tbl_wiki;
    public $tbl_wiki_discuss;
    public $tbl_wiki_mailcue;
    public $tbl_wiki_conf;
    public $session_id = null;
    public $course_id = null;
    public $condition_session = null;
    public $group_id;
    public $assig_user_id;
    public $groupfilter = 'group_id=0';
    public $courseInfo;
    public $charset;
    public $page;
    public $action;
    public $wikiData = array();
    public $url;

    public function __construct()
    {
        // Database table definition
        $this->tbl_wiki           = Database::get_course_table(TABLE_WIKI);
        $this->tbl_wiki_discuss   = Database::get_course_table(TABLE_WIKI_DISCUSS);
        $this->tbl_wiki_mailcue   = Database::get_course_table(TABLE_WIKI_MAILCUE);
        $this->tbl_wiki_conf      = Database::get_course_table(TABLE_WIKI_CONF);

        $this->session_id = api_get_session_id();
        $this->condition_session = api_get_session_condition($this->session_id);
        $this->course_id = api_get_course_int_id();
        $this->group_id = api_get_group_id();


        if (!empty($this->group_id)) {
            $this->groupfilter = ' group_id="'.$this->group_id.'"';
        }
        $this->courseInfo = api_get_course_info();
        $this->url = api_get_path(WEB_CODE_PATH).'wiki/index.php?'.api_get_cidreq();
    }

    /**
     * Check whether this title is already used
     * @param string $link
     * @return bool  False if title is already taken
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
     **/
    public function checktitle($link)
    {
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $course_id = $this->course_id;
        $groupfilter = $this->groupfilter;

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$course_id.' AND
                    reflink="'.Database::escape_string($link).'" AND
                    '.$groupfilter.$condition_session.'';
        $result = Database::query($sql);
        $numberofresults = Database::num_rows($result);
        // the value has not been found and is this available
        if ($numberofresults == 0) {
            return true;
        } else {
            // the value has been found
            return false;
        }
    }

    /**
     * check wikilinks that has a page
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     * @param string $input
     **/
    public function links_to($input)
    {
        $input_array = preg_split("/(\[\[|\]\])/",$input,-1, PREG_SPLIT_DELIM_CAPTURE);
        $all_links = array();

        foreach ($input_array as $key=>$value) {
            if (isset($input_array[$key-1]) && $input_array[$key-1] == '[[' &&
                isset($input_array[$key+1]) && $input_array[$key+1] == ']]'
            ) {
                if (api_strpos($value, "|") !== false) {
                    $full_link_array=explode("|", $value);
                    $link=trim($full_link_array[0]);
                    $title=trim($full_link_array[1]);
                } else {
                    $link=trim($value);
                    $title=trim($value);
                }
                unset($input_array[$key-1]);
                unset($input_array[$key+1]);
                //replace blank spaces by _ within the links. But to remove links at the end add a blank space
                $all_links[]= Database::escape_string(str_replace(' ','_',$link)).' ';
            }
        }
        $output = implode($all_links);

        return $output;
    }

    /**
     * detect and add style to external links
     * @author Juan Carlos Raña Trabado
     **/
    public function detect_external_link($input)
    {
        $exlink='href=';
        $exlinkStyle='class="wiki_link_ext" href=';
        $output=str_replace($exlink, $exlinkStyle, $input);
        return $output;
    }

    /**
     * detect and add style to anchor links
     * @author Juan Carlos Raña Trabado
     **/
    public function detect_anchor_link($input)
    {
        $anchorlink = 'href="#';
        $anchorlinkStyle='class="wiki_anchor_link" href="#';
        $output = str_replace($anchorlink, $anchorlinkStyle, $input);

        return $output;
    }

    /**
     * detect and add style to mail links
     * author Juan Carlos Raña Trabado
     **/
    public function detect_mail_link($input)
    {
        $maillink='href="mailto';
        $maillinkStyle='class="wiki_mail_link" href="mailto';
        $output=str_replace($maillink, $maillinkStyle, $input);
        return $output;
    }

    /**
     * detect and add style to ftp links
     * @author Juan Carlos Raña Trabado
     **/
    public function detect_ftp_link($input)
    {
        $ftplink='href="ftp';
        $ftplinkStyle='class="wiki_ftp_link" href="ftp';
        $output=str_replace($ftplink, $ftplinkStyle, $input);
        return $output;
    }

    /**
     * detect and add style to news links
     * @author Juan Carlos Raña Trabado
     **/
    public function detect_news_link($input)
    {
        $newslink='href="news';
        $newslinkStyle='class="wiki_news_link" href="news';
        $output=str_replace($newslink, $newslinkStyle, $input);
        return $output;
    }

    /**
     * detect and add style to irc links
     * @author Juan Carlos Raña Trabado
     **/
    public function detect_irc_link($input)
    {
        $irclink='href="irc';
        $irclinkStyle='class="wiki_irc_link" href="irc';
        $output=str_replace($irclink, $irclinkStyle, $input);
        return $output;
    }
    /**
     * This function allows users to have [link to a title]-style links like in most regular wikis.
     * It is true that the adding of links is probably the most anoying part of Wiki for the people
     * who know something about the wiki syntax.
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
     * Improvements [[]] and [[ | ]]by Juan Carlos Raña
     * Improvements internal wiki style and mark group by Juan Carlos Raña
     **/
    public function make_wiki_link_clickable($input)
    {
        $groupId = api_get_group_id();
        //now doubles brackets
        $input_array = preg_split("/(\[\[|\]\])/",$input,-1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($input_array as $key => $value) {
            //now doubles brackets
            if (isset($input_array[$key-1]) && $input_array[$key-1] == '[[' AND
                $input_array[$key+1] == ']]'
            ) {
                // now full wikilink
                if (api_strpos($value, "|") !== false){
                    $full_link_array=explode("|", $value);
                    $link=trim(strip_tags($full_link_array[0]));
                    $title=trim($full_link_array[1]);
                } else {
                    $link=trim(strip_tags($value));
                    $title=trim($value);
                }

                //if wikilink is homepage
                if ($link=='index') {
                    $title=get_lang('DefaultTitle');
                }
                if ($link==get_lang('DefaultTitle')){
                    $link='index';
                }

                // note: checkreflink checks if the link is still free. If it is not used then it returns true, if it is used, then it returns false. Now the title may be different
                if (self::checktitle(strtolower(str_replace(' ','_',$link)))) {
                    $link = api_html_entity_decode($link);
                    $input_array[$key]='<a href="'.api_get_path(WEB_PATH).'main/wiki/index.php?'.api_get_cidreq().'&action=addnew&amp;title='.Security::remove_XSS($link).'&group_id='.$groupId.'" class="new_wiki_link">'.$title.'</a>';
                } else {
                    $input_array[$key]='<a href="'.api_get_path(WEB_PATH).'main/wiki/index.php?'.api_get_cidreq().'&action=showpage&amp;title='.urlencode(strtolower(str_replace(' ','_',$link))).'&group_id='.$groupId.'" class="wiki_link">'.$title.'</a>';
                }
                unset($input_array[$key-1]);
                unset($input_array[$key+1]);
            }
        }
        $output=implode('',$input_array);
        return $output;
    }

    /**
     * This function saves a change in a wiki page
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
     * @return language string saying that the changes are stored
     **/
    public function save_wiki($values)
    {
        $tbl_wiki = $this->tbl_wiki;
        $tbl_wiki_conf = $this->tbl_wiki_conf;
        $_course = $this->courseInfo;
        $dtime = date( "Y-m-d H:i:s" );
        $session_id = api_get_session_id();
        $groupId = api_get_group_id();

        $_clean = array(
            'task' => null,
            'feedback1' => null,
            'feedback2' => null,
            'feedback3' => null,
            'fprogress1' => null,
            'fprogress2' => null,
            'fprogress3' => null,
            'max_text' => null,
            'max_version' => null,
            'delayedsubmit' => null,
            'assignment' => null
        );

        // NOTE: visibility, visibility_disc and ratinglock_disc changes are not made here, but through the interce buttons

        // cleaning the variables
        $_clean['page_id']		= Database::escape_string($values['page_id']);
        $_clean['reflink']		= Database::escape_string(trim($values['reflink']));
        $_clean['title']		= Database::escape_string(trim($values['title']));
        $_clean['content']		= Database::escape_string($values['content']);
        if (api_get_setting('htmlpurifier_wiki') == 'true'){
            $purifier = new HTMLPurifier();
            $_clean['content'] = $purifier->purify($_clean['content']);
        }
        $_clean['user_id']		= api_get_user_id();
        $_clean['assignment']	= Database::escape_string($values['assignment']);
        $_clean['comment']		= Database::escape_string($values['comment']);
        $_clean['progress']		= Database::escape_string($values['progress']);
        $_clean['version']		= intval($values['version']) + 1 ;
        $_clean['linksto'] 		= self::links_to($_clean['content']); //and check links content

        //cleaning config variables
        if (!empty($values['task'])) {
            $_clean['task'] = Database::escape_string($values['task']);
        }

        if (!empty($values['feedback1']) || !empty($values['feedback2']) || !empty($values['feedback3'])) {
            $_clean['feedback1']=Database::escape_string($values['feedback1']);
            $_clean['feedback2']=Database::escape_string($values['feedback2']);
            $_clean['feedback3']=Database::escape_string($values['feedback3']);
            $_clean['fprogress1']=Database::escape_string($values['fprogress1']);
            $_clean['fprogress2']=Database::escape_string($values['fprogress2']);
            $_clean['fprogress3']=Database::escape_string($values['fprogress3']);
        }

        if (isset($values['initstartdate']) && $values['initstartdate'] == 1) {
            $_clean['startdate_assig'] = Database::escape_string($values['startdate_assig']);
        } else {
            $_clean['startdate_assig'] = '0000-00-00 00:00:00';
        }

        if (isset($values['initenddate']) && $values['initenddate']==1) {
            $_clean['enddate_assig'] = Database::escape_string($values['enddate_assig']);
        } else {
            $_clean['enddate_assig'] = '0000-00-00 00:00:00';
        }

        if (isset($values['delayedsubmit'])) {
            $_clean['delayedsubmit']=Database::escape_string($values['delayedsubmit']);
        }

        if (!empty($values['max_text']) || !empty($values['max_version'])) {
            $_clean['max_text']	=Database::escape_string($values['max_text']);
            $_clean['max_version']=Database::escape_string($values['max_version']);
        }

        $course_id = api_get_course_int_id();
        $sql = "INSERT INTO ".$tbl_wiki." (c_id, page_id, reflink, title, content, user_id, group_id, dtime, assignment, comment, progress, version, linksto, user_ip, session_id)
                VALUES ($course_id, '".$_clean['page_id']."','".$_clean['reflink']."','".$_clean['title']."','".$_clean['content']."','".$_clean['user_id']."','".$groupId."','".$dtime."','".$_clean['assignment']."','".$_clean['comment']."','".$_clean['progress']."','".$_clean['version']."','".$_clean['linksto']."','".Database::escape_string($_SERVER['REMOTE_ADDR'])."', '".Database::escape_string($session_id)."')";
        Database::query($sql);
        $Id = Database::insert_id();

        if ($Id > 0) {
            //insert into item_property
            api_item_property_update(api_get_course_info(), TOOL_WIKI, $Id, 'WikiAdded', api_get_user_id(), $groupId);
        }

        if ($_clean['page_id']	==0) {
            $sql='UPDATE '.$tbl_wiki.' SET page_id="'.$Id.'" WHERE c_id = '.$course_id.' AND id="'.$Id.'"';
            Database::query($sql);
        }

        //update wiki config
        if ($_clean['reflink']=='index' && $_clean['version']==1) {
            $sql="INSERT INTO ".$tbl_wiki_conf." (c_id, page_id, task, feedback1, feedback2, feedback3, fprogress1, fprogress2, fprogress3, max_text, max_version, startdate_assig, enddate_assig, delayedsubmit)
                  VALUES ($course_id, '".$Id."','".$_clean['task']."','".$_clean['feedback1']."','".$_clean['feedback2']."','".$_clean['feedback3']."','".$_clean['fprogress1']."','".$_clean['fprogress2']."','".$_clean['fprogress3']."','".$_clean['max_text']."','".$_clean['max_version']."','".$_clean['startdate_assig']."','".$_clean['enddate_assig']."','".$_clean['delayedsubmit']."')";
        } else {
            $sql = 'UPDATE '.$tbl_wiki_conf.' SET
                        task="'.$_clean['task'].'",
                        feedback1="'.$_clean['feedback1'].'",
                        feedback2="'.$_clean['feedback2'].'",
                        feedback3="'.$_clean['feedback3'].'",
                        fprogress1="'.$_clean['fprogress1'].'",
                        fprogress2="'.$_clean['fprogress2'].'",
                        fprogress3="'.$_clean['fprogress3'].'",
                        max_text="'.$_clean['max_text'].'",
                        max_version="'.$_clean['max_version'].'",
                        startdate_assig="'.$_clean['startdate_assig'].'",
                        enddate_assig="'.$_clean['enddate_assig'].'",
                        delayedsubmit="'.$_clean['delayedsubmit'].'"
                    WHERE
                        page_id = "'.$_clean['page_id'].'" AND
                        c_id = '.$course_id;
        }
        Database::query($sql);
        api_item_property_update($_course, 'wiki', $Id, 'WikiAdded', api_get_user_id(), $groupId);
        self::check_emailcue($_clean['reflink'], 'P', $dtime, $_clean['user_id']);
        $this->setWikiData($Id);

        return get_lang('Saved');
    }

    /**
     * This function restore a wikipage
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     * @return string Message of success (to be printed on screen)
     **/
    public function restore_wikipage(
        $r_page_id,
        $r_reflink,
        $r_title,
        $r_content,
        $r_group_id,
        $r_assignment,
        $r_progress,
        $c_version,
        $r_version,
        $r_linksto
    ) {
        $tbl_wiki = $this->tbl_wiki;
        $_course = $this->courseInfo;
        $r_user_id = api_get_user_id();
        $r_dtime = api_get_utc_datetime();
        $r_version = $r_version+1;
        $r_comment = get_lang('RestoredFromVersion').': '.$c_version;
        $session_id = api_get_session_id();
        $course_id = api_get_course_int_id();

        $r_page_id = Database::escape_string($r_page_id);
        $r_title = Database::escape_string($r_title);
        $r_content = Database::escape_string($r_content);
        $r_group_id = Database::escape_string($r_group_id);
        $r_assignment = Database::escape_string($r_assignment);
        $r_progress = Database::escape_string($r_progress);
        $r_version = Database::escape_string($r_version);
        $r_linksto = Database::escape_string($r_linksto);
        $r_comment = Database::escape_string($r_comment);

        $sql = "INSERT INTO ".$tbl_wiki." (c_id, page_id, reflink, title, content, user_id, group_id, dtime, assignment, comment, progress, version, linksto, user_ip, session_id) VALUES
        ($course_id, '".$r_page_id."','".$r_reflink."','".$r_title."','".$r_content."','".$r_user_id."','".$r_group_id."','".$r_dtime."','".$r_assignment."','".$r_comment."','".$r_progress."','".$r_version."','".$r_linksto."','".Database::escape_string($_SERVER['REMOTE_ADDR'])."','".Database::escape_string($session_id)."')";

        Database::query($sql);
        $Id = Database::insert_id();
        api_item_property_update($_course, 'wiki', $Id, 'WikiAdded', api_get_user_id(), $r_group_id);
        self::check_emailcue($r_reflink, 'P', $r_dtime, $r_user_id);

        return get_lang('PageRestored');
    }

    /**
     * This function delete a wiki
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     * @return   string  Message of success (to be printed)
     **/
    public function delete_wiki()
    {
        $tbl_wiki = $this->tbl_wiki;
        $tbl_wiki_discuss = $this->tbl_wiki_discuss;
        $tbl_wiki_mailcue = $this->tbl_wiki_mailcue;
        $tbl_wiki_conf = $this->tbl_wiki_conf;
        $session_id = $this->session_id;
        $condition_session = $this->condition_session;
        $group_id = $this->group_id;
        $groupfilter = $this->groupfilter;
        $course_id = $this->course_id;

        //identify the first id by group = identify wiki
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE  c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
                ORDER BY id DESC';
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $id 		= $row['id'];
            $group_id	= $row['group_id'];
            $session_id = $row['session_id'];
            //$page_id	= $row['page_id'];
            Database::query('DELETE FROM '.$tbl_wiki_conf.' 	WHERE page_id="'.$id.'" AND c_id = '.$course_id);
            Database::query('DELETE FROM '.$tbl_wiki_discuss.'	WHERE publication_id="'.$id.'" AND c_id = '.$course_id);
        }

        Database::query('DELETE FROM '.$tbl_wiki_mailcue.' WHERE session_id="'.$session_id.'" AND group_id="'.$group_id.'" AND c_id = '.$course_id);
        Database::query('DELETE FROM '.$tbl_wiki.' WHERE session_id="'.$session_id.'" AND group_id="'.$group_id.'" AND c_id = '.$course_id);
        return get_lang('WikiDeleted');
    }

    /**
     * This function saves a new wiki page.
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
     * @todo consider merging this with the function save_wiki into one single function.
     * @return string Message of success
     **/
    public function save_new_wiki($values)
    {
        $tbl_wiki = $this->tbl_wiki;
        $tbl_wiki_conf = $this->tbl_wiki_conf;
        $charset = $this->charset;
        $assig_user_id = $this->assig_user_id;

        $_clean = array();

        // cleaning the variables
        $_clean['assignment'] = null;
        if (isset($values['assignment'])) {
            $_clean['assignment'] = Database::escape_string($values['assignment']);
        }

        // session_id
        $session_id = api_get_session_id();
        // Unlike ordinary pages of pages of assignments. Allow create a ordinary page although there is a assignment with the same name
        if ($_clean['assignment']==2 || $_clean['assignment']==1) {
            $page = str_replace(' ','_',$values['title']."_uass".$assig_user_id);
        } else {
            $page = str_replace(' ','_',$values['title']);
        }
        $_clean['reflink'] = Database::escape_string($page);
        $_clean['title']   = Database::escape_string(trim($values['title']));
        $_clean['content'] = Database::escape_string($values['content']);

        if (api_get_setting('htmlpurifier_wiki') == 'true'){
            $purifier = new HTMLPurifier();
            $_clean['content'] = $purifier->purify($_clean['content']);
        }

        //re-check after strip_tags if the title is empty
        if(empty($_clean['title']) || empty($_clean['reflink'])) {
            return false;
        }

        if ($_clean['assignment']==2)  {//config by default for individual assignment (students)
            //Identifies the user as a creator, not the teacher who created
            $_clean['user_id']=(int)Database::escape_string($assig_user_id);
            $_clean['visibility']=0;
            $_clean['visibility_disc']=0;
            $_clean['ratinglock_disc']=0;
        } else {
            $_clean['user_id']=api_get_user_id();
            $_clean['visibility']=1;
            $_clean['visibility_disc']=1;
            $_clean['ratinglock_disc']=1;
        }

        $_clean['comment']=Database::escape_string($values['comment']);
        $_clean['progress']=Database::escape_string($values['progress']);
        $_clean['version']=1;

        $groupId = api_get_group_id();

        $_clean['linksto'] = self::links_to($_clean['content']);	//check wikilinks

        //cleaning config variables
        $_clean['task']= Database::escape_string($values['task']);
        $_clean['feedback1']=Database::escape_string($values['feedback1']);
        $_clean['feedback2']=Database::escape_string($values['feedback2']);
        $_clean['feedback3']=Database::escape_string($values['feedback3']);
        $_clean['fprogress1']=Database::escape_string($values['fprogress1']);
        $_clean['fprogress2']=Database::escape_string($values['fprogress2']);
        $_clean['fprogress3']=Database::escape_string($values['fprogress3']);

        if (isset($values['initstartdate']) && $values['initstartdate'] == 1) {
            $_clean['startdate_assig'] = Database::escape_string($values['startdate_assig']);
        } else {
            $_clean['startdate_assig'] = '0000-00-00 00:00:00';
        }

        if (isset($values['initenddate']) && $values['initenddate']==1) {
            $_clean['enddate_assig'] = Database::escape_string($values['enddate_assig']);
        } else {
            $_clean['enddate_assig'] = '0000-00-00 00:00:00';
        }

        $_clean['delayedsubmit']=Database::escape_string($values['delayedsubmit']);
        $_clean['max_text']=Database::escape_string($values['max_text']);
        $_clean['max_version']=Database::escape_string($values['max_version']);

        $course_id = api_get_course_int_id();

        // Filter no _uass
        if (api_eregi('_uass', $values['title']) ||
            (api_strtoupper(trim($values['title'])) == 'INDEX' ||
                api_strtoupper(trim(api_htmlentities($values['title'], ENT_QUOTES, $charset))) == api_strtoupper(api_htmlentities(get_lang('DefaultTitle'), ENT_QUOTES, $charset)))
        ) {
            self::setMessage(Display::display_warning_message(get_lang('GoAndEditMainPage'), false, true));
        } else {
            $var = $_clean['reflink'];
            $group_id = intval($_GET['group_id']);
            if (!self::checktitle($var)) {
                return get_lang('WikiPageTitleExist').'<a href="index.php?action=edit&amp;title='.$var.'&group_id='.$group_id.'">'.$values['title'].'</a>';
            } else {
                $dtime = date( "Y-m-d H:i:s" );
                $sql = "INSERT INTO ".$tbl_wiki." (c_id, reflink, title, content, user_id, group_id, dtime, visibility, visibility_disc, ratinglock_disc, assignment, comment, progress, version, linksto, user_ip, session_id) VALUES
                        ($course_id, '".$_clean['reflink']."','".$_clean['title']."','".$_clean['content']."','".$_clean['user_id']."','".$groupId."','".$dtime."','".$_clean['visibility']."','".$_clean['visibility_disc']."','".$_clean['ratinglock_disc']."','".$_clean['assignment']."','".$_clean['comment']."','".$_clean['progress']."','".$_clean['version']."','".$_clean['linksto']."','".Database::escape_string($_SERVER['REMOTE_ADDR'])."', '".Database::escape_string($session_id)."')";
                Database::query($sql);
                $Id = Database::insert_id();
                if ($Id > 0) {
                    //insert into item_property
                    api_item_property_update(api_get_course_info(), TOOL_WIKI, $Id, 'WikiAdded', api_get_user_id(), $groupId);
                }

                $sql = 'UPDATE '.$tbl_wiki.' SET page_id="'.$Id.'" WHERE c_id = '.$course_id.' AND id="'.$Id.'"';
                Database::query($sql);

                //insert wiki config
                $sql = " INSERT INTO ".$tbl_wiki_conf." (c_id, page_id, task, feedback1, feedback2, feedback3, fprogress1, fprogress2, fprogress3, max_text, max_version, startdate_assig, enddate_assig, delayedsubmit)
                        VALUES ($course_id, '".$Id."','".$_clean['task']."','".$_clean['feedback1']."','".$_clean['feedback2']."','".$_clean['feedback3']."','".$_clean['fprogress1']."','".$_clean['fprogress2']."','".$_clean['fprogress3']."','".$_clean['max_text']."','".$_clean['max_version']."','".$_clean['startdate_assig']."','".$_clean['enddate_assig']."','".$_clean['delayedsubmit']."')";
                Database::query($sql);
                $this->setWikiData($Id);
                self::check_emailcue(0, 'A');
                return get_lang('NewWikiSaved');
            }
        }
    }

    /**
     * @param FormValidator $form
     * @param array $row
     */
    public function setForm($form, $row = array())
    {
        $toolBar = api_is_allowed_to_edit(null,true)
            ? array('ToolbarSet' => 'Wiki', 'Width' => '100%', 'Height' => '400')
            : array('ToolbarSet' => 'WikiStudent', 'Width' => '100%', 'Height' => '400', 'UserStatus' => 'student');

        $form->add_html_editor('content', get_lang('Content'), false, false, $toolBar);
        //$content
        $form->addElement('text', 'comment', get_lang('Comments'));
        $progress = array('', 10, 20, 30, 40, 50, 60, 70, 80, 90, 100);

        $form->addElement('select', 'progress', get_lang('Progress'), $progress);

        if ((api_is_allowed_to_edit(false,true) || api_is_platform_admin()) && isset($row['reflink']) && $row['reflink'] != 'index') {

            $advanced = '<a href="javascript://" onclick="advanced_parameters()" >
                         <div id="plus_minus">&nbsp;'.
                Display::return_icon(
                    'div_show.gif',
                    get_lang('Show'),
                    array('style'=>'vertical-align:middle')
                ).'&nbsp;'.get_lang('AdvancedParameters').'</div></a>';

            $form->addElement('advanced_settings', $advanced);

            $form->addElement('html', '<div id="options" style="display:none">');
            $form->add_html_editor('task', get_lang('DescriptionOfTheTask'), false, false, array('ToolbarSet' => 'wiki_task', 'Width' => '100%', 'Height' => '200'));

            $form->addElement('label', null, get_lang('AddFeedback'));
            $form->addElement('textarea', 'feedback1', get_lang('Feedback1'));
            $form->addElement('select', 'fprogress1', get_lang('FProgress'), $progress);

            $form->addElement('textarea', 'feedback2', get_lang('Feedback2'));
            $form->addElement('select', 'fprogress2', get_lang('FProgress'), $progress);

            $form->addElement('textarea', 'feedback3', get_lang('Feedback3'));
            $form->addElement('select', 'fprogress3', get_lang('FProgress'), $progress);

            $form->addElement('checkbox', 'initstartdate', null, get_lang('StartDate'), array('id' => 'start_date_toggle'));

            $style = "display:block";
            $row['initstartdate'] = 1;
            if ($row['startdate_assig'] == '0000-00-00 00:00:00') {
                $style = "display:none";
                $row['initstartdate'] = null;
            }

            $form->addElement('html', '<div id="start_date" style="'.$style.'">');
            $form->addElement('datepicker', 'startdate_assig');
            $form->addElement('html', '</div>');
            $form->addElement('checkbox', 'initenddate', null, get_lang('EndDate'), array('id' => 'end_date_toggle'));

            $style = "display:block";
            $row['initenddate'] = 1;
            if ($row['enddate_assig'] == '0000-00-00 00:00:00') {
                $style = "display:none";
                $row['initenddate'] = null;
            }

            $form->addElement('html', '<div id="end_date" style="'.$style.'">');
            $form->addElement('datepicker', 'enddate_assig');
            $form->addElement('html', '</div>');
            $form->addElement('checkbox', 'delayedsubmit', null, get_lang('AllowLaterSends'));
            $form->addElement('text', 'max_text', get_lang('NMaxWords'));
            $form->addElement('text', 'max_version', get_lang('NMaxVersion'));
            $form->addElement('checkbox', 'assignment', null, get_lang('CreateAssignmentPage'));
            $form->addElement('html', '</div>');
        }

        $form->addElement('hidden', 'page_id');
        $form->addElement('hidden', 'reflink');
//        $form->addElement('hidden', 'assignment');
        $form->addElement('hidden', 'version');
        $form->addElement('hidden', 'wpost_id', api_get_unique_id());
    }

    /**
     * This function displays the form for adding a new wiki page.
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
     * @return html code
     **/
    public function display_new_wiki_form()
    {
        $url = api_get_self().'?'.api_get_cidreq().'&action=addnew&group_id='.api_get_group_id();
        $form = new FormValidator('wiki_new', 'post', $url);
        $form->addElement('text', 'title', get_lang('Title'));
        $form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');
        self::setForm($form);
        $form->addElement('button', 'SaveWikiNew', get_lang('Save'));
        $form->display();

        if ($form->validate()) {
            $values = $form->exportValues();

            if (empty($_POST['title'])) {
                self::setMessage(Display::display_error_message(get_lang("NoWikiPageTitle"), false, true));
            } elseif (strtotime($values['startdate_assig']) > strtotime($values['enddate_assig'])) {
                self::setMessage(Display::display_error_message(get_lang("EndDateCannotBeBeforeTheStartDate"), false, true));
            } elseif (!self::double_post($_POST['wpost_id'])) {
                //double post
            } else {
                if ($values['assignment'] == 1) {
                    self::auto_add_page_users($values);
                }
                $return_message = self::save_new_wiki($values);

                if ($return_message == false) {
                    self::setMessage(Display::display_error_message(get_lang('NoWikiPageTitle'), false, true));
                } else {
                    self::setMessage(Display::display_confirmation_message($return_message, false, true));
                }

                $wikiData = self::getWikiData();
                $redirectUrl = $this->url.'&action=showpage&title='.$wikiData['reflink'];
                header('Location: '.$redirectUrl);
                exit;
            }
        }
    }

    /**
     * This function displays a wiki entry
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
     * @author Juan Carlos Raña Trabado
     * @param string $newtitle
     * @return string html code
     **/
    public function display_wiki_entry($newtitle)
    {
        $tbl_wiki = $this->tbl_wiki;
        $tbl_wiki_conf = $this->tbl_wiki_conf;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $page = $this->page;

        $session_id = api_get_session_id();
        $course_id = api_get_course_int_id();

        if ($newtitle) {
            $pageMIX = $newtitle; //display the page after it is created
        } else {
            $pageMIX = $page;//display current page
        }

        $filter = null;
        if (isset($_GET['view']) && $_GET['view']) {
            $_clean['view'] = Database::escape_string($_GET['view']);
            $filter =' AND w.id="'.$_clean['view'].'"';
        }

        //first, check page visibility in the first page version
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$course_id.' AND
                    reflink="'.Database::escape_string($pageMIX).'" AND
                   '.$groupfilter.$condition_session.'
              ORDER BY id ASC';
        $result=Database::query($sql);
        $row = Database::fetch_array($result);
        $KeyVisibility=$row['visibility'];

        // second, show the last version
        $sql = 'SELECT * FROM '.$tbl_wiki.' w , '.$tbl_wiki_conf.' wc
                WHERE
                    wc.c_id 	  = '.$course_id.' AND
                    w.c_id 		  = '.$course_id.' AND
                    wc.page_id	  = w.page_id AND
                    w.reflink	  = "'.Database::escape_string($pageMIX).'" AND
                    w.session_id  = '.$session_id.' AND
                    w.'.$groupfilter.'  '.$filter.'
                ORDER BY id DESC';

        $result = Database::query($sql);
        $row    = Database::fetch_array($result); // we do not need a while loop since we are always displaying the last version

        //log users access to wiki (page_id)
        if (!empty($row['page_id'])) {
            event_system(LOG_WIKI_ACCESS, LOG_WIKI_PAGE_ID, $row['page_id']);
        }
        //update visits
        if ($row['id']) {
            $sql='UPDATE '.$tbl_wiki.' SET hits=(hits+1) WHERE c_id = '.$course_id.' AND id='.$row['id'].'';
            Database::query($sql);
        }

        // if both are empty and we are displaying the index page then we display the default text.
        if ($row['content']=='' AND $row['title']=='' AND $page=='index') {
            if (api_is_allowed_to_edit(false,true) || api_is_platform_admin() || GroupManager::is_user_in_group(api_get_user_id(), api_get_group_id())) {
                //Table structure for better export to pdf
                $default_table_for_content_Start='<table align="center" border="0"><tr><td align="center">';
                $default_table_for_content_End='</td></tr></table>';
                $content = $default_table_for_content_Start.sprintf(get_lang('DefaultContent'),api_get_path(WEB_IMG_PATH)).$default_table_for_content_End;
                $title=get_lang('DefaultTitle');
            } else {
                return self::setMessage(Display::display_normal_message(get_lang('WikiStandBy'), false, true));
            }
        } else {
            $content = Security::remove_XSS($row['content']);
            $title = Security::remove_XSS($row['title']);
        }

        //assignment mode: identify page type
        $icon_assignment = null;
        if ($row['assignment']==1) {
            $icon_assignment = Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'),'',ICON_SIZE_SMALL);
        } elseif($row['assignment']==2) {
            $icon_assignment = Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',ICON_SIZE_SMALL);
        }

        //task mode
        $icon_task = null;
        if (!empty($row['task'])) {
            $icon_task=Display::return_icon('wiki_task.png', get_lang('StandardTask'),'',ICON_SIZE_SMALL);
        }

        //Show page. Show page to all users if isn't hide page. Mode assignments: if student is the author, can view
        if ($KeyVisibility == "1" ||
            api_is_allowed_to_edit(false,true) ||
            api_is_platform_admin() ||
            ($row['assignment']==2 && $KeyVisibility=="0" && (api_get_user_id()==$row['user_id']))
        ) {
            echo '<div id="wikititle">';
            $protect_page = null;
            $lock_unlock_protect = null;
            // page action: protecting (locking) the page
            if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                if (self::check_protect_page()==1) {
                    $protect_page = Display::return_icon('lock.png', get_lang('PageLockedExtra'),'',ICON_SIZE_SMALL);
                    $lock_unlock_protect='unlock';
                } else {
                    $protect_page = Display::return_icon('unlock.png', get_lang('PageUnlockedExtra'),'',ICON_SIZE_SMALL);
                    $lock_unlock_protect='lock';
                }
            }

            if ($row['id']) {
                echo '<span style="float:right;">';
                echo '<a href="index.php?action=showpage&amp;actionpage='.$lock_unlock_protect.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$protect_page.'</a>';
                echo '</span>';
            }
            $visibility_page = null;
            $lock_unlock_visibility = null;
            //page action: visibility
            if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                if (self::check_visibility_page() == 1) {
                    $visibility_page= Display::return_icon('visible.png', get_lang('ShowPageExtra'),'', ICON_SIZE_SMALL);
                    $lock_unlock_visibility='invisible';

                } else {
                    $visibility_page= Display::return_icon('invisible.png', get_lang('HidePageExtra'),'', ICON_SIZE_SMALL);
                    $lock_unlock_visibility='visible';
                }
            }

            if ($row['id']) {
                echo '<span style="float:right;">';
                echo '<a href="index.php?action=showpage&amp;actionpage='.$lock_unlock_visibility.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$visibility_page.'</a>';
                echo '</span>';
            }

            //page action: notification
            if (api_is_allowed_to_session_edit()) {
                if (self::check_notify_page($page)==1) {
                    $notify_page= Display::return_icon('messagebox_info.png', get_lang('NotifyByEmail'),'',ICON_SIZE_SMALL);
                    $lock_unlock_notify_page='unlocknotify';
                } else {
                    $notify_page= Display::return_icon('mail.png', get_lang('CancelNotifyByEmail'),'',ICON_SIZE_SMALL);
                    $lock_unlock_notify_page='locknotify';
                }
            }

            echo '<span style="float:right;">';
            echo '<a href="index.php?action=showpage&amp;actionpage='.$lock_unlock_notify_page.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$notify_page.'</a>';
            echo '</span>';

            //ONly available if row['id'] is set
            if ($row['id']) {
                //page action: export to pdf
                echo '<span style="float:right;">';
                echo '<form name="form_export2PDF" method="get" action="'.api_get_path(WEB_CODE_PATH).'wiki/index.php?'.api_get_cidreq().'" >';
                echo '<input type="hidden" name="action" value="export_to_pdf">';
                echo '<input type="hidden" name="wiki_id" value="'.$row['id'].'">';
                echo '<input type="image" src="'.api_get_path(WEB_IMG_PATH).'icons/22/pdf.png" border ="0" title="'.get_lang('ExportToPDF').'" alt="'.get_lang('ExportToPDF').'" style=" width:22px; border:none; margin-top: -9px">';
                echo '</form>';
                echo '</span>';

                // Page action: copy last version to doc area
                if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                    echo '<span style="float:right;">';
                    echo '<form name="form_export2DOC" method="get" action="'.api_get_path(WEB_CODE_PATH).'wiki/index.php?'.api_get_cidreq().'" >';
                    echo '<input type=hidden name="action" value="export2doc">';
                    echo '<input type=hidden name="doc_id" value="'.$row['id'].'">';
                    echo '<input type="image" src="'.api_get_path(WEB_IMG_PATH).'icons/22/export_to_documents.png" border ="0" title="'.get_lang('ExportToDocArea').'" alt="'.get_lang('ExportToDocArea').'" style=" width:22px; border:none; margin-top: -6px">';
                    echo '</form>';
                    echo '</span>';
                }

                if (api_is_unoconv_installed()) {
                    echo '<span style="float:right;">';
                    echo '<a href="'.api_get_path(WEB_CODE_PATH).'wiki/index.php?action=export_to_doc_file&id='.$row['id'].'">'.
                        Display::return_icon('export_doc.png', get_lang('ExportToDoc'), array(), ICON_SIZE_SMALL).'</a>';
                    echo '</span>';
                }
            }

            //export to print
            ?>
            <script>
                function goprint() {
                    var a = window.open('','','width=800,height=600');
                    a.document.open("text/html");
                    a.document.write(document.getElementById('wikicontent').innerHTML);
                    a.document.close();
                    a.print();
                }
            </script>
            <?php
            echo '<span style="float:right; cursor: pointer;">';
            echo Display::return_icon('printer.png', get_lang('Print'),array('onclick' => "javascript: goprint();"),ICON_SIZE_SMALL);
            echo '</span>';

            if (empty($title)) {
                $title=get_lang('DefaultTitle');
            }

            if (self::wiki_exist($title)) {
                echo $icon_assignment.'&nbsp;'.$icon_task.'&nbsp;'.api_htmlentities($title);
            } else {
                echo api_htmlentities($title);
            }

            echo '</div>';
            echo '<div id="wikicontent">'. self::make_wiki_link_clickable(
                    self::detect_external_link(
                        self::detect_anchor_link(
                            self::detect_mail_link(
                                self::detect_ftp_link(
                                    self::detect_irc_link(
                                        self::detect_news_link($content)
                                    )
                                )
                            )
                        )
                    )
                ).'</div>';
            echo '<div id="wikifooter">'.get_lang('Progress').': '.($row['progress']*10).'%&nbsp;&nbsp;&nbsp;'.get_lang('Rating').': '.$row['score'].'&nbsp;&nbsp;&nbsp;'.get_lang('Words').': '.self::word_count($content).'</div>';
        } //end filter visibility
    }

    /**
     * This function counted the words in a document. Thanks Adeel Khan
     * @param   string  Document's text
     * @return  int     Number of words
     */
    public function word_count($document)
    {
        $search = array(
            '@<script[^>]*?>.*?</script>@si',
            '@<style[^>]*?>.*?</style>@siU',
            '@<div id="player.[^>]*?>.*?</div>@',
            '@<![\s\S]*?--[ \t\n\r]*>@'
        );

        $document = preg_replace($search, '', $document);

        # strip all html tags
        $wc = strip_tags($document);
        $wc = html_entity_decode($wc, ENT_NOQUOTES, 'UTF-8');// TODO:test also old html_entity_decode(utf8_encode($wc))

        # remove 'words' that don't consist of alphanumerical characters or punctuation. And fix accents and some letters
        $pattern = "#[^(\w|\d|\'|\"|\.|\!|\?|;|,|\\|\/|\-|:|\&|@|á|é|í|ó|ú|à|è|ì|ò|ù|ä|ë|ï|ö|ü|Á|É|Í|Ó|Ú|À|È|Ò|Ù|Ä|Ë|Ï|Ö|Ü|â|ê|î|ô|û|Â|Ê|Î|Ô|Û|ñ|Ñ|ç|Ç)]+#";
        $wc = trim(preg_replace($pattern, " ", $wc));

        # remove one-letter 'words' that consist only of punctuation
        $wc = trim(preg_replace("#\s*[(\'|\"|\.|\!|\?|;|,|\\|\/|\-|:|\&|@)]\s*#", " ", $wc));

        # remove superfluous whitespace
        $wc = preg_replace("/\s\s+/", " ", $wc);

        # split string into an array of words
        $wc = explode(" ", $wc);

        # remove empty elements
        $wc = array_filter($wc);

        # return the number of words
        return count($wc);
    }

    /**
     * This function checks if wiki title exist
     */
    public function wiki_exist($title)
    {
        $tbl_wiki = $this->tbl_wiki;
        $groupfilter = $this->groupfilter;
        $condition_session = $this->condition_session;

        $course_id = api_get_course_int_id();

        $sql='SELECT id FROM '.$tbl_wiki.'
              WHERE
                c_id = '.$course_id.' AND
                title="'.Database::escape_string($title).'" AND
                '.$groupfilter.$condition_session.'
              ORDER BY id ASC';
        $result=Database::query($sql);
        $cant=Database::num_rows($result);
        if ($cant>0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if this navigation tab has to be set to active
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
     * @return html code
     */
    public function is_active_navigation_tab($paramwk)
    {
        if (isset($_GET['action']) && $_GET['action'] == $paramwk) {
            return ' class="active"';
        }
    }

    /**
     * Lock add pages
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     * return current database status of protect page and change it if get action
     */
    public function check_addnewpagelock()
    {
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $course_id = api_get_course_int_id();

        $sql = 'SELECT *
                FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
                ORDER BY id ASC';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $status_addlock = $row['addlock'];

        // Change status
        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
            if (isset($_GET['actionpage']) && $_GET['actionpage'] =='lockaddnew' && $status_addlock==1) {
                $status_addlock=0;
            }
            if (isset($_GET['actionpage']) && $_GET['actionpage'] =='unlockaddnew' && $status_addlock==0) {
                $status_addlock=1;
            }

            Database::query('UPDATE '.$tbl_wiki.' SET addlock="'.Database::escape_string($status_addlock).'"
            WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'');

            $sql = 'SELECT *
                    FROM '.$tbl_wiki.'
                    WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
                    ORDER BY id ASC';
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
        }
        return $row['addlock'];

    }

    /**
     * Protect page
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     * return current database status of protect page and change it if get action
     */
    public function check_protect_page()
    {
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $page = $this->page;

        $course_id = api_get_course_int_id();
        $sql='SELECT * FROM '.$tbl_wiki.'
              WHERE
                c_id = '.$course_id.' AND
                reflink="'.Database::escape_string($page).'" AND
                '.$groupfilter.$condition_session.'
              ORDER BY id ASC';

        $result=Database::query($sql);
        $row=Database::fetch_array($result);
        $status_editlock = $row['editlock'];
        $id = $row['id'];

        ///change status
        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
            if (isset($_GET['actionpage']) && $_GET['actionpage']=='lock' && $status_editlock==0) {
                $status_editlock=1;
            }
            if (isset($_GET['actionpage']) && $_GET['actionpage']=='unlock' && $status_editlock==1) {
                $status_editlock=0;
            }

            $sql = 'UPDATE '.$tbl_wiki.' SET editlock="'.Database::escape_string($status_editlock).'"
                    WHERE c_id = '.$course_id.' AND id="'.$id.'"';
            Database::query($sql);

            $sql='SELECT * FROM '.$tbl_wiki.'
                  WHERE
                    c_id = '.$course_id.' AND
                    reflink="'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                  ORDER BY id ASC';
            $result=Database::query($sql);
            $row = Database::fetch_array($result);
        }

        //show status
        return $row['editlock'];
    }

    /**
     * Visibility page
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     * return current database status of visibility and change it if get action
     */
    public function check_visibility_page()
    {
        $tbl_wiki = $this->tbl_wiki;
        $page = $this->page;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $course_id = api_get_course_int_id();

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session.'
                ORDER BY id ASC';
        $result=Database::query($sql);
        $row=Database::fetch_array($result);
        $status_visibility=$row['visibility'];
        //change status
        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
            if (isset($_GET['actionpage']) && $_GET['actionpage']=='visible' && $status_visibility==0) {
                $status_visibility=1;

            }
            if (isset($_GET['actionpage']) && $_GET['actionpage']=='invisible' && $status_visibility==1) {
                $status_visibility=0;
            }

            $sql='UPDATE '.$tbl_wiki.' SET visibility="'.Database::escape_string($status_visibility).'"
                 WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session;
            Database::query($sql);

            // Although the value now is assigned to all (not only the first), these three lines remain necessary. They do that by changing the page state is made when you press the button and not have to wait to change his page
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                        '.$groupfilter.$condition_session.'
                    ORDER BY id ASC';
            $result=Database::query($sql);
            $row = Database::fetch_array($result);
        }

        if (empty($row['id'])) {
            $row['visibility']= 1;
        }

        //show status
        return $row['visibility'];
    }

    /**
     * Visibility discussion
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     * @return int current database status of discuss visibility and change it if get action page
     */
    public function check_visibility_discuss()
    {
        $tbl_wiki = $this->tbl_wiki;
        $page = $this->page;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;

        $course_id = api_get_course_int_id();

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$course_id.' AND
                    reflink="'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id ASC';
        $result=Database::query($sql);
        $row=Database::fetch_array($result);

        $status_visibility_disc = $row['visibility_disc'];

        //change status
        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
            if (isset($_GET['actionpage']) && $_GET['actionpage'] =='showdisc' && $status_visibility_disc==0) {
                $status_visibility_disc=1;
            }
            if (isset($_GET['actionpage']) && $_GET['actionpage'] =='hidedisc' && $status_visibility_disc==1) {
                $status_visibility_disc=0;
            }

            $sql = 'UPDATE '.$tbl_wiki.' SET visibility_disc="'.Database::escape_string($status_visibility_disc).'"
                    WHERE
                        c_id = '.$course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                        '.$groupfilter.$condition_session;
            Database::query($sql);

            //Although the value now is assigned to all (not only the first), these three lines remain necessary. They do that by changing the page state is made when you press the button and not have to wait to change his page
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                        '.$groupfilter.$condition_session.'
                    ORDER BY id ASC';
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
        }
        return $row['visibility_disc'];
    }

    /**
     * Lock add discussion
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     * @return int current database status of lock dicuss and change if get action
     */
    public function check_addlock_discuss()
    {
        $tbl_wiki = $this->tbl_wiki;
        $page = $this->page;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $course_id = api_get_course_int_id();

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$course_id.' AND
                    reflink="'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id ASC';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        $status_addlock_disc=$row['addlock_disc'];

        //change status
        if (api_is_allowed_to_edit() || api_is_platform_admin()) {
            if (isset($_GET['actionpage']) && $_GET['actionpage'] =='lockdisc' && $status_addlock_disc==0) {
                $status_addlock_disc=1;
            }
            if (isset($_GET['actionpage']) && $_GET['actionpage'] =='unlockdisc' && $status_addlock_disc==1) {
                $status_addlock_disc=0;
            }

            $sql = 'UPDATE '.$tbl_wiki.' SET addlock_disc="'.Database::escape_string($status_addlock_disc).'"
                    WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session;
            Database::query($sql);

            //Although the value now is assigned to all (not only the first), these three lines remain necessary. They do that by changing the page state is made when you press the button and not have to wait to change his page
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session.'
                    ORDER BY id ASC';
            $result=Database::query($sql);
            $row=Database::fetch_array($result);
        }

        return $row['addlock_disc'];
    }

    /**
     * Lock rating discussion
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     * @return  int  current database status of rating discuss and change it if get action
     */
    public function check_ratinglock_discuss()
    {
        $tbl_wiki = $this->tbl_wiki;
        $page = $this->page;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $course_id = api_get_course_int_id();

        $sql='SELECT * FROM '.$tbl_wiki.'
              WHERE  c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session.' ORDER BY id ASC';
        $result=Database::query($sql);
        $row=Database::fetch_array($result);
        $status_ratinglock_disc=$row['ratinglock_disc'];

        //change status
        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
            if (isset($_GET['actionpage']) && $_GET['actionpage'] =='lockrating' && $status_ratinglock_disc==0) {
                $status_ratinglock_disc=1;
            }
            if (isset($_GET['actionpage']) && $_GET['actionpage'] =='unlockrating' && $status_ratinglock_disc==1) {
                $status_ratinglock_disc=0;
            }

            $sql = 'UPDATE '.$tbl_wiki.' SET ratinglock_disc="'.Database::escape_string($status_ratinglock_disc).'"
                    WHERE
                        c_id = '.$course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                        '.$groupfilter.$condition_session;
             //Visibility. Value to all,not only for the first
            Database::query($sql);

            //Although the value now is assigned to all (not only the first), these three lines remain necessary. They do that by changing the page state is made when you press the button and not have to wait to change his page
            $sql='SELECT * FROM '.$tbl_wiki.'
                  WHERE
                    c_id = '.$course_id.' AND
                    reflink="'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                  ORDER BY id ASC';
            $result=Database::query($sql);
            $row=Database::fetch_array($result);
        }

        return $row['ratinglock_disc'];
    }

    /**
     * Notify page changes
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     * @return int the current notification status
     */
    public function check_notify_page($reflink)
    {
        $tbl_wiki = $this->tbl_wiki;
        $tbl_wiki_mailcue = $this->tbl_wiki_mailcue;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $groupId = api_get_group_id();
        $session_id = api_get_session_id();
        $course_id = api_get_course_int_id();
        $userId = api_get_user_id();

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND reflink="'.$reflink.'" AND '.$groupfilter.$condition_session.' ORDER BY id ASC';
        $result=Database::query($sql);
        $row=Database::fetch_array($result);
        $id = $row['id'];
        $sql='SELECT * FROM '.$tbl_wiki_mailcue.'
              WHERE c_id = '.$course_id.' AND id="'.$id.'" AND user_id="'.api_get_user_id().'" AND type="P"';
        $result=Database::query($sql);
        $row=Database::fetch_array($result);

        $idm = $row['id'];

        if (empty($idm)) {
            $status_notify=0;
        } else {
            $status_notify=1;
        }

        // Change status
        if (isset($_GET['actionpage']) && $_GET['actionpage'] =='locknotify' && $status_notify==0) {
            $sql = "SELECT id FROM $tbl_wiki_mailcue
                    WHERE c_id = $course_id AND id = $id AND user_id = $userId";
            $result = Database::query($sql);
            $exist = false;
            if (Database::num_rows($result)) {
                $exist = true;
            }
            if ($exist == false) {
                $sql="INSERT INTO ".$tbl_wiki_mailcue." (c_id, id, user_id, type, group_id, session_id) VALUES
                ($course_id, '".$id."','".api_get_user_id()."','P','".$groupId."','".$session_id."')";
                Database::query($sql);
            }
            $status_notify=1;
        }

        if (isset($_GET['actionpage']) && $_GET['actionpage'] =='unlocknotify' && $status_notify==1) {
            $sql = 'DELETE FROM '.$tbl_wiki_mailcue.'
                    WHERE id="'.$id.'" AND user_id="'.api_get_user_id().'" AND type="P" AND c_id = '.$course_id;
            Database::query($sql);
            $status_notify=0;
        }

        return $status_notify;
    }

    /**
     * Notify discussion changes
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     * @param string $reflink
     * @return int current database status of rating discuss and change it if get action
     */
    public function check_notify_discuss($reflink)
    {
        $tbl_wiki_mailcue = $this->tbl_wiki_mailcue;
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;

        $course_id = api_get_course_int_id();
        $groupId = api_get_group_id();
        $session_id = api_get_session_id();

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND reflink="'.$reflink.'" AND '.$groupfilter.$condition_session.'
                ORDER BY id ASC';
        $result=Database::query($sql);
        $row=Database::fetch_array($result);
        $id=$row['id'];
        $sql = 'SELECT * FROM '.$tbl_wiki_mailcue.'
             WHERE c_id = '.$course_id.' AND id="'.$id.'" AND user_id="'.api_get_user_id().'" AND type="D"';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $idm = $row['id'];

        if (empty($idm)) {
            $status_notify_disc=0;
        } else {
            $status_notify_disc=1;
        }

        //change status
        if (isset($_GET['actionpage']) && $_GET['actionpage'] =='locknotifydisc' && $status_notify_disc==0) {
            $sql="INSERT INTO ".$tbl_wiki_mailcue." (c_id, id, user_id, type, group_id, session_id) VALUES
            ($course_id, '".$id."','".api_get_user_id()."','D','".$groupId."','".$session_id."')";
            Database::query($sql);
            $status_notify_disc=1;
        }
        if (isset($_GET['actionpage']) && $_GET['actionpage'] =='unlocknotifydisc' && $status_notify_disc==1) {
            $sql = 'DELETE FROM '.$tbl_wiki_mailcue.'
                    WHERE c_id = '.$course_id.' AND id="'.$id.'" AND user_id="'.api_get_user_id().'" AND type="D" AND c_id = '.$course_id;
            Database::query($sql);
            $status_notify_disc=0;
        }

        return $status_notify_disc;
    }

    /**
     * Notify all changes
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     */
    public function check_notify_all()
    {
        $tbl_wiki_mailcue = $this->tbl_wiki_mailcue;
        $course_id = api_get_course_int_id();
        $groupId = api_get_group_id();
        $session_id=api_get_session_id();

        $sql = 'SELECT * FROM '.$tbl_wiki_mailcue.'
                WHERE
                    c_id = '.$course_id.' AND
                    user_id="'.api_get_user_id().'" AND
                    type="F" AND
                    group_id="'.$groupId.'" AND
                    session_id="'.$session_id.'"';
        $result=Database::query($sql);
        $row=Database::fetch_array($result);

        $idm=$row['user_id'];

        if (empty($idm)) {
            $status_notify_all=0;
        } else {
            $status_notify_all=1;
        }

        //change status
        if (isset($_GET['actionpage']) && $_GET['actionpage'] =='locknotifyall' && $status_notify_all==0) {
            $sql="INSERT INTO ".$tbl_wiki_mailcue." (c_id, user_id, type, group_id, session_id) VALUES
            ($course_id, '".api_get_user_id()."','F','".$groupId."','".$session_id."')";
            Database::query($sql);
            $status_notify_all=1;
        }

        if (isset($_GET['actionpage']) && isset($_GET['actionpage']) && $_GET['actionpage']  =='unlocknotifyall' && $status_notify_all==1) {
            $sql ='DELETE FROM '.$tbl_wiki_mailcue.'
                   WHERE
                    c_id = '.$course_id.' AND
                    user_id="'.api_get_user_id().'" AND
                    type="F" AND
                    group_id="'.$groupId.'" AND
                    session_id="'.$session_id.'" AND
                    c_id = '.$course_id;
            Database::query($sql);
            $status_notify_all=0;
        }

        //show status
        return $status_notify_all;
    }

    /**
     * Sends pending e-mails
     */
    public function check_emailcue($id_or_ref, $type, $lastime='', $lastuser='')
    {
        $tbl_wiki_mailcue = $this->tbl_wiki_mailcue;
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $_course = $this->courseInfo;
        $groupId = api_get_group_id();
        $session_id=api_get_session_id();
        $course_id = api_get_course_int_id();

        $group_properties  = GroupManager :: get_group_properties($groupId);
        $group_name = $group_properties['name'];
        $allow_send_mail = false; //define the variable to below
        $email_assignment = null;
        if ($type=='P') {
            //if modifying a wiki page
            //first, current author and time
            //Who is the author?
            $userinfo = api_get_user_info($lastuser);
            $email_user_author = get_lang('EditedBy').': '.$userinfo['complete_name'];

            //When ?
            $year = substr($lastime, 0, 4);
            $month = substr($lastime, 5, 2);
            $day = substr($lastime, 8, 2);
            $hours=substr($lastime, 11,2);
            $minutes=substr($lastime, 14,2);
            $seconds=substr($lastime, 17,2);
            $email_date_changes=$day.' '.$month.' '.$year.' '.$hours.":".$minutes.":".$seconds;

            //second, extract data from first reg
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE  c_id = '.$course_id.' AND reflink="'.$id_or_ref.'" AND '.$groupfilter.$condition_session.'
                    ORDER BY id ASC';
            $result=Database::query($sql);
            $row=Database::fetch_array($result);

            $id=$row['id'];
            $email_page_name=$row['title'];
            if ($row['visibility']==1) {
                $allow_send_mail=true; //if visibility off - notify off
                $sql = 'SELECT * FROM '.$tbl_wiki_mailcue.'
                        WHERE
                            c_id = '.$course_id.' AND
                            id="'.$id.'" AND
                            type="'.$type.'" OR
                            type="F" AND
                            group_id="'.$groupId.'" AND
                            session_id="'.$session_id.'"';
                //type: P=page, D=discuss, F=full.
                $result=Database::query($sql);
                $emailtext=get_lang('EmailWikipageModified').' <strong>'.$email_page_name.'</strong> '.get_lang('Wiki');
            }
        } elseif ($type=='D') {
            //if added a post to discuss

            //first, current author and time
            //Who is the author of last message?
            $userinfo = api_get_user_info($lastuser);
            $email_user_author = get_lang('AddedBy').': '.$userinfo['complete_name'];

            //When ?
            $year = substr($lastime, 0, 4);
            $month = substr($lastime, 5, 2);
            $day = substr($lastime, 8, 2);
            $hours=substr($lastime, 11,2);
            $minutes=substr($lastime, 14,2);
            $seconds=substr($lastime, 17,2);
            $email_date_changes=$day.' '.$month.' '.$year.' '.$hours.":".$minutes.":".$seconds;

            //second, extract data from first reg

            $id=$id_or_ref; //$id_or_ref is id from tblwiki

            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE c_id = '.$course_id.' AND id="'.$id.'"
                    ORDER BY id ASC';

            $result=Database::query($sql);
            $row=Database::fetch_array($result);

            $email_page_name=$row['title'];
            if ($row['visibility_disc']==1) {
                $allow_send_mail=true; //if visibility off - notify off
                $sql = 'SELECT * FROM '.$tbl_wiki_mailcue.'
                        WHERE
                            c_id = '.$course_id.' AND
                            id="'.$id.'" AND
                            type="'.$type.'" OR
                            type="F" AND
                            group_id="'.$groupId.'" AND
                            session_id="'.$session_id.'"';
                //type: P=page, D=discuss, F=full
                $result=Database::query($sql);
                $emailtext=get_lang('EmailWikiPageDiscAdded').' <strong>'.$email_page_name.'</strong> '.get_lang('Wiki');
            }
        } elseif($type=='A') {
            //for added pages
            $id=0; //for tbl_wiki_mailcue
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE c_id = '.$course_id.'
                    ORDER BY id DESC'; //the added is always the last

            $result=Database::query($sql);
            $row=Database::fetch_array($result);
            $email_page_name=$row['title'];

            //Who is the author?
            $userinfo = api_get_user_info($row['user_id']);
            $email_user_author= get_lang('AddedBy').': '.$userinfo['complete_name'];

            //When ?
            $year = substr($row['dtime'], 0, 4);
            $month = substr($row['dtime'], 5, 2);
            $day = substr($row['dtime'], 8, 2);
            $hours=substr($row['dtime'], 11,2);
            $minutes=substr($row['dtime'], 14,2);
            $seconds=substr($row['dtime'], 17,2);
            $email_date_changes=$day.' '.$month.' '.$year.' '.$hours.":".$minutes.":".$seconds;

            if($row['assignment']==0) {
                $allow_send_mail=true;
            } elseif($row['assignment']==1) {
                $email_assignment=get_lang('AssignmentDescExtra').' ('.get_lang('AssignmentMode').')';
                $allow_send_mail=true;
            } elseif($row['assignment']==2) {
                $allow_send_mail=false; //Mode tasks: avoids notifications to all users about all users
            }

            $sql = 'SELECT * FROM '.$tbl_wiki_mailcue.'
                    WHERE c_id = '.$course_id.' AND  id="'.$id.'" AND type="F" AND group_id="'.$groupId.'" AND session_id="'.$session_id.'"';
            //type: P=page, D=discuss, F=full
            $result=Database::query($sql);

            $emailtext = get_lang('EmailWikiPageAdded').' <strong>'.$email_page_name.'</strong> '.get_lang('In').' '. get_lang('Wiki');
        } elseif ($type=='E') {
            $id=0;
            $allow_send_mail=true;

            //Who is the author?
            $userinfo = api_get_user_info(api_get_user_id());	//current user
            $email_user_author = get_lang('DeletedBy').': '.$userinfo['complete_name'];
            //When ?
            $today = date('r');		//current time
            $email_date_changes=$today;

            $sql = 'SELECT * FROM '.$tbl_wiki_mailcue.'
                    WHERE
                        c_id = '.$course_id.' AND
                        id="'.$id.'" AND type="F" AND
                        group_id="'.$groupId.'" AND
                        session_id="'.$session_id.'"'; //type: P=page, D=discuss, F=wiki
            $result = Database::query($sql);
            $emailtext = get_lang('EmailWikipageDedeleted');
        }
        ///make and send email
        if ($allow_send_mail) {
            while ($row = Database::fetch_array($result)) {
                $userinfo = api_get_user_info($row['user_id']);	//$row['user_id'] obtained from tbl_wiki_mailcue
                $name_to = $userinfo['complete_name'];
                $email_to = $userinfo['email'];
                $sender_name = api_get_setting('emailAdministrator');
                $sender_email = api_get_setting('emailAdministrator');
                $email_subject = get_lang('EmailWikiChanges').' - '.$_course['official_code'];
                $email_body = get_lang('DearUser').' '.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).',<br /><br />';
                if($session_id==0){
                    $email_body .= $emailtext.' <strong>'.$_course['name'].' - '.$group_name.'</strong><br /><br /><br />';
                }else{
                    $email_body .= $emailtext.' <strong>'.$_course['name'].' ('.api_get_session_name(api_get_session_id()).') - '.$group_name.'</strong><br /><br /><br />';
                }
                $email_body .= $email_user_author.' ('.$email_date_changes.')<br /><br /><br />';
                $email_body .= $email_assignment.'<br /><br /><br />';
                $email_body .= '<font size="-2">'.get_lang('EmailWikiChangesExt_1').': <strong>'.get_lang('NotifyChanges').'</strong><br />';
                $email_body .= get_lang('EmailWikiChangesExt_2').': <strong>'.get_lang('NotNotifyChanges').'</strong></font><br />';
                @api_mail_html($name_to, $email_to, $email_subject, $email_body, $sender_name, $sender_email);
            }
        }
    }

    /**
     * Function export last wiki page version to document area
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     */
    public function export2doc($doc_id)
    {
        $_course = $this->courseInfo;
        $groupId = api_get_group_id();
        $data = self::get_wiki_data($doc_id);

        if (empty($data)) {
            return false;
        }
        $wikiTitle = $data['title'];
        $wikiContents = $data['content'];

        $template =
            '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{LANGUAGE}" lang="{LANGUAGE}">
            <head>
            <title>{TITLE}</title>
            <meta http-equiv="Content-Type" content="text/html; charset={ENCODING}" />
            <style type="text/css" media="screen, projection">
            /*<![CDATA[*/
            {CSS}
            /*]]>*/
            </style>
            {ASCIIMATHML_SCRIPT}</head>
            <body dir="{TEXT_DIRECTION}">
            {CONTENT}
            </body>
            </html>';

        $css_file = api_get_path(TO_SYS, WEB_CSS_PATH).api_get_setting('stylesheets').'/default.css';
        if (file_exists($css_file)) {
            $css = @file_get_contents($css_file);
        } else {
            $css = '';
        }
        // Fixing some bugs in css files.
        $root_rel = api_get_path(REL_PATH);
        $css_path = 'main/css/';
        $theme = api_get_setting('stylesheets').'/';
        $css = str_replace('behavior:url("/main/css/csshover3.htc");', '', $css);
        $css = str_replace('main/', $root_rel.'main/', $css);
        $css = str_replace('images/', $root_rel.$css_path.$theme.'images/', $css);
        $css = str_replace('../../img/', $root_rel.'main/img/', $css);

        $asciimathmal_script = (api_contains_asciimathml($wikiContents) || api_contains_asciisvg($wikiContents))
            ? '<script src="'.api_get_path(TO_REL, SCRIPT_ASCIIMATHML).'" type="text/javascript"></script>'."\n" : '';

        $template = str_replace(array('{LANGUAGE}', '{ENCODING}', '{TEXT_DIRECTION}', '{TITLE}', '{CSS}', '{ASCIIMATHML_SCRIPT}'),
            array(api_get_language_isocode(), api_get_system_encoding(), api_get_text_direction(), $wikiTitle, $css, $asciimathmal_script),
            $template);

        if (0 != $groupId) {
            $groupPart = '_group' . $groupId; // and add groupId to put the same document title in different groups
            $group_properties  = GroupManager :: get_group_properties($groupId);
            $groupPath = $group_properties['directory'];
        } else {
            $groupPart = '';
            $groupPath ='';
        }

        $exportDir = api_get_path(SYS_COURSE_PATH).api_get_course_path(). '/document'.$groupPath;
        $exportFile = replace_dangerous_char($wikiTitle, 'strict') . $groupPart;

        //$clean_wikiContents = trim(preg_replace("/\[\[|\]\]/", " ", $wikiContents));
        //$array_clean_wikiContents= explode('|', $clean_wikiContents);
        $wikiContents = trim(preg_replace("/\[[\[]?([^\]|]*)[|]?([^|\]]*)\][\]]?/", "$1", $wikiContents));
        //TODO: put link instead of title

        $wikiContents = str_replace('{CONTENT}', $wikiContents, $template);

        // replace relative path by absolute path for courses, so you can see items into this page wiki (images, mp3, etc..) exported in documents
        if (api_strpos($wikiContents,'../../courses/') !== false) {
            $web_course_path = api_get_path(WEB_COURSE_PATH);
            $wikiContents = str_replace('../../courses/',$web_course_path,$wikiContents);
        }

        $i = 1;
        while ( file_exists($exportDir . '/' .$exportFile.'_'.$i.'.html') )
            $i++; //only export last version, but in new export new version in document area
        $wikiFileName = $exportFile . '_' . $i . '.html';
        $exportPath = $exportDir . '/' . $wikiFileName;
        file_put_contents( $exportPath, $wikiContents );
        require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
        $doc_id = add_document($_course, $groupPath.'/'.$wikiFileName, 'file', filesize($exportPath), $wikiTitle);
        api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', api_get_user_id(), $groupId);

        return $doc_id;
        // TODO: link to go document area
    }

    /**
     * Exports the wiki page to PDF
     */
    public function export_to_pdf($id, $course_code)
    {
        if (!api_is_platform_admin()) {
            if (api_get_setting('students_export2pdf') == 'true') {
                return false;
            }
        }

        require_once api_get_path(LIBRARY_PATH).'pdf.lib.php';
        $data        = self::get_wiki_data($id);
        $content_pdf = api_html_entity_decode($data['content'], ENT_QUOTES, api_get_system_encoding());

        //clean wiki links
        $content_pdf=trim(preg_replace("/\[[\[]?([^\]|]*)[|]?([^|\]]*)\][\]]?/", "$1", $content_pdf));
        //TODO: It should be better to display the link insted of the tile but it is hard for [[title]] links

        $title_pdf = api_html_entity_decode($data['title'], ENT_QUOTES, api_get_system_encoding());
        $title_pdf = api_utf8_encode($title_pdf, api_get_system_encoding());
        $content_pdf = api_utf8_encode($content_pdf, api_get_system_encoding());

        $html='
        <!-- defines the headers/footers - this must occur before the headers/footers are set -->

        <!--mpdf
        <pageheader name="odds" content-left="'.$title_pdf.'"  header-style-left="color: #880000; font-style: italic;"  line="1" />
        <pagefooter name="odds" content-right="{PAGENO}/{nb}" line="1" />

        <!-- set the headers/footers - they will occur from here on in the document -->
        <!--mpdf
        <setpageheader name="odds" page="odd" value="on" show-this-page="1" />
        <setpagefooter name="odds" page="O" value="on" />

        mpdf-->'.$content_pdf;

        $css_file = api_get_path(TO_SYS, WEB_CSS_PATH).api_get_setting('stylesheets').'/print.css';
        if (file_exists($css_file)) {
            $css = @file_get_contents($css_file);
        } else {
            $css = '';
        }
        require_once api_get_path(LIBRARY_PATH).'pdf.lib.php';
        $pdf = new PDF();
        $pdf->content_to_pdf($html, $css, $title_pdf, $course_code);
        exit;
    }

    /**
     * Function prevent double post (reload or F5)
     *
     */
    public function double_post($wpost_id)
    {
        if (isset($_SESSION['wpost_id'])) {
            if ($wpost_id == $_SESSION['wpost_id']) {
                return false;
            } else {
                $_SESSION['wpost_id'] = $wpost_id;
                return true;
            }
        } else {
            $_SESSION['wpost_id'] = $wpost_id;
            return true;
        }
    }

    /**
     * Function wizard individual assignment
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     */
    public function auto_add_page_users($values)
    {
        $assignment_type = $values['assignment'];

        //$assig_user_id is need to identify end reflinks
        $session_id = $this->session_id;
        $groupId = api_get_group_id();

        if ($groupId==0) {
            //extract course members
            if(!empty($session_id)) {
                $a_users_to_add = CourseManager::get_user_list_from_course_code(api_get_course_id(), $session_id);
            } else {
                $a_users_to_add = CourseManager::get_user_list_from_course_code(api_get_course_id(), 0);
            }
        } else {
            //extract group members
            $subscribed_users = GroupManager :: get_subscribed_users($groupId);
            $subscribed_tutors = GroupManager :: get_subscribed_tutors($groupId);
            $a_users_to_add_with_duplicates = array_merge($subscribed_users, $subscribed_tutors);

            //remove duplicates
            $a_users_to_add = $a_users_to_add_with_duplicates;
            //array_walk($a_users_to_add, create_function('&$value,$key', '$value = json_encode($value);'));
            $a_users_to_add = array_unique($a_users_to_add);
            //array_walk($a_users_to_add, create_function('&$value,$key', '$value = json_decode($value, true);'));
        }

        $all_students_pages = array();

        //data about teacher
        $userinfo = api_get_user_info(api_get_user_id());
        $username = api_htmlentities(sprintf(get_lang('LoginX'), $userinfo['username'], ENT_QUOTES));
        $name = $userinfo['complete_name']." - ".$username;
        if (api_get_user_id()<>0) {
            $image_path = UserManager::get_user_picture_path_by_id(api_get_user_id(),'web',false, true);
            $image_repository = $image_path['dir'];
            $existing_image = $image_path['file'];
            $photo= '<img src="'.$image_repository.$existing_image.'" alt="'.$name.'"  width="40" height="50" align="top" title="'.$name.'"  />';
        } else {
            $photo= '<img src="'.api_get_path(WEB_CODE_PATH)."img/unknown.jpg".'" alt="'.$name.'"  width="40" height="50" align="top"  title="'.$name.'"  />';
        }

        //teacher assignment title
        $title_orig = $values['title'];

        //teacher assignment reflink
        $link2teacher = $values['title'] = $title_orig."_uass".api_get_user_id();

        //first: teacher name, photo, and assignment description (original content)
        // $content_orig_A='<div align="center" style="background-color: #F5F8FB;  border:double">'.$photo.'<br />'.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).'<br />('.get_lang('Teacher').')</div><br/><div>';
        $content_orig_A='<div align="center" style="background-color: #F5F8FB; border:solid; border-color: #E6E6E6">
        <table border="0">
            <tr><td style="font-size:24px">'.get_lang('AssignmentDesc').'</td></tr>
            <tr><td>'.$photo.'<br />'.Display::tag('span', api_get_person_name($userinfo['firstname'], $userinfo['lastname']), array('title'=>$username)).'</td></tr>
        </table></div>';
        $content_orig_B='<br/><div align="center" style="font-size:24px">'.get_lang('AssignmentDescription').': '.$title_orig.'</div><br/>'.$_POST['content'];

        //Second: student list (names, photo and links to their works).
        //Third: Create Students work pages.
        foreach ($a_users_to_add as $o_user_to_add) {
            if ($o_user_to_add['user_id'] != api_get_user_id()) {
                //except that puts the task
                $assig_user_id = $o_user_to_add['user_id']; //identifies each page as created by the student, not by teacher
                $image_path = UserManager::get_user_picture_path_by_id($assig_user_id,'web',false, true);
                $image_repository = $image_path['dir'];
                $existing_image = $image_path['file'];
                $username = api_htmlentities(sprintf(get_lang('LoginX'), $o_user_to_add['username'], ENT_QUOTES));
                $name = api_get_person_name($o_user_to_add['firstname'], $o_user_to_add['lastname'])." . ".$username;
                $photo= '<img src="'.$image_repository.$existing_image.'" alt="'.$name.'"  width="40" height="50" align="bottom" title="'.$name.'"  />';

                $is_tutor_of_group = GroupManager::is_tutor_of_group($assig_user_id,$groupId); //student is tutor
                $is_tutor_and_member = (GroupManager::is_tutor_of_group($assig_user_id,$groupId) && GroupManager::is_subscribed($assig_user_id, $groupId));
                //student is tutor and member

                if($is_tutor_and_member) {
                    $status_in_group=get_lang('GroupTutorAndMember');
                } else {
                    if($is_tutor_of_group) {
                        $status_in_group=get_lang('GroupTutor');
                    } else {
                        $status_in_group=" "; //get_lang('GroupStandardMember')
                    }
                }

                if ($assignment_type==1) {
                    $values['title']= $title_orig;
                    //$values['comment'] = get_lang('AssignmentFirstComToStudent');
                    $values['content'] = '<div align="center" style="background-color: #F5F8FB; border:solid; border-color: #E6E6E6">
                    <table border="0">
                    <tr><td style="font-size:24px">'.get_lang('AssignmentWork').'</td></tr>
                    <tr><td>'.$photo.'<br />'.$name.'</td></tr></table>
                    </div>[['.$link2teacher.' | '.get_lang('AssignmentLinktoTeacherPage').']] ';
                    //If $content_orig_B is added here, the task written by the professor was copied to the page of each student. TODO: config options

                    // AssignmentLinktoTeacherPage
                    $all_students_pages[] = '<li>'.
                        Display::tag(
                            'span',
                            strtoupper($o_user_to_add['lastname']).', '.$o_user_to_add['firstname'], array('title'=>$username)
                        ).
                        ' [['.$_POST['title']."_uass".$assig_user_id.' | '.$photo.']] '.$status_in_group.'</li>';
                    //don't change this line without guaranteeing that users will be ordered by last names in the following format (surname, name)
                    $values['assignment']=2;
                }
                $this->assig_user_id = $assig_user_id;
                self::save_new_wiki($values);
            }
        }

        foreach ($a_users_to_add as $o_user_to_add) {
            if ($o_user_to_add['user_id'] == api_get_user_id()) {
                $assig_user_id=$o_user_to_add['user_id'];
                if ($assignment_type == 1) {
                    $values['title']= $title_orig;
                    $values['comment']=get_lang('AssignmentDesc');
                    sort($all_students_pages);
                    $values['content']=$content_orig_A.$content_orig_B.'<br/>
                    <div align="center" style="font-size:18px; background-color: #F5F8FB; border:solid; border-color:#E6E6E6">
                    '.get_lang('AssignmentLinkstoStudentsPage').'
                    </div><br/>
                    <div style="background-color: #F5F8FB; border:solid; border-color:#E6E6E6">
                    <ol>'.implode($all_students_pages).'</ol>
                    </div>
                    <br/>';
                    $values['assignment']=1;
                }
                $this->assig_user_id = $assig_user_id;
                self::save_new_wiki($values);
            }
        }
    }

    /**
     * Displays the results of a wiki search
     * @param   string  Search term
     * @param   int     Whether to search the contents (1) or just the titles (0)
     * @param int
     */
    public function display_wiki_search_results($search_term, $search_content=0, $all_vers=0)
    {
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $_course = $this->courseInfo;

        echo '<legend>'.get_lang('WikiSearchResults').'</legend>';
        $course_id = api_get_course_int_id();

        //only by professors when page is hidden
        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
            if ($all_vers=='1') {
                if ($search_content=='1') {
                    $sql = "SELECT * FROM ".$tbl_wiki."
                            WHERE
                                c_id = $course_id AND
                                title LIKE '%".Database::escape_string($search_term)."%' OR
                                content LIKE '%".Database::escape_string($search_term)."%' AND
                                ".$groupfilter.$condition_session."";
                    //search all pages and all versions
                } else {
                    $sql = "SELECT * FROM ".$tbl_wiki."
                            WHERE
                                c_id = $course_id AND
                                title LIKE '%".Database::escape_string($search_term)."%' AND
                                ".$groupfilter.$condition_session."";
                    //search all pages and all versions
                }
            } else {
                if ($search_content=='1') {
                    $sql = "SELECT * FROM ".$tbl_wiki." s1
                            WHERE
                                s1.c_id = $course_id AND
                                title LIKE '%".Database::escape_string($search_term)."%' OR
                                content LIKE '%".Database::escape_string($search_term)."%' AND
                                id=(
                                    SELECT MAX(s2.id)
                                    FROM ".$tbl_wiki." s2
                                    WHERE
                                        s2.c_id = $course_id AND
                                        s1.reflink = s2.reflink AND
                                        ".$groupfilter.$condition_session.")";
                    // warning don't use group by reflink because don't return the last version
                }
                else {
                    $sql = " SELECT * FROM ".$tbl_wiki." s1
                            WHERE
                                s1.c_id = $course_id AND
                                title LIKE '%".Database::escape_string($search_term)."%' AND
                                id=(
                                    SELECT MAX(s2.id)
                                    FROM ".$tbl_wiki." s2
                                    WHERE
                                        s2.c_id = $course_id AND
                                        s1.reflink = s2.reflink AND
                                        ".$groupfilter.$condition_session.")";
                    // warning don't use group by reflink because don't return the last version
                }
            }
        } else {
            if($all_vers=='1') {
                if ($search_content=='1') {
                    $sql = "SELECT * FROM ".$tbl_wiki."
                            WHERE
                                c_id = $course_id AND
                                visibility=1 AND
                                title LIKE '%".Database::escape_string($search_term)."%' OR
                                content LIKE '%".Database::escape_string($search_term)."%' AND
                                ".$groupfilter.$condition_session."";
                    //search all pages and all versions
                } else {
                    $sql = "SELECT * FROM ".$tbl_wiki."
                            WHERE
                                c_id = $course_id AND
                                visibility=1 AND
                                title LIKE '%".Database::escape_string($search_term)."%' AND
                                ".$groupfilter.$condition_session."";
                    //search all pages and all versions
                }
            } else {
                if($search_content=='1') {
                    $sql = "SELECT * FROM ".$tbl_wiki." s1
                            WHERE
                                s1.c_id = $course_id AND
                                visibility=1 AND
                                title LIKE '%".Database::escape_string($search_term)."%' OR
                                content LIKE '%".Database::escape_string($search_term)."%' AND
                                id=(
                                    SELECT MAX(s2.id)
                                    FROM ".$tbl_wiki." s2
                                    WHERE s2.c_id = $course_id AND
                                    s1.reflink = s2.reflink AND
                                    ".$groupfilter.$condition_session.")";
                    // warning don't use group by reflink because don't return the last version
                } else {
                    $sql = "SELECT * FROM ".$tbl_wiki." s1
                            WHERE
                                s1.c_id = $course_id AND
                                visibility=1 AND
                                title LIKE '%".Database::escape_string($search_term)."%' AND
                            id=(
                                SELECT MAX(s2.id) FROM ".$tbl_wiki." s2
                                WHERE s2.c_id = $course_id AND
                                s1.reflink = s2.reflink AND
                                ".$groupfilter.$condition_session.")";
                    // warning don't use group by reflink because don't return the last version
                }
            }
        }

        $result = Database::query($sql);

        //show table
        $rows = array();
        if (Database::num_rows($result) > 0) {
            while ($obj = Database::fetch_object($result)) {
                //get author
                $userinfo = api_get_user_info($obj->user_id);

                //get time
                $year 	 = substr($obj->dtime, 0, 4);
                $month	 = substr($obj->dtime, 5, 2);
                $day 	 = substr($obj->dtime, 8, 2);
                $hours   = substr($obj->dtime, 11,2);
                $minutes = substr($obj->dtime, 14,2);
                $seconds = substr($obj->dtime, 17,2);

                //get type assignment icon
                if($obj->assignment==1) {
                    $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',ICON_SIZE_SMALL);
                } elseif ($obj->assignment==2) {
                    $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',ICON_SIZE_SMALL);
                } elseif ($obj->assignment==0) {
                    $ShowAssignment= Display::return_icon('px_transparent.gif');
                }
                $row = array();
                $row[] =$ShowAssignment;

                if($all_vers=='1') {
                    $row[] = '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&view='.$obj->id.'&session_id='.api_htmlentities(urlencode($_GET['$session_id'])).'&group_id='.api_htmlentities(urlencode($_GET['group_id'])).'">'.
                        api_htmlentities($obj->title).'</a>';
                } else {
                    $row[] = '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                        $obj->title.'</a>';
                }

                $row[] = $obj->user_id <>0 ? '<a href="'.api_get_path(WEB_CODE_PATH).'user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.
                    api_htmlentities($userinfo['complete_name']).'</a>' : get_lang('Anonymous').' ('.$obj->user_ip.')';
                $row[] = $year.'-'.$month.'-'.$day.' '.$hours.":".$minutes.":".$seconds;

                if ($all_vers=='1') {
                    $row[] = $obj->version;
                } else {
                    if (api_is_allowed_to_edit(false,true)|| api_is_platform_admin()) {
                        $showdelete=' <a href="'.api_get_self().'?'.api_get_cidreq().'&action=delete&title='.api_htmlentities(urlencode($obj->reflink)).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                            Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL);
                    }
                    $row[] = '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=edit&title='.api_htmlentities(urlencode($obj->reflink)).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                        Display::return_icon('edit.png', get_lang('EditPage'),'',ICON_SIZE_SMALL).'</a>
                        <a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=discuss&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                        Display::return_icon('discuss.png', get_lang('Discuss'),'',ICON_SIZE_SMALL).'</a>
                        <a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=history&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                        Display::return_icon('history.png', get_lang('History'),'',ICON_SIZE_SMALL).'</a> <a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=links&title='.api_htmlentities(urlencode($obj->reflink)).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                        Display::return_icon('what_link_here.png', get_lang('LinksPages'),'',ICON_SIZE_SMALL).'</a>'.$showdelete;
                }
                $rows[] = $row;
            }

            $table = new SortableTableFromArrayConfig($rows, 1, 10,'SearchPages_table','','','ASC');
            $table->set_additional_parameters(
                array(
                    'cidReq' => $_GET['cidReq'],
                    'action'=> $_GET['action'],
                    'group_id'=>Security::remove_XSS($_GET['group_id']),
                    'mode_table'=>'yes2',
                    'search_term'=>$search_term,
                    'search_content'=>$search_content,
                    'all_vers'=>$all_vers
                )
            );
            $table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
            $table->set_header(1,get_lang('Title'), true);
            if ($all_vers=='1') {
                $table->set_header(2,get_lang('Author'), true);
                $table->set_header(3,get_lang('Date'), true);
                $table->set_header(4,get_lang('Version'), true);
            } else {
                $table->set_header(2,get_lang('Author').' ('.get_lang('LastVersion').')', true);
                $table->set_header(3,get_lang('Date').' ('.get_lang('LastVersion').')', true);
                $table->set_header(4,get_lang('Actions'), false, array ('style' => 'width:130px;'));
            }
            $table->display();
        } else {
            echo get_lang('NoSearchResults');
        }
    }

    /**
     * Returns a date picker
     * @todo replace this function with the formvalidator datepicker
     *
     */
    public function draw_date_picker($prefix,$default='')
    {
        if (empty($default)) {
            $default = date('Y-m-d H:i:s');
        }
        $parts = explode(' ', $default);
        list($d_year,$d_month,$d_day) = explode('-',$parts[0]);
        list($d_hour,$d_minute) = explode(':',$parts[1]);

        $month_list = array(
            1=>get_lang('JanuaryLong'),
            2=>get_lang('FebruaryLong'),
            3=>get_lang('MarchLong'),
            4=>get_lang('AprilLong'),
            5=>get_lang('MayLong'),
            6=>get_lang('JuneLong'),
            7=>get_lang('JulyLong'),
            8=>get_lang('AugustLong'),
            9=>get_lang('SeptemberLong'),
            10=>get_lang('OctoberLong'),
            11=>get_lang('NovemberLong'),
            12=>get_lang('DecemberLong')
        );

        $minute = range(10,59);
        array_unshift($minute,'00','01','02','03','04','05','06','07','08','09');
        $date_form = self::make_select($prefix.'_day', array_combine(range(1,31),range(1,31)), $d_day);
        $date_form .= self::make_select($prefix.'_month', $month_list, $d_month);
        $date_form .= self::make_select($prefix.'_year', array($d_year-2=>$d_year-2, $d_year-1=>$d_year-1, $d_year=> $d_year, $d_year+1=>$d_year+1, $d_year+2=>$d_year+2), $d_year).'&nbsp;&nbsp;&nbsp;&nbsp;';
        $date_form .= self::make_select($prefix.'_hour', array_combine(range(0,23),range(0,23)), $d_hour).' : ';
        $date_form .= self::make_select($prefix.'_minute', $minute, $d_minute);

        return $date_form;
    }

    /**
     * Draws an HTML form select with the given options
     *
     */
    public function make_select($name,$values,$checked='')
    {
        $output = '<select name="'.$name.'" id="'.$name.'">';
        foreach($values as $key => $value) {
            $output .= '<option value="'.$key.'" '.(($checked==$key)?'selected="selected"':'').'>'.$value.'</option>';
        }
        $output .= '</select>';
        return $output;
    }

    /**
     * Translates a form date into a more usable format
     *
     */
    public function get_date_from_select($prefix)
    {
        return $_POST[$prefix.'_year'].'-'.
        self::two_digits($_POST[$prefix.'_month']).'-'.
        self::two_digits($_POST[$prefix.'_day']).' '.
        self::two_digits($_POST[$prefix.'_hour']).':'.
        self::two_digits($_POST[$prefix.'_minute']).':00';
    }

    /**
     * Converts 1-9 to 01-09
     */
    public function two_digits($number)
    {
        $number = (int)$number;
        return ($number < 10) ? '0'.$number : $number;
    }

    /**
     * Get wiki information
     * @param   int     wiki id
     * @return  array   wiki data
     */
    public function get_wiki_data($id)
    {
        $tbl_wiki = $this->tbl_wiki;
        $course_id = api_get_course_int_id();
        $id = intval($id);
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND id = '.$id.' ';
        $result=Database::query($sql);
        $data = array();
        while ($row=Database::fetch_array($result,'ASSOC'))   {
            $data = $row;
        }
        return $data;
    }

    /**
     * @param string $refLink
     * @return array
     */
    public function getLastWikiData($refLink)
    {
        $tbl_wiki = $this->tbl_wiki;
        $groupfilter = $this->groupfilter;
        $condition_session = $this->condition_session;
        $course_id = api_get_course_int_id();

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$course_id.' AND
                    reflink="'.Database::escape_string($refLink).'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id DESC';

        $result = Database::query($sql);
        return Database::fetch_array($result);
    }

    /**
     * Get wiki information
     * @param   string     wiki id
     * @param int $courseId
     * @return  array   wiki data
     */
    public function getPageByTitle($title, $courseId = null)
    {
        $tbl_wiki = $this->tbl_wiki;
        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        } else {
            $courseId = intval($courseId);
        }

        if (empty($title) || empty($courseId)) {
            return array();
        }

        $title = Database::escape_string($title);
        $sql = "SELECT * FROM $tbl_wiki
                WHERE c_id = $courseId AND reflink = '$title'";
        $result = Database::query($sql);
        $data = array();
        if (Database::num_rows($result)) {
            $data = Database::fetch_array($result,'ASSOC');
        }
        return $data;
    }

    /**
     * @param string $title
     * @param int $courseId
     * @param string
     * @param string
     * @return bool
     */
    public function deletePage($title, $courseId, $groupfilter = null, $condition_session = null)
    {
        $tbl_wiki = $this->tbl_wiki;
        $tbl_wiki_discuss = $this->tbl_wiki_discuss;
        $tbl_wiki_mailcue = $this->tbl_wiki_mailcue;
        $tbl_wiki_conf = $this->tbl_wiki_conf;

        $pageInfo = self::getPageByTitle($title, $courseId);
        if (!empty($pageInfo)) {
            $pageId = $pageInfo['id'];
            $sql = "DELETE FROM $tbl_wiki_conf
                    WHERE c_id = $courseId AND page_id = $pageId";
            Database::query($sql);

            $sql = 'DELETE FROM '.$tbl_wiki_discuss.'
                    WHERE c_id = '.$courseId.' AND publication_id = '.$pageId;
            Database::query($sql);

            $sql = 'DELETE FROM  '.$tbl_wiki_mailcue.'
                    WHERE c_id = '.$courseId.' AND id = '.$pageId.' AND '.$groupfilter.$condition_session.'';
            Database::query($sql);

            $sql = 'DELETE FROM '.$tbl_wiki.'
                    WHERE c_id = '.$courseId.' AND id = '.$pageId.' AND '.$groupfilter.$condition_session.'';
            Database::query($sql);
            self::check_emailcue(0, 'E');
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getAllWiki()
    {
        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $condition_session = $this->condition_session;

        $sql = "SELECT * FROM $tbl_wiki
                WHERE
                    c_id = $course_id AND
                    is_editing != '0' ".$condition_session;
        $result = Database::query($sql);
        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param int $isEditing
     */
    public function updateWikiIsEditing($isEditing)
    {
        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $condition_session = $this->condition_session;
        $isEditing = Database::escape_string($isEditing);

        $sql = 'UPDATE '.$tbl_wiki.' SET
                is_editing="0",
                time_edit="0000-00-00 00:00:00"
                WHERE
                    c_id = '.$course_id.' AND
                    is_editing="'.$isEditing.'" '.
                    $condition_session;
        Database::query($sql);
    }

    /**
     * Release of blocked pages to prevent concurrent editions
     * @param int $userId
     * @param string $action
     */
    public function blockConcurrentEditions($userId, $action = null)
    {
        $result = self::getAllWiki();
        if (!empty($result)) {
            foreach ($result  as $is_editing_block) {
                $max_edit_time	= 1200; // 20 minutes
                $timestamp_edit	= strtotime($is_editing_block['time_edit']);
                $time_editing	= time()-$timestamp_edit;

                //first prevent concurrent users and double version
                if ($is_editing_block['is_editing'] == $userId) {
                    $_SESSION['_version'] = $is_editing_block['version'];
                } else {
                    unset($_SESSION['_version']);
                }
                //second checks if has exceeded the time that a page may be available or if a page was edited and saved by its author
                if ($time_editing>$max_edit_time || ($is_editing_block['is_editing']==$userId && $action!='edit')) {
                    self::updateWikiIsEditing($is_editing_block['is_editing']);
                }
            }
        }
    }

    /**
     * Showing wiki stats
     */
    public function getStats()
    {
        if (!api_is_allowed_to_edit(false, true)) {
            return false;
        }

        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $session_id = $this->session_id;
        $tbl_wiki_conf = $this->tbl_wiki_conf;

        echo '<div class="actions">'.get_lang('Statistics').'</div>';

        // Check all versions of all pages
        $total_words 			= 0;
        $total_links 			= 0;
        $total_links_anchors 	= 0;
        $total_links_mail		= 0;
        $total_links_ftp 		= 0;
        $total_links_irc		= 0;
        $total_links_news 		= 0;
        $total_wlinks 			= 0;
        $total_images 			= 0;
        $clean_total_flash 		= 0;
        $total_flash			= 0;
        $total_mp3				= 0;
        $total_flv_p 			= 0;
        $total_flv				= 0;
        $total_youtube			= 0;
        $total_multimedia		= 0;
        $total_tables			= 0;

        $sql = "SELECT *, COUNT(*) AS TOTAL_VERS, SUM(hits) AS TOTAL_VISITS
                FROM ".$tbl_wiki."
                WHERE c_id = $course_id AND ".$groupfilter.$condition_session."";

        $allpages=Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_versions = $row['TOTAL_VERS'];
            $total_visits = intval($row['TOTAL_VISITS']);
        }

        $sql = "SELECT * FROM ".$tbl_wiki."
                WHERE c_id = $course_id AND ".$groupfilter.$condition_session."";
        $allpages = Database::query($sql);

        while ($row=Database::fetch_array($allpages)) {
            $total_words 			= $total_words + self::word_count($row['content']);
            $total_links 			= $total_links+substr_count($row['content'], "href=");
            $total_links_anchors 	= $total_links_anchors+substr_count($row['content'], 'href="#');
            $total_links_mail		= $total_links_mail+substr_count($row['content'], 'href="mailto');
            $total_links_ftp 		= $total_links_ftp+substr_count($row['content'], 'href="ftp');
            $total_links_irc		= $total_links_irc+substr_count($row['content'], 'href="irc');
            $total_links_news 		= $total_links_news+substr_count($row['content'], 'href="news');
            $total_wlinks 			= $total_wlinks+substr_count($row['content'], "[[");
            $total_images 			= $total_images+substr_count($row['content'], "<img");
            $clean_total_flash = preg_replace('/player.swf/', ' ', $row['content']);
            $total_flash			= $total_flash+substr_count($clean_total_flash, '.swf"');
            //.swf" end quotes prevent insert swf through flvplayer (is not counted)
            $total_mp3				= $total_mp3+substr_count($row['content'], ".mp3");
            $total_flv_p = $total_flv_p+substr_count($row['content'], ".flv");
            $total_flv				=	$total_flv_p/5;
            $total_youtube			= $total_youtube+substr_count($row['content'], "http://www.youtube.com");
            $total_multimedia		= $total_multimedia+substr_count($row['content'], "video/x-msvideo");
            $total_tables			= $total_tables+substr_count($row['content'], "<table");
        }

        //check only last version of all pages (current page)

        $sql =' SELECT *, COUNT(*) AS TOTAL_PAGES, SUM(hits) AS TOTAL_VISITS_LV
                FROM  '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$course_id.' AND id=(
                    SELECT MAX(s2.id)
                    FROM '.$tbl_wiki.' s2
                    WHERE
                        s2.c_id = '.$course_id.' AND
                        s1.reflink = s2.reflink AND
                        '.$groupfilter.' AND
                        session_id='.$session_id.')';
        $allpages = Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            $total_pages	 		= $row['TOTAL_PAGES'];
            $total_visits_lv 		= intval($row['TOTAL_VISITS_LV']);
        }

        $total_words_lv			= 0;
        $total_links_lv			= 0;
        $total_links_anchors_lv	= 0;
        $total_links_mail_lv 	= 0;
        $total_links_ftp_lv 	= 0;
        $total_links_irc_lv 	= 0;
        $total_links_news_lv 	= 0;
        $total_wlinks_lv 		= 0;
        $total_images_lv 		= 0;
        $clean_total_flash_lv 	= 0;
        $total_flash_lv			= 0;
        $total_mp3_lv			= 0;
        $total_flv_p_lv		    = 0;
        $total_flv_lv			= 0;
        $total_youtube_lv		= 0;
        $total_multimedia_lv	= 0;
        $total_tables_lv		= 0;

        $sql = 'SELECT * FROM  '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$course_id.' AND id=(
                    SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                    WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.'
                )';
        $allpages = Database::query($sql);

        while ($row=Database::fetch_array($allpages)) {
            $total_words_lv 		= $total_words_lv+ self::word_count($row['content']);
            $total_links_lv 		= $total_links_lv+substr_count($row['content'], "href=");
            $total_links_anchors_lv	= $total_links_anchors_lv+substr_count($row['content'], 'href="#');
            $total_links_mail_lv 	= $total_links_mail_lv+substr_count($row['content'], 'href="mailto');
            $total_links_ftp_lv 	= $total_links_ftp_lv+substr_count($row['content'], 'href="ftp');
            $total_links_irc_lv 	= $total_links_irc_lv+substr_count($row['content'], 'href="irc');
            $total_links_news_lv 	= $total_links_news_lv+substr_count($row['content'], 'href="news');
            $total_wlinks_lv 		= $total_wlinks_lv+substr_count($row['content'], "[[");
            $total_images_lv 		= $total_images_lv+substr_count($row['content'], "<img");
            $clean_total_flash_lv = preg_replace('/player.swf/', ' ', $row['content']);
            $total_flash_lv			= $total_flash_lv+substr_count($clean_total_flash_lv, '.swf"');
            //.swf" end quotes prevent insert swf through flvplayer (is not counted)
            $total_mp3_lv			= $total_mp3_lv+substr_count($row['content'], ".mp3");
            $total_flv_p_lv = $total_flv_p_lv+substr_count($row['content'], ".flv");
            $total_flv_lv			= $total_flv_p_lv/5;
            $total_youtube_lv		= $total_youtube_lv+substr_count($row['content'], "http://www.youtube.com");
            $total_multimedia_lv	= $total_multimedia_lv+substr_count($row['content'], "video/x-msvideo");
            $total_tables_lv		= $total_tables_lv+substr_count($row['content'], "<table");
        }

        //Total pages edited at this time
        $total_editing_now=0;
        $sql = 'SELECT *, COUNT(*) AS TOTAL_EDITING_NOW
                FROM  '.$tbl_wiki.' s1
                WHERE is_editing!=0 AND s1.c_id = '.$course_id.' AND
                id=(
                    SELECT MAX(s2.id)
                    FROM '.$tbl_wiki.' s2
                    WHERE
                        s2.c_id = '.$course_id.' AND
                        s1.reflink = s2.reflink AND
                        '.$groupfilter.' AND
                        session_id='.$session_id.'
        )';

        // Can not use group by because the mark is set in the latest version
        $allpages=Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            $total_editing_now	= $row['TOTAL_EDITING_NOW'];
        }

        // Total hidden pages
        $total_hidden=0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE  c_id = '.$course_id.' AND visibility=0 AND '.$groupfilter.$condition_session.'
                GROUP BY reflink';
        // or group by page_id. As the mark of hidden places it in all versions of the page, I can use group by to see the first
        $allpages=Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            $total_hidden = $total_hidden+1;
        }

        //Total protect pages
        $total_protected=0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE  c_id = '.$course_id.' AND editlock=1 AND '.$groupfilter.$condition_session.'
                GROUP BY reflink';
        // or group by page_id. As the mark of protected page is the first version of the page, I can use group by

        $allpages=Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            $total_protected = $total_protected+1;
        }

        // Total empty versions.
        $total_empty_content=0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$course_id.' AND
                    content="" AND
                    '.$groupfilter.$condition_session.'';
        $allpages = Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            $total_empty_content	= $total_empty_content+1;
        }

        //Total empty pages (last version)

        $total_empty_content_lv=0;
        $sql = 'SELECT  * FROM  '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$course_id.' AND content="" AND id=(
                SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                WHERE s1.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')';
        $allpages=Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_empty_content_lv	= $total_empty_content_lv+1;
        }

        // Total locked discuss pages
        $total_lock_disc=0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND addlock_disc=0 AND '.$groupfilter.$condition_session.'
                GROUP BY reflink';//group by because mark lock in all vers, then always is ok
        $allpages=Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_lock_disc	= $total_lock_disc+1;
        }

        // Total hidden discuss pages.
        $total_hidden_disc = 0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND visibility_disc=0 AND '.$groupfilter.$condition_session.'
                GROUP BY reflink';
        //group by because mark lock in all vers, then always is ok
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_hidden_disc	= $total_hidden_disc+1;
        }

        //Total versions with any short comment by user or system

        $total_comment_version = 0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND comment!="" AND '.$groupfilter.$condition_session.'';
        $allpages=Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_comment_version	= $total_comment_version+1;
        }

        // Total pages that can only be scored by teachers.

        $total_only_teachers_rating=0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND
                ratinglock_disc = 0 AND
                '.$groupfilter.$condition_session.'
                GROUP BY reflink';//group by because mark lock in all vers, then always is ok
        $allpages=Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            $total_only_teachers_rating	= $total_only_teachers_rating+1;
        }

        // Total pages scored by peers
        // put always this line alfter check num all pages and num pages rated by teachers
        $total_rating_by_peers=$total_pages-$total_only_teachers_rating;

        //Total pages identified as standard task

        $total_task=0;
        $sql='SELECT * FROM '.$tbl_wiki.', '.$tbl_wiki_conf.'
              WHERE '.$tbl_wiki_conf.'.c_id = '.$course_id.' AND
               '.$tbl_wiki_conf.'.task!="" AND
               '.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND
                '.$tbl_wiki.'.'.$groupfilter.$condition_session;
        $allpages = Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            $total_task=$total_task+1;
        }

        //Total pages identified as teacher page (wiki portfolio mode - individual assignment)

        $total_teacher_assignment=0;
        $sql = 'SELECT  * FROM  '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$course_id.' AND assignment=1 AND id=(SELECT MAX(s2.id)
                FROM '.$tbl_wiki.' s2 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')';
        //mark all versions, but do not use group by reflink because y want the pages not versions
        $allpages=Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_teacher_assignment=$total_teacher_assignment+1;
        }

        //Total pages identifies as student page (wiki portfolio mode - individual assignment)

        $total_student_assignment=0;
        $sql = 'SELECT  * FROM  '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$course_id.' AND assignment=2 AND
                id=(SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')';
        //mark all versions, but do not use group by reflink because y want the pages not versions
        $allpages=Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            $total_student_assignment = $total_student_assignment+1;
        }

        //Current Wiki status add new pages
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
                GROUP BY addlock';//group by because mark 0 in all vers, then always is ok
        $allpages = Database::query($sql);
        $wiki_add_lock = null;
        while ($row=Database::fetch_array($allpages)) {
            $wiki_add_lock=$row['addlock'];
        }

        if ($wiki_add_lock==1) {
            $status_add_new_pag=get_lang('Yes');
        } else {
            $status_add_new_pag=get_lang('No');
        }

        //Creation date of the oldest wiki page and version

        $first_wiki_date='0000-00-00 00:00:00';
        $sql = 'SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
                ORDER BY dtime ASC LIMIT 1';
        $allpages=Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            $first_wiki_date=$row['dtime'];
        }

        // Date of publication of the latest wiki version.

        $last_wiki_date='0000-00-00 00:00:00';
        $sql = 'SELECT * FROM '.$tbl_wiki.'  WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
                ORDER BY dtime DESC LIMIT 1';
        $allpages=Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            $last_wiki_date=$row['dtime'];
        }

        // Average score of all wiki pages. (If a page has not scored zero rated)
        $media_score = 0;
        $sql = "SELECT *, SUM(score) AS TOTAL_SCORE FROM ".$tbl_wiki."
                WHERE c_id = $course_id AND ".$groupfilter.$condition_session."
                GROUP BY reflink ";
        //group by because mark in all versions, then always is ok. Do not use "count" because using "group by", would give a wrong value
        $allpages = Database::query($sql);
        $total_score = 0;
        while ($row=Database::fetch_array($allpages)) {
            $total_score = $total_score+$row['TOTAL_SCORE'];
        }

        if (!empty($total_pages)) {
            $media_score = $total_score/$total_pages;//put always this line alfter check num all pages
        }

        // Average user progress in his pages.

        $media_progress=0;
        $sql = 'SELECT  *, SUM(progress) AS TOTAL_PROGRESS
                FROM  '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$course_id.' AND id=
                (SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                 WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')';
        //As the value is only the latest version I can not use group by
        $allpages=Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            $total_progress	= $row['TOTAL_PROGRESS'];
        }

        if (!empty($total_pages)) {
            $media_progress=$total_progress/$total_pages;//put always this line alfter check num all pages
        }

        //Total users that have participated in the Wiki

        $total_users=0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE  c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
                GROUP BY user_id';
        //as the mark of user it in all versions of the page, I can use group by to see the first
        $allpages=Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            $total_users = $total_users+1;
        }

        // Total of different IP addresses that have participated in the wiki
        $total_ip=0;
        $sql='SELECT * FROM '.$tbl_wiki.'
              WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
              GROUP BY user_ip';
        $allpages=Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            $total_ip	= $total_ip+1;
        }

        echo '<table class="data_table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th colspan="2">'.get_lang('General').'</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tr>';
        echo '<td>'.get_lang('StudentAddNewPages').'</td>';
        echo '<td>'.$status_add_new_pag.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('DateCreateOldestWikiPage').'</td>';
        echo '<td>'.$first_wiki_date.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('DateEditLatestWikiVersion').'</td>';
        echo '<td>'.$last_wiki_date.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('AverageScoreAllPages').'</td>';
        echo '<td>'.$media_score.' %</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('AverageMediaUserProgress').'</td>';
        echo '<td>'.$media_progress.' %</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('TotalWikiUsers').'</td>';
        echo '<td>'.$total_users.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('TotalIpAdress').'</td>';
        echo '<td>'.$total_ip.'</td>';
        echo '</tr>';
        echo '</table>';
        echo '<br/>';

        echo '<table class="data_table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th colspan="2">'.get_lang('Pages').' '.get_lang('And').' '.get_lang('Versions').'</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tr>';
        echo '<td>'.get_lang('Pages').' - '.get_lang('NumContributions').'</td>';
        echo '<td>'.$total_pages.' ('.get_lang('Versions').': '.$total_versions.')</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('EmptyPages').'</td>';
        echo '<td>'.$total_empty_content_lv.' ('.get_lang('Versions').': '.$total_empty_content.')</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('NumAccess').'</td>';
        echo '<td>'.$total_visits_lv.' ('.get_lang('Versions').': '.$total_visits.')</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('TotalPagesEditedAtThisTime').'</td>';
        echo '<td>'.$total_editing_now.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('TotalHiddenPages').'</td>';
        echo '<td>'.$total_hidden.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('NumProtectedPages').'</td>';
        echo '<td>'.$total_protected.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('LockedDiscussPages').'</td>';
        echo '<td>'.$total_lock_disc.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('HiddenDiscussPages').'</td>';
        echo '<td>'.$total_hidden_disc.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('TotalComments').'</td>';
        echo '<td>'.$total_comment_version.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('TotalOnlyRatingByTeacher').'</td>';
        echo '<td>'.$total_only_teachers_rating.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('TotalRatingPeers').'</td>';
        echo '<td>'.$total_rating_by_peers.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('TotalTeacherAssignments').' - '.get_lang('PortfolioMode').'</td>';
        echo '<td>'.$total_teacher_assignment.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('TotalStudentAssignments').' - '.get_lang('PortfolioMode').'</td>';
        echo '<td>'.$total_student_assignment.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('TotalTask').' - '.get_lang('StandardMode').'</td>';
        echo '<td>'.$total_task.'</td>';
        echo '</tr>';
        echo '</table>';
        echo '<br/>';

        echo '<table class="data_table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th colspan="3">'.get_lang('ContentPagesInfo').'</th>';
        echo '</tr>';
        echo '<tr>';
        echo '<td></td>';
        echo '<td>'.get_lang('InTheLastVersion').'</td>';
        echo '<td>'.get_lang('InAllVersions').'</td>';
        echo '</tr>';
        echo '</thead>';
        echo '<tr>';
        echo '<td>'.get_lang('NumWords').'</td>';
        echo '<td>'.$total_words_lv.'</td>';
        echo '<td>'.$total_words.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('NumlinksHtmlImagMedia').'</td>';
        echo '<td>'.$total_links_lv.' ('.get_lang('Anchors').':'.$total_links_anchors_lv.', Mail:'.$total_links_mail_lv.', FTP:'.$total_links_ftp_lv.' IRC:'.$total_links_irc_lv.', News:'.$total_links_news_lv.', ... ) </td>';
        echo '<td>'.$total_links.' ('.get_lang('Anchors').':'.$total_links_anchors.', Mail:'.$total_links_mail.', FTP:'.$total_links_ftp.', IRC:'.$total_links_irc.', News:'.$total_links_news.', ... ) </td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('NumWikilinks').'</td>';
        echo '<td>'.$total_wlinks_lv.'</td>';
        echo '<td>'.$total_wlinks.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('NumImages').'</td>';
        echo '<td>'.$total_images_lv.'</td>';
        echo '<td>'.$total_images.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('NumFlash').'</td>';
        echo '<td>'.$total_flash_lv.'</td>';
        echo '<td>'.$total_flash.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('NumMp3').'</td>';
        echo '<td>'.$total_mp3_lv.'</td>';
        echo '<td>'.$total_mp3.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('NumFlvVideo').'</td>';
        echo '<td>'.$total_flv_lv.'</td>';
        echo '<td>'.$total_flv.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('NumYoutubeVideo').'</td>';
        echo '<td>'.$total_youtube_lv.'</td>';
        echo '<td>'.$total_youtube.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('NumOtherAudioVideo').'</td>';
        echo '<td>'.$total_multimedia_lv.'</td>';
        echo '<td>'.$total_multimedia.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('NumTables').'</td>';
        echo '<td>'.$total_tables_lv.'</td>';
        echo '<td>'.$total_tables.'</td>';
        echo '</tr>';
        echo '</table>';
    }

    /**
     * @param string $action
     */
    public function getActiveUsers($action)
    {
        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $_course = $this->courseInfo;

        echo '<div class="actions">'.get_lang('MostActiveUsers').'</div>';
        $sql='SELECT *, COUNT(*) AS NUM_EDIT FROM '.$tbl_wiki.'  WHERE  c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' GROUP BY user_id';
        $allpages = Database::query($sql);

        //show table
        if (Database::num_rows($allpages) > 0) {
            while ($obj = Database::fetch_object($allpages)) {
                $userinfo = api_get_user_info($obj->user_id);
                $username = api_htmlentities(sprintf(get_lang('LoginX'), $userinfo['username']), ENT_QUOTES);
                $row = array();
                if ($obj->user_id <> 0) {
                    $row[] = '<a href="'.api_get_path(WEB_CODE_PATH).'user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.
                        Display::tag('span', api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])), array('title'=>$username)).
                        '</a><a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=usercontrib&user_id='.urlencode($obj->user_id).
                        '&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'"></a>';
                } else {
                    $row[] = get_lang('Anonymous').' ('.$obj->user_ip.')';
                }
                $row[] ='<a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=usercontrib&user_id='.urlencode($obj->user_id).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.$obj->NUM_EDIT.'</a>';
                $rows[] = $row;
            }

            $table = new SortableTableFromArrayConfig($rows,1,10,'MostActiveUsersA_table','','','DESC');
            $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($action),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
            $table->set_header(0,get_lang('Author'), true);
            $table->set_header(1,get_lang('Contributions'), true,array ('style' => 'width:30px;'));
            $table->display();
        }
    }

    /**
     * @param string $page
     */
    public function getDiscuss($page)
    {
        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $tbl_wiki_discuss = $this->tbl_wiki_discuss;

        if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
            api_not_allowed();
        }

        if (!$_GET['title']) {
            self::setMessage(Display::display_error_message(get_lang("MustSelectPage"), false, true));
            return;
        }

        //first extract the date of last version
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session.'
                ORDER BY id DESC';
        $result=Database::query($sql);
        $row=Database::fetch_array($result);
        $lastversiondate=api_get_local_time($row['dtime'], null, date_default_timezone_get());
        $lastuserinfo = api_get_user_info($row['user_id']);
        $username = api_htmlentities(sprintf(get_lang('LoginX'), $lastuserinfo['username']), ENT_QUOTES);

        //select page to discuss
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session.'
                ORDER BY id ASC';
        $result=Database::query($sql);
        $row=Database::fetch_array($result);
        $id=$row['id'];
        $firstuserid=$row['user_id'];

        //mode assignment: previous to show  page type
        $icon_assignment = null;
        if ($row['assignment']==1) {
            $icon_assignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'),'',ICON_SIZE_SMALL);
        } elseif($row['assignment']==2) {
            $icon_assignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWorkExtra'),'',ICON_SIZE_SMALL);
        }

        $countWPost = null;
        $avg_WPost_score = null;

        //Show title and form to discuss if page exist
        if ($id!='') {
            //Show discussion to students if isn't hidden. Show page to all teachers if is hidden. Mode assignments: If is hidden, show pages to student only if student is the author
            if ($row['visibility_disc']==1 || api_is_allowed_to_edit(false,true) || api_is_platform_admin() || ($row['assignment']==2 && $row['visibility_disc']==0 && (api_get_user_id()==$row['user_id']))) {
                echo '<div id="wikititle">';

                // discussion action: protecting (locking) the discussion
                $addlock_disc = null;
                $lock_unlock_disc = null;
                if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                    if (self::check_addlock_discuss() == 1) {
                        $addlock_disc = Display::return_icon('unlock.png', get_lang('UnlockDiscussExtra'),'',ICON_SIZE_SMALL);
                        $lock_unlock_disc ='unlockdisc';
                    } else {
                        $addlock_disc = Display::return_icon('lock.png', get_lang('LockDiscussExtra'),'',ICON_SIZE_SMALL);
                        $lock_unlock_disc ='lockdisc';
                    }
                }
                echo '<span style="float:right">';
                echo '<a href="index.php?action=discuss&amp;actionpage='.$lock_unlock_disc.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$addlock_disc.'</a>';
                echo '</span>';

                // discussion action: visibility.  Show discussion to students if isn't hidden. Show page to all teachers if is hidden.
                $visibility_disc = null;
                $hide_show_disc = null;
                if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                    if (self::check_visibility_discuss()==1) {
                        /// TODO: 	Fix Mode assignments: If is hidden, show discussion to student only if student is the author
                        $visibility_disc = Display::return_icon('visible.png', get_lang('ShowDiscussExtra'),'',ICON_SIZE_SMALL);
                        $hide_show_disc = 'hidedisc';
                    } else {
                        $visibility_disc = Display::return_icon('invisible.png', get_lang('HideDiscussExtra'),'',ICON_SIZE_SMALL);
                        $hide_show_disc = 'showdisc';
                    }
                }
                echo '<span style="float:right">';
                echo '<a href="index.php?action=discuss&amp;actionpage='.$hide_show_disc.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$visibility_disc.'</a>';
                echo '</span>';

                //discussion action: check add rating lock. Show/Hide list to rating for all student
                $lock_unlock_rating_disc = null;
                $ratinglock_disc = null;
                if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                    if (self::check_ratinglock_discuss() == 1) {
                        $ratinglock_disc = Display::return_icon('star.png', get_lang('UnlockRatingDiscussExtra'),'',ICON_SIZE_SMALL);
                        $lock_unlock_rating_disc = 'unlockrating';
                    } else {
                        $ratinglock_disc = Display::return_icon('star_na.png', get_lang('LockRatingDiscussExtra'),'',ICON_SIZE_SMALL);
                        $lock_unlock_rating_disc = 'lockrating';
                    }
                }

                echo '<span style="float:right">';
                echo '<a href="index.php?action=discuss&amp;actionpage='.$lock_unlock_rating_disc.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$ratinglock_disc.'</a>';
                echo '</span>';

                //discussion action: email notification
                if (self::check_notify_discuss($page) == 1) {
                    $notify_disc= Display::return_icon('messagebox_info.png', get_lang('NotifyDiscussByEmail'),'',ICON_SIZE_SMALL);
                    $lock_unlock_notify_disc='unlocknotifydisc';
                } else {
                    $notify_disc= Display::return_icon('mail.png', get_lang('CancelNotifyDiscussByEmail'),'',ICON_SIZE_SMALL);
                    $lock_unlock_notify_disc='locknotifydisc';
                }
                echo '<span style="float:right">';
                echo '<a href="index.php?action=discuss&amp;actionpage='.$lock_unlock_notify_disc.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$notify_disc.'</a>';
                echo '</span>';

                echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.api_htmlentities($row['title']);

                echo ' ('.get_lang('MostRecentVersionBy').' <a href="'.api_get_path(WEB_CODE_PATH).'user/userInfo.php?uInfo='.$lastuserinfo['user_id'].'">'.
                    Display::tag('span', api_htmlentities(api_get_person_name($lastuserinfo['firstname'], $lastuserinfo['lastname'])), array('title'=>$username)).
                    '</a> '.$lastversiondate.$countWPost.')'.$avg_WPost_score.' '; //TODO: read average score

                echo '</div>';

                if ($row['addlock_disc']==1 || api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                    //show comments but students can't add theirs
                    ?>
                    <form name="form1" method="post" action="">
                        <table>
                            <tr>
                                <td valign="top" ><?php echo get_lang('Comments');?>:</td>
                                <?php  echo '<input type="hidden" name="wpost_id" value="'.md5(uniqid(rand(), true)).'">';//prevent double post ?>
                                <td><textarea name="comment" cols="80" rows="5" id="comment"></textarea></td>
                            </tr>
                            <tr>
                                <?php
                                //check if rating is allowed
                                if ($row['ratinglock_disc']==1 || api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                                    ?>
                                    <td><?php echo get_lang('Rating');?>: </td>
                                    <td valign="top"><select name="rating" id="rating">
                                            <option value="-" selected>-</option>
                                            <option value="0">0</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                            <option value="6">6</option>
                                            <option value="7">7</option>
                                            <option value="8">8</option>
                                            <option value="9">9</option>
                                            <option value="10">10</option>
                                        </select></td>
                                <?php
                                } else {
                                    echo '<input type=hidden name="rating" value="-">';// must pass a default value to avoid rate automatically
                                }
                                ?>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td> <?php  echo '<button class="save" type="submit" name="Submit"> '.get_lang('Send').'</button>'; ?></td>
                            </tr>
                        </table>
                    </form>

                    <?php
                    if (isset($_POST['Submit']) && self::double_post($_POST['wpost_id'])) {
                        $dtime = date( "Y-m-d H:i:s" );
                        $message_author = api_get_user_id();
                        $sql="INSERT INTO $tbl_wiki_discuss (c_id, publication_id, userc_id, comment, p_score, dtime) VALUES
                    	($course_id, '".$id."','".$message_author."','".Database::escape_string($_POST['comment'])."','".Database::escape_string($_POST['rating'])."','".$dtime."')";
                        $result=Database::query($sql) or die(Database::error());
                        self::check_emailcue($id, 'D', $dtime, $message_author);
                    }
                }//end discuss lock

                echo '<hr noshade size="1">';
                $user_table = Database :: get_main_table(TABLE_MAIN_USER);

                $sql="SELECT * FROM $tbl_wiki_discuss reviews, $user_table user
                  WHERE reviews.c_id = $course_id AND reviews.publication_id='".$id."' AND user.user_id='".$firstuserid."'
                  ORDER BY id DESC";
                $result=Database::query($sql) or die(Database::error());

                $countWPost = Database::num_rows($result);
                echo get_lang('NumComments').": ".$countWPost; //comment's numbers

                $sql = "SELECT SUM(p_score) as sumWPost
                        FROM $tbl_wiki_discuss
                        WHERE c_id = $course_id AND publication_id = '".$id."' AND NOT p_score='-'
                        ORDER BY id DESC";
                $result2=Database::query($sql) or die(Database::error());
                $row2=Database::fetch_array($result2);

                $sql = "SELECT * FROM $tbl_wiki_discuss
                        WHERE c_id = $course_id AND publication_id='".$id."' AND NOT p_score='-'";
                $result3=Database::query($sql) or die(Database::error());
                $countWPost_score= Database::num_rows($result3);

                echo ' - '.get_lang('NumCommentsScore').': '.$countWPost_score;//

                if ($countWPost_score!=0) {
                    $avg_WPost_score = round($row2['sumWPost'] / $countWPost_score,2).' / 10';
                } else {
                    $avg_WPost_score = $countWPost_score;
                }

                echo ' - '.get_lang('RatingMedia').': '.$avg_WPost_score; // average rating

                $sql = 'UPDATE '.$tbl_wiki.' SET score="'.Database::escape_string($avg_WPost_score).'"
                    WHERE c_id = '.$course_id.' AND reflink="'.Database::escape_string($page).'" AND '.$groupfilter.$condition_session;
                // check if work ok. TODO:
                Database::query($sql);

                echo '<hr noshade size="1">';
                //echo '<div style="overflow:auto; height:170px;">';

                while ($row=Database::fetch_array($result)) {
                    $userinfo = api_get_user_info($row['userc_id']);
                    if (($userinfo['status'])=="5") {
                        $author_status=get_lang('Student');
                    } else {
                        $author_status=get_lang('Teacher');
                    }

                    $user_id = $row['userc_id'];
                    $name = $userinfo['complete_name'];
                    if ($user_id<>0) {
                        $image_path = UserManager::get_user_picture_path_by_id($user_id,'web',false, true);
                        $image_repository = $image_path['dir'];
                        $existing_image = $image_path['file'];
                        $author_photo= '<img src="'.$image_repository.$existing_image.'" alt="'.api_htmlentities($name).'"  width="40" height="50" align="top" title="'.api_htmlentities($name).'"  />';
                    } else {
                        $author_photo= '<img src="'.api_get_path(WEB_CODE_PATH)."img/unknown.jpg".'" alt="'.api_htmlentities($name).'"  width="40" height="50" align="top"  title="'.api_htmlentities($name).'"  />';
                    }

                    //stars
                    $p_score=$row['p_score'];
                    switch ($p_score) {
                        case  0:
                            $imagerating = Display::return_icon('rating/stars_0.gif');
                            break;
                        case  1:
                            $imagerating = Display::return_icon('rating/stars_5.gif');
                            break;
                        case  2:
                            $imagerating = Display::return_icon('rating/stars_10.gif');
                            break;
                        case  3:
                            $imagerating = Display::return_icon('rating/stars_15.gif');
                            break;
                        case  4:
                            $imagerating = Display::return_icon('rating/stars_20.gif');
                            break;
                        case  5:
                            $imagerating = Display::return_icon('rating/stars_25.gif');
                            break;
                        case  6:
                            $imagerating = Display::return_icon('rating/stars_30.gif');
                            break;
                        case  7:
                            $imagerating = Display::return_icon('rating/stars_35.gif');
                            break;
                        case  8:
                            $imagerating = Display::return_icon('rating/stars_40.gif');
                            break;
                        case  9:
                            $imagerating = Display::return_icon('rating/stars_45.gif');
                            break;
                        case  10:
                            $imagerating = Display::return_icon('rating/stars_50.gif');
                            break;
                    }
                    echo '<p><table>';
                    echo '<tr>';
                    echo '<td rowspan="2">'.$author_photo.'</td>';
                    echo '<td style=" color:#999999"><a href="'.api_get_path(WEB_CODE_PATH).'user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.
                        Display::tag('span', api_htmlentities($userinfo['complete_name'])).
                        '</a> ('.$author_status.') '.
                        api_get_local_time($row['dtime'], null, date_default_timezone_get()).
                        ' - '.get_lang('Rating').': '.$row['p_score'].' '.$imagerating.' </td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo '<td>'.api_htmlentities($row['comment']).'</td>';
                    echo '</tr>';
                    echo "</table>";
                }
            } else {
                self::setMessage(Display::display_warning_message(get_lang('LockByTeacher'), false, true));
            }
        } else {
            self::setMessage(Display::display_normal_message(get_lang('DiscussNotAvailable'), false, true));
        }
    }

    /**
     * Show all pages
     */
    public function allPages($action)
    {
        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $session_id = $this->session_id;
        $groupfilter = $this->groupfilter;
        $_course = $this->courseInfo;

        echo '<div class="actions">'.get_lang('AllPages').'</div>';

        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) { //only by professors if page is hidden
            $sql = 'SELECT  *
                    FROM  '.$tbl_wiki.' s1
        		    WHERE s1.c_id = '.$course_id.' AND id=(
                    SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                    WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')';
            // warning don't use group by reflink because does not return the last version

        } else {
            $sql = 'SELECT  *  FROM   '.$tbl_wiki.' s1
				    WHERE visibility=1 AND s1.c_id = '.$course_id.' AND id=(
                        SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                        WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.' AND session_id='.$session_id.')';
            // warning don't use group by reflink because does not return the last version
        }

        $allpages = Database::query($sql);

        //show table
        if (Database::num_rows($allpages) > 0) {
            while ($obj = Database::fetch_object($allpages)) {
                //get author
                $userinfo = api_get_user_info($obj->user_id);
                $username = api_htmlentities(sprintf(get_lang('LoginX'), $userinfo['username']), ENT_QUOTES);

                //get type assignment icon
                if ($obj->assignment==1) {
                    $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',ICON_SIZE_SMALL);
                } elseif ($obj->assignment==2) {
                    $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',ICON_SIZE_SMALL);
                } elseif ($obj->assignment==0) {
                    $ShowAssignment = Display::return_icon('px_transparent.gif');
                }

                //get icon task
                if (!empty($obj->task)) {
                    $icon_task=Display::return_icon('wiki_task.png', get_lang('StandardTask'),'',ICON_SIZE_SMALL);
                } else {
                    $icon_task= Display::return_icon('px_transparent.gif');
                }

                $row = array();
                $row[] = $ShowAssignment.$icon_task;
                $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">
                '.api_htmlentities($obj->title).'</a>';
                if ($obj->user_id <>0) {
                    $row[] =  '<a href="'.api_get_path(WEB_CODE_PATH).'user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.
                        Display::tag('span', api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])), array('title'=>$username)).
                        '</a>';
                }
                else {
                    $row[] =  get_lang('Anonymous').' ('.api_htmlentities($obj->user_ip).')';
                }
                $row[] = api_get_local_time($obj->dtime, null, date_default_timezone_get());

                if (api_is_allowed_to_edit(false,true)|| api_is_platform_admin()) {
                    $showdelete=' <a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=delete&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                        Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL);
                }
                if (api_is_allowed_to_session_edit(false,true) ) {
                    $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=edit&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                        Display::return_icon('edit.png', get_lang('EditPage'),'',ICON_SIZE_SMALL).'</a> <a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=discuss&title='.api_htmlentities(urlencode($obj->reflink)).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                        Display::return_icon('discuss.png', get_lang('Discuss'),'',ICON_SIZE_SMALL).'</a> <a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=history&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                        Display::return_icon('history.png', get_lang('History'),'',ICON_SIZE_SMALL).'</a>
                        <a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=links&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                        Display::return_icon('what_link_here.png', get_lang('LinksPages'),'',ICON_SIZE_SMALL).'</a>'.$showdelete;
                }
                $rows[] = $row;
            }

            $table = new SortableTableFromArrayConfig($rows,1,10,'AllPages_table','','','ASC');
            $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($action),'group_id'=>Security::remove_XSS($_GET['group_id'])));
            $table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
            $table->set_header(1,get_lang('Title'), true);
            $table->set_header(2,get_lang('Author').' ('.get_lang('LastVersion').')', true);
            $table->set_header(3,get_lang('Date').' ('.get_lang('LastVersion').')', true);
            if (api_is_allowed_to_session_edit(false,true) ) {
                $table->set_header(4,get_lang('Actions'), true, array ('style' => 'width:130px;'));
            }
            $table->display();
        }
    }

    /**
     * Get recent changes
     * @param string $page
     * @param string $action
     *
     */
    public function recentChanges($page, $action)
    {
        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $tbl_wiki_conf = $this->tbl_wiki_conf;
        $_course = $this->courseInfo;

        if (api_is_allowed_to_session_edit(false,true) ) {
            if (self::check_notify_all()==1) {
                $notify_all= Display::return_icon('messagebox_info.png', get_lang('NotifyByEmail'),'',ICON_SIZE_SMALL).' '.get_lang('NotNotifyChanges');
                $lock_unlock_notify_all='unlocknotifyall';
            } else {
                $notify_all=Display::return_icon('mail.png', get_lang('CancelNotifyByEmail'),'',ICON_SIZE_SMALL).' '.get_lang('NotifyChanges');
                $lock_unlock_notify_all='locknotifyall';
            }
        }

        echo '<div class="actions"><span style="float: right;">';
        echo '<a href="index.php?action=recentchanges&amp;actionpage='.$lock_unlock_notify_all.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$notify_all.'</a>';
        echo '</span>'.get_lang('RecentChanges').'</div>';

        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) { //only by professors if page is hidden
            $sql = 'SELECT * FROM '.$tbl_wiki.', '.$tbl_wiki_conf.'
        		WHERE 	'.$tbl_wiki_conf.'.c_id= '.$course_id.' AND
        				'.$tbl_wiki.'.c_id= '.$course_id.' AND
        				'.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND
        				'.$tbl_wiki.'.'.$groupfilter.$condition_session.'
        		ORDER BY dtime DESC'; // new version
        } else {
            $sql = 'SELECT *
                FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$course_id.' AND
                    '.$groupfilter.$condition_session.' AND
                    visibility=1
                ORDER BY dtime DESC';
            // old version TODO: Replace by the bottom line
        }

        $allpages = Database::query($sql);

        //show table
        if (Database::num_rows($allpages) > 0) {
            $rows = array();
            while ($obj = Database::fetch_object($allpages)) {
                //get author
                $userinfo = api_get_user_info($obj->user_id);
                $username = api_htmlentities(sprintf(get_lang('LoginX'), $userinfo['username']), ENT_QUOTES);

                //get type assignment icon
                if ($obj->assignment==1) {
                    $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',ICON_SIZE_SMALL);
                } elseif ($obj->assignment==2) {
                    $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',ICON_SIZE_SMALL);
                } elseif ($obj->assignment==0) {
                    $ShowAssignment=Display::return_icon('px_transparent.gif');
                }

                // Get icon task
                if (!empty($obj->task)) {
                    $icon_task=Display::return_icon('wiki_task.png', get_lang('StandardTask'),'',ICON_SIZE_SMALL);
                } else {
                    $icon_task=Display::return_icon('px_transparent.gif');
                }

                $row = array();
                $row[] = api_get_local_time($obj->dtime, null, date_default_timezone_get());
                $row[] = $ShowAssignment.$icon_task;
                $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&amp;view='.$obj->id.'&session_id='.api_get_session_id().'&group_id='.api_get_group_id().'">'.
                    api_htmlentities($obj->title).'</a>';
                $row[] = $obj->version>1 ? get_lang('EditedBy') : get_lang('AddedBy');
                if ($obj->user_id <> 0 ) {
                    $row[] = '<a href="'.api_get_path(WEB_CODE_PATH).'user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.
                        Display::tag('span', api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])), array('title'=>$username)).
                        '</a>';
                } else {
                    $row[] = get_lang('Anonymous').' ('.api_htmlentities($obj->user_ip).')';
                }
                $rows[] = $row;
            }

            $table = new SortableTableFromArrayConfig($rows,0,10,'RecentPages_table','','','DESC');
            $table->set_additional_parameters(
                array(
                    'cidReq' =>api_get_course_id(),
                    'action'=>Security::remove_XSS($action),
                    'session_id' => api_get_session_id(),
                    'group_id' => api_get_group_id()
                )
            );
            $table->set_header(0,get_lang('Date'), true, array ('style' => 'width:200px;'));
            $table->set_header(1,get_lang('Type'), true, array ('style' => 'width:30px;'));
            $table->set_header(2,get_lang('Title'), true);
            $table->set_header(3,get_lang('Actions'), true, array ('style' => 'width:80px;'));
            $table->set_header(4,get_lang('Author'), true);
            $table->display();
        }
    }

    /**
     * What links here. Show pages that have linked this page
     *
     * @param string $page
     */
    public function getLinks($page)
    {
        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $_course = $this->courseInfo;
        $action = $this->action;

        if (!$_GET['title']) {
            self::setMessage(Display::display_error_message(get_lang("MustSelectPage"), false, true));
        } else {
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                        '.$groupfilter.$condition_session.'';
            $result = Database::query($sql);
            $row = Database::fetch_array($result);

            //get type assignment icon

            if ($row['assignment']==1) {
                $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',ICON_SIZE_SMALL);
            } elseif ($row['assignment']==2) {
                $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',ICON_SIZE_SMALL);
            } elseif ($row['assignment']==0) {
                $ShowAssignment=Display::return_icon('px_transparent.gif');
            }

            //fix Title to reflink (link Main Page)
            if ($page==get_lang('DefaultTitle')) {
                $page='index';
            }

            echo '<div id="wikititle">';
            echo get_lang('LinksPagesFrom').': '.$ShowAssignment.' <a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=showpage&title='.api_htmlentities(urlencode($page)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                api_htmlentities($row['title']).'</a>';
            echo '</div>';

            //fix index to title Main page into linksto

            if ($page == 'index') {
                $page=str_replace(' ','_',get_lang('DefaultTitle'));
            }

            //table
            if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) { //only by professors if page is hidden
                $sql = "SELECT * FROM ".$tbl_wiki." s1
                        WHERE s1.c_id = $course_id AND linksto LIKE '%".Database::escape_string($page)." %' AND id=(
                        SELECT MAX(s2.id) FROM ".$tbl_wiki." s2
                        WHERE s2.c_id = $course_id AND s1.reflink = s2.reflink AND ".$groupfilter.$condition_session.")";
                //add blank space after like '%" " %' to identify each word
            } else {
                $sql = "SELECT * FROM ".$tbl_wiki." s1
                        WHERE s1.c_id = $course_id AND visibility=1 AND linksto LIKE '%".Database::escape_string($page)." %' AND id=(
                        SELECT MAX(s2.id) FROM ".$tbl_wiki." s2
                        WHERE s2.c_id = $course_id AND s1.reflink = s2.reflink AND ".$groupfilter.$condition_session.")";
                //add blank space after like '%" " %' to identify each word
            }

            $allpages=Database::query($sql);

            //show table
            if (Database::num_rows($allpages) > 0) {
                $rows = array();
                while ($obj = Database::fetch_object($allpages)) {
                    //get author
                    $userinfo = api_get_user_info($obj->user_id);

                    //get time
                    $year 	 = substr($obj->dtime, 0, 4);
                    $month	 = substr($obj->dtime, 5, 2);
                    $day 	 = substr($obj->dtime, 8, 2);
                    $hours   = substr($obj->dtime, 11,2);
                    $minutes = substr($obj->dtime, 14,2);
                    $seconds = substr($obj->dtime, 17,2);

                    //get type assignment icon
                    if ($obj->assignment==1) {
                        $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',ICON_SIZE_SMALL);
                    } elseif ($obj->assignment==2) {
                        $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',ICON_SIZE_SMALL);
                    } elseif ($obj->assignment==0) {
                        $ShowAssignment=Display::return_icon('px_transparent.gif');
                    }

                    $row = array();
                    $row[] =$ShowAssignment;
                    $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                        api_htmlentities($obj->title).'</a>';
                    if ($obj->user_id <>0) {
                        $row[] = '<a href="'.api_get_path(WEB_CODE_PATH).'user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.
                            Display::tag('span', api_htmlentities($userinfo['complete_name_with_username'])).'</a>';
                    }
                    else {
                        $row[] = get_lang('Anonymous').' ('.$obj->user_ip.')';
                    }
                    $row[] = $year.'-'.$month.'-'.$day.' '.$hours.":".$minutes.":".$seconds;
                    $rows[] = $row;
                }

                $table = new SortableTableFromArrayConfig($rows,1,10,'AllPages_table','','','ASC');
                $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($action),'group_id'=>Security::remove_XSS($_GET['group_id'])));
                $table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
                $table->set_header(1,get_lang('Title'), true);
                $table->set_header(2,get_lang('Author'), true);
                $table->set_header(3,get_lang('Date'), true);
                $table->display();
            }
        }
    }

    /**
     * @param string $action
     */
    public function getSearchPages($action)
    {
        echo '<div class="actions">'.get_lang('SearchPages').'</div>';
        if (isset($_GET['mode_table'])) {
            if (!isset($_GET['SearchPages_table_page_nr'])) {
                $_GET['search_term'] = $_POST['search_term'];
                $_GET['search_content'] = $_POST['search_content'];
                $_GET['all_vers'] = $_POST['all_vers'];
            }
            self::display_wiki_search_results(
                $_GET['search_term'],
                $_GET['search_content'],
                $_GET['all_vers']
            );
        } else {

            // initiate the object
            $form = new FormValidator('wiki_search',
                'post',
                api_get_self().'?cidReq='.api_get_course_id().'&action='.api_htmlentities($action).'&session_id='.api_get_session_id().'&group_id='.api_get_group_id().'&mode_table=yes1&search_term='.api_htmlentities($_GET['search_term']).'&search_content='.api_htmlentities($_GET['search_content']).'&all_vers='.api_htmlentities($_GET['all_vers'])
            );

            // Setting the form elements

            $form->addElement('text', 'search_term', get_lang('SearchTerm'),'class="input_titles" id="search_title"');
            $form->addElement('checkbox', 'search_content', null, get_lang('AlsoSearchContent'));
            $form->addElement('checkbox', 'all_vers', null, get_lang('IncludeAllVersions'));
            $form->addElement('style_submit_button', 'SubmitWikiSearch', get_lang('Search'), 'class="search"');

            // setting the rules
            $form->addRule('search_term', get_lang('ThisFieldIsRequired'), 'required');
            $form->addRule('search_term', get_lang('TooShort'),'minlength',3); //TODO: before fixing the pagination rules worked, not now

            if ($form->validate()) {
                $form->display();
                $values = $form->exportValues();
                self::display_wiki_search_results(
                    $values['search_term'],
                    $values['search_content'],
                    $values['all_vers']
                );
            } else {
                $form->display();
            }
        }
    }

    /**
     * @param int $userId
     * @param string $action
     */
    public function getUserContributions($userId, $action)
    {
        $_course = $this->courseInfo;
        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;

        $userId = intval($userId);
        $userinfo = api_get_user_info($userId);
        $username = api_htmlentities(sprintf(get_lang('LoginX'), $userinfo['username']), ENT_QUOTES);

        echo '<div class="actions">'.get_lang('UserContributions').': <a href="'.api_get_path(WEB_CODE_PATH).'user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.
            Display::tag('span', api_htmlentities($userinfo['complete_name']), array('title'=>$username)).
            '</a><a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=usercontrib&user_id='.$userId.
            '&session_id='.$this->session_id.'&group_id='.$this->group_id.'"></a></div>';

        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
            //only by professors if page is hidden
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$course_id.' AND
                        '.$groupfilter.$condition_session.' AND
                        user_id="'.$userId.'"';
        } else {
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$course_id.' AND
                        '.$groupfilter.$condition_session.' AND
                        user_id="'.$userId.'" AND
                        visibility=1';
        }

        $allpages = Database::query($sql);

        //show table
        if (Database::num_rows($allpages) > 0) {
            $rows = array();
            while ($obj = Database::fetch_object($allpages)) {
                // Get time
                $year 	 = substr($obj->dtime, 0, 4);
                $month	 = substr($obj->dtime, 5, 2);
                $day 	 = substr($obj->dtime, 8, 2);
                $hours   = substr($obj->dtime, 11,2);
                $minutes = substr($obj->dtime, 14,2);
                $seconds = substr($obj->dtime, 17,2);

                //get type assignment icon
                if ($obj->assignment==1) {
                    $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'),'',ICON_SIZE_SMALL);
                } elseif ($obj->assignment==2) {
                    $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',ICON_SIZE_SMALL);
                } elseif ($obj->assignment==0) {
                    $ShowAssignment= Display::return_icon('px_transparent.gif');
                }

                $row = array();
                $row[] = $year.'-'.$month.'-'.$day.' '.$hours.":".$minutes.":".$seconds;
                $row[] = $ShowAssignment;
                $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&view='.$obj->id.'&session_id='.api_get_session_id().'&group_id='.api_get_group_id().'">'.
                    api_htmlentities($obj->title).'</a>';
                $row[] = Security::remove_XSS($obj->version);
                $row[] = Security::remove_XSS($obj->comment);
                $row[] = Security::remove_XSS($obj->progress).' %';
                $row[] = Security::remove_XSS($obj->score);
                $rows[] = $row;

            }

            $table = new SortableTableFromArrayConfig($rows,2,10,'UsersContributions_table','','','ASC');
            $table->set_additional_parameters(
                array(
                    'cidReq' =>Security::remove_XSS($_GET['cidReq']),
                    'action'=>Security::remove_XSS($action),
                    'user_id'=>Security::remove_XSS($userId),
                    'session_id'=>Security::remove_XSS($_GET['session_id']),
                    'group_id'=>Security::remove_XSS($_GET['group_id'])
                )
            );
            $table->set_header(0,get_lang('Date'), true, array ('style' => 'width:200px;'));
            $table->set_header(1,get_lang('Type'), true, array ('style' => 'width:30px;'));
            $table->set_header(2,get_lang('Title'), true, array ('style' => 'width:200px;'));
            $table->set_header(3,get_lang('Version'), true, array ('style' => 'width:30px;'));
            $table->set_header(4,get_lang('Comment'), true, array ('style' => 'width:200px;'));
            $table->set_header(5,get_lang('Progress'), true, array ('style' => 'width:30px;'));
            $table->set_header(6,get_lang('Rating'), true, array ('style' => 'width:30px;'));
            $table->display();
        }
    }

    /**
     * @param string $action
     */
    public function getMostChangedPages($action)
    {
        $_course = $this->courseInfo;
        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;

        echo '<div class="actions">'.get_lang('MostChangedPages').'</div>';

        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) { //only by professors if page is hidden
            $sql = 'SELECT *, MAX(version) AS MAX FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
                GROUP BY reflink';//TODO:check MAX and group by return last version
        } else {
            $sql = 'SELECT *, MAX(version) AS MAX FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.' AND visibility=1
                GROUP BY reflink'; //TODO:check MAX and group by return last version
        }

        $allpages = Database::query($sql);

        //show table
        if (Database::num_rows($allpages) > 0) {
            $rows = array();
            while ($obj = Database::fetch_object($allpages)) {
                //get type assignment icon
                if ($obj->assignment==1) {
                    $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',ICON_SIZE_SMALL);
                } elseif ($obj->assignment==2) {
                    $ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',ICON_SIZE_SMALL);
                } elseif ($obj->assignment==0) {
                    $ShowAssignment= Display::return_icon('px_transparent.gif');
                }

                $row = array();
                $row[] = $ShowAssignment;
                $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                    api_htmlentities($obj->title).'</a>';
                $row[] = $obj->MAX;
                $rows[] = $row;
            }

            $table = new SortableTableFromArrayConfig($rows,2,10,'MostChangedPages_table','','','DESC');
            $table->set_additional_parameters(
                array(
                    'cidReq' =>Security::remove_XSS($_GET['cidReq']),
                    'action'=>Security::remove_XSS($action),
                    'session_id'=>Security::remove_XSS($_GET['session_id']),
                    'group_id'=>Security::remove_XSS($_GET['group_id'])
                )
            );
            $table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
            $table->set_header(1,get_lang('Title'), true);
            $table->set_header(2,get_lang('Changes'), true);
            $table->display();
        }
    }

    /**
     */
    public function restorePage()
    {
        $userId = api_get_user_id();
        $_course = $this->courseInfo;
        $current_row = $this->getWikiData();
        $last_row = $this->getLastWikiData($this->page);

        if (empty($last_row)) {
            return false;
        }

        $PassEdit = false;

        /* Only teachers and platform admin can edit the index page.
        Only teachers and platform admin can edit an assignment teacher*/
        if (($current_row['reflink']=='index' || $current_row['reflink']=='' || $current_row['assignment'] == 1) &&
            (!api_is_allowed_to_edit(false,true) && $this->group_id == 0)
        ) {
            self::setMessage(
                Display::display_normal_message(get_lang('OnlyEditPagesCourseManager'), false, true)
            );
        } else {

            //check if is a wiki group
            if ($current_row['group_id'] != 0) {
                //Only teacher, platform admin and group members can edit a wiki group
                if (api_is_allowed_to_edit(false,true) ||
                    api_is_platform_admin() ||
                    GroupManager :: is_user_in_group($userId, $this->group_id)
                ) {
                    $PassEdit = true;
                } else {
                    self::setMessage(
                        Display::display_normal_message(get_lang('OnlyEditPagesGroupMembers'), false, true)
                    );
                }
            } else {
                $PassEdit = true;
            }

            // check if is an assignment
            //$icon_assignment = null;
            if ($current_row['assignment'] == 1) {
                self::setMessage(Display::display_normal_message(get_lang('EditAssignmentWarning'), false, true));
                //$icon_assignment = Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'),'',ICON_SIZE_SMALL);
            } elseif($current_row['assignment']==2) {
                //$icon_assignment = Display::return_icon('wiki_work.png', get_lang('AssignmentWorkExtra'),'',ICON_SIZE_SMALL);
                if (($userId == $current_row['user_id'])==false) {
                    if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                        $PassEdit = true;
                    } else {
                        self::setMessage(Display::display_warning_message(get_lang('LockByTeacher'), false, true));
                        $PassEdit = false;
                    }
                } else {
                    $PassEdit = true;
                }
            }

            //show editor if edit is allowed
            if ($PassEdit) {
                if ($current_row['editlock'] == 1 && (api_is_allowed_to_edit(false,true)==false || api_is_platform_admin()==false)) {
                    self::setMessage(Display::display_normal_message(get_lang('PageLockedExtra'), false, true));
                } else {
                    if ($last_row['is_editing']!=0 && $last_row['is_editing'] != $userId) {
                        // Checking for concurrent users
                        $timestamp_edit = strtotime($last_row['time_edit']);
                        $time_editing = time() - $timestamp_edit;
                        $max_edit_time = 1200; // 20 minutes
                        $rest_time = $max_edit_time - $time_editing;
                        $userinfo = api_get_user_info($last_row['is_editing']);
                        $is_being_edited = get_lang('ThisPageisBeginEditedBy').' <a href='.api_get_path(WEB_CODE_PATH).'user/userInfo.php?uInfo='.$userinfo['user_id'].'>'.
                            Display::tag('span', $userinfo['complete_name_with_username']).
                            get_lang('ThisPageisBeginEditedTryLater').' '.date( "i",$rest_time).' '.get_lang('MinMinutes');
                        self::setMessage(Display::display_normal_message($is_being_edited, false, true));
                    } else {
                        self::setMessage(Display::display_confirmation_message(
                                self::restore_wikipage(
                                    $current_row['page_id'],
                                    $current_row['reflink'],
                                    $current_row['title'],
                                    $current_row['content'],
                                    $current_row['group_id'],
                                    $current_row['assignment'],
                                    $current_row['progress'],
                                    $current_row['version'],
                                    $last_row['version'],
                                    $current_row['linksto']
                                ).': <a href="index.php?cidReq='.$_course['code'].'&action=showpage&amp;title='.api_htmlentities(urlencode($last_row['reflink'])).'&session_id='.$last_row['session_id'].'&group_id='.$last_row['group_id'].'">'.
                                api_htmlentities($last_row['title']).'</a>',
                                false,
                                true
                            ));
                    }
                }
            }
        }
    }

    /**
     * @param string $wikiId
     */
    public function setWikiData($wikiId)
    {
        $this->wikiData = self::get_wiki_data($wikiId);
    }

    /**
     * @return array
     */
    public function getWikiData()
    {
        return $this->wikiData;
    }

    /**
     * Check last version
     * @param int $view
     */
    public function checkLastVersion($view)
    {
        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $page = $this->page;
        $_course = $this->courseInfo;

        if (empty($view)) {
            return false;
        }

        $current_row = $this->getWikiData();

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$course_id.' AND
                    reflink = "'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id DESC'; //last version
        $result = Database::query($sql);

        $last_row = Database::fetch_array($result);

        if ($view < $last_row['id']) {

            $message = '<center>'.get_lang('NoAreSeeingTheLastVersion').'<br />
            '.get_lang("Version").' (
            <a href="index.php?cidReq='.$_course['code'].'&action=showpage&amp;title='.api_htmlentities(urlencode($current_row['reflink'])).'&group_id='.$current_row['group_id'].'&session_id='.$current_row['session_id'].'&view='.api_htmlentities($_GET['view']).'" title="'.get_lang('CurrentVersion').'">
            '.$current_row['version'].'
            </a> /
            <a href="index.php?cidReq='.$_course['code'].'&action=showpage&amp;title='.api_htmlentities(urlencode($last_row['reflink'])).'&group_id='.$last_row['group_id'].'&session_id='.$last_row['session_id'].'" title="'.get_lang('LastVersion').'">
            '.$last_row['version'].'
            </a>) <br />'.get_lang("ConvertToLastVersion").':
            <a href="index.php?cidReq='.$_course['id'].'&action=restorepage&amp;title='.api_htmlentities(urlencode($last_row['reflink'])).'&group_id='.$last_row['group_id'].'&session_id='.$last_row['session_id'].'&view='.api_htmlentities($_GET['view']).'">'.
                get_lang("Restore").'</a></center>';
            self::setMessage(Display::display_warning_message($message, false, true));
        }
    }

    /**
     *  Get most linked pages
     */
    public function getMostLinked()
    {
        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $groupfilter = $this->groupfilter;
        $condition_session = $this->condition_session;
        $_course = $this->courseInfo;

        echo '<div class="actions">'.get_lang('MostLinkedPages').'</div>';
        $pages = array();
        $linked = array();

        // Get name pages
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE  c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
                GROUP BY reflink
                ORDER BY reflink ASC';
        $allpages = Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            if ($row['reflink']=='index') {
                $row['reflink']=str_replace(' ', '_', get_lang('DefaultTitle'));
            }
            $pages[] = $row['reflink'];
        }

        // Get name refs in last pages
        $sql = 'SELECT *
                FROM '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$course_id.' AND id=(
                    SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                    WHERE
                        s2.c_id = '.$course_id.' AND
                        s1.reflink = s2.reflink AND
                        '.$groupfilter.$condition_session.'
                )';

        $allpages = Database::query($sql);

        while ($row=Database::fetch_array($allpages)) {
            //remove self reference
            $row['linksto']= str_replace($row["reflink"], " ", trim($row["linksto"]));
            $refs = explode(" ", trim($row["linksto"]));

            // Find linksto into reflink. If found ->page is linked
            foreach ($refs as $v) {
                if (in_array($v, $pages)) {
                    if (trim($v)!="") {
                        $linked[]=$v;
                    }
                }
            }
        }

        $linked = array_unique($linked);
        //make a unique list. TODO:delete this line and count how many for each page
        //show table
        $rows = array();
        foreach ($linked as $linked_show) {
            $row = array();
            $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=showpage&title='.api_htmlentities(urlencode(str_replace('_',' ',$linked_show))).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                str_replace('_',' ',$linked_show).'</a>';
            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig($rows,0,10,'LinkedPages_table','','','DESC');
        $table->set_additional_parameters(
            array(
                'cidReq' =>Security::remove_XSS($_GET['cidReq']),
                'action'=>Security::remove_XSS($this->action),
                'session_id'=>Security::remove_XSS($_GET['session_id']),
                'group_id'=>Security::remove_XSS($_GET['group_id'])
            )
        );
        $table->set_header(0,get_lang('Title'), true);
        $table->display();
    }

    /**
     * Get orphan pages
     */
    public function getOrphaned()
    {
        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $groupfilter = $this->groupfilter;
        $condition_session = $this->condition_session;
        $_course = $this->courseInfo;

        echo '<div class="actions">'.get_lang('OrphanedPages').'</div>';

        $pages = array();
        $orphaned = array();

        //get name pages
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
                GROUP BY reflink
                ORDER BY reflink ASC';
        $allpages=Database::query($sql);
        while ($row=Database::fetch_array($allpages)) {
            $pages[] = $row['reflink'];
        }

        //get name refs in last pages and make a unique list
        $sql = 'SELECT  *  FROM   '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$course_id.' AND id=(
                SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                WHERE
                    s2.c_id = '.$course_id.' AND
                    s1.reflink = s2.reflink AND
                    '.$groupfilter.$condition_session.'
                )';
        $allpages = Database::query($sql);
        $array_refs_linked = array();
        while ($row=Database::fetch_array($allpages)) {
            $row['linksto']= str_replace($row["reflink"], " ", trim($row["linksto"])); //remove self reference
            $refs = explode(" ", trim($row["linksto"]));
            foreach ($refs as $ref_linked){
                if ($ref_linked==str_replace(' ','_',get_lang('DefaultTitle'))) {
                    $ref_linked='index';
                }
                $array_refs_linked[]= $ref_linked;
            }
        }

        $array_refs_linked = array_unique($array_refs_linked);

        //search each name of list linksto into list reflink
        foreach ($pages as $v) {
            if (!in_array($v, $array_refs_linked)) {
                $orphaned[] = $v;
            }
        }
        $rows = array();
        foreach ($orphaned as $orphaned_show) {
            // get visibility status and title
            $sql = 'SELECT *
                    FROM  '.$tbl_wiki.'
		            WHERE
		                c_id = '.$course_id.' AND
		                '.$groupfilter.$condition_session.' AND
		                reflink="'.Database::escape_string($orphaned_show).'"
                    GROUP BY reflink';
            $allpages=Database::query($sql);
            while ($row=Database::fetch_array($allpages)) {
                $orphaned_title=$row['title'];
                $orphaned_visibility=$row['visibility'];
                if ($row['assignment']==1) {
                    $ShowAssignment=Display::return_icon('wiki_assignment.png','','',ICON_SIZE_SMALL);
                } elseif ($row['assignment']==2) {
                    $ShowAssignment=Display::return_icon('wiki_work.png','','',ICON_SIZE_SMALL);
                } elseif ($row['assignment']==0) {
                    $ShowAssignment= Display::return_icon('px_transparent.gif');
                }
            }

            if (!api_is_allowed_to_edit(false,true) || !api_is_platform_admin() AND $orphaned_visibility==0){
                continue;
            }

            //show table
            $row = array();
            $row[] = $ShowAssignment;
            $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=showpage&title='.api_htmlentities(urlencode($orphaned_show)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                api_htmlentities($orphaned_title).'</a>';
            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig($rows,1, 10, 'OrphanedPages_table','','','DESC');
        $table->set_additional_parameters(
            array(
                'cidReq' =>Security::remove_XSS($_GET['cidReq']),
                'action'=>Security::remove_XSS($this->action),
                'session_id'=>Security::remove_XSS($_GET['session_id']),
                'group_id'=>Security::remove_XSS($_GET['group_id'])
            )
        );
        $table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
        $table->set_header(1,get_lang('Title'), true);
        $table->display();
    }

    /**
     * Get wanted pages
     */
    public function getWantedPages()
    {
        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $groupfilter = $this->groupfilter;
        $condition_session = $this->condition_session;

        echo '<div class="actions">'.get_lang('WantedPages').'</div>';
        $pages = array();
        $wanted = array();
        //get name pages
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE  c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
                GROUP BY reflink
                ORDER BY reflink ASC';
        $allpages=Database::query($sql);

        while ($row=Database::fetch_array($allpages)) {
            if ($row['reflink']=='index'){
                $row['reflink']=str_replace(' ','_',get_lang('DefaultTitle'));
            }
            $pages[] = $row['reflink'];
        }

        //get name refs in last pages
        $sql = 'SELECT * FROM   '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$course_id.' AND id=(
                    SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                    WHERE s2.c_id = '.$course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.$condition_session.'
                )';

        $allpages = Database::query($sql);

        while ($row=Database::fetch_array($allpages)) {
            $refs = explode(" ", trim($row["linksto"]));
            // Find linksto into reflink. If not found ->page is wanted
            foreach ($refs as $v) {

                if (!in_array($v, $pages)) {
                    if (trim($v)!="") {
                        $wanted[]=$v;
                    }
                }
            }
        }

        $wanted = array_unique($wanted);//make a unique list

        //show table
        $rows = array();
        foreach ($wanted as $wanted_show) {
            $row = array();
            $wanted_show=Security::remove_XSS($wanted_show);
            $row[] = '<a href="'.api_get_path(WEB_PATH).'main/wiki/index.php?cidReq=&action=addnew&title='.str_replace('_',' ',$wanted_show).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'" class="new_wiki_link">'.str_replace('_',' ',$wanted_show).'</a>';//meter un remove xss en lugar de htmlentities
            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig($rows,0,10,'WantedPages_table','','','DESC');
        $table->set_additional_parameters(
            array('cidReq' =>Security::remove_XSS($_GET['cidReq']),
                'action'=>Security::remove_XSS($this->action),
                'session_id'=>Security::remove_XSS($_GET['session_id']),
                'group_id'=>Security::remove_XSS($_GET['group_id']))
        );
        $table->set_header(0,get_lang('Title'), true);
        $table->display();
    }

    public function getMostVisited()
    {
        $tbl_wiki = $this->tbl_wiki;
        $course_id = $this->course_id;
        $groupfilter = $this->groupfilter;
        $condition_session = $this->condition_session;
        $_course = $this->courseInfo;

        echo '<div class="actions">'.get_lang('MostVisitedPages').'</div>';

        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) { //only by professors if page is hidden
            $sql = 'SELECT *, SUM(hits) AS tsum FROM '.$tbl_wiki.'
                    WHERE c_id = '.$course_id.' AND '.$groupfilter.$condition_session.'
                    GROUP BY reflink';
        } else {
            $sql = 'SELECT *, SUM(hits) AS tsum FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$course_id.' AND
                        '.$groupfilter.$condition_session.' AND
                        visibility=1
                    GROUP BY reflink';
        }

        $allpages = Database::query($sql);

        //show table
        if (Database::num_rows($allpages) > 0) {
            $rows = array();
            while ($obj = Database::fetch_object($allpages)) {
                //get type assignment icon
                if ($obj->assignment==1) {
                    $ShowAssignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'),'',ICON_SIZE_SMALL);
                } elseif ($obj->assignment==2) {
                    $ShowAssignment=$ShowAssignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWork'),'',ICON_SIZE_SMALL);
                } elseif ($obj->assignment==0) {
                    $ShowAssignment = Display::return_icon('px_transparent.gif');
                }

                $row = array();
                $row[] =$ShowAssignment;
                $row[] = '<a href="'.api_get_self().'?cidReq='.$_course['code'].'&action=showpage&title='.api_htmlentities(urlencode($obj->reflink)).'&session_id='.api_htmlentities($_GET['session_id']).'&group_id='.api_htmlentities($_GET['group_id']).'">'.
                    api_htmlentities($obj->title).'</a>';
                $row[] = $obj->tsum;
                $rows[] = $row;
            }

            $table = new SortableTableFromArrayConfig($rows,2,10,'MostVisitedPages_table','','','DESC');
            $table->set_additional_parameters(array('cidReq' =>Security::remove_XSS($_GET['cidReq']),'action'=>Security::remove_XSS($this->action),'session_id'=>Security::remove_XSS($_GET['session_id']),'group_id'=>Security::remove_XSS($_GET['group_id'])));
            $table->set_header(0,get_lang('Type'), true, array ('style' => 'width:30px;'));
            $table->set_header(1,get_lang('Title'), true);
            $table->set_header(2,get_lang('Visits'), true);
            $table->display();
        }
    }

    /**
     * Get actions bar
     * @return string
     */
    public function showActionBar()
    {
        $_course = $this->courseInfo;
        $session_id = $this->session_id;
        $groupId = $this->group_id;
        $page = $this->page;

        echo '<div class="actions">';
        /*        echo '&nbsp;<a href="index.php?cidReq='.$_course['id'].'&action=show&amp;title=index&session_id='.$session_id.'&group_id='.$groupId.'"'.self::is_active_navigation_tab('show').'>'.
            Display::return_icon('wiki.png',get_lang('HomeWiki'),'',ICON_SIZE_MEDIUM).'</a>&nbsp;';*/
        echo '<ul class="nav" style="margin-bottom:0px">
                <li class="dropdown">
                <a class="dropdown-toggle" href="javascript:void(0)">'.Display::return_icon('menu.png', get_lang('Menu'), '', ICON_SIZE_MEDIUM).'</a>';
        // menu home
        echo '<ul class="dropdown-menu">';
        echo '<li><a href="index.php?action=showpage&title=index&cidReq='.$_course['id'].'&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('Home').'</a></li>';
        if (api_is_allowed_to_session_edit(false,true)) {
            //menu add page
            echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=addnew&session_id='.$session_id.'&group_id='.$groupId.'"'.self::is_active_navigation_tab('addnew').'>'.get_lang('AddNew').'</a>';
        }
        $lock_unlock_addnew = null;
        $protect_addnewpage = null;

        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
            // page action: enable or disable the adding of new pages
            if (self::check_addnewpagelock()==0) {
                $protect_addnewpage = Display::return_icon('off.png', get_lang('AddOptionProtected'));
                $lock_unlock_addnew ='unlockaddnew';
            } else {
                $protect_addnewpage = Display::return_icon('on.png', get_lang('AddOptionUnprotected'));
                $lock_unlock_addnew ='lockaddnew';
            }
        }

        echo '<a href="index.php?action=show&amp;actionpage='.$lock_unlock_addnew.'&amp;title='.api_htmlentities(urlencode($page)).'">'.$protect_addnewpage.'</a></li>';
        // menu find
        echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=searchpages&session_id='.$session_id.'&group_id='.$groupId.'"'.self::is_active_navigation_tab('searchpages').'>'.get_lang('SearchPages').'</a></li>';
        // menu all pages
        echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=allpages&session_id='.$session_id.'&group_id='.$groupId.'"'.self::is_active_navigation_tab('allpages').'>'.get_lang('AllPages').'</a></li>';
        // menu recent changes
        echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=recentchanges&session_id='.$session_id.'&group_id='.$groupId.'"'.self::is_active_navigation_tab('recentchanges').'>'.get_lang('RecentChanges').'</a></li>';
        // menu delete all wiki
        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
            echo '<li><a href="index.php?action=deletewiki&amp;title='.api_htmlentities(urlencode($page)).'"'.self::is_active_navigation_tab('deletewiki').'>'.get_lang('DeleteWiki').'</a></li>';
        }
        ///menu more
        echo '<li><a href="index.php?action=more&amp;title='.api_htmlentities(urlencode($page)).'"'.self::is_active_navigation_tab('more').'>'.get_lang('Statistics').'</a></li>';
        echo '</ul>';
        echo '</li>';

        //menu show page
        echo '<a href="index.php?cidReq='.$_course['id'].'&action=showpage&amp;title='.api_htmlentities(urlencode($page)).'&session_id='.$session_id.'&group_id='.$groupId.'"'.self::is_active_navigation_tab('showpage').'>'.
            Display::return_icon('page.png',get_lang('ShowThisPage'),'',ICON_SIZE_MEDIUM).'</a>';

        if (api_is_allowed_to_session_edit(false,true) ) {
            //menu edit page
            echo '<a href="index.php?cidReq='.$_course['id'].'&action=edit&amp;title='.api_htmlentities(urlencode($page)).'&session_id='.$session_id.'&group_id='.$groupId.'"'.self::is_active_navigation_tab('edit').'>'.
                Display::return_icon('edit.png',get_lang('EditThisPage'),'',ICON_SIZE_MEDIUM).'</a>';

            //menu discuss page
            echo '<a href="index.php?action=discuss&amp;title='.api_htmlentities(urlencode($page)).'"'.self::is_active_navigation_tab('discuss').'>'.
                Display::return_icon('discuss.png',get_lang('DiscussThisPage'),'',ICON_SIZE_MEDIUM).'</a>';
        }

        //menu history
        echo '<a href="index.php?cidReq='.$_course['id'].'&action=history&amp;title='.api_htmlentities(urlencode($page)).'&session_id='.$session_id.'&group_id='.$groupId.'"'.self::is_active_navigation_tab('history').'>'.
            Display::return_icon('history.png',get_lang('ShowPageHistory'),'',ICON_SIZE_MEDIUM).'</a>';
        //menu linkspages
        echo '<a href="index.php?action=links&amp;title='.api_htmlentities(urlencode($page)).'&session_id='.$session_id.'&group_id='.$groupId.'"'.self::is_active_navigation_tab('links').'>'.
            Display::return_icon('what_link_here.png',get_lang('LinksPages'),'',ICON_SIZE_MEDIUM).'</a>';

        //menu delete wikipage
        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
            echo '<a href="index.php?action=delete&amp;title='.api_htmlentities(urlencode($page)).'"'.self::is_active_navigation_tab('delete').'>'.
                Display::return_icon('delete.png',get_lang('DeleteThisPage'),'',ICON_SIZE_MEDIUM).'</a>';
        }
        echo '</ul>';
        echo '</div>'; // End actions
    }

    /**
     * Showing warning
     */
    public function deletePageWarning()
    {
        $page = $this->page;
        $course_id = $this->course_id;
        $groupfilter = $this->groupfilter;
        $condition_session = $this->condition_session;

        if (!$_GET['title']) {
            self::setMessage(Display::display_error_message(get_lang('MustSelectPage'), false, true));
            return;
        }

        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
            self::setMessage('<div id="wikititle">'.get_lang('DeletePageHistory').'</div>');

            if ($page == "index") {
                self::setMessage(Display::display_warning_message(get_lang('WarningDeleteMainPage'), false, true));
            }

            $message = get_lang('ConfirmDeletePage')."
                <a href=\"index.php\">".get_lang("No")."</a>
                <a href=\"".api_get_self()."?action=delete&amp;title=".api_htmlentities(urlencode($page))."&amp;delete=yes\">".
                get_lang("Yes")."</a>";

            if (!isset($_GET['delete'])) {
                self::setMessage(Display::display_warning_message($message, false, true));
            }

            if (isset($_GET['delete']) && $_GET['delete'] == 'yes') {
                $result = self::deletePage($page, $course_id, $groupfilter, $condition_session);
                if ($result) {
                    self::setMessage(Display::display_confirmation_message(get_lang('WikiPageDeleted'), false, true));
                }
            }
        } else {
            self::setMessage(Display::display_normal_message(get_lang("OnlyAdminDeletePageWiki"), false, true));
        }
    }

    /**
     * Edit page
     */
    public function editPage()
    {
        $tbl_wiki = $this->tbl_wiki;
        $tbl_wiki_conf = $this->tbl_wiki_conf;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $page = $this->page;
        $course_id = $this->course_id;
        $groupId = $this->group_id;
        $userId = api_get_user_id();

        if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
            api_not_allowed();
        }

        $sql = 'SELECT *
                FROM '.$tbl_wiki.', '.$tbl_wiki_conf.'
    		    WHERE
                    '.$tbl_wiki.'.c_id = '.$course_id.' AND
                    '.$tbl_wiki_conf.'.c_id = '.$course_id.' AND
                    '.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND
                    '.$tbl_wiki.'.reflink= "'.Database::escape_string($page).'" AND
                    '.$tbl_wiki.'.'.$groupfilter.$condition_session.'
                ORDER BY id DESC';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        // we do not need a while loop since we are always displaying the last version

        if ($row['content']=='' AND $row['title']=='' AND $page=='') {
            self::setMessage(Display::display_error_message(get_lang('MustSelectPage'), false, true));
            return;
        } elseif ($row['content']=='' AND $row['title']=='' AND $page=='index') {
            //Table structure for better export to pdf
            $default_table_for_content_Start='<table align="center" border="0"><tr><td align="center">';
            $default_table_for_content_End='</td></tr></table>';
            $content = $default_table_for_content_Start.sprintf(get_lang('DefaultContent'),api_get_path(WEB_IMG_PATH)).$default_table_for_content_End;
            $title=get_lang('DefaultTitle');
            $page_id=0;
        } else {
            $content = api_html_entity_decode($row['content']);
            $title = api_html_entity_decode($row['title']);
            $page_id = $row['page_id'];
        }

        //Only teachers and platform admin can edit the index page. Only teachers and platform admin can edit an assignment teacher. And users in groups
        if (($row['reflink']=='index' || $row['reflink']=='' || $row['assignment']==1) && (!api_is_allowed_to_edit(false,true) && intval($_GET['group_id'])==0)) {
            self::setMessage(Display::display_error_message(get_lang('OnlyEditPagesCourseManager'), false, true));
        } else {
            $PassEdit=false;

            //check if is a wiki group
            if ($groupId!=0) {
                //Only teacher, platform admin and group members can edit a wiki group
                if (api_is_allowed_to_edit(false,true) || api_is_platform_admin() || GroupManager :: is_user_in_group($userId, $groupId)) {
                    $PassEdit=true;
                } else {
                    self::setMessage(Display::display_normal_message(get_lang('OnlyEditPagesGroupMembers'), false, true));
                }
            } else {
                $PassEdit=true;
            }
            $icon_assignment = null;
            // check if is a assignment
            if ($row['assignment']==1) {
                self::setMessage(Display::display_normal_message(get_lang('EditAssignmentWarning'), false, true));
                $icon_assignment=Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'),'',ICON_SIZE_SMALL);
            } elseif ($row['assignment']==2) {
                $icon_assignment=Display::return_icon('wiki_work.png', get_lang('AssignmentWorkExtra'),'',ICON_SIZE_SMALL);
                if (($userId == $row['user_id'])==false) {
                    if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                        $PassEdit=true;
                    } else {
                        self::setMessage(Display::display_warning_message(get_lang('LockByTeacher'), false, true));
                        $PassEdit=false;
                    }
                } else {
                    $PassEdit=true;
                }
            }

            if ($PassEdit) {
                //show editor if edit is allowed
                if ($row['editlock']==1 &&
                    (api_is_allowed_to_edit(false,true)==false || api_is_platform_admin()==false)
                ) {
                    self::setMessage(Display::display_normal_message(get_lang('PageLockedExtra'), false, true));
                } else {
                    // Check tasks

                    if (!empty($row['startdate_assig']) &&
                        $row['startdate_assig']!='0000-00-00 00:00:00' &&
                        time()<strtotime($row['startdate_assig'])
                    ) {
                        $message=get_lang('TheTaskDoesNotBeginUntil').': '.api_get_local_time($row['startdate_assig'], null, date_default_timezone_get());
                        self::setMessage(Display::display_warning_message($message, false, true));
                        if (!api_is_allowed_to_edit(false,true)) {
                            return;
                        }
                    }

                    if (!empty($row['enddate_assig']) &&
                        $row['enddate_assig']!='0000-00-00 00:00:00' &&
                        time() > strtotime($row['enddate_assig']) &&
                        $row['enddate_assig']!='0000-00-00 00:00:00' &&
                        $row['delayedsubmit']==0
                    ) {
                        $message=get_lang('TheDeadlineHasBeenCompleted').': '.api_get_local_time($row['enddate_assig'], null, date_default_timezone_get());
                        self::setMessage(Display::display_warning_message($message, false, true));
                        if (!api_is_allowed_to_edit(false,true)) {
                            return;
                        }
                    }

                    if (!empty($row['max_version']) && $row['version']>=$row['max_version']) {
                        $message=get_lang('HasReachedMaxiNumVersions');
                        self::setMessage(Display::display_warning_message($message, false, true));
                        if (!api_is_allowed_to_edit(false,true)) {
                            return;
                        }
                    }

                    if (!empty($row['max_text']) && $row['max_text']<=self::word_count($row['content'])) {
                        $message = get_lang('HasReachedMaxNumWords');
                        self::setMessage(Display::display_warning_message($message, false, true));
                        if (!api_is_allowed_to_edit(false,true)) {
                            return;
                        }
                    }

                    if (!empty($row['task'])) {
                        //previous change 0 by text
                        if ($row['startdate_assig']=='0000-00-00 00:00:00') {
                            $message_task_startdate  =get_lang('No');
                        } else {
                            $message_task_startdate = api_get_local_time($row['startdate_assig'], null, date_default_timezone_get());
                        }

                        if ($row['enddate_assig']=='0000-00-00 00:00:00') {
                            $message_task_enddate = get_lang('No');
                        } else {
                            $message_task_enddate = api_get_local_time($row['enddate_assig'], null, date_default_timezone_get());
                        }

                        if ($row['delayedsubmit']==0) {
                            $message_task_delayedsubmit=get_lang('No');
                        } else {
                            $message_task_delayedsubmit=get_lang('Yes');
                        }
                        if ($row['max_version']==0) {
                            $message_task_max_version=get_lang('No');
                        } else {
                            $message_task_max_version=$row['max_version'];
                        }
                        if ($row['max_text']==0) {
                            $message_task_max_text=get_lang('No');
                        } else {
                            $message_task_max_text=$row['max_text'];
                        }

                        //comp message
                        $message_task='<b>'.get_lang('DescriptionOfTheTask').'</b><p>'.$row['task'].'</p><hr>';
                        $message_task.='<p>'.get_lang('StartDate').': '.$message_task_startdate.'</p>';
                        $message_task.='<p>'.get_lang('EndDate').': '.$message_task_enddate;
                        $message_task.=' ('.get_lang('AllowLaterSends').') '.$message_task_delayedsubmit.'</p>';
                        $message_task.='<p>'.get_lang('OtherSettings').': '.get_lang('NMaxVersion').': '.$message_task_max_version;
                        $message_task.=' '.get_lang('NMaxWords').': '.$message_task_max_text;
                        //display message
                        self::setMessage(Display::display_normal_message($message_task, false, true));
                    }

                    if ($row['progress']==$row['fprogress1'] && !empty($row['fprogress1'])) {
                        $feedback_message='<b>'.get_lang('Feedback').'</b><p>'.api_htmlentities($row['feedback1']).'</p>';
                        self::setMessage(Display::display_normal_message($feedback_message, false, true));
                    } elseif ($row['progress']==$row['fprogress2'] && !empty($row['fprogress2'])) {
                        $feedback_message='<b>'.get_lang('Feedback').'</b><p>'.api_htmlentities($row['feedback2']).'</p>';
                        self::setMessage(Display::display_normal_message($feedback_message, false, true));
                    } elseif ($row['progress']==$row['fprogress3'] && !empty($row['fprogress3'])) {
                        $feedback_message='<b>'.get_lang('Feedback').'</b><p>'.api_htmlentities($row['feedback3']).'</p>';
                        self::setMessage(Display::display_normal_message($feedback_message, false, true));
                    }

                    // Previous checking for concurrent editions
                    if ($row['is_editing']==0) {
                        self::setMessage(Display::display_normal_message(get_lang('WarningMaxEditingTime'), false, true));
                        $time_edit = date("Y-m-d H:i:s");
                        $sql = 'UPDATE '.$tbl_wiki.' SET is_editing="'.$userId.'", time_edit="'.$time_edit.'"
                                WHERE c_id = '.$course_id.' AND  id="'.$row['id'].'"';
                        Database::query($sql);
                    } elseif ($row['is_editing']!= $userId) {
                        $timestamp_edit=strtotime($row['time_edit']);
                        $time_editing=time()-$timestamp_edit;
                        $max_edit_time=1200; // 20 minutes
                        $rest_time=$max_edit_time-$time_editing;

                        $userinfo = api_get_user_info($row['is_editing']);
                        $username = api_htmlentities(sprintf(get_lang('LoginX'), $userinfo['username']), ENT_QUOTES);

                        $is_being_edited = get_lang('ThisPageisBeginEditedBy').
                            ' <a href='.api_get_path(WEB_CODE_PATH).'user/userInfo.php?uInfo='.$userinfo['user_id'].'>'.
                            Display::tag('span', api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])), array('title'=>$username)).
                            '</a>. '.get_lang('ThisPageisBeginEditedTryLater').' '.date( "i",$rest_time).' '.get_lang('MinMinutes').'';
                        self::setMessage(Display::display_normal_message($is_being_edited, false, true));
                        return;
                    }

                    // Form.
                    $url = api_get_self().'?action=edit&title='.urlencode($page).'&session_id='.api_get_session_id().'&group_id='.api_get_group_id();
                    $form = new FormValidator('wiki', 'post', $url);
                    $form->addElement('header', $icon_assignment.str_repeat('&nbsp;',3).api_htmlentities($title));
                    self::setForm($form, $row);
                    $form->addElement('hidden', 'title');
                    $form->addElement('button', 'SaveWikiChange', get_lang('Save'));
                    $row['title'] = $title;
                    $row['page_id'] = $page_id;
                    $row['reflink'] = $page;

                    $form->setDefaults($row);
                    $form->display();

                    // Saving a change

                    if ($form->validate()) {
                        if (empty($_POST['title'])) {
                            self::setMessage(Display::display_error_message(get_lang("NoWikiPageTitle"), false, true));
                        } elseif (!self::double_post($_POST['wpost_id'])) {
                            //double post
                        } elseif ($_POST['version']!='' && $_SESSION['_version'] !=0 && $_POST['version'] != $_SESSION['_version']) {
                            //prevent concurrent users and double version
                            self::setMessage(Display::display_error_message(get_lang("EditedByAnotherUser"), false, true));
                        } else {
                            $return_message = self::save_wiki($form->exportValues());
                            self::setMessage(Display::display_confirmation_message($return_message, false, true));
                        }
                        $wikiData = self::getWikiData();
                        $redirectUrl = $this->url.'&action=showpage&title='.$wikiData['reflink'];
                        header('Location: '.$redirectUrl);
                        exit;
                    }
                }
            }
        }
    }

    /**
     * Get history
     */
    public function getHistory()
    {
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $page = $this->page;
        $course_id = $this->course_id;
        $_course = $this->courseInfo;
        $session_id = $this->session_id;
        $userId = api_get_user_id();

        if (!$_GET['title']) {
            self::setMessage(Display::display_error_message(get_lang("MustSelectPage"), false, true));
            return;
        }

        /* First, see the property visibility that is at the last register and
        therefore we should select descending order.
        But to give ownership to each record,
        this is no longer necessary except for the title. TODO: check this*/

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$course_id.' AND
                    reflink="'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id DESC';
        $result = Database::query($sql);

        $KeyVisibility = null;
        $KeyAssignment = null;
        $KeyTitle = null;
        $KeyUserId = null;
        while ($row=Database::fetch_array($result)) {
            $KeyVisibility = $row['visibility'];
            $KeyAssignment = $row['assignment'];
            $KeyTitle = $row['title'];
            $KeyUserId = $row['user_id'];
        }
        $icon_assignment = null;
        if ($KeyAssignment == 1) {
            $icon_assignment = Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'), '', ICON_SIZE_SMALL);
        } elseif($KeyAssignment == 2) {
            $icon_assignment = Display::return_icon('wiki_work.png', get_lang('AssignmentWorkExtra'), '', ICON_SIZE_SMALL);
        }

        // Second, show

        //if the page is hidden and is a job only sees its author and professor
        if ($KeyVisibility == 1 ||
            api_is_allowed_to_edit(false,true) ||
            api_is_platform_admin() ||
            (
                $KeyAssignment==2 && $KeyVisibility==0 &&
                ($userId == $KeyUserId)
            )
        ) {
            // We show the complete history
            if (!isset($_POST['HistoryDifferences']) && !isset($_POST['HistoryDifferences2'])) {
                $sql = 'SELECT * FROM '.$tbl_wiki.'
                        WHERE
                            c_id = '.$course_id.' AND
                            reflink="'.Database::escape_string($page).'" AND
                            '.$groupfilter.$condition_session.'
                        ORDER BY id DESC';
                $result = Database::query($sql);
                $title		= $_GET['title'];
                $group_id	= $_GET['group_id'];

                echo '<div id="wikititle">';
                echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.api_htmlentities($KeyTitle);
                echo '</div>';

                echo '<form id="differences" method="POST" action="index.php?cidReq='.$_course['code'].'&action=history&title='.api_htmlentities(urlencode($title)).'&session_id='.api_htmlentities($session_id).'&group_id='.api_htmlentities($group_id).'">';

                echo '<ul style="list-style-type: none;">';
                echo '<br/>';
                echo '<button class="search" type="submit" name="HistoryDifferences" value="HistoryDifferences">'.get_lang('ShowDifferences').' '.get_lang('LinesDiff').'</button>';
                echo '<button class="search" type="submit" name="HistoryDifferences2" value="HistoryDifferences2">'.get_lang('ShowDifferences').' '.get_lang('WordsDiff').'</button>';
                echo '<br/><br/>';

                $counter=0;
                $total_versions=Database::num_rows($result);

                while ($row=Database::fetch_array($result)) {
                    $userinfo = api_get_user_info($row['user_id']);
                    $username = api_htmlentities(sprintf(get_lang('LoginX'), $userinfo['username']), ENT_QUOTES);

                    echo '<li style="margin-bottom: 5px;">';
                    ($counter==0) ? $oldstyle='style="visibility: hidden;"':$oldstyle='';
                    ($counter==0) ? $newchecked=' checked':$newchecked='';
                    ($counter==$total_versions-1) ? $newstyle='style="visibility: hidden;"':$newstyle='';
                    ($counter==1) ? $oldchecked=' checked':$oldchecked='';
                    echo '<input name="old" value="'.$row['id'].'" type="radio" '.$oldstyle.' '.$oldchecked.'/> ';
                    echo '<input name="new" value="'.$row['id'].'" type="radio" '.$newstyle.' '.$newchecked.'/> ';
                    echo '<a href="'.api_get_self().'?action=showpage&amp;title='.api_htmlentities(urlencode($page)).'&amp;view='.$row['id'].'">';
                    echo '<a href="'.api_get_self().'?cidReq='.$_course['id'].'&action=showpage&amp;title='.api_htmlentities(urlencode($page)).'&amp;view='.$row['id'].'&session_id='.$session_id.'&group_id='.$group_id.'">';
                    echo api_get_local_time($row['dtime'], null, date_default_timezone_get());
                    echo '</a>';
                    echo ' ('.get_lang('Version').' '.$row['version'].')';
                    echo ' '.get_lang('By').' ';
                    if ($row['user_id']<>0) {
                        echo '<a href="'.api_get_path(WEB_CODE_PATH).'user/userInfo.php?uInfo='.$userinfo['user_id'].'">'.
                            Display::tag('span', api_htmlentities(api_get_person_name($userinfo['firstname'], $userinfo['lastname'])), array('title'=>$username)).
                            '</a>';
                    } else {
                        echo get_lang('Anonymous').' ('.api_htmlentities($row['user_ip']).')';
                    }
                    echo ' ( '.get_lang('Progress').': '.api_htmlentities($row['progress']).'%, ';
                    $comment = $row['comment'];
                    if (!empty($comment)) {
                        echo get_lang('Comments').': '.api_htmlentities(api_substr($row['comment'], 0, 100));
                        if (api_strlen($row['comment'])>100) {
                            echo '... ';
                        }
                    } else {
                        echo get_lang('Comments').':  ---';
                    }
                    echo ' ) </li>';
                    $counter++;
                } //end while

                echo '<br/>';
                echo '<button class="search" type="submit" name="HistoryDifferences" value="HistoryDifferences">'.get_lang('ShowDifferences').' '.get_lang('LinesDiff').'</button>';
                echo '<button class="search" type="submit" name="HistoryDifferences2" value="HistoryDifferences2">'.get_lang('ShowDifferences').' '.get_lang('WordsDiff').'</button>';
                echo '</ul></form>';
            } else { // We show the differences between two versions
                $version_old = array();
                if (isset($_POST['old'])) {
                    $sql_old= "SELECT * FROM $tbl_wiki
                           WHERE c_id = $course_id AND id='".Database::escape_string($_POST['old'])."'";
                    $result_old=Database::query($sql_old);
                    $version_old=Database::fetch_array($result_old);
                }

                $sql_new="SELECT * FROM $tbl_wiki WHERE c_id = $course_id AND id='".Database::escape_string($_POST['new'])."'";
                $result_new=Database::query($sql_new);
                $version_new=Database::fetch_array($result_new);
                $oldTime = isset($version_old['dtime']) ? $version_old['dtime'] : null;
                $oldContent = isset($version_old['content']) ? $version_old['content'] : null;

                if (isset($_POST['HistoryDifferences'])) {
                    include 'diff.inc.php';
                    //title
                    echo '<div id="wikititle">'.api_htmlentities($version_new['title']).'
                <font size="-2"><i>('.get_lang('DifferencesNew').'</i>
                    <font style="background-color:#aaaaaa">'.$version_new['dtime'].'</font>
                    <i>'.get_lang('DifferencesOld').'</i>
                    <font style="background-color:#aaaaaa">'.$oldTime.'</font>
                ) '.get_lang('Legend').':  <span class="diffAdded" >'.get_lang('WikiDiffAddedLine').'</span>
                <span class="diffDeleted" >'.get_lang('WikiDiffDeletedLine').'</span> <span class="diffMoved">'.get_lang('WikiDiffMovedLine').'</span></font>
                </div>';
                }
                if (isset($_POST['HistoryDifferences2'])) {
                    // including global PEAR diff libraries
                    require_once 'Text/Diff.php';
                    require_once 'Text/Diff/Renderer/inline.php';
                    //title
                    echo '<div id="wikititle">'.api_htmlentities($version_new['title']).'
                        <font size="-2"><i>('.get_lang('DifferencesNew').'</i> <font style="background-color:#aaaaaa">'.$version_new['dtime'].'</font>
                        <i>'.get_lang('DifferencesOld').'</i> <font style="background-color:#aaaaaa">'.$oldTime.'</font>)
                        '.get_lang('Legend').':  <span class="diffAddedTex" >'.get_lang('WikiDiffAddedTex').'</span>
                        <span class="diffDeletedTex" >'.get_lang('WikiDiffDeletedTex').'</span></font></div>';
                }


                if (isset($_POST['HistoryDifferences'])) {
                    echo '<table>'.diff($oldContent, $version_new['content'], true, 'format_table_line' ).'</table>'; // format_line mode is better for words
                    echo '<br />';
                    echo '<strong>'.get_lang('Legend').'</strong><div class="diff">' . "\n";
                    echo '<table><tr>';
                    echo  '<td>';
                    echo '</td><td>';
                    echo '<span class="diffEqual" >'.get_lang('WikiDiffUnchangedLine').'</span><br />';
                    echo '<span class="diffAdded" >'.get_lang('WikiDiffAddedLine').'</span><br />';
                    echo '<span class="diffDeleted" >'.get_lang('WikiDiffDeletedLine').'</span><br />';
                    echo '<span class="diffMoved" >'.get_lang('WikiDiffMovedLine').'</span><br />';
                    echo '</td>';
                    echo '</tr></table>';
                }

                if (isset($_POST['HistoryDifferences2'])) {
                    $lines1 = array(strip_tags($oldContent)); //without <> tags
                    $lines2 = array(strip_tags($version_new['content'])); //without <> tags
                    $diff = new Text_Diff($lines1, $lines2);
                    $renderer = new Text_Diff_Renderer_inline();
                    echo '<style>del{background:#fcc}ins{background:#cfc}</style>'.$renderer->render($diff); // Code inline
                    echo '<br />';
                    echo '<strong>'.get_lang('Legend').'</strong><div class="diff">' . "\n";
                    echo '<table><tr>';
                    echo  '<td>';
                    echo '</td><td>';
                    echo '<span class="diffAddedTex" >'.get_lang('WikiDiffAddedTex').'</span><br />';
                    echo '<span class="diffDeletedTex" >'.get_lang('WikiDiffDeletedTex').'</span><br />';
                    echo '</td>';
                    echo '</tr></table>';
                }
            }
        }
    }

    /**
     * Get stat tables
     */
    public function getStatsTable()
    {
        $_course = $this->courseInfo;
        $session_id = $this->session_id;
        $groupId = $this->group_id;

        echo '<div class="actions">'.get_lang('More').'</div>';
        echo '<table border="0">';
        echo '  <tr>';
        echo '    <td>';
        echo '      <ul>';
        //Submenu Most active users
        echo '        <li><a href="index.php?cidReq='.$_course['code'].'&action=mactiveusers&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('MostActiveUsers').'</a></li>';
        //Submenu Most visited pages
        echo '        <li><a href="index.php?cidReq='.$_course['code'].'&action=mvisited&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('MostVisitedPages').'</a></li>';
        //Submenu Most changed pages
        echo '        <li><a href="index.php?cidReq='.$_course['code'].'&action=mostchanged&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('MostChangedPages').'</a></li>';
        echo '      </ul>';
        echo '    </td>';
        echo '    <td>';
        echo '      <ul>';
        // Submenu Orphaned pages
        echo '        <li><a href="index.php?cidReq='.$_course['code'].'&action=orphaned&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('OrphanedPages').'</a></li>';
        // Submenu Wanted pages
        echo '        <li><a href="index.php?cidReq='.$_course['code'].'&action=wanted&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('WantedPages').'</a></li>';
        // Submenu Most linked pages
        echo '<li><a href="index.php?cidReq='.$_course['code'].'&action=mostlinked&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('MostLinkedPages').'</a></li>';
        echo '</ul>';
        echo '</td>';
        echo '<td style="vertical-align:top">';
        echo '<ul>';
        // Submenu Statistics
        if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
            echo '<li><a href="index.php?cidReq='.$_course['id'].'&action=statistics&session_id='.$session_id.'&group_id='.$groupId.'">'.get_lang('Statistics').'</a></li>';
        }
        echo '      </ul>';
        echo'    </td>';
        echo '  </tr>';
        echo '</table>';
    }

    /**
     * Kind of controller
     * @param string $action
     */
    public function handleAction($action)
    {
        $page = $this->page;

        switch ($action) {
            case 'export_to_pdf':
                if (isset($_GET['wiki_id'])) {
                    self::export_to_pdf($_GET['wiki_id'], api_get_course_id());
                    exit;
                }
                break;
            case 'export2doc':
                if (isset($_GET['doc_id'])) {
                    $export2doc = self::export2doc($_GET['doc_id']);
                    if ($export2doc) {
                        self::setMessage(
                            Display::display_confirmation_message(
                                get_lang('ThePageHasBeenExportedToDocArea'),
                                false,
                                true
                            )
                        );
                    }
                }
                break;
            case 'restorepage':
                self::restorePage();
                break;
            case 'more':
                self::getStatsTable();
                break;
            case 'statistics':
                self::getStats();
                break;
            case 'mactiveusers':
                self::getActiveUsers($action);
                break;
            case 'usercontrib':
                self::getUserContributions($_GET['user_id'], $action);
                break;
            case 'mostchanged':
                $this->getMostChangedPages($action);
                break;
            case 'mvisited':
                self::getMostVisited();
                break;
            case 'wanted':
                $this->getWantedPages();
                break;
            case 'orphaned':
                self::getOrphaned();
                break;
            case 'mostlinked':
                self::getMostLinked();
                break;
            case 'delete':
                self::deletePageWarning($page);
                break;
            case 'deletewiki':
                $title = '<div class="actions">'.get_lang('DeleteWiki').'</div>';
                if (api_is_allowed_to_edit(false,true) || api_is_platform_admin()) {
                    $message = get_lang('ConfirmDeleteWiki');
                    $message .= '<p>
                        <a href="index.php">'.get_lang('No').'</a>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        <a href="'.api_get_self().'?action=deletewiki&amp;delete=yes">'.get_lang('Yes').'</a>
                    </p>';

                    if (!isset($_GET['delete'])) {
                        self::setMessage($title.Display::display_warning_message($message, false, true));
                    }
                } else {
                    self::setMessage(Display::display_normal_message(get_lang("OnlyAdminDeleteWiki"), false, true));
                }

                if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                    if (isset($_GET['delete']) && $_GET['delete'] == 'yes') {
                        $return_message = self::delete_wiki();
                        self::setMessage(Display::display_confirmation_message($return_message, false, true));
                        $this->redirectHome();
                    }
                }
                break;
            case 'searchpages':
                self::getSearchPages($action);
                break;
            case 'links':
                self::getLinks($page);
                break;
            case 'addnew':
                if (api_get_session_id()!=0 && api_is_allowed_to_session_edit(false,true)==false) {
                    api_not_allowed();
                }
                echo '<div class="actions">'.get_lang('AddNew').'</div>';
                echo '<br/>';
                //first, check if page index was created. chektitle=false
                if (self::checktitle('index')) {
                    if (api_is_allowed_to_edit(false,true) ||
                        api_is_platform_admin() ||
                        GroupManager::is_user_in_group(api_get_user_id(), api_get_group_id())
                    ) {
                        self::setMessage(Display::display_normal_message(get_lang('GoAndEditMainPage'), false, true));
                    } else {
                        self::setMessage(Display::display_normal_message(get_lang('WikiStandBy'), false, true));
                    }
                } elseif (self::check_addnewpagelock()==0 && (api_is_allowed_to_edit(false, true)==false || api_is_platform_admin()==false)) {
                    self::setMessage(Display::display_error_message(get_lang('AddPagesLocked'), false, true));
                } else {
                    if (api_is_allowed_to_edit(false,true) ||
                        api_is_platform_admin() ||
                        GroupManager::is_user_in_group(api_get_user_id(), api_get_group_id()) ||
                        $_GET['group_id'] == 0
                    ) {
                        self::display_new_wiki_form();
                    } else {
                        self::setMessage(Display::display_normal_message(get_lang('OnlyAddPagesGroupMembers'), false, true));
                    }
                }
                break;
            case 'show':
                self::display_wiki_entry($page);
                break;
            case 'showpage':
                self::display_wiki_entry($page);
                //self::setMessage(Display::display_error_message(get_lang('MustSelectPage'));
                break;
            case 'edit':
                self::editPage();
                break;
            case 'history':
                self::getHistory();
                break;
            case 'recentchanges':
                self::recentChanges($page, $action);
                break;
            case 'allpages':
                self::allPages($action);
                break;
            case 'discuss':
                //self::setMessage(Display::display_confirmation_message(get_lang('CommentAdded'));
                self::getDiscuss($page);
                break;
            case 'export_to_doc_file':
                self::exportTo($_GET['id'], 'odt');
                exit;
                break;
        }
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $messagesArray = Session::read('wiki_message');
        if (empty($messagesArray)) {
            $messagesArray = array($message);
        } else {
            $messagesArray[] = $message;
        }

        Session::write('wiki_message', $messagesArray);
    }

    /**
     *  Get messages
     * @return string
     */
    public function getMessages()
    {
        $messagesArray = Session::read('wiki_message');
        $messageToString = null;
        if (!empty($messagesArray)) {
            foreach ($messagesArray as $message) {
                $messageToString .= $message.'<br />';
            }
        }
        Session::erase('wiki_message');

        return $messageToString;
    }

    /**
     * Redirect to home
     */
    public function redirectHome()
    {
        $redirectUrl = $this->url.'&action=showpage&title=index';
        header('Location: '.$redirectUrl);
        exit;
    }

    /**
     * Export wiki content in a odf
     * @param int $id
     * @param string int
     * @return bool
     */
    public function exportTo($id, $format = 'doc')
    {
        $data = self::get_wiki_data($id);
        if (!empty($data['content'])) {
            global $app;
            $content = $app['chamilo.filesystem']->convertRelativeToAbsoluteUrl($data['content']);
            $filePath = $app['chamilo.filesystem']->putContentInTempFile($content, $data['reflink'], 'html');
            $convertedFile = $app['chamilo.filesystem']->transcode($filePath, $format);

            DocumentManager::file_send_for_download($convertedFile);
        }
        return false;
    }
}

