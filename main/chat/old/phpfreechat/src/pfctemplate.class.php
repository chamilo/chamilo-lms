<?php
/**
 * pfctemplate.class.php
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

/**
 * pfcTemplate is used to display chat templates (html and javascript)
 * @author Stephane Gully <stephane.gully@gmail.com>
 */
class pfcTemplate
{
  var $tpl_filename;
  var $vars;
  
  function pfcTemplate($tpl_filename = "")
  {
    $this->tpl_filename = $tpl_filename;
  }

  function setTemplate($tpl_filename)
  {
    $this->tpl_filename = $tpl_filename;
  }

  function getOutput()
  {
    ob_start();
    if (!file_exists($this->tpl_filename))
      die(_pfc("%s template could not be found", $this->tpl_filename));
    // assign defined vars to this template
    foreach( $this->vars as $v_name => $v_val )
      $$v_name = $v_val;
    // execute the template
    include($this->tpl_filename);
    $result = ob_get_contents();
    ob_end_clean();
    return $result;
  }

  function assignObject(&$obj, $name = "c")
  {
    $vars = get_object_vars($obj);
    foreach( $vars as $v_name => $v_val )
      $this->vars[$v_name] = $v_val;
    $this->vars[$name] =& $obj; // assigne also the whole object
  }
}

?>