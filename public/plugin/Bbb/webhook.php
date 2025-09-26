<?php
/* For license terms, see /license.txt */

/**
 * BBB Webhook endpoint for Chamilo (Bbb plugin)
 *
 * Responsibilities:
 *  - Validate HMAC query signature (our own signature added when registering the hook)
 *  - Parse payload (JSON preferred; XML and form as fallback)
 *  - Map events to per-participant metrics in ConferenceActivity.metrics (JSON)
 *  - Ensure there is an OPEN ConferenceActivity row for (meeting,user)
 */

use Chamilo\CoreBundle\Entity\ConferenceActivity;
use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ConferenceActivityRepository;
use Chamilo\CoreBundle\Repository\ConferenceMeetingRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;

require_once dirname(__DIR__, 3).'/public/main/inc/global.inc.php';

// --------- Debug toggle (set from plugin/config if you want) ----------
$DEBUG = true; // TODO: set to false in production, or read from $plugin->get('debug_webhooks') === 'true'

// Small helper
function dbg($msg){ global $DEBUG; if ($DEBUG) { error_log('[BBB webhook] '.$msg); } }

// --------- Safe JSON response ----------
function http_json($code, $data) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

// --------- Payload readers ----------
function read_raw_payload() {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) { return [null, null, 0]; }
    // JSON
    $js = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($js)) {
        return ['json', $js, strlen($raw)];
    }
    // XML
    $xml = @simplexml_load_string($raw);
    if ($xml) {
        return ['xml', $xml, strlen($raw)];
    }
    // form-encoded
    parse_str($raw, $arr);
    if (is_array($arr) && $arr) {
        return ['form', $arr, strlen($raw)];
    }
    return [null, null, strlen($raw)];
}

// --------- Metrics helpers ----------
function metrics_get(array $m, string $path, $default=null) {
    $p = explode('.', $path);
    foreach ($p as $k) {
        if (!is_array($m) || !array_key_exists($k, $m)) return $default;
        $m = $m[$k];
    }
    return $m;
}
function metrics_set(array &$m, string $path, $value) {
    $p = explode('.', $path);
    $cur =& $m;
    foreach ($p as $k) {
        if (!isset($cur[$k]) || !is_array($cur[$k])) $cur[$k] = [];
        $cur =& $cur[$k];
    }
    $cur = $value;
}
function metrics_inc(array &$m, string $path, int $delta=1) {
    $v = (int) metrics_get($m, $path, 0);
    metrics_set($m, $path, $v + $delta);
}
function metrics_add_seconds(array &$m, string $tempStartPath, string $totalPath, int $stopTs) {
    $startTs = (int) metrics_get($m, $tempStartPath, 0);
    if ($startTs > 0 && $stopTs >= $startTs) {
        $acc = (int) metrics_get($m, $totalPath, 0);
        metrics_set($m, $totalPath, $acc + ($stopTs - $startTs));
        metrics_set($m, $tempStartPath, 0);
    }
}

try {
    // ---------- 1) Validate HMAC we add at webhook registration ----------
    $au  = isset($_GET['au'])  ? (int) $_GET['au']  : 0;
    $mid = isset($_GET['mid']) ? (string) $_GET['mid'] : '';
    $ts  = isset($_GET['ts'])  ? (int) $_GET['ts'] : 0;   // optional but recommended to avoid replay
    $sig = isset($_GET['sig']) ? (string) $_GET['sig'] : '';

    $plugin   = BbbPlugin::create();
    $hashAlgo = $plugin->webhooksHashAlgo(); // 'sha256' | 'sha1'
    $salt     = (string) $plugin->get('salt');

    if (!$salt || !$hashAlgo) {
        dbg('plugin not configured (missing salt/hashAlgo)');
        http_json(500, ['ok'=>false,'error'=>'plugin_not_configured']);
    }

    if (!$au || !$sig) {
        dbg('missing signature fields');
        http_json(400, ['ok'=>false,'error'=>'missing_signature_fields']);
    }

    // Optional anti-replay: allow 15 minutes skew
    if ($ts && abs(time() - $ts) > 900) {
        dbg('expired signature (timestamp out of window)');
        http_json(403, ['ok'=>false,'error'=>'expired_signature']);
    }

    // IMPORTANT: this must match how you generated it in Bbb::buildWebhookCallbackUrl()
    // If there you used au|mid|ts then keep au|mid|ts here; if you used au|mid, keep that.
    $payloadForHmac = $au.'|'.$mid;  // sin timestamp
    $expected = hash_hmac($hashAlgo, $payloadForHmac, $salt);
    if (!hash_equals($expected, $sig)) {
        error_log('[BBB webhook] bad signature: payload='.$payloadForHmac);
        http_response_code(403);
        echo json_encode(['ok'=>false,'error'=>'bad_signature']);
        exit;
    }

    // ---------- 2) Parse incoming payload ----------
    list($fmt, $payloadObj, $rawLen) = read_raw_payload();
    dbg('request ok; body_format=' . ($fmt ?: 'none') . ' body_size=' . $rawLen . 'B');

    if (!$fmt) {
        // Some BBB pings might not have body
        http_json(200, ['ok'=>true,'note'=>'no_payload']);
    }

    $ev = [
        'event'        => null,
        'meetingID'    => null,
        'internalID'   => null,
        'userID'       => null,
        'username'     => null,
        'emoji'        => null,
        'timestamp'    => time(),
    ];

    if ($fmt === 'json') {
        $ev['event']      = $payloadObj['event']           ?? ($payloadObj['header']['name'] ?? null);
        $ev['meetingID']  = $payloadObj['meetingID']       ?? ($payloadObj['payload']['meeting']['externalMeetingID'] ?? null);
        $ev['internalID'] = $payloadObj['internalMeetingID'] ?? ($payloadObj['payload']['meeting']['internalMeetingID'] ?? null);
        $ev['userID']     = $payloadObj['userID']          ?? ($payloadObj['payload']['user']['externalUserID'] ?? null);
        $ev['username']   = $payloadObj['username']        ?? ($payloadObj['payload']['user']['name'] ?? null);
        $ev['emoji']      = $payloadObj['emoji']           ?? ($payloadObj['payload']['emoji'] ?? null);
        $ev['timestamp']  = (int)($payloadObj['timestamp'] ?? time());
    } elseif ($fmt === 'xml') {
        $ev['event']      = (string)($payloadObj->event ?? $payloadObj->header->name ?? '');
        $ev['meetingID']  = (string)($payloadObj->meetingID ?? $payloadObj->payload->meeting->externalMeetingID ?? '');
        $ev['internalID'] = (string)($payloadObj->internalMeetingID ?? $payloadObj->payload->meeting->internalMeetingID ?? '');
        $ev['userID']     = (string)($payloadObj->userID ?? $payloadObj->payload->user->externalUserID ?? '');
        $ev['username']   = (string)($payloadObj->username ?? $payloadObj->payload->user->name ?? '');
        $ev['emoji']      = (string)($payloadObj->emoji ?? $payloadObj->payload->emoji ?? '');
        $ev['timestamp']  = (int)($payloadObj->timestamp ?? time());
    } else { // form
        $arr = $payloadObj;
        $ev['event']      = $arr['event']      ?? ($arr['name'] ?? null);
        $ev['meetingID']  = $arr['meetingID']  ?? ($arr['externalMeetingID'] ?? null);
        $ev['internalID'] = $arr['internalMeetingID'] ?? null;
        $ev['userID']     = $arr['userID']     ?? ($arr['externalUserID'] ?? null);
        $ev['username']   = $arr['username']   ?? null;
        $ev['emoji']      = $arr['emoji']      ?? null;
        $ev['timestamp']  = (int)($arr['timestamp'] ?? time());
    }

    // If hook was registered per meeting, enforce the meetingID from query
    if ($mid !== '') { $ev['meetingID'] = $mid; }

    dbg('event='.($ev['event'] ?? 'null').' meetingID='.($ev['meetingID'] ?? 'null').' userID='.($ev['userID'] ?? 'null'));

    // ---------- 3) Resolve meeting and user ----------
    $em = Database::getManager();
    /** @var ConferenceMeetingRepository $mRepo */
    $mRepo = $em->getRepository(ConferenceMeeting::class);
    /** @var ConferenceActivityRepository $aRepo */
    $aRepo = $em->getRepository(ConferenceActivity::class);
    /** @var UserRepository $uRepo */
    $uRepo = $em->getRepository(User::class);

    // Meeting by external remoteId first, then internalMeetingId
    $meeting = null;
    if (!empty($ev['meetingID'])) {
        $meeting = $mRepo->findOneBy(['remoteId' => (string)$ev['meetingID']]);
    }
    if (!$meeting && !empty($ev['internalID'])) {
        $meeting = $mRepo->findOneBy(['internalMeetingId' => (string)$ev['internalID']]);
    }
    if (!$meeting) {
        dbg('meeting not found');
        http_json(200, ['ok'=>true,'note'=>'meeting_not_found']);
    }

    // Resolve user: prefer numeric externalUserID; fallback to username
    $user = null;
    if (!empty($ev['userID']) && ctype_digit((string)$ev['userID'])) {
        $user = $uRepo->find((int)$ev['userID']);
    }
    if (!$user && !empty($ev['username'])) {
        $user = $uRepo->findOneBy(['username' => (string)$ev['username']]);
    }
    if (!$user) {
        dbg('user not found');
        http_json(200, ['ok'=>true,'note'=>'user_not_found']);
    }

    // ---------- 4) Find or create OPEN ConferenceActivity ----------
    $open = $aRepo->createQueryBuilder('a')
        ->where('a.meeting = :m')
        ->andWhere('a.participant = :u')
        ->andWhere('a.close = :open')
        ->setParameter('m', $meeting)
        ->setParameter('u', $user)
        ->setParameter('open', BbbPlugin::ROOM_OPEN)
        ->orderBy('a.id','DESC')
        ->getQuery()->getOneOrNullResult();

    if (!$open) {
        $open = new ConferenceActivity();
        $open->setMeeting($meeting);
        $open->setParticipant($user);
        $open->setInAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $open->setOutAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $open->setClose(BbbPlugin::ROOM_OPEN);
        $em->persist($open);
        $em->flush();
    }

    // ---------- 5) Load/update metrics ----------
    $metrics = $open->getMetrics();
    if (!is_array($metrics)) {
        $metrics = [
            'totals' => ['talk_seconds'=>0, 'camera_seconds'=>0],
            'counts' => ['messages'=>0, 'reactions'=>0, 'hands'=>0, 'reactions_breakdown'=>[]],
            'temp'   => ['talk_started_at'=>0, 'camera_started_at'=>0],
        ];
    }

    $eName = strtolower((string)($ev['event'] ?? ''));
    $tsEvt = (int)($ev['timestamp'] ?? time());
    $changed = false;

    switch ($eName) {
        // Chat
        case 'publicchatmessageposted':
        case 'chat_message_posted':
        case 'message_posted':
            metrics_inc($metrics, 'counts.messages', 1);
            $changed = true;
            break;

        // Voice start/stop
        case 'uservoiceactivated':
        case 'user_talking_started':
        case 'audio_talk_started':
            metrics_set($metrics, 'temp.talk_started_at', $tsEvt);
            $changed = true;
            break;

        case 'uservoicedeactivated':
        case 'user_talking_stopped':
        case 'audio_talk_stopped':
            metrics_add_seconds($metrics, 'temp.talk_started_at', 'totals.talk_seconds', $tsEvt);
            $changed = true;
            break;

        // Camera start/stop
        case 'webcamsharestarted':
        case 'camera_share_started':
            metrics_set($metrics, 'temp.camera_started_at', $tsEvt);
            $changed = true;
            break;

        case 'webcamsharestopped':
        case 'camera_share_stopped':
            metrics_add_seconds($metrics, 'temp.camera_started_at', 'totals.camera_seconds', $tsEvt);
            $changed = true;
            break;

        // Reactions
        case 'useremojichanged':
        case 'user_reaction_changed':
        case 'reaction':
            $emoji = (string)($ev['emoji'] ?? '');
            if ($emoji !== '') {
                metrics_inc($metrics, 'counts.reactions', 1);
                $rb = metrics_get($metrics, 'counts.reactions_breakdown', []);
                $rb[$emoji] = (int)($rb[$emoji] ?? 0) + 1;
                metrics_set($metrics, 'counts.reactions_breakdown', $rb);
                $changed = true;
            }
            break;

        // Hand raise
        case 'userraisedhand':
        case 'user_hand_raised':
            metrics_inc($metrics, 'counts.hands', 1);
            $changed = true;
            break;

        // Participant left
        case 'participantleft':
        case 'user_left':
            metrics_add_seconds($metrics, 'temp.talk_started_at',   'totals.talk_seconds',   $tsEvt);
            metrics_add_seconds($metrics, 'temp.camera_started_at', 'totals.camera_seconds', $tsEvt);
            $outAt = (new \DateTime('@'.$tsEvt))->setTimezone(new \DateTimeZone('UTC'));
            $open->setOutAt($outAt);
            $open->setClose(BbbPlugin::ROOM_CLOSE);
            $changed = true;
            break;

        // Participant joined: ensure row exists (already done)
        case 'participantjoined':
        case 'user_joined':
            $changed = true;
            break;

        default:
            dbg('unknown event: '.$eName);
            break;
    }

    if ($changed) {
        $open->setMetrics($metrics);
        $em->persist($open);
        $em->flush();
    }

    http_json(200, [
        'ok'         => true,
        'event'      => $eName,
        'meeting_id' => $meeting->getId(),
        'user_id'    => $user->getId(),
    ]);

} catch (\Throwable $e) {
    // Never leak stack traces to caller, but log them if DEBUG
    dbg('unhandled exception: '.$e->getMessage());
    http_json(500, ['ok'=>false,'error'=>'internal_error']);
}
