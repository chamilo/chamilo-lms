<?php

class Redireccion
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
        Database::delete(
            $this->table,
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

