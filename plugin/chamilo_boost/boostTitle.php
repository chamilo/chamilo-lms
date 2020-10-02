<?php
/* For licensing terms, see /license.txt */

class boostTitle extends Plugin
{
	
    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Damien Renou',
            array(
                'enable_plugin_boostTitle' => 'boolean'
            )
        );
    }
	
    public static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }
	
    public function install()
	{
        $sql = "CREATE TABLE IF NOT EXISTS boostTitle(
                id INT NOT NULL AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
				indexTitle int NOT NULL,
				subTitle VARCHAR(255) NOT NULL,
				typeCard VARCHAR(255) NOT NULL,
				imagePic VARCHAR(255) NOT NULL,
				imageUrl VARCHAR(255) NOT NULL,
				acces VARCHAR(255) NOT NULL,
				picture VARCHAR(255) NOT NULL,
				content VARCHAR(255) NOT NULL,
				leftContent longtext,
				rightContent longtext,
				idContent VARCHAR(255) NOT NULL,
                url_id INT,
				PRIMARY KEY (id));
        ";
        Database::query($sql);
    }
	
    public function uninstall()
    {

    }
}
