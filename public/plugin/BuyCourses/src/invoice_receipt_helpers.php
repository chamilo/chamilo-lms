<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

/*
 * Small HTML-escaping helpers shared by invoice.php and receipt.php, so the two PDF
 * documents don't each carry their own (potentially drifting) copy of this logic.
 */

if (!function_exists('buycourses_invoice_escape')) {
    function buycourses_invoice_escape($value): string
    {
        return htmlspecialchars((string) $value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('buycourses_invoice_nl2br')) {
    function buycourses_invoice_nl2br($value): string
    {
        return nl2br(buycourses_invoice_escape($value));
    }
}

if (!function_exists('buycourses_invoice_label')) {
    function buycourses_invoice_label(BuyCoursesPlugin $plugin, string $key, string $fallback): string
    {
        $label = $plugin->get_lang($key);

        return empty($label) || $label === $key ? $fallback : $label;
    }
}
