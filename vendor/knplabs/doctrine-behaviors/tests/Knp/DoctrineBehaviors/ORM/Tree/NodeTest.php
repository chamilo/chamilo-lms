<?php

namespace Tests\Knp\DoctrineBehaviors\ORM\Tree;

use Knp\DoctrineBehaviors\Model\Tree\NodeInterface;
use Tests\Knp\DoctrineBehaviors\ORM\EntityManagerProvider;
use BehaviorFixtures\ORM\TreeNodeEntity;
use Knp\DoctrineBehaviors\ORM\Tree\TreeSubscriber;
use Knp\DoctrineBehaviors\Reflection\ClassAnalyzer;
use Doctrine\Common\EventManager;

require_once __DIR__.'/../EntityManagerProvider.php';

class NodeTest extends \PHPUnit_Framework_TestCase
{
    use EntityManagerProvider;

    protected function getUsedEntityFixtures()
    {
        return array(
            'BehaviorFixtures\\ORM\\TreeNodeEntity'
        );
    }

    protected function getEventManager()
    {
        $em = new EventManager;

        $em->addEventSubscriber(
            new TreeSubscriber(
                new ClassAnalyzer(),
                false,
                'Knp\DoctrineBehaviors\Model\Tree\Node'
            )
        );

        return $em;
    }

    protected function buildNode(array $values = array())
    {
        $node = new TreeNodeEntity;
        foreach ($values as $method => $value) {
            $node->$method($value);
        }

        return $node;
    }

    private function buildTree()
    {
        $item = $this->buildNode();
        $item->setMaterializedPath('');
        $item->setId(1);

        $childItem = $this->buildNode();
        $childItem->setMaterializedPath('/1');
        $childItem->setId(2);
        $childItem->setChildNodeOf($item);

        $secondChildItem = $this->buildNode();
        $secondChildItem->setMaterializedPath('/1');
        $secondChildItem->setId(3);
        $secondChildItem->setChildNodeOf($item);

        $childChildItem = $this->buildNode();
        $childChildItem->setId(4);
        $childChildItem->setMaterializedPath('/1/2');
        $childChildItem->setChildNodeOf($childItem);

        $childChildChildItem = $this->buildNode();
        $childChildChildItem->setId(5);
        $childChildChildItem->setMaterializedPath('/1/2/4');
        $childChildChildItem->setChildNodeOf($childChildItem);

        return $item;
    }

    public function testBuildTree()
    {
        $root = $this->buildNode(array('setMaterializedPath' => ''     , 'setName' => 'root'        , 'setId' => 1));
        $flatTree = array(
            $this->buildNode(array('setMaterializedPath' => '/1'       , 'setName' => 'Villes'      , 'setId' => 2)),
            $this->buildNode(array('setMaterializedPath' => '/1/2'     , 'setName' => 'Nantes'      , 'setId' => 3)),
            $this->buildNode(array('setMaterializedPath' => '/1/2/3'   , 'setName' => 'Nantes Est'  , 'setId' => 4)),
            $this->buildNode(array('setMaterializedPath' => '/1/2/3'   , 'setName' => 'Nantes Nord' , 'setId' => 5)),
            $this->buildNode(array('setMaterializedPath' => '/1/2/3/5' , 'setName' => 'St-Mihiel'   , 'setId' => 6)),
        );

        $root->buildTree($flatTree);
        $this->assertCount(1, $root->getChildNodes());

        $this->assertCount(1, $root->getChildNodes()->first()->getChildNodes());
        $this->assertCount(2, $root->getChildNodes()->first()->getChildNodes()->first()->getChildNodes());

        $this->assertEquals(1, $root->getNodeLevel());
        $this->assertEquals(4, $root->getChildNodes()->first()->getChildNodes()->first()->getChildNodes()->first()->getNodeLevel());
    }

    public function testIsRoot()
    {
        $tree = $this->buildTree();

        $this->assertTrue($tree->getRootNode()->isRootNode());
        $this->assertTrue($tree->isRootNode());
    }

    public function testIsLeaf()
    {
        $tree = $this->buildTree();

        $this->assertTrue($tree[0][0][0]->isLeafNode());
        $this->assertTrue($tree[1]->isLeafNode());
    }

    public function testGetRoot()
    {
        $tree = $this->buildTree();

        $this->assertEquals($tree, $tree->getRootNode());
        $this->assertNull($tree->getRootNode()->getParentNode());

        $this->assertEquals($tree, $tree->getChildNodes()->get(0)->getChildNodes()->get(0)->getRootNode());
    }

    public function provideRootPaths()
    {
        return array(
            array($this->buildNode(array('setMaterializedPath' => '/0/1'))            , '/0'),
            array($this->buildNode(array('setMaterializedPath' => '/'))               , '/'),
            array($this->buildNode(array('setMaterializedPath' => ''))                , '/'),
            array($this->buildNode(array('setMaterializedPath' => '/test'))           , '/test'),
            array($this->buildNode(array('setMaterializedPath' => '/0/1/2/3/4/5/6/')) , '/0'),
        );
    }

    /**
     * @dataProvider provideisChildNodeOf
     **/
    public function testisChildNodeOf(NodeInterface $child, NodeInterface $parent, $expected)
    {
        $this->assertEquals($expected, $child->isChildNodeOf($parent));
    }

    public function provideisChildNodeOf()
    {
        $tree = $this->buildTree();

        return array(
            array($tree[0][0]    ,  $tree[0]          ,  true),
            array($tree[0][0][0] ,  $tree[0][0]       ,  true),
            array($tree[0][0][0] ,  $tree[0]          ,  false),
            array($tree[0][0][0] ,  $tree[0][0][0]    ,  false),
        );
    }

    public function provideToArray()
    {
        $expected = array (
            1 =>
            array (
                'node' => '',
                'children' =>
                array (
                    2 =>
                    array (
                        'node' => '',
                        'children' =>
                        array (
                            4 =>
                            array (
                                'node' => '',
                                'children' =>
                                array (
                                    5 =>
                                    array (
                                        'node' => '',
                                        'children' =>
                                        array (
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    3 =>
                    array (
                        'node' => '',
                        'children' =>
                        array (
                        ),
                    ),
                ),
            ),
        );

        return $expected;
    }

    public function testToArray()
    {
        $expected = $this->provideToArray();
        $tree = $this->buildTree();
        $this->assertEquals($expected, $tree->toArray());
    }

    public function testToJson()
    {
        $expected = $this->provideToArray();
        $tree = $this->buildTree();
        $this->assertEquals(json_encode($expected), $tree->toJson());
    }

    public function testToFlatArray()
    {
        $tree = $this->buildTree();

        $expected = array(
          1 => '',
          2 => '----',
          4 => '------',
          5 => '--------',
          3 => '----',
        );

        $this->assertEquals($expected, $tree->toFlatArray());
    }

    public function testArrayAccess()
    {
        $tree = $this->buildTree();

        $tree[] = $this->buildNode(array('setId' => 45));
        $tree[] = $this->buildNode(array('setId' => 46));
        $this->assertEquals(4, $tree->getChildNodes()->count());

        $tree[2][] = $this->buildNode(array('setId' => 47));
        $tree[2][] = $this->buildNode(array('setId' => 48));
        $this->assertEquals(2, $tree[2]->getChildNodes()->count());

        $this->assertTrue(isset($tree[2][1]));
        $this->assertFalse(isset($tree[2][1][2]));

        unset($tree[2][1]);
        $this->assertFalse(isset($tree[2][1]));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage You must provide an id for this node if you want it to be part of a tree.
     **/
    public function testsetChildNodeOfWithoutId()
    {
        $this->buildNode(array('setMaterializedPath' => '/0/1'))->setChildNodeOf($this->buildNode(array('setMaterializedPath' => '/0')));
    }

    public function testChildrenCount()
    {
        $tree = $this->buildTree();

        $this->assertEquals(2, $tree->getChildNodes()->count());
        $this->assertEquals(1, $tree->getChildNodes()->get(0)->getChildNodes()->count());
    }

    public function testGetPath()
    {
        $tree = $this->buildTree();

        $this->assertEquals('/1', $tree->getRealMaterializedPath());
        $this->assertEquals('/1/2', $tree->getChildNodes()->get(0)->getRealMaterializedPath());
        $this->assertEquals('/1/2/4', $tree->getChildNodes()->get(0)->getChildNodes()->get(0)->getRealMaterializedPath());
        $this->assertEquals('/1/2/4/5', $tree->getChildNodes()->get(0)->getChildNodes()->get(0)->getChildNodes()->get(0)->getRealMaterializedPath());

        $childChildItem = $tree->getChildNodes()->get(0)->getChildNodes()->get(0);
        $childChildChildItem = $tree->getChildNodes()->get(0)->getChildNodes()->get(0)->getChildNodes()->get(0);
        $childChildItem->setChildNodeOf($tree);
        $this->assertEquals('/1/4', $childChildItem->getRealMaterializedPath(), 'The path has been updated fo the node');
        $this->assertEquals('/1/4/5', $childChildChildItem->getRealMaterializedPath(), 'The path has been updated fo the node and all its descendants');
        $this->assertTrue($tree->getChildNodes()->contains($childChildItem), 'The children collection has been updated to reference the moved node');
    }

    public function testMoveChildren()
    {
        $tree = $this->buildTree();

        $childChildItem = $tree->getChildNodes()->get(0)->getChildNodes()->get(0);
        $childChildChildItem = $tree->getChildNodes()->get(0)->getChildNodes()->get(0)->getChildNodes()->get(0);
        $this->assertEquals(4, $childChildChildItem->getNodeLevel(), 'The level is well calcuated');

        $childChildItem->setChildNodeOf($tree);
        $this->assertEquals('/1/4', $childChildItem->getRealMaterializedPath(), 'The path has been updated fo the node');
        $this->assertEquals('/1/4/5', $childChildChildItem->getRealMaterializedPath(), 'The path has been updated fo the node and all its descendants');
        $this->assertTrue($tree->getChildNodes()->contains($childChildItem), 'The children collection has been updated to reference the moved node');

        $this->assertEquals(3, $childChildChildItem->getNodeLevel(), 'The level has been updated');
    }

    public function testGetTree()
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BehaviorFixtures\ORM\TreeNodeEntity');

        $entity = new TreeNodeEntity(1);
        $entity[0] = new TreeNodeEntity(2);
        $entity[0][0] = new TreeNodeEntity(3);

        $em->persist($entity);
        $em->persist($entity[0]);
        $em->persist($entity[0][0]);
        $em->flush();

        $root = $repo->getTree();

        $this->assertEquals($root[0][0], $entity[0][0]);
    }
}

