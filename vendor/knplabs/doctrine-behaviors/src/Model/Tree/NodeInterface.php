<?php

namespace Knp\DoctrineBehaviors\Model\Tree;

use Doctrine\Common\Collections\Collection;

/**
 * Tree\Node defines a set of needed methods
 * to work with materialized path tree nodes
 *
 * @author     Florian Klein <florian.klein@free.fr>
 */
interface NodeInterface
{
    /**
     * @return string the field that will represent the node in the path
     **/
    public function getNodeId();

    /**
     * @return string the materialized path,
     * eg the representation of path from all ancestors
     **/
    public function getMaterializedPath();

    /**
     * @return string the real materialized path,
     * eg the representation of path from all ancestors + current node
     **/
    public function getRealMaterializedPath();

    /**
     * @return string the materialized path from the parent, eg: the representation of path from all parent ancestors
     **/
    public function getParentMaterializedPath();

    /**
     * Set parent path.
     *
     * @param string $path the value to set.
     */
    public function setParentMaterializedPath($path);

    /**
     * @return NodeInterface the parent node
     **/
    public function getParentNode();

    /**
     * @param string $path the materialized path, eg: the the materialized path to its parent
     *
     * @return NodeInterface $this Fluent interface
     **/
    public function setMaterializedPath($path);

    /**
     * Used to build the hierarchical tree.
     * This method will do:
     *    - modify the parent of this node
     *    - Add the this node to the children of the new parent
     *    - Remove the this node from the children of the old parent
     *    - Modify the materialized path of this node and all its children, recursively
     *
     * @param NodeInterface $node The node to use as a parent
     *
     * @return NodeInterface $this Fluent interface
     **/
    public function setChildNodeOf(NodeInterface $node);

    /**
     * @param NodeInterface $node the node to append to the children collection
     *
     * @return NodeInterface $this Fluent interface
     **/
    public function addChildNode(NodeInterface $node);

    /**
     * @return Collection the children collection
     **/
    public function getChildNodes();

    /**
     * @return bool if the node is a leaf (i.e has no children)
     **/
    public function isLeafNode();

    /**
     * @return bool if the node is a root (i.e has no parent)
     **/
    public function isRootNode();
    
    /**
     * @return NodeInterface
     **/
    public function getRootNode();

    /**
     * Tells if this node is a child of another node
     * @param NodeInterface $node the node to compare with
     *
     * @return boolean true if this node is a direct child of $node
     **/
    public function isChildNodeOf(NodeInterface $node);

    /**
     *
     * @return integer the level of this node, eg: the depth compared to root node
     **/
    public function getNodeLevel();

    /**
     * Builds a hierarchical tree from a flat collection of NodeInterface elements
     *
     * @return void
     **/
    public function buildTree(array $nodes);
}
