<?php
/* For license terms, see /license.txt */

/**
 * BBB Webhooks Dashboard (Global, grouped by meeting) – “participants activity” style
 * - Global KPIs
 * - Filters: title, date range, only-online rows, specific meeting
 * - Grouped table by meeting with progress bar, icons, and reactions breakdown
 * - CSV export and auto-refresh
 */

use Chamilo\CoreBundle\Entity\ConferenceActivity;
use Chamilo\CoreBundle\Entity\ConferenceMeeting;

require_once __DIR__.'/config.php';

/* --- Security --- */
api_block_anonymous_users();
if (!api_is_platform_admin()) {
    api_not_allowed(true);
}

/* --- Helpers --- */
function json_out($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
function csv_out(string $filename, array $rows, array $header) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $header);
    foreach ($rows as $r) { fputcsv($out, $r); }
    fclose($out);
    exit;
}
function dt_utc($s = 'now'): \DateTime {
    return new \DateTime($s, new \DateTimeZone('UTC'));
}
function hms(int $s): string {
    $h = intdiv($s, 3600); $m = intdiv($s % 3600, 60); $sec = $s % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $sec);
}

/* --- Repos --- */
$em          = Database::getManager();
$actRepo     = $em->getRepository(ConferenceActivity::class);
$meetingRepo = $em->getRepository(ConferenceMeeting::class);

/* --- Constants --- */
$ROOM_OPEN  = BbbPlugin::ROOM_OPEN;
$ROOM_CLOSE = BbbPlugin::ROOM_CLOSE;

/* --- Filters --- */
$qTitle   = trim((string)($_GET['q'] ?? ''));
$onlyOpen = isset($_GET['only_open']) ? (int)$_GET['only_open'] : 0;
$fromStr  = trim((string)($_GET['from'] ?? ''));
$toStr    = trim((string)($_GET['to']   ?? ''));
$meetingFilter = isset($_GET['meeting_id']) ? (int)$_GET['meeting_id'] : 0;

$now    = dt_utc('now');
$today0 = dt_utc('today');

$from = $fromStr ? new \DateTime($fromStr.' 00:00:00', new \DateTimeZone('UTC')) : (clone $now)->modify('-24 hours');
$to   = $toStr   ? new \DateTime($toStr.' 23:59:59', new \DateTimeZone('UTC'))   : (clone $now);

/* --- Meetings select list --- */
$meetingsQb = $meetingRepo->createQueryBuilder('m')
    ->select('m.id,m.title')
    ->orderBy('m.createdAt', 'DESC');
if ($qTitle !== '') {
    $meetingsQb->andWhere('m.title LIKE :q')->setParameter('q', '%'.$qTitle.'%');
}
$meetingOptions = $meetingsQb->getQuery()->getArrayResult();
$meetingIdsInFilter = array_map('intval', array_column($meetingOptions, 'id'));
if ($meetingFilter > 0) {
    // If the selected meeting is not in current filtered list, still force it
    $meetingIdsInFilter = in_array($meetingFilter, $meetingIdsInFilter, true) ? [$meetingFilter] : [$meetingFilter];
}

/* ===================== Stats & grouped data ===================== */
$statsAndData = (function() use ($actRepo, $meetingRepo, $ROOM_OPEN, $ROOM_CLOSE, $now, $today0, $from, $to, $meetingIdsInFilter) {

    /* KPIs */
    $q1 = $actRepo->createQueryBuilder('a')->select('COUNT(a.id)')->where('a.close = :o')->setParameter('o', $ROOM_OPEN);
    if (!empty($meetingIdsInFilter)) $q1->andWhere('a.meeting IN (:mid)')->setParameter('mid', $meetingIdsInFilter);
    $connected_now = (int)$q1->getQuery()->getSingleScalarResult();

    $q2 = $meetingRepo->createQueryBuilder('m')->select('COUNT(m.id)')->where('m.status = 1');
    if (!empty($meetingIdsInFilter)) $q2->andWhere('m.id IN (:mid)')->setParameter('mid', $meetingIdsInFilter);
    $active_by_status = (int)$q2->getQuery()->getSingleScalarResult();

    $q3 = $actRepo->createQueryBuilder('a')->select('COUNT(DISTINCT IDENTITY(a.meeting))')->where('a.close = :o')->setParameter('o', $ROOM_OPEN);
    if (!empty($meetingIdsInFilter)) $q3->andWhere('a.meeting IN (:mid)')->setParameter('mid', $meetingIdsInFilter);
    $active_by_open = (int)$q3->getQuery()->getSingleScalarResult();

    $active_meetings = max($active_by_status, $active_by_open);

    $q4 = $actRepo->createQueryBuilder('a')->select('COUNT(a.id)')->where('a.inAt >= :d0')->setParameter('d0', $today0);
    if (!empty($meetingIdsInFilter)) $q4->andWhere('a.meeting IN (:mid)')->setParameter('mid', $meetingIdsInFilter);
    $joins_today = (int)$q4->getQuery()->getSingleScalarResult();

    $q5 = $actRepo->createQueryBuilder('a')->select('COUNT(a.id)')->where('a.inAt >= :t')->setParameter('t', (clone $now)->modify('-24 hours'));
    if (!empty($meetingIdsInFilter)) $q5->andWhere('a.meeting IN (:mid)')->setParameter('mid', $meetingIdsInFilter);
    $joins_24h = (int)$q5->getQuery()->getSingleScalarResult();

    $q6 = $actRepo->createQueryBuilder('a')->select('COUNT(a.id)')
        ->where('a.close = :c')->andWhere('a.outAt IS NOT NULL')->andWhere('a.outAt >= :t')
        ->setParameter('c', $ROOM_CLOSE)->setParameter('t', (clone $now)->modify('-24 hours'));
    if (!empty($meetingIdsInFilter)) $q6->andWhere('a.meeting IN (:mid)')->setParameter('mid', $meetingIdsInFilter);
    $leaves_24h = (int)$q6->getQuery()->getSingleScalarResult();

    $events_24h = $joins_24h + $leaves_24h;

    /* Grouped table by meeting */
    $qb = $actRepo->createQueryBuilder('a')
        ->leftJoin('a.meeting', 'm')->addSelect('m')
        ->leftJoin('a.participant', 'u')->addSelect('u')
        ->where('(a.inAt BETWEEN :f AND :t) OR (a.outAt BETWEEN :f AND :t)')
        ->setParameter('f', $from)->setParameter('t', $to)
        ->orderBy('m.createdAt','DESC')->addOrderBy('u.lastname','ASC')->addOrderBy('a.id','ASC');
    if (!empty($meetingIdsInFilter)) $qb->andWhere('m.id IN (:mid)')->setParameter('mid', $meetingIdsInFilter);

    $acts = $qb->getQuery()->getResult();

    $grouped = []; // mid => data
    foreach ($acts as $a) {
        /** @var ConferenceActivity $a */
        $m = $a->getMeeting(); if (!$m) continue;
        $u = $a->getParticipant(); if (!$u) continue;
        $mid = (int)$m->getId();
        if (!isset($grouped[$mid])) {
            // Meeting duration info (not used for progress bar; bar is per max user time)
            $start = $m->getCreatedAt(); $end = $m->getClosedAt();
            $meetingDuration = ($start && $end) ? max(0, $end->getTimestamp() - $start->getTimestamp()) : 0;

            $grouped[$mid] = [
                'meeting' => [
                    'id'         => $mid,
                    'title'      => (string)$m->getTitle(),
                    'status'     => $m->isOpen() ? 'running' : 'finished',
                    'created_at' => $start?->format('Y-m-d H:i:s'),
                    'closed_at'  => $end?->format('Y-m-d H:i:s'),
                    'duration_s' => $meetingDuration,
                ],
                'rows'   => [],
                'totals' => [
                    'users' => 0,
                    'online_seconds'=>0, 'talk_seconds'=>0, 'camera_seconds'=>0,
                    'messages'=>0, 'reactions'=>0, 'hands'=>0,
                    'reactions_breakdown'=>[],
                ],
                'max_online_user_s' => 0, // for progress bar scale
            ];
        }

        $uid  = (int)$u->getId();
        $name = method_exists($u,'getFullName') ? $u->getFullName() : trim(($u->getLastname().' '.$u->getFirstname()));
        if (!isset($grouped[$mid]['rows'][$uid])) {
            $grouped[$mid]['rows'][$uid] = [
                'user_id'=>$uid, 'user'=>$name ?: ('#'.$uid),
                'online_seconds'=>0, 'talk_seconds'=>0, 'camera_seconds'=>0,
                'messages'=>0, 'reactions'=>0, 'hands'=>0,
                'reactions_breakdown'=>[],
                'status'=>'offline',
                'first_join'=>$a->getInAt()?->format('Y-m-d H:i:s'),
                'last_seen'=>$a->getOutAt()?->format('Y-m-d H:i:s'),
            ];
        }

        // Accumulate times
        $in=$a->getInAt(); $out=$a->getOutAt();
        if ($in instanceof DateTimeInterface && $out instanceof DateTimeInterface) {
            $seg = max(0, $out->getTimestamp() - $in->getTimestamp());
            $grouped[$mid]['rows'][$uid]['online_seconds'] += $seg;
        }

        // Metrics coming from webhook payload (if any)
        $mj = is_array($a->getMetrics()) ? $a->getMetrics() : [];
        $grouped[$mid]['rows'][$uid]['talk_seconds']   += (int)($mj['totals']['talk_seconds']   ?? 0);
        $grouped[$mid]['rows'][$uid]['camera_seconds'] += (int)($mj['totals']['camera_seconds'] ?? 0);
        $grouped[$mid]['rows'][$uid]['messages']       += (int)($mj['counts']['messages']       ?? 0);
        $grouped[$mid]['rows'][$uid]['reactions']      += (int)($mj['counts']['reactions']      ?? 0);
        $grouped[$mid]['rows'][$uid]['hands']          += (int)($mj['counts']['hands']          ?? 0);

        // Reactions breakdown (emoji => count) if provided
        if (!empty($mj['counts']['reactions_breakdown']) && is_array($mj['counts']['reactions_breakdown'])) {
            foreach ($mj['counts']['reactions_breakdown'] as $emoji=>$cnt) {
                $grouped[$mid]['rows'][$uid]['reactions_breakdown'][$emoji] =
                    ($grouped[$mid]['rows'][$uid]['reactions_breakdown'][$emoji] ?? 0) + (int)$cnt;
            }
        }

        // Status and last seen
        $grouped[$mid]['rows'][$uid]['status'] = $a->isClose() ? 'offline' : 'online';
        if ($a->getOutAt() instanceof DateTimeInterface) {
            $grouped[$mid]['rows'][$uid]['last_seen'] = $a->getOutAt()->format('Y-m-d H:i:s');
        }
    }

    // Totals and max user online seconds per meeting (for progress bar)
    foreach ($grouped as $mid=>&$G) {
        $G['rows'] = array_values($G['rows']);
        foreach ($G['rows'] as $r) {
            $G['totals']['users']++;
            $G['totals']['online_seconds'] += (int)$r['online_seconds'];
            $G['totals']['talk_seconds']   += (int)$r['talk_seconds'];
            $G['totals']['camera_seconds'] += (int)$r['camera_seconds'];
            $G['totals']['messages']       += (int)$r['messages'];
            $G['totals']['reactions']      += (int)$r['reactions'];
            $G['totals']['hands']          += (int)$r['hands'];
            $G['max_online_user_s'] = max($G['max_online_user_s'], (int)$r['online_seconds']);
            foreach ($r['reactions_breakdown'] as $emoji=>$cnt) {
                $G['totals']['reactions_breakdown'][$emoji] =
                    ($G['totals']['reactions_breakdown'][$emoji] ?? 0) + (int)$cnt;
            }
        }
    }
    unset($G);

    return [
        'kpis'    => compact('connected_now','active_meetings','joins_today','events_24h'),
        'grouped' => array_values($grouped),
        'now'     => $now->format('Y-m-d H:i:s'),
    ];
})();

/* --- CSV export --- */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = [];
    foreach ($statsAndData['grouped'] as $G) {
        $rows[] = ['# Meeting', $G['meeting']['title']];
        $rows[] = ['User','Online','Talking','Camera','Messages','Reactions','Hands','Status','First join','Last seen'];
        foreach ($G['rows'] as $r) {
            $rows[] = [
                (string)$r['user'],
                hms((int)$r['online_seconds']),
                hms((int)$r['talk_seconds']),
                hms((int)$r['camera_seconds']),
                (int)$r['messages'],
                (int)$r['reactions'],
                (int)$r['hands'],
                (string)$r['status'],
                (string)$r['first_join'],
                (string)$r['last_seen'],
            ];
        }
        $rows[] = ['Totals',
            hms((int)$G['totals']['online_seconds']),
            hms((int)$G['totals']['talk_seconds']),
            hms((int)$G['totals']['camera_seconds']),
            (int)$G['totals']['messages'],
            (int)$G['totals']['reactions'],
            (int)$G['totals']['hands'],
            '',
            '',
            ''
        ];
        $rows[] = [];
    }
    csv_out('bbb_grouped_dashboard.csv', $rows, []);
}

/* --- AJAX --- */
if (isset($_GET['ajax'])) {
    json_out($statsAndData);
}

/* ===================== HTML ===================== */
$tpl = new Template('[BBB] Webhooks Dashboard (Global)');
ob_start(); ?>
    <style>
        .cards {display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:18px;}
        .card {background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;box-shadow:0 6px 20px rgba(0,0,0,.06);}
        .card .lbl{font-size:12px;color:#64748b}
        .card .val{font-size:28px;font-weight:700;color:#0f172a;margin-top:4px}

        .group   {background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 6px 16px rgba(0,0,0,.05);overflow:hidden;margin-bottom:16px;}
        .g-head  {display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-bottom:1px solid #eef2f7;background:#f8fafc}
        .g-title {font-weight:700;color:#0f172a}
        .badge   {display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;margin-left:8px}
        .online  {background:#e6f8ec;color:#14804a}
        .offline {background:#f1f5f9;color:#334155}

        table{width:100%;border-collapse:collapse}
        th,td{padding:10px 12px;border-bottom:1px solid #eef2f7;text-align:left;font-size:13px}
        thead th{background:#fbfdff;color:#111827}
        .usercell{display:flex;align-items:center;gap:10px}
        .avatar{width:32px;height:32px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#64748b;border:1px solid #e5e7eb}
        .muted{color:#64748b;font-size:12px}

        .bar{position:relative;height:8px;border-radius:999px;background:#eef2f7;overflow:hidden}
        .bar > span{position:absolute;left:0;top:0;height:100%;background:#22c55e}
        .row-meta{display:flex;align-items:center;gap:8px;color:#475569;font-size:12px}
        .chip{display:inline-flex;align-items:center;gap:6px;padding:2px 8px;border-radius:999px;background:#f8fafc;border:1px solid #eef2f7}
        .emoji-list span{margin-right:8px}
        .btn-slim{padding:8px 14px;border:1px solid #64748b;border-radius:8px;color:#334155;text-decoration:none;background:#fff}
        .btn-primary{padding:8px 14px;border-radius:8px;background:#2563eb;color:#fff;border:none}
        .toolbar{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:12px}
        .field{display:flex;flex-direction:column;gap:6px}
        .field input,.field select{padding:6px 10px;border:1px solid #e5e7eb;border-radius:8px}
    </style>

    <div class="toolbar">
        <div class="field">
            <label class="muted">Meeting title contains</label>
            <input id="q" type="text" value="<?php echo htmlspecialchars($qTitle)?>">
        </div>
        <div class="field">
            <label class="muted">From (UTC)</label>
            <input id="from" type="date" value="<?php echo htmlspecialchars($fromStr)?>">
        </div>
        <div class="field">
            <label class="muted">To (UTC)</label>
            <input id="to" type="date" value="<?php echo htmlspecialchars($toStr)?>">
        </div>
        <div class="field">
            <label class="chip"><input id="only_open" type="checkbox" <?php echo $onlyOpen?'checked':''?>> Only online rows</label>
        </div>
        <div class="field">
            <label class="muted">Meeting</label>
            <select id="meeting_id">
                <option value="0">All meetings</option>
                <?php foreach ($meetingOptions as $m): ?>
                    <option value="<?php echo $m['id']?>" <?php echo $meetingFilter===(int)$m['id']?'selected':''?>><?php echo htmlspecialchars($m['title'])?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <div class="field">
        <button id="apply" class="btn-primary">Apply</button>
    </div>
    <div class="field">
        <a id="csv" class="btn-slim">Export CSV</a>
    </div>
    <label class="chip" style="margin-left:auto"><input id="auto" type="checkbox" checked> Auto-refresh (10s)</label>
    </div>

    <div class="cards">
        <?php $k=$statsAndData['kpis']; foreach ([['Active meetings',$k['active_meetings']],['Connected now',$k['connected_now']],['Joins today',$k['joins_today']],['Events (24h)',$k['events_24h']]] as $c): ?>
            <div class="card"><div class="lbl"><?php echo $c[0]?></div><div class="val kpi-val"><?php echo $c[1]?></div></div>
        <?php endforeach; ?>
    </div>

    <div id="groups">
        <?php foreach ($statsAndData['grouped'] as $G): $maxBar = max(1,(int)$G['max_online_user_s']); ?>
            <div class="group" data-meeting="<?php echo $G['meeting']['id']?>">
                <div class="g-head">
                    <div class="g-title">
                        <?php echo htmlspecialchars($G['meeting']['title'])?>
                        <span class="badge <?php echo $G['meeting']['status']==='running'?'online':'offline'?>"><?php echo htmlspecialchars($G['meeting']['status'])?></span>
                    </div>
                    <div class="muted">
                        Users: <?php echo $G['totals']['users']?> —
                        Online: <?php echo hms((int)$G['totals']['online_seconds'])?> —
                        Talk: <?php echo hms((int)$G['totals']['talk_seconds'])?> —
                        Camera: <?php echo hms((int)$G['totals']['camera_seconds'])?>
                    </div>
                </div>

                <div style="overflow:auto">
                    <table>
                        <thead>
                        <tr>
                            <th>USERS</th>
                            <th style="width:260px">ONLINE TIME</th>
                            <th>CONVERSATION</th>
                            <th>CAMERA SHARE</th>
                            <th>MESSAGES</th>
                            <th>REACTIONS</th>
                            <th>HANDS</th>
                            <th>RESULTS</th>
                            <th>STATUS</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($G['rows'] as $r): if ($onlyOpen && $r['status']!=='online') continue;
                            $pct = round(((int)$r['online_seconds'] / $maxBar) * 100);
                            $pct = max(2, min(100, $pct));
                            // Build reactions string (breakdown if available)
                            $rx = '';
                            if (!empty($r['reactions_breakdown'])) {
                                foreach ($r['reactions_breakdown'] as $emo=>$cnt) {
                                    $rx .= '<span>'.$emo.' '.(int)$cnt.'</span> ';
                                }
                                $rx = trim($rx);
                            } else {
                                $rx = (int)$r['reactions'];
                            }
                            ?>
                            <tr>
                                <td>
                                    <div class="usercell">
                                        <div class="avatar">👤</div>
                                        <div>
                                            <div style="font-weight:600;color:#0f172a"><?php echo htmlspecialchars($r['user'])?></div>
                                            <div class="muted">Joined: <?php echo htmlspecialchars($r['first_join'] ?? '—')?> · Last: <?php echo htmlspecialchars($r['last_seen'] ?? '—')?></div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="row-meta" style="margin-bottom:6px">🔊 <?php echo hms((int)$r['online_seconds'])?></div>
                                    <div class="bar"><span style="width:<?php echo $pct?>%"></span></div>
                                </td>

                                <td>
                                    <div class="row-meta">🎙️ <?php echo hms((int)$r['talk_seconds'])?></div>
                                </td>

                                <td>
                                    <div class="row-meta">📷 <?php echo hms((int)$r['camera_seconds'])?></div>
                                </td>

                                <td>
                                    <div class="row-meta">💬 <?php echo  (int)$r['messages'] ?></div>
                                </td>

                                <td>
                                    <div class="row-meta emoji-list"><?php echo $rx ?: '—'?></div>
                                </td>

                                <td>
                                    <div class="row-meta">✋ <?php echo  (int)$r['hands'] ?></div>
                                </td>

                                <td class="muted">N/A</td>

                                <td>
                                    <span class="badge <?php echo $r['status']==='online'?'online':'offline'?>"><?php echo htmlspecialchars(strtoupper($r['status']))?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="muted">Updated: <span id="updatedAt"><?php echo $statsAndData['now']?></span></div>

    <script>
        (function(){
            function esc(s){return String(s||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));}
            function hms(s){s=+s||0;const h=Math.floor(s/3600),m=Math.floor((s%3600)/60),sec=s%60;return String(h).padStart(2,'0')+':'+String(m).padStart(2,'0')+':'+String(sec).padStart(2,'0');}
            function qs(){
                const p=new URLSearchParams();
                const q=document.getElementById('q').value.trim();
                const f=document.getElementById('from').value;
                const t=document.getElementById('to').value;
                const o=document.getElementById('only_open').checked?1:0;
                const m=document.getElementById('meeting_id').value;
                if(q) p.set('q',q);
                if(f) p.set('from',f);
                if(t) p.set('to',t);
                if(o) p.set('only_open','1');
                if(+m>0) p.set('meeting_id',m);
                return p.toString();
            }
            function render(d){
                // KPIs
                const k=d.kpis||{},kpis=document.querySelectorAll('.kpi-val');
                if(kpis[0]) kpis[0].textContent=k.active_meetings??0;
                if(kpis[1]) kpis[1].textContent=k.connected_now??0;
                if(kpis[2]) kpis[2].textContent=k.joins_today??0;
                if(kpis[3]) kpis[3].textContent=k.events_24h??0;
                document.getElementById('updatedAt').textContent=d.now||'';
            }
            async function fetchStats(){
                const r=await fetch('?ajax=1&'+qs(),{credentials:'same-origin'});
                if(!r.ok) return;
                render(await r.json());
                const base=location.pathname, q=qs();
                fetch(base+'?'+q,{credentials:'same-origin'}).then(r=>r.text()).then(html=>{
                    const tmp=document.createElement('div'); tmp.innerHTML=html;
                    const newGroups=tmp.querySelector('#groups'); const newCards=tmp.querySelectorAll('.kpi-val');
                    if(newGroups){ document.querySelector('#groups').replaceWith(newGroups); }
                    const kpis=document.querySelectorAll('.kpi-val');
                    kpis.forEach((el,i)=>{ if(newCards[i]) el.textContent=newCards[i].textContent; });
                    const updated=tmp.querySelector('#updatedAt'); if(updated) document.getElementById('updatedAt').textContent=updated.textContent;
                }).catch(()=>{});
            }
            document.getElementById('apply').addEventListener('click',()=>{
                const base=location.pathname, q=qs();
                history.replaceState(null,'', q? (base+'?'+q) : base);
                fetchStats();
            });
            document.getElementById('csv').addEventListener('click',e=>{
                e.preventDefault(); window.location.href='?export=csv&'+qs();
            });
            let timer=setInterval(fetchStats,10000);
            document.getElementById('auto').addEventListener('change',e=>{
                if(e.target.checked){ timer=setInterval(fetchStats,10000); }
                else{ clearInterval(timer); }
            });
        })();
    </script>
<?php
$html = ob_get_clean();

/* Render */
$tpl->assign('content', $html);
$actionLinks = Display::toolbarButton(
    get_lang('VideoConference'),
    api_get_path(WEB_PLUGIN_PATH).'Bbb/listing.php?global=1&user_id='.api_get_user_id(),
    'video',
    'primary'
);
$tpl->assign('actions', Display::toolbarAction('toolbar', [$actionLinks]));
$tpl->display_one_col_template();
