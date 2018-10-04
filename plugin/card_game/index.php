<?php
/* For license terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This is the main script of the Card Game plugin.
 * It is loaded on every page through the inclusion of the plugin in the
 * pre_footer region (a mandatory step of the installation).
 *
 * @author Damien Renou
 *
 * @package chamilo.plugin.card_game
 */
require_once __DIR__.'/../../main/inc/global.inc.php';

if (!api_is_anonymous()) {
    require_once 'card_game.php';
    $cardGame = CardGame::create();

    $version = '?v=041';
    $interface = 'localhost';
    $parsedUrl = parse_url($_SERVER['REQUEST_URI']);
    $parsedUrlpath = $parsedUrl['path'];
    $pluginPath = api_get_path(WEB_PLUGIN_PATH).'card_game/resources/';

    $fh = '<script type="text/javascript" src="'.$pluginPath.'js/cardgame.js'.$version.'" ></script>';
    $fh .= '<link href="'.$pluginPath.'css/cardgame.css'.$version.'" rel="stylesheet" type="text/css">';

    $fh .= '<div id="cardgamemessage" style="display:none;" >'.$cardGame->get_lang('openDeckCardGame').'</div>';
    $fh .= '<div id="cardgameengage" style="display:none;" >'.$cardGame->get_lang('engageDeckCardGame').'</div>';
    $fh .= '<div id="cardgameloose" style="display:none;" >'.$cardGame->get_lang('cardgameloose').'</div>';

    $fh .= '<div id="linkcardgame" style="display:none;" >'.$pluginPath.'ajax.card.php</div>';

    $user = api_get_user_info();
    $userId = (int) $user['id'];

    $cardGameSession = Session::read('cardgame');
    if (!empty($cardGameSession)) {
        if (isset($userId)) {
            $sqlCount = "SELECT access_date FROM plugin_card_game WHERE user_id = $userId";
            $resultCount = Database::query($sqlCount)->rowCount();

            if ($resultCount === 0) {
                // @todo change date call
                $sql = "INSERT INTO plugin_card_game (user_id, access_date, pan) 
                        VALUES ($userId, DATE_ADD(CURDATE(), INTERVAL -1 DAY), 1);";
                $resultInsert = Database::query($sql);
                Session::write('cardgame', 'havedeck');
            } else {
                // @todo change date call
                $sqlDate = "SELECT access_date 
                          FROM plugin_card_game 
                          WHERE access_date = CURDATE() 
                          AND user_id = $userId";
                $resultDate = Database::query($sqlDate)->rowCount();

                if ($resultDate == 0) {
                    Session::write('cardgame', 'havedeck');
                    $fh .= '<div id="havedeckcardgame" ></div>';
                } else {
                    Session::write('cardgame', 'done');
                }
            }
        }
    } else {
        if ($cardGameSession == 'havedeck') {
            $fh .= '<div id="havedeckcardgame" ></div>';
        }
    }

    $parts = '';

    if (isset($userId)) {
        try {
            $sqlParts = "SELECT parts, pan FROM plugin_card_game WHERE user_id = $userId";
            $resultParts = Database::query($sqlParts);
            while ($part = Database::fetch_array($resultParts)) {
                $parts = $part['parts'];
                $pan = $part['pan'];
            }
        } catch (Exception $e) {
            echo 'Exception: ', $e->getMessage(), "\n";
        }
    }

    echo '<div id="memocardgame" style="display:none;" >'.$parts.'</div>';
    echo '<div id="pancardgame" style="display:none;" >'.$pan.'</div>';

    echo $fh;
}
