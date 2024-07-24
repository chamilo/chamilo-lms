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
        $html = '<textarea id="'.$this->getTextareaId().'" name="'.$this->getName().'" >
                 '.$value.'
                 </textarea>';
        $html .= $this->editorReplace();

        return $html;
    }

    /**
     * Return the HTML code required to run editor.
     *
     * @param string $value
     */
    public function createHtmlStyle($value): string
    {
        $style = '';
        $value = trim($value);

        if ('' === $value || '<html><head><title></title></head><body></body></html>' === $value) {
            $style = api_get_bootstrap_and_font_awesome();
            $style .= Container::getThemeHelper()->getThemeAssetLinkTag('document.css');
        }

        $html = '<textarea id="'.$this->getTextareaId().'" name="'.$this->getName().'" >
                 '.$style.$value.'
                 </textarea>';
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
        $javascript = $this->toJavascript($config);

        // it replaces [browser] by image picker callback
        $javascript = str_replace('"[browser]"', $this->getFileManagerPicker(), $javascript);

        return "<script>
            window.addEventListener('message', function(event) {
                // Check if the received message contains the URL data
                if (event.data.url) {
                    // Check if we are in an iframe
                    if (window.parent !== window) {
                        // Send the message to the parent window
                        window.parent.postMessage(event.data, '*');
                        // Access the callback function in the parent window
                        const parentWindow = window.parent.window[0].window;
                        if (parentWindow && parentWindow.tinyMCECallback) {
                            parentWindow.tinyMCECallback(event.data.url);
                            delete parentWindow.tinyMCECallback;
                        }
                    } else if (window.tinyMCECallback) {
                        // Handle the message in the main context
                        window.tinyMCECallback(event.data.url);
                        delete window.tinyMCECallback;
                    }
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                window.chEditors = window.chEditors || [];
                window.chEditors.push($javascript)
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

            /*$image = $this->urlGenerator->generate(
                'get_document_template_action',
                array('file' => $image),
                UrlGenerator::ABSOLUTE_URL
            );*/

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
     * Get a custom image picker.
     */
    private function getImagePicker(): string
    {
        return 'function (cb, value, meta) {
            var input = document.createElement("input");
            input.setAttribute("type", "file");
            input.setAttribute("accept", "image/*");
            input.onchange = function () {
                var file = this.files[0];
                var reader = new FileReader();
                reader.onload = function () {
                    var id = "blobid" + (new Date()).getTime();
                    var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                    var base64 = reader.result.split(",")[1];
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
     * @return string javaScript function as string
     */
    private function getFileManagerPicker($onlyPersonalfiles = true): string
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
            return $this->getImagePicker();
        }

        return '
            function(cb, value, meta) {
                window.tinyMCECallback = cb;
                let fileType = meta.filetype;
                let fileManagerUrl = "'.$url.'";

                if (fileType === "image") {
                    fileManagerUrl += "&type=images";
                } else if (fileType === "file") {
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
                <!DOCYTPE html>
                <html>
                    <head>
                        <meta charset="'.api_get_system_encoding().'" />
                    </head>
                    <body  dir="'.api_get_text_direction().'">
                        <p>
                            <br/>
                        </p>
                    </body>
                    </html>
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
            /*$templateItem['html'] = file_get_contents(api_get_path(SYS_COURSE_PATH)
                .$courseDirectory.'/document'.$templateData['path']);*/

            $image = $template->getImage();
            if (!empty($image)) {
                $templateItem['image'] = api_get_path(WEB_COURSE_PATH)
                    .$courseDirectory.'/upload/template_thumbnails/'.$template->getImage();
            }

            $templateList[] = $templateItem;
        }

        return $templateList;
    }
}
