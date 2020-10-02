<?php
/* For licensing terms, see /license.txt */

class chamilo_boost extends Plugin
{
    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Damien Renou',
            array(
                'enable_plugin_chamilo_boost' => 'boolean'
            )
        );
    }
	
    /**
     * @return chamilo_boost|null
     */
    public static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }
	
    public function install()
	{

        $sql = "CREATE TABLE IF NOT EXISTS `boosttitle` (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            indexTitle int(11) NOT NULL,
            subTitle varchar(255) NOT NULL,
            typeCard varchar(255) NOT NULL,
            imagePic varchar(255) NOT NULL,
            imageUrl varchar(255) NOT NULL,
            acces varchar(255) NOT NULL,
            picture varchar(255) NOT NULL,
            content varchar(255) NOT NULL,
            leftContent longtext,
            rightContent longtext,
            idContent varchar(255) NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE = MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;";

        Database::query($sql);


    }
	
    public function uninstall()
    {

    }
}
