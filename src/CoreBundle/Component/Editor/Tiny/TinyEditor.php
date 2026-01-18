<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Tiny;

use Chamilo\CoreBundle\Component\Editor\Editor;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\SystemTemplate;
use Chamilo\CoreBundle\Entity\Templates;
use Chamilo\CoreBundle\Framework\Container;
use Database;

use const JSON_UNESCAPED_SLASHES;

class TinyEditor extends Editor
{
    /**
     * Return the HTML code required to run editor.
     *
     * @param string $value
     */
    public function createHtml($value): string
    {
        $html = '<textarea id="'.$this->getTextareaId().'" name="'.$this->getName().'" >'
            .$value
            .'</textarea>';
        $html .= $this->editorReplace();

        return $html;
    }

    /**
     * Return the HTML code required to run editor with inline style (full page).
     *
     * @param string $value
     */
    public function createHtmlStyle($value): string
    {
        $style = '';
        $value = trim($value);

        if ('' === $value || '<html><head><title></title></head><body></body></html>' === $value) {
            // Load default CSS only when the editor is empty to provide a visual baseline
            $style = api_get_bootstrap_and_font_awesome();
            $style .= Container::getThemeHelper()->getThemeAssetLinkTag('document.css');
        }

        $html = '<textarea id="'.$this->getTextareaId().'" name="'.$this->getName().'" >'
            .$style.$value
            .'</textarea>';
        $html .= $this->editorReplace();

        return $html;
    }

    public function editorReplace(): string
    {
        $toolbar = new Toolbar\Basic(
            $this->urlGenerator,
            $this->toolbarSet,
            $this->config,
            'CkEditor'
        );

        $config = $toolbar->getConfig();
        $config['selector'] = '#'.$this->getTextareaId();

        // Convert PHP config to a TinyMCE init JavaScript snippet
        $javascript = $this->toJavascript($config);

        // Replace file browser placeholder by our File Manager picker
        $javascript = str_replace('"[browser]"', $this->getFileManagerPicker(), $javascript);

        return "<script>
            // 1) Cross-frame bridge: accept file URL messages from the File Manager
            //    Supported payloads:
            //      - { url: '...' }
            //      - { mceAction: 'fileSelected', content: { url: '...' }, cbId?: '...' }
            window.addEventListener('message', function(event) {
                try {
                    if (!event || event.origin !== window.location.origin) {
                        return;
                    }

                    // Forward to parent when embedded (same-origin only)
                    if (window.parent && window.parent !== window) {
                        try {
                            window.parent.postMessage(event.data, window.location.origin);
                        } catch (e) {
                            // Ignore forwarding errors
                        }
                    }

                    var data = event.data || {};
                    var pickedUrl = data.url || (data.content && data.content.url) || null;
                    if (!pickedUrl) {
                        return;
                    }

                    // Preferred: cbId registry callback (reliable for plugin dialogs)
                    var cbId = data.cbId || null;
                    if (cbId && window.__chamiloTinyPickerCallbacks && typeof window.__chamiloTinyPickerCallbacks[cbId] === 'function') {
                        try {
                            window.__chamiloTinyPickerCallbacks[cbId](pickedUrl);
                        } finally {
                            delete window.__chamiloTinyPickerCallbacks[cbId];
                        }
                        return;
                    }

                    // Legacy fallback: single global callback
                    if (window.tinyMCECallback) {
                        window.tinyMCECallback(pickedUrl);
                        delete window.tinyMCECallback;
                    }
                } catch (e) {
                    // console.warn('[TinyMCE] File picker message bridge failed:', e);
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                // 2) Patch tinymce.init once to merge local config with the shared base config
                //    The shared base config is defined in /theme/<theme>/tiny-settings.js
                if (window.tinymce && !window.tinymce.__chamiloPatched) {
                    var originalInit = window.tinymce.init.bind(window.tinymce);
                    window.tinymce.__chamiloPatched = true;

                    window.tinymce.init = function(cfg) {
                        if (window.buildTinyMceConfig && typeof window.buildTinyMceConfig === 'function') {
                            try {
                                cfg = window.buildTinyMceConfig(cfg);
                            } catch (e) {
                                // console.warn('TinyMCE config merge failed:', e);
                            }
                        }
                        return originalInit(cfg);
                    };
                }

                // 3) Defer editor initialization until all legacy scripts are ready
                window.chEditors = window.chEditors || [];
                window.chEditors.push({$javascript});
            });
        </script>";
    }

    /**
     * @param array $templates
     */
    public function formatTemplates($templates): string
    {
        if (empty($templates)) {
            return '';
        }
        $templateList = [];
        $cssTheme = api_get_path(WEB_CSS_PATH).'themes/'.api_get_visual_theme().'/';
        $search = ['{CSS_THEME}', '{IMG_DIR}', '{REL_PATH}', '{COURSE_DIR}', '{CSS}'];
        $replace = [
            $cssTheme,
            api_get_path(REL_CODE_PATH).'img/',
            api_get_path(REL_PATH),
            '',
        ];

        /** @var SystemTemplate $template */
        foreach ($templates as $template) {
            $image = $template->getImage();
            $image = !empty($image) ? $image : 'empty.gif';

            $content = str_replace($search, $replace, $template->getContent());

            $templateList[] = [
                'title' => $this->translator->trans($template->getTitle()),
                'description' => $this->translator->trans($template->getComment()),
                'image' => $image,
                'html' => $content,
            ];
        }

        return json_encode($templateList);
    }

    /**
     * Get the templates in JSON format.
     *
     * @return false|string
     */
    public function simpleFormatTemplates()
    {
        $templates = $this->getEmptyTemplate();

        if (api_is_allowed_to_edit(false, true)) {
            $platformTemplates = $this->getPlatformTemplates();
            $templates = array_merge($templates, $platformTemplates);
        }

        $personalTemplates = $this->getPersonalTemplates();
        $templates = array_merge($templates, $personalTemplates);

        return json_encode($templates);
    }

    /**
     * Default image picker (uses a native file input and embeds as data URL).
     */
    private function getImagePicker(): string
    {
        return 'function (cb, value, meta) {
            var input = document.createElement("input");
            input.type = "file";
            input.accept = (meta && meta.filetype === "image") ? "image/*" : "*/*";
            input.onchange = function () {
                var file = this.files && this.files[0];
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function () {
                    var id = "blobid" + (new Date()).getTime();
                    var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                    var base64 = String(reader.result || "").split(",")[1];
                    var blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);
                    cb(blobInfo.blobUri(), { title: file.name });
                };
                reader.readAsDataURL(file);
            };
            input.click();
        }';
    }

    /**
     * Generates a JavaScript function for TinyMCE file manager picker (legacy).
     *
     * Behavior:
     *  - If a course context exists: use the Documents manager (/resources/document/{node}/manager)
     *  - Otherwise: use Personal files manager (/resources/filemanager/personal_list/{node})
     *  - Adds type filters: images|media|files
     *  - Uses cbId registry callback to reliably fill TinyMCE plugin inputs
     */
    private function getFileManagerPicker(bool $onlyPersonalfiles = false): string
    {
        $baseUrl = $this->getLegacyManagerBaseUrl($onlyPersonalfiles);

        if (empty($baseUrl)) {
            // Fail-safe: if a URL cannot be determined, fall back to the native picker
            return $this->getImagePicker();
        }

        $baseUrlJson = json_encode($baseUrl, JSON_UNESCAPED_SLASHES);

        return '
            function(cb, value, meta) {
                // Create a unique callback id (cbId) and store cb in a registry.
                var cbId;
                try {
                    cbId = (window.crypto && window.crypto.randomUUID) ? window.crypto.randomUUID() : null;
                } catch (e) {
                    cbId = null;
                }
                if (!cbId) {
                    cbId = "cb_" + Date.now() + "_" + Math.random().toString(16).slice(2);
                }

                window.__chamiloTinyPickerCallbacks = window.__chamiloTinyPickerCallbacks || {};
                window.__chamiloTinyPickerCallbacks[cbId] = function(pickedUrl) {
                    try {
                        cb(pickedUrl);
                    } finally {
                        try {
                            delete window.__chamiloTinyPickerCallbacks[cbId];
                        } catch (e) {
                            // Ignore
                        }
                    }
                };

                // Legacy fallback (postMessage bridge may still use this)
                window.tinyMCECallback = cb;

                var fileType = (meta && meta.filetype) ? String(meta.filetype).toLowerCase() : "file";
                var typeParam = "files";
                if (fileType === "image") {
                    typeParam = "images";
                } else if (fileType === "media") {
                    typeParam = "media";
                }

                var fileManagerUrl = '.$baseUrlJson.';

                // Append picker params safely
                try {
                    var u = new URL(fileManagerUrl, window.location.origin);
                    u.searchParams.set("type", typeParam);
                    u.searchParams.set("picker", "tinymce");
                    u.searchParams.set("cbId", cbId);
                    fileManagerUrl = u.toString();
                } catch (e) {
                    // Minimal fallback if URL() fails
                    var sep = (fileManagerUrl.indexOf("?") === -1) ? "?" : "&";
                    fileManagerUrl = fileManagerUrl + sep
                        + "type=" + encodeURIComponent(typeParam)
                        + "&picker=tinymce"
                        + "&cbId=" + encodeURIComponent(cbId);
                }

                tinymce.activeEditor.windowManager.openUrl({
                    title: "File Manager",
                    url: fileManagerUrl,
                    width: 980,
                    height: 600,
                    onMessage: function(api, message) {
                        try {
                            var data = message || {};
                            var pickedUrl = (data.content && data.content.url) || data.url || null;
                            if (!pickedUrl) return;

                            if (window.__chamiloTinyPickerCallbacks && typeof window.__chamiloTinyPickerCallbacks[cbId] === "function") {
                                window.__chamiloTinyPickerCallbacks[cbId](pickedUrl);
                                delete window.__chamiloTinyPickerCallbacks[cbId];
                            } else if (window.tinyMCECallback) {
                                window.tinyMCECallback(pickedUrl);
                                delete window.tinyMCECallback;
                            }

                            if (api && typeof api.close === "function") {
                                api.close();
                            }
                        } catch (e) {
                            // console.warn("[TinyMCE] onMessage handler failed:", e);
                        }
                    }
                });
            }
        ';
    }

    /**
     * Resolve the correct legacy manager URL based on context.
     * - Course context => Documents manager
     * - Otherwise => Personal files manager.
     */
    private function getLegacyManagerBaseUrl(bool $onlyPersonalfiles): ?string
    {
        $user = api_get_user_entity();
        $course = api_get_course_entity();

        // Prefer Documents manager when in a course (unless explicitly forced to personal files)
        if (!$onlyPersonalfiles && null !== $course) {
            $resourceNodeId = $course->getResourceNode()->getId();

            // Example produced URL:
            // /resources/document/{node}/manager?cid=...&sid=...&gid=...
            return api_get_path(WEB_PATH).'resources/document/'.$resourceNodeId.'/manager?'.api_get_cidreq();
        }

        if (null !== $user) {
            $resourceNodeId = $user->getResourceNode()->getId();
            $url = api_get_path(WEB_PATH).'resources/filemanager/personal_list/'.$resourceNodeId.'?loadNode=1';

            // If a course exists and caller forced personal manager, keep cidreq available for context (safe no-op if ignored)
            if (null !== $course) {
                $url .= '&'.api_get_cidreq();
            }

            return $url;
        }

        return null;
    }

    /**
     * Get the empty template.
     */
    private function getEmptyTemplate(): array
    {
        return [
            [
                'title' => get_lang('Blank template'),
                'description' => null,
                'image' => api_get_path(WEB_PUBLIC_PATH).'img/template_thumb/empty.gif',
                'html' => '
                <!DOCTYPE html>
                <html>
                    <head>
                        <meta charset="'.api_get_system_encoding().'" />
                    </head>
                    <body dir="'.api_get_text_direction().'">
                        <p><br/></p>
                    </body>
                </html>
            ',
            ],
        ];
    }

    /**
     * Get the platform templates.
     */
    private function getPlatformTemplates(): array
    {
        $entityManager = Database::getManager();
        $systemTemplates = $entityManager->getRepository(SystemTemplate::class)->findAll();
        $cssTheme = api_get_path(WEB_CSS_PATH).'themes/'.api_get_visual_theme().'/';
        $search = ['{CSS_THEME}', '{IMG_DIR}', '{REL_PATH}', '{COURSE_DIR}', '{CSS}'];
        $replace = [
            $cssTheme,
            api_get_path(REL_PATH).'public/img/',
            api_get_path(REL_PATH),
            api_get_path(REL_PATH).'public/img/document/',
            '',
        ];

        $templateList = [];

        foreach ($systemTemplates as $template) {
            $image = $template->getImage();
            $image = !empty($image) ? $image : 'empty.gif';
            $image = api_get_path(WEB_PUBLIC_PATH).'img/template_thumb/'.$image;
            $templateContent = $template->getContent();
            $content = str_replace($search, $replace, $templateContent);

            $templateList[] = [
                'title' => get_lang($template->getTitle()),
                'description' => get_lang($template->getComment()),
                'image' => $image,
                'html' => $content,
            ];
        }

        return $templateList;
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    private function getPersonalTemplates($userId = 0)
    {
        if (empty($userId)) {
            $userId = api_get_user_id();
        }

        $entityManager = Database::getManager();
        $templatesRepo = $entityManager->getRepository(Templates::class);
        $user = api_get_user_entity($userId);
        $course = $entityManager->find(Course::class, api_get_course_int_id());

        if (!$user || !$course) {
            return [];
        }

        $courseTemplates = $templatesRepo->getCourseTemplates($course, $user);
        $templateList = [];

        foreach ($courseTemplates as $templateData) {
            $template = $templateData[0];

            $templateItem = [];
            $templateItem['title'] = $template->getTitle();
            $templateItem['description'] = $template->getDescription();
            $templateItem['image'] = api_get_path(WEB_PUBLIC_PATH).'img/template_thumb/noimage.gif';

            $image = $template->getImage();
            if (!empty($image)) {
                // If you have a course dir resolver, replace the following with your real path builder.
                // $templateItem['image'] = api_get_path(WEB_COURSE_PATH) . $courseDirectory . '/upload/template_thumbnails/' . $template->getImage();
            }

            $templateList[] = $templateItem;
        }

        return $templateList;
    }
}
