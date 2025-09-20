<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\CkEditor;

use Chamilo\CoreBundle\Component\Editor\Editor;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\SystemTemplate;
use Chamilo\CoreBundle\Entity\Templates;
use Chamilo\CoreBundle\Framework\Container;
use Database;

class CkEditor extends Editor
{
    /**
     * Return the HTML code required to run editor.
     *
     * @param string $value
     */
    public function createHtml($value): string
    {
        $html = '<textarea id="'.$this->getTextareaId().'" name="'.$this->getName().'" >'
            . $value
            . '</textarea>';
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
            $style  = api_get_bootstrap_and_font_awesome();
            $style .= Container::getThemeHelper()->getThemeAssetLinkTag('document.css');
        }

        $html = '<textarea id="'.$this->getTextareaId().'" name="'.$this->getName().'" >'
            . $style.$value
            . '</textarea>';
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

        // Inject script that:
        //  1) Bridges postMessage -> callback for the File Manager
        //  2) Monkey-patches tinymce.init to always merge with window.buildTinyMceConfig (from tiny-settings.js)
        //  3) Queues the generated TinyMCE init script into window.chEditors for deferred execution
        return "<script>
            // 1) Cross-frame bridge: accept file URL messages from the File Manager
            window.addEventListener('message', function(event) {
                if (event && event.data && event.data.url) {
                    if (window.parent !== window) {
                        // Forward to parent if inside an iframe
                        window.parent.postMessage(event.data, '*');
                        const parentWindow = (window.parent && window.parent.window && window.parent.window[0]) ? window.parent.window[0].window : null;
                        if (parentWindow && parentWindow.tinyMCECallback) {
                            parentWindow.tinyMCECallback(event.data.url);
                            delete parentWindow.tinyMCECallback;
                        }
                    } else if (window.tinyMCECallback) {
                        // Handle directly if on the main window
                        window.tinyMCECallback(event.data.url);
                        delete window.tinyMCECallback;
                    }
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                // 2) Patch tinymce.init once to merge local config with the shared base config
                //    The shared base config is defined in /theme/<theme>/tiny-settings.js
                if (window.tinymce && !window.tinymce.__chamiloPatched) {
                    const originalInit = window.tinymce.init.bind(window.tinymce);
                    window.tinymce.__chamiloPatched = true;
                    window.tinymce.init = function(cfg) {
                        // If the shared builder exists, merge base + local configs
                        if (window.buildTinyMceConfig && typeof window.buildTinyMceConfig === 'function') {
                            try {
                                cfg = window.buildTinyMceConfig(cfg);
                            } catch (e) {
                                // Fail-safe: if merge fails, fall back to the given cfg
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
            // api_get_path(REL_DEFAULT_COURSE_DOCUMENT_PATH),
            '',
        ];

        /** @var SystemTemplate $template */
        foreach ($templates as $template) {
            $image = $template->getImage();
            $image = !empty($image) ? $image : 'empty.gif';

            // If generating absolute URLs is needed, use the router (commented out example)
            /*
            $image = $this->urlGenerator->generate(
                'get_document_template_action',
                ['file' => $image],
                UrlGenerator::ABSOLUTE_URL
            );
            */

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
     * Generates a JavaScript function for TinyMCE file manager picker.
     *
     * @param bool $onlyPersonalfiles if true, only shows personal files
     *
     * @return string JavaScript function as a string
     */
    private function getFileManagerPicker(bool $onlyPersonalfiles = true): string
    {
        $user = api_get_user_entity();
        $course = api_get_course_entity();

        if ($onlyPersonalfiles) {
            if (null !== $user) {
                $cidReqQuery = '';
                if (null !== $course) {
                    $parentResourceNodeId = $course->getResourceNode()->getId();
                    $cidReqQuery = '&'.api_get_cidreq().'&parentResourceNodeId='.$parentResourceNodeId;
                }
                $resourceNodeId = $user->getResourceNode()->getId();
                $url = api_get_path(WEB_PATH).'resources/filemanager/personal_list/'.$resourceNodeId.'?loadNode=1'.$cidReqQuery;
            }
        } else {
            if (null !== $course) {
                $resourceNodeId = $course->getResourceNode()->getId();
                $url = api_get_path(WEB_PATH).'resources/document/'.$resourceNodeId.'/manager?'.api_get_cidreq();
            } elseif (null !== $user) {
                $resourceNodeId = $user->getResourceNode()->getId();
                $url = api_get_path(WEB_PATH).'resources/filemanager/personal_list/'.$resourceNodeId.'?loadNode=1';
            }
        }

        if (!isset($url)) {
            // Fallback to simple native image picker if a URL cannot be determined
            return $this->getImagePicker();
        }

        return '
            function(cb, value, meta) {
                // Store callback to be used by the postMessage bridge
                window.tinyMCECallback = cb;
                var fileType = (meta && meta.filetype) ? meta.filetype : "file";
                var fileManagerUrl = "'.$url.'";

                if (fileType === "image") {
                    fileManagerUrl += "&type=images";
                } else {
                    fileManagerUrl += "&type=files";
                }

                tinymce.activeEditor.windowManager.openUrl({
                    title: "File Manager",
                    url: fileManagerUrl,
                    width: 980,
                    height: 600
                });
            }
        ';
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

            // If you want to include HTML content from course documents, implement a safe resolver here.
            // (Left out intentionally to avoid file system coupling in this context.)

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
