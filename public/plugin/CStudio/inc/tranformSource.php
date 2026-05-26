
<?php

    function getSrcForEditor($srcFlx)
    {
        // $srcFlx = preg_replace("#<div class=\"editRapidIcon\">(.*?)</div>#is","", $srcFlx);
        // $srcFlx = preg_replace("#<div class=\"editRapidIcon reload\">(.*?)</div>#is","", $srcFlx);
        // $srcFlx = preg_replace('#<div\b[^>]+\bclass\s*=\s*[\'\"]editRapidIcon[\'\"][^>]*>([\s\S]*?)</div>#', ' ', $srcFlx);
        // $cssI = " style='position:absolute;cursor:pointer;background-image:url(\"img/editdoc.png\");background-position:center center;background-repeat:no-repeat;right:2px;top:3px;width:50px;height:50px;z-index: 1000;' ";
        // $editBtn = '<div class="editRapidIcon" onClick="parent.displayVideoEdit(this);" '.$cssI.' ></div><video ';
        // $srcFlx = str_replace ("<video ",$editBtn,$srcFlx);

        $srcFlx = remove_html_comments($srcFlx);

        return remove_img64($srcFlx);
    }

function remove_html_comments($content)
{
    if (!str_contains($content, '<!--')) {
        return $content;
    }

    return preg_replace('/<!--(.|\s)*?-->/', '', $content);
}

function remove_img64($content)
{
    $errorImageUrl = 'img/error.jpg';

    return replaceImagesWithError($content, $errorImageUrl);
}

function replaceImagesWithError($base_html, $errorImageUrl)
{
    // detect have svg in src on img
    $matches = [];
    preg_match_all('/src="([^"]+)/i', $base_html, $matches);

    $haveImgProblem = false;
    for ($i = 0; $i < count($matches[0]); $i++) {
        $cleanSrc = $matches[0][$i];
        $cleanSrc = str_replace('src="', '', $cleanSrc);

        if (str_contains($cleanSrc, 'data:image/png;base64')) {
            $haveImgProblem = true;
        }
        if (str_contains($cleanSrc, '<svg')) {
            $haveImgProblem = true;
        }
        if (str_contains($cleanSrc, '.w3.org/2000/svg')) {
            $haveImgProblem = true;
        }
    }

    if ($haveImgProblem) {
        // echo "haveImgProblem";

        $base_html = str_replace(["\r", "\n"], '', $base_html);

        $base_html = str_replace('path>  </svg', 'path> </svg', $base_html); // 2
        $base_html = str_replace('path> </svg', 'path></svg', $base_html); // 1

        $base_html = str_replace('quot;>  <path', 'quot;> <path', $base_html);
        $base_html = str_replace('quot;> <path', 'quot;><path', $base_html);

        $matches = [];
        preg_match_all('/src="([^"]+)/i', $base_html, $matches);

        for ($i = 0; $i < count($matches[0]); $i++) {
            $cleanSrc = $matches[0][$i];
            $cleanSrc = str_replace('src="', '', $cleanSrc);
            $haveImgProblem2 = false;
            if (str_contains($cleanSrc, 'data:image/png;base64')) {
                $haveImgProblem2 = true;
            }
            if (str_contains($cleanSrc, '<svg')) {
                $haveImgProblem2 = true;
            }
            if (str_contains($cleanSrc, '.w3.org/2000/svg')) {
                $haveImgProblem2 = true;
            }

            // echo '<span style="color:red;" > *** '.htmlspecialchars($cleanSrc).'</span><br>';
            if (true == $haveImgProblem2) {
                $modifiedHtml = str_replace($cleanSrc, $errorImageUrl, $base_html);
                $modifiedHtml = str_replace($cleanSrc, $errorImageUrl, $modifiedHtml);
                $base_html = $modifiedHtml;
            }
        }
    }

    return $base_html;
}

function getSrcForSave($srcFlx)
{
    // dhcondi
    $srcFlx = preg_replace('#<div class="editRapidIcon">(.*?)</div>#is', '', $srcFlx);
    $srcFlx = preg_replace('#<div class="editRapidIcon reload">(.*?)</div>#is', '', $srcFlx);

    return preg_replace('#<div\b[^>]+\bclass\s*=\s*[\'\"]editRapidIcon[\'\"][^>]*>([\s\S]*?)</div>#', ' ', $srcFlx);
}

function getSrcForPrint($srcFlx)
{
    $srcFlx = preg_replace('#<div class="editRapidIcon">(.*?)</div>#is', '', $srcFlx);
    $srcFlx = preg_replace('#<div class="editRapidIcon reload">(.*?)</div>#is', '', $srcFlx);
    $srcFlx = preg_replace('#<div\b[^>]+\bclass\s*=\s*[\'\"]editRapidIcon[\'\"][^>]*>([\s\S]*?)</div>#', ' ', $srcFlx);

    $srcFlx = preg_replace('#<div class="datatext2(.*?)</div>#is', '', $srcFlx);
    $srcFlx = preg_replace('#<div class="datatext3(.*?)</div>#is', '', $srcFlx);
    $srcFlx = preg_replace('#<div class="datatext4(.*?)</div>#is', '', $srcFlx);
    $srcFlx = preg_replace('#<div class="datatext5(.*?)</div>#is', '', $srcFlx);

    $srcFlx = preg_replace('#<span class="datatext2(.*?)</span>#is', '', $srcFlx);
    $srcFlx = preg_replace('#<span class="datatext3(.*?)</span>#is', '', $srcFlx);
    $srcFlx = preg_replace('#<span class="datatext4(.*?)</span>#is', '', $srcFlx);
    $srcFlx = preg_replace('#<span class="datatext5(.*?)</span>#is', '', $srcFlx);

    $srcFlx = preg_replace('#<video (.*?)</video>#is', '', $srcFlx);

    $srcFlx = str_replace('onmousedown="parent.displayEditButon(this);"', '', $srcFlx);
    $srcFlx = str_replace('oncontextmenu="return false;"', '', $srcFlx);
    // onmousedown="parent.displayEditButon(this);"
    // oncontextmenu="return false;"
    // onmousedown="parent.displayEditButon(this);"

    return preg_replace('#<div class="editRapidIcon reload">(.*?)</div>#is', '', $srcFlx);
}

function cleanCssForEdit($srcFlx)
{
    $srcFlx = str_replace('.oelcardinfo p:first-of-type', '.obsolete', $srcFlx);
    $srcFlx = str_replace('.oelcard', '.obsoleteoelcard', $srcFlx);
    $srcFlx = str_replace('height:0px;', '', $srcFlx);
    $srcFlx = str_replace('.qcmbarre .quizzTextqcm{', '.quizzTextqcmobsolete{', $srcFlx);

    return str_replace('.qcmbarre .quizzTextqcm {', '.quizzTextqcmobsolete{', $srcFlx);
}

function preventImg64($srcFlx)
{
    // <img src="data:image/png;filename=18483112.png;base64,iVBORw0KGgoAAAANSUhEUg
    $srcFlx = str_replace('<img src="data:image/png;filename', '<div style="width:50px;height:50px;border:red 2px solid;" src="error ', $srcFlx);
    $srcFlx = str_replace('<img src="data:image/png;,', '<div style="width:50px;height:50px;border:red 2px solid;" src="error ', $srcFlx);
    $srcFlx = str_replace('<img src="data:image/png;base64,', '<div style="width:50px;height:50px;border:red 2px solid;" src="error ', $srcFlx);
    $srcFlx = str_replace('data:image/png;base64,', 'error ', $srcFlx);

    return str_replace('data:image/png;,', 'error ', $srcFlx);
}

function removeTagByClass(string $html, string $className)
{
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $finder = new DOMXPath($dom);

    $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' {$className} ')]");

    foreach ($nodes as $node) {
        $node->parentNode->removeChild($node);
    }

    return $dom->saveHTML();
}
