<?php
/**
 * JCapture plugin
 *
 * @author Pavel Vlasov
 */
 
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
require_once (DOKU_PLUGIN.'action.php');
 
class action_plugin_jcapture extends DokuWiki_Action_Plugin {
 
  /**
   * return some info
   */
  function getInfo() {
    return array(
                 'author' => 'Pavel Vlasov',
                 'email'  => 'Pavel.Vlasov@nasdanika.com',
                 'name'   => 'JCapture',
                 'desc'   => 'Plugin for making screen captures.',
                 'url'    => 'http://www.nasdanika.com/wiki/doku.php?id=products:jcapture:start',
                 );
  }

    /**
     * Register the eventhandlers
     */
    function register(&$controller) {
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insert_button', array());
    }
 
    /**
     * Inserts the toolbar button
     */
    function insert_button(& $event, $param) {
        $event->data[] = array(
            'type' => 'JCapture',
            'title' => 'Screen capture',
            'icon' => '../../plugins/jcapture/camera.png',
            'open' => '<abutton>',
            'close' => '</abutton>',
        );
    }
 
}

