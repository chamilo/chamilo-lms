<?php
/* For license terms, see /license.txt */
exit;
/**
 * This file is a cron microclock script.
 * It will be used as replacement of setting individual
 * cron lines for all virtual instances.
 *
 * Setup this vcron to run at the smallest period possible, as
 * it will schedule all availables vchamilos to be run as required.
 * Note that one activaton of this cron may not always run real crons
 * or may be run more than one cron.
 *
 * If used on a big system with clustering, ensure hostnames are adressed
 * at the load balancer entry and not on physical hosts
 *
 * @package plugin/vchamilo
 * @category plugins
 *
 * @author Valery fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
define('CLI_SCRIPT', true); // for chamilo imported code
require_once dirname(dirname(__DIR__)).'/main/inc/global.inc.php';

global $DB;
$DB = new DatabaseManager();

define('ROUND_ROBIN', 0);
define('LOWEST_POSSIBLE_GAP', 1);

global $VCRON;

$VCRON = new stdClass();
$VCRON->ACTIVATION = 'cli'; // choose how individual cron are launched, 'cli' or 'web'
$VCRON->STRATEGY = ROUND_ROBIN; // choose vcron rotation mode
$VCRON->PERIOD = 15 * MINSECS; // used if LOWEST_POSSIBLE_GAP to setup the max gap
$VCRON->TIMEOUT = 300; // time out for CURL call to effective cron
// $VCRON->TRACE = $_configuration['root_sys'].'plugin/vchamilo/log/vcrontrace.log';   // Trace file where to collect cron outputs
$VCRON->TRACE = '/data/log/chamilo/vcrontrace.log'; // Trace file where to collect cron outputs
$VCRON->TRACE_ENABLE = true; // enables tracing

if (!is_dir($_configuration['root_sys'].'plugin/vchamilo/log')) {
    $mode = api_get_permissions_for_new_directories();
    mkdir($_configuration['root_sys'].'plugin/vchamilo/log', $mode, true);
}

/**
 * fire a cron URL using CURL.
 */
function fire_vhost_cron($vhost)
{
    global $VCRON;

    if ($VCRON->TRACE_ENABLE) {
        $CRONTRACE = fopen($VCRON->TRACE, 'a');
    }
    $ch = curl_init($vhost->root_web.'/main/cron/run.php');

    $http_proxy_host = api_get_setting('vchamilo_httpproxyhost', 'vchamilo');
    $http_proxy_port = api_get_setting('vchamilo_httpproxyport', 'vchamilo');
    $http_proxy_bypass = api_get_setting('vchamilo_httpproxybypass', 'vchamilo');
    $http_proxy_user = api_get_setting('vchamilo_httpproxyuser', 'vchamilo');
    $http_proxy_password = api_get_setting('vchamilo_httpproxypassword', 'vchamilo');

    curl_setopt($ch, CURLOPT_TIMEOUT, $VCRON->TIMEOUT);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Chamilo');
    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: text/xml charset=UTF-8"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    // Check for proxy.
    if (!empty($http_proxy_host) && !is_proxybypass($uri)) {
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);

        if (empty($http_proxy_port)) {
            echo "Using proxy $http_proxy_host\n";
            curl_setopt($ch, CURLOPT_PROXY, $http_proxy_host);
        } else {
            echo "Using proxy $http_proxy_host:$http_proxy_port\n";
            curl_setopt($ch, CURLOPT_PROXY, $http_proxy_host.':'.$http_proxy_port);
        }

        if (!empty($http_proxy_user) and !empty($http_proxy_password)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $http_proxy_user.':'.$http_proxy_password);
            if (defined('CURLOPT_PROXYAUTH')) {
                // any proxy authentication if PHP 5.1
                curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC | CURLAUTH_NTLM);
            }
        }
    }

    $timestamp_send = time();
    $rawresponse = curl_exec($ch);
    $timestamp_receive = time();

    if ($rawresponse === false) {
        $error = curl_errno($ch).':'.curl_error($ch);
        if ($VCRON->TRACE_ENABLE) {
            if ($CRONTRACE) {
                fputs($CRONTRACE, "VCron start on $vhost->root_web : ".api_time_to_hms($timestamp_send)."\n");
                fputs($CRONTRACE, "VCron Error : $error \n");
                fputs($CRONTRACE, "VCron stop on $vhost->root_web : $timestamp_receive\n#################\n\n");
                fclose($CRONTRACE);
            }
        }
        echo "VCron started on $vhost->root_web : ".api_time_to_hms($timestamp_send)."\n";
        echo "VCron Error : $error \n";
        echo "VCron stop on $vhost->root_web : ".api_time_to_hms($timestamp_receive)."\n#################\n\n";

        return false;
    }

    if ($VCRON->TRACE_ENABLE) {
        if ($CRONTRACE) {
            fputs($CRONTRACE, "VCron start on $vhost->vhostname : ".api_time_to_hms($timestamp_send)."\n");
            fputs($CRONTRACE, $rawresponse."\n");
            fputs($CRONTRACE, "VCron stop on $vhost->vhostname : ".api_time_to_hms($timestamp_receive)."\n#################\n\n");
            fclose($CRONTRACE);
        }
    }
    echo "VCron start on $vhost->root_web : ".api_time_to_hms($timestamp_send)."\n";
    echo $rawresponse."\n";
    echo "VCron stop on $vhost->root_web : ".api_time_to_hms($timestamp_receive)."\n#################\n\n";
    $vhost->lastcrongap = time() - $vhost->lastcron;
    $vhost->lastcron = $timestamp_send;
    $vhost->croncount++;

    $vhostid = $vhost->id;
    unset($vhost->id);

    Database::update('vchamilo', (array) $vhost, ['id = ?' => $vhostid]);
}

/**
 * fire a cron URL using cli exec.
 */
function exec_vhost_cron($vhost)
{
    global $VCRON, $DB, $_configuration;

    if ($VCRON->TRACE_ENABLE) {
        $CRONTRACE = fopen($VCRON->TRACE, 'a');
    }

    $cmd = 'php "'.$_configuration['root_sys'].'/plugin/vchamilo/cli/cron.php" --host='.$vhost->root_web;

    $timestamp_send = time();
    exec($cmd, $rawresponse);
    $timestamp_receive = time();

    if ($VCRON->TRACE_ENABLE) {
        if ($CRONTRACE) {
            fputs($CRONTRACE, "VCron start on $vhost->root_web : $timestamp_send\n");
            fputs($CRONTRACE, $rawresponse."\n");
            fputs($CRONTRACE, "VCron stop on $vhost->root_web : $timestamp_receive\n#################\n\n");
            fclose($CRONTRACE);
        }
    }

    echo "VCron start on $vhost->root_web : $timestamp_send\n";
    echo implode("\n", $rawresponse)."\n";
    echo "VCron stop on $vhost->root_web : $timestamp_receive\n#################\n\n";

    $vhost->lastcrongap = time() - $vhost->lastcron;
    $vhost->lastcron = $timestamp_send;
    $vhost->croncount++;

    $DB->update_record('vchamilo', $vhost, 'id');
}

/**
 * check if $url matches anything in proxybypass list.
 *
 * any errors just result in the proxy being used (least bad)
 *
 * @global object
 *
 * @param string $url url to check
 *
 * @return bool true if we should bypass the proxy
 */
function is_proxybypass($url)
{
    $http_proxy_host = api_get_setting('vchamilo_httpproxyhost', 'vchamilo');
    $http_proxy_port = api_get_setting('vchamilo_httpproxyport', 'vchamilo');
    $http_proxy_bypass = api_get_setting('vchamilo_httpproxybypass', 'vchamilo');

    // sanity check
    if (empty($http_proxy_host) or empty($http_proxy_bypass)) {
        return false;
    }

    // get the host part out of the url
    if (!$host = parse_url($url, PHP_URL_HOST)) {
        return false;
    }

    // get the possible bypass hosts into an array
    $matches = explode(',', $http_proxy_bypass);

    // check for a match
    // (IPs need to match the left hand side and hosts the right of the url,
    // but we can recklessly check both as there can't be a false +ve)
    $bypass = false;
    foreach ($matches as $match) {
        $match = trim($match);

        // try for IP match (Left side)
        $lhs = substr($host, 0, strlen($match));
        if (strcasecmp($match, $lhs) == 0) {
            return true;
        }

        // try for host match (Right side)
        $rhs = substr($host, -strlen($match));
        if (strcasecmp($match, $rhs) == 0) {
            return true;
        }
    }

    // nothing matched.
    return false;
}

// Main execution sequence

if (!$vchamilos = Database::select('*', 'vchamilo', [], 'all')) {
    exit("Nothing to do. No Vhosts");
}

$allvhosts = array_values($vchamilos);

echo "<pre>";
echo "Chamilo VCron... start\n";
echo "Last croned : ".api_get_setting('vchamilo_cron_lasthost', 'vchamilo')."\n";

if ($VCRON->STRATEGY == ROUND_ROBIN) {
    $rr = 0;
    foreach ($allvhosts as $vhostassoc) {
        $vhost = (object) $vhostassoc;
        if ($rr == 1) {
            api_set_setting('vchamilo_cron_lasthost', $vhost->id);
            echo "Round Robin : ".$vhost->root_web."\n";
            if ($VCRON->ACTIVATION == 'cli') {
                exec_vhost_cron($vhost);
            } else {
                fire_vhost_cron($vhost);
            }

            exit('Done.');
        }
        if ($vhost->id == api_get_setting('vchamilo_cron_lasthost', 'vchamilo')) {
            $rr = 1; // take next one
        }
    }

    // We were at last. Loop back and take first.
    $firsthost = (object) $allvhosts[0];
    api_set_setting('vchamilo_cron_lasthost', $firsthost->id, 'vchamilo');
    echo "Round Robin : ".$firsthost->root_web."\n";
    if ($VCRON->ACTIVATION == 'cli') {
        exec_vhost_cron($firsthost);
    } else {
        fire_vhost_cron($firsthost);
    }
} elseif ($VCRON->STRATEGY == LOWEST_POSSIBLE_GAP) {
    // First make measurement of cron period.
    if (api_get_setting('vcrontickperiod', 'vchamilo')) {
        api_set_setting('vcrontime', time(), 'vchamilo');

        return;
    }
    api_set_setting('vcrontickperiod', time() - api_get_setting('vcrontime', 'vchamilo'), 'vchamilo');
    $hostsperturn = max(1, $VCRON->PERIOD / api_get_setting('vcrontickperiod', 'vchamilo') * count($allvhosts));
    $i = 0;
    foreach ($allvhosts as $vhostassoc) {
        $vhost = (object) $vhostassoc;
        if ((time() - $vhost->lastcron) > $VCRON->PERIOD) {
            if ($VCRON->ACTIVATION == 'cli') {
                exec_vhost_cron($vhost);
            } else {
                fire_vhost_cron($vhost);
            }
            $i++;
            if ($i >= $hostsperturn) {
                return;
            }
        }
    }
}
