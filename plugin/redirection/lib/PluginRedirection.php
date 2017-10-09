<?php
/* For licensing terms, see /license.txt */
/**
 * Config the plugin
 * @author Enrique Alcaraz Lopez 
 * @package chamilo.plugin.redirection
 */

class PluginRedirection 
{    
    # Insertamos la redirección
    public static function insert($user_id, $url)
    {
        return Database::insert(
            'plugin_redirection',
            [
                'user_id' => $user_id,
                'url' => $url
            ]
        );
    }
    
    # Borramos al redirección
    public static function delete($id)
    {
        $table = Database::get_main_table('plugin_redirection');
        Database::delete(
            $table,
            array('id = ?' => array($id))
        );
    }
    
    # Devolemos las redirecciones
    public static function get()
    {
        $table = Database::get_main_table('plugin_redirection');
        return Database::select('*', $table);
    }
}
