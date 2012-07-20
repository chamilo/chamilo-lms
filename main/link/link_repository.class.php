<?php


namespace Link;

use Database;

/**
 * Database interface for Link
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Genevas
 * @license /license.txt 
 */
class LinkRepository
{

    /**
     * 
     * @return \Link\LinkRepository
     */
    public static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }

    public function save($link)
    {
        $id = $link->id;
        if (empty($id)) {
            return $this->insert($link);
        } else {
            return $this->update($link);
        }
    }

    /**
     *
     * @param \Link\Link $link
     * @return bool 
     */
    public function insert($link)
    {
        $c_id = (int) $link->c_id;

        $session_id = (int) $link->session_id;
        $session_id = $session_id ? $session_id : '0';

        $url = trim($link->url);
        $url = Database::escape_string($url);

        $title = trim($link->title);
        $title = Database::escape_string($title);

        $description = trim($link->description);
        $description = Database::escape_string($description);

        $category_id = intval($link->category_id);
        $category_id = $category_id ? $category_id : '0';

        $display_order = $this->next_display_order($c_id);

        $on_homepage = $link->on_homepage;
        $on_homepage = $on_homepage ? '1' : '0';

        $target = $link->target;
        $target = Database::escape_string($target);

        $table = Database :: get_course_table(TABLE_LINK);
        $sql = "INSERT INTO $table 
                    (c_id, url, title, description, category_id, display_order, on_homepage, target, session_id)
			    VALUES 
                    ($c_id , '$url', '$title', '$description', $category_id, $display_order, '$on_homepage', '$target', $session_id)";
        $result = (bool) Database :: query($sql);



        if ($result) {
            $id = Database::insert_id();
            $link->id = $id;
            $link->display_order = $display_order;

            $_course = api_get_course_info_by_id($c_id);
            $tool = TOOL_LINK;
            $user_id = api_get_user_id();
            api_item_property_update($_course, $tool, $id, 'LinkAdded', $user_id);
        }
        return $result;
    }

    function update($link)
    {

        $c_id = (int) $link->c_id;
        $id = (int) $link->id;

        $session_id = (int) $link->session_id;
        $session_id = $session_id ? $session_id : '0';

        $url = trim($link->url);
        $url = Database::escape_string($url);

        $title = trim($link->title);
        $title = Database::escape_string($title);

        $description = trim($link->description);
        $description = Database::escape_string($description);

        $category_id = intval($link->category_id);
        $category_id = $category_id ? $category_id : '0';

        $display_order = (int) $link->display_order;

        $on_homepage = $link->on_homepage;
        $on_homepage = $on_homepage ? '1' : '0';

        $target = $link->target;
        $target = Database::escape_string($target);

        $table = Database :: get_course_table(TABLE_LINK);
        $sql = "UPDATE $table SET
                    url = '$url',
                    title = '$title',
                    description = '$description',
                    category_id = $category_id, 
                    display_order = $display_order,
                    on_homepage = '$on_homepage',
                    target = '$target',
                    session_id = $session_id
			    WHERE
                    c_id = $c_id AND
                    id = $id";
        $result = (bool) Database :: query($sql);

        if ($result) {
            $_course = api_get_course_info_by_id($c_id);
            $tool = TOOL_LINK;
            $user_id = api_get_user_id();
            api_item_property_update($_course, $tool, $id, 'LinkUpdated', $user_id);
        }
        return $result;
    }

    public function remove_by_category($category)
    {
        $result = true;
        $c_id = (int) $category->c_id;
        $id = (int) $category->id;
        $where = "l.c_id=$c_id AND l.category_id=$id";
        $links = $links = $this->find($where);
        foreach ($links as $link) {
            $success = $this->remove($link);
            if (!$success) {
                $result = false;
            }
        }

        return $result;
    }

    public function remove_by_course($c_id, $session_id = 0)
    {
        $result = true;     
        $session_where = api_get_session_condition($session_id, true, true);
        $where = "l.c_id=$c_id $session_where";
        
        $links = $links = $this->find($where);
        foreach ($links as $link) {
            $success = $this->remove($link);
            if (!$success) {
                $result = false;
            }
        }
        
        

        return $result;
    }

    /**
     *
     * Note:
     * 
     * Note as ids are reused when objects are deleted it is either we delete
     * everything or nothing.
     * 
     * @param \Link\Link|object $link
     * @return bool 
     */
    public function remove($link)
    {
        $table = Database :: get_course_table(TABLE_LINK);
        $c_id = (int) $link->c_id;
        $id = (int) $link->id;

        $sql = "DELETE FROM $table WHERE c_id=$c_id AND id=$id";
        $result = Database :: query($sql);
        if ($result) {            
            $tool = TOOL_LINK;
            $tbl_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);
            $sql = "DELETE FROM $tbl_property WHERE c_id=$c_id AND ref=$id AND tool='$tool'";
            Database :: query($sql);
            
            
            $id = "Link.$id";
            $table = Database :: get_course_table(TABLE_METADATA);
            $sql = "DELETE FROM $table WHERE c_id=$c_id AND eid='$id'";
            Database :: query($sql);
        }
        return (bool) $result;
    }

    /**
     *
     * @param string $where
     * @return \Link\Link 
     */
    public function find_one($where)
    {
        $items = $this->find($where);
        foreach ($items as $item) {
            return $item;
        }
        return null;
    }
    
    /**
     *
     * @param int $c_id
     * @param int $id
     * @return \Link\Link 
     */
    public function find_one_by_id($c_id, $id)
    {
        $where = "l.c_id = $c_id AND l.id = $id";
        return $this->find_one($where);
    }
    
    /**
     *
     * @param int $c_id
     * @param int $session_id
     * @param string $url
     * @return \Link\Link 
     */
    public function find_one_by_course_and_url($c_id, $session_id, $url)
    {
        $c_id = (int)$c_id;
        $session_id = (int)$session_id;
        $url = Database::escape_string($url);
        
        $session_where =api_get_session_condition($session_id, true, true);
        $where = "l.c_id = $c_id $session_where AND l.url = '$url'";
        return $this->find_one($where);
    }

    /**
     *
     * @param string $where
     * @return array
     */
    public function find($where = '')
    {
        $result = array();

        $tbl_link = Database :: get_course_table(TABLE_LINK);
        $tbl_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);
        $tool = TOOL_LINK;
        $where = $where ? " AND ($where)" : '';

        $sql = "SELECT 
                    l.*, 
                    p.visibility 
                FROM 
                    $tbl_link AS l,
                    $tbl_property AS p
                WHERE  
                    p.tool='$tool' AND
					l.id = p.ref AND
					l.c_id = p.c_id
                    $where
                ORDER BY 
                    l.display_order DESC";
        $rs = Database :: query($sql);
        while ($data = Database::fetch_object($rs)) {
            $result[] = Link::create($data);
        }
        return $result;
    }

    /**
     *
     * @param \Link\LinkCategory $category 
     */
    public function find_by_category($category, $visible_only = false)
    {
        $session_id = $category->session_id;
        $id = $category->id;

        $where = $visible_only ? 'p.visibility=1' : '(p.visibility=0 OR p.visibility=1)';
        $where .=api_get_session_condition($session_id, true, true);
        $where .= "AND l.category_id = $id";
        return $this->find($where);
    }

    public function make_visible($c_id, $id)
    {
        $_course = api_get_course_info_by_id($c_id);
        $user_id = api_get_user_id();
        $result = api_item_property_update($_course, TOOL_LINK, $id, 'visible', $user_id);
        return $result;
    }

    public function make_invisible($c_id, $id)
    {
        $_course = api_get_course_info_by_id($c_id);
        $user_id = api_get_user_id();
        $result = api_item_property_update($_course, TOOL_LINK, $id, 'invisible', $user_id);
        return $result;
    }

    function next_display_order($c_id)
    {
        $table = Database :: get_course_table(TABLE_LINK);
        $sql = "SELECT MAX(display_order) FROM  $table WHERE c_id = $c_id ";
        $rs = Database :: query($sql);
        list ($result) = Database :: fetch_row($rs);
        $result = $result + 1;
        $result = intval($result);
        return $result;
    }

    /**
     * Ensure $ids are sorted in the order given from greater to lower
     * 
     * Simple algorithm, works only if all ids are given at the same time.
     * This would not work if pagination were used and only a subset were sorted.
     * On the other hand the algorithm is sturdy, it will work even if current 
     * weights/display orders are not correctly set up in the db.
     * 
     * @param int $c_id
     * @param array $ids 
     */
    public function order($c_id, $ids)
    {
        $result = true;
        $ids = array_map('intval', $ids);
        $table = Database :: get_course_table(TABLE_LINK);

        $counter = 0;
        $weight = count($ids) + 1;
        foreach ($ids as $id) {
            $sql = "UPDATE $table SET display_order = $weight WHERE c_id = $c_id AND id = $id";
            $success = Database::query($sql);
            if (!$success) {
                $result = false;
            }
            --$weight;
        }
        return $result;
    }

}
