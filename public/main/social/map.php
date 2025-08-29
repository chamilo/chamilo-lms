<?php
/* For licensing terms, see /license.txt */

/**
 * Social map with user geolocation
 * - Checks Google Maps plugin enabled + configured for current access URL
 * - Reads API key and extra field list from access_url_rel_plugin.configuration
 * - Accepts values as JSON {"lat","lng"}, "label::lat,lng" or "lat,lng"
 * - LEFT JOIN so a user appears if at least one field has coords
 * - Caches result set (APCu -> filesystem)
 */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Repository\AccessUrlRelPluginRepository;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

/* ----------------------- helpers ----------------------- */
function extractLatLng($raw) {
    if (empty($raw)) return [null, null];
    $raw = trim((string) $raw);

    // JSON {"lat":...,"lng":...}
    if (strlen($raw) > 1 && $raw[0] === '{') {
        $obj = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($obj['lat'], $obj['lng'])) {
            return [$obj['lat'], $obj['lng']];
        }
    }
    // Legacy "label::lat,lng"
    if (strpos($raw, '::') !== false) {
        [, $coords] = explode('::', $raw, 2);
        $p = array_map('trim', explode(',', $coords, 2));
        if (count($p) === 2) return [$p[0], $p[1]];
    }
    // Simple "lat,lng"
    if (strpos($raw, ',') !== false) {
        $p = array_map('trim', explode(',', $raw, 2));
        if (count($p) === 2) return [$p[0], $p[1]];
    }
    return [null, null];
}

function resolveGeoType(): int {
    if (defined('ExtraField::FIELD_TYPE_GEOLOCALIZATION')) return constant('ExtraField::FIELD_TYPE_GEOLOCALIZATION');
    if (defined('ExtraField::FIELD_TYPE_GEOLOCATION'))   return constant('ExtraField::FIELD_TYPE_GEOLOCATION');
    if (defined('ExtraField::FIELD_TYPE_TEXT'))          return constant('ExtraField::FIELD_TYPE_TEXT');
    return 1; // TEXT fallback
}

function ensureGeoExtraField(ExtraField $ef, ?string $var, string $label): ?array {
    if (empty($var)) return null;
    $info = $ef->get_handler_field_info_by_field_variable($var);
    if (!empty($info)) return $info;

    // Create the field only for platform admins
    if (api_is_platform_admin()) {
        $payload = [
            'variable'           => $var,
            'display_text'       => $label,
            'value_type'         => resolveGeoType(),
            'visible_to_self'    => 1,
            'visible_to_others'  => 1,
            'changeable'         => 1,
            'created_at'         => date('Y-m-d H:i:s'),
        ];
        $ef->save($payload);
        return $ef->get_handler_field_info_by_field_variable($var);
    }
    return null;
}

/* ----------------------- services ----------------------- */
$pluginHelper    = Container::$container->get(PluginHelper::class);
$accessUrlHelper = Container::$container->get(AccessUrlHelper::class);
$pluginRepo      = Container::$container->get(AccessUrlRelPluginRepository::class);

/* ---------------- validate plugin enabled --------------- */
$PLUGIN_NAME = 'google_maps';
if (!$pluginHelper->isPluginEnabled($PLUGIN_NAME)) {
    if (api_is_platform_admin()) {
        Display::display_header(get_lang('Social'));
        echo Display::return_message('Google Maps plugin is not enabled for this portal URL. Enable it in Administration → Plugins.', 'warning');
        echo '<p><a href="'.api_get_path(WEB_CODE_PATH).'admin/plugins.php">Open Plugins admin</a></p>';
        Display::display_footer();
    } else {
        api_not_allowed(true);
    }
    exit;
}

/* ---------------- load plugin config for URL ------------ */
$currentUrl = $accessUrlHelper->getCurrent();
if ($currentUrl === null) {
    api_not_allowed(true);
}
$rel = $pluginRepo->findOneByPluginName($PLUGIN_NAME, $currentUrl->getId());
if (!$rel || !$rel->isActive()) {
    api_not_allowed(true);
}
$config = $rel->getConfiguration();
if (is_string($config)) {
    $tmp = json_decode($config, true);
    if (json_last_error() === JSON_ERROR_NONE) $config = $tmp;
}
if (!is_array($config)) $config = [];

$enabledRaw = $config['enable_api'] ?? null;
$apiKey     = trim((string)($config['api_key'] ?? ''));

/* truthy parsing for enable_api */
$localization = ($enabledRaw === true) || ($enabledRaw === 1) || ($enabledRaw === '1') || ($enabledRaw === 'true') || ($enabledRaw === 'on') || ($enabledRaw === 'yes');

if (!$localization || $apiKey === '') {
    if (api_is_platform_admin()) {
        Display::display_header(get_lang('Social'));
        echo Display::return_message('Google Maps plugin not configured. Turn on API and set API key in Administration → Plugins → Google Maps.', 'warning');
        echo '<p><a href="'.api_get_path(WEB_CODE_PATH).'admin/plugins.php">'.get_lang('Open Plugins admin').'</a></p>';
        Display::display_footer();
    } else {
        api_not_allowed(true);
    }
    exit;
}

/* ---------------- fields to use ------------------------ */
$pluginFieldsCsv = (string)($config['extra_field_name'] ?? '');
$vars = array_values(array_filter(array_map('trim', explode(',', $pluginFieldsCsv))));
if (empty($vars)) {
    $fieldsSetting = api_get_setting('profile.allow_social_map_fields', true);
    if (!$fieldsSetting || empty($fieldsSetting['fields']) || !is_array($fieldsSetting['fields'])) {
        api_not_allowed(true);
    }
    $vars = array_values($fieldsSetting['fields']);
}
$vars = array_values(array_unique(array_filter($vars)));
$var1 = $vars[0] ?? null;
$var2 = $vars[1] ?? null;

/* ---------------- ensure extrafields exist -------------- */
$extraField = new ExtraField('user');
$info1 = ensureGeoExtraField($extraField, $var1, $var1 ?: 'Geolocation A');
$info2 = ensureGeoExtraField($extraField, $var2, $var2 ?: 'Geolocation B');
if (empty($info1) && empty($info2)) {
    api_not_allowed(true);
}

/* ---------------- build query --------------------------- */
$tableUser = Database::get_main_table(TABLE_MAIN_USER);

$select = "u.id, u.firstname, u.lastname";
$joins  = [];
$conds  = [];

if (!empty($info1)) {
    $select .= ", ev1.field_value AS f1";
    $joins[] = "LEFT JOIN extra_field_values ev1
                  ON ev1.item_id = u.id
                 AND ev1.field_id = ".$info1['id'];
    $conds[] = "COALESCE(ev1.field_value,'') <> ''";
}
if (!empty($info2)) {
    $select .= ", ev2.field_value AS f2";
    $joins[] = "LEFT JOIN extra_field_values ev2
                  ON ev2.item_id = u.id
                 AND ev2.field_id = ".$info2['id'];
    $conds[] = "COALESCE(ev2.field_value,'') <> ''";
}

if (empty($conds)) {
    api_not_allowed(true);
}

$sql = "SELECT $select
        FROM $tableUser u
        ".implode("\n", $joins)."
        WHERE u.active = 1
          AND (".implode(' OR ', $conds).")";

/* ---------------- caching (APCu -> FS) ------------------ */
$useApcu = function_exists('apcu_enabled') ? apcu_enabled() : (extension_loaded('apcu') && (PHP_SAPI !== 'cli' || (bool)ini_get('apc.enable_cli')));

$cache = $useApcu
    ? new ApcuAdapter('social_map')
    : new FilesystemAdapter('social_map', 300, rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'chamilo_cache');

$cacheKey = sprintf('places:url%d:f1_%s:f2_%s', (int)$currentUrl->getId(), (string)($info1['id'] ?? 0), (string)($info2['id'] ?? 0));
$item = $cache->getItem($cacheKey);
if (!$item->isHit()) {
    $result = Database::query($sql);
    $data   = Database::store_result($result, 'ASSOC');
    $item->set($data);
    $item->expiresAfter(300);
    $cache->save($item);
} else {
    $data = $item->get();
}

/* ---------------- massage rows -------------------------- */
$guessType = static function (?string $var): ?string {
    if (!$var) return null;
    $v = strtolower($var);
    if (str_contains($v, 'villedustage')) return 'stage';
    if (str_contains($v, 'ville')) return 'ville';
    return null;
};
$type1 = $guessType($var1);
$type2 = $guessType($var2);

foreach ($data as &$row) {
    $row['complete_name'] = trim($row['firstname'].' '.$row['lastname']);
    $row['lastname']  = '';
    $row['firstname'] = '';

    if (array_key_exists('f1', $row)) {
        [$aLat, $aLng] = extractLatLng($row['f1']);
        if ($aLat !== null && $aLng !== null) {
            $row['f1_lat']  = $aLat;
            $row['f1_long'] = $aLng;
            if ($type1) {
                $row[$type1.'_lat']  = $aLat;
                $row[$type1.'_long'] = $aLng;
            }
        }
        unset($row['f1']);
    }
    if (array_key_exists('f2', $row)) {
        [$bLat, $bLng] = extractLatLng($row['f2']);
        if ($bLat !== null && $bLng !== null) {
            $row['f2_lat']  = $bLat;
            $row['f2_long'] = $bLng;
            if ($type2) {
                $row[$type2.'_lat']  = $bLat;
                $row[$type2.'_long'] = $bLng;
            }
        }
        unset($row['f2']);
    }
}
unset($row);

$data = array_values(array_filter($data, static function ($r) {
    return isset($r['f1_lat'],$r['f1_long'])
        || isset($r['f2_lat'],$r['f2_long'])
        || isset($r['ville_lat'],$r['ville_long'])
        || isset($r['stage_lat'],$r['stage_long']);
}));

/* ---------------- render ------------------------------- */
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_JS_PATH).'map/markerclusterer.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_JS_PATH).'map/oms.min.js"></script>';

$tpl = new Template(null);
$tpl->assign('url', api_get_path(WEB_PATH).'social');
$tpl->assign('places', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
$tpl->assign('api_key', $apiKey);
$tpl->assign('gmap_api_key', $apiKey);
$tpl->assign('field_1', !empty($info1) ? ($info1['display_text'] ?? $var1 ?? '') : '');
$tpl->assign('field_2', !empty($info2) ? ($info2['display_text'] ?? $var2 ?? '') : '');

/* Icons (if your template uses them) */
$tpl->assign('image_city', Display::return_icon('red-dot.png', '', [], ICON_SIZE_SMALL, false, true));
$tpl->assign('image_stage', Display::return_icon('blue-dot.png', '', [], ICON_SIZE_SMALL, false, true));

$layout = $tpl->get_template('social/map.tpl');
$tpl->display($layout);
