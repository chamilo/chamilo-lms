<?php
/* For license terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This script answers to AJAX calls to store a new piece as revealed
 * in the plugin_card_game table.
 *
 * @author Damien Renou
 *
 * @package chamilo.plugin.card_game
 */
require_once __DIR__.'/../../../main/inc/global.inc.php';

$cardGameSession = Session::read('cardgame');

if (!empty($cardGameSession)) {
    if ($cardGameSession == 'havedeck') {
        $part = '1';
        if (isset($_GET['part'])) {
            $part = (int) $_GET['part'];
            $userId = api_get_user_id();

            if (isset($userId)) {
                $sql = "UPDATE plugin_card_game 
                        SET access_date = CURDATE(), parts = CONCAT(parts,'!!$part;!') 
                        WHERE user_id = $userId";
                Database::query($sql);
                Session::write('cardgame', 'done');

                $sql = "UPDATE plugin_card_game 
                        SET pan = pan + 1, parts = '' 
                        WHERE parts LIKE '%!1;%' AND parts LIKE '%!2;%' AND parts LIKE '%!3;%' AND parts LIKE '%!4;%' AND parts LIKE '%!5;%' AND parts LIKE '%!6;%' AND parts LIKE '%!7%' AND parts LIKE '%!8%' AND parts LIKE '%!9%' AND parts LIKE '%10%' AND parts LIKE '%11%' AND parts LIKE '%12%' AND parts LIKE '%13%' AND parts LIKE '%14%' AND parts LIKE '%15%' 
                        AND user_id = $userId";
                Database::query($sql);
            }
        } elseif (isset($_GET['loose'])) {
            $userId = api_get_user_id();

            if (isset($userId)) {
                $sql = "UPDATE plugin_card_game 
                        SET access_date = CURDATE() 
                        WHERE user_id = $userId";
                Database::query($sql);
                Session::write('cardgame', 'done');
            }
        }
    }
}
