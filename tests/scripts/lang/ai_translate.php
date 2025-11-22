<?php

/* For licensing terms, see /license.txt */

// This script completes translations for Chamilo LMS using the Grok API.
// Run from tests/scripts/lang/ with access to ../../../translations/
// Requires curl and JSON extensions.
// Set XAI_API_KEY environment variable with your Grok API key.
// Usage: php grok_translate.php [lang1] [lang2] ...
// If no languages provided, processes all messages.*.po except messages.en.po

exit;
$api_key = getenv('XAI_API_KEY');
if (!$api_key) {
    die("Error: XAI_API_KEY environment variable not set.\n");
}

$trans_dir = '../../../translations/';
$pot_file = $trans_dir.'messages.pot';
$en_file = $trans_dir.'messages.en.po';
$log_file = 'translation.log';

$log_handle = fopen($log_file, 'a');
if (!$log_handle) {
    die("Error: Could not open log file.\n");
}

$languages = array_slice($argv, 1);
if (empty($languages)) {
    // Scan for all messages.*.po files except en
    $po_files = glob($trans_dir.'messages.*.po');
    foreach ($po_files as $po_file) {
        $basename = basename($po_file);
        if ($basename === 'messages.en.po') {
            continue;
        }
        $lang = substr($basename, strlen('messages.'), -strlen('.po'));
        $languages[] = $lang;
    }
}

// Load English translations once
$en_data = parse_po($en_file);
$en_trans = $en_data['translations'];

// Load POT msgids for order
$msgids = parse_pot_msgids($pot_file);
$total_terms = count($msgids);

foreach ($languages as $lang) {
    $target_file = $trans_dir."messages.$lang.po";
    if (!file_exists($target_file)) {
        echo "Warning: File $target_file not found. Skipping.\n";
        continue;
    }

    fwrite($log_handle, "\nLanguage: $lang\n");

    $target_data = parse_po($target_file);
    $headers = $target_data['headers'];
    $target_trans = &$target_data['translations'];

    $need_translate = [];
    $index = 0;

    foreach ($msgids as $msgid) {
        $index++;
        $source = $en_trans[$msgid] ?? '';
        $current = $target_trans[$msgid] ?? '';

        $action = 'kept_existing';
        $new_current = $current;

        if ($current === '') {
            $action = 'translate_new';
            $new_current = ''; // Will be translated
        } else {
            $words = str_word_count($current);
            if ($words > 2 && $current === $source) {
                $action = 'retranslate_lazy';
                $new_current = '';
            } elseif ($words > 20) {
                // Simple check for fallback to English: if last half matches source's last half
                $half_len = floor(strlen($current) / 2);
                $current_last = substr($current, -$half_len);
                $source_last = substr($source, -$half_len);
                if ($current_last === $source_last) {
                    $action = 'retranslate_partial';
                    $new_current = '';
                }
            }
        }

        if ($new_current === '') {
            $need_translate[] = ['index' => $index, 'msgid' => $msgid, 'source' => $source];
        } else {
            fwrite($log_handle, "Term $index/$total_terms: $msgid - action: $action\n");
        }

        if ($index % 50 === 0 || $index === $total_terms) {
            echo "Progress for $lang: $index / $total_terms\n";
        }
    }

    // Batch translate needed terms
    $batch_size = 50; // Optimized batch size to balance context and API limits
    $batches = array_chunk($need_translate, $batch_size);

    $lang_name = get_lang_name($lang); // e.g., French, Spanish

    foreach ($batches as $batch_num => $batch) {
        $prompt = "You are an expert translator for Chamilo, an Open Source web Learning Management System. Translate the following English strings to $lang_name. Use natural, accurate language. If unsure about the best translation or if context is ambiguous, leave it as an empty string. Do not use English fallback.\n\n";
        $prompt .= "Strings:\n";
        foreach ($batch as $i => $item) {
            $prompt .= ($i + 1).". \"{$item['source']}\"\n";
        }
        $prompt .= "\nProvide only the numbered list of translations, each as a quoted string or empty string if unsure. Example:\n1. \"Translated text\"\n2. \"\"\n";

        $response = call_grok_api($prompt, $api_key);
        $trans_list = parse_api_response($response);

        foreach ($batch as $i => $item) {
            $new_msgstr = $trans_list[$i] ?? '';
            $target_trans[$item['msgid']] = $new_msgstr;
            $action = $new_msgstr !== '' ? 'translated' : 'left_empty_unsure';
            fwrite($log_handle, "Term {$item['index']}/$total_terms: {$item['msgid']} - action: $action\n");
        }

        // Sleep to respect potential rate limits (adjust based on your tier)
        sleep(1);
    }

    // Write updated PO file
    write_po($target_file, $headers, $msgids, $target_trans);

    echo "Completed $lang. Log updated.\n";
}

fclose($log_handle);

function parse_pot_msgids($file)
{
    $msgids = [];
    $lines = file($file);
    $current_msgid = '';
    $in_msgid = false;
    foreach ($lines as $line) {
        $trim = trim($line);
        if (strpos($trim, 'msgid ') === 0) {
            if ($current_msgid !== '' && $current_msgid !== "\n") { // Skip empty/header
                $msgids[] = rtrim($current_msgid, "\n");
            }
            $in_msgid = true;
            $current_msgid = trim(substr($trim, strlen('msgid ')), '"')."\n";
        } elseif ($in_msgid && strpos($trim, '"') === 0) {
            $current_msgid .= trim($trim, '"')."\n";
        } elseif (strpos($trim, 'msgstr ') === 0) {
            $in_msgid = false;
        }
    }
    if ($current_msgid !== '' && $current_msgid !== "\n") {
        $msgids[] = rtrim($current_msgid, "\n");
    }
    return $msgids;
}

function parse_po($file)
{
    $headers = '';
    $translations = [];
    $lines = file($file);
    $current_msgid = '';
    $current_msgstr = '';
    $in_msgid = false;
    $in_msgstr = false;
    foreach ($lines as $line) {
        $trim = trim($line);
        if (strpos($trim, 'msgid ') === 0) {
            if ($current_msgid !== '') {
                $translations[rtrim($current_msgid, "\n")] = rtrim($current_msgstr, "\n");
            } elseif ($current_msgstr !== '') {
                $headers = rtrim($current_msgstr, "\n");
            }
            $in_msgid = true;
            $in_msgstr = false;
            $current_msgid = trim(substr($trim, strlen('msgid ')), '"')."\n";
            $current_msgstr = '';
        } elseif (strpos($trim, 'msgstr ') === 0) {
            $in_msgid = false;
            $in_msgstr = true;
            $current_msgstr = trim(substr($trim, strlen('msgstr ')), '"')."\n";
        } elseif ($in_msgid && strpos($trim, '"') === 0) {
            $current_msgid .= trim($trim, '"')."\n";
        } elseif ($in_msgstr && strpos($trim, '"') === 0) {
            $current_msgstr .= trim($trim, '"')."\n";
        }
    }
    if ($current_msgid !== '') {
        $translations[rtrim($current_msgid, "\n")] = rtrim($current_msgstr, "\n");
    } elseif ($current_msgstr !== '') {
        $headers = rtrim($current_msgstr, "\n");
    }
    return ['headers' => $headers, 'translations' => $translations];
}

function write_po($file, $headers, $msgids, $translations)
{
    $content = "msgid \"\"\nmsgstr \"\"\n";
    $header_lines = explode("\n", $headers);
    foreach ($header_lines as $h_line) {
        $content .= '"'.str_replace('"', '\"', $h_line)."\n\"\n";
    }
    $content .= "\n";

    foreach ($msgids as $msgid) {
        $msgstr = $translations[$msgid] ?? '';
        $content .= "msgid \"\"\n";
        $msgid_lines = explode("\n", $msgid);
        foreach ($msgid_lines as $m_line) {
            if ($m_line === '') {
                continue;
            }
            $content .= '"'.str_replace('"', '\"', $m_line)."\n\"\n";
        }
        $content .= "msgstr \"\"\n";
        $msgstr_lines = explode("\n", $msgstr);
        foreach ($msgstr_lines as $s_line) {
            if ($s_line === '') {
                continue;
            }
            $content .= '"'.str_replace('"', '\"', $s_line)."\n\"\n";
        }
        $content .= "\n";
    }

    file_put_contents($file, $content);
}

function get_lang_name($lang)
{
    $map = [
        'fr_FR' => 'French',
        'es' => 'Spanish',
        // Add more if needed for other languages
    ];
    return $map[$lang] ?? ucfirst($lang);
}

function call_grok_api($prompt, $api_key)
{
    $url = 'https://api.x.ai/v1/chat/completions';
    $data = [
        'model' => 'grok-beta', // Optimized choice: reliable for translations, balance cost/quality
        'messages' => [
            ['role' => 'system', 'content' => 'You are a precise translator. Follow instructions exactly.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.1, // Low for consistent translations
        'max_tokens' => 4096, // Sufficient for batch
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer '.$api_key,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    return $json['choices'][0]['message']['content'] ?? '';
}

function parse_api_response($content)
{
    $trans_list = [];
    $lines = explode("\n", trim($content));
    foreach ($lines as $line) {
        if (preg_match('/^(\d+)\.\s*"(.*)"\s*$/', $line, $matches)) {
            $num = (int) $matches[1];
            $trans = $matches[2];
            // Unescape basic \n etc. if needed, but assume clean
            $trans_list[$num - 1] = str_replace('\"', '"', $trans); // Basic unescape
        }
    }
    return $trans_list;
}
