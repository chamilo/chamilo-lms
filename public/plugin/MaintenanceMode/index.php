<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

http_response_code(503);
header('Retry-After: 3600');
header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

$title = 'Maintenance mode';
$message = 'The platform is temporarily unavailable due to maintenance. Please try again later.';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body class="min-h-screen bg-gray-15">
    <main class="min-h-screen flex items-center justify-center p-6">
        <section class="w-full max-w-xl rounded-2xl border border-gray-25 bg-white p-8 shadow-sm">
            <p class="mb-2 text-sm font-semibold uppercase tracking-wide text-primary">Chamilo</p>
            <h1 class="mb-4 text-3xl font-bold text-gray-90">
                <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
            </h1>
            <p class="text-base text-gray-70">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </section>
    </main>
</body>
</html>
