<textarea <?php echo $view['form']->block($form, 'attributes') ?>><?php echo htmlspecialchars($value) ?></textarea>

<?php if ($enable) : ?>
    <?php if (!$view['ivory_ckeditor']->isLoaded()) : ?>
        <script type="text/javascript">
            var CKEDITOR_BASEPATH = "<?php echo $view['ivory_ckeditor']->renderBasePath($base_path); ?>";
        </script>

        <script type="text/javascript" src="<?php echo $view['ivory_ckeditor']->renderJsPath($js_path); ?>"></script>
    <?php endif; ?>

    <script type="text/javascript">
        <?php echo $view['ivory_ckeditor']->renderDestroy($id); ?>

        <?php foreach ($plugins as $pluginName => $plugin): ?>
            <?php echo $view['ivory_ckeditor']->renderPlugin($pluginName, $plugin); ?>
        <?php endforeach; ?>

        <?php foreach ($styles as $styleName => $style): ?>
            <?php echo $view['ivory_ckeditor']->renderStylesSet($styleName, $style); ?>
        <?php endforeach; ?>

        <?php foreach ($templates as $templateName => $template): ?>
            <?php echo $view['ivory_ckeditor']->renderTemplate($templateName, $template); ?>
        <?php endforeach; ?>

        <?php echo $view['ivory_ckeditor']->renderReplace($id, $config); ?>
    </script>
<?php endif; ?>
