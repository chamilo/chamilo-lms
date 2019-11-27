<?php
/* For licensing terms, see /license.txt */

use Zend\Feed\Reader\Feed\FeedInterface;
use Zend\Feed\Reader\Reader;

$plugin = RssPlugin::create();

$url = $plugin->get_rss();
$title = $plugin->get_block_title();
$title = $title ? "<h4>$title</h4>" : '';
$css = $plugin->get_css();

if (empty($url)) {
    echo Display::return_message(get_lang('NoRSSItem'), 'warning');

    return;
}
try {
    $channel = Reader::import($url);
    if (!empty($channel)) {
        /** @var FeedInterface $item */
        foreach ($channel as $item) {
            $title = $item->getTitle();
            $link = $item->getLink();
            if (!empty($link)) {
                $title = Display::url($title, $link, ['target' => '_blank']);
            }
            echo Display::panel($item->getDescription(), $title);
        }
    }
} catch (Exception $e) {
    echo Display::return_message($plugin->get_lang('no_valid_rss'), 'warning');
    error_log($e->getMessage());
}
