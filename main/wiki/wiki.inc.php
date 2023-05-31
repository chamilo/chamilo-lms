<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Entity\CWikiCategory;
use ChamiloSession as Session;
use Doctrine\DBAL\Driver\Statement;

/**
 * Class Wiki
 * Functions library for the wiki tool.
 *
 * @author Juan Carlos Raña <herodoto@telefonica.net>
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @author Julio Montoya <gugli100@gmail.com> using the pdf.lib.php library
 */
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
    public $wikiData = [];
    public $url;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Database table definition
        $this->tbl_wiki = Database::get_course_table(TABLE_WIKI);
        $this->tbl_wiki_discuss = Database::get_course_table(
            TABLE_WIKI_DISCUSS
        );
        $this->tbl_wiki_mailcue = Database::get_course_table(
            TABLE_WIKI_MAILCUE
        );
        $this->tbl_wiki_conf = Database::get_course_table(TABLE_WIKI_CONF);

        $this->session_id = api_get_session_id();
        $this->condition_session = api_get_session_condition($this->session_id);
        $this->course_id = api_get_course_int_id();
        $this->group_id = api_get_group_id();

        if (!empty($this->group_id)) {
            $this->groupfilter = ' group_id="'.$this->group_id.'"';
        }
        $this->courseInfo = api_get_course_info();
        $this->courseCode = api_get_course_id();
        $this->url = api_get_path(WEB_CODE_PATH).'wiki/index.php?'.api_get_cidreq();
    }

    /**
     * Check whether this title is already used.
     *
     * @param string $link
     *
     * @return bool False if title is already taken
     *
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
     */
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
        $num = Database::num_rows($result);
        // the value has not been found and is this available
        if ($num == 0) {
            return true;
        }

        return false;
    }

    /**
     * check wikilinks that has a page.
     *
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     *
     * @param string $input
     *
     * @return string
     */
    public function links_to($input)
    {
        $input_array = preg_split(
            "/(\[\[|\]\])/",
            $input,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        $all_links = [];

        foreach ($input_array as $key => $value) {
            if (isset($input_array[$key - 1]) && $input_array[$key - 1] == '[[' &&
                isset($input_array[$key + 1]) && $input_array[$key + 1] == ']]'
            ) {
                if (api_strpos($value, "|") !== false) {
                    $full_link_array = explode("|", $value);
                    $link = trim($full_link_array[0]);
                    $title = trim($full_link_array[1]);
                } else {
                    $link = trim($value);
                    $title = trim($value);
                }
                unset($input_array[$key - 1]);
                unset($input_array[$key + 1]);
                //replace blank spaces by _ within the links. But to remove links at the end add a blank space
                $all_links[] = Database::escape_string(
                    str_replace(' ', '_', $link)
                ).' ';
            }
        }

        return implode($all_links);
    }

    /**
     * detect and add style to external links.
     *
     * @author Juan Carlos Raña Trabado
     */
    public function detect_external_link($input)
    {
        $exlink = 'href=';
        $exlinkStyle = 'class="wiki_link_ext" href=';

        return str_replace($exlink, $exlinkStyle, $input);
    }

    /**
     * detect and add style to anchor links.
     *
     * @author Juan Carlos Raña Trabado
     */
    public function detect_anchor_link($input)
    {
        $anchorlink = 'href="#';
        $anchorlinkStyle = 'class="wiki_anchor_link" href="#';
        $output = str_replace($anchorlink, $anchorlinkStyle, $input);

        return $output;
    }

    /**
     * detect and add style to mail links
     * author Juan Carlos Raña Trabado.
     */
    public function detect_mail_link($input)
    {
        $maillink = 'href="mailto';
        $maillinkStyle = 'class="wiki_mail_link" href="mailto';
        $output = str_replace($maillink, $maillinkStyle, $input);

        return $output;
    }

    /**
     * detect and add style to ftp links.
     *
     * @author Juan Carlos Raña Trabado
     */
    public function detect_ftp_link($input)
    {
        $ftplink = 'href="ftp';
        $ftplinkStyle = 'class="wiki_ftp_link" href="ftp';
        $output = str_replace($ftplink, $ftplinkStyle, $input);

        return $output;
    }

    /**
     * detect and add style to news links.
     *
     * @author Juan Carlos Raña Trabado
     */
    public function detect_news_link($input)
    {
        $newslink = 'href="news';
        $newslinkStyle = 'class="wiki_news_link" href="news';
        $output = str_replace($newslink, $newslinkStyle, $input);

        return $output;
    }

    /**
     * detect and add style to irc links.
     *
     * @author Juan Carlos Raña Trabado
     */
    public function detect_irc_link($input)
    {
        $irclink = 'href="irc';
        $irclinkStyle = 'class="wiki_irc_link" href="irc';
        $output = str_replace($irclink, $irclinkStyle, $input);

        return $output;
    }

    /**
     * This function allows users to have [link to a title]-style links like in most regular wikis.
     * It is true that the adding of links is probably the most anoying part of Wiki for the people
     * who know something about the wiki syntax.
     *
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
     * Improvements [[]] and [[ | ]]by Juan Carlos Raña
     * Improvements internal wiki style and mark group by Juan Carlos Raña
     */
    public function make_wiki_link_clickable($input)
    {
        //now doubles brackets
        $input_array = preg_split(
            "/(\[\[|\]\])/",
            $input,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        foreach ($input_array as $key => $value) {
            //now doubles brackets
            if (isset($input_array[$key - 1]) &&
                $input_array[$key - 1] == '[[' && $input_array[$key + 1] == ']]'
            ) {
                // now full wikilink
                if (api_strpos($value, "|") !== false) {
                    $full_link_array = explode("|", $value);
                    $link = trim(strip_tags($full_link_array[0]));
                    $title = trim($full_link_array[1]);
                } else {
                    $link = trim(strip_tags($value));
                    $title = trim($value);
                }

                //if wikilink is homepage
                if ($link == 'index') {
                    $title = get_lang('DefaultTitle');
                }
                if ($link == get_lang('DefaultTitle')) {
                    $link = 'index';
                }

                // note: checkreflink checks if the link is still free. If it is not used then it returns true, if it is used, then it returns false. Now the title may be different
                if (self::checktitle(
                    strtolower(str_replace(' ', '_', $link))
                )) {
                    $link = api_html_entity_decode($link);
                    $input_array[$key] = '<a href="'.$this->url.'&action=addnew&title='.Security::remove_XSS($link).'" class="new_wiki_link">'.$title.'</a>';
                } else {
                    $input_array[$key] = '<a href="'.$this->url.'&action=showpage&title='.urlencode(strtolower(str_replace(' ', '_', $link))).'" class="wiki_link">'.$title.'</a>';
                }
                unset($input_array[$key - 1]);
                unset($input_array[$key + 1]);
            }
        }
        $output = implode('', $input_array);

        return $output;
    }

    /**
     * This function saves a change in a wiki page.
     *
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
     *
     * @param array $values
     *
     * @return string
     */
    public function save_wiki($values)
    {
        $tbl_wiki = $this->tbl_wiki;
        $tbl_wiki_conf = $this->tbl_wiki_conf;

        $_course = $this->courseInfo;
        $time = api_get_utc_datetime(null, false, true);
        $userId = api_get_user_id();
        $groupInfo = GroupManager::get_group_properties($this->group_id);

        $_clean = [
            'task' => '',
            'feedback1' => '',
            'feedback2' => '',
            'feedback3' => '',
            'fprogress1' => '',
            'fprogress2' => '',
            'fprogress3' => '',
            'max_text' => 0,
            'max_version' => 0,
            'delayedsubmit' => '',
            'assignment' => 0,
        ];

        $pageId = intval($values['page_id']);

        // NOTE: visibility, visibility_disc and ratinglock_disc changes
        // are not made here, but through the interce buttons

        // cleaning the variables
        if (api_get_setting('htmlpurifier_wiki') == 'true') {
            //$purifier = new HTMLPurifier();
            $values['content'] = Security::remove_XSS($values['content']);
        }
        $version = intval($values['version']) + 1;
        $linkTo = self::links_to($values['content']); //and check links content

        //cleaning config variables
        if (!empty($values['task'])) {
            $_clean['task'] = $values['task'];
        }

        if (!empty($values['feedback1']) ||
            !empty($values['feedback2']) ||
            !empty($values['feedback3'])
        ) {
            $_clean['feedback1'] = $values['feedback1'];
            $_clean['feedback2'] = $values['feedback2'];
            $_clean['feedback3'] = $values['feedback3'];
            $_clean['fprogress1'] = $values['fprogress1'];
            $_clean['fprogress2'] = $values['fprogress2'];
            $_clean['fprogress3'] = $values['fprogress3'];
        }

        if (isset($values['initstartdate']) && $values['initstartdate'] == 1) {
            $_clean['startdate_assig'] = $values['startdate_assig'];
        } else {
            $_clean['startdate_assig'] = null;
        }

        if (isset($values['initenddate']) && $values['initenddate'] == 1) {
            $_clean['enddate_assig'] = $values['enddate_assig'];
        } else {
            $_clean['enddate_assig'] = null;
        }

        if (isset($values['delayedsubmit'])) {
            $_clean['delayedsubmit'] = $values['delayedsubmit'];
        }

        if (!empty($values['max_text']) || !empty($values['max_version'])) {
            $_clean['max_text'] = $values['max_text'];
            $_clean['max_version'] = $values['max_version'];
        }

        $values['assignment'] = $values['assignment'] ?? 0;
        $values['page_id'] = $values['page_id'] ?? 0;

        $em = Database::getManager();

        $newWiki = (new CWiki())
            ->setCId($this->course_id)
            ->setAddlock(1)
            ->setVisibility(1)
            ->setVisibilityDisc(1)
            ->setAddlockDisc(1)
            ->setRatinglockDisc(1)
            ->setPageId($pageId)
            ->setReflink(trim($values['reflink']))
            ->setTitle(trim($values['title']))
            ->setContent($values['content'])
            ->setUserId($userId)
            ->setGroupId($this->group_id)
            ->setDtime($time)
            ->setAssignment($values['assignment'])
            ->setComment($values['comment'])
            ->setProgress($values['progress'])
            ->setVersion($version)
            ->setLinksto($linkTo)
            ->setUserIp(api_get_real_ip())
            ->setSessionId($this->session_id)
            ->setPageId($values['page_id'])
            ->setEditlock(0)
            ->setIsEditing(0)
            ->setTimeEdit($time)
            ->setTag('')
        ;

        $em->persist($newWiki);
        $em->flush();

        $id = $newWiki->getIid();

        if ($id > 0) {
            $sql = "UPDATE $tbl_wiki SET id = iid WHERE iid = $id";
            Database::query($sql);

            // insert into item_property
            api_item_property_update(
                $_course,
                TOOL_WIKI,
                $id,
                'WikiAdded',
                $userId,
                $groupInfo
            );

            if ($values['page_id'] == 0) {
                $sql = 'UPDATE '.$tbl_wiki.' SET page_id="'.$id.'"
                        WHERE c_id = '.$this->course_id.' AND iid ="'.$id.'"';
                Database::query($sql);
            }

            self::assignCategoriesToWiki($newWiki, $values['category'] ?? []);
        }

        // Update wiki config
        if ($values['reflink'] == 'index' && $version == 1) {
            $params = [
                'c_id' => $this->course_id,
                'page_id' => $id,
                'task' => $_clean['task'],
                'feedback1' => $_clean['feedback1'],
                'feedback2' => $_clean['feedback2'],
                'feedback3' => $_clean['feedback3'],
                'fprogress1' => $_clean['fprogress1'],
                'fprogress2' => $_clean['fprogress2'],
                'fprogress3' => $_clean['fprogress3'],
                'max_text' => intval($_clean['max_text']),
                'max_version' => intval($_clean['max_version']),
                'startdate_assig' => $_clean['startdate_assig'],
                'enddate_assig' => $_clean['enddate_assig'],
                'delayedsubmit' => $_clean['delayedsubmit'],
            ];
            Database::insert($tbl_wiki_conf, $params);
        } else {
            $params = [
                'task' => $_clean['task'],
                'feedback1' => $_clean['feedback1'],
                'feedback2' => $_clean['feedback2'],
                'feedback3' => $_clean['feedback3'],
                'fprogress1' => $_clean['fprogress1'],
                'fprogress2' => $_clean['fprogress2'],
                'fprogress3' => $_clean['fprogress3'],
                'max_text' => intval($_clean['max_text']),
                'max_version' => intval($_clean['max_version']),
                'startdate_assig' => $_clean['startdate_assig'],
                'enddate_assig' => $_clean['enddate_assig'],
                'delayedsubmit' => $_clean['delayedsubmit'],
            ];
            Database::update(
                $tbl_wiki_conf,
                $params,
                ['page_id = ? AND c_id = ?' => [$pageId, $this->course_id]]
            );
        }

        api_item_property_update(
            $_course,
            'wiki',
            $id,
            'WikiAdded',
            $userId,
            $groupInfo
        );
        self::check_emailcue($_clean['reflink'], 'P', $time, $userId);
        $this->setWikiData($id);

        return get_lang('Saved');
    }

    /**
     * This function restore a wikipage.
     *
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     *
     * @return string Message of success (to be printed on screen)
     */
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
        $_course = $this->courseInfo;
        $r_user_id = api_get_user_id();
        $r_dtime = api_get_utc_datetime();
        $dTime = api_get_utc_datetime(null, false, true);
        $r_version = $r_version + 1;
        $r_comment = get_lang('RestoredFromVersion').': '.$c_version;
        $groupInfo = GroupManager::get_group_properties($r_group_id);

        $em = Database::getManager();

        $newWiki = (new CWiki())
            ->setCId($this->course_id)
            ->setPageId($r_page_id)
            ->setReflink($r_reflink)
            ->setTitle($r_title)
            ->setContent($r_content)
            ->setUserId($r_user_id)
            ->setGroupId($r_group_id)
            ->setDtime($dTime)
            ->setAssignment($r_assignment)
            ->setComment($r_comment)
            ->setProgress($r_progress)
            ->setVersion($r_version)
            ->setLinksto($r_linksto)
            ->setUserIp(api_get_real_ip())
            ->setSessionId($this->session_id)
            ->setAddlock(0)
            ->setEditlock(0)
            ->setVisibility(0)
            ->setAddlockDisc(0)
            ->setVisibilityDisc(0)
            ->setRatinglockDisc(0)
            ->setIsEditing(0)
            ->setTag('')
        ;

        $em->persist($newWiki);
        $em->flush();

        $newWiki->setId(
            $newWiki->getIid()
        );

        $em->flush();

        api_item_property_update(
            $_course,
            'wiki',
            $newWiki->getIid(),
            'WikiAdded',
            api_get_user_id(),
            $groupInfo
        );
        self::check_emailcue($r_reflink, 'P', $r_dtime, $r_user_id);

        return get_lang('PageRestored');
    }

    /**
     * This function delete a wiki.
     *
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     *
     * @return string Message of success (to be printed)
     */
    public function delete_wiki()
    {
        $tbl_wiki = $this->tbl_wiki;
        $tbl_wiki_discuss = $this->tbl_wiki_discuss;
        $tbl_wiki_mailcue = $this->tbl_wiki_mailcue;
        $tbl_wiki_conf = $this->tbl_wiki_conf;
        $conditionSession = $this->condition_session;
        $groupFilter = $this->groupfilter;

        $sql = "SELECT page_id FROM $tbl_wiki
                WHERE c_id = ".$this->course_id." AND $groupFilter $conditionSession
                ORDER BY id DESC";

        $result = Database::query($sql);
        $pageList = Database::store_result($result);
        if ($pageList) {
            foreach ($pageList as $pageData) {
                $pageId = $pageData['page_id'];
                $sql = "DELETE FROM $tbl_wiki_conf
                        WHERE c_id = ".$this->course_id." AND page_id = $pageId";
                Database::query($sql);

                $sql = "DELETE FROM $tbl_wiki_discuss
                        WHERE c_id = ".$this->course_id." AND publication_id = $pageId";
                Database::query($sql);
            }
        }

        $sql = "DELETE FROM $tbl_wiki_mailcue
                WHERE c_id = ".$this->course_id." AND $groupFilter $conditionSession ";
        Database::query($sql);

        $sql = "DELETE FROM $tbl_wiki
                WHERE c_id = ".$this->course_id." AND $groupFilter $conditionSession ";
        Database::query($sql);

        return get_lang('WikiDeleted');
    }

    /**
     * This function saves a new wiki page.
     *
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
     *
     * @todo consider merging this with the function save_wiki into one single function.
     */
    public function save_new_wiki($values)
    {
        $tbl_wiki = $this->tbl_wiki;
        $tbl_wiki_conf = $this->tbl_wiki_conf;
        $assig_user_id = $this->assig_user_id;
        $_clean = [];

        // cleaning the variables
        $_clean['assignment'] = '';
        if (isset($values['assignment'])) {
            $_clean['assignment'] = $values['assignment'];
        }

        // Unlike ordinary pages of pages of assignments.
        // Allow create a ordinary page although there is a assignment with the same name
        if ($_clean['assignment'] == 2 || $_clean['assignment'] == 1) {
            $page = str_replace(
                ' ',
                '_',
                $values['title']."_uass".$assig_user_id
            );
        } else {
            $page = str_replace(' ', '_', $values['title']);
        }
        $_clean['reflink'] = $page;
        $_clean['title'] = trim($values['title']);
        $_clean['content'] = $values['content'];

        if (api_get_setting('htmlpurifier_wiki') === 'true') {
            $purifier = new HTMLPurifier();
            $_clean['content'] = $purifier->purify($_clean['content']);
        }

        //re-check after strip_tags if the title is empty
        if (empty($_clean['title']) || empty($_clean['reflink'])) {
            return false;
        }

        if ($_clean['assignment'] == 2) {
            //config by default for individual assignment (students)
            //Identifies the user as a creator, not the teacher who created
            $_clean['user_id'] = intval($assig_user_id);
            $_clean['visibility'] = 0;
            $_clean['visibility_disc'] = 0;
            $_clean['ratinglock_disc'] = 0;
        } else {
            $_clean['user_id'] = api_get_user_id();
            $_clean['visibility'] = 1;
            $_clean['visibility_disc'] = 1;
            $_clean['ratinglock_disc'] = 1;
        }

        $_clean['comment'] = $values['comment'];
        $_clean['progress'] = $values['progress'];
        $_clean['version'] = 1;

        $groupInfo = GroupManager::get_group_properties($this->group_id);

        //check wikilinks
        $_clean['linksto'] = self::links_to($_clean['content']);

        // cleaning config variables
        $_clean['task'] = $values['task'] ?? '';
        $_clean['feedback1'] = $values['feedback1'] ?? '';
        $_clean['feedback2'] = $values['feedback2'] ?? '';
        $_clean['feedback3'] = $values['feedback3'] ?? '';
        $_clean['fprogress1'] = $values['fprogress1'] ?? '';
        $_clean['fprogress2'] = $values['fprogress2'] ?? '';
        $_clean['fprogress3'] = $values['fprogress3'] ?? '';

        if (isset($values['initstartdate']) && $values['initstartdate'] == 1) {
            $_clean['startdate_assig'] = $values['startdate_assig'];
        } else {
            $_clean['startdate_assig'] = null;
        }

        if (isset($values['initenddate']) && $values['initenddate'] == 1) {
            $_clean['enddate_assig'] = $values['enddate_assig'];
        } else {
            $_clean['enddate_assig'] = null;
        }

        $_clean['delayedsubmit'] = $values['delayedsubmit'] ?? '';
        $_clean['max_text'] = $values['max_text'] ?? '';
        $_clean['max_version'] = $values['max_version'] ?? '';

        // Filter no _uass
        if (api_strtoupper(trim($values['title'])) === 'INDEX') {
            Display::addFlash(
                Display::return_message(
                    get_lang('GoAndEditMainPage'),
                    'warning',
                    false
                )
            );
        } else {
            $var = $_clean['reflink'];
            if (!self::checktitle($var)) {
                return get_lang('WikiPageTitleExist').
                    '<a href="'.$this->url.'&action=edit&title='.$var.'">'.
                    $values['title'].'</a>';
            } else {
                $em = Database::getManager();
                $dtime = api_get_utc_datetime(null, false, true);

                $newWiki = (new CWiki())
                    ->setCId($this->course_id)
                    ->setReflink($_clean['reflink'])
                    ->setTitle($_clean['title'])
                    ->setContent($_clean['content'])
                    ->setUserId($_clean['user_id'])
                    ->setGroupId($this->group_id)
                    ->setDtime($dtime)
                    ->setVisibility($_clean['visibility'])
                    ->setVisibilityDisc($_clean['visibility_disc'])
                    ->setRatinglockDisc($_clean['ratinglock_disc'])
                    ->setAssignment($_clean['assignment'])
                    ->setComment($_clean['comment'])
                    ->setProgress($_clean['progress'])
                    ->setVersion($_clean['version'])
                    ->setLinksto($_clean['linksto'])
                    ->setUserIp(api_get_real_ip())
                    ->setSessionId($this->session_id)
                    ->setAddlock(0)
                    ->setAddlockDisc(1)
                    ->setEditlock(0)
                    ->setIsEditing(0)
                    ->setTag('')
                ;

                $em->persist($newWiki);
                $em->flush();

                $id = $newWiki->getIid();

                if ($id > 0) {
                    $sql = "UPDATE $tbl_wiki SET id = iid WHERE iid = $id";
                    Database::query($sql);

                    //insert into item_property
                    api_item_property_update(
                        $this->courseInfo,
                        TOOL_WIKI,
                        $id,
                        'WikiAdded',
                        api_get_user_id(),
                        $groupInfo
                    );

                    $sql = 'UPDATE '.$tbl_wiki.' SET page_id="'.$id.'"
                            WHERE c_id = '.$this->course_id.' AND id = "'.$id.'"';
                    Database::query($sql);

                    // insert wiki config
                    $params = [
                        'c_id' => $this->course_id,
                        'page_id' => $id,
                        'task' => $_clean['task'],
                        'feedback1' => $_clean['feedback1'],
                        'feedback2' => $_clean['feedback2'],
                        'feedback3' => $_clean['feedback3'],
                        'fprogress1' => $_clean['fprogress1'],
                        'fprogress2' => $_clean['fprogress2'],
                        'fprogress3' => $_clean['fprogress3'],
                        'max_text' => $_clean['max_text'],
                        'max_version' => $_clean['max_version'],
                        'startdate_assig' => $_clean['startdate_assig'],
                        'enddate_assig' => $_clean['enddate_assig'],
                        'delayedsubmit' => $_clean['delayedsubmit'],
                    ];

                    Database::insert($tbl_wiki_conf, $params);

                    self::assignCategoriesToWiki($newWiki, $values['category'] ?? []);

                    $this->setWikiData($id);
                    self::check_emailcue(0, 'A');

                    return get_lang('NewWikiSaved');
                }
            }
        }
    }

    public function setForm(FormValidator $form, array $row = [])
    {
        $toolBar = api_is_allowed_to_edit(null, true)
            ? [
                'ToolbarSet' => 'Wiki',
                'Width' => '100%',
                'Height' => '400',
            ]
            : [
                'ToolbarSet' => 'WikiStudent',
                'Width' => '100%',
                'Height' => '400',
                'UserStatus' => 'student',
            ];

        $form->addHtmlEditor(
            'content',
            get_lang('Content'),
            false,
            false,
            $toolBar
        );
        //$content
        $form->addElement('text', 'comment', get_lang('Comments'));
        $progress = ['', 10, 20, 30, 40, 50, 60, 70, 80, 90, 100];

        $form->addElement(
            'select',
            'progress',
            get_lang('Progress'),
            $progress
        );

        if (true === api_get_configuration_value('wiki_categories_enabled')) {
            $em = Database::getManager();

            $categories = $em->getRepository(CWikiCategory::class)
                ->findByCourse(
                    api_get_course_entity(),
                    api_get_session_entity()
                );

            $form->addSelectFromCollection(
                'category',
                get_lang('Categories'),
                $categories,
                ['multiple' => 'multiple'],
                false,
                'getNodeName'
            );
        }

        if ((api_is_allowed_to_edit(false, true) ||
            api_is_platform_admin()) &&
            isset($row['reflink']) && $row['reflink'] != 'index'
        ) {
            $form->addElement(
                'advanced_settings',
                'advanced_params',
                get_lang('AdvancedParameters')
            );
            $form->addElement(
                'html',
                '<div id="advanced_params_options" style="display:none">'
            );

            $form->addHtmlEditor(
                'task',
                get_lang('DescriptionOfTheTask'),
                false,
                false,
                [
                    'ToolbarSet' => 'wiki_task',
                    'Width' => '100%',
                    'Height' => '200',
                ]
            );

            $form->addElement('label', null, get_lang('AddFeedback'));
            $form->addElement('textarea', 'feedback1', get_lang('Feedback1'));
            $form->addElement(
                'select',
                'fprogress1',
                get_lang('FProgress'),
                $progress
            );

            $form->addElement('textarea', 'feedback2', get_lang('Feedback2'));
            $form->addElement(
                'select',
                'fprogress2',
                get_lang('FProgress'),
                $progress
            );

            $form->addElement('textarea', 'feedback3', get_lang('Feedback3'));
            $form->addElement(
                'select',
                'fprogress3',
                get_lang('FProgress'),
                $progress
            );

            $form->addElement(
                'checkbox',
                'initstartdate',
                null,
                get_lang('StartDate'),
                ['id' => 'start_date_toggle']
            );

            $style = "display:block";
            $row['initstartdate'] = 1;
            if (empty($row['startdate_assig'])) {
                $style = "display:none";
                $row['initstartdate'] = null;
            }

            $form->addElement(
                'html',
                '<div id="start_date" style="'.$style.'">'
            );
            $form->addDatePicker('startdate_assig', '');
            $form->addElement('html', '</div>');
            $form->addElement(
                'checkbox',
                'initenddate',
                null,
                get_lang('EndDate'),
                ['id' => 'end_date_toggle']
            );

            $style = "display:block";
            $row['initenddate'] = 1;
            if (empty($row['enddate_assig'])) {
                $style = "display:none";
                $row['initenddate'] = null;
            }

            $form->addHtml('<div id="end_date" style="'.$style.'">');
            $form->addDatePicker('enddate_assig', '');
            $form->addHtml('</div>');
            $form->addElement(
                'checkbox',
                'delayedsubmit',
                null,
                get_lang('AllowLaterSends')
            );
            $form->addElement('text', 'max_text', get_lang('NMaxWords'));
            $form->addElement('text', 'max_version', get_lang('NMaxVersion'));
            $form->addElement(
                'checkbox',
                'assignment',
                null,
                get_lang('CreateAssignmentPage')
            );
            $form->addElement('html', '</div>');
        }

        $form->addElement('hidden', 'page_id');
        $form->addElement('hidden', 'reflink');
        $form->addElement('hidden', 'version');
        $form->addElement('hidden', 'wpost_id', api_get_unique_id());
    }

    /**
     * This function displays the form for adding a new wiki page.
     *
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
     *
     * @return string html code
     */
    public function display_new_wiki_form()
    {
        $url = $this->url.'&'.http_build_query(['action' => 'addnew']);
        $form = new FormValidator('wiki_new', 'post', $url);
        $form->addElement('text', 'title', get_lang('Title'));
        $form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');
        self::setForm($form);
        $title = isset($_GET['title']) ? Security::remove_XSS($_GET['title']) : '';
        $form->setDefaults(['title' => $title]);
        $form->addButtonSave(get_lang('Save'), 'SaveWikiNew');
        $form->display();

        if ($form->validate()) {
            $values = $form->exportValues();
            if (isset($values['startdate_assig']) &&
                isset($values['enddate_assig']) &&
                strtotime($values['startdate_assig']) > strtotime(
                    $values['enddate_assig']
                )
            ) {
                Display::addFlash(
                    Display::return_message(
                        get_lang("EndDateCannotBeBeforeTheStartDate"),
                        'error',
                        false
                    )
                );
            } elseif (!self::double_post($_POST['wpost_id'])) {
                //double post
            } else {
                if (isset($values['assignment']) && $values['assignment'] == 1) {
                    self::auto_add_page_users($values);
                }

                $return_message = $this->save_new_wiki($values);

                if ($return_message == false) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('NoWikiPageTitle'),
                            'error',
                            false
                        )
                    );
                } else {
                    Display::addFlash(
                        Display::return_message(
                            $return_message,
                            'confirmation',
                            false
                        )
                    );
                }

                $wikiData = self::getWikiData();
                $redirectUrl = $this->url.'&'
                    .http_build_query(['action' => 'showpage', 'title' => $wikiData['reflink']]);
                header('Location: '.$redirectUrl);
                exit;
            }
        }
    }

    /**
     * This function displays a wiki entry.
     *
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
     * @author Juan Carlos Raña Trabado
     */
    public function display_wiki_entry(string $newtitle)
    {
        $tblWiki = $this->tbl_wiki;
        $tblWikiConf = $this->tbl_wiki_conf;
        $conditionSession = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $page = $this->page;

        if ($newtitle) {
            $pageMIX = $newtitle; //display the page after it is created
        } else {
            $pageMIX = $page; //display current page
        }

        $filter = null;
        if (isset($_GET['view']) && $_GET['view']) {
            $_clean['view'] = Database::escape_string($_GET['view']);
            $filter = ' AND w.id="'.$_clean['view'].'"';
        }

        // First, check page visibility in the first page version
        $sql = 'SELECT * FROM '.$tblWiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    reflink = "'.Database::escape_string($pageMIX).'" AND
                   '.$groupfilter.$conditionSession.'
                ORDER BY id';
        $result = Database::query($sql);
        $row = Database::fetch_array($result, 'ASSOC');

        $KeyVisibility = null;
        if ($KeyVisibility) {
            $KeyVisibility = $row['visibility'];
        }

        // second, show the last version
        $sql = 'SELECT * FROM '.$tblWiki.' w
            INNER JOIN '.$tblWikiConf.' wc
            ON (wc.page_id = w.page_id AND wc.c_id = w.c_id)
            WHERE
                w.c_id = '.$this->course_id.' AND
                w.reflink = "'.Database::escape_string($pageMIX).'" AND
                w.session_id = '.$this->session_id.' AND
                w.'.$groupfilter.'  '.$filter.'
            ORDER BY id DESC';

        $result = Database::query($sql);
        // we do not need awhile loop since we are always displaying the last version
        $row = Database::fetch_array($result, 'ASSOC');

        //log users access to wiki (page_id)
        if (!empty($row['page_id'])) {
            Event::addEvent(LOG_WIKI_ACCESS, LOG_WIKI_PAGE_ID, $row['page_id']);
        }
        //update visits
        if ($row && $row['id']) {
            $sql = 'UPDATE '.$tblWiki.' SET hits=(hits+1)
                WHERE c_id = '.$this->course_id.' AND id='.$row['id'];
            Database::query($sql);
        }

        $groupInfo = GroupManager::get_group_properties($this->group_id);

        // if both are empty, and we are displaying the index page then we display the default text.
        if (!$row || ($row['content'] == '' && $row['title'] == '' && $page == 'index')) {
            if (api_is_allowed_to_edit(false, true) ||
                api_is_platform_admin() ||
                GroupManager::is_user_in_group(api_get_user_id(), $groupInfo) ||
                api_is_allowed_in_course()
            ) {
                //Table structure for better export to pdf
                $default_table_for_content_Start = '<div class="text-center">';
                $default_table_for_content_End = '</div>';
                $content = $default_table_for_content_Start.
                    sprintf(
                        get_lang('DefaultContent'),
                        api_get_path(WEB_IMG_PATH)
                    ).
                    $default_table_for_content_End;
                $title = get_lang('DefaultTitle');
            } else {
                Display::addFlash(
                    Display::return_message(
                        get_lang('WikiStandBy'),
                        'normal',
                        false
                    )
                );

                return;
            }
        } else {
            if (true === api_get_configuration_value('wiki_html_strict_filtering')) {
                $content = Security::remove_XSS($row['content'], COURSEMANAGERLOWSECURITY);
            } else {
                $content = Security::remove_XSS($row['content']);
            }
            $title = Security::remove_XSS($row['title']);
        }

        if (self::wiki_exist($title)) {
            //assignment mode: identify page type
            $icon_assignment = null;
            if ($row['assignment'] == 1) {
                $icon_assignment = Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'));
            } elseif ($row['assignment'] == 2) {
                $icon_assignment = Display::return_icon('wiki_work.png', get_lang('AssignmentWork'));
            }

            // task mode
            $icon_task = null;
            if (!empty($row['task'])) {
                $icon_task = Display::return_icon('wiki_task.png', get_lang('StandardTask'));
            }

            $pageTitle = $icon_assignment.PHP_EOL.$icon_task.'&nbsp;'.api_htmlentities($title);
        } else {
            $pageTitle = api_htmlentities($title);
        }

        // Show page. Show page to all users if isn't hide page. Mode assignments: if student is the author, can view
        if ($KeyVisibility != "1"
            && !api_is_allowed_to_edit(false, true)
            && !api_is_platform_admin()
            && ($row['assignment'] != 2 || $KeyVisibility != "0" || api_get_user_id() != $row['user_id'])
            && !api_is_allowed_in_course()
        ) {
            return;
        }

        $actionsLeft = '';
        $actionsRight = '';
        // menu edit page
        $editLink = '<a href="'.$this->url.'&action=edit&title='.api_htmlentities(urlencode($page)).'"'
            .self::is_active_navigation_tab('edit').'>'
            .Display::return_icon('edit.png', get_lang('EditThisPage'), [], ICON_SIZE_MEDIUM).'</a>';

        if (api_is_allowed_to_edit(false, true)) {
            $actionsLeft .= $editLink;
        } else {
            if ((api_is_allowed_in_course() ||
                GroupManager::is_user_in_group(
                    api_get_user_id(),
                    $groupInfo
                ))
            ) {
                $actionsLeft .= $editLink;
            } else {
                $actionsLeft .= '';
            }
        }

        $pageProgress = 0;
        $pageScore = 0;

        if ($row && $row['id']) {
            $pageProgress = $row['progress'] * 10;
            $pageScore = $row['score'];

            $protect_page = null;
            $lock_unlock_protect = null;
            // page action: protecting (locking) the page
            if (api_is_allowed_to_edit(false, true) ||
                api_is_platform_admin()
            ) {
                if (self::check_protect_page() == 1) {
                    $protect_page = Display::return_icon(
                        'lock.png',
                        get_lang('PageLockedExtra'),
                        [],
                        ICON_SIZE_MEDIUM
                    );
                    $lock_unlock_protect = 'unlock';
                } else {
                    $protect_page = Display::return_icon(
                        'unlock.png',
                        get_lang('PageUnlockedExtra'),
                        [],
                        ICON_SIZE_MEDIUM
                    );
                    $lock_unlock_protect = 'lock';
                }
            }

            $actionsRight .= '<a href="'.$this->url.'&action=showpage&actionpage='.$lock_unlock_protect
                .'&title='.api_htmlentities(urlencode($page)).'">'.
            $protect_page.'</a>';

            $visibility_page = null;
            $lock_unlock_visibility = null;
            //page action: visibility
            if (api_is_allowed_to_edit(false, true) ||
                api_is_platform_admin()
            ) {
                if (self::check_visibility_page() == 1) {
                    $visibility_page = Display::return_icon(
                        'visible.png',
                        get_lang('ShowPageExtra'),
                        [],
                        ICON_SIZE_MEDIUM
                    );
                    $lock_unlock_visibility = 'invisible';
                } else {
                    $visibility_page = Display::return_icon(
                        'invisible.png',
                        get_lang('HidePageExtra'),
                        [],
                        ICON_SIZE_MEDIUM
                    );
                    $lock_unlock_visibility = 'visible';
                }
            }

            $actionsRight .= '<a href="'.$this->url.'&action=showpage&actionpage='
                .$lock_unlock_visibility.'&title='.api_htmlentities(urlencode($page)).'">'.$visibility_page.'</a>';

            // Only available if row['id'] is set
            //page action: notification
            $lock_unlock_notify_page = '';

            if (api_is_allowed_to_session_edit()) {
                if (self::check_notify_page($page) == 1) {
                    $notify_page = Display::return_icon(
                        'messagebox_info.png',
                        get_lang('NotifyByEmail'),
                        [],
                        ICON_SIZE_MEDIUM
                    );
                    $lock_unlock_notify_page = 'unlocknotify';
                } else {
                    $notify_page = Display::return_icon(
                        'mail.png',
                        get_lang('CancelNotifyByEmail'),
                        [],
                        ICON_SIZE_MEDIUM
                    );
                    $lock_unlock_notify_page = 'locknotify';
                }
            }

            if (api_is_allowed_to_session_edit(false, true)
                && api_is_allowed_to_edit()
                || GroupManager::is_user_in_group(api_get_user_id(), $groupInfo)
            ) {
                // menu discuss page
                $actionsRight .= '<a href="'.$this->url.'&action=discuss&title='
                    .api_htmlentities(urlencode($page)).'" '.self::is_active_navigation_tab('discuss').'>'
                    .Display::return_icon(
                        'discuss.png',
                        get_lang('DiscussThisPage'),
                        [],
                        ICON_SIZE_MEDIUM
                    ).'</a>';
            }

            //menu history
            $actionsRight .= '<a href="'.$this->url.'&action=history&title='
                .api_htmlentities(urlencode($page)).'" '.self::is_active_navigation_tab('history').'>'.
                Display::return_icon(
                    'history.png',
                    get_lang('ShowPageHistory'),
                    [],
                    ICON_SIZE_MEDIUM
                ).'</a>';
            //menu linkspages
            $actionsRight .= '<a href="'.$this->url.'&action=links&title='
                .api_htmlentities(urlencode($page)).'" '.self::is_active_navigation_tab('links').'>'
                .Display::return_icon(
                    'what_link_here.png',
                    get_lang('LinksPages'),
                    [],
                    ICON_SIZE_MEDIUM
                ).'</a>';

            //menu delete wikipage
            if (api_is_allowed_to_edit(false, true) ||
                api_is_platform_admin()
            ) {
                $actionsRight .= '<a href="'.$this->url.'&action=delete&title='
                    .api_htmlentities(urlencode($page)).'"'.self::is_active_navigation_tab('delete').'>'
                    .Display::return_icon(
                        'delete.png',
                        get_lang('DeleteThisPage'),
                        [],
                        ICON_SIZE_MEDIUM
                    ).'</a>';
            }

            $actionsRight .= '<a href="'.$this->url.'&action=showpage&actionpage='
                .$lock_unlock_notify_page.'&title='.api_htmlentities(urlencode($page)).'">'.$notify_page.'</a>';

            // Page action: copy last version to doc area
            if (api_is_allowed_to_edit(false, true) ||
                api_is_platform_admin()
            ) {
                $actionsRight .= '<a href="'.$this->url.'&action=export2doc&wiki_id='.$row['id'].'">'
                    .Display::return_icon(
                        'export_to_documents.png',
                        get_lang('ExportToDocArea'),
                        [],
                        ICON_SIZE_MEDIUM
                    ).'</a>';
            }

            $actionsRight .= '<a href="'.$this->url.'&action=export_to_pdf&wiki_id='.$row['id'].'">'
                .Display::return_icon(
                    'pdf.png',
                    get_lang('ExportToPDF'),
                    [],
                    ICON_SIZE_MEDIUM
                ).'</a>';

            $unoconv = api_get_configuration_value('unoconv.binaries');
            if ($unoconv) {
                $actionsRight .= Display::url(
                    Display::return_icon('export_doc.png', get_lang('ExportToDoc'), [], ICON_SIZE_MEDIUM),
                    $this->url.'&'.http_build_query(['action' => 'export_to_doc_file', 'id' => $row['id']])
                );
            }

            //export to print?>
            <script>
                function goprint() {
                    var a = window.open('', '', 'width=800,height=600');
                    a.document.open("text/html");
                    a.document.write($('#wikicontent .panel-heading').html());
                    a.document.write($('#wikicontent .panel-body').html());
                    a.document.close();
                    a.print();
                }
            </script>
            <?php
            $actionsRight .= Display::url(
                Display::return_icon(
                    'printer.png',
                    get_lang('Print'),
                    [],
                    ICON_SIZE_MEDIUM
                ),
                '#',
                ['onclick' => "javascript: goprint();"]
            );
        }

        $contentHtml = Display::toolbarAction(
            'toolbar-wikistudent',
            [$actionsLeft, $actionsRight],
            [3, 9]
        );

        $pageWiki = self::detect_news_link($content);
        $pageWiki = self::detect_irc_link($pageWiki);
        $pageWiki = self::detect_ftp_link($pageWiki);
        $pageWiki = self::detect_mail_link($pageWiki);
        $pageWiki = self::detect_anchor_link($pageWiki);
        $pageWiki = self::detect_external_link($pageWiki);
        $pageWiki = self::make_wiki_link_clickable($pageWiki);

        $footerWiki = '<ul class="list-inline" style="margin-bottom: 0;">'
            .'<li>'.get_lang('Progress').': '.$pageProgress.'%</li>'
            .'<li>'.get_lang('Rating').': '.$pageScore.'</li>'
            .'<li>'.get_lang('Words').': '.self::word_count($content).'</li>';

        $footerWiki .= $this->returnCategoriesBlock(
            !empty($row['id']) ? $row['id'] : 0,
            '<li class="pull-right">',
            '</li>'
        );

        $footerWiki .= '</ul>';
        // wikicontent require to print wiki document
        $contentHtml .= '<div id="wikicontent">'.Display::panel($pageWiki, $pageTitle, $footerWiki).'</div>'; //end filter visibility

        $this->renderShowPage($contentHtml);
    }

    /**
     * This function counted the words in a document. Thanks Adeel Khan.
     *
     * @param   string  Document's text
     *
     * @return int Number of words
     */
    public function word_count($document)
    {
        $search = [
            '@<script[^>]*?>.*?</script>@si',
            '@<style[^>]*?>.*?</style>@siU',
            '@<div id="player.[^>]*?>.*?</div>@',
            '@<![\s\S]*?--[ \t\n\r]*>@',
        ];

        $document = preg_replace($search, '', $document);

        // strip all html tags
        $wc = strip_tags($document);
        $wc = html_entity_decode(
            $wc,
            ENT_NOQUOTES,
            'UTF-8'
        ); // TODO:test also old html_entity_decode(utf8_encode($wc))

        // remove 'words' that don't consist of alphanumerical characters or punctuation. And fix accents and some letters
        $pattern = "#[^(\w|\d|\'|\"|\.|\!|\?|;|,|\\|\/|\-|:|\&|@|á|é|í|ó|ú|à|è|ì|ò|ù|ä|ë|ï|ö|ü|Á|É|Í|Ó|Ú|À|È|Ò|Ù|Ä|Ë|Ï|Ö|Ü|â|ê|î|ô|û|Â|Ê|Î|Ô|Û|ñ|Ñ|ç|Ç)]+#";
        $wc = trim(preg_replace($pattern, " ", $wc));

        // remove one-letter 'words' that consist only of punctuation
        $wc = trim(
            preg_replace(
                "#\s*[(\'|\"|\.|\!|\?|;|,|\\|\/|\-|:|\&|@)]\s*#",
                " ",
                $wc
            )
        );

        // remove superfluous whitespace
        $wc = preg_replace("/\s\s+/", " ", $wc);

        // split string into an array of words
        $wc = explode(" ", $wc);

        // remove empty elements
        $wc = array_filter($wc);

        // return the number of words
        return count($wc);
    }

    /**
     * This function checks if wiki title exist.
     */
    public function wiki_exist($title)
    {
        $tbl_wiki = $this->tbl_wiki;
        $groupfilter = $this->groupfilter;
        $condition_session = $this->condition_session;

        $sql = 'SELECT id FROM '.$tbl_wiki.'
              WHERE
                c_id = '.$this->course_id.' AND
                title="'.Database::escape_string($title).'" AND
                '.$groupfilter.$condition_session.'
              ORDER BY id ASC';
        $result = Database::query($sql);
        $cant = Database::num_rows($result);
        if ($cant > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if this navigation tab has to be set to active.
     *
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
     *
     * @return string html code
     */
    public function is_active_navigation_tab($paramwk)
    {
        if (isset($_GET['action']) && $_GET['action'] == $paramwk) {
            return ' class="active"';
        }
    }

    /**
     * Lock add pages.
     *
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     * return current database status of protect page and change it if get action
     */
    public function check_addnewpagelock()
    {
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;

        $sql = 'SELECT *
                FROM '.$tbl_wiki.'
                WHERE c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session.'
                ORDER BY id ASC';

        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        $status_addlock = null;
        if ($row) {
            $status_addlock = $row['addlock'];
        }

        // Change status
        if (api_is_allowed_to_edit(false, true) ||
            api_is_platform_admin()
        ) {
            if (isset($_GET['actionpage'])) {
                if ($_GET['actionpage'] == 'lockaddnew' && $status_addlock == 1) {
                    $status_addlock = 0;
                }
                if ($_GET['actionpage'] == 'unlockaddnew' && $status_addlock == 0) {
                    $status_addlock = 1;
                }
                $sql = 'UPDATE '.$tbl_wiki.' SET
                            addlock="'.Database::escape_string($status_addlock).'"
                        WHERE c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session;
                Database::query($sql);
            }

            $sql = 'SELECT *
                    FROM '.$tbl_wiki.'
                    WHERE c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session.'
                    ORDER BY id ASC';
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            if ($row) {
                return $row['addlock'];
            }
        }

        return null;
    }

    /**
     * Protect page.
     *
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     * return current database status of protect page and change it if get action
     */
    public function check_protect_page()
    {
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $page = $this->page;

        $sql = 'SELECT * FROM '.$tbl_wiki.'
              WHERE
                c_id = '.$this->course_id.' AND
                reflink="'.Database::escape_string($page).'" AND
                '.$groupfilter.$condition_session.'
              ORDER BY id ASC';

        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        if (!$row) {
            return 0;
        }

        $status_editlock = $row['editlock'];
        $id = $row['page_id'];

        // Change status
        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            if (isset($_GET['actionpage']) && $_GET['actionpage'] == 'lock' && $status_editlock == 0) {
                $status_editlock = 1;
            }
            if (isset($_GET['actionpage']) && $_GET['actionpage'] == 'unlock' && $status_editlock == 1) {
                $status_editlock = 0;
            }

            $sql = 'UPDATE '.$tbl_wiki.' SET
                    editlock="'.Database::escape_string($status_editlock).'"
                    WHERE c_id = '.$this->course_id.' AND page_id="'.$id.'"';
            Database::query($sql);

            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$this->course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                  ORDER BY id ASC';
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
        }

        //show status
        return (int) $row['editlock'];
    }

    /**
     * Visibility page.
     *
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     * return current database status of visibility and change it if get action
     */
    public function check_visibility_page()
    {
        $tbl_wiki = $this->tbl_wiki;
        $page = $this->page;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    reflink="'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        if (!$row) {
            return 0;
        }

        $status_visibility = $row['visibility'];
        //change status
        if (api_is_allowed_to_edit(false, true) ||
            api_is_platform_admin()
        ) {
            if (isset($_GET['actionpage']) &&
                $_GET['actionpage'] == 'visible' &&
                $status_visibility == 0
            ) {
                $status_visibility = 1;
            }
            if (isset($_GET['actionpage']) &&
                $_GET['actionpage'] == 'invisible' &&
                $status_visibility == 1
            ) {
                $status_visibility = 0;
            }

            $sql = 'UPDATE '.$tbl_wiki.' SET
                    visibility = "'.Database::escape_string($status_visibility).'"
                    WHERE
                        c_id = '.$this->course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                        '.$groupfilter.$condition_session;
            Database::query($sql);

            // Although the value now is assigned to all (not only the first),
            // these three lines remain necessary.
            // They do that by changing the page state is
            // made when you press the button and not have to wait to change his page
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$this->course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                        '.$groupfilter.$condition_session.'
                    ORDER BY id ASC';
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
        }

        if (empty($row['id'])) {
            $row['visibility'] = 1;
        }

        //show status
        return $row['visibility'];
    }

    /**
     * Visibility discussion.
     *
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     *
     * @return int current database status of discuss visibility
     *             and change it if get action page
     */
    public function check_visibility_discuss()
    {
        $tbl_wiki = $this->tbl_wiki;
        $page = $this->page;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    reflink="'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id ASC';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        $status_visibility_disc = $row['visibility_disc'];

        //change status
        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            if (isset($_GET['actionpage']) &&
                $_GET['actionpage'] == 'showdisc' &&
                $status_visibility_disc == 0
            ) {
                $status_visibility_disc = 1;
            }
            if (isset($_GET['actionpage']) &&
                $_GET['actionpage'] == 'hidedisc' &&
                $status_visibility_disc == 1
            ) {
                $status_visibility_disc = 0;
            }

            $sql = 'UPDATE '.$tbl_wiki.' SET
                    visibility_disc="'.Database::escape_string($status_visibility_disc).'"
                    WHERE
                        c_id = '.$this->course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                        '.$groupfilter.$condition_session;
            Database::query($sql);

            // Although the value now is assigned to all (not only the first),
            // these three lines remain necessary.
            // They do that by changing the page state is made when you press
            // the button and not have to wait to change his page
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$this->course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                        '.$groupfilter.$condition_session.'
                    ORDER BY id ASC';
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
        }

        return $row['visibility_disc'];
    }

    /**
     * Lock add discussion.
     *
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     *
     * @return int current database status of lock dicuss and change if get action
     */
    public function check_addlock_discuss()
    {
        $tbl_wiki = $this->tbl_wiki;
        $page = $this->page;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    reflink="'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id ASC';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        $status_addlock_disc = $row['addlock_disc'];

        //change status
        if (api_is_allowed_to_edit() || api_is_platform_admin()) {
            if (isset($_GET['actionpage']) &&
                $_GET['actionpage'] == 'lockdisc' &&
                $status_addlock_disc == 0
            ) {
                $status_addlock_disc = 1;
            }
            if (isset($_GET['actionpage']) &&
                $_GET['actionpage'] == 'unlockdisc' &&
                $status_addlock_disc == 1
            ) {
                $status_addlock_disc = 0;
            }

            $sql = 'UPDATE '.$tbl_wiki.' SET
                    addlock_disc="'.Database::escape_string($status_addlock_disc).'"
                    WHERE
                        c_id = '.$this->course_id.' AND
                        reflink = "'.Database::escape_string($page).'" AND
                         '.$groupfilter.$condition_session;
            Database::query($sql);

            // Although the value now is assigned to all (not only the first),
            // these three lines remain necessary.
            // They do that by changing the page state is made when you press
            // the button and not have to wait to change his page
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$this->course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                        '.$groupfilter.$condition_session.'
                    ORDER BY id ASC';
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
        }

        return $row['addlock_disc'];
    }

    /**
     * Lock rating discussion.
     *
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     *
     * @return int current database status of rating discuss and change it if get action
     */
    public function check_ratinglock_discuss()
    {
        $tbl_wiki = $this->tbl_wiki;
        $page = $this->page;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    reflink="'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id ASC';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $status_ratinglock_disc = $row['ratinglock_disc'];

        //change status
        if (api_is_allowed_to_edit(false, true) ||
            api_is_platform_admin()
        ) {
            if (isset($_GET['actionpage']) &&
                $_GET['actionpage'] == 'lockrating' &&
                $status_ratinglock_disc == 0
            ) {
                $status_ratinglock_disc = 1;
            }
            if (isset($_GET['actionpage']) &&
                $_GET['actionpage'] == 'unlockrating' &&
                $status_ratinglock_disc == 1
            ) {
                $status_ratinglock_disc = 0;
            }

            $sql = 'UPDATE '.$tbl_wiki.'
                    SET ratinglock_disc="'.Database::escape_string($status_ratinglock_disc).'"
                    WHERE
                        c_id = '.$this->course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                        '.$groupfilter.$condition_session;
            // Visibility. Value to all,not only for the first
            Database::query($sql);

            // Although the value now is assigned to all (not only the first),
            // these three lines remain necessary. They do that by changing the
            // page state is made when you press the button and not have to wait
            // to change his page
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$this->course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                  ORDER BY id ASC';
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
        }

        return $row['ratinglock_disc'];
    }

    /**
     * Notify page changes.
     *
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     *
     * @return int the current notification status
     */
    public function check_notify_page($reflink)
    {
        $tbl_wiki = $this->tbl_wiki;
        $tbl_wiki_mailcue = $this->tbl_wiki_mailcue;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $userId = api_get_user_id();

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    reflink="'.$reflink.'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id ASC';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $id = $row['id'];
        $sql = 'SELECT * FROM '.$tbl_wiki_mailcue.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    id="'.$id.'" AND
                    user_id="'.api_get_user_id().'" AND
                    type="P"';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        $idm = $row ? $row['id'] : 0;
        if (empty($idm)) {
            $status_notify = 0;
        } else {
            $status_notify = 1;
        }

        // Change status
        if (isset($_GET['actionpage']) &&
            $_GET['actionpage'] == 'locknotify' &&
            $status_notify == 0
        ) {
            $sql = "SELECT id FROM $tbl_wiki_mailcue
                    WHERE c_id = ".$this->course_id." AND id = $id AND user_id = $userId";
            $result = Database::query($sql);
            $exist = false;
            if (Database::num_rows($result)) {
                $exist = true;
            }
            if ($exist == false) {
                $sql = "INSERT INTO ".$tbl_wiki_mailcue." (c_id, id, user_id, type, group_id, session_id) VALUES
                (".$this->course_id.", '".$id."','".api_get_user_id()."','P','".$this->group_id."','".$this->session_id."')";
                Database::query($sql);
            }
            $status_notify = 1;
        }

        if (isset($_GET['actionpage']) &&
            $_GET['actionpage'] == 'unlocknotify' &&
            $status_notify == 1
        ) {
            $sql = 'DELETE FROM '.$tbl_wiki_mailcue.'
                    WHERE
                        id="'.$id.'" AND
                        user_id="'.api_get_user_id().'" AND
                        type="P" AND
                        c_id = '.$this->course_id;
            Database::query($sql);
            $status_notify = 0;
        }

        return $status_notify;
    }

    /**
     * Notify discussion changes.
     *
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     *
     * @param string $reflink
     *
     * @return int current database status of rating discuss and change it if get action
     */
    public function check_notify_discuss($reflink)
    {
        $tbl_wiki_mailcue = $this->tbl_wiki_mailcue;
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    reflink="'.$reflink.'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id ASC';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $id = $row['id'];
        $sql = 'SELECT * FROM '.$tbl_wiki_mailcue.'
                WHERE
                    c_id = '.$this->course_id.' AND id="'.$id.'" AND user_id="'.api_get_user_id().'" AND type="D"';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $idm = $row ? $row['id'] : 0;

        if (empty($idm)) {
            $status_notify_disc = 0;
        } else {
            $status_notify_disc = 1;
        }

        // change status
        if (isset($_GET['actionpage']) &&
            $_GET['actionpage'] == 'locknotifydisc' &&
            $status_notify_disc == 0
        ) {
            $sql = "INSERT INTO ".$tbl_wiki_mailcue." (c_id, id, user_id, type, group_id, session_id) VALUES
            (".$this->course_id.", '".$id."','".api_get_user_id()."','D','".$this->group_id."','".$this->session_id."')";
            Database::query($sql);
            $status_notify_disc = 1;
        }
        if (isset($_GET['actionpage']) &&
            $_GET['actionpage'] == 'unlocknotifydisc' &&
            $status_notify_disc == 1
        ) {
            $sql = 'DELETE FROM '.$tbl_wiki_mailcue.'
                    WHERE
                        id="'.$id.'" AND
                        user_id="'.api_get_user_id().'" AND
                        type="D" AND
                        c_id = '.$this->course_id;
            Database::query($sql);
            $status_notify_disc = 0;
        }

        return $status_notify_disc;
    }

    /**
     * Notify all changes.
     *
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     */
    public function check_notify_all()
    {
        $tbl_wiki_mailcue = $this->tbl_wiki_mailcue;

        $sql = 'SELECT * FROM '.$tbl_wiki_mailcue.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    user_id="'.api_get_user_id().'" AND
                    type="F" AND
                    group_id="'.$this->group_id.'" AND
                    session_id="'.$this->session_id.'"';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        $idm = $row ? $row['user_id'] : 0;

        if (empty($idm)) {
            $status_notify_all = 0;
        } else {
            $status_notify_all = 1;
        }

        //change status
        if (isset($_GET['actionpage']) &&
            $_GET['actionpage'] == 'locknotifyall' &&
            $status_notify_all == 0
        ) {
            $sql = "INSERT INTO ".$tbl_wiki_mailcue." (c_id, user_id, type, group_id, session_id) VALUES
            (".$this->course_id.", '".api_get_user_id()."','F','".$this->group_id."','".$this->session_id."')";
            Database::query($sql);
            $status_notify_all = 1;
        }

        if (isset($_GET['actionpage']) &&
            $_GET['actionpage'] == 'unlocknotifyall' &&
            $status_notify_all == 1
        ) {
            $sql = 'DELETE FROM '.$tbl_wiki_mailcue.'
                   WHERE
                    user_id="'.api_get_user_id().'" AND
                    type="F" AND
                    group_id="'.$this->group_id.'" AND
                    session_id="'.$this->session_id.'" AND
                    c_id = '.$this->course_id;
            Database::query($sql);
            $status_notify_all = 0;
        }

        //show status
        return $status_notify_all;
    }

    /**
     * Sends pending e-mails.
     */
    public function check_emailcue(
        $id_or_ref,
        $type,
        $lastime = '',
        $lastuser = ''
    ) {
        $tbl_wiki_mailcue = $this->tbl_wiki_mailcue;
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $_course = $this->courseInfo;
        $group_properties = GroupManager::get_group_properties($this->group_id);
        $group_name = $group_properties ? $group_properties['name'] : '';
        $allow_send_mail = false; //define the variable to below
        $email_assignment = null;

        if (!is_string($lastime) && get_class($lastime) == 'DateTime') {
            $lastime = $lastime->format('Y-m-d H:i:s');
        }

        if ($type == 'P') {
            //if modifying a wiki page
            //first, current author and time
            //Who is the author?
            $userinfo = api_get_user_info($lastuser);
            $email_user_author = get_lang('EditedBy').': '.$userinfo['complete_name'];

            //When ?
            $year = substr($lastime, 0, 4);
            $month = substr($lastime, 5, 2);
            $day = substr($lastime, 8, 2);
            $hours = substr($lastime, 11, 2);
            $minutes = substr($lastime, 14, 2);
            $seconds = substr($lastime, 17, 2);
            $email_date_changes = $day.' '.$month.' '.$year.' '.$hours.":".$minutes.":".$seconds;

            //second, extract data from first reg
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$this->course_id.' AND
                        reflink="'.$id_or_ref.'" AND
                        '.$groupfilter.$condition_session.'
                    ORDER BY id ASC';
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            $id = $row['id'];
            $email_page_name = $row['title'];
            if ($row['visibility'] == 1) {
                $allow_send_mail = true; //if visibility off - notify off
                $sql = 'SELECT * FROM '.$tbl_wiki_mailcue.'
                        WHERE
                            c_id = '.$this->course_id.' AND
                            id="'.$id.'" AND
                            type="'.$type.'" OR
                            type="F" AND
                            group_id="'.$this->group_id.'" AND
                            session_id="'.$this->session_id.'"';
                //type: P=page, D=discuss, F=full.
                $result = Database::query($sql);
                $emailtext = get_lang('EmailWikipageModified').
                    '<strong>'.$email_page_name.'</strong> '.
                    get_lang('Wiki');
            }
        } elseif ($type == 'D') {
            //if added a post to discuss
            //first, current author and time
            //Who is the author of last message?
            $userinfo = api_get_user_info($lastuser);
            $email_user_author = get_lang('AddedBy').': '.$userinfo['complete_name'];

            //When ?
            $year = substr($lastime, 0, 4);
            $month = substr($lastime, 5, 2);
            $day = substr($lastime, 8, 2);
            $hours = substr($lastime, 11, 2);
            $minutes = substr($lastime, 14, 2);
            $seconds = substr($lastime, 17, 2);
            $email_date_changes = $day.' '.$month.' '.$year.' '.$hours.":".$minutes.":".$seconds;
            //second, extract data from first reg
            $id = $id_or_ref; //$id_or_ref is id from tblwiki
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE c_id = '.$this->course_id.' AND id="'.$id.'"
                    ORDER BY id ASC';

            $result = Database::query($sql);
            $row = Database::fetch_array($result);

            $email_page_name = $row['title'];
            if ($row['visibility_disc'] == 1) {
                $allow_send_mail = true; //if visibility off - notify off
                $sql = 'SELECT * FROM '.$tbl_wiki_mailcue.'
                        WHERE
                            c_id = '.$this->course_id.' AND
                            id="'.$id.'" AND
                            type="'.$type.'" OR
                            type="F" AND
                            group_id="'.$this->group_id.'" AND
                            session_id="'.$this->session_id.'"';
                //type: P=page, D=discuss, F=full
                $result = Database::query($sql);
                $emailtext = get_lang(
                        'EmailWikiPageDiscAdded'
                    ).' <strong>'.$email_page_name.'</strong> '.get_lang(
                        'Wiki'
                    );
            }
        } elseif ($type == 'A') {
            //for added pages
            $id = 0; //for tbl_wiki_mailcue
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE c_id = '.$this->course_id.'
                    ORDER BY id DESC'; //the added is always the last

            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            $email_page_name = $row['title'];

            //Who is the author?
            $userinfo = api_get_user_info($row['user_id']);
            $email_user_author = get_lang('AddedBy').': '.$userinfo['complete_name'];

            //When ?
            $year = substr($row['dtime'], 0, 4);
            $month = substr($row['dtime'], 5, 2);
            $day = substr($row['dtime'], 8, 2);
            $hours = substr($row['dtime'], 11, 2);
            $minutes = substr($row['dtime'], 14, 2);
            $seconds = substr($row['dtime'], 17, 2);
            $email_date_changes = $day.' '.$month.' '.$year.' '.$hours.":".$minutes.":".$seconds;

            if ($row['assignment'] == 0) {
                $allow_send_mail = true;
            } elseif ($row['assignment'] == 1) {
                $email_assignment = get_lang('AssignmentDescExtra').' ('.get_lang('AssignmentMode').')';
                $allow_send_mail = true;
            } elseif ($row['assignment'] == 2) {
                $allow_send_mail = false; //Mode tasks: avoids notifications to all users about all users
            }

            $sql = 'SELECT * FROM '.$tbl_wiki_mailcue.'
                    WHERE
                        c_id = '.$this->course_id.' AND
                        id="'.$id.'" AND
                        type="F" AND
                        group_id="'.$this->group_id.'" AND
                        session_id="'.$this->session_id.'"';

            //type: P=page, D=discuss, F=full
            $result = Database::query($sql);
            $emailtext = get_lang('EmailWikiPageAdded').' <strong>'.
                $email_page_name.'</strong> '.get_lang('In').' '.get_lang('Wiki');
        } elseif ($type == 'E') {
            $id = 0;
            $allow_send_mail = true;
            // Who is the author?
            $userinfo = api_get_user_info(api_get_user_id()); //current user
            $email_user_author = get_lang('DeletedBy').': '.$userinfo['complete_name'];
            //When ?
            $today = date('r'); //current time
            $email_date_changes = $today;
            $sql = 'SELECT * FROM '.$tbl_wiki_mailcue.'
                    WHERE
                        c_id = '.$this->course_id.' AND
                        id="'.$id.'" AND type="F" AND
                        group_id="'.$this->group_id.'" AND
                        session_id="'.$this->session_id.'"'; //type: P=page, D=discuss, F=wiki
            $result = Database::query($sql);
            $emailtext = get_lang('EmailWikipageDedeleted');
        }
        ///make and send email
        if ($allow_send_mail) {
            $extraParameters = [];
            if (api_get_configuration_value('mail_header_from_custom_course_logo') == true) {
                $extraParameters = ['logo' => CourseManager::getCourseEmailPicture($_course)];
            }

            while ($row = Database::fetch_array($result)) {
                $userinfo = api_get_user_info(
                    $row['user_id']
                ); //$row['user_id'] obtained from tbl_wiki_mailcue
                $name_to = $userinfo['complete_name'];
                $email_to = $userinfo['email'];
                $sender_name = api_get_setting('emailAdministrator');
                $sender_email = api_get_setting('emailAdministrator');
                $email_subject = get_lang(
                        'EmailWikiChanges'
                    ).' - '.$_course['official_code'];
                $email_body = get_lang('DearUser').' '.api_get_person_name(
                        $userinfo['firstname'],
                        $userinfo['lastname']
                    ).',<br /><br />';
                if (0 == $this->session_id) {
                    $email_body .= $emailtext.' <strong>'.$_course['name'].' - '.$group_name.'</strong><br /><br /><br />';
                } else {
                    $email_body .= $emailtext.' <strong>'.$_course['name'].' ('.api_get_session_name().') - '.$group_name.'</strong><br /><br /><br />';
                }
                $email_body .= $email_user_author.' ('.$email_date_changes.')<br /><br /><br />';
                $email_body .= $email_assignment.'<br /><br /><br />';
                $email_body .= '<font size="-2">'.get_lang(
                        'EmailWikiChangesExt_1'
                    ).': <strong>'.get_lang('NotifyChanges').'</strong><br />';
                $email_body .= get_lang(
                        'EmailWikiChangesExt_2'
                    ).': <strong>'.get_lang(
                        'NotNotifyChanges'
                    ).'</strong></font><br />';
                @api_mail_html(
                    $name_to,
                    $email_to,
                    $email_subject,
                    $email_body,
                    $sender_name,
                    $sender_email,
                    [],
                    [],
                    false,
                    $extraParameters,
                    ''
                );
            }
        }
    }

    /**
     * Function export last wiki page version to document area.
     *
     * @param int $doc_id wiki page id
     *
     * @return mixed
     *
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     */
    public function export2doc($doc_id)
    {
        $_course = $this->courseInfo;
        $groupInfo = GroupManager::get_group_properties($this->group_id);
        $data = self::getWikiDataFromDb($doc_id);

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

        $css_file = api_get_path(SYS_CSS_PATH).'themes/'.api_get_setting('stylesheets').'/default.css';
        if (file_exists($css_file)) {
            $css = @file_get_contents($css_file);
        } else {
            $css = '';
        }
        // Fixing some bugs in css files.
        $root_rel = api_get_path(REL_PATH);
        $css_path = 'main/css/';
        $theme = api_get_setting('stylesheets').'/';
        $css = str_replace(
            'behavior:url("/main/css/csshover3.htc");',
            '',
            $css
        );
        $css = str_replace('main/', $root_rel.'main/', $css);
        $css = str_replace(
            'images/',
            $root_rel.$css_path.$theme.'images/',
            $css
        );
        $css = str_replace('../../img/', $root_rel.'main/img/', $css);
        $asciimathmal_script = (api_contains_asciimathml(
                $wikiContents
            ) || api_contains_asciisvg($wikiContents))
            ? '<script src="'.api_get_path(
                WEB_CODE_PATH
            ).'inc/lib/javascript/asciimath/ASCIIMathML.js" type="text/javascript"></script>'."\n" : '';

        $template = str_replace(
            [
                '{LANGUAGE}',
                '{ENCODING}',
                '{TEXT_DIRECTION}',
                '{TITLE}',
                '{CSS}',
                '{ASCIIMATHML_SCRIPT}',
            ],
            [
                api_get_language_isocode(),
                api_get_system_encoding(),
                api_get_text_direction(),
                $wikiTitle,
                $css,
                $asciimathmal_script,
            ],
            $template
        );

        if (0 != $this->group_id) {
            $groupPart = '_group'.$this->group_id; // and add groupId to put the same document title in different groups
            $group_properties = GroupManager::get_group_properties($this->group_id);
            $groupPath = $group_properties['directory'];
        } else {
            $groupPart = '';
            $groupPath = '';
        }

        $exportDir = api_get_path(SYS_COURSE_PATH).api_get_course_path(
            ).'/document'.$groupPath;
        $exportFile = api_replace_dangerous_char($wikiTitle).$groupPart;
        $wikiContents = trim(
            preg_replace(
                "/\[[\[]?([^\]|]*)[|]?([^|\]]*)\][\]]?/",
                "$1",
                $wikiContents
            )
        );
        //TODO: put link instead of title
        $wikiContents = str_replace('{CONTENT}', $wikiContents, $template);
        // replace relative path by absolute path for courses, so you can see
        // items into this page wiki (images, mp3, etc..) exported in documents
        if (api_strpos(
                $wikiContents,
                '../..'.api_get_path(REL_COURSE_PATH)
            ) !== false) {
            $web_course_path = api_get_path(WEB_COURSE_PATH);
            $wikiContents = str_replace(
                '../..'.api_get_path(REL_COURSE_PATH),
                $web_course_path,
                $wikiContents
            );
        }

        $i = 1;
        //only export last version, but in new export new version in document area
        while (file_exists($exportDir.'/'.$exportFile.'_'.$i.'.html')) {
            $i++;
        }

        $wikiFileName = $exportFile.'_'.$i.'.html';
        $exportPath = $exportDir.'/'.$wikiFileName;

        file_put_contents($exportPath, $wikiContents);
        $doc_id = add_document(
            $_course,
            $groupPath.'/'.$wikiFileName,
            'file',
            filesize($exportPath),
            $wikiTitle
        );

        api_item_property_update(
            $_course,
            TOOL_DOCUMENT,
            $doc_id,
            'DocumentAdded',
            api_get_user_id(),
            $groupInfo
        );

        return $doc_id;
    }

    /**
     * Exports the wiki page to PDF.
     */
    public function export_to_pdf($id, $course_code)
    {
        if (!api_is_platform_admin()) {
            if (api_get_setting('students_export2pdf') !== 'true') {
                Display::addFlash(
                    Display::return_message(
                        get_lang('PDFDownloadNotAllowedForStudents'),
                        'error',
                        false
                    )
                );

                return false;
            }
        }

        $data = self::getWikiDataFromDb($id);
        $content_pdf = api_html_entity_decode(
            $data['content'],
            ENT_QUOTES,
            api_get_system_encoding()
        );

        //clean wiki links
        $content_pdf = trim(
            preg_replace(
                "/\[[\[]?([^\]|]*)[|]?([^|\]]*)\][\]]?/",
                "$1",
                $content_pdf
            )
        );
        //TODO: It should be better to display the link insted of the tile but it is hard for [[title]] links

        $title_pdf = api_html_entity_decode(
            $data['title'],
            ENT_QUOTES,
            api_get_system_encoding()
        );
        $title_pdf = api_utf8_encode($title_pdf, api_get_system_encoding());
        $content_pdf = api_utf8_encode($content_pdf, api_get_system_encoding());

        $html = '
        <!-- defines the headers/footers - this must occur before the headers/footers are set -->

        <!--mpdf
        <pageheader name="odds" content-left="'.$title_pdf.'"  header-style-left="color: #880000; font-style: italic;"  line="1" />
        <pagefooter name="odds" content-right="{PAGENO}/{nb}" line="1" />

        <!-- set the headers/footers - they will occur from here on in the document -->
        <!--mpdf
        <setpageheader name="odds" page="odd" value="on" show-this-page="1" />
        <setpagefooter name="odds" page="O" value="on" />

        mpdf-->'.$content_pdf;

        $css = api_get_print_css();

        $pdf = new PDF();
        $pdf->content_to_pdf($html, $css, $title_pdf, $course_code);
        exit;
    }

    /**
     * Function prevent double post (reload or F5).
     */
    public function double_post($wpost_id)
    {
        $postId = Session::read('wpost_id');
        if (!empty($postId)) {
            if ($wpost_id == $postId) {
                return false;
            } else {
                Session::write('wpost_id', $wpost_id);

                return true;
            }
        } else {
            Session::write('wpost_id', $wpost_id);

            return true;
        }
    }

    /**
     * Function wizard individual assignment.
     *
     * @author Juan Carlos Raña <herodoto@telefonica.net>
     */
    public function auto_add_page_users($values)
    {
        $assignment_type = $values['assignment'];
        $groupInfo = GroupManager::get_group_properties($this->group_id);
        if (0 == $this->group_id) {
            //extract course members
            if (!empty($this->session_id)) {
                $a_users_to_add = CourseManager::get_user_list_from_course_code($this->courseCode, $this->session_id);
            } else {
                $a_users_to_add = CourseManager::get_user_list_from_course_code($this->courseCode);
            }
        } else {
            //extract group members
            $subscribed_users = GroupManager::get_subscribed_users($groupInfo);
            $subscribed_tutors = GroupManager::get_subscribed_tutors(
                $groupInfo
            );
            $a_users_to_add_with_duplicates = array_merge(
                $subscribed_users,
                $subscribed_tutors
            );
            //remove duplicates
            $a_users_to_add = $a_users_to_add_with_duplicates;
            $a_users_to_add = array_unique($a_users_to_add);
        }

        $all_students_pages = [];
        // Data about teacher
        $userId = api_get_user_id();
        $userinfo = api_get_user_info($userId);
        $username = api_htmlentities(
            sprintf(get_lang('LoginX'), $userinfo['username'], ENT_QUOTES)
        );
        $name = $userinfo['complete_name']." - ".$username;
        $photo = '<img src="'.$userinfo['avatar'].'" alt="'.$name.'"  width="40" height="50" align="top" title="'.$name.'"  />';

        // teacher assignment title
        $title_orig = $values['title'];

        // teacher assignment reflink
        $link2teacher = $values['title'] = $title_orig."_uass".$userId;

        // first: teacher name, photo, and assignment description (original content)
        $content_orig_A = '<div align="center" style="background-color: #F5F8FB; border:solid; border-color: #E6E6E6">
        <table border="0">
            <tr><td style="font-size:24px">'.get_lang('AssignmentDesc').'</td></tr>
            <tr><td>'.$photo.'<br />'.Display::tag(
                'span',
                api_get_person_name(
                    $userinfo['firstname'],
                    $userinfo['lastname']
                ),
                ['title' => $username]
            ).'</td></tr>
        </table></div>';

        $content_orig_B = '<br/><div align="center" style="font-size:24px">'.
            get_lang('AssignmentDescription').': '.
            $title_orig.'</div><br/>'.Security::remove_XSS($_POST['content']);

        //Second: student list (names, photo and links to their works).
        //Third: Create Students work pages.
        foreach ($a_users_to_add as $o_user_to_add) {
            if ($o_user_to_add['user_id'] != $userId) {
                // except that puts the task
                $assig_user_id = $o_user_to_add['user_id'];
                // identifies each page as created by the student, not by teacher

                $userPicture = UserManager::getUserPicture($assig_user_id);
                $username = api_htmlentities(
                    sprintf(
                        get_lang('LoginX'),
                        $o_user_to_add['username'],
                        ENT_QUOTES
                    )
                );
                $name = api_get_person_name(
                        $o_user_to_add['firstname'],
                        $o_user_to_add['lastname']
                    )." . ".$username;
                $photo = '<img src="'.$userPicture.'" alt="'.$name.'"  width="40" height="50" align="bottom" title="'.$name.'"  />';

                $is_tutor_of_group = GroupManager::is_tutor_of_group(
                    $assig_user_id,
                    $groupInfo
                ); //student is tutor
                $is_tutor_and_member = GroupManager::is_tutor_of_group(
                        $assig_user_id,
                        $groupInfo
                    ) &&
                    GroupManager::is_subscribed($assig_user_id, $groupInfo);
                // student is tutor and member
                if ($is_tutor_and_member) {
                    $status_in_group = get_lang('GroupTutorAndMember');
                } else {
                    if ($is_tutor_of_group) {
                        $status_in_group = get_lang('GroupTutor');
                    } else {
                        $status_in_group = " "; //get_lang('GroupStandardMember')
                    }
                }

                if ($assignment_type == 1) {
                    $values['title'] = $title_orig;
                    $values['content'] = '<div align="center" style="background-color: #F5F8FB; border:solid; border-color: #E6E6E6">
                    <table border="0">
                    <tr><td style="font-size:24px">'.get_lang('AssignmentWork').'</td></tr>
                    <tr><td>'.$photo.'<br />'.$name.'</td></tr></table>
                    </div>[['.$link2teacher.' | '.get_lang(
                            'AssignmentLinktoTeacherPage'
                        ).']] ';
                    //If $content_orig_B is added here, the task written by
                    // the professor was copied to the page of each student.
                    // TODO: config options
                    // AssignmentLinktoTeacherPage
                    $all_students_pages[] = '<li>'.
                        Display::tag(
                            'span',
                            strtoupper(
                                $o_user_to_add['lastname']
                            ).', '.$o_user_to_add['firstname'],
                            ['title' => $username]
                        ).
                        ' [['.Security::remove_XSS(
                            $_POST['title']
                        )."_uass".$assig_user_id.' | '.$photo.']] '.$status_in_group.'</li>';
                    // don't change this line without guaranteeing
                    // that users will be ordered by last names in the
                    // following format (surname, name)
                    $values['assignment'] = 2;
                }
                $this->assig_user_id = $assig_user_id;
                $this->save_new_wiki($values);
            }
        }

        foreach ($a_users_to_add as $o_user_to_add) {
            if ($o_user_to_add['user_id'] == $userId) {
                $assig_user_id = $o_user_to_add['user_id'];
                if ($assignment_type == 1) {
                    $values['title'] = $title_orig;
                    $values['comment'] = get_lang('AssignmentDesc');
                    sort($all_students_pages);
                    $values['content'] = $content_orig_A.$content_orig_B.'<br/>
                    <div align="center" style="font-size:18px; background-color: #F5F8FB; border:solid; border-color:#E6E6E6">
                    '.get_lang('AssignmentLinkstoStudentsPage').'
                    </div><br/>
                    <div style="background-color: #F5F8FB; border:solid; border-color:#E6E6E6">
                    <ol>'.implode($all_students_pages).'</ol>
                    </div>
                    <br/>';
                    $values['assignment'] = 1;
                }
                $this->assig_user_id = $assig_user_id;
                $this->save_new_wiki($values);
            }
        }
    }

    /**
     * Displays the results of a wiki search.
     */
    public function display_wiki_search_results(
        string $search_term,
        int $search_content = 0,
        int $all_vers = 0,
        array $categoryIdList = [],
        bool $matchAllCategories = false
    ) {
        $tbl_wiki = $this->tbl_wiki;
        $sessionCondition = api_get_session_condition($this->session_id, true, false, 'wp.session_id');
        $groupfilter = ' wp.group_id = '.$this->group_id.' ';
        $subGroupfilter = ' s2.group_id = '.$this->group_id.' ';
        $subSessionCondition = api_get_session_condition($this->session_id, true, false, 's2.session_id').' ';
        $categoryIdList = array_map('intval', $categoryIdList);
        $categoriesJoin = '';

        if ($categoryIdList) {
            if ($matchAllCategories) {
                foreach ($categoryIdList as $categoryId) {
                    $categoriesJoin .= "INNER JOIN c_wiki_rel_category AS wrc$categoryId
                            ON (wp.iid = wrc$categoryId.wiki_id AND wrc$categoryId.category_id = $categoryId)
                        INNER JOIN c_wiki_category AS wc$categoryId
                            ON (wrc$categoryId.category_id = wc$categoryId.id) ";
                }
            } else {
                $categoriesJoin = 'INNER JOIN c_wiki_rel_category AS wrc ON (wp.iid = wrc.wiki_id)
                    INNER JOIN c_wiki_category AS wc ON (wrc.category_id = wc.id) ';
            }
        }

        $categoriesCondition = !$matchAllCategories
            ? ($categoryIdList ? 'AND wc.id IN ('.implode(', ', $categoryIdList).')' : '')
            : '';

        echo '<legend>'.get_lang('WikiSearchResults').': '.Security::remove_XSS($search_term).'</legend>';

        //only by professors when page is hidden
        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            if (1 === $all_vers) {
                $sql = "SELECT * FROM $tbl_wiki AS wp $categoriesJoin
                    WHERE wp.c_id = ".$this->course_id."
                        AND (wp.title LIKE '%".Database::escape_string($search_term)."%' ";

                if (1 === $search_content) {
                    $sql .= "OR wp.content LIKE '%".Database::escape_string($search_term)."%' ";
                }

                $sql .= ") AND ".$groupfilter.$sessionCondition.$categoriesCondition;
            } else {
                // warning don't use group by reflink because don't return the last version
                $sql = "SELECT * FROM $tbl_wiki AS wp
                    WHERE wp.c_id = ".$this->course_id."
                        AND (wp.title LIKE '%".Database::escape_string($search_term)."%' ";

                if (1 === $search_content) {
                    // warning don't use group by reflink because don't return the last version
                    $sql .= "OR wp.content LIKE '%".Database::escape_string($search_term)."%' ";
                }

                $sql .= ") AND wp.id IN (
                    SELECT MAX(s2.id)
                    FROM ".$tbl_wiki." s2 $categoriesJoin
                    WHERE s2.c_id = ".$this->course_id."
                        AND s2.reflink = wp.reflink
                        AND ".$subGroupfilter.$subSessionCondition.$categoriesCondition."
                )";
            }
        } else {
            if (1 === $all_vers) {
                $sql = "SELECT * FROM $tbl_wiki AS wp $categoriesJoin
                    WHERE wp.c_id = ".$this->course_id."
                        AND wp.visibility = 1
                        AND (wp.title LIKE '%".Database::escape_string($search_term)."%' ";

                if (1 === $search_content) {
                    //search all pages and all versions
                    $sql .= "OR wp.content LIKE '%".Database::escape_string($search_term)."%' ";
                }

                $sql .= ") AND ".$groupfilter.$sessionCondition.$categoriesCondition;
            } else {
                // warning don't use group by reflink because don't return the last version
                $sql = "SELECT * FROM $tbl_wiki AS wp
                    WHERE wp.c_id = ".$this->course_id."
                        AND wp.visibility = 1
                        AND (wp.title LIKE '%".Database::escape_string($search_term)."%' ";

                if (1 === $search_content) {
                    $sql .= "OR wp.content LIKE '%".Database::escape_string($search_term)."%' ";
                }

                $sql .= ") AND wp.id IN (
                        SELECT MAX(s2.id) FROM $tbl_wiki s2 $categoriesJoin
                        WHERE s2.c_id = ".$this->course_id."
                            AND s2.reflink = wp.reflink
                            AND ".$subGroupfilter.$subSessionCondition.$categoriesCondition."
                    )";
            }
        }

        $result = Database::query($sql);

        //show table
        $rows = [];
        if (Database::num_rows($result) > 0) {
            $iconEdit = Display::return_icon('edit.png', get_lang('EditPage'));
            $iconDiscuss = Display::return_icon('discuss.png', get_lang('Discuss'));
            $iconHistory = Display::return_icon('history.png', get_lang('History'));
            $iconLinks = Display::return_icon('what_link_here.png', get_lang('LinksPages'));
            $iconDelete = Display::return_icon('delete.png', get_lang('Delete'));

            while ($obj = Database::fetch_object($result)) {
                //get author
                $userinfo = api_get_user_info($obj->user_id);

                //get type assignment icon
                $ShowAssignment = '';
                if ($obj->assignment == 1) {
                    $ShowAssignment = Display::return_icon('wiki_assignment.png', get_lang('AssignmentDesc'));
                } elseif ($obj->assignment == 2) {
                    $ShowAssignment = Display::return_icon('wiki_work.png', get_lang('AssignmentWork'));
                } elseif ($obj->assignment == 0) {
                    $ShowAssignment = Display::return_icon('px_transparent.gif');
                }
                $row = [];
                $row[] = $ShowAssignment;

                $wikiLinkParams = [
                    'action' => 'showpage',
                    'title' => api_htmlentities($obj->reflink),
                ];

                if (1 === $all_vers) {
                    $wikiLinkParams['view'] = $obj->id;
                }

                $row[] = Display::url(
                    api_htmlentities($obj->title),
                    $this->url.'&'.http_build_query($wikiLinkParams)
                ).$this->returnCategoriesBlock($obj->iid, '<div><small>', '</small></div>');

                $row[] = ($obj->user_id != 0 && $userinfo !== false)
                    ? UserManager::getUserProfileLink($userinfo)
                    : get_lang('Anonymous').' ('.$obj->user_ip.')';
                $row[] = api_convert_and_format_date($obj->dtime);

                if (1 === $all_vers) {
                    $row[] = $obj->version;
                } else {
                    $showdelete = '';
                    if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                        $showdelete = Display::url(
                            $iconDelete,
                            $this->url.'&'.http_build_query([
                                'action' => 'delete',
                                'title' => api_htmlentities($obj->reflink),
                            ])
                        );
                    }

                    $row[] = Display::url(
                            $iconEdit,
                            $this->url.'&'.http_build_query([
                                'action' => 'edit',
                                'title' => api_htmlentities($obj->reflink),
                            ])
                        )
                        .Display::url(
                            $iconDiscuss,
                            $this->url.'&'.http_build_query([
                                'action' => 'discuss',
                                'title' => api_htmlentities($obj->reflink),
                            ])
                        )
                        .Display::url(
                            $iconHistory,
                            $this->url.'&'.http_build_query([
                                'action' => 'history',
                                'title' => api_htmlentities($obj->reflink),
                            ])
                        )
                        .Display::url(
                            $iconLinks,
                            $this->url.'&'.http_build_query([
                                'action' => 'links',
                                'title' => api_htmlentities($obj->reflink),
                            ])
                        )
                        .$showdelete;
                }
                $rows[] = $row;
            }

            $table = new SortableTableFromArrayConfig(
                $rows,
                1,
                10,
                'SearchPages_table',
                '',
                '',
                'ASC'
            );
            $table->set_additional_parameters(
                [
                    'cidReq' => $this->courseCode,
                    'gidReq' => $this->group_id,
                    'id_session' => $this->session_id,
                    'action' => $_GET['action'],
                    'mode_table' => 'yes2',
                    'search_term' => $search_term,
                    'search_content' => $search_content,
                    'all_vers' => $all_vers,
                    'categories' => $categoryIdList,
                    'match_all_categories' => $matchAllCategories,
                ]
            );
            $table->set_header(
                0,
                get_lang('Type'),
                true,
                ['style' => 'width:30px;']
            );
            $table->set_header(1, get_lang('Title'));
            if (1 === $all_vers) {
                $table->set_header(2, get_lang('Author'));
                $table->set_header(3, get_lang('Date'));
                $table->set_header(4, get_lang('Version'));
            } else {
                $table->set_header(
                    2,
                    get_lang('Author').' <small>'.get_lang('LastVersion').'</small>'
                );
                $table->set_header(
                    3,
                    get_lang('Date').' <small>'.get_lang('LastVersion').'</small>'
                );
                $table->set_header(
                    4,
                    get_lang('Actions'),
                    false,
                    ['style' => 'width:130px;']
                );
            }
            $table->display();
        } else {
            echo get_lang('NoSearchResults');
        }
    }

    /**
     * Get wiki information.
     *
     * @param   int|bool wiki id
     *
     * @return array wiki data
     */
    public function getWikiDataFromDb($id)
    {
        $tbl_wiki = $this->tbl_wiki;
        if ($id === false) {
            return [];
        }
        $id = intval($id);
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$this->course_id.' AND id = '.$id.' ';
        $result = Database::query($sql);
        $data = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $data = $row;
        }

        return $data;
    }

    /**
     * @param string $refLink
     *
     * @return array
     */
    public function getLastWikiData($refLink)
    {
        $tbl_wiki = $this->tbl_wiki;
        $groupfilter = $this->groupfilter;
        $condition_session = $this->condition_session;

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    reflink="'.Database::escape_string($refLink).'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id DESC';

        $result = Database::query($sql);

        return Database::fetch_array($result);
    }

    /**
     * Get wiki information.
     *
     * @param   string     wiki id
     * @param int $courseId
     *
     * @return array wiki data
     */
    public function getPageByTitle($title, $courseId = null)
    {
        $tbl_wiki = $this->tbl_wiki;
        if (empty($courseId)) {
            $courseId = $this->course_id;
        } else {
            $courseId = intval($courseId);
        }

        if (empty($title) || empty($courseId)) {
            return [];
        }

        $title = Database::escape_string($title);
        $sql = "SELECT * FROM $tbl_wiki
                WHERE c_id = $courseId AND reflink = '$title'";
        $result = Database::query($sql);
        $data = [];
        if (Database::num_rows($result)) {
            $data = Database::fetch_array($result, 'ASSOC');
        }

        return $data;
    }

    /**
     * @param string $title
     * @param int    $courseId
     * @param string
     * @param string
     *
     * @return bool
     */
    public function deletePage(
        $title,
        $courseId,
        $groupfilter = null,
        $condition_session = null
    ) {
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
        $condition_session = $this->condition_session;

        $sql = "SELECT * FROM $tbl_wiki
                WHERE
                    c_id = ".$this->course_id." AND
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
        $condition_session = $this->condition_session;
        $isEditing = Database::escape_string($isEditing);

        $sql = 'UPDATE '.$tbl_wiki.' SET
                is_editing = "0",
                time_edit = NULL
                WHERE
                    c_id = '.$this->course_id.' AND
                    is_editing="'.$isEditing.'" '.
            $condition_session;
        Database::query($sql);
    }

    /**
     * Release of blocked pages to prevent concurrent editions.
     *
     * @param int    $userId
     * @param string $action
     */
    public function blockConcurrentEditions($userId, $action = null)
    {
        $result = self::getAllWiki();
        if (!empty($result)) {
            foreach ($result as $is_editing_block) {
                $max_edit_time = 1200; // 20 minutes
                $timestamp_edit = strtotime($is_editing_block['time_edit']);
                $time_editing = time() - $timestamp_edit;

                // First prevent concurrent users and double version
                if ($is_editing_block['is_editing'] == $userId) {
                    Session::write('_version', $is_editing_block['version']);
                } else {
                    Session::erase('_version');
                }
                // Second checks if has exceeded the time that a page may
                // be available or if a page was edited and saved by its author
                if ($time_editing > $max_edit_time ||
                    ($is_editing_block['is_editing'] == $userId &&
                        $action != 'edit')
                ) {
                    self::updateWikiIsEditing($is_editing_block['is_editing']);
                }
            }
        }
    }

    /**
     * Showing wiki stats.
     */
    public function getStats()
    {
        if (!api_is_allowed_to_edit(false, true)) {
            return false;
        }

        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $tbl_wiki_conf = $this->tbl_wiki_conf;

        echo '<div class="actions">'.get_lang('Statistics').'</div>';

        // Check all versions of all pages
        $total_words = 0;
        $total_links = 0;
        $total_links_anchors = 0;
        $total_links_mail = 0;
        $total_links_ftp = 0;
        $total_links_irc = 0;
        $total_links_news = 0;
        $total_wlinks = 0;
        $total_images = 0;
        $clean_total_flash = 0;
        $total_flash = 0;
        $total_mp3 = 0;
        $total_flv_p = 0;
        $total_flv = 0;
        $total_youtube = 0;
        $total_multimedia = 0;
        $total_tables = 0;

        $sql = "SELECT *, COUNT(*) AS TOTAL_VERS, SUM(hits) AS TOTAL_VISITS
                FROM ".$tbl_wiki."
                WHERE c_id = ".$this->course_id." AND ".$groupfilter.$condition_session;

        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_versions = $row['TOTAL_VERS'];
            $total_visits = intval($row['TOTAL_VISITS']);
        }

        $sql = "SELECT * FROM ".$tbl_wiki."
                WHERE c_id = ".$this->course_id." AND ".$groupfilter.$condition_session."";
        $allpages = Database::query($sql);

        while ($row = Database::fetch_array($allpages)) {
            $total_words = $total_words + self::word_count($row['content']);
            $total_links = $total_links + substr_count(
                $row['content'],
                "href="
            );
            $total_links_anchors = $total_links_anchors + substr_count(
                $row['content'],
                'href="#'
            );
            $total_links_mail = $total_links_mail + substr_count(
                $row['content'],
                'href="mailto'
            );
            $total_links_ftp = $total_links_ftp + substr_count(
                $row['content'],
                'href="ftp'
            );
            $total_links_irc = $total_links_irc + substr_count(
                $row['content'],
                'href="irc'
            );
            $total_links_news = $total_links_news + substr_count(
                $row['content'],
                'href="news'
            );
            $total_wlinks = $total_wlinks + substr_count($row['content'], "[[");
            $total_images = $total_images + substr_count(
                $row['content'],
                "<img"
            );
            $clean_total_flash = preg_replace(
                '/player.swf/',
                ' ',
                $row['content']
            );
            $total_flash = $total_flash + substr_count(
                $clean_total_flash,
                '.swf"'
            );
            //.swf" end quotes prevent insert swf through flvplayer (is not counted)
            $total_mp3 = $total_mp3 + substr_count($row['content'], ".mp3");
            $total_flv_p = $total_flv_p + substr_count($row['content'], ".flv");
            $total_flv = $total_flv_p / 5;
            $total_youtube = $total_youtube + substr_count(
                $row['content'],
                "http://www.youtube.com"
            );
            $total_multimedia = $total_multimedia + substr_count(
                $row['content'],
                "video/x-msvideo"
            );
            $total_tables = $total_tables + substr_count(
                $row['content'],
                "<table"
            );
        }

        // Check only last version of all pages (current page)
        $sql = ' SELECT *, COUNT(*) AS TOTAL_PAGES, SUM(hits) AS TOTAL_VISITS_LV
                FROM  '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$this->course_id.' AND id=(
                    SELECT MAX(s2.id)
                    FROM '.$tbl_wiki.' s2
                    WHERE
                        s2.c_id = '.$this->course_id.' AND
                        s1.reflink = s2.reflink AND
                        '.$groupfilter.' AND
                        session_id='.$this->session_id.')';
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_pages = $row['TOTAL_PAGES'];
            $total_visits_lv = intval($row['TOTAL_VISITS_LV']);
        }

        $total_words_lv = 0;
        $total_links_lv = 0;
        $total_links_anchors_lv = 0;
        $total_links_mail_lv = 0;
        $total_links_ftp_lv = 0;
        $total_links_irc_lv = 0;
        $total_links_news_lv = 0;
        $total_wlinks_lv = 0;
        $total_images_lv = 0;
        $clean_total_flash_lv = 0;
        $total_flash_lv = 0;
        $total_mp3_lv = 0;
        $total_flv_p_lv = 0;
        $total_flv_lv = 0;
        $total_youtube_lv = 0;
        $total_multimedia_lv = 0;
        $total_tables_lv = 0;

        $sql = 'SELECT * FROM  '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$this->course_id.' AND id=(
                    SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                    WHERE
                        s2.c_id = '.$this->course_id.' AND
                        s1.reflink = s2.reflink AND
                        '.$groupfilter.' AND
                        session_id='.$this->session_id.'
                )';
        $allpages = Database::query($sql);

        while ($row = Database::fetch_array($allpages)) {
            $total_words_lv = $total_words_lv + self::word_count(
                $row['content']
            );
            $total_links_lv = $total_links_lv + substr_count(
                $row['content'],
                "href="
            );
            $total_links_anchors_lv = $total_links_anchors_lv + substr_count(
                $row['content'],
                'href="#'
            );
            $total_links_mail_lv = $total_links_mail_lv + substr_count(
                $row['content'],
                'href="mailto'
            );
            $total_links_ftp_lv = $total_links_ftp_lv + substr_count(
                $row['content'],
                'href="ftp'
            );
            $total_links_irc_lv = $total_links_irc_lv + substr_count(
                $row['content'],
                'href="irc'
            );
            $total_links_news_lv = $total_links_news_lv + substr_count(
                $row['content'],
                'href="news'
            );
            $total_wlinks_lv = $total_wlinks_lv + substr_count(
                $row['content'],
                "[["
            );
            $total_images_lv = $total_images_lv + substr_count(
                $row['content'],
                "<img"
            );
            $clean_total_flash_lv = preg_replace(
                '/player.swf/',
                ' ',
                $row['content']
            );
            $total_flash_lv = $total_flash_lv + substr_count(
                $clean_total_flash_lv,
                '.swf"'
            );
            //.swf" end quotes prevent insert swf through flvplayer (is not counted)
            $total_mp3_lv = $total_mp3_lv + substr_count(
                $row['content'],
                ".mp3"
            );
            $total_flv_p_lv = $total_flv_p_lv + substr_count(
                $row['content'],
                ".flv"
            );
            $total_flv_lv = $total_flv_p_lv / 5;
            $total_youtube_lv = $total_youtube_lv + substr_count(
                $row['content'],
                "http://www.youtube.com"
            );
            $total_multimedia_lv = $total_multimedia_lv + substr_count(
                $row['content'],
                "video/x-msvideo"
            );
            $total_tables_lv = $total_tables_lv + substr_count(
                $row['content'],
                "<table"
            );
        }

        //Total pages edited at this time
        $total_editing_now = 0;
        $sql = 'SELECT *, COUNT(*) AS TOTAL_EDITING_NOW
                FROM  '.$tbl_wiki.' s1
                WHERE is_editing!=0 AND s1.c_id = '.$this->course_id.' AND
                id=(
                    SELECT MAX(s2.id)
                    FROM '.$tbl_wiki.' s2
                    WHERE
                        s2.c_id = '.$this->course_id.' AND
                        s1.reflink = s2.reflink AND
                        '.$groupfilter.' AND
                        session_id='.$this->session_id.'
        )';

        // Can not use group by because the mark is set in the latest version
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_editing_now = $row['TOTAL_EDITING_NOW'];
        }

        // Total hidden pages
        $total_hidden = 0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    visibility = 0 AND
                    '.$groupfilter.$condition_session.'
                GROUP BY reflink';
        // or group by page_id. As the mark of hidden places it in all
        // versions of the page, I can use group by to see the first
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_hidden = $total_hidden + 1;
        }

        //Total protect pages
        $total_protected = 0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    editlock = 1 AND
                     '.$groupfilter.$condition_session.'
                GROUP BY reflink';
        // or group by page_id. As the mark of protected page is the
        // first version of the page, I can use group by
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_protected = $total_protected + 1;
        }

        // Total empty versions.
        $total_empty_content = 0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    content="" AND
                    '.$groupfilter.$condition_session.'';
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_empty_content = $total_empty_content + 1;
        }

        //Total empty pages (last version)

        $total_empty_content_lv = 0;
        $sql = 'SELECT  * FROM  '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$this->course_id.' AND content="" AND id=(
                    SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                    WHERE
                        s1.c_id = '.$this->course_id.' AND
                        s1.reflink = s2.reflink AND
                        '.$groupfilter.' AND
                        session_id='.$this->session_id.'
                )';
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_empty_content_lv = $total_empty_content_lv + 1;
        }

        // Total locked discuss pages
        $total_lock_disc = 0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$this->course_id.' AND addlock_disc=0 AND '.$groupfilter.$condition_session.'
                GROUP BY reflink'; //group by because mark lock in all vers, then always is ok
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_lock_disc = $total_lock_disc + 1;
        }

        // Total hidden discuss pages.
        $total_hidden_disc = 0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$this->course_id.' AND visibility_disc=0 AND '.$groupfilter.$condition_session.'
                GROUP BY reflink';
        //group by because mark lock in all vers, then always is ok
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_hidden_disc = $total_hidden_disc + 1;
        }

        // Total versions with any short comment by user or system
        $total_comment_version = 0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$this->course_id.' AND comment!="" AND '.$groupfilter.$condition_session.'';
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_comment_version = $total_comment_version + 1;
        }

        // Total pages that can only be scored by teachers.
        $total_only_teachers_rating = 0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$this->course_id.' AND
                ratinglock_disc = 0 AND
                '.$groupfilter.$condition_session.'
                GROUP BY reflink'; //group by because mark lock in all vers, then always is ok
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_only_teachers_rating = $total_only_teachers_rating + 1;
        }

        // Total pages scored by peers
        // put always this line alfter check num all pages and num pages rated by teachers
        $total_rating_by_peers = $total_pages - $total_only_teachers_rating;

        //Total pages identified as standard task
        $total_task = 0;
        $sql = 'SELECT * FROM '.$tbl_wiki.', '.$tbl_wiki_conf.'
              WHERE '.$tbl_wiki_conf.'.c_id = '.$this->course_id.' AND
               '.$tbl_wiki_conf.'.task!="" AND
               '.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND
                '.$tbl_wiki.'.'.$groupfilter.$condition_session;
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_task = $total_task + 1;
        }

        //Total pages identified as teacher page (wiki portfolio mode - individual assignment)
        $total_teacher_assignment = 0;
        $sql = 'SELECT  * FROM  '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$this->course_id.' AND assignment=1 AND id=(
                    SELECT MAX(s2.id)
                    FROM '.$tbl_wiki.' s2
                    WHERE
                        s2.c_id = '.$this->course_id.' AND
                        s1.reflink = s2.reflink AND
                        '.$groupfilter.' AND
                         session_id='.$this->session_id.'
                )';
        //mark all versions, but do not use group by reflink because y want the pages not versions
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_teacher_assignment = $total_teacher_assignment + 1;
        }

        //Total pages identifies as student page (wiki portfolio mode - individual assignment)
        $total_student_assignment = 0;
        $sql = 'SELECT  * FROM  '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$this->course_id.' AND assignment=2 AND
                id = (SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                WHERE
                    s2.c_id = '.$this->course_id.' AND
                    s1.reflink = s2.reflink AND
                    '.$groupfilter.' AND
                    session_id='.$this->session_id.'
                )';
        //mark all versions, but do not use group by reflink because y want the pages not versions
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_student_assignment = $total_student_assignment + 1;
        }

        //Current Wiki status add new pages
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session.'
                GROUP BY addlock'; //group by because mark 0 in all vers, then always is ok
        $allpages = Database::query($sql);
        $wiki_add_lock = null;
        while ($row = Database::fetch_array($allpages)) {
            $wiki_add_lock = $row['addlock'];
        }

        if ($wiki_add_lock == 1) {
            $status_add_new_pag = get_lang('Yes');
        } else {
            $status_add_new_pag = get_lang('No');
        }

        // Creation date of the oldest wiki page and version
        $first_wiki_date = null;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session.'
                ORDER BY dtime ASC
                LIMIT 1';
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $first_wiki_date = api_get_local_time($row['dtime']);
        }

        // Date of publication of the latest wiki version.

        $last_wiki_date = null;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session.'
                ORDER BY dtime DESC
                LIMIT 1';
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $last_wiki_date = api_get_local_time($row['dtime']);
        }

        // Average score of all wiki pages. (If a page has not scored zero rated)
        $media_score = 0;
        $sql = "SELECT *, SUM(score) AS TOTAL_SCORE FROM ".$tbl_wiki."
                WHERE c_id = ".$this->course_id." AND ".$groupfilter.$condition_session."
                GROUP BY reflink ";
        //group by because mark in all versions, then always is ok.
        // Do not use "count" because using "group by", would give a wrong value
        $allpages = Database::query($sql);
        $total_score = 0;
        while ($row = Database::fetch_array($allpages)) {
            $total_score = $total_score + $row['TOTAL_SCORE'];
        }

        if (!empty($total_pages)) {
            $media_score = $total_score / $total_pages;
            //put always this line alfter check num all pages
        }

        // Average user progress in his pages.
        $media_progress = 0;
        $sql = 'SELECT  *, SUM(progress) AS TOTAL_PROGRESS
                FROM  '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$this->course_id.' AND id=
                (
                    SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                    WHERE
                        s2.c_id = '.$this->course_id.' AND
                        s1.reflink = s2.reflink AND
                        '.$groupfilter.' AND
                        session_id='.$this->session_id.'
                )';
        // As the value is only the latest version I can not use group by
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_progress = $row['TOTAL_PROGRESS'];
        }

        if (!empty($total_pages)) {
            $media_progress = $total_progress / $total_pages;
            //put always this line alfter check num all pages
        }

        // Total users that have participated in the Wiki
        $total_users = 0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE  c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session.'
                GROUP BY user_id';
        //as the mark of user it in all versions of the page, I can use group by to see the first
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_users = $total_users + 1;
        }

        // Total of different IP addresses that have participated in the wiki
        $total_ip = 0;
        $sql = 'SELECT * FROM '.$tbl_wiki.'
              WHERE c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session.'
              GROUP BY user_ip';
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $total_ip = $total_ip + 1;
        }

        echo '<table class="table table-hover table-striped data_table">';
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

        echo '<table class="table table-hover table-striped data_table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th colspan="2">'.get_lang('Pages').' '.get_lang(
                'And'
            ).' '.get_lang('Versions').'</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tr>';
        echo '<td>'.get_lang('Pages').' - '.get_lang(
                'NumContributions'
            ).'</td>';
        echo '<td>'.$total_pages.' ('.get_lang(
                'Versions'
            ).': '.$total_versions.')</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('EmptyPages').'</td>';
        echo '<td>'.$total_empty_content_lv.' ('.get_lang(
                'Versions'
            ).': '.$total_empty_content.')</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('NumAccess').'</td>';
        echo '<td>'.$total_visits_lv.' ('.get_lang(
                'Versions'
            ).': '.$total_visits.')</td>';
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
        echo '<td>'.get_lang('TotalTeacherAssignments').' - '.get_lang(
                'PortfolioMode'
            ).'</td>';
        echo '<td>'.$total_teacher_assignment.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('TotalStudentAssignments').' - '.get_lang(
                'PortfolioMode'
            ).'</td>';
        echo '<td>'.$total_student_assignment.'</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>'.get_lang('TotalTask').' - '.get_lang(
                'StandardMode'
            ).'</td>';
        echo '<td>'.$total_task.'</td>';
        echo '</tr>';
        echo '</table>';
        echo '<br/>';

        echo '<table class="table table-hover table-striped data_table">';
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
        echo '<td>'.$total_links_lv.' ('.get_lang(
                'Anchors'
            ).':'.$total_links_anchors_lv.', Mail:'.$total_links_mail_lv.', FTP:'.$total_links_ftp_lv.' IRC:'.$total_links_irc_lv.', News:'.$total_links_news_lv.', ... ) </td>';
        echo '<td>'.$total_links.' ('.get_lang(
                'Anchors'
            ).':'.$total_links_anchors.', Mail:'.$total_links_mail.', FTP:'.$total_links_ftp.', IRC:'.$total_links_irc.', News:'.$total_links_news.', ... ) </td>';
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
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;

        echo '<div class="actions">'.get_lang('MostActiveUsers').'</div>';
        $sql = 'SELECT *, COUNT(*) AS NUM_EDIT FROM '.$tbl_wiki.'
                WHERE  c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session.'
                GROUP BY user_id';
        $allpages = Database::query($sql);

        //show table
        if (Database::num_rows($allpages) > 0) {
            while ($obj = Database::fetch_object($allpages)) {
                $userinfo = api_get_user_info($obj->user_id);
                $row = [];
                if ($obj->user_id != 0 && $userinfo !== false) {
                    $row[] = Display::url(
                        $userinfo['complete_name_with_username'],
                        $this->url.'&'.http_build_query(['action' => 'usercontrib', 'user_id' => (int) $obj->user_id])
                    );
                } else {
                    $row[] = get_lang('Anonymous').' ('.$obj->user_ip.')';
                }
                $row[] = Display::url(
                    $obj->NUM_EDIT,
                    $this->url.'&'.http_build_query(['action' => 'usercontrib', 'user_id' => (int) $obj->user_id])
                );
                $rows[] = $row;
            }

            $table = new SortableTableFromArrayConfig(
                $rows,
                1,
                10,
                'MostActiveUsersA_table',
                '',
                '',
                'DESC'
            );
            $table->set_additional_parameters(
                [
                    'cidReq' => $this->courseCode,
                    'gidReq' => $this->group_id,
                    'id_session' => $this->session_id,
                    'action' => Security::remove_XSS($action),
                ]
            );
            $table->set_header(0, get_lang('Author'), true);
            $table->set_header(
                1,
                get_lang('Contributions'),
                true,
                ['style' => 'width:30px;']
            );
            $table->display();
        }
    }

    /**
     * @param string $page
     */
    public function getDiscuss($page)
    {
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $tbl_wiki_discuss = $this->tbl_wiki_discuss;

        if (0 != $this->session_id &&
            api_is_allowed_to_session_edit(false, true) == false
        ) {
            api_not_allowed();
        }

        if (!$_GET['title']) {
            Display::addFlash(
                Display::return_message(
                    get_lang("MustSelectPage"),
                    'error',
                    false
                )
            );

            return;
        }

        // First extract the date of last version
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    reflink = "'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id DESC';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $lastversiondate = api_get_local_time($row['dtime']);
        $lastuserinfo = api_get_user_info($row['user_id']);

        // Select page to discuss
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    reflink="'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id ASC';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $id = $row['id'];
        $firstuserid = $row['user_id'];

        if (isset($_POST['Submit']) && self::double_post($_POST['wpost_id'])) {
            $dtime = api_get_utc_datetime();
            $message_author = api_get_user_id();

            $params = [
                'c_id' => $this->course_id,
                'publication_id' => $id,
                'userc_id' => $message_author,
                'comment' => $_POST['comment'],
                'p_score' => $_POST['rating'],
                'dtime' => $dtime,
            ];
            $discussId = Database::insert($tbl_wiki_discuss, $params);
            if ($discussId) {
                $sql = "UPDATE $tbl_wiki_discuss SET id = iid WHERE iid = $discussId";
                Database::query($sql);
            }

            self::check_emailcue($id, 'D', $dtime, $message_author);

            header(
                'Location: '.$this->url.'&action=discuss&title='.api_htmlentities(urlencode($page))
            );
            exit;
        }

        // mode assignment: previous to show  page type
        $icon_assignment = null;
        if ($row['assignment'] == 1) {
            $icon_assignment = Display::return_icon(
                'wiki_assignment.png',
                get_lang('AssignmentDescExtra'),
                '',
                ICON_SIZE_SMALL
            );
        } elseif ($row['assignment'] == 2) {
            $icon_assignment = Display::return_icon(
                'wiki_work.png',
                get_lang('AssignmentWorkExtra'),
                '',
                ICON_SIZE_SMALL
            );
        }

        $countWPost = null;
        $avg_WPost_score = null;

        // Show title and form to discuss if page exist
        if ($id != '') {
            // Show discussion to students if isn't hidden.
            // Show page to all teachers if is hidden.
            // Mode assignments: If is hidden, show pages to student only if student is the author
            if ($row['visibility_disc'] == 1 ||
                api_is_allowed_to_edit(false, true) ||
                api_is_platform_admin() ||
                ($row['assignment'] == 2 && $row['visibility_disc'] == 0 && (api_get_user_id() == $row['user_id']))
            ) {
                echo '<div id="wikititle">';
                // discussion action: protecting (locking) the discussion
                $addlock_disc = null;
                $lock_unlock_disc = null;
                if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                    if (self::check_addlock_discuss() == 1) {
                        $addlock_disc = Display::return_icon(
                            'unlock.png',
                            get_lang('UnlockDiscussExtra'),
                            '',
                            ICON_SIZE_SMALL
                        );
                        $lock_unlock_disc = 'unlockdisc';
                    } else {
                        $addlock_disc = Display::return_icon(
                            'lock.png',
                            get_lang('LockDiscussExtra'),
                            '',
                            ICON_SIZE_SMALL
                        );
                        $lock_unlock_disc = 'lockdisc';
                    }
                }
                echo '<span style="float:right">';
                echo '<a href="'.$this->url.'&action=discuss&actionpage='.$lock_unlock_disc.'&title='.api_htmlentities(
                        urlencode($page)
                    ).'">'.$addlock_disc.'</a>';
                echo '</span>';

                // discussion action: visibility.  Show discussion to students if isn't hidden. Show page to all teachers if is hidden.
                $visibility_disc = null;
                $hide_show_disc = null;
                if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                    if (self::check_visibility_discuss() == 1) {
                        /// TODO: 	Fix Mode assignments: If is hidden, show discussion to student only if student is the author
                        $visibility_disc = Display::return_icon(
                            'visible.png',
                            get_lang('ShowDiscussExtra'),
                            '',
                            ICON_SIZE_SMALL
                        );
                        $hide_show_disc = 'hidedisc';
                    } else {
                        $visibility_disc = Display::return_icon(
                            'invisible.png',
                            get_lang('HideDiscussExtra'),
                            '',
                            ICON_SIZE_SMALL
                        );
                        $hide_show_disc = 'showdisc';
                    }
                }
                echo '<span style="float:right">';
                echo '<a href="'.$this->url.'&action=discuss&actionpage='.$hide_show_disc.'&title='.api_htmlentities(
                        urlencode($page)
                    ).'">'.$visibility_disc.'</a>';
                echo '</span>';

                // discussion action: check add rating lock. Show/Hide list to rating for all student
                $lock_unlock_rating_disc = null;
                $ratinglock_disc = null;
                if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                    if (self::check_ratinglock_discuss() == 1) {
                        $ratinglock_disc = Display::return_icon(
                            'star.png',
                            get_lang('UnlockRatingDiscussExtra'),
                            '',
                            ICON_SIZE_SMALL
                        );
                        $lock_unlock_rating_disc = 'unlockrating';
                    } else {
                        $ratinglock_disc = Display::return_icon(
                            'star_na.png',
                            get_lang('LockRatingDiscussExtra'),
                            '',
                            ICON_SIZE_SMALL
                        );
                        $lock_unlock_rating_disc = 'lockrating';
                    }
                }

                echo '<span style="float:right">';
                echo '<a href="'.$this->url.'&action=discuss&actionpage='.$lock_unlock_rating_disc.'&title='.api_htmlentities(
                        urlencode($page)
                    ).'">'.$ratinglock_disc.'</a>';
                echo '</span>';

                // discussion action: email notification
                if (self::check_notify_discuss($page) == 1) {
                    $notify_disc = Display::return_icon(
                        'messagebox_info.png',
                        get_lang('NotifyDiscussByEmail'),
                        '',
                        ICON_SIZE_SMALL
                    );
                    $lock_unlock_notify_disc = 'unlocknotifydisc';
                } else {
                    $notify_disc = Display::return_icon(
                        'mail.png',
                        get_lang('CancelNotifyDiscussByEmail'),
                        '',
                        ICON_SIZE_SMALL
                    );
                    $lock_unlock_notify_disc = 'locknotifydisc';
                }
                echo '<span style="float:right">';
                echo '<a href="'.$this->url.'&action=discuss&actionpage='.$lock_unlock_notify_disc.'&title='.api_htmlentities(
                        urlencode($page)
                    ).'">'.$notify_disc.'</a>';
                echo '</span>';
                echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.api_htmlentities(
                        $row['title']
                    );
                if ($lastuserinfo !== false) {
                    echo ' ('.get_lang('MostRecentVersionBy').' '.
                        UserManager::getUserProfileLink($lastuserinfo).' '.$lastversiondate.$countWPost.')'.$avg_WPost_score.' '; //TODO: read average score
                }

                echo '</div>';
                if ($row['addlock_disc'] == 1 || api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                    //show comments but students can't add theirs
                    ?>
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <form name="form1" method="post" action=""
                                  class="form-horizontal">
                                <div class="form-group">
                                    <label
                                        class="col-sm-2 control-label">
                                        <?php echo get_lang('Comments'); ?>:</label>
                                    <div class="col-sm-10">
                                        <?php echo '<input type="hidden" name="wpost_id" value="'.md5(uniqid(rand(), true)).'">'; //prevent double post?>
                                        <textarea class="form-control"
                                                  name="comment" cols="80"
                                                  rows="5"
                                                  id="comment">
                                        </textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <?php
                                    //check if rating is allowed
                                    if ($row['ratinglock_disc'] == 1 || api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                                        ?>
                                        <label
                                            class="col-sm-2 control-label"><?php echo get_lang('Rating'); ?>:</label>
                                        <div class="col-sm-10">
                                            <select name="rating" id="rating" class="selectpicker">
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
                                            </select>
                                        </div>
                                        <?php
                                    } else {
                                        echo '<input type=hidden name="rating" value="-">';
                                        // must pass a default value to avoid rate automatically
                                    } ?>

                                </div>
                                <div class="form-group">
                                    <div class="col-sm-offset-2 col-sm-10">
                                        <?php echo '<button class="btn btn-default" type="submit" name="Submit"> '.
                                            get_lang('Send').'</button>'; ?>
                                    </div>
                                </div>
                        </div>
                    </div>
                    </form>
                    <?php
                }
                // end discuss lock

                echo '<hr noshade size="1">';
                $user_table = Database::get_main_table(TABLE_MAIN_USER);

                $sql = "SELECT *
                        FROM $tbl_wiki_discuss reviews, $user_table user
                        WHERE
                            reviews.c_id = ".$this->course_id." AND
                            reviews.publication_id='".$id."' AND
                            user.user_id='".$firstuserid."'
                        ORDER BY reviews.id DESC";
                $result = Database::query($sql);

                $countWPost = Database::num_rows($result);
                echo get_lang('NumComments').": ".$countWPost; //comment's numbers

                $sql = "SELECT SUM(p_score) as sumWPost
                        FROM $tbl_wiki_discuss
                        WHERE c_id = ".$this->course_id." AND publication_id = '".$id."' AND NOT p_score='-'
                        ORDER BY id DESC";
                $result2 = Database::query($sql);
                $row2 = Database::fetch_array($result2);

                $sql = "SELECT * FROM $tbl_wiki_discuss
                        WHERE c_id = ".$this->course_id." AND publication_id='".$id."' AND NOT p_score='-'";
                $result3 = Database::query($sql);
                $countWPost_score = Database::num_rows($result3);

                echo ' - '.get_lang('NumCommentsScore').': '.$countWPost_score;

                if ($countWPost_score != 0) {
                    $avg_WPost_score = round($row2['sumWPost'] / $countWPost_score, 2).' / 10';
                } else {
                    $avg_WPost_score = $countWPost_score;
                }

                echo ' - '.get_lang('RatingMedia').': '.$avg_WPost_score; // average rating

                $sql = 'UPDATE '.$tbl_wiki.' SET
                        score = "'.Database::escape_string($avg_WPost_score).'"
                        WHERE
                            c_id = '.$this->course_id.' AND
                            reflink="'.Database::escape_string($page).'" AND
                            '.$groupfilter.$condition_session;
                // check if work ok. TODO:
                Database::query($sql);

                echo '<hr noshade size="1">';
                while ($row = Database::fetch_array($result)) {
                    $userinfo = api_get_user_info($row['userc_id']);
                    if (($userinfo['status']) == "5") {
                        $author_status = get_lang('Student');
                    } else {
                        $author_status = get_lang('Teacher');
                    }

                    $name = $userinfo['complete_name'];
                    $author_photo = '<img src="'.$userinfo['avatar'].'" alt="'.api_htmlentities($name).'"  width="40" height="50" align="top"  title="'.api_htmlentities($name).'"  />';

                    // stars
                    $p_score = $row['p_score'];
                    switch ($p_score) {
                        case 0:
                            $imagerating = Display::return_icon(
                                'rating/stars_0.gif'
                            );
                            break;
                        case 1:
                            $imagerating = Display::return_icon(
                                'rating/stars_5.gif'
                            );
                            break;
                        case 2:
                            $imagerating = Display::return_icon(
                                'rating/stars_10.gif'
                            );
                            break;
                        case 3:
                            $imagerating = Display::return_icon(
                                'rating/stars_15.gif'
                            );
                            break;
                        case 4:
                            $imagerating = Display::return_icon(
                                'rating/stars_20.gif'
                            );
                            break;
                        case 5:
                            $imagerating = Display::return_icon(
                                'rating/stars_25.gif'
                            );
                            break;
                        case 6:
                            $imagerating = Display::return_icon(
                                'rating/stars_30.gif'
                            );
                            break;
                        case 7:
                            $imagerating = Display::return_icon(
                                'rating/stars_35.gif'
                            );
                            break;
                        case 8:
                            $imagerating = Display::return_icon(
                                'rating/stars_40.gif'
                            );
                            break;
                        case 9:
                            $imagerating = Display::return_icon(
                                'rating/stars_45.gif'
                            );
                            break;
                        case 10:
                            $imagerating = Display::return_icon(
                                'rating/stars_50.gif'
                            );
                            break;
                    }
                    echo '<p><table>';
                    echo '<tr>';
                    echo '<td rowspan="2">'.$author_photo.'</td>';
                    $userProfile = '';
                    if ($userinfo !== false) {
                        $userProfile = UserManager::getUserProfileLink(
                            $userinfo
                        );
                    }
                    echo '<td style=" color:#999999">'.$userProfile.' ('.$author_status.') '.
                        api_get_local_time(
                            $row['dtime']
                        ).
                        ' - '.get_lang(
                            'Rating'
                        ).': '.$row['p_score'].' '.$imagerating.' </td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo '<td>'.api_htmlentities($row['comment']).'</td>';
                    echo '</tr>';
                    echo "</table>";
                }
            } else {
                Display::addFlash(
                    Display::return_message(
                        get_lang('LockByTeacher'),
                        'warning',
                        false
                    )
                );
            }
        } else {
            Display::addFlash(
                Display::return_message(
                    get_lang('DiscussNotAvailable'),
                    'normal',
                    false
                )
            );
        }
    }

    /**
     * Show all pages.
     */
    public function allPages($action)
    {
        echo '<div class="actions">'.get_lang('AllPages');

        // menu delete all wiki
        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            echo ' <a href="'.$this->url.'&action=deletewiki">'.
                Display::return_icon(
                    'delete.png',
                    get_lang('DeleteWiki'),
                    '',
                    ICON_SIZE_MEDIUM
                ).'</a>';
        }
        echo '</div>';

        //show table
        $table = new SortableTable(
            'AllPages_table',
            function () {
                $result = $this->gelAllPagesQuery(true);

                return (int) Database::fetch_assoc($result)['nbr'];
            },
            function ($from, $numberOfItems, $column, $direction) {
                $result = $this->gelAllPagesQuery(false, $from, $numberOfItems, $column, $direction);
                $rows = [];

                while ($data = Database::fetch_assoc($result)) {
                    $rows[] = [
                        $data['col0'],
                        [$data['col1'], $data['reflink'], $data['iid']],
                        [$data['col2'], $data['user_ip']],
                        $data['col3'],
                        $data['reflink'],
                    ];
                }

                return $rows;
            }
        );
        $table->set_additional_parameters(
            [
                'cidReq' => $this->courseCode,
                'gidReq' => $this->group_id,
                'id_session' => $this->session_id,
                'action' => Security::remove_XSS($action),
            ]
        );
        $table->set_header(
            0,
            get_lang('Type'),
            true,
            ['style' => 'width:30px;']
        );
        $table->set_header(1, get_lang('Title'));
        $table->set_header(
            2,
            get_lang('Author').' <small>'.get_lang('LastVersion').'</small>'
        );
        $table->set_header(
            3,
            get_lang('Date').' <small>'.get_lang('LastVersion').'</small>'
        );
        if (api_is_allowed_to_session_edit(false, true)) {
            $table->set_header(
                4,
                get_lang('Actions'),
                false,
                ['style' => 'width: 145px;']
            );
        }
        $table->set_column_filter(
            0,
            function ($value, string $urlParams, array $row) {
                $return = '';
                //get type assignment icon
                if (1 == $value) {
                    $return .= Display::return_icon(
                        'wiki_assignment.png',
                        get_lang('AssignmentDesc'),
                        '',
                        ICON_SIZE_SMALL
                    );
                } elseif (2 == $value) {
                    $return .= Display::return_icon(
                        'wiki_work.png',
                        get_lang('AssignmentWork'),
                        '',
                        ICON_SIZE_SMALL
                    );
                } elseif (0 == $value) {
                    $return .= Display::return_icon(
                        'px_transparent.gif'
                    );
                }

                //get icon task
                if (!empty($row['task'])) {
                    $return .= Display::return_icon(
                        'wiki_task.png',
                        get_lang('StandardTask'),
                        '',
                        ICON_SIZE_SMALL
                    );
                } else {
                    $return .= Display::return_icon('px_transparent.gif');
                }

                return $return;
            }
        );
        $table->set_column_filter(
            1,
            function ($value) {
                list($title, $refLink, $iid) = $value;

                return Display::url(
                        api_htmlentities($title),
                        $this->url.'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($refLink)])
                    )
                    .$this->returnCategoriesBlock($iid, '<div><small>', '</small></div>');
            }
        );
        $table->set_column_filter(
            2,
            function ($value) {
                list($userId, $userIp) = $value;
                //get author
                $userinfo = api_get_user_info($userId);

                if ($userinfo !== false) {
                    return UserManager::getUserProfileLink($userinfo);
                }

                return get_lang('Anonymous').' ('.api_htmlentities($userIp).')';
            }
        );
        $table->set_column_filter(
            3,
            function ($value) {
                return api_get_local_time($value);
            }
        );
        $table->set_column_filter(
            4,
            function ($value) {
                $actions = '';

                if (api_is_allowed_to_session_edit(false, true)) {
                    $actions = Display::url(
                            Display::return_icon('edit.png', get_lang('EditPage')),
                            $this->url.'&'.http_build_query(['action' => 'edit', 'title' => api_htmlentities($value)])
                        )
                        .Display::url(
                            Display::return_icon('discuss.png', get_lang('Discuss')),
                            $this->url.'&'.http_build_query(['action' => 'discuss', 'title' => api_htmlentities($value)])
                        )
                        .Display::url(
                            Display::return_icon('history.png', get_lang('History')),
                            $this->url.'&'.http_build_query(['action' => 'history', 'title' => api_htmlentities($value)])
                        )
                        .Display::url(
                            Display::return_icon('what_link_here.png', get_lang('LinksPages')),
                            $this->url.'&'.http_build_query(['action' => 'links', 'title' => api_htmlentities($value)])
                        )
                    ;
                }

                if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                    $actions .= Display::url(
                            Display::return_icon('delete.png', get_lang('Delete')),
                            $this->url.'&'.http_build_query(['action' => 'delete', 'title' => api_htmlentities($value)])
                        )
                    ;
                }

                return $actions;
            }
        );
        $table->display();
    }

    /**
     * Get recent changes.
     *
     * @param string $page
     * @param string $action
     */
    public function recentChanges($page, $action)
    {
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $tbl_wiki_conf = $this->tbl_wiki_conf;

        if (api_is_allowed_to_session_edit(false, true)) {
            if (self::check_notify_all() == 1) {
                $notify_all = Display::return_icon(
                        'messagebox_info.png',
                        get_lang('NotifyByEmail'),
                        '',
                        ICON_SIZE_SMALL
                    ).' '.get_lang('NotNotifyChanges');
                $lock_unlock_notify_all = 'unlocknotifyall';
            } else {
                $notify_all = Display::return_icon(
                        'mail.png',
                        get_lang('CancelNotifyByEmail'),
                        '',
                        ICON_SIZE_SMALL
                    ).' '.get_lang('NotifyChanges');
                $lock_unlock_notify_all = 'locknotifyall';
            }
        }

        echo '<div class="actions"><span style="float: right;">';
        echo '<a href="'.$this->url.'&action=recentchanges&actionpage='.$lock_unlock_notify_all.'&title='.api_htmlentities(
                urlencode($page)
            ).'">'.$notify_all.'</a>';
        echo '</span>'.get_lang('RecentChanges').'</div>';

        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            //only by professors if page is hidden
            $sql = 'SELECT * FROM '.$tbl_wiki.', '.$tbl_wiki_conf.'
        		WHERE 	'.$tbl_wiki_conf.'.c_id= '.$this->course_id.' AND
        				'.$tbl_wiki.'.c_id= '.$this->course_id.' AND
        				'.$tbl_wiki_conf.'.page_id='.$tbl_wiki.'.page_id AND
        				'.$tbl_wiki.'.'.$groupfilter.$condition_session.'
        		ORDER BY dtime DESC'; // new version
        } else {
            $sql = 'SELECT *
                FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    '.$groupfilter.$condition_session.' AND
                    visibility=1
                ORDER BY dtime DESC';
            // old version TODO: Replace by the bottom line
        }

        $allpages = Database::query($sql);

        //show table
        if (Database::num_rows($allpages) > 0) {
            $rows = [];
            while ($obj = Database::fetch_object($allpages)) {
                //get author
                $userinfo = api_get_user_info($obj->user_id);

                //get type assignment icon
                if ($obj->assignment == 1) {
                    $ShowAssignment = Display::return_icon(
                        'wiki_assignment.png',
                        get_lang('AssignmentDesc'),
                        '',
                        ICON_SIZE_SMALL
                    );
                } elseif ($obj->assignment == 2) {
                    $ShowAssignment = Display::return_icon(
                        'wiki_work.png',
                        get_lang('AssignmentWork'),
                        '',
                        ICON_SIZE_SMALL
                    );
                } elseif ($obj->assignment == 0) {
                    $ShowAssignment = Display::return_icon(
                        'px_transparent.gif'
                    );
                }

                // Get icon task
                if (!empty($obj->task)) {
                    $icon_task = Display::return_icon(
                        'wiki_task.png',
                        get_lang('StandardTask'),
                        '',
                        ICON_SIZE_SMALL
                    );
                } else {
                    $icon_task = Display::return_icon('px_transparent.gif');
                }

                $row = [];
                $row[] = api_get_local_time(
                    $obj->dtime
                );
                $row[] = $ShowAssignment.$icon_task;
                $row[] = Display::url(
                    api_htmlentities($obj->title),
                    $this->url.'&'.http_build_query(
                        [
                            'action' => 'showpage',
                            'title' => api_htmlentities($obj->reflink),
                            'view' => $obj->id,
                        ]
                    )
                );
                $row[] = $obj->version > 1 ? get_lang('EditedBy') : get_lang(
                    'AddedBy'
                );
                if ($userinfo !== false) {
                    $row[] = UserManager::getUserProfileLink($userinfo);
                } else {
                    $row[] = get_lang('Anonymous').' ('.api_htmlentities(
                            $obj->user_ip
                        ).')';
                }
                $rows[] = $row;
            }

            $table = new SortableTableFromArrayConfig(
                $rows,
                0,
                10,
                'RecentPages_table',
                '',
                '',
                'DESC'
            );
            $table->set_additional_parameters(
                [
                    'cidReq' => $this->courseCode,
                    'gidReq' => $this->group_id,
                    'id_session' => $this->session_id,
                    'action' => Security::remove_XSS($action),
                ]
            );
            $table->set_header(
                0,
                get_lang('Date'),
                true,
                ['style' => 'width:200px;']
            );
            $table->set_header(
                1,
                get_lang('Type'),
                true,
                ['style' => 'width:30px;']
            );
            $table->set_header(2, get_lang('Title'), true);
            $table->set_header(
                3,
                get_lang('Actions'),
                true,
                ['style' => 'width:80px;']
            );
            $table->set_header(4, get_lang('Author'), true);
            $table->display();
        }
    }

    /**
     * What links here. Show pages that have linked this page.
     *
     * @param string $page
     */
    public function getLinks($page)
    {
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $action = $this->action;

        if (!$_GET['title']) {
            Display::addFlash(
                Display::return_message(
                    get_lang("MustSelectPage"),
                    'error',
                    false
                )
            );
        } else {
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$this->course_id.' AND
                        reflink="'.Database::escape_string($page).'" AND
                        '.$groupfilter.$condition_session;
            $result = Database::query($sql);
            $row = Database::fetch_array($result);

            //get type assignment icon
            $ShowAssignment = '';
            if ($row['assignment'] == 1) {
                $ShowAssignment = Display::return_icon(
                    'wiki_assignment.png',
                    get_lang('AssignmentDesc'),
                    '',
                    ICON_SIZE_SMALL
                );
            } elseif ($row['assignment'] == 2) {
                $ShowAssignment = Display::return_icon(
                    'wiki_work.png',
                    get_lang('AssignmentWork'),
                    '',
                    ICON_SIZE_SMALL
                );
            } elseif ($row['assignment'] == 0) {
                $ShowAssignment = Display::return_icon('px_transparent.gif');
            }

            //fix Title to reflink (link Main Page)
            if ($page == get_lang('DefaultTitle')) {
                $page = 'index';
            }

            echo '<div id="wikititle">'
                .get_lang('LinksPagesFrom').": $ShowAssignment "
                .Display::url(
                    api_htmlentities($row['title']),
                    $this->url.'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($page)])
                )
                .'</div>'
            ;

            //fix index to title Main page into linksto

            if ($page == 'index') {
                $page = str_replace(' ', '_', get_lang('DefaultTitle'));
            }

            if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
                // only by professors if page is hidden
                $sql = "SELECT * FROM ".$tbl_wiki." s1
                        WHERE s1.c_id = ".$this->course_id." AND linksto LIKE '%".Database::escape_string(
                        $page
                    )."%' AND id=(
                        SELECT MAX(s2.id) FROM ".$tbl_wiki." s2
                        WHERE s2.c_id = ".$this->course_id." AND s1.reflink = s2.reflink AND ".$groupfilter.$condition_session.")";
            } else {
                //add blank space after like '%" " %' to identify each word
                $sql = "SELECT * FROM ".$tbl_wiki." s1
                        WHERE s1.c_id = ".$this->course_id." AND visibility=1 AND linksto LIKE '%".Database::escape_string(
                        $page
                    )."%' AND id=(
                        SELECT MAX(s2.id) FROM ".$tbl_wiki." s2
                        WHERE s2.c_id = ".$this->course_id." AND s1.reflink = s2.reflink AND ".$groupfilter.$condition_session.")";
            }

            $allpages = Database::query($sql);

            //show table
            if (Database::num_rows($allpages) > 0) {
                $rows = [];
                while ($obj = Database::fetch_object($allpages)) {
                    //get author
                    $userinfo = api_get_user_info($obj->user_id);

                    //get time
                    $year = substr($obj->dtime, 0, 4);
                    $month = substr($obj->dtime, 5, 2);
                    $day = substr($obj->dtime, 8, 2);
                    $hours = substr($obj->dtime, 11, 2);
                    $minutes = substr($obj->dtime, 14, 2);
                    $seconds = substr($obj->dtime, 17, 2);

                    //get type assignment icon
                    if ($obj->assignment == 1) {
                        $ShowAssignment = Display::return_icon(
                            'wiki_assignment.png',
                            get_lang('AssignmentDesc'),
                            '',
                            ICON_SIZE_SMALL
                        );
                    } elseif ($obj->assignment == 2) {
                        $ShowAssignment = Display::return_icon(
                            'wiki_work.png',
                            get_lang('AssignmentWork'),
                            '',
                            ICON_SIZE_SMALL
                        );
                    } elseif ($obj->assignment == 0) {
                        $ShowAssignment = Display::return_icon(
                            'px_transparent.gif'
                        );
                    }

                    $row = [];
                    $row[] = $ShowAssignment;
                    $row[] = Display::url(
                        api_htmlentities($obj->title),
                        $this->url.'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($obj->reflink)])
                    );
                    if ($userinfo !== false) {
                        $row[] = UserManager::getUserProfileLink($userinfo);
                    } else {
                        $row[] = get_lang('Anonymous').' ('.$obj->user_ip.')';
                    }
                    $row[] = $year.'-'.$month.'-'.$day.' '.$hours.":".$minutes.":".$seconds;
                    $rows[] = $row;
                }

                $table = new SortableTableFromArrayConfig(
                    $rows,
                    1,
                    10,
                    'AllPages_table',
                    '',
                    '',
                    'ASC'
                );
                $table->set_additional_parameters(
                    [
                        'cidReq' => $this->courseCode,
                        'gidReq' => $this->group_id,
                        'id_session' => $this->session_id,
                        'action' => Security::remove_XSS($action),
                    ]
                );
                $table->set_header(
                    0,
                    get_lang('Type'),
                    true,
                    ['style' => 'width:30px;']
                );
                $table->set_header(1, get_lang('Title'), true);
                $table->set_header(2, get_lang('Author'), true);
                $table->set_header(3, get_lang('Date'), true);
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
                $_GET['search_term'] = $_POST['search_term'] ?? '';
                $_GET['search_content'] = $_POST['search_content'] ?? '';
                $_GET['all_vers'] = $_POST['all_vers'] ?? '';
                $_GET['categories'] = $_POST['categories'] ?? [];
                $_GET['match_all_categories'] = !empty($_POST['match_all_categories']);
            }
            $this->display_wiki_search_results(
                $_GET['search_term'],
                (int) $_GET['search_content'],
                (int) $_GET['all_vers'],
                $_GET['categories'],
                $_GET['match_all_categories']
            );
        } else {
            // initiate the object
            $form = new FormValidator(
                'wiki_search',
                'get',
                $this->url.'&'.http_build_query(['action' => api_htmlentities($action), 'mode_table' => 'yes1'])
            );

            $form->addHidden('cidReq', $this->courseCode);
            $form->addHidden('id_session', $this->session_id);
            $form->addHidden('gidReq', $this->group_id);
            $form->addHidden('gradebook', '0');
            $form->addHidden('origin', '');
            $form->addHidden('action', 'searchpages');

            // Setting the form elements

            $form->addText(
                'search_term',
                get_lang('SearchTerm'),
                false,
                ['autofocus' => 'autofocus']
            );
            $form->addCheckBox('search_content', '', get_lang('AlsoSearchContent'));
            $form->addCheckbox('all_vers', '', get_lang('IncludeAllVersions'));

            if (true === api_get_configuration_value('wiki_categories_enabled')) {
                $categories = Database::getManager()
                    ->getRepository(CWikiCategory::class)
                    ->findByCourse(api_get_course_entity(), api_get_session_entity())
                ;

                $form->addSelectFromCollection(
                    'categories',
                    get_lang('Categories'),
                    $categories,
                    ['multiple' => 'multiple'],
                    false,
                    'getNodeName'
                );
                $form->addCheckBox(
                    'match_all_categories',
                    '',
                    get_lang('OnlyThoseThatCorrespondToAllTheSelectedCategories')
                );
            }

            $form->addButtonSearch(get_lang('Search'), 'SubmitWikiSearch');

            // setting the rules
            $form->addRule(
                'search_term',
                get_lang('TooShort'),
                'minlength',
                3
            ); //TODO: before fixing the pagination rules worked, not now

            if ($form->validate()) {
                $form->display();
                $values = $form->exportValues();
                $this->display_wiki_search_results(
                    $values['search_term'],
                    (int) ($values['search_content'] ?? ''),
                    (int) ($values['all_vers'] ?? ''),
                    $values['categories'] ?? [],
                    !empty($values['match_all_categories'])
                );
            } else {
                $form->display();
            }
        }
    }

    /**
     * @param int    $userId
     * @param string $action
     */
    public function getUserContributions($userId, $action)
    {
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $userId = (int) $userId;
        $userinfo = api_get_user_info($userId);
        if ($userinfo !== false) {
            echo '<div class="actions">'
                .Display::url(
                    get_lang('UserContributions').': '.$userinfo['complete_name_with_username'],
                    $this->url.'&'.http_build_query(['action' => 'usercontrib', 'user_id' => $userId])
                )
            ;
        }

        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            //only by professors if page is hidden
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$this->course_id.' AND
                        '.$groupfilter.$condition_session.' AND
                        user_id="'.$userId.'"';
        } else {
            $sql = 'SELECT * FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$this->course_id.' AND
                        '.$groupfilter.$condition_session.' AND
                        user_id="'.$userId.'" AND
                        visibility=1';
        }

        $allpages = Database::query($sql);

        //show table
        if (Database::num_rows($allpages) > 0) {
            $rows = [];
            while ($obj = Database::fetch_object($allpages)) {
                //get type assignment icon
                $ShowAssignment = '';
                if ($obj->assignment == 1) {
                    $ShowAssignment = Display::return_icon(
                        'wiki_assignment.png',
                        get_lang('AssignmentDescExtra'),
                        '',
                        ICON_SIZE_SMALL
                    );
                } elseif ($obj->assignment == 2) {
                    $ShowAssignment = Display::return_icon(
                        'wiki_work.png',
                        get_lang('AssignmentWork'),
                        '',
                        ICON_SIZE_SMALL
                    );
                } elseif ($obj->assignment == 0) {
                    $ShowAssignment = Display::return_icon(
                        'px_transparent.gif'
                    );
                }

                $row = [];
                $row[] = api_get_local_time($obj->dtime);
                $row[] = $ShowAssignment;
                $row[] = Display::url(
                    api_htmlentities($obj->title),
                    $this->url.'&'
                        .http_build_query([
                            'action' => 'showpage',
                            'title' => api_htmlentities($obj->reflink),
                            'view' => (int) $obj->id,
                        ])
                );
                $row[] = Security::remove_XSS($obj->version);
                $row[] = Security::remove_XSS($obj->comment);
                $row[] = Security::remove_XSS($obj->progress).' %';
                $row[] = Security::remove_XSS($obj->score);
                $rows[] = $row;
            }

            $table = new SortableTableFromArrayConfig(
                $rows,
                2,
                10,
                'UsersContributions_table',
                '',
                '',
                'ASC'
            );
            $table->set_additional_parameters(
                [
                    'cidReq' => $this->courseCode,
                    'gidReq' => $this->group_id,
                    'id_session' => $this->session_id,
                    'action' => Security::remove_XSS($action),
                    'user_id' => intval($userId),
                ]
            );
            $table->set_header(
                0,
                get_lang('Date'),
                true,
                ['style' => 'width:200px;']
            );
            $table->set_header(
                1,
                get_lang('Type'),
                true,
                ['style' => 'width:30px;']
            );
            $table->set_header(
                2,
                get_lang('Title'),
                true,
                ['style' => 'width:200px;']
            );
            $table->set_header(
                3,
                get_lang('Version'),
                true,
                ['style' => 'width:30px;']
            );
            $table->set_header(
                4,
                get_lang('Comment'),
                true,
                ['style' => 'width:200px;']
            );
            $table->set_header(
                5,
                get_lang('Progress'),
                true,
                ['style' => 'width:30px;']
            );
            $table->set_header(
                6,
                get_lang('Rating'),
                true,
                ['style' => 'width:30px;']
            );
            $table->display();
        }
    }

    /**
     * @param string $action
     */
    public function getMostChangedPages($action)
    {
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;

        echo '<div class="actions">'.get_lang('MostChangedPages').'</div>';

        if (api_is_allowed_to_edit(false, true) ||
            api_is_platform_admin()
        ) { //only by professors if page is hidden
            $sql = 'SELECT *, MAX(version) AS MAX FROM '.$tbl_wiki.'
                    WHERE c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session.'
                    GROUP BY reflink'; //TODO:check MAX and group by return last version
        } else {
            $sql = 'SELECT *, MAX(version) AS MAX FROM '.$tbl_wiki.'
                    WHERE c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session.' AND visibility=1
                    GROUP BY reflink'; //TODO:check MAX and group by return last version
        }

        $allpages = Database::query($sql);

        //show table
        if (Database::num_rows($allpages) > 0) {
            $rows = [];
            while ($obj = Database::fetch_object($allpages)) {
                //get type assignment icon
                $ShowAssignment = '';
                if ($obj->assignment == 1) {
                    $ShowAssignment = Display::return_icon(
                        'wiki_assignment.png',
                        get_lang('AssignmentDesc'),
                        '',
                        ICON_SIZE_SMALL
                    );
                } elseif ($obj->assignment == 2) {
                    $ShowAssignment = Display::return_icon(
                        'wiki_work.png',
                        get_lang('AssignmentWork'),
                        '',
                        ICON_SIZE_SMALL
                    );
                } elseif ($obj->assignment == 0) {
                    $ShowAssignment = Display::return_icon(
                        'px_transparent.gif'
                    );
                }

                $row = [];
                $row[] = $ShowAssignment;
                $row[] = Display::url(
                    api_htmlentities($obj->title),
                    $this->url.'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($obj->reflink)])
                );
                $row[] = $obj->MAX;
                $rows[] = $row;
            }

            $table = new SortableTableFromArrayConfig(
                $rows,
                2,
                10,
                'MostChangedPages_table',
                '',
                '',
                'DESC'
            );
            $table->set_additional_parameters(
                [
                    'cidReq' => $this->courseCode,
                    'gidReq' => $this->group_id,
                    'id_session' => $this->session_id,
                    'action' => Security::remove_XSS($action),
                ]
            );
            $table->set_header(
                0,
                get_lang('Type'),
                true,
                ['style' => 'width:30px;']
            );
            $table->set_header(1, get_lang('Title'), true);
            $table->set_header(2, get_lang('Changes'), true);
            $table->display();
        }
    }

    /**
     * Restore page.
     *
     * @return bool
     */
    public function restorePage()
    {
        $userId = api_get_user_id();
        $current_row = $this->getWikiData();
        $last_row = $this->getLastWikiData($this->page);

        if (empty($last_row)) {
            return false;
        }

        $PassEdit = false;

        /* Only teachers and platform admin can edit the index page.
        Only teachers and platform admin can edit an assignment teacher*/
        if (($current_row['reflink'] == 'index' ||
                $current_row['reflink'] == '' ||
                $current_row['assignment'] == 1) &&
            (!api_is_allowed_to_edit(false, true) &&
                $this->group_id == 0)
        ) {
            Display::addFlash(
                Display::return_message(
                    get_lang('OnlyEditPagesCourseManager'),
                    'normal',
                    false
                )
            );
        } else {
            // check if is a wiki group
            if ($current_row['group_id'] != 0) {
                $groupInfo = GroupManager::get_group_properties(
                    $this->group_id
                );
                //Only teacher, platform admin and group members can edit a wiki group
                if (api_is_allowed_to_edit(false, true) ||
                    api_is_platform_admin() ||
                    GroupManager::is_user_in_group($userId, $groupInfo) ||
                    api_is_allowed_in_course()
                ) {
                    $PassEdit = true;
                } else {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('OnlyEditPagesGroupMembers'),
                            'normal',
                            false
                        )
                    );
                }
            } else {
                $PassEdit = true;
            }

            // check if is an assignment
            //$icon_assignment = null;
            if ($current_row['assignment'] == 1) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('EditAssignmentWarning'),
                        'normal',
                        false
                    )
                );
            } elseif ($current_row['assignment'] == 2) {
                if (($userId == $current_row['user_id']) == false) {
                    if (api_is_allowed_to_edit(
                            false,
                            true
                        ) || api_is_platform_admin()) {
                        $PassEdit = true;
                    } else {
                        Display::addFlash(
                            Display::return_message(
                                get_lang('LockByTeacher'),
                                'normal',
                                false
                            )
                        );
                        $PassEdit = false;
                    }
                } else {
                    $PassEdit = true;
                }
            }

            //show editor if edit is allowed
            if ($PassEdit) {
                if ($current_row['editlock'] == 1 &&
                    (api_is_allowed_to_edit(false, true) == false ||
                        api_is_platform_admin() == false)
                ) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('PageLockedExtra'),
                            'normal',
                            false
                        )
                    );
                } else {
                    if ($last_row['is_editing'] != 0 && $last_row['is_editing'] != $userId) {
                        // Checking for concurrent users
                        $timestamp_edit = strtotime($last_row['time_edit']);
                        $time_editing = time() - $timestamp_edit;
                        $max_edit_time = 1200; // 20 minutes
                        $rest_time = $max_edit_time - $time_editing;
                        $userinfo = api_get_user_info($last_row['is_editing']);
                        $is_being_edited = get_lang(
                                'ThisPageisBeginEditedBy'
                            ).' <a href='.$userinfo['profile_url'].'>'.
                            Display::tag(
                                'span',
                                $userinfo['complete_name_with_username']
                            ).
                            get_lang('ThisPageisBeginEditedTryLater').' '.date(
                                "i",
                                $rest_time
                            ).' '.get_lang('MinMinutes');
                        Display::addFlash(
                            Display::return_message(
                                $is_being_edited,
                                'normal',
                                false
                            )
                        );
                    } else {
                        Display::addFlash(
                            Display::return_message(
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
                                ).': '
                                .Display::url(
                                    api_htmlentities($last_row['title']),
                                    $this->url.'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($last_row['reflink'])])
                                ),
                                'confirmation',
                                false
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * @param int|bool $wikiId
     */
    public function setWikiData($wikiId)
    {
        $this->wikiData = self::getWikiDataFromDb($wikiId);
    }

    /**
     * @return array
     */
    public function getWikiData()
    {
        return $this->wikiData;
    }

    /**
     * Check last version.
     *
     * @param int $view
     */
    public function checkLastVersion($view)
    {
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $page = $this->page;

        if (empty($view)) {
            return false;
        }

        $current_row = $this->getWikiData();
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    reflink = "'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id DESC'; //last version
        $result = Database::query($sql);
        $last_row = Database::fetch_array($result);

        if ($view < $last_row['id']) {
            $message = '<center>'.get_lang('NoAreSeeingTheLastVersion').'<br />'
                .get_lang("Version").' ('
                .Display::url(
                    $current_row['version'],
                    $this->url.'&'.http_build_query([
                        'action' => 'showpage',
                        'title' => api_htmlentities($current_row['reflink']),
                        'view' => (int) $_GET['view'],
                    ]),
                    ['title' => get_lang('CurrentVersion')]
                )
                .' / '
                .Display::url(
                    $last_row['version'],
                    $this->url.'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($last_row['reflink'])]),
                    ['title' => get_lang('LastVersion')]
                )
                .')<br>'.get_lang('ConvertToLastVersion').': '
                .Display::url(
                    get_lang("Restore"),
                    $this->url.'&'.http_build_query([
                        'action' => 'restorepage',
                        'title' => api_htmlentities($last_row['reflink']),
                        'view' => (int) $_GET['view'],
                    ])
                )
                .'</center>'
            ;
            echo Display::return_message($message, 'warning', false);
        }
    }

    /**
     *  Get most linked pages.
     */
    public function getMostLinked()
    {
        $tbl_wiki = $this->tbl_wiki;
        $groupfilter = $this->groupfilter;
        $condition_session = $this->condition_session;

        echo '<div class="actions">'.get_lang('MostLinkedPages').'</div>';
        $pages = [];
        $linked = [];

        // Get name pages
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE  c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session.'
                GROUP BY reflink
                ORDER BY reflink ASC';
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            if ($row['reflink'] == 'index') {
                $row['reflink'] = str_replace(
                    ' ',
                    '_',
                    get_lang('DefaultTitle')
                );
            }
            $pages[] = $row['reflink'];
        }

        // Get name refs in last pages
        $sql = 'SELECT *
                FROM '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$this->course_id.' AND id=(
                    SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                    WHERE
                        s2.c_id = '.$this->course_id.' AND
                        s1.reflink = s2.reflink AND
                        '.$groupfilter.$condition_session.'
                )';

        $allpages = Database::query($sql);

        while ($row = Database::fetch_array($allpages)) {
            //remove self reference
            $row['linksto'] = str_replace(
                $row["reflink"],
                " ",
                trim($row["linksto"])
            );
            $refs = explode(" ", trim($row["linksto"]));

            // Find linksto into reflink. If found ->page is linked
            foreach ($refs as $v) {
                if (in_array($v, $pages)) {
                    if (trim($v) != "") {
                        $linked[] = $v;
                    }
                }
            }
        }

        $linked = array_unique($linked);
        //make a unique list. TODO:delete this line and count how many for each page
        //show table
        $rows = [];
        foreach ($linked as $linked_show) {
            $row = [];
            $row[] = Display::url(
                str_replace('_', ' ', $linked_show),
                $this->url.'&'.http_build_query(['action' => 'showpage', 'title' => str_replace('_', ' ', $linked_show)])
            );
            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig(
            $rows,
            0,
            10,
            'LinkedPages_table',
            '',
            '',
            'DESC'
        );
        $table->set_additional_parameters(
            [
                'cidReq' => $this->courseCode,
                'gidReq' => $this->group_id,
                'id_session' => $this->session_id,
                'action' => Security::remove_XSS($this->action),
            ]
        );
        $table->set_header(0, get_lang('Title'), true);
        $table->display();
    }

    /**
     * Get orphan pages.
     */
    public function getOrphaned()
    {
        $tbl_wiki = $this->tbl_wiki;
        $groupfilter = $this->groupfilter;
        $condition_session = $this->condition_session;

        echo '<div class="actions">'.get_lang('OrphanedPages').'</div>';

        $pages = [];
        $orphaned = [];

        //get name pages
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session.'
                GROUP BY reflink
                ORDER BY reflink ASC';
        $allpages = Database::query($sql);
        while ($row = Database::fetch_array($allpages)) {
            $pages[] = $row['reflink'];
        }

        //get name refs in last pages and make a unique list
        $sql = 'SELECT  *  FROM   '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$this->course_id.' AND id=(
                SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                WHERE
                    s2.c_id = '.$this->course_id.' AND
                    s1.reflink = s2.reflink AND
                    '.$groupfilter.$condition_session.'
                )';
        $allpages = Database::query($sql);
        $array_refs_linked = [];
        while ($row = Database::fetch_array($allpages)) {
            $row['linksto'] = str_replace(
                $row["reflink"],
                " ",
                trim($row["linksto"])
            ); //remove self reference
            $refs = explode(" ", trim($row["linksto"]));
            foreach ($refs as $ref_linked) {
                if ($ref_linked == str_replace(
                        ' ',
                        '_',
                        get_lang('DefaultTitle')
                    )) {
                    $ref_linked = 'index';
                }
                $array_refs_linked[] = $ref_linked;
            }
        }

        $array_refs_linked = array_unique($array_refs_linked);

        //search each name of list linksto into list reflink
        foreach ($pages as $v) {
            if (!in_array($v, $array_refs_linked)) {
                $orphaned[] = $v;
            }
        }
        $rows = [];
        foreach ($orphaned as $orphaned_show) {
            // get visibility status and title
            $sql = 'SELECT *
                    FROM  '.$tbl_wiki.'
		            WHERE
		                c_id = '.$this->course_id.' AND
		                '.$groupfilter.$condition_session.' AND
		                reflink="'.Database::escape_string($orphaned_show).'"
                    GROUP BY reflink';
            $allpages = Database::query($sql);
            while ($row = Database::fetch_array($allpages)) {
                $orphaned_title = $row['title'];
                $orphaned_visibility = $row['visibility'];
                if ($row['assignment'] == 1) {
                    $ShowAssignment = Display::return_icon(
                        'wiki_assignment.png',
                        '',
                        '',
                        ICON_SIZE_SMALL
                    );
                } elseif ($row['assignment'] == 2) {
                    $ShowAssignment = Display::return_icon(
                        'wiki_work.png',
                        '',
                        '',
                        ICON_SIZE_SMALL
                    );
                } elseif ($row['assignment'] == 0) {
                    $ShowAssignment = Display::return_icon(
                        'px_transparent.gif'
                    );
                }
            }

            if (!api_is_allowed_to_edit(false, true) || !api_is_platform_admin(
                ) && $orphaned_visibility == 0) {
                continue;
            }

            //show table
            $row = [];
            $row[] = $ShowAssignment;
            $row[] = Display::url(
                api_htmlentities($orphaned_title),
                $this->url.'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($orphaned_show)])
            );
            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig(
            $rows,
            1,
            10,
            'OrphanedPages_table',
            '',
            '',
            'DESC'
        );
        $table->set_additional_parameters(
            [
                'cidReq' => $this->courseCode,
                'gidReq' => $this->group_id,
                'id_session' => $this->session_id,
                'action' => Security::remove_XSS($this->action),
            ]
        );
        $table->set_header(
            0,
            get_lang('Type'),
            true,
            ['style' => 'width:30px;']
        );
        $table->set_header(1, get_lang('Title'), true);
        $table->display();
    }

    /**
     * Get wanted pages.
     */
    public function getWantedPages()
    {
        $tbl_wiki = $this->tbl_wiki;
        $groupfilter = $this->groupfilter;
        $condition_session = $this->condition_session;

        echo '<div class="actions">'.get_lang('WantedPages').'</div>';
        $pages = [];
        $wanted = [];
        //get name pages
        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE  c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session.'
                GROUP BY reflink
                ORDER BY reflink ASC';
        $allpages = Database::query($sql);

        while ($row = Database::fetch_array($allpages)) {
            if ($row['reflink'] == 'index') {
                $row['reflink'] = str_replace(
                    ' ',
                    '_',
                    get_lang('DefaultTitle')
                );
            }
            $pages[] = $row['reflink'];
        }

        //get name refs in last pages
        $sql = 'SELECT * FROM   '.$tbl_wiki.' s1
                WHERE s1.c_id = '.$this->course_id.' AND id=(
                    SELECT MAX(s2.id) FROM '.$tbl_wiki.' s2
                    WHERE s2.c_id = '.$this->course_id.' AND s1.reflink = s2.reflink AND '.$groupfilter.$condition_session.'
                )';

        $allpages = Database::query($sql);

        while ($row = Database::fetch_array($allpages)) {
            $refs = explode(" ", trim($row["linksto"]));
            // Find linksto into reflink. If not found ->page is wanted
            foreach ($refs as $v) {
                if (!in_array($v, $pages)) {
                    if (trim($v) != "") {
                        $wanted[] = $v;
                    }
                }
            }
        }

        $wanted = array_unique($wanted); //make a unique list

        //show table
        $rows = [];
        foreach ($wanted as $wanted_show) {
            $row = [];
            $wanted_show = Security::remove_XSS($wanted_show);
            $row[] = Display::url(
                str_replace('_', ' ', $wanted_show),
                $this->url.'&'.http_build_query(['action' => 'addnew', 'title' => str_replace('_', ' ', $wanted_show)]),
                ['class' => 'new_wiki_link']
            );
            $rows[] = $row;
        }

        $table = new SortableTableFromArrayConfig(
            $rows,
            0,
            10,
            'WantedPages_table',
            '',
            '',
            'DESC'
        );
        $table->set_additional_parameters(
            [
                'cidReq' => $this->courseCode,
                'gidReq' => $this->group_id,
                'id_session' => $this->session_id,
                'action' => Security::remove_XSS($this->action),
            ]
        );
        $table->set_header(0, get_lang('Title'), true);
        $table->display();
    }

    /**
     * Most visited.
     */
    public function getMostVisited()
    {
        $tbl_wiki = $this->tbl_wiki;
        $groupfilter = $this->groupfilter;
        $condition_session = $this->condition_session;

        echo '<div class="actions">'.get_lang('MostVisitedPages').'</div>';

        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin(
            )) { //only by professors if page is hidden
            $sql = 'SELECT *, SUM(hits) AS tsum FROM '.$tbl_wiki.'
                    WHERE c_id = '.$this->course_id.' AND '.$groupfilter.$condition_session.'
                    GROUP BY reflink';
        } else {
            $sql = 'SELECT *, SUM(hits) AS tsum FROM '.$tbl_wiki.'
                    WHERE
                        c_id = '.$this->course_id.' AND
                        '.$groupfilter.$condition_session.' AND
                        visibility=1
                    GROUP BY reflink';
        }

        $allpages = Database::query($sql);

        //show table
        if (Database::num_rows($allpages) > 0) {
            $rows = [];
            while ($obj = Database::fetch_object($allpages)) {
                //get type assignment icon
                $ShowAssignment = '';
                if ($obj->assignment == 1) {
                    $ShowAssignment = Display::return_icon(
                        'wiki_assignment.png',
                        get_lang('AssignmentDesc'),
                        '',
                        ICON_SIZE_SMALL
                    );
                } elseif ($obj->assignment == 2) {
                    $ShowAssignment = $ShowAssignment = Display::return_icon(
                        'wiki_work.png',
                        get_lang('AssignmentWork'),
                        '',
                        ICON_SIZE_SMALL
                    );
                } elseif ($obj->assignment == 0) {
                    $ShowAssignment = Display::return_icon(
                        'px_transparent.gif'
                    );
                }

                $row = [];
                $row[] = $ShowAssignment;
                $row[] = Display::url(
                    api_htmlentities($obj->title),
                    $this->url.'&'.http_build_query(['action' => 'showpage', 'title' => api_htmlentities($obj->reflink)])
                );
                $row[] = $obj->tsum;
                $rows[] = $row;
            }

            $table = new SortableTableFromArrayConfig(
                $rows,
                2,
                10,
                'MostVisitedPages_table',
                '',
                '',
                'DESC'
            );
            $table->set_additional_parameters(
                [
                    'cidReq' => $this->courseCode,
                    'gidReq' => $this->group_id,
                    'id_session' => $this->session_id,
                    'action' => Security::remove_XSS($this->action),
                ]
            );
            $table->set_header(
                0,
                get_lang('Type'),
                true,
                ['style' => 'width:30px;']
            );
            $table->set_header(1, get_lang('Title'), true);
            $table->set_header(2, get_lang('Visits'), true);
            $table->display();
        }
    }

    /**
     * Get actions bar.
     */
    public function showActionBar()
    {
        $page = $this->page;
        $actionsLeft = Display::url(
            Display::return_icon('home.png', get_lang('Home'), [], ICON_SIZE_MEDIUM),
            $this->url.'&'.http_build_query(['action' => 'showpage', 'title' => 'index'])
        );

        if (api_is_allowed_to_session_edit(false, true) && api_is_allowed_to_edit()) {
            // menu add page
            $actionsLeft .= '<a href="'.$this->url.'&action=addnew" '.self::is_active_navigation_tab('addnew').'>'
                .Display::return_icon('new_document.png', get_lang('AddNew'), [], ICON_SIZE_MEDIUM).'</a>';
        }

        if (
            true === api_get_configuration_value('wiki_categories_enabled')
            && (api_is_allowed_to_edit(false, true) || api_is_platform_admin())
        ) {
            $actionsLeft .= Display::url(
                Display::return_icon('folder.png', get_lang('Categories'), [], ICON_SIZE_MEDIUM),
                $this->url.'&action=category'
            );

            // page action: enable or disable the adding of new pages
            if (self::check_addnewpagelock() == 0) {
                $protect_addnewpage = Display::return_icon(
                    'off.png',
                    get_lang('AddOptionProtected')
                );
                $lock_unlock_addnew = 'unlockaddnew';
            } else {
                $protect_addnewpage = Display::return_icon(
                    'on.png',
                    get_lang('AddOptionUnprotected')
                );
                $lock_unlock_addnew = 'lockaddnew';
            }
        }

        // menu find
        $actionsLeft .= '<a href="'.$this->url.'&action=searchpages"'.self::is_active_navigation_tab('searchpages').'>'
            .Display::return_icon('search.png', get_lang('SearchPages'), '', ICON_SIZE_MEDIUM).'</a>';
        ///menu more
        $actionsLeft .= '<a href="'.$this->url.'&action=more&title='.api_htmlentities(urlencode($page)).'" '
            .self::is_active_navigation_tab('more').'>'
            .Display::return_icon('statistics.png', get_lang('Statistics'), [], ICON_SIZE_MEDIUM).'</a>';

        // menu all pages
        $actionsLeft .= '<a href="'.$this->url.'&action=allpages" '.self::is_active_navigation_tab('allpages').'>'
            .Display::return_icon('list_badges.png', get_lang('AllPages'), [], ICON_SIZE_MEDIUM).'</a>';
        // menu recent changes
        $actionsLeft .= '<a href="'.$this->url.'&action=recentchanges" '.self::is_active_navigation_tab('recentchanges').'>'
            .Display::return_icon('history.png', get_lang('RecentChanges'), [], ICON_SIZE_MEDIUM).'</a>';

        $frmSearch = new FormValidator('wiki_search', 'get', '', '', [], FormValidator::LAYOUT_INLINE);
        $frmSearch->addText('search_term', get_lang('SearchTerm'), false);
        $frmSearch->addHidden('cidReq', $this->courseCode);
        $frmSearch->addHidden('id_session', $this->session_id);
        $frmSearch->addHidden('gidReq', $this->group_id);
        $frmSearch->addHidden('gradebook', '0');
        $frmSearch->addHidden('origin', '');
        $frmSearch->addHidden('action', 'searchpages');
        $frmSearch->addButtonSearch(get_lang('Search'));

        $actionsRight = $frmSearch->returnForm();

        echo Display::toolbarAction('toolbar-wiki', [$actionsLeft, $actionsRight]);
    }

    /**
     * Showing warning.
     */
    public function deletePageWarning()
    {
        $page = $this->page;
        $groupfilter = $this->groupfilter;
        $condition_session = $this->condition_session;

        if (!$_GET['title']) {
            Display::addFlash(
                Display::return_message(
                    get_lang('MustSelectPage'),
                    'error',
                    false
                )
            );

            return;
        }

        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            Display::addFlash(
                '<div id="wikititle">'.get_lang('DeletePageHistory').'</div>'
            );
            if ($page == "index") {
                Display::addFlash(
                    Display::return_message(
                        get_lang('WarningDeleteMainPage'),
                        'warning',
                        false
                    )
                );
            }
            $message = get_lang('ConfirmDeletePage')."
                <a href=\"".$this->url."\">".get_lang("No")."</a>
                <a href=\"".$this->url."&action=delete&title=".api_htmlentities(urlencode($page))."&delete=yes\">".
                get_lang("Yes")."</a>";

            if (!isset($_GET['delete'])) {
                Display::addFlash(
                    Display::return_message($message, 'warning', false)
                );
            }

            if (isset($_GET['delete']) && $_GET['delete'] == 'yes') {
                $result = self::deletePage(
                    $page,
                    $this->course_id,
                    $groupfilter,
                    $condition_session
                );
                if ($result) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('WikiPageDeleted'),
                            'confirmation',
                            false
                        )
                    );
                }
            }
        } else {
            Display::addFlash(
                Display::return_message(
                    get_lang('OnlyAdminDeletePageWiki'),
                    'normal',
                    false
                )
            );
        }
    }

    /**
     * Edit page.
     */
    public function editPage()
    {
        $tbl_wiki = $this->tbl_wiki;
        $tbl_wiki_conf = $this->tbl_wiki_conf;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $page = $this->page;
        $userId = api_get_user_id();

        if (0 != $this->session_id &&
            api_is_allowed_to_session_edit(false, true) == false
        ) {
            api_not_allowed();
        }

        $sql = 'SELECT *
            FROM '.$tbl_wiki.' w INNER JOIN '.$tbl_wiki_conf.' c
            ON  (w.c_id = c.c_id AND w.page_id = c.page_id)
            WHERE
                w.c_id = '.$this->course_id.' AND
                w.reflink= "'.Database::escape_string($page).'" AND
                w.'.$groupfilter.$condition_session.'
            ORDER BY id DESC';
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        $PassEdit = false;
        // Check if is a wiki group
        if (!empty($this->group_id)) {
            $groupInfo = GroupManager::get_group_properties($this->group_id);
            //Only teacher, platform admin and group members can edit a wiki group
            if (api_is_allowed_to_edit(false, true) ||
                api_is_platform_admin() ||
                GroupManager::is_user_in_group($userId, $groupInfo)
            ) {
                $PassEdit = true;
            } else {
                Display::addFlash(
                    Display::return_message(
                        get_lang('OnlyEditPagesGroupMembers')
                    )
                );
            }
        } else {
            $PassEdit = true;
        }

        $content = '<div class="text-center">'
            .sprintf(get_lang('DefaultContent'), api_get_path(WEB_IMG_PATH))
            .'</div>';
        $title = get_lang('DefaultTitle');
        $page_id = 0;

        $icon_assignment = '';

        // we do not need awhile loop since we are always displaying the last version
        if ($row) {
            if ($row['content'] == '' && $row['title'] == '' && $page == '') {
                Display::addFlash(
                    Display::return_message(get_lang('MustSelectPage'), 'error', false)
                );

                return;
            }

            $content = api_html_entity_decode($row['content']);
            $title = api_html_entity_decode($row['title']);
            $page_id = $row['page_id'];

            // Only teachers and platform admin can edit the index page.
            // Only teachers and platform admin can edit an assignment teacher.
            // And users in groups

            if (($row['reflink'] == 'index' || $row['reflink'] == '' || $row['assignment'] == 1)
                && (!api_is_allowed_to_edit(false, true) && 0 == $this->group_id)
                && !api_is_allowed_in_course()
            ) {
                Display::addFlash(
                    Display::return_message(get_lang('OnlyEditPagesCourseManager'), 'error')
                );

                return;
            }

            // check if is an assignment
            if ($row['assignment'] == 1) {
                Display::addFlash(
                    Display::return_message(get_lang('EditAssignmentWarning'))
                );

                $icon_assignment = Display::return_icon('wiki_assignment.png', get_lang('AssignmentDescExtra'));
            } elseif ($row['assignment'] == 2) {
                $icon_assignment = Display::return_icon('wiki_work.png', get_lang('AssignmentWorkExtra'));
                if (($userId == $row['user_id']) == false) {
                    if (api_is_allowed_to_edit(
                            false,
                            true
                        ) || api_is_platform_admin()) {
                        $PassEdit = true;
                    } else {
                        Display::addFlash(
                            Display::return_message(get_lang('LockByTeacher'), 'warning')
                        );
                        $PassEdit = false;
                    }
                } else {
                    $PassEdit = true;
                }
            }

            if ($PassEdit) {
                if ($row['editlock'] == 1 &&
                    (api_is_allowed_to_edit(false, true) == false ||
                        api_is_platform_admin() == false)
                ) {
                    Display::addFlash(
                        Display::return_message(get_lang('PageLockedExtra'))
                    );
                }
            }
        }

        if ($PassEdit) {
            //show editor if edit is allowed <<<<<
            if ((!empty($row['id']) && $row['editlock'] != 1)
                || api_is_allowed_to_edit(false, true) != false
                && api_is_platform_admin() != false
            ) {
                // Check tasks
                if (!empty($row['startdate_assig']) && time() <
                    api_strtotime($row['startdate_assig'])
                ) {
                    $message = get_lang('TheTaskDoesNotBeginUntil').': '.api_get_local_time($row['startdate_assig']);

                    Display::addFlash(
                        Display::return_message($message, 'warning')
                    );

                    if (!api_is_allowed_to_edit(false, true)) {
                        $this->redirectHome();
                    }
                }

                if (!empty($row['enddate_assig']) &&
                    time() > strtotime($row['enddate_assig']) &&
                    $row['delayedsubmit'] == 0
                ) {
                    $message = get_lang('TheDeadlineHasBeenCompleted').': '.api_get_local_time($row['enddate_assig']);
                    Display::addFlash(
                        Display::return_message($message, 'warning')
                    );
                    if (!api_is_allowed_to_edit(false, true)) {
                        $this->redirectHome();
                    }
                }

                if (!empty($row['max_version']) && $row['version'] >= $row['max_version']) {
                    $message = get_lang('HasReachedMaxiNumVersions');
                    Display::addFlash(
                        Display::return_message($message, 'warning')
                    );
                    if (!api_is_allowed_to_edit(false, true)) {
                        $this->redirectHome();
                    }
                }

                if (!empty($row['max_text']) && $row['max_text'] <= self::word_count(
                        $row['content']
                    )) {
                    $message = get_lang('HasReachedMaxNumWords');
                    Display::addFlash(
                        Display::return_message($message, 'warning')
                    );
                    if (!api_is_allowed_to_edit(false, true)) {
                        $this->redirectHome();
                    }
                }

                if (!empty($row['task'])) {
                    //previous change 0 by text
                    $message_task_startdate = empty($row['startdate_assig'])
                        ? api_get_local_time($row['startdate_assig'])
                        : get_lang('No');

                    $message_task_enddate = empty($row['enddate_assig'])
                        ? api_get_local_time($row['enddate_assig'])
                        : get_lang('No');

                    $message_task_delayedsubmit = $row['delayedsubmit'] == 0 ? get_lang('No') : get_lang('Yes');

                    $message_task_max_version = $row['max_version'] == 0 ? get_lang('No') : $row['max_version'];

                    $message_task_max_text = $row['max_text'] == 0 ? get_lang('No') : $row['max_text'];

                    // Comp message
                    $message_task = '<b>'.get_lang('DescriptionOfTheTask').'</b><p>'.$row['task'].'</p><hr>'
                        .'<p>'.get_lang('StartDate').': '.$message_task_startdate.'</p>'
                        .'<p>'.get_lang('EndDate').': '.$message_task_enddate
                        .' ('.get_lang('AllowLaterSends').') '.$message_task_delayedsubmit.'</p>'
                        .'<p>'.get_lang('OtherSettings').': '.get_lang('NMaxVersion').': '.$message_task_max_version
                        .' '.get_lang('NMaxWords').': '.$message_task_max_text.'</p>';
                    // Display message
                    Display::addFlash(
                        Display::return_message($message_task)
                    );
                }

                if (!empty($row['id'])) {
                    $feedback_message = '';
                    if ($row['progress'] == $row['fprogress1'] && !empty($row['fprogress1'])) {
                        $feedback_message = '<b>'.get_lang('Feedback').'</b>'
                            .'<p>'.api_htmlentities($row['feedback1']).'</p>';
                    } elseif ($row['progress'] == $row['fprogress2'] && !empty($row['fprogress2'])) {
                        $feedback_message = '<b>'.get_lang('Feedback').'</b>'
                            .'<p>'.api_htmlentities($row['feedback2']).'</p>';
                    } elseif ($row['progress'] == $row['fprogress3'] && !empty($row['fprogress3'])) {
                        $feedback_message = '<b>'.get_lang('Feedback').'</b>'
                            .'<p>'.api_htmlentities($row['feedback3']).'</p>';
                    }

                    if (!empty($feedback_message)) {
                        Display::addFlash(
                            Display::return_message($feedback_message)
                        );
                    }
                }

                // Previous checking for concurrent editions
                if (!empty($row['id']) && $row['is_editing'] == 0) {
                    Display::addFlash(
                        Display::return_message(get_lang('WarningMaxEditingTime'))
                    );
                    $time_edit = api_get_utc_datetime();
                    $sql = 'UPDATE '.$tbl_wiki.' SET
                            is_editing = "'.$userId.'",
                            time_edit = "'.$time_edit.'"
                            WHERE c_id = '.$this->course_id.' AND id="'.$row['id'].'"';
                    Database::query($sql);
                } elseif (!empty($row['id']) && $row['is_editing'] != $userId) {
                    $timestamp_edit = strtotime($row['time_edit']);
                    $time_editing = time() - $timestamp_edit;
                    $max_edit_time = 1200; // 20 minutes
                    $rest_time = $max_edit_time - $time_editing;

                    $userinfo = api_get_user_info($row['is_editing']);
                    if ($userinfo !== false) {
                        $is_being_edited = get_lang('ThisPageisBeginEditedBy').PHP_EOL
                            .UserManager::getUserProfileLink($userinfo).PHP_EOL
                            .get_lang('ThisPageisBeginEditedTryLater').PHP_EOL
                            .date("i", $rest_time).PHP_EOL
                            .get_lang('MinMinutes');

                        Display::addFlash(
                            Display::return_message($is_being_edited, 'normal', false)
                        );
                    }

                    $this->redirectHome();
                }

                // Form.
                $url = $this->url.'&'.http_build_query(['action' => 'edit', 'title' => $page]);
                $form = new FormValidator('wiki', 'post', $url);
                $form->addElement(
                    'header',
                    $icon_assignment.str_repeat('&nbsp;', 3).api_htmlentities($title)
                );
                self::setForm($form, !empty($row['id']) ? $row : []);
                $form->addElement('hidden', 'title');
                $form->addButtonSave(get_lang('Save'), 'SaveWikiChange');
                $row['title'] = $title;
                $row['page_id'] = $page_id;
                $row['reflink'] = $page;
                $row['content'] = $content;

                if (!empty($row['id']) && true === api_get_configuration_value('wiki_categories_enabled')) {
                    $wiki = Database::getManager()->find(CWiki::class, $row['id']);

                    foreach ($wiki->getCategories() as $category) {
                        $row['category'][] = $category->getId();
                    }
                }

                $form->setDefaults($row);
                $form->display();

                // Saving a change
                if ($form->validate()) {
                    $versionFromSession = Session::read('_version');
                    if (empty($_POST['title'])) {
                        Display::addFlash(
                            Display::return_message(
                                get_lang("NoWikiPageTitle"),
                                'error'
                            )
                        );
                    } elseif (!self::double_post($_POST['wpost_id'])) {
                        //double post
                    } elseif ($_POST['version'] != '' && $versionFromSession != 0 && $_POST['version'] != $versionFromSession) {
                        //prevent concurrent users and double version
                        Display::addFlash(
                            Display::return_message(
                                get_lang("EditedByAnotherUser"),
                                'error'
                            )
                        );
                    } else {
                        $returnMessage = self::save_wiki(
                            $form->exportValues()
                        );
                        Display::addFlash(
                            Display::return_message(
                                $returnMessage,
                                'confirmation'
                            )
                        );
                    }
                    $wikiData = self::getWikiData();
                    $redirectUrl = $this->url.'&action=showpage&title='.$wikiData['reflink'];
                    header('Location: '.$redirectUrl);
                    exit;
                }
            }
        }
    }

    /**
     * Get history.
     */
    public function getHistory()
    {
        $tbl_wiki = $this->tbl_wiki;
        $condition_session = $this->condition_session;
        $groupfilter = $this->groupfilter;
        $page = $this->page;
        $course_id = $this->course_id;
        $session_id = $this->session_id;
        $userId = api_get_user_id();

        if (!$_GET['title']) {
            Display::addFlash(
                Display::return_message(
                    get_lang("MustSelectPage"),
                    'error',
                    false
                )
            );

            return;
        }

        /* First, see the property visibility that is at the last register and
        therefore we should select descending order.
        But to give ownership to each record,
        this is no longer necessary except for the title. TODO: check this*/

        $sql = 'SELECT * FROM '.$tbl_wiki.'
                WHERE
                    c_id = '.$this->course_id.' AND
                    reflink="'.Database::escape_string($page).'" AND
                    '.$groupfilter.$condition_session.'
                ORDER BY id DESC';
        $result = Database::query($sql);

        $KeyVisibility = null;
        $KeyAssignment = null;
        $KeyTitle = null;
        $KeyUserId = null;
        while ($row = Database::fetch_array($result)) {
            $KeyVisibility = $row['visibility'];
            $KeyAssignment = $row['assignment'];
            $KeyTitle = $row['title'];
            $KeyUserId = $row['user_id'];
        }
        $icon_assignment = null;
        if ($KeyAssignment == 1) {
            $icon_assignment = Display::return_icon(
                'wiki_assignment.png',
                get_lang('AssignmentDescExtra'),
                '',
                ICON_SIZE_SMALL
            );
        } elseif ($KeyAssignment == 2) {
            $icon_assignment = Display::return_icon(
                'wiki_work.png',
                get_lang('AssignmentWorkExtra'),
                '',
                ICON_SIZE_SMALL
            );
        }

        // Second, show
        //if the page is hidden and is a job only sees its author and professor
        if ($KeyVisibility == 1 ||
            api_is_allowed_to_edit(false, true) ||
            api_is_platform_admin() ||
            (
                $KeyAssignment == 2 && $KeyVisibility == 0 &&
                ($userId == $KeyUserId)
            )
        ) {
            // We show the complete history
            if (!isset($_POST['HistoryDifferences']) &&
                !isset($_POST['HistoryDifferences2'])
            ) {
                $sql = 'SELECT * FROM '.$tbl_wiki.'
                        WHERE
                            c_id = '.$this->course_id.' AND
                            reflink="'.Database::escape_string($page).'" AND
                            '.$groupfilter.$condition_session.'
                        ORDER BY id DESC';
                $result = Database::query($sql);
                $title = $_GET['title'];

                echo '<div id="wikititle">';
                echo $icon_assignment.'&nbsp;&nbsp;&nbsp;'.api_htmlentities(
                        $KeyTitle
                    );
                echo '</div>';

                $actionUrl = $this->url.'&'.http_build_query(['action' => 'history', 'title' => api_htmlentities($title)]);
                echo '<form id="differences" method="POST" action="'.$actionUrl.'">';

                echo '<ul style="list-style-type: none;">';
                echo '<br/>';
                echo '<button class="search" type="submit" name="HistoryDifferences" value="HistoryDifferences">'.
                    get_lang('ShowDifferences').' '.get_lang(
                        'LinesDiff'
                    ).'</button>';
                echo '<button class="search" type="submit" name="HistoryDifferences2" value="HistoryDifferences2">'.
                    get_lang('ShowDifferences').' '.get_lang(
                        'WordsDiff'
                    ).'</button>';
                echo '<br/><br/>';

                $counter = 0;
                $total_versions = Database::num_rows($result);

                while ($row = Database::fetch_array($result)) {
                    $userinfo = api_get_user_info($row['user_id']);
                    $username = api_htmlentities(
                        sprintf(get_lang('LoginX'), $userinfo['username']),
                        ENT_QUOTES
                    );

                    echo '<li style="margin-bottom: 5px;">';
                    ($counter == 0) ? $oldstyle = 'style="visibility: hidden;"' : $oldstyle = '';
                    ($counter == 0) ? $newchecked = ' checked' : $newchecked = '';
                    ($counter == $total_versions - 1) ? $newstyle = 'style="visibility: hidden;"' : $newstyle = '';
                    ($counter == 1) ? $oldchecked = ' checked' : $oldchecked = '';
                    echo '<input name="old" value="'.$row['id'].'" type="radio" '.$oldstyle.' '.$oldchecked.'/> ';
                    echo '<input name="new" value="'.$row['id'].'" type="radio" '.$newstyle.' '.$newchecked.'/> ';
                    echo '<a href="'.$this->url.'&action=showpage&title='.api_htmlentities(urlencode($page)).'&view='.$row['id'].'">';
                    echo api_get_local_time($row['dtime']);
                    echo '</a>';
                    echo ' ('.get_lang('Version').' '.$row['version'].')';
                    echo ' '.get_lang('By').' ';
                    if ($userinfo !== false) {
                        echo UserManager::getUserProfileLink($userinfo);
                    } else {
                        echo get_lang('Anonymous').' ('.api_htmlentities(
                                $row['user_ip']
                            ).')';
                    }
                    echo ' ( '.get_lang('Progress').': '.api_htmlentities(
                            $row['progress']
                        ).'%, ';
                    $comment = $row['comment'];
                    if (!empty($comment)) {
                        $comment = api_substr($comment, 0, 100);
                        if ($comment !== false) {
                            $comment = api_htmlentities($comment);
                            echo get_lang('Comments').': '.$comment;
                            if (api_strlen($row['comment']) > 100) {
                                echo '... ';
                            }
                        }
                    } else {
                        echo get_lang('Comments').':  ---';
                    }
                    echo ' ) </li>';
                    $counter++;
                } //end while

                echo '<br/>';
                echo '<button class="search" type="submit" name="HistoryDifferences" value="HistoryDifferences">'.get_lang(
                        'ShowDifferences'
                    ).' '.get_lang('LinesDiff').'</button>';
                echo '<button class="search" type="submit" name="HistoryDifferences2" value="HistoryDifferences2">'.get_lang(
                        'ShowDifferences'
                    ).' '.get_lang('WordsDiff').'</button>';
                echo '</ul></form>';
            } else { // We show the differences between two versions
                $version_old = [];
                if (isset($_POST['old'])) {
                    $sql_old = "SELECT * FROM $tbl_wiki
                                WHERE c_id = ".$this->course_id." AND id='".Database::escape_string(
                            $_POST['old']
                        )."'";
                    $result_old = Database::query($sql_old);
                    $version_old = Database::fetch_array($result_old);
                }

                $sql_new = "SELECT * FROM $tbl_wiki
                            WHERE
                              c_id = ".$this->course_id." AND
                              id = '".Database::escape_string($_POST['new'])."'";
                $result_new = Database::query($sql_new);
                $version_new = Database::fetch_array($result_new);
                $oldTime = isset($version_old['dtime']) ? api_get_local_time($version_old['dtime']) : null;
                $oldContent = isset($version_old['content']) ? $version_old['content'] : null;

                if (isset($_POST['HistoryDifferences'])) {
                    include 'diff.inc.php';
                    //title
                    echo '<div id="wikititle">'.api_htmlentities(
                            $version_new['title']
                        ).'
                            <font size="-2"><i>('.get_lang('DifferencesNew').'</i>
                            <font style="background-color:#aaaaaa">'.api_get_local_time($version_new['dtime']).'</font>
                            <i>'.get_lang('DifferencesOld').'</i>
                            <font style="background-color:#aaaaaa">'.$oldTime.'</font>
                ) '.get_lang('Legend').':  <span class="diffAdded" >'.get_lang(
                            'WikiDiffAddedLine'
                        ).'</span>
                <span class="diffDeleted" >'.get_lang(
                            'WikiDiffDeletedLine'
                        ).'</span> <span class="diffMoved">'.get_lang(
                            'WikiDiffMovedLine'
                        ).'</span></font>
                </div>';
                }
                if (isset($_POST['HistoryDifferences2'])) {
                    //title
                    echo '<div id="wikititle">'.api_htmlentities(
                            $version_new['title']
                        ).'
                        <font size="-2"><i>('.get_lang(
                            'DifferencesNew'
                        ).'</i> <font style="background-color:#aaaaaa">'.api_get_local_time($version_new['dtime']).'</font>
                        <i>'.get_lang(
                            'DifferencesOld'
                        ).'</i> <font style="background-color:#aaaaaa">'.$oldTime.'</font>)
                        '.get_lang(
                            'Legend'
                        ).':  <span class="diffAddedTex" >'.get_lang(
                            'WikiDiffAddedTex'
                        ).'</span>
                        <span class="diffDeletedTex" >'.get_lang(
                            'WikiDiffDeletedTex'
                        ).'</span></font></div>';
                }

                if (isset($_POST['HistoryDifferences'])) {
                    echo '<table>'.diff(
                            $oldContent,
                            $version_new['content'],
                            true,
                            'format_table_line'
                        ).'</table>'; // format_line mode is better for words
                    echo '<br />';
                    echo '<strong>'.get_lang(
                            'Legend'
                        ).'</strong><div class="diff">'."\n";
                    echo '<table><tr>';
                    echo '<td>';
                    echo '</td><td>';
                    echo '<span class="diffEqual" >'.get_lang(
                            'WikiDiffUnchangedLine'
                        ).'</span><br />';
                    echo '<span class="diffAdded" >'.get_lang(
                            'WikiDiffAddedLine'
                        ).'</span><br />';
                    echo '<span class="diffDeleted" >'.get_lang(
                            'WikiDiffDeletedLine'
                        ).'</span><br />';
                    echo '<span class="diffMoved" >'.get_lang(
                            'WikiDiffMovedLine'
                        ).'</span><br />';
                    echo '</td>';
                    echo '</tr></table>';
                }

                if (isset($_POST['HistoryDifferences2'])) {
                    $lines1 = [strip_tags($oldContent)]; //without <> tags
                    $lines2 = [
                        strip_tags(
                            $version_new['content']
                        ),
                    ]; //without <> tags
                    $diff = new Text_Diff($lines1, $lines2);
                    $renderer = new Text_Diff_Renderer_inline();
                    echo '<style>del{background:#fcc}ins{background:#cfc}</style>'.$renderer->render(
                            $diff
                        ); // Code inline
                    echo '<br />';
                    echo '<strong>'.get_lang(
                            'Legend'
                        ).'</strong><div class="diff">'."\n";
                    echo '<table><tr>';
                    echo '<td>';
                    echo '</td><td>';
                    echo '<span class="diffAddedTex" >'.get_lang(
                            'WikiDiffAddedTex'
                        ).'</span><br />';
                    echo '<span class="diffDeletedTex" >'.get_lang(
                            'WikiDiffDeletedTex'
                        ).'</span><br />';
                    echo '</td>';
                    echo '</tr></table>';
                }
            }
        }
    }

    /**
     * Get stat tables.
     */
    public function getStatsTable()
    {
        echo '<div class="actions">'.get_lang('More').'</div>';
        echo '<table border="0">';
        echo '  <tr>';
        echo '    <td>';
        echo '      <ul>';
        //Submenu Most active users
        echo '        <li><a href="'.$this->url.'&action=mactiveusers">'.get_lang('MostActiveUsers').'</a></li>';
        //Submenu Most visited pages
        echo '        <li><a href="'.$this->url.'&action=mvisited">'.get_lang('MostVisitedPages').'</a></li>';
        //Submenu Most changed pages
        echo '        <li><a href="'.$this->url.'&action=mostchanged">'.get_lang('MostChangedPages').'</a></li>';
        echo '      </ul>';
        echo '    </td>';
        echo '    <td>';
        echo '      <ul>';
        // Submenu Orphaned pages
        echo '        <li><a href="'.$this->url.'&action=orphaned">'.get_lang('OrphanedPages').'</a></li>';
        // Submenu Wanted pages
        echo '        <li><a href="'.$this->url.'&action=wanted">'.get_lang('WantedPages').'</a></li>';
        // Submenu Most linked pages
        echo '<li><a href="'.$this->url.'&action=mostlinked">'.get_lang('MostLinkedPages').'</a></li>';
        echo '</ul>';
        echo '</td>';
        echo '<td style="vertical-align:top">';
        echo '<ul>';
        // Submenu Statistics
        if (api_is_allowed_to_edit(false, true) || api_is_platform_admin()) {
            echo '<li><a href="'.$this->url.'&action=statistics">'.get_lang('Statistics').'</a></li>';
        }
        echo '      </ul>';
        echo '    </td>';
        echo '  </tr>';
        echo '</table>';
    }

    /**
     * Kind of controller.
     */
    public function handleAction(string $action)
    {
        $page = $this->page;
        switch ($action) {
            case 'export_to_pdf':
                if (isset($_GET['wiki_id'])) {
                    self::export_to_pdf($_GET['wiki_id'], $this->courseCode);
                    break;
                }
                break;
            case 'export2doc':
                if (isset($_GET['wiki_id'])) {
                    $export2doc = self::export2doc($_GET['wiki_id']);
                    if ($export2doc) {
                        Display::addFlash(
                            Display::return_message(
                                get_lang('ThePageHasBeenExportedToDocArea'),
                                'confirmation',
                                false
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
                $title = '<div class="actions">'.get_lang(
                        'DeleteWiki'
                    ).'</div>';
                if (api_is_allowed_to_edit(
                        false,
                        true
                    ) || api_is_platform_admin()) {
                    $message = get_lang('ConfirmDeleteWiki');
                    $message .= '<p>
                        <a href="'.$this->url.'">'.get_lang(
                            'No'
                        ).'</a>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        <a href="'.$this->url.'&action=deletewiki&delete=yes">'.
                        get_lang('Yes').'</a>
                    </p>';

                    if (!isset($_GET['delete'])) {
                        Display::addFlash(
                            $title.Display::return_message(
                                $message,
                                'warning',
                                false
                            )
                        );
                    }
                } else {
                    Display::addFlash(
                        Display::return_message(
                            get_lang("OnlyAdminDeleteWiki"),
                            'normal',
                            false
                        )
                    );
                }

                if (api_is_allowed_to_edit(
                        false,
                        true
                    ) || api_is_platform_admin()) {
                    if (isset($_GET['delete']) && $_GET['delete'] == 'yes') {
                        $return_message = self::delete_wiki();
                        Display::addFlash(
                            Display::return_message(
                                $return_message,
                                'confirmation',
                                false
                            )
                        );
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
                if (0 != $this->session_id && api_is_allowed_to_session_edit(false, true) == false) {
                    api_not_allowed();
                }
                $groupInfo = GroupManager::get_group_properties($this->group_id);
                echo '<div class="actions">'.get_lang('AddNew').'</div>';
                echo '<br/>';
                //first, check if page index was created. chektitle=false
                if (self::checktitle('index')) {
                    if (api_is_allowed_to_edit(false, true) ||
                        api_is_platform_admin() ||
                        GroupManager::is_user_in_group(
                            api_get_user_id(),
                            $groupInfo
                        ) ||
                        api_is_allowed_in_course()
                    ) {
                        Display::addFlash(
                            Display::return_message(get_lang('GoAndEditMainPage'), 'normal', false)
                        );
                    } else {
                        Display::addFlash(
                            Display::return_message(get_lang('WikiStandBy'), 'normal', false)
                        );
                    }
                } elseif (self::check_addnewpagelock() == 0
                    && (
                        api_is_allowed_to_edit(false, true) == false
                        || api_is_platform_admin() == false
                    )
                ) {
                    Display::addFlash(
                        Display::return_message(get_lang('AddPagesLocked'), 'error', false)
                    );
                } else {
                    $groupInfo = GroupManager::get_group_properties($this->group_id);
                    if (api_is_allowed_to_edit(false, true) ||
                        api_is_platform_admin() ||
                        GroupManager::is_user_in_group(
                            api_get_user_id(),
                            $groupInfo
                        ) ||
                        $_GET['group_id'] == 0
                    ) {
                        self::display_new_wiki_form();
                    } else {
                        Display::addFlash(
                            Display::return_message(get_lang('OnlyAddPagesGroupMembers'), 'normal', false)
                        );
                    }
                }
                break;
            case 'show':
            case 'showpage':
                self::display_wiki_entry($page);
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
                self::getDiscuss($page);
                break;
            case 'export_to_doc_file':
                self::exportTo($_GET['id'], 'odt');
                exit;
                break;
            case 'category':
                $this->addCategory();
                break;
            case 'delete_category':
                $this->deleteCategory();
                break;
        }
    }

    /**
     * Redirect to home.
     */
    public function redirectHome()
    {
        header('Location: '.$this->url.'&action=showpage&title=index');
        exit;
    }

    /**
     * Export wiki content in a ODF.
     *
     * @param int $id
     * @param string int
     *
     * @return bool
     */
    public function exportTo($id, $format = 'doc')
    {
        $data = self::getWikiDataFromDb($id);

        if (isset($data['content']) && !empty($data['content'])) {
            Export::htmlToOdt($data['content'], $data['reflink'], $format);
        }

        return false;
    }

    private function renderShowPage(string $contentHtml)
    {
        $wikiCategoriesEnabled = api_get_configuration_value('wiki_categories_enabled');

        if ($wikiCategoriesEnabled) {
            $em = Database::getManager();
            $categoryRepo = $em->getRepository(CWikiCategory::class);

            $course = api_get_course_entity();
            $session = api_get_session_entity();

            $count = $categoryRepo->countByCourse($course, $session);
            $tree = get_lang('NoCategories');

            if ($count) {
                $tree = $categoryRepo->buildCourseTree(
                    $course,
                    $session,
                    [
                        'decorate' => true,
                        'rootOpen' => '<ul class="fa-ul">',
                        'nodeDecorator' => function ($node) {
                            $prefix = '<span aria-hidden="true" class="fa fa-li fa-angle-right text-muted"></span>';

                            $urlParams = [
                                'search_term' => '',
                                'SubmitWikiSearch' => '',
                                '_qf__wiki_search' => '',
                                'action' => 'searchpages',
                                'categories' => ['' => $node['id']],
                            ];

                            return Display::url(
                                $prefix.$node['name'],
                                $this->url.'&'.http_build_query($urlParams)
                            );
                        },
                    ]
                );
            }

            echo '<div class="row">';
            echo '<aside class="col-sm-3">';
            echo $count > 0 ? '<h4>'.get_lang('Categories').'</h4>' : '';
            echo $tree;
            echo "</aside>"; // .col-sm-3
            echo '<div class="col-sm-9">';
        }

        echo $contentHtml;

        if ($wikiCategoriesEnabled) {
            echo "</div>"; // .col-sm-9
            echo "</div>"; // .row
        }
    }

    private function returnCategoriesBlock(int $wikiId, string $tagStart = '<div>', string $tagEnd = '</div>'): string
    {
        if (true !== api_get_configuration_value('wiki_categories_enabled') || empty($wikiId)) {
            return '';
        }

        try {
            $wiki = Database::getManager()->find(CWiki::class, $wikiId);
        } catch (Exception $e) {
            return '';
        }

        $categoryLinks = array_map(
            function (CWikiCategory $category) {
                $urlParams = [
                    'search_term' => isset($_GET['search_term']) ? Security::remove_XSS($_GET['search_term']) : '',
                    'SubmitWikiSearch' => '',
                    '_qf__wiki_search' => '',
                    'action' => 'searchpages',
                    'categories' => ['' => $category->getId()],
                ];

                return Display::url(
                    $category->getName(),
                    $this->url.'&'.http_build_query($urlParams)
                );
            },
            $wiki->getCategories()->getValues()
        );

        return $tagStart.implode(', ', $categoryLinks).$tagEnd;
    }

    private function gelAllPagesQuery(
        $onlyCount = false,
        $from = 0,
        $numberOfItems = 10,
        $column = 0,
        $direction = 'ASC'
    ): ?Statement {
        $tblWiki = $this->tbl_wiki;

        $fields = $onlyCount
            ? 'COUNT(s1.iid) AS nbr'
            : 's1.assignment col0, s1.title col1, s1.user_id col2, s1.dtime col3, s1.reflink, s1.user_ip, s1.iid';

        $query = 'SELECT '.$fields.' FROM '.$tblWiki.' s1 WHERE s1.c_id = '.$this->course_id.' ';

        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
            // warning don't use group by reflink because does not return the last version
            $query .= 'AND visibility = 1 ';
        }

        $query .= 'AND id = (
            SELECT MAX(s2.id) FROM '.$tblWiki.' s2
            WHERE s2.c_id = '.$this->course_id.'
                AND s1.reflink = s2.reflink
                AND '.$this->groupfilter.'
                AND session_id = '.$this->session_id.'
        ) ';

        if (!$onlyCount) {
            $query .= "ORDER BY col$column $direction LIMIT $from, $numberOfItems";
        }

        return Database::query($query);
    }

    private function deleteCategory()
    {
        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
            api_not_allowed(true);
        }

        if (true !== api_get_configuration_value('wiki_categories_enabled')) {
            api_not_allowed(true);
        }

        $em = Database::getManager();

        $category = null;

        if (isset($_GET['id'])) {
            $category = $em->find(CWikiCategory::class, $_GET['id']);

            if (!$category) {
                api_not_allowed(true);
            }
        }

        $em->remove($category);
        $em->flush();

        Display::addFlash(
            Display::return_message(get_lang('CategoryDeleted'), 'success')
        );

        header('Location: '.$this->url.'&action=category');
        exit;
    }

    private function addCategory()
    {
        if (!api_is_allowed_to_edit(false, true) && !api_is_platform_admin()) {
            api_not_allowed(true);
        }

        if (true !== api_get_configuration_value('wiki_categories_enabled')) {
            api_not_allowed(true);
        }

        $categoryRepo = Database::getManager()->getRepository(CWikiCategory::class);

        $categoryToEdit = null;

        if (isset($_GET['id'])) {
            $categoryToEdit = $categoryRepo->find($_GET['id']);

            if (!$categoryToEdit) {
                api_not_allowed(true);
            }
        }

        $course = api_get_course_entity();
        $session = api_get_session_entity();

        if ($categoryToEdit
            && ($course !== $categoryToEdit->getCourse() || $session !== $categoryToEdit->getSession())
        ) {
            api_not_allowed(true);
        }

        $iconEdit = Display::return_icon('edit.png', get_lang('Edit'));
        $iconDelete = Display::return_icon('delete.png', get_lang('Delete'));

        $categories = $categoryRepo->findByCourse($course, $session);
        $categoryList = array_map(
            function (CWikiCategory $category) use ($iconEdit, $iconDelete) {
                $actions = [];
                $actions[] = Display::url(
                    $iconEdit,
                    $this->url.'&'.http_build_query(['action' => 'category', 'id' => $category->getId()])
                );
                $actions[] = Display::url(
                    $iconDelete,
                    $this->url.'&'.http_build_query(['action' => 'delete_category', 'id' => $category->getId()])
                );

                return [
                    $category->getNodeName(),
                    implode(PHP_EOL, $actions),
                ];
            },
            $categories
        );

        $table = new SortableTableFromArray($categoryList);
        $table->set_header(0, get_lang('Name'), false);
        $table->set_header(1, get_lang('Actions'), false, ['class' => 'text-right'], ['class' => 'text-right']);

        $form = $this->createCategoryForm($categoryToEdit);
        $form->display();
        echo '<hr>';
        $table->display();
    }

    private function createCategoryForm(CWikiCategory $category = null): FormValidator
    {
        $em = Database::getManager();
        $categoryRepo = $em->getRepository(CWikiCategory::class);

        $course = api_get_course_entity($this->courseInfo['real_id']);
        $session = api_get_session_entity($this->session_id);

        $categories = $categoryRepo->findByCourse($course, $session);

        $formAction = $this->url.'&'.http_build_query([
                'action' => 'category',
                'id' => $category ? $category->getId() : null,
            ]);

        $form = new FormValidator('category', 'post', $formAction);
        $form->addHeader(get_lang('AddCategory'));
        $form->addSelectFromCollection('parent', get_lang('Parent'), $categories, [], true, 'getNodeName');
        $form->addText('name', get_lang('Name'));

        if ($category) {
            $form->addButtonUpdate(get_lang('Update'));
        } else {
            $form->addButtonSave(get_lang('Save'));
        }

        if ($form->validate()) {
            $values = $form->exportValues();
            $parent = $categoryRepo->find($values['parent']);

            if (!$category) {
                $category = (new CWikiCategory())
                    ->setCourse($course)
                    ->setSession($session)
                ;

                $em->persist($category);

                Display::addFlash(
                    Display::return_message(get_lang('CategoryAdded'), 'success')
                );
            } else {
                Display::addFlash(
                    Display::return_message(get_lang('CategoryEdited'), 'success')
                );
            }

            $category
                ->setName($values['name'])
                ->setParent($parent)
            ;

            $em->flush();

            header('Location: '.$this->url.'&action=category');
            exit;
        }

        if ($category) {
            $form->setDefaults([
                'parent' => $category->getParent() ? $category->getParent()->getId() : 0,
                'name' => $category->getName(),
            ]);
        }

        return $form;
    }

    private static function assignCategoriesToWiki(CWiki $wiki, array $categoriesIdList)
    {
        if (true !== api_get_configuration_value('wiki_categories_enabled')) {
            return;
        }

        $em = Database::getManager();

        foreach ($categoriesIdList as $categoryId) {
            $category = $em->find(CWikiCategory::class, $categoryId);
            $wiki->addCategory($category);
        }

        $em->flush();
    }
}
