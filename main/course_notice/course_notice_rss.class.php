<?php

/**
 * Formats in RSS the courses notices returned by CourseNoticeQuery.
 * 
 * View of CourseNotice
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class CourseNoticeRss
{

    protected $query;

    function __construct($user_id = null, $limit = 20)
    {
        $this->query = CourseNoticeQuery::create($user_id, $limit);
    }

    /**
     * unique id used by the cache 
     */
    function get_unique_id()
    {
        return strtolower(__CLASS__) . $this->get_query()->get_user_id();
    }
    
    /**
     *
     * @return CourseNoticeQuery
     */
    function get_query()
    {
        return $this->query;
    }

    function get_title()
    {
        return get_lang('CourseRssTitle');
    }

    function get_description()
    {
        return get_lang('CourseRssDescription');
    }
    
    function to_string()
    {
        return (string)$this;
    }
    
    function __toString()
    {
        ob_start();
        $this->display();
        $result = ob_get_clean();
        return $result;
    }

    function display()
    {
        $channel = $this->channel();

        echo <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:atom="http://www.w3.org/2005/Atom"
    xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
    xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
>

<channel>
    <title>{$channel->title}</title>
    <atom:link href="" rel="self" type="application/rss+xml" />
    <link>{$channel->link}</link>
    <description>{$channel->description}</description>
    <lastBuildDate>{$channel->last_build_date}</lastBuildDate>
    <language>{$channel->language}</language>
    <sy:updatePeriod>{$channel->update_period}</sy:updatePeriod>
    <sy:updateFrequency>{$channel->update_frequency}</sy:updateFrequency>
    <generator>{$channel->generator}</generator>
EOT;

        foreach ($channel->items as $item)
        {
            echo <<<EOT
            
    <item>
        <title>{$item->title}</title>
        <link>{$item->link}</link>
        <pubDate>{$item->date}</pubDate>
        <dc:creator>{$item->author}</dc:creator>
        <category><![CDATA[{$item->course_title}]]></category>
        <description><![CDATA[{$item->description}]]></description>
    </item>

EOT;
        }
        echo '</channel></rss>';
    }

    function channel()
    {
        $result = (object) array();
        $result->title = $this->get_title();
        $result->description = $this->get_description();
        $result->link = Uri::www();
        $result->last_build_date = time();
        $result->language = api_get_language_isocode();
        $result->update_period = 'hourly';
        $result->update_frequency = 1;
        $result->generator = Uri::chamilo();

        $items = $this->get_query()->get_items();
        $items = $this->format($items);
        $result->items = $items;
        return $result;
    }

    protected function format($items)
    {
        $result = array();
        foreach ($items as $item)
        {
            $result[] = $this->format_item($item);
        }
        return $result;
    }

    protected function format_item($item)
    {
        $result = (object) array();
        $item = (object) $item;

        $author = (object) UserManager::get_user_info_by_id($item->lastedit_user_id);

        $result->title = $item->title;
        $result->description = $item->description;
        $result->description .= $result->description ? '<br/>' : '';
        $result->description .= '<i>' . $item->course_title . ' &gt; ' . $this->get_tool_lang($item->tool) . ' &gt; ' . $item->title . '</i>';

        $result->date = date('r', strtotime($item->lastedit_date));
        $result->author = htmlentities($author->firstname . ' ' . $author->lastname . ' <' . $author->email . '>');
        $result->author_email = $author->email;
        $result->tool = $item->tool;
        $result->course_code = $item->code;
        $result->course_title = $item->course_title;
        $result->course_description = $item->course_description;
        $result->course_id = $item->c_id;

        $tool = $item->tool;
        $f = array($this, "format_$tool");
        if (is_callable($f))
        {
            call_user_func($f, $result, $item);
        }
        return $result;
    }

    protected function get_tool_lang($tool_name)
    {
        if ($tool_name = TOOL_CALENDAR_EVENT)
        {
            return get_lang('Agenda');
        }
        else if ($tool_name = TOOL_DOCUMENT)
        {
            return get_lang('Document');
        }
        else if ($tool_name = TOOL_LINK)
        {
            return get_lang('Link');
        }
        else if ($tool_name = TOOL_ANNOUNCEMENT)
        {
            return get_lang('Announcement');
        }
    }

    protected function format_document($result, $item)
    {
        $params = Uri::course_params($item->code, $item->session_id, $item->to_group_id);
        $params['id'] = $item->ref;
        $params['action'] = 'download';
        $result->link = Uri::url('main/document/document.php', $params);
    }

    protected function format_announcement($result, $item)
    {
        $params = Uri::course_params($item->code, $item->session_id, $item->to_group_id);
        $params['id'] = $item->ref;
        $params['action'] = 'view';
        $result->link = Uri::url('main/announcements/announcements.php', $params);
    }

    protected function format_link($result, $item)
    {
        $result->link = $item->url;
    }

    protected function format_calendar_event($result, $item)
    {
        $params = Uri::course_params($item->code, $item->session_id, $item->to_group_id);
        // . 'calendar/agenda.php?cidReq=' . $item->code . '#' . $item->id;        
        $result->link = Uri::url('main/calendar/agenda.php', $params);
        //$result->description .= '<br/><i>' . $course->title . ' &gt; ' . get_lang('Agenda') . ' &gt; ' . $item->title . '</i>';
    }

}