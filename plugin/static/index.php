<?php

$plugin = StaticPlugin::create();

$content = $plugin->get_content();

$title = $plugin->get_block_title();
$title = $title ? "<h4>$title</h4>" : '';

$css = $plugin->get_css();
$css = $css ? "<style type=\"text/css\">$css</style>" : '';

if (empty($content))
{
    echo '';
}

echo <<<EOT
<div class="well sidebar-nav static">
    $css
    $title
    $content
</div>
EOT;
