<?php

/**
 * Internal group. I.e. a group from this instance of Mahara.
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetMaharaGroupRenderer extends AssetRenderer
{

    public static function get_group_id($ref)
    {
        $ref = trim($ref);
        $pattern = '#group/view.php\?id\=([0123456789]+)#';
        $matches = array();
        //mahara group's profile
        if (preg_match($pattern, $ref, $matches))
        {
            return $matches[1];
        }
        //group id
        if ($val = intval($ref))
        {
            return $val;
        }

        return false;
    }

    /**
     *
     * @param HttpResource $asset 
     */
    public function render($asset)
    {
        $url = $asset->url();
        $group_id = self::get_group_id($url);
        if (empty($group_id))
        {
            return false;
        }

        $data = get_record('group', 'id', $group_id);

        $result = array();
        safe_require('blocktype', 'ple/group');
        $result[self::EMBED_SNIPPET] = PluginBlocktypeGroup::render_preview($group_id);
        $result[self::THUMBNAIL] = PluginBlocktypeGroup::get_thumbnail($group_id);
        $result[self::TITLE] = $data->name;
        $result[self::DESCRIPTION] = $data->description;

        return $result;
    }

}