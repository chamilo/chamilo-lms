<?php
/**
 * pfcproxycommand.class.php
 *
 * Copyright © 2006 Stephane Gully <stephane.gully@gmail.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details. 
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the
 * Free Software Foundation, 51 Franklin St, Fifth Floor,
 * Boston, MA  02110-1301  USA
 */
require_once dirname(__FILE__)."/pfci18n.class.php";
require_once dirname(__FILE__)."/pfcuserconfig.class.php";
require_once dirname(__FILE__)."/pfccommand.class.php";

/**
 * pfcProxyCommand is an abstract class (interface) which must be inherited by each concrete proxy commands
 *
 * @author Stephane Gully <stephane.gully@gmail.com>
 */
class pfcProxyCommand extends pfcCommand
{
  /**
   * Next proxy command
   */
  var $next;

  /**
   * The proxy name (similare to the command name)
   */
  var $proxyname;
  
  /**
   * Constructor
   */
  function pfcProxyCommand()
  {
    pfcCommand::pfcCommand();
  }

  function linkTo(&$cmd)
  {
    $this->next = $cmd;
  }
}

?>