<?php

declare(strict_types=1);

require_once __DIR__.'/tranformSource.php';

$base_html = file_get_contents('test-fail.html');
$base_html = getSrcForEditor($base_html);

echo htmlspecialchars($base_html);

echo '<hr>';

print_r($base_html);

echo '<hr>';

$base_html = str_replace(["\r", "\n"], '', $base_html);
$base_html = str_replace('path>  </svg', 'path> </svg', $base_html); // 2
$base_html = str_replace('path> </svg', 'path></svg', $base_html); // 1

// quot;> <path
$base_html = str_replace('quot;>  <path', 'quot;> <path', $base_html);
$base_html = str_replace('quot;> <path', 'quot;><path', $base_html);

$fp = fopen('test_out.html', 'w');
fwrite($fp, $base_html);
fclose($fp);

// replace string "path> </svg" by "path></svg" in php
// https://stackoverflow.com/questions/10633609/replace-string-path-svg-by-path-svg-in-php

$matches = [];
preg_match_all('/src="([^"]+)/i', $base_html, $matches);
for ($i = 0; $i < count($matches[0]); $i++) {
    $cleanSrc = $matches[0][$i];
    $cleanSrc = str_replace('src="', '', $cleanSrc);
    $errorImageUrl = 'img/error.jpg';
    echo ' * '.htmlspecialchars($cleanSrc).'<br>';

    // delete all line breaks

    $modifiedHtml = str_replace($cleanSrc, $errorImageUrl, $base_html);
    $modifiedHtml = str_replace($cleanSrc, $errorImageUrl, $modifiedHtml);
}

echo '<hr>';

echo htmlspecialchars($modifiedHtml);

echo '<hr>';
