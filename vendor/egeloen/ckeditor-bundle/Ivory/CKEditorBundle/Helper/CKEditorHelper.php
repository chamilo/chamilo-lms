<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Helper;

use Ivory\JsonBuilder\JsonBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Templating\Helper\Helper;

/**
 * CKEditor helper.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class CKEditorHelper extends Helper
{
    /** @var boolean */
    protected $loaded = false;

    /** @var \Ivory\JsonBuilder\JsonBuilder */
    protected $jsonBuilder;

    /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
    protected $container;

    /**
     * Creates a CKEditor template helper.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container The container.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->jsonBuilder = new JsonBuilder();
        $this->container = $container;
    }

    /**
     * Checks if CKEditor is loaded.
     *
     * @return boolean TRUE if CKEditor is loaded else FALSE.
     */
    public function isLoaded()
    {
        return $this->loaded;
    }

    /**
     * Renders the base path.
     *
     * @param string $basePath The base path.
     *
     * @return string The rendered base path.
     */
    public function renderBasePath($basePath)
    {
        return $this->getAssetsVersionTrimerHelper()->trim($this->getAssetsHelper()->getUrl($basePath));
    }

    /**
     * Renders the js path.
     *
     * @param string $jsPath The js path.
     *
     * @return string The rendered js path.
     */
    public function renderJsPath($jsPath)
    {
        return $this->getAssetsHelper()->getUrl($jsPath);
    }

    /**
     * Renders the replace.
     *
     * @param string $id     The identifier.
     * @param array  $config The config.
     *
     * @return string The rendered replace.
     */
    public function renderReplace($id, array $config)
    {
        $this->loaded = true;

        $this->jsonBuilder
            ->reset()
            ->setValues($this->fixConfigFilebrowsers($this->fixConfigContentsCss($config)));

        $this->fixConfigEscapedValues($config);

        return sprintf('CKEDITOR.replace("%s", %s);', $id, $this->fixConfigConstants($this->jsonBuilder->build()));
    }

    /**
     * Renders the destroy.
     *
     * @param string $id The identifier.
     *
     * @return string The rendered destroy.
     */
    public function renderDestroy($id)
    {
        return sprintf('if (CKEDITOR.instances["%s"]) { delete CKEDITOR.instances["%s"]; }', $id, $id);
    }

    /**
     * Renders a plugin.
     *
     * @param string $name   The name.
     * @param array  $plugin The plugin.
     *
     * @return string The rendered plugin.
     */
    public function renderPlugin($name, array $plugin)
    {
        return sprintf(
            'CKEDITOR.plugins.addExternal("%s", "%s", "%s");',
            $name,
            $this->getAssetsVersionTrimerHelper()->trim($this->getAssetsHelper()->getUrl($plugin['path'])),
            $plugin['filename']
        );
    }

    /**
     * Renders a styles set.
     *
     * @param string $name      The name
     * @param array  $stylesSet The style set.
     *
     * @return string The rendered style set.
     */
    public function renderStylesSet($name, array $stylesSet)
    {
        $this->jsonBuilder
            ->reset()
            ->setValues($stylesSet);

        return sprintf(
            'if (CKEDITOR.stylesSet.get("%s") === null) { CKEDITOR.stylesSet.add("%s", %s); }',
            $name,
            $name,
            $this->jsonBuilder->build()
        );
    }

    /**
     * Renders a template.
     *
     * @param string $name     The template name.
     * @param array  $template The template.
     *
     * @return string The rendered template.
     */
    public function renderTemplate($name, array $template)
    {
        if (isset($template['imagesPath'])) {
            $template['imagesPath'] = $this->getAssetsVersionTrimerHelper()->trim(
                $this->getAssetsHelper()->getUrl($template['imagesPath'])
            );
        }

        $this->jsonBuilder
            ->reset()
            ->setValues($template);

        return sprintf('CKEDITOR.addTemplates("%s", %s);', $name, $this->jsonBuilder->build());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ivory_ckeditor';
    }

    /**
     * Fixes the config contents css.
     *
     * @param array $config The config.
     *
     * @return array The fixed config.
     */
    protected function fixConfigContentsCss(array $config)
    {
        if (isset($config['contentsCss'])) {
            $cssContents = (array) $config['contentsCss'];

            $config['contentsCss'] = array();
            foreach ($cssContents as $cssContent) {
                $config['contentsCss'][] = $this->getAssetsVersionTrimerHelper()->trim(
                    $this->getAssetsHelper()->getUrl($cssContent)
                );
            }
        }

        return $config;
    }

    /**
     * Fix the config filebrowsers.
     *
     * @param array $config The config.
     *
     * @return array The fixed config.
     */
    protected function fixConfigFilebrowsers(array $config)
    {
        $filebrowserKeys = array(
            'Browse',
            'FlashBrowse',
            'ImageBrowse',
            'ImageBrowseLink',
            'Upload',
            'FlashUpload',
            'ImageUpload',
        );

        foreach ($filebrowserKeys as $filebrowserKey) {
            $filebrowserHandler = 'filebrowser'.$filebrowserKey.'Handler';
            $filebrowserUrl = 'filebrowser'.$filebrowserKey.'Url';
            $filebrowserRoute = 'filebrowser'.$filebrowserKey.'Route';
            $filebrowserRouteParameters = 'filebrowser'.$filebrowserKey.'RouteParameters';
            $filebrowserRouteAbsolute = 'filebrowser'.$filebrowserKey.'RouteAbsolute';

            if (isset($config[$filebrowserHandler])) {
                $config[$filebrowserUrl] = $config[$filebrowserHandler]($this->getRouter());
            } elseif (isset($config[$filebrowserRoute])) {
                $config[$filebrowserUrl] = $this->getRouter()->generate(
                    $config[$filebrowserRoute],
                    isset($config[$filebrowserRouteParameters]) ? $config[$filebrowserRouteParameters] : array(),
                    isset($config[$filebrowserRouteAbsolute]) ? $config[$filebrowserRouteAbsolute] : false
                );
            }

            unset($config[$filebrowserHandler]);
            unset($config[$filebrowserRoute]);
            unset($config[$filebrowserRouteParameters]);
            unset($config[$filebrowserRouteAbsolute]);
        }

        return $config;
    }

    /**
     * Fixes the config escaped values and sets them on the json builder.
     *
     * @param array $config The config.
     */
    protected function fixConfigEscapedValues(array $config)
    {
        if (isset($config['protectedSource'])) {
            foreach ($config['protectedSource'] as $key => $value) {
                $this->jsonBuilder->setValue(sprintf('[protectedSource][%s]', $key), $value, false);
            }
        }

        $escapedValueKeys = array(
            'stylesheetParser_skipSelectors',
            'stylesheetParser_validSelectors',
        );

        foreach ($escapedValueKeys as $escapedValueKey) {
            if (isset($config[$escapedValueKey])) {
                $this->jsonBuilder->setValue(sprintf('[%s]', $escapedValueKey), $config[$escapedValueKey], false);
            }
        }
    }

    /**
     * Fixes the config constants.
     *
     * @param string $json The json config.
     *
     * @return string The fixes config.
     */
    protected function fixConfigConstants($json)
    {
        return preg_replace('/"(CKEDITOR\.[A-Z_]+)"/', '$1', $json);
    }

    /**
     * Gets the assets helper.
     *
     * @return \Symfony\Component\Templating\Helper\CoreAssetsHelper The assets helper.
     */
    protected function getAssetsHelper()
    {
        return $this->container->get('templating.helper.assets');
    }

    /**
     * Gets the assets version trimer helper.
     *
     * @return \Ivory\CKEditorBundle\Helper\AssetsVersionTrimerHelper The assets version trimer helper.
     */
    protected function getAssetsVersionTrimerHelper()
    {
        return $this->container->get('ivory_ck_editor.helper.assets_version_trimer');
    }

    /**
     * Gets the router.
     *
     * @return \Symfony\Component\Routing\RouterInterface The router.
     */
    protected function getRouter()
    {
        return $this->container->get('router');
    }
}
