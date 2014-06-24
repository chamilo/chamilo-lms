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

namespace SimpleThings\EntityAudit\Request;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\SecurityContext;
use SimpleThings\EntityAudit\AuditConfiguration;

/**
 * Inject the SecurityContext username into the AuditConfiguration as current username.
 */
class CurrentUserListener
{
    /**
     * @var AuditConfiguration
     */
    private $auditConfiguration;
    /**
     * @var SecuritYcontext
     */
    private $securityContext;
    
    public function __construct(AuditConfiguration $config, SecurityContext $context = null)
    {
        $this->auditConfiguration = $config;
        $this->securityContext = $context;
    }
    
    /**
     * Handles access authorization.
     *
     * @param GetResponseEvent $event An Event instance
     */
    public function handle(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if ($this->securityContext) {
            $token = $this->securityContext->getToken();
            if ($token && $token->isAuthenticated()) {
                $this->auditConfiguration->setCurrentUsername($token->getUsername());
            }
        }
    }
}
