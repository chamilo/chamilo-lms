<?php
/* For licensing terms, see /license.txt */

use Zend\Feed\Reader\Reader;
use Zend\Feed\Reader\Feed\FeedInterface;

$plugin = RssPlugin::create();

$url = $plugin->get_rss();
$title = $plugin->get_block_title();
$title = $title ? "<h4>$title</h4>" : '';
$css = $plugin->get_css();

if (empty($url)) {
    echo Display::return_message(get_lang('NoRSSItem'), 'warning');
    return;
}

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
