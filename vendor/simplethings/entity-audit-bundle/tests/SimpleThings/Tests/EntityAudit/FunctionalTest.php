<?php
/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @link http://www.simplethings.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace SimpleThings\EntityAudit\Tests;

use Doctrine\Common\EventManager;
use SimpleThings\EntityAudit\EventListener\CreateSchemaListener;
use SimpleThings\EntityAudit\EventListener\LogRevisionsListener;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;

use Doctrine\ORM\Mapping AS ORM;

class FunctionalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager
     */
    private $em = null;

    /**
     * @var AuditManager
     */
    private $auditManager = null;

    public function testAuditable()
    {
        $user = new UserAudit("beberlei");
        $article = new ArticleAudit("test", "yadda!", $user);

        $this->em->persist($user);
        $this->em->persist($article);
        $this->em->flush();

        $this->assertEquals(1, count($this->em->getConnection()->fetchAll('SELECT id FROM revisions')));
        $this->assertEquals(1, count($this->em->getConnection()->fetchAll('SELECT * FROM UserAudit_audit')));
        $this->assertEquals(1, count($this->em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit')));

        $article->setText("oeruoa");

        $this->em->flush();

        $this->assertEquals(2, count($this->em->getConnection()->fetchAll('SELECT id FROM revisions')));
        $this->assertEquals(2, count($this->em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit')));

        $this->em->remove($user);
        $this->em->remove($article);
        $this->em->flush();

        $this->assertEquals(3, count($this->em->getConnection()->fetchAll('SELECT id FROM revisions')));
        $this->assertEquals(2, count($this->em->getConnection()->fetchAll('SELECT * FROM UserAudit_audit')));
        $this->assertEquals(3, count($this->em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit')));
    }

    public function testFind()
    {
        $user = new UserAudit("beberlei");

        $this->em->persist($user);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
        $auditUser = $reader->find(get_class($user), $user->getId(), 1);

        $this->assertInstanceOf(get_class($user), $auditUser, "Audited User is also a User instance.");
        $this->assertEquals($user->getId(), $auditUser->getId(), "Ids of audited user and real user should be the same.");
        $this->assertEquals($user->getName(), $auditUser->getName(), "Name of audited user and real user should be the same.");
        $this->assertFalse($this->em->contains($auditUser), "Audited User should not be in the identity map.");
        $this->assertNotSame($user, $auditUser, "User and Audited User instances are not the same.");
    }

    public function testFindNoRevisionFound()
    {
        $reader = $this->auditManager->createAuditReader($this->em);

        $this->setExpectedException("SimpleThings\EntityAudit\AuditException", "No revision of class 'SimpleThings\EntityAudit\Tests\UserAudit' (1) was found at revision 1 or before. The entity did not exist at the specified revision yet.");
        $auditUser = $reader->find("SimpleThings\EntityAudit\Tests\UserAudit", 1, 1);
    }

    public function testFindNotAudited()
    {
        $reader = $this->auditManager->createAuditReader($this->em);

        $this->setExpectedException("SimpleThings\EntityAudit\AuditException", "Class 'stdClass' is not audited.");
        $auditUser = $reader->find("stdClass", 1, 1);
    }

    public function testFindRevisionHistory()
    {
        $user = new UserAudit("beberlei");

        $this->em->persist($user);
        $this->em->flush();

        $article = new ArticleAudit("test", "yadda!", $user);

        $this->em->persist($article);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
        $revisions = $reader->findRevisionHistory();

        $this->assertEquals(2, count($revisions));
        $this->assertContainsOnly('SimpleThings\EntityAudit\Revision', $revisions);

        $this->assertEquals(2, $revisions[0]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[0]->getTimestamp());
        $this->assertEquals('beberlei', $revisions[0]->getUsername());

        $this->assertEquals(1, $revisions[1]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[1]->getTimestamp());
        $this->assertEquals('beberlei', $revisions[1]->getUsername());
    }

    public function testFindEntitesChangedAtRevision()
    {
        $user = new UserAudit("beberlei");
        $article = new ArticleAudit("test", "yadda!", $user);

        $this->em->persist($user);
        $this->em->persist($article);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
        $changedEntities = $reader->findEntitiesChangedAtRevision(1);

        $this->assertEquals(2, count($changedEntities));
        $this->assertContainsOnly('SimpleThings\EntityAudit\ChangedEntity', $changedEntities);

        $this->assertEquals('SimpleThings\EntityAudit\Tests\ArticleAudit', $changedEntities[0]->getClassName());
        $this->assertEquals('INS', $changedEntities[0]->getRevisionType());
        $this->assertEquals(array('id' => 1), $changedEntities[0]->getId());
        $this->assertInstanceOf('SimpleThings\EntityAudit\Tests\ArticleAudit', $changedEntities[0]->getEntity());

        $this->assertEquals('SimpleThings\EntityAudit\Tests\UserAudit', $changedEntities[1]->getClassName());
        $this->assertEquals('INS', $changedEntities[1]->getRevisionType());
        $this->assertEquals(array('id' => 1), $changedEntities[1]->getId());
        $this->assertInstanceOf('SimpleThings\EntityAudit\Tests\UserAudit', $changedEntities[1]->getEntity());
    }

    public function testFindRevisions()
    {
        $user = new UserAudit("beberlei");

        $this->em->persist($user);
        $this->em->flush();

        $user->setName("beberlei2");
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
        $revisions = $reader->findRevisions(get_class($user), $user->getId());

        $this->assertEquals(2, count($revisions));
        $this->assertContainsOnly('SimpleThings\EntityAudit\Revision', $revisions);

        $this->assertEquals(2, $revisions[0]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[0]->getTimestamp());
        $this->assertEquals('beberlei', $revisions[0]->getUsername());

        $this->assertEquals(1, $revisions[1]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[1]->getTimestamp());
        $this->assertEquals('beberlei', $revisions[1]->getUsername());
    }

    public function setUp()
    {
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $driver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader);
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('SimpleThings\EntityAudit\Tests\Proxies');
        $config->setMetadataDriverImpl($driver);

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $auditConfig = new AuditConfiguration();
        $auditConfig->setCurrentUsername("beberlei");
        $auditConfig->setAuditedEntityClasses(array('SimpleThings\EntityAudit\Tests\ArticleAudit', 'SimpleThings\EntityAudit\Tests\UserAudit'));

        $this->auditManager = new AuditManager($auditConfig);
        $this->auditManager->registerEvents($evm = new EventManager());

        #$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata('SimpleThings\EntityAudit\Tests\ArticleAudit'),
            $this->em->getClassMetadata('SimpleThings\EntityAudit\Tests\UserAudit'),
        ));
    }

    public function testFindCurrentRevision()
    {
        $user = new UserAudit('Broncha');

        $this->em->persist($user);
        $this->em->flush();

        $user->setName("Rajesh");
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $revision = $reader->getCurrentRevision(get_class($user), $user->getId());
        $this->assertEquals(2, $revision);

        $user->setName("David");
        $this->em->flush();

        $revision = $reader->getCurrentRevision(get_class($user), $user->getId());
        $this->assertEquals(3, $revision);
    }
}

/**
 * @ORM\Entity
 */
class ArticleAudit
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;

    /** @ORM\Column(type="string", name="my_title_column") */
    private $title;

    /** @ORM\Column(type="text") */
    private $text;

    /** @ORM\ManyToOne(targetEntity="UserAudit") */
    private $author;

    function __construct($title, $text, $author)
    {
        $this->title = $title;
        $this->text = $text;
        $this->author = $author;
    }

    public function setText($text)
    {
        $this->text = $text;
    }
}

/**
 * @ORM\Entity
 */
class UserAudit
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;
    /** @ORM\Column(type="string") */
    private $name;

    function __construct($name)
    {
        $this->name = $name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
}
