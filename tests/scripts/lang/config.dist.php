<?php
// Configuration settings for scripts in this folder.
// Copy this file to config.php and fill in your API key before using the scripts.
$translationAPIKey = '{your_api_key}';
$translationAPIEndpoint = 'https://api.x.ai/v1/chat/completions';
// Base (source) language and file
$translationSourceLanguageCode = 'en_US';
// grok-4.6 is fine for UI translation quality. Do NOT leave reasoning at the
// API default ("high"): that spends ~30–45s thinking before the first token,
// which is why 10-term batches used to hit CURLOPT_TIMEOUT. "low" is enough
// for short interface strings. For cheaper/faster throughput you can also
// use grok-4.3 (supports Batch API; lower $/token).
$translationModel = 'grok-4.6';
$translationReasoningEffort = 'low';
$translationTimeoutSeconds = 180;
$translationBatchSize = 50;
