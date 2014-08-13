<?php

namespace Chamilo\CoreBundle\Migrations\Schema\v10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class NotebookBundle implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery("CREATE TABLE c_notebook (iid INT AUTO_INCREMENT NOT NULL, c_id INT NOT NULL, notebook_id INT NOT NULL, user_id INT NOT NULL, course VARCHAR(40) NOT NULL, session_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, creation_date DATETIME NOT NULL, update_date DATETIME NOT NULL, status INT DEFAULT NULL, PRIMARY KEY(iid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $queries->addQuery("CREATE TABLE c_notebook_audit (iid INT NOT NULL, rev INT NOT NULL, c_id INT DEFAULT NULL, notebook_id INT DEFAULT NULL, user_id INT DEFAULT NULL, course VARCHAR(40) DEFAULT NULL, session_id INT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, creation_date DATETIME DEFAULT NULL, update_date DATETIME DEFAULT NULL, status INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, PRIMARY KEY(iid, rev)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
    }

    public function down(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery("DROP TABLE c_notebook");
        $queries->addQuery("DROP TABLE c_notebook_audit");
    }
}
