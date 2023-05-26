<?php

namespace Chamilo\CoreBundle\Traits\Repository\ORM;

use Gedmo\Tool\Wrapper\EntityWrapper;
use Doctrine\ORM\Query;
use Gedmo\Tree\Strategy;
use Gedmo\Tree\Strategy\ORM\Nested;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Exception\UnexpectedValueException;
use Doctrine\ORM\Proxy\Proxy;

/**
 * The NestedTreeRepository trait has some useful functions
 * to interact with NestedSet tree. Repository uses
 * the strategy used by listener.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait NestedTreeRepositoryTrait
{
    use TreeRepositoryTrait;

    /**
     * {@inheritDoc}
     */
    public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc')
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);
        $qb = $this->getQueryBuilder();
        $qb
            ->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where($qb->expr()->isNull('node.'.$config['parent']))
        ;

        if ($sortByField !== null) {
            $qb->orderBy('node.'.$sortByField, strtolower($direction) === 'asc' ? 'asc' : 'desc');
        } else {
            $qb->orderBy('node.'.$config['left'], 'ASC');
        }

        return $qb;
    }

    /**
     * {@inheritDoc}
     */
    public function getRootNodesQuery($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQueryBuilder($sortByField, $direction)->getQuery();
    }

    /**
     * {@inheritDoc}
     */
    public function getRootNodes($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQuery($sortByField, $direction)->getResult();
    }

    /**
     * Persists node in given position strategy
     */
    protected function persistAs($node, $child = null, $position = Nested::FIRST_CHILD)
    {
        $em = $this->getEntityManager();
        $wrapped = new EntityWrapper($node, $em);
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($em, $meta->name);

        $siblingInPosition = null;
        if ($child !== null) {
            switch ($position) {
                case Nested::PREV_SIBLING:
                case Nested::NEXT_SIBLING:
                    $sibling = new EntityWrapper($child, $em);
                    $newParent = $sibling->getPropertyValue($config['parent']);
                    if (null === $newParent && isset($config['root'])) {
                        throw new UnexpectedValueException("Cannot persist sibling for a root node, tree operation is not possible");
                    }
                    $siblingInPosition = $child;
                    $child = $newParent;
                    break;
            }
            $wrapped->setPropertyValue($config['parent'], $child);
        }

        $wrapped->setPropertyValue($config['left'], 0); // simulate changeset
        $oid = spl_object_hash($node);
        $this->listener->getStrategy($em, $meta->name)->setNodePosition($oid, $position, $siblingInPosition);
        $em->persist($node);

        return $this;
    }

    /**
     * Persists given $node as first child of tree
     *
     * @param $node
     * @return self
     */
    public function persistAsFirstChild($node)
    {
        return $this->persistAs($node, null, Nested::FIRST_CHILD);
    }

    /**
     * Persists given $node as first child of $parent node
     *
     * @param $node
     * @param $parent
     * @return self
     */
    public function persistAsFirstChildOf($node, $parent)
    {
        return $this->persistAs($node, $parent, Nested::FIRST_CHILD);
    }

    /**
     * Persists given $node as last child of tree
     *
     * @param $node
     * @return self
     */
    public function persistAsLastChild($node)
    {
        return $this->persistAs($node, null, Nested::LAST_CHILD);
    }

    /**
     * Persists given $node as last child of $parent node
     *
     * @param $node
     * @param $parent
     * @return self
     */
    public function persistAsLastChildOf($node, $parent)
    {
        return $this->persistAs($node, $parent, Nested::LAST_CHILD);
    }

    /**
     * Persists given $node next to $sibling node
     *
     * @param $node
     * @param $sibling
     * @return self
     */
    public function persistAsNextSiblingOf($node, $sibling)
    {
        return $this->persistAs($node, $sibling, Nested::NEXT_SIBLING);
    }

    /**
     * Persists given $node previous to $sibling node
     *
     * @param $node
     * @param $sibling
     * @return self
     */
    public function persistAsPrevSiblingOf($node, $sibling)
    {
        return $this->persistAs($node, $sibling, Nested::PREV_SIBLING);
    }

    /**
     * Persists given $node same as first child of it's parent
     *
     * @param $node
     * @return self
     */
    public function persistAsNextSibling($node)
    {
        return $this->persistAs($node, null, Nested::NEXT_SIBLING);
    }

    /**
     * Persists given $node same as last child of it's parent
     *
     * @param $node
     * @return self
     */
    public function persistAsPrevSibling($node)
    {
        return $this->persistAs($node, null, Nested::PREV_SIBLING);
    }

    /**
     * Get the Tree path query builder by given $node
     *
     * @param object $node
     *
     * @throws InvalidArgumentException - if input is not valid
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getPathQueryBuilder($node)
    {
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);
        $wrapped = new EntityWrapper($node, $this->getEntityManager());
        if (!$wrapped->hasValidIdentifier()) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }
        $left = $wrapped->getPropertyValue($config['left']);
        $right = $wrapped->getPropertyValue($config['right']);
        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where($qb->expr()->lte('node.'.$config['left'], $left))
            ->andWhere($qb->expr()->gte('node.'.$config['right'], $right))
            ->orderBy('node.'.$config['left'], 'ASC')
        ;
        if (isset($config['root'])) {
            $root = $wrapped->getPropertyValue($config['root']);
            $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
            $qb->setParameter('rid', $root);
        }

        return $qb;
    }

    /**
     * Get the Tree path query by given $node
     *
     * @param object $node
     *
     * @return \Doctrine\ORM\Query
     */
    public function getPathQuery($node)
    {
        return $this->getPathQueryBuilder($node)->getQuery();
    }

    /**
     * Get the Tree path of Nodes by given $node
     *
     * @param object $node
     *
     * @return array - list of Nodes in path
     */
    public function getPath($node)
    {
        return $this->getPathQuery($node)->getResult();
    }

    /**
     * @see getChildrenQueryBuilder
     */
    public function childrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);

        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
        ;
        if ($node !== null) {
            if ($node instanceof $meta->name) {
                $wrapped = new EntityWrapper($node, $this->getEntityManager());
                if (!$wrapped->hasValidIdentifier()) {
                    throw new InvalidArgumentException("Node is not managed by UnitOfWork");
                }
                if ($direct) {
                    $qb->where($qb->expr()->eq('node.'.$config['parent'], ':pid'));
                    $qb->setParameter('pid', $wrapped->getIdentifier());
                } else {
                    $left = $wrapped->getPropertyValue($config['left']);
                    $right = $wrapped->getPropertyValue($config['right']);
                    if ($left && $right) {
                        $qb->where($qb->expr()->lt('node.'.$config['right'], $right));
                        $qb->andWhere($qb->expr()->gt('node.'.$config['left'], $left));
                    }
                }
                if (isset($config['root'])) {
                    $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
                    $qb->setParameter('rid', $wrapped->getPropertyValue($config['root']));
                }
                if ($includeNode) {
                    $idField = $meta->getSingleIdentifierFieldName();
                    $qb->where('('.$qb->getDqlPart('where').') OR node.'.$idField.' = :rootNode');
                    $qb->setParameter('rootNode', $node);
                }
            } else {
                throw new \InvalidArgumentException("Node is not related to this repository");
            }
        } else {
            if ($direct) {
                $qb->where($qb->expr()->isNull('node.'.$config['parent']));
            }
        }
        if (!$sortByField) {
            $qb->orderBy('node.'.$config['left'], 'ASC');
        } elseif (is_array($sortByField)) {
            $fields = '';
            foreach ($sortByField as $field) {
                $fields .= 'node.'.$field.',';
            }
            $fields = rtrim($fields, ',');
            $qb->orderBy($fields, $direction);
        } else {
            if ($meta->hasField($sortByField) && in_array(strtolower($direction), array('asc', 'desc'))) {
                $qb->orderBy('node.'.$sortByField, $direction);
            } else {
                throw new InvalidArgumentException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }

        return $qb;
    }

    /**
     * @see getChildrenQuery
     */
    public function childrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->childrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode)->getQuery();
    }

    /**
     * @see getChildren
     */
    public function children($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        $q = $this->childrenQuery($node, $direct, $sortByField, $direction, $includeNode);

        return $q->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function getChildrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->childrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * {@inheritDoc}
     */
    public function getChildrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->childrenQuery($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->children($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * Get tree leafs query builder
     *
     * @param object $root        - root node in case of root tree is required
     * @param string $sortByField - field name to sort by
     * @param string $direction   - sort direction : "ASC" or "DESC"
     *
     * @throws InvalidArgumentException - if input is not valid
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getLeafsQueryBuilder($root = null, $sortByField = null, $direction = 'ASC')
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);

        if (isset($config['root']) && null === $root) {
            throw new InvalidArgumentException("If tree has root, getLeafs method requires any node of this tree");
        }

        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where($qb->expr()->eq('node.'.$config['right'], '1 + node.'.$config['left']))
        ;
        if (isset($config['root'])) {
            if ($root instanceof $meta->name) {
                $wrapped = new EntityWrapper($root, $this->getEntityManager());
                $rootId = $wrapped->getPropertyValue($config['root']);
                if (!$rootId) {
                    throw new InvalidArgumentException("Root node must be managed");
                }
                $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
                $qb->setParameter('rid', $rootId);
            } else {
                throw new InvalidArgumentException("Node is not related to this repository");
            }
        }
        if (!$sortByField) {
            if (isset($config['root'])) {
                $qb->addOrderBy('node.'.$config['root'], 'ASC');
            }
            $qb->addOrderBy('node.'.$config['left'], 'ASC');
        } else {
            if ($meta->hasField($sortByField) && in_array(strtolower($direction), array('asc', 'desc'))) {
                $qb->orderBy('node.'.$sortByField, $direction);
            } else {
                throw new InvalidArgumentException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }

        return $qb;
    }

    /**
     * Get tree leafs query
     *
     * @param object $root        - root node in case of root tree is required
     * @param string $sortByField - field name to sort by
     * @param string $direction   - sort direction : "ASC" or "DESC"
     *
     * @return \Doctrine\ORM\Query
     */
    public function getLeafsQuery($root = null, $sortByField = null, $direction = 'ASC')
    {
        return $this->getLeafsQueryBuilder($root, $sortByField, $direction)->getQuery();
    }

    /**
     * Get list of leaf nodes of the tree
     *
     * @param object $root        - root node in case of root tree is required
     * @param string $sortByField - field name to sort by
     * @param string $direction   - sort direction : "ASC" or "DESC"
     *
     * @return array
     */
    public function getLeafs($root = null, $sortByField = null, $direction = 'ASC')
    {
        return $this->getLeafsQuery($root, $sortByField, $direction)->getResult();
    }

    /**
     * Get the query builder for next siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf - include the node itself
     *
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getNextSiblingsQueryBuilder($node, $includeSelf = false)
    {
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
        $wrapped = new EntityWrapper($node, $this->getEntityManager());
        if (!$wrapped->hasValidIdentifier()) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }

        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);
        $parent = $wrapped->getPropertyValue($config['parent']);
        if (isset($config['root']) && !$parent) {
            throw new InvalidArgumentException("Cannot get siblings from tree root node");
        }

        $left = $wrapped->getPropertyValue($config['left']);

        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where(
                $includeSelf ?
                $qb->expr()->gte('node.'.$config['left'], $left) :
                $qb->expr()->gt('node.'.$config['left'], $left)
            )
            ->orderBy("node.{$config['left']}", 'ASC')
        ;
        if ($parent) {
            $wrappedParent = new EntityWrapper($parent, $this->getEntityManager());
            $qb->andWhere($qb->expr()->eq('node.'.$config['parent'], ':pid'));
            $qb->setParameter('pid', $wrappedParent->getIdentifier());
        } else {
            $qb->andWhere($qb->expr()->isNull('node.'.$config['parent']));
        }

        return $qb;
    }

    /**
     * Get the query for next siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf - include the node itself
     *
     * @return \Doctrine\ORM\Query
     */
    public function getNextSiblingsQuery($node, $includeSelf = false)
    {
        return $this->getNextSiblingsQueryBuilder($node, $includeSelf)->getQuery();
    }

    /**
     * Find the next siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf - include the node itself
     *
     * @return array
     */
    public function getNextSiblings($node, $includeSelf = false)
    {
        return $this->getNextSiblingsQuery($node, $includeSelf)->getResult();
    }

    /**
     * Get query builder for previous siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf - include the node itself
     *
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getPrevSiblingsQueryBuilder($node, $includeSelf = false)
    {
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
        $wrapped = new EntityWrapper($node, $this->getEntityManager());
        if (!$wrapped->hasValidIdentifier()) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }

        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);
        $parent = $wrapped->getPropertyValue($config['parent']);
        if (isset($config['root']) && !$parent) {
            throw new InvalidArgumentException("Cannot get siblings from tree root node");
        }

        $left = $wrapped->getPropertyValue($config['left']);

        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where(
                $includeSelf ?
                $qb->expr()->lte('node.'.$config['left'], $left) :
                $qb->expr()->lt('node.'.$config['left'], $left)
            )
            ->orderBy("node.{$config['left']}", 'ASC')
        ;
        if ($parent) {
            $wrappedParent = new EntityWrapper($parent, $this->getEntityManager());
            $qb->andWhere($qb->expr()->eq('node.'.$config['parent'], ':pid'));
            $qb->setParameter('pid', $wrappedParent->getIdentifier());
        } else {
            $qb->andWhere($qb->expr()->isNull('node.'.$config['parent']));
        }

        return $qb;
    }

    /**
     * Get query for previous siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf - include the node itself
     *
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
     *
     * @return \Doctrine\ORM\Query
     */
    public function getPrevSiblingsQuery($node, $includeSelf = false)
    {
        return $this->getPrevSiblingsQueryBuilder($node, $includeSelf)->getQuery();
    }

    /**
     * Find the previous siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf - include the node itself
     *
     * @return array
     */
    public function getPrevSiblings($node, $includeSelf = false)
    {
        return $this->getPrevSiblingsQuery($node, $includeSelf)->getResult();
    }

    /**
     * Move the node down in the same level
     *
     * @param object   $node
     * @param int|bool $number integer - number of positions to shift
     *                         boolean - if "true" - shift till last position
     *
     * @throws \RuntimeException - if something fails in transaction
     *
     * @return boolean - true if shifted
     */
    public function moveDown($node, $number = 1)
    {
        $result = false;
        $meta = $this->getClassMetadata();
        if ($node instanceof $meta->name) {
            $nextSiblings = $this->getNextSiblings($node);
            if ($numSiblings = count($nextSiblings)) {
                $result = true;
                if ($number === true) {
                    $number = $numSiblings;
                } elseif ($number > $numSiblings) {
                    $number = $numSiblings;
                }
                $this->listener
                    ->getStrategy($this->getEntityManager(), $meta->name)
                    ->updateNode($this->getEntityManager(), $node, $nextSiblings[$number - 1], Nested::NEXT_SIBLING);
            }
        } else {
            throw new InvalidArgumentException("Node is not related to this repository");
        }

        return $result;
    }

    /**
     * Move the node up in the same level
     *
     * @param object   $node
     * @param int|bool $number integer - number of positions to shift
     *                         boolean - true shift till first position
     *
     * @throws \RuntimeException - if something fails in transaction
     *
     * @return boolean - true if shifted
     */
    public function moveUp($node, $number = 1)
    {
        $result = false;
        $meta = $this->getClassMetadata();
        if ($node instanceof $meta->name) {
            $prevSiblings = array_reverse($this->getPrevSiblings($node));
            if ($numSiblings = count($prevSiblings)) {
                $result = true;
                if ($number === true) {
                    $number = $numSiblings;
                } elseif ($number > $numSiblings) {
                    $number = $numSiblings;
                }
                $this->listener
                    ->getStrategy($this->getEntityManager(), $meta->name)
                    ->updateNode($this->getEntityManager(), $node, $prevSiblings[$number - 1], Nested::PREV_SIBLING);
            }
        } else {
            throw new InvalidArgumentException("Node is not related to this repository");
        }

        return $result;
    }

    /**
     * UNSAFE: be sure to backup before running this method when necessary
     *
     * Removes given $node from the tree and reparents its descendants
     *
     * @param object $node
     *
     * @throws \RuntimeException - if something fails in transaction
     */
    public function removeFromTree($node)
    {
        $meta = $this->getClassMetadata();
        $em = $this->getEntityManager();

        if ($node instanceof $meta->name) {
            $wrapped = new EntityWrapper($node, $em);
            $config = $this->listener->getConfiguration($em, $meta->name);
            $right = $wrapped->getPropertyValue($config['right']);
            $left = $wrapped->getPropertyValue($config['left']);
            $rootId = isset($config['root']) ? $wrapped->getPropertyValue($config['root']) : null;

            if ($right == $left + 1) {
                $this->removeSingle($wrapped);
                $this->listener
                    ->getStrategy($em, $meta->name)
                    ->shiftRL($em, $config['useObjectClass'], $right, -2, $rootId);

                return; // node was a leaf
            }
            // process updates in transaction
            $em->getConnection()->beginTransaction();
            try {
                $parent = $wrapped->getPropertyValue($config['parent']);
                $parentId = null;
                if ($parent) {
                    $wrappedParent = new EntityWrapper($parent, $em);
                    $parentId = $wrappedParent->getIdentifier();
                }
                $pk = $meta->getSingleIdentifierFieldName();
                $nodeId = $wrapped->getIdentifier();
                $shift = -1;

                // in case if root node is removed, children become roots
                if (isset($config['root']) && !$parent) {
                    $qb = $this->getQueryBuilder();
                    $qb->select('node.'.$pk, 'node.'.$config['left'], 'node.'.$config['right'])
                        ->from($config['useObjectClass'], 'node');

                    $qb->andWhere($qb->expr()->eq('node.'.$config['parent'], ':pid'));
                    $qb->setParameter('pid', $nodeId);
                    $nodes = $qb->getQuery()->getArrayResult();

                    foreach ($nodes as $newRoot) {
                        $left = $newRoot[$config['left']];
                        $right = $newRoot[$config['right']];
                        $rootId = $newRoot[$pk];
                        $shift = -($left - 1);

                        $qb = $this->getQueryBuilder();
                        $qb->update($config['useObjectClass'], 'node');
                        $qb->set('node.'.$config['root'], ':rid');
                        $qb->setParameter('rid', $rootId);
                        $qb->where($qb->expr()->eq('node.'.$config['root'], ':rpid'));
                        $qb->setParameter('rpid', $nodeId);
                        $qb->andWhere($qb->expr()->gte('node.'.$config['left'], $left));
                        $qb->andWhere($qb->expr()->lte('node.'.$config['right'], $right));
                        $qb->getQuery()->getSingleScalarResult();

                        $qb = $this->getQueryBuilder();
                        $qb->update($config['useObjectClass'], 'node');
                        $qb->set('node.'.$config['parent'], ':pid');
                        $qb->setParameter('pid', $parentId);
                        $qb->where($qb->expr()->eq('node.'.$config['parent'], ':rpid'));
                        $qb->setParameter('rpid', $nodeId);
                        $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
                        $qb->setParameter('rid', $rootId);
                        $qb->getQuery()->getSingleScalarResult();

                        $this->listener
                            ->getStrategy($em, $meta->name)
                            ->shiftRangeRL($em, $config['useObjectClass'], $left, $right, $shift, $rootId, $rootId, - 1);
                        $this->listener
                            ->getStrategy($em, $meta->name)
                            ->shiftRL($em, $config['useObjectClass'], $right, -2, $rootId);
                    }
                } else {
                    $qb = $this->getQueryBuilder();
                    $qb->update($config['useObjectClass'], 'node');
                    $qb->set('node.'.$config['parent'], ':pid');
                    $qb->setParameter('pid', $parentId);
                    $qb->where($qb->expr()->eq('node.'.$config['parent'], ':rpid'));
                    $qb->setParameter('rpid', $nodeId);
                    if (isset($config['root'])) {
                        $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
                        $qb->setParameter('rid', $rootId);
                    }
                    $qb->getQuery()->getSingleScalarResult();

                    $this->listener
                        ->getStrategy($em, $meta->name)
                        ->shiftRangeRL($em, $config['useObjectClass'], $left, $right, $shift, $rootId, $rootId, - 1);

                    $this->listener
                        ->getStrategy($em, $meta->name)
                        ->shiftRL($em, $config['useObjectClass'], $right, -2, $rootId);
                }
                $this->removeSingle($wrapped);
                $em->getConnection()->commit();
            } catch (\Exception $e) {
                $em->close();
                $em->getConnection()->rollback();
                throw new \Gedmo\Exception\RuntimeException('Transaction failed', null, $e);
            }
        } else {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
    }

    /**
     * Reorders $node's sibling nodes and child nodes,
     * according to the $sortByField and $direction specified
     *
     * @param object|null $node        - node from which to start reordering the tree; null will reorder everything
     * @param string      $sortByField - field name to sort by
     * @param string      $direction   - sort direction : "ASC" or "DESC"
     * @param boolean     $verify      - true to verify tree first
     *
     * @return bool|null
     */
    public function reorder($node, $sortByField = null, $direction = 'ASC', $verify = true)
    {
        $meta = $this->getClassMetadata();
        if ($node instanceof $meta->name || $node === null) {
            $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);
            if ($verify && is_array($this->verify())) {
                return false;
            }

            $nodes = $this->children($node, true, $sortByField, $direction);
            foreach ($nodes as $node) {
                $wrapped = new EntityWrapper($node, $this->getEntityManager());
                $right = $wrapped->getPropertyValue($config['right']);
                $left = $wrapped->getPropertyValue($config['left']);
                $this->moveDown($node, true);
                if ($left != ($right - 1)) {
                    $this->reorder($node, $sortByField, $direction, false);
                }
            }
        } else {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
    }

    /**
     * Reorders all nodes in the tree according to the $sortByField and $direction specified.
     *
     * @param string  $sortByField - field name to sort by
     * @param string  $direction   - sort direction : "ASC" or "DESC"
     * @param boolean $verify      - true to verify tree first
     */
    public function reorderAll($sortByField = null, $direction = 'ASC', $verify = true)
    {
        $this->reorder(null, $sortByField, $direction, $verify);
    }

    /**
     * Verifies that current tree is valid.
     * If any error is detected it will return an array
     * with a list of errors found on tree
     *
     * @return array|bool - true on success,error list on failure
     */
    public function verify()
    {
        if (!$this->childCount()) {
            return true; // tree is empty
        }

        $errors = array();
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);
        if (isset($config['root'])) {
            $trees = $this->getRootNodes();
            foreach ($trees as $tree) {
                $this->verifyTree($errors, $tree);
            }
        } else {
            $this->verifyTree($errors);
        }

        return $errors ?: true;
    }

    /**
     * NOTE: flush your entity manager after
     *
     * Tries to recover the tree
     *
     * @return void
     */
    public function recover()
    {
        if ($this->verify() === true) {
            return;
        }

        $meta = $this->getClassMetadata();
        $em = $this->getEntityManager();
        $config = $this->listener->getConfiguration($em, $meta->name);
        $self = $this;

        $doRecover = function ($root, &$count, $level = 0) use ($meta, $config, $self, $em, &$doRecover) {
            $lft = $count++;
            foreach ($self->getChildren($root, true) as $child) {
                $doRecover($child, $count, $level + 1);
            }
            $rgt = $count++;
            $meta->getReflectionProperty($config['left'])->setValue($root, $lft);
            $meta->getReflectionProperty($config['right'])->setValue($root, $rgt);
            if (isset($config['level'])) {
                $meta->getReflectionProperty($config['level'])->setValue($root, $level);
            }
            $em->persist($root);
        };

        if (isset($config['root'])) {
            foreach ($this->getRootNodes() as $root) {
                $count = 1; // reset on every root node
                $doRecover($root, $count);
            }
        } else {
            $count = 1;
            foreach ($this->getChildren(null, true) as $root) {
                $doRecover($root, $count);
            }
        }
    }

    /**
     * Added in Chamilo.
     */
    public function recoverNode($node, $sortByField = null)
    {
        $meta = $this->getClassMetadata();
        $em = $this->getEntityManager();
        $config = $this->listener->getConfiguration($em, $meta->name);
        $doRecover = function ($root, &$count, $level = 0) use ($meta, $config, $node, $em, $sortByField, &$doRecover) {
            $lft = $count++;
            foreach ($this->getChildren($root, true, $sortByField) as $child) {
                $doRecover($child, $count, $level + 1);
            }
            $rgt = $count++;
            $meta->getReflectionProperty($config['left'])->setValue($root, $lft);
            $meta->getReflectionProperty($config['right'])->setValue($root, $rgt);
            if (isset($config['level'])) {
                $meta->getReflectionProperty($config['level'])->setValue($root, $level);
            }
            $em->persist($root);
        };

        $count = 1;
        $doRecover($node, $count);
    }

    public function verifyNode($node)
    {
        if (!$node->childCount()) {
            return true; // tree is empty
        }

        $errors = array();
        $this->verifyTree($errors, $node);

        return $errors ?: true;
    }

    /**
     * {@inheritDoc}
     */
    public function getNodesHierarchyQueryBuilder($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);

        return $this->childrenQueryBuilder(
            $node,
            $direct,
            isset($config['root']) ? array($config['root'], $config['left']) : $config['left'],
            'ASC',
            $includeNode
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getNodesHierarchyQuery($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        return $this->getNodesHierarchyQueryBuilder($node, $direct, $options, $includeNode)->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesHierarchy($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        return $this->getNodesHierarchyQuery($node, $direct, $options, $includeNode)->getArrayResult();
    }

    /**
     * {@inheritdoc}
     */
    protected function validate()
    {
        return $this->listener->getStrategy($this->getEntityManager(), $this->getClassMetadata()->name)->getName() === Strategy::NESTED;
    }

    /**
     * Collect errors on given tree if
     * where are any
     *
     * @param array  $errors
     * @param object $root
     */
    private function verifyTree(&$errors, $root = null)
    {
        $meta = $this->getClassMetadata();
        $em = $this->getEntityManager();
        $config = $this->listener->getConfiguration($em, $meta->name);

        $identifier = $meta->getSingleIdentifierFieldName();
        $rootId = isset($config['root']) ? $meta->getReflectionProperty($config['root'])->getValue($root) : null;
        $qb = $this->getQueryBuilder();
        $qb->select($qb->expr()->min('node.'.$config['left']))
            ->from($config['useObjectClass'], 'node')
        ;
        if (isset($config['root'])) {
            $qb->where($qb->expr()->eq('node.'.$config['root'], ':rid'));
            $qb->setParameter('rid', $rootId);
        }
        $min = intval($qb->getQuery()->getSingleScalarResult());
        $edge = $this->listener->getStrategy($em, $meta->name)->max($em, $config['useObjectClass'], $rootId);
        // check duplicate right and left values
        for ($i = $min; $i <= $edge; $i++) {
            $qb = $this->getQueryBuilder();
            $qb->select($qb->expr()->count('node.'.$identifier))
                ->from($config['useObjectClass'], 'node')
                ->where($qb->expr()->orX(
                    $qb->expr()->eq('node.'.$config['left'], $i),
                    $qb->expr()->eq('node.'.$config['right'], $i)
                ))
            ;
            if (isset($config['root'])) {
                $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
                $qb->setParameter('rid', $rootId);
            }
            $count = intval($qb->getQuery()->getSingleScalarResult());
            if ($count !== 1) {
                if ($count === 0) {
                    $errors[] = "index [{$i}], missing".($root ? ' on tree root: '.$rootId : '');
                } else {
                    $errors[] = "index [{$i}], duplicate".($root ? ' on tree root: '.$rootId : '');
                }
            }
        }
        // check for missing parents
        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->leftJoin('node.'.$config['parent'], 'parent')
            ->where($qb->expr()->isNotNull('node.'.$config['parent']))
            ->andWhere($qb->expr()->isNull('parent.'.$identifier))
        ;
        if (isset($config['root'])) {
            $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
            $qb->setParameter('rid', $rootId);
        }
        $nodes = $qb->getQuery()->getArrayResult();
        if (count($nodes)) {
            foreach ($nodes as $node) {
                $errors[] = "node [{$node[$identifier]}] has missing parent".($root ? ' on tree root: '.$rootId : '');
            }

            return; // loading broken relation can cause infinite loop
        }

        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where($qb->expr()->lt('node.'.$config['right'], 'node.'.$config['left']))
        ;
        if (isset($config['root'])) {
            $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
            $qb->setParameter('rid', $rootId);
        }
        $result = $qb->getQuery()
            ->setMaxResults(1)
            ->getResult(Query::HYDRATE_ARRAY);
        $node = count($result) ? array_shift($result) : null;

        if ($node) {
            $id = $node[$identifier];
            $errors[] = "node [{$id}], left is greater than right".($root ? ' on tree root: '.$rootId : '');
        }

        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
        ;
        if (isset($config['root'])) {
            $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
            $qb->setParameter('rid', $rootId);
        }
        $nodes = $qb->getQuery()->getResult(Query::HYDRATE_OBJECT);

        foreach ($nodes as $node) {
            $right = $meta->getReflectionProperty($config['right'])->getValue($node);
            $left = $meta->getReflectionProperty($config['left'])->getValue($node);
            $id = $meta->getReflectionProperty($identifier)->getValue($node);
            $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
            if (!$right || !$left) {
                $errors[] = "node [{$id}] has invalid left or right values";
            } elseif ($right == $left) {
                $errors[] = "node [{$id}] has identical left and right values";
            } elseif ($parent) {
                if ($parent instanceof Proxy && !$parent->__isInitialized__) {
                    $em->refresh($parent);
                }
                $parentRight = $meta->getReflectionProperty($config['right'])->getValue($parent);
                $parentLeft = $meta->getReflectionProperty($config['left'])->getValue($parent);
                $parentId = $meta->getReflectionProperty($identifier)->getValue($parent);
                if ($left < $parentLeft) {
                    $errors[] = "node [{$id}] left is less than parent`s [{$parentId}] left value";
                } elseif ($right > $parentRight) {
                    $errors[] = "node [{$id}] right is greater than parent`s [{$parentId}] right value";
                }
            } else {
                $qb = $this->getQueryBuilder();
                $qb->select($qb->expr()->count('node.'.$identifier))
                    ->from($config['useObjectClass'], 'node')
                    ->where($qb->expr()->lt('node.'.$config['left'], $left))
                    ->andWhere($qb->expr()->gt('node.'.$config['right'], $right))
                ;
                if (isset($config['root'])) {
                    $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
                    $qb->setParameter('rid', $rootId);
                }
                if ($count = intval($qb->getQuery()->getSingleScalarResult())) {
                    $errors[] = "node [{$id}] parent field is blank, but it has a parent";
                }
            }
        }
    }

    /**
     * Removes single node without touching children
     *
     * @internal
     *
     * @param EntityWrapper $wrapped
     */
    private function removeSingle(EntityWrapper $wrapped)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);

        $pk = $meta->getSingleIdentifierFieldName();
        $nodeId = $wrapped->getIdentifier();
        // prevent from deleting whole branch
        $qb = $this->getQueryBuilder();
        $qb->update($config['useObjectClass'], 'node')
            ->set('node.'.$config['left'], 0)
            ->set('node.'.$config['right'], 0);

        $qb->andWhere($qb->expr()->eq('node.'.$pk, ':id'));
        $qb->setParameter('id', $nodeId);
        $qb->getQuery()->getSingleScalarResult();

        // remove the node from database
        $qb = $this->getQueryBuilder();
        $qb->delete($config['useObjectClass'], 'node');
        $qb->andWhere($qb->expr()->eq('node.'.$pk, ':id'));
        $qb->setParameter('id', $nodeId);
        $qb->getQuery()->getSingleScalarResult();

        // remove from identity map
        $this->getEntityManager()->getUnitOfWork()->removeFromIdentityMap($wrapped->getObject());
    }
}
