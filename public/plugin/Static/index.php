<?php

$plugin = StaticPlugin::create();

if (!$plugin->isEnabled()) {
    return;
}

$content = trim($plugin->get_content());

if ('' === $content) {
    return;
}

$title = trim($plugin->get_block_title());
$css = trim($plugin->get_css());
$escapedTitle = '' !== $title ? Security::remove_XSS($title) : '';

if ('' !== $css) {
    echo '<style type="text/css">'.$css.'</style>';
}
?>

<section class="static-plugin rounded-lg border border-gray-25 bg-white p-4 shadow-sm">
    <?php if ('' !== $escapedTitle) { ?>
        <h4 class="mb-3 text-lg font-semibold text-gray-90">
            <?php echo $escapedTitle; ?>
        </h4>
    <?php } ?>

    <div class="static-plugin__content prose max-w-none text-gray-90">
        <?php echo $content; ?>
    </div>
</section>
