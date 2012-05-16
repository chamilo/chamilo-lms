<?php

/**
 *
 * Internal person. I.e. a person from this instance of Mahara.
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetMaharaPersonRenderer extends AssetRenderer
{

    public static function get_user_id($ref)
    {
        $ref = trim($ref);
        $pattern = '#user/view.php\?id\=([0123456789]+)#';
        $matches = array();
        //mahara user's profile
        if (preg_match($pattern, $ref, $matches))
        {
            return $matches[1];
        }
        //email
        if ($user = get_record('usr', 'email', $ref))
        {
            return $user->id;
        }
        //user id
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
        $id = self::get_user_id($url);
        if (empty($id))
        {
            return false;
        }

        $data = get_record('usr', 'id', $id);

        $result = array();
        safe_require('blocktype', 'ple/person');
        $result[self::EMBED_SNIPPET] = PluginBlocktypePerson::render_preview($id);
        $result[self::THUMBNAIL] = PluginBlocktypePerson::get_thumbnail($id);
        $result[self::TITLE] = $data->prefferedname ? $data->prefferedname : $data->firstname . ' ' . $data->lastname;
        $result[self::DESCRIPTION] = isset($data->description) ? $data->description : '';

        return $result;
    }

}