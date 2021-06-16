<?php

/* For licensing terms, see /license.txt */

/**
 * Class LpIndexGenerator.
 */
class LpIndexGenerator
{
    /**
     * @var learnpath
     */
    private $lp;
    /**
     * @var array
     */
    private $courseInfo;
    /**
     * @var DOMDocument
     */
    private $domDocument;

    public function __construct(learnpath $lp)
    {
        $this->lp = $lp;
        $this->courseInfo = api_get_course_info();
        $this->domDocument = new DOMDocument();

        $this->generateHtml();
    }

    public function generate(): string
    {
        $this->generateToc();

        $indexHtml = @$this->domDocument->saveHTML();

        return api_utf8_decode_xml($indexHtml);
    }

    private function generateHtml()
    {
        $iso = api_get_language_isocode();
        $title = api_utf8_encode($this->lp->get_name());

        $this->domDocument->loadHTML(
            '<!doctype html>
            <html lang="'.$iso.'">
            <head>
            <meta charset="UTF-8">
            <meta name="viewport"
                  content="width=device-width,user-scalable=no,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0">
            <meta http-equiv="X-UA-Compatible" content="ie=edge">
            <title>'.$title.'</title>
            <link rel="stylesheet" href="assets/bootstrap/bootstrap.min.css">
            <style>
                .page-header { margin-top: 0; padding-top: 10px; }
                #toc__ul { height: calc(100vh - 80px - 15px); overflow: auto; }
            </style>
            </head>
            <body>
            <div class="container-fluid">
                <h1 class="page-header">'.$title.'</h1>
                <div class="row">
                    <div class="col-md-3">
                        <ul id="toc__ul"></ul>
                    </div>
                    <div class="col-md-9">
                        <div class="embed-responsive embed-responsive-16by9">
                            <iframe class="embed-responsive-item" id="content__iframe" name="content-frame"
                                    src="" frameborder="0"></iframe>
                        </div>
                    </div>
                </div>
            </div>
            </body>
            </html>'
        );
    }

    private function generateToc()
    {
        $ulNode = $this->domDocument->getElementById('toc__ul');

        $folderName = 'document';
        $pathToRemove = '';
        $pathToReplace = '';
        $result = $this->lp->generate_lp_folder($this->courseInfo);

        if (isset($result['dir']) && strpos($result['dir'], 'learning_path')) {
            $pathToRemove = 'document'.$result['dir'];
            $pathToReplace = $folderName;
        }

        if ($this->lp->ref === 'chamilo_scorm_export') {
            $pathToRemove = 'scorm/'.$this->lp->path.'/document/';
        }

        foreach ($this->lp->ordered_items as $itemId) {
            $item = $this->lp->items[$itemId];

            if (!in_array($item->type, [TOOL_QUIZ, TOOL_FORUM, TOOL_THREAD, TOOL_LINK, TOOL_STUDENTPUBLICATION])) {
                $myFilePath = $item->get_file_path('scorm/'.$this->lp->path.'/');
                $itemFilePath = $myFilePath;

                if (!empty($pathToRemove)) {
                    $itemFilePath = str_replace($pathToRemove, $pathToReplace, $myFilePath);

                    if ($this->lp->ref === 'chamilo_scorm_export') {
                        $pathToRemove = 'scorm/'.$this->lp->path.'/';
                        $itemFilePath = 'document/'.str_replace($pathToRemove, '', $myFilePath);
                    }
                }
            } elseif (TOOL_LINK === $item->type) {
                $itemFilePath = "link_{$item->get_id()}.html";
            } elseif (TOOL_QUIZ === $item->type) {
                $itemFilePath = "quiz_{$item->get_id()}.html";
            } else {
                continue;
            }

            $itemText = htmlspecialchars(api_utf8_encode($item->get_title()), ENT_QUOTES);

            $liNode = $this->domDocument->createElement('li');
            $liNode->setAttribute('id', "item_{$item->get_id()}");

            if (!empty($item->parent) && $item->parent != 0) {
                $possibleItemParent = $this->lp->get_scorm_xml_node(
                    $ulNode->childNodes,
                    "item_$item->parent",
                    'li',
                    'id'
                );

                if ($possibleItemParent instanceof DOMElement) {
                    $innerUlNode = $possibleItemParent->getElementsByTagName('ul')->item(0)
                        ?: $this->domDocument->createElement('ul');
                    $innerUlNode->appendChild($liNode);

                    $possibleItemParent->appendChild($innerUlNode);
                }
            } else {
                $ulNode->appendChild($liNode);
            }

            if (!empty($itemFilePath) && $itemFilePath !== 'document/') {
                $aNode = $this->domDocument->createElement('a', $itemText);
                $aNode->setAttribute('href', $itemFilePath);
                $aNode->setAttribute('target', 'content-frame');

                $liNode->appendChild($aNode);
            } else {
                $liNode->appendChild(
                    $this->domDocument->createTextNode($itemText)
                );
            }
        }
    }
}
