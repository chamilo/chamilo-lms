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

namespace SimpleThings\EntityAudit\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for listing auditing information
 *
 * @author Tim Nagel <tim@nagel.com.au>
 */
class AuditController extends Controller
{
    /**
     * @return \SimpleThings\EntityAudit\AuditReader
     */
    protected function getAuditReader()
    {
        return $this->get('simplethings_entityaudit.reader');
    }

    /**
     * @return \SimpleThings\EntityAudit\AuditManager
     */
    protected function getAuditManager()
    {
        return $this->get('simplethings_entityaudit.manager');
    }

    /**
     * Renders a paginated list of revisions.
     *
     * @param int $page
     * @return Response
     */
    public function indexAction($page = 1)
    {
        $reader = $this->getAuditReader();
        $revisions = $reader->findRevisionHistory(20, 20 * ($page - 1));

        return $this->render('SimpleThingsEntityAuditBundle:Audit:index.html.twig', array(
            'revisions' => $revisions,
        ));
    }

    /**
     * Shows entities changed in the specified revision.
     *
     * @param integer $rev
     * @return Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function viewRevisionAction($rev)
    {
        $revision = $this->getAuditReader()->findRevision($rev);
        if (!$revision) {
            throw $this->createNotFoundException(sprintf('Revision %i not found', $rev));
        }

        $changedEntities = $this->getAuditReader()->findEntitiesChangedAtRevision($rev);

        return $this->render('SimpleThingsEntityAuditBundle:Audit:view_revision.html.twig', array(
            'revision' => $revision,
            'changedEntities' => $changedEntities,
        ));
    }

    /**
     * Lists revisions for the supplied entity.
     *
     * @param string $className
     * @param string $id
     * @return Response
     */
    public function viewEntityAction($className, $id)
    {
        $ids = explode(',', $id);
        $revisions = $this->getAuditReader()->findRevisions($className, $ids);

        return $this->render('SimpleThingsEntityAuditBundle:Audit:view_entity.html.twig', array(
            'id' => $id,
            'className' => $className,
            'revisions' => $revisions,
        ));
    }

    /**
     * Shows the data for an entity at the specified revision.
     *
     * @param string $className
     * @param string $id Comma separated list of identifiers
     * @param int $rev
     * @return Response
     */
    public function viewDetailAction($className, $id, $rev)
    {
        $ids = explode(',', $id);
        $entity = $this->getAuditReader()->find($className, $ids, $rev);

        $data = $this->getAuditReader()->getEntityValues($className, $entity);
        krsort($data);

        return $this->render('SimpleThingsEntityAuditBundle:Audit:view_detail.html.twig', array(
            'id' => $id,
            'rev' => $rev,
            'className' => $className,
            'entity' => $entity,
            'data' => $data,
        ));
    }

    /**
     * Compares an entity at 2 different revisions.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $className
     * @param string $id Comma separated list of identifiers
     * @param null|int $oldRev if null, pulled from the query string
     * @param null|int $newRev if null, pulled from the query string
     * @return Response
     */
    public function compareAction(Request $request, $className, $id, $oldRev = null, $newRev = null)
    {
        if (null === $oldRev) {
            $oldRev = $request->query->get('oldRev');
        }

        if (null === $newRev) {
            $newRev = $request->query->get('newRev');
        }

        $ids = explode(',', $id);
        $diff = $this->getAuditReader()->diff($className, $ids, $oldRev, $newRev);

        return $this->render('SimpleThingsEntityAuditBundle:Audit:compare.html.twig', array(
            'className' => $className,
            'id' => $id,
            'oldRev' => $oldRev,
            'newRev' => $newRev,
            'diff' => $diff,
        ));
    }

}