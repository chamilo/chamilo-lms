<template>
  <Teleport to="body">
    <div
      v-if="canRenderDock"
      class="chd"
    >
      <!-- FAB -->
      <button
        class="chd-fab"
        :class="{ 'has-unread': fabHasUnread }"
        :title="t('Chat')"
        aria-label="Open chat"
        @click="toggleDock(true)"
      >
        <i class="mdi mdi-message-text-outline" />
      </button>

      <!-- Dock -->
      <div
        v-if="open"
        class="chd-dock"
        role="dialog"
        aria-label="Chat dock"
      >
        <header class="chd-header">
          <div class="chd-title">
            <i class="mdi mdi-chat-outline" />
            <span>{{ t("Chat") }}</span>
          </div>
          <div class="chd-actions">
            <button
              class="chd-btn"
              @click="toggleStatus"
            >
              <span
                class="chd-dot"
                :class="userStatus === 1 ? 'chd-dot--on' : 'chd-dot--off'"
              />
              {{ userStatus === 1 ? t("Online") : t("Offline") }}
            </button>
            <button
              class="chd-btn chd-btn--ghost"
              @click="toggleDock(false)"
              aria-label="Close"
            >
              <i class="mdi mdi-close" />
            </button>
          </div>
        </header>

        <section class="chd-body">
          <!-- Contacts -->
          <aside class="chd-sidebar">
            <div class="chd-sidebar__head">
              <strong>{{ t("Contacts") }}</strong>
              <button
                class="chd-btn chd-btn--ghost chd-btn--xs"
                @click="loadContacts"
                :disabled="loadingContacts"
              >
                <i class="mdi mdi-refresh" />
              </button>
            </div>

            <!-- AI Tutor quick entry (only when course context is available) -->
            <div
              v-if="inCourse && tutorCtx.enabled && !contactsHasAiTutor"
              class="chd-ai"
            >
              <button
                class="chd-ai__btn"
                :class="{ 'is-active': Number(activePeer?.id || 0) === AI_PEER_ID }"
                @click="openConversation({ id: AI_PEER_ID, name: t('AI Tutor'), image: '' })"
                :disabled="tutorCtx.inTest"
                :title="tutorCtx.inTest ? t('AI tutor is disabled during tests') : t('AI Tutor')"
              >
                <i class="mdi mdi-robot-outline" />
                <span class="chd-truncate">{{ t("AI Tutor") }}</span>
                <span
                  class="chd-presence on"
                  aria-hidden="true"
                />
              </button>

              <p
                v-if="tutorCtx.inTest"
                class="chd-text--muted chd-ai__hint"
              >
                {{ t("AI tutor is disabled during tests") }}
              </p>
            </div>

            <div
              class="chd-contacts"
              @scroll.passive="onContactsScroll"
            >
              <template v-if="contactsHtml">
                <div
                  class="chd-contacts-html chd-legacy"
                  v-html="contactsHtml"
                  @click.prevent="onContactsClick"
                />
              </template>
              <p
                v-else
                class="chd-text--muted"
              >
                {{ t("No contacts found") }}
              </p>
            </div>
          </aside>

          <!-- Conversation -->
          <main class="chd-chat">
            <div class="chd-chat__head">
              <div class="chd-peer">
                <template v-if="activePeer">
                  <img
                    v-if="activePeerAvatar"
                    :src="activePeerAvatar"
                    class="chd-avatar"
                    alt=""
                  />
                  <i
                    v-else
                    class="mdi mdi-account chd-avatar chd-avatar--fallback"
                    aria-hidden="true"
                  />
                  <div class="chd-peer__meta">
                    <strong class="chd-truncate">{{ activePeer.name }}</strong>
                    <span
                      class="chd-presence"
                      :class="activePeer.online ? 'on' : 'off'"
                    />
                  </div>
                </template>
                <template v-else>
                  <strong>{{ t("Select a contact") }}</strong>
                </template>
              </div>
            </div>

            <!-- Scrollable messages area -->
            <div
              class="chd-chat__body"
              ref="scrollBox"
              @scroll.passive="handleScroll"
            >
              <template v-if="activePeer">
                <div
                  v-for="msg in activeMessages"
                  :key="msg.id"
                  :class="bubbleClass(msg)"
                >
                  <div
                    class="chd-bubble"
                    :class="{ 'is-pending': msg.pending }"
                  >
                    <div
                      class="chd-bubble__content"
                      v-html="renderMessage(msg.message)"
                    />
                    <div class="chd-bubble__meta">
                      <span class="chd-bubble__date">{{ formatTs(msg.date) }}</span>
                      <span
                        v-if="isMine(msg)"
                        class="chd-bubble__ack"
                        :title="ackTitle(msg)"
                      >
                        {{ ackGlyph(msg) }}
                      </span>
                    </div>
                  </div>
                </div>
                <div
                  v-if="activeMessages.length === 0"
                  class="chd-text--muted chd-center chd-py-8"
                >
                  {{ t("No messages yet") }}
                </div>
              </template>
              <div
                v-else
                class="chd-text--muted chd-center chd-py-16"
              >
                {{ t("Pick someone in the left box to start chatting") }}
              </div>
            </div>

            <!-- Composer -->
            <div
              class="chd-composer"
              v-if="activePeer"
            >
              <textarea
                v-model.trim="draft"
                class="chd-input"
                :placeholder="composerPlaceholder"
                rows="2"
                @keydown="onComposerKeydown"
                @keydown.enter.prevent.exact="send"
                @keydown.enter.shift.exact="newline"
              />
              <div class="chd-composer__actions">
                <span class="chd-hint">{{ t("Enter to send · Shift+Enter for newline") }}</span>
                <div class="chd-spacer" />
                <button
                  class="chd-btn chd-btn--danger-outline"
                  @click="clearConversation"
                  :disabled="sending || clearing"
                >
                  {{ t("Reset") }}
                </button>
                <button
                  class="chd-btn chd-btn--primary"
                  @click="send"
                  :disabled="sendDisabled"
                >
                  <i class="mdi mdi-send" /> {{ t("Send") }}
                </button>
              </div>
            </div>
          </main>
        </section>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, onBeforeUnmount, watch, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import { useCidReq } from "../../composables/cidReq"

const { t } = useI18n({ useScope: "global" })
const route = useRoute()

/**
 * cidreq context (course/session/group).
 * We prefer vue-router (route.fullPath) because DockedChat is mounted in App.vue.
 * We still keep a safe fallback to window.location for edge cases.
 */
const cid = ref(0)
const sid = ref(0)
const gid = ref(0)

function toInt(v) {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}

function extractCourseIdFromPath(pathname) {
  const p = String(pathname || "")
  let m = p.match(/\/course\/(\d+)(?:\/|$)/i)
  if (m) return toInt(m[1]) || 0
  m = p.match(/\/courses\/(\d+)(?:\/|$)/i)
  if (m) return toInt(m[1]) || 0
  return 0
}

function readCidReqFromRouteAndLocation() {
  // Try route query first
  const q = route?.query || {}
  const qp = (k) => (q?.[k] ?? "").toString()

  let c = toInt(qp("cid")) || toInt(qp("cidReq")) || toInt(qp("cidreq")) || 0
  let s = toInt(qp("sid")) || 0
  let g = toInt(qp("gid")) || 0

  // Fallback to URL path for routes like /course/5/home
  if (c <= 0) c = extractCourseIdFromPath(route?.path || window.location.pathname)

  // Last fallback: raw window.location search
  if (c <= 0) {
    const sp = new URLSearchParams(window.location.search || "")
    c = toInt(sp.get("cid")) || toInt(sp.get("cidReq")) || toInt(sp.get("cidreq")) || 0
    s = s || toInt(sp.get("sid") ?? "0") || 0
    g = g || toInt(sp.get("gid") ?? "0") || 0
    if (c <= 0) c = extractCourseIdFromPath(window.location.pathname)
  }

  cid.value = c || 0
  sid.value = s || 0
  gid.value = g || 0
}

/**
 * Keep compatibility: if useCidReq() works, bind to it.
 * If it doesn't provide cid for path-based routes, route watcher will override via readCidReqFromRouteAndLocation().
 */
function safeBindCidReq() {
  try {
    const r = useCidReq()
    if (r?.cid && typeof r.cid === "object" && "value" in r.cid) cid.value = toInt(r.cid.value) || 0
    if (r?.sid && typeof r.sid === "object" && "value" in r.sid) sid.value = toInt(r.sid.value) || 0
    if (r?.gid && typeof r.gid === "object" && "value" in r.gid) gid.value = toInt(r.gid.value) || 0
  } catch {
    // No injection/router in some contexts: ignore.
  }
}

safeBindCidReq()
readCidReqFromRouteAndLocation()

function getRuntimeCidReq() {
  // Use the most up-to-date reactive values first.
  let c = toInt(cid.value)
  let s = toInt(sid.value)
  let g = toInt(gid.value)

  // Ensure cid is resolved from path if needed.
  if (c <= 0) c = extractCourseIdFromPath(route?.path || window.location.pathname)
  return { cid: c || 0, sid: s || 0, gid: g || 0 }
}

function runtimeCidReqParams() {
  const { cid: c, sid: s, gid: g } = getRuntimeCidReq()
  if (c <= 0) return {}
  // Keep all aliases to be compatible with legacy endpoints.
  return { cid: c, cidReq: c, cidreq: c, sid: s || 0, gid: g || 0 }
}

watch(
  () => {
    const r = getRuntimeCidReq()
    return `${r.cid}:${r.sid}:${r.gid}`
  },
  async (nv, ov) => {
    if (nv === ov) return
  },
)

/** AI peer id used across backend/services */
const AI_PEER_ID = -1

const openedOnce = ref(false)
const AUTO_OPEN_LAST_PEER = false
const queuedBadgePids = new Set()

const activePeer = ref(null) // { id, name, online, image }
const activePeerAvatar = computed(() => (typeof activePeer.value?.image === "string" ? activePeer.value.image : ""))

const LAST_PEER_KEY = "chd:lastPeerId"
watch(activePeer, (v) => {
  if (v?.id) localStorage.setItem(LAST_PEER_KEY, String(v.id))
})

function isAssignmentsPage() {
  const p = String(window.location.pathname || "")
  const q = String(window.location.search || "")
  return (
    p.includes("/work/") ||
    p.includes("/student_publication/") ||
    p.endsWith("/work.php") ||
    q.includes("work.php") ||
    q.includes("student_publication")
  )
}

function isExercisePage() {
  const p = String(window.location.pathname || "")
  const q = String(window.location.search || "")
  return (
    p.includes("/exercise/") ||
    p.endsWith("/exercise.php") ||
    q.includes("exercise.php") ||
    (q.includes("cidReq") && (q.includes("exercise") || q.includes("lp_id")))
  )
}

const inCourse = computed(() => getRuntimeCidReq().cid > 0)

const dockEnabled = computed(() => {
  const bodyVal = document?.body?.dataset?.chatDockEnabled
  const v = window?.CHAMILO_CHAT_DOCK_ENABLED ?? window?.CHAT_DOCK_ENABLED ?? window?.CHAT_DOCKED_ENABLED ?? bodyVal
  if (v === undefined || v === null || v === "") return true
  if (typeof v === "string") return /^(1|true|on|yes)$/i.test(v)
  return !!v
})

const canRenderDock = computed(() => {
  if (!dockEnabled.value) return false
  if (isAssignmentsPage()) return false
  if (isExercisePage()) return false
  return true
})

function RG(candidates, fallback) {
  for (const name of candidates) {
    try {
      const u = window.Routing?.generate(name)
      if (u) return u
    } catch {
      // ignore
    }
  }
  return fallback
}

const API = {
  start: RG(["chat_api_start", "chamilo_core_chat_api_start"], "/account/chat/api/start"),
  contacts: RG(["chat_api_contacts", "chamilo_core_chat_api_contacts"], "/account/chat/api/contacts"),
  heartbeat: RG(["chat_api_heartbeat", "chamilo_core_chat_api_heartbeat"], "/account/chat/api/heartbeat"),
  send: RG(["chat_api_send", "chamilo_core_chat_api_send"], "/account/chat/api/send"),
  status: RG(["chat_api_status", "chamilo_core_chat_api_status"], "/account/chat/api/status"),
  history: RG(["chat_api_history", "chamilo_core_chat_api_history"], "/account/chat/api/history"),
  history_since: RG(
    ["chat_api_history_since", "chamilo_core_chat_api_history_since"],
    "/account/chat/api/history_since",
  ),
  preview: RG(["chat_api_preview", "chamilo_core_chat_api_preview"], "/account/chat/api/preview"),
  presence: RG(["chat_api_presence", "chamilo_core_chat_api_presence"], "/account/chat/api/presence"),
  ack: RG(["chat_api_ack", "chamilo_core_chat_api_ack"], "/account/chat/api/ack"),
  tutor_context: RG(
    ["chat_api_tutor_context", "chamilo_core_chat_api_tutor_context"],
    "/account/chat/api/tutor_context",
  ),
  tutor_reset: RG(["chat_api_tutor_reset", "chamilo_core_chat_api_tutor_reset"], "/account/chat/api/tutor/reset"),
}

/** ===== State ===== */
const open = ref(false)
const userStatus = ref(0)
const me = reactive({ id: 0, name: "", secToken: "" })

const contactsHtml = ref("")
const loadingContacts = ref(false)

const messagesByPeer = reactive(new Map())
const fetchingPrev = ref(false)

const draft = ref("")
const sending = ref(false)
const clearing = ref(false)

const scrollBox = ref(null)

/** Tutor context (backend-authoritative) */
const tutorCtx = reactive({
  loaded: false,
  enabled: false, // TRUE only when backend says enabled (course-aware)
  inTest: false,
  course: null, // { id, title, language }
  provider: "", // default provider key
})

/** Unread tracking */
const unreadByPeer = reactive(new Map())
const clearedAtByPeer = reactive(new Map())
const lastSeenMsgIdByPeer = reactive(new Map())

const fabUnread = ref(0)
const unreadTotal = computed(() => {
  let s = 0
  unreadByPeer.forEach((v) => (s += Number(v || 0)))
  return s
})
const fabHasUnread = computed(() => unreadTotal.value > 0 || fabUnread.value > 0)

let hbTimer = null
let contactsTimer = null

function qs(obj) {
  return new URLSearchParams(obj).toString()
}

function withCidReq(params) {
  const extra = runtimeCidReqParams()
  if (!Object.keys(extra).length) return params || {}
  return { ...(params || {}), ...extra }
}

function addCidReqToUrl(url) {
  try {
    const u = new URL(url, window.location.origin)
    const p = runtimeCidReqParams()
    Object.entries(p).forEach(([k, v]) => u.searchParams.set(k, String(v)))
    return u.toString()
  } catch {
    return url
  }
}

const contactsHasAiTutor = computed(() => {
  const html = String(contactsHtml.value || "")
  if (!html) return false
  return (
    /data-(?:user|id|uid|peer|contact-id)\s*=\s*["']-1["']/i.test(html) ||
    /\b(peer_id|user_id|uid)\s*=\s*-1\b/i.test(html) ||
    /(AI\s*Tutor)/i.test(html)
  )
})

function normalizeContactsHtmlForAiTutor(html) {
  const raw = String(html || "")
  if (!raw) return ""

  const wrap = document.createElement("div")
  wrap.innerHTML = raw

  // Remove any existing AI tutor rows first (different possible markers).
  wrap
    .querySelectorAll(
      '.chd-ai-contact,[data-user="-1"],[data-id="-1"],[data-uid="-1"],[data-user-id="-1"],[data-id-user="-1"]',
    )
    .forEach((n) => n.remove())

  const shouldShowAi = !!tutorCtx.enabled && !!inCourse.value && !tutorCtx.inTest

  if (shouldShowAi) {
    const row = document.createElement("div")
    row.className = "chd-ai-contact"
    row.dataset.user = String(AI_PEER_ID)
    row.setAttribute("data-name", "AI Tutor")
    row.style.cursor = "pointer"
    row.style.padding = "10px"
    row.style.borderBottom = "1px solid #eee"
    row.innerHTML = `
      <span style="margin-right:8px;">🤖</span>
      <strong>AI Tutor</strong>
      <span style="float:right; color:#2e7d32;">●</span>
    `
    wrap.prepend(row)
  }

  return wrap.innerHTML
}

function linkify(str) {
  return (str || "").replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>')
}
function renderMessage(html) {
  return linkify(html)
}
function formatTs(ts) {
  const d = Number(ts) * 1000
  if (!Number.isFinite(d)) return ""
  try {
    return new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" }).format(new Date(d))
  } catch {
    return ""
  }
}
function bubbleClass(msg) {
  const mine = Number(msg.from_user_info?.id) === me.id || Number(msg?.f) === me.id
  return mine ? "chd-row chd-row--me" : "chd-row chd-row--peer"
}
function handleScroll(e) {
  onScrollUpLoadMore(e)
  onBodyScrollForRead()
}
function escapeForHtml(s) {
  return (s || "").replace(
    /[&<>"']/g,
    (m) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" })[m],
  )
}
function isMine(m) {
  return Number(m?.from_user_info?.id) === me.id || Number(m?.f) === me.id
}

function ackGlyph(msg) {
  const v = Number(msg?.recd ?? 0)
  if (v >= 2) return "✓✓"
  if (v >= 1) return "✓"
  return ""
}
function ackTitle(msg) {
  const v = Number(msg?.recd ?? 0)
  if (v >= 2) return t("Read")
  if (v >= 1) return t("Delivered")
  return t("Sending")
}

async function getJSON(url, params) {
  const full = params ? `${url}?${qs(withCidReq(params))}` : addCidReqToUrl(url)
  const r = await fetch(full, { credentials: "same-origin" })
  if (!r.ok) throw new Error("Network error")
  return r.json()
}

async function post(url, params, expectJson = true) {
  const fullUrl = addCidReqToUrl(url)
  const r = await fetch(fullUrl, {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
    body: new URLSearchParams(withCidReq(params || {})),
  })
  if (!r.ok) throw new Error("Network error")
  return expectJson ? r.json() : r.text()
}

function byChronoId(a, b) {
  const da = Number(a?.date) || 0
  const db = Number(b?.date) || 0
  if (da !== db) return da - db
  const aid = Number(a?.id) || 0
  const bid = Number(b?.id) || 0
  const aneg = aid < 0
  const bneg = bid < 0
  if (aneg !== bneg) return aneg ? 1 : -1
  return aid - bid
}

function normalizeItems(raw) {
  const list = Array.isArray(raw) ? raw.slice() : Object.values(raw || {})
  return list.sort(byChronoId)
}
function filterAfterClear(pid, list) {
  const cut = clearedAtByPeer.get(pid)
  if (!cut) return list
  return list.filter((m) => Number(m?.date) > cut)
}

/** Pending optimistic messages */
const pendingByPeer = reactive(new Map())
function addPending(pid, tempId, msg, ts) {
  const arr = pendingByPeer.get(pid) || []
  arr.push({ id: tempId, msg: String(msg || ""), ts: Number(ts) })
  pendingByPeer.set(pid, arr)
}
function removePending(pid, tempId) {
  const arr = pendingByPeer.get(pid) || []
  const idx = arr.findIndex((x) => x.id === tempId)
  if (idx >= 0) arr.splice(idx, 1)
  pendingByPeer.set(pid, arr)
}
function replaceTempId(pid, tempId, newMsg) {
  const arr = messagesByPeer.get(pid) || []
  const i = arr.findIndex((m) => m.id === tempId)
  if (i >= 0) {
    arr[i] = { ...arr[i], ...newMsg, id: Number(newMsg.id), pending: false }
    messagesByPeer.set(pid, arr.slice().sort(byChronoId))
  }
}

function textToPlain(s) {
  const el = document.createElement("div")
  el.innerHTML = String(s ?? "")
  return (el.textContent || "").trim()
}
function sameContent(a, b) {
  const textA = textToPlain(a?.message ?? a?.m ?? "")
  const textB = textToPlain(b?.message ?? b?.m ?? "")
  if (textA !== textB) return false
  const ta = Number(a?.date) || 0
  const tb = Number(b?.date) || 0
  return Math.abs(ta - tb) <= 10
}
function adoptPendingFromServer(pid, srvList, prevList) {
  const pend = pendingByPeer.get(pid) || []
  if (!pend.length) return new Set()
  const adopted = new Set()
  for (const srv of srvList) {
    if (!isMine(srv)) continue
    const hit = pend.find((p) => sameContent(srv, { message: p.msg, date: srv.date }))
    if (!hit) continue
    const tempIdx = prevList.findIndex((m) => m.id === hit.id)
    if (tempIdx >= 0) {
      prevList[tempIdx] = { ...prevList[tempIdx], ...srv, id: Number(srv.id), pending: false }
      adopted.add(Number(srv.id))
      removePending(pid, hit.id)
    }
  }
  if (adopted.size) messagesByPeer.set(pid, prevList.slice())
  return adopted
}
function dedupeNewWithPending(pid, newList) {
  const pend = pendingByPeer.get(pid) || []
  if (!pend.length) return newList
  return newList.filter((srv) => {
    if (!isMine(srv)) return true
    const same = pend.find((p) => sameContent(srv, { message: p.msg, date: srv.date }))
    return !same
  })
}

/** Unread helpers */
function incUnread(pid, delta = 1) {
  const curr = Number(unreadByPeer.get(pid) || 0)
  unreadByPeer.set(pid, curr + delta)
  requestAnimationFrame(() => updateContactUnreadBadge(pid))
  maybeStartBlink()
}
function resetUnread(pid) {
  unreadByPeer.set(pid, 0)
  requestAnimationFrame(() => updateContactUnreadBadge(pid))
  maybeStopBlink()
}

function isAtBottom(el, tolerance = 24) {
  if (!el) return false
  return el.scrollHeight - el.scrollTop - el.clientHeight <= tolerance
}

async function markActiveAsRead() {
  if (!activePeer.value) return
  const pid = Number(activePeer.value.id || 0)
  const arr = messagesByPeer.get(pid) || []
  if (!arr.length) return

  const last = Number(arr[arr.length - 1]?.id || 0)
  const lastSeenPrev = Number(lastSeenMsgIdByPeer.get(pid) || 0)
  if (last <= 0 || last <= lastSeenPrev) return

  lastSeenMsgIdByPeer.set(pid, last)
  resetUnread(pid)

  // AI tutor is not part of classic chat ack.
  if (pid <= 0) return

  try {
    await post(API.ack, { peer_id: pid, last_seen_id: last })
  } catch {
    // ignore
  }
}

let ackDebounceTimer = null
function debounceMarkAsRead(ms = 180) {
  clearTimeout(ackDebounceTimer)
  ackDebounceTimer = setTimeout(() => markActiveAsRead(), ms)
}

function isActuallyViewingActivePeer() {
  const el = scrollBox.value
  return open.value && document.hasFocus() && !!activePeer.value && !!el && el.clientHeight > 0 && isAtBottom(el, 24)
}
function maybeMarkAsReadOnView() {
  if (isActuallyViewingActivePeer()) debounceMarkAsRead()
}

function onScrollUpLoadMore(e) {
  const el = e.target
  if (!activePeer.value || fetchingPrev.value) return
  if (el.scrollTop <= 0) getPreviousMessages()
}
function onBodyScrollForRead() {
  if (!activePeer.value) return
  maybeMarkAsReadOnView()
}

/** Contacts DOM helpers */
function extractPeerIdFromNode(node) {
  if (!node) return 0
  const ds = node.dataset || {}
  const cand = Number(ds.user || ds.id || ds.userId || ds.idUser || ds.uid || ds.friend || ds.contactId || 0)
  if (cand) return cand
  if (node.matches?.("a[href]")) {
    try {
      const u = new URL(node.getAttribute("href"), window.location.origin)
      const p = Number(
        u.searchParams.get("user") ||
          u.searchParams.get("id") ||
          u.searchParams.get("uid") ||
          u.searchParams.get("friend") ||
          u.searchParams.get("contact") ||
          0,
      )
      if (p) return p
    } catch {
      // ignore
    }
  }
  const oc = node.getAttribute?.("onclick") || ""
  if (oc) {
    const m = oc.match(/(?:chatWith|openChat|startChat)\s*\(\s*['"]?(\d+)['"]?/i)
    if (m) return Number(m[1])
  }
  const idAttr = node.id || ""
  if (idAttr) {
    const m2 = idAttr.match(/(?:friend|user|contact|person|peer)[_-]?(\d+)/i)
    if (m2) return Number(m2[1])
  }
  return 0
}

function findContactNodesByPeerId(root, pid) {
  if (!root || !pid) return []
  const candidates = Array.from(
    root.querySelectorAll(
      "[data-user],[data-id],[data-user-id],[data-id-user],[data-uid],[data-friend],[data-contact-id],a[href],[onclick],[id]",
    ),
  )
  return candidates
    .filter((el) => extractPeerIdFromNode(el) === pid)
    .map((el) => {
      const row =
        el.closest(".chd-contact-row") || el.closest(".list-group-item, .media, .panel, .panel-body, li, tr, div") || el
      row.classList.add("chd-contact-row")
      return row
    })
}

function setRowUnreadDot(row, hasUnread) {
  if (!row) return
  let dot = row.querySelector(":scope > .chd-contact-dot")
  if (hasUnread) {
    if (!dot) {
      dot = document.createElement("span")
      dot.className = "chd-contact-dot"
      row.appendChild(dot)
    }
  } else {
    if (dot) dot.remove()
  }
}

function updateContactUnreadBadge(pid) {
  const root = document.querySelector(".chd .chd-contacts .chd-contacts-html")
  if (!root) {
    queuedBadgePids.add(pid)
    return
  }
  const count = Number(unreadByPeer.get(pid) || 0)
  const rows = findContactNodesByPeerId(root, pid)
  rows.forEach((row) => setRowUnreadDot(row, count > 0))
}

function repaintAllContactBadges() {
  const root = document.querySelector(".chd .chd-contacts .chd-contacts-html")
  if (!root) return
  root.querySelectorAll(".chd-contact-dot").forEach((n) => n.remove())
  unreadByPeer.forEach((count, pid) => {
    if (Number(count || 0) > 0) updateContactUnreadBadge(Number(pid))
  })
  if (queuedBadgePids.size) {
    queuedBadgePids.forEach((pid) => updateContactUnreadBadge(pid))
    queuedBadgePids.clear()
  }
}

function collectVisibleContactIds() {
  const root = document.querySelector(".chd .chd-contacts .chd-contacts-html")
  const ids = new Set()

  // Only include AI Tutor when backend says it is enabled (course-aware).
  if (tutorCtx.enabled && inCourse.value) ids.add(AI_PEER_ID)

  if (root) {
    root
      .querySelectorAll("[data-user],[data-id],[data-user-id],[data-id-user],a[href],[onclick],[id]")
      .forEach((el) => {
        const id = extractPeerIdFromNode(el)
        if (id) ids.add(id)
      })
  }

  if (activePeer.value?.id) ids.add(activePeer.value.id)
  return Array.from(ids)
}

function paintPresenceOnContacts(map) {
  const root = document.querySelector(".chd .chd-contacts .chd-contacts-html")
  if (!root) return
  Object.entries(map || {}).forEach(([sid, online]) => {
    const pid = Number(sid)
    const rows = findContactNodesByPeerId(root, pid)
    rows.forEach((row) => {
      const icon =
        row.querySelector("i.mdi-account-check") ||
        row.querySelector("i.mdi-account-outline") ||
        row.querySelector('i[class*="mdi-account"]')
      if (icon) {
        icon.classList.toggle("mdi-account-check", !!online)
        icon.classList.toggle("mdi-account-outline", !online)
        icon.classList.toggle("is-online", !!online)
        icon.classList.toggle("is-offline", !online)
      }
    })
  })
}

/** Tutor context */
async function loadTutorContext() {
  tutorCtx.loaded = false
  tutorCtx.enabled = false
  tutorCtx.inTest = false
  tutorCtx.course = null
  tutorCtx.provider = ""

  try {
    const r = await getJSON(API.tutor_context)
    tutorCtx.enabled = !!r?.enabled
    tutorCtx.inTest = !!r?.in_test
    tutorCtx.course = r?.course || null
    tutorCtx.provider = typeof r?.provider === "string" ? r.provider : ""
  } catch {
    tutorCtx.enabled = false
    tutorCtx.inTest = false
    tutorCtx.course = null
    tutorCtx.provider = ""
  } finally {
    tutorCtx.loaded = true
  }
}

async function startSession() {
  const data = await getJSON(API.start)
  if (!data) return
  me.name = data.me || ""
  me.id = Number(data.user_id || 0)
  userStatus.value = Number(data.user_status || 0)
  me.secToken = data.sec_token || data.chat_sec_token || ""

  if (data.items) {
    Object.entries(data.items).forEach(([peerId, userItems]) => {
      const pid = Number(peerId)
      // Ignore AI peer state from classic chat payload
      if (pid <= 0) return
      const arr = filterAfterClear(pid, normalizeItems(userItems?.items ?? []))
      messagesByPeer.set(pid, arr)
      if (arr.length) lastSeenMsgIdByPeer.set(pid, arr[arr.length - 1].id)
    })
  }
}

async function loadContacts() {
  loadingContacts.value = true
  try {
    const html = await post(API.contacts, { to: "user_id" }, false)
    contactsHtml.value = normalizeContactsHtmlForAiTutor(String(html || ""))
    requestAnimationFrame(() => repaintAllContactBadges())
  } finally {
    loadingContacts.value = false
  }
}

function onContactsClick(e) {
  const a = e.target.closest("a[href]")
  if (a) {
    e.preventDefault()
    e.stopPropagation()
  }

  let el = e.target.closest("[data-user],[data-id],[data-user-id],[data-id-user]")
  if (el) {
    const userId = Number(el.dataset.user || el.dataset.id || el.dataset.userId || el.dataset.idUser || 0)
    const name = el.getAttribute("data-name") || el.textContent?.trim() || "User"
    const image = el.getAttribute("data-image") || ""
    if (userId) return openConversation({ id: userId, name, image })
  }

  el = e.target.closest("[onclick]")
  if (el) {
    const oc = el.getAttribute("onclick") || ""
    const m =
      oc.match(/chatWith\s*\(\s*['"]?(\d+)['"]?\s*[,\)]/i) ||
      oc.match(/openChat\s*\(\s*['"]?(\d+)['"]?\s*[,\)]/i) ||
      oc.match(/startChat\s*\(\s*['"]?(\d+)['"]?\s*[,\)]/i)
    if (m) {
      const userId = Number(m[1])
      const name = el.getAttribute("data-name") || el.textContent?.trim() || "User"
      return openConversation({ id: userId, name })
    }
  }

  if (a) {
    try {
      const u = new URL(a.getAttribute("href"), window.location.origin)
      const id = Number(u.searchParams.get("user") || u.searchParams.get("id") || 0)
      if (id) {
        const name = a.getAttribute("data-name") || a.textContent?.trim() || "User"
        return openConversation({ id, name })
      }
    } catch {
      // ignore
    }
  }

  el = e.target.closest("[id]")
  if (el) {
    const m = (el.id || "").match(/(?:friend|user|contact)[_-]?(\d+)/i)
    if (m) {
      const userId = Number(m[1])
      const name = el.getAttribute("title") || el.getAttribute("data-name") || el.textContent?.trim() || "User"
      return openConversation({ id: userId, name })
    }
  }
}

function pickOnline() {
  return undefined
}

function aiQueryParamsIfNeeded(pid) {
  if (Number(pid) !== AI_PEER_ID) return {}
  return tutorCtx.provider ? { ai_provider: tutorCtx.provider } : {}
}

const lastIdByPeer = reactive(new Map())

function resetAiThreadCache() {
  messagesByPeer.delete(AI_PEER_ID)
  unreadByPeer.set(AI_PEER_ID, 0)
  lastSeenMsgIdByPeer.set(AI_PEER_ID, 0)
  lastIdByPeer.set(AI_PEER_ID, 0)
}

async function openConversation(peer) {
  const pid = Number(peer.id)

  // Guard: AI Tutor must be course-only and enabled by backend.
  if (pid === AI_PEER_ID && (!tutorCtx.enabled || !inCourse.value || tutorCtx.inTest)) return

  activePeer.value = {
    id: pid,
    name: peer.name || "User",
    image: peer.image || "",
    online: pid === AI_PEER_ID ? true : (pickOnline(pid) ?? false),
  }

  const existing = messagesByPeer.get(pid)
  if (!existing || existing.length === 0) {
    await getPreviousMessages()
  }

  requestAnimationFrame(() => {
    const el = scrollBox.value
    if (el) el.scrollTop = el.scrollHeight
    maybeMarkAsReadOnView()
  })

  lastIdByPeer.set(pid, lastKnownIdForPeer(pid))
  maybeResetScheduler()
}

async function getPreviousMessages() {
  if (!activePeer.value) return
  const pid = activePeer.value.id
  const current = messagesByPeer.get(pid) || []
  const visible = current.length
  fetchingPrev.value = true
  try {
    const items = await getJSON(API.history, { user_id: pid, visible_messages: visible, ...aiQueryParamsIfNeeded(pid) })
    const list = filterAfterClear(pid, normalizeItems(items))
    if (list.length) {
      const merged = [...list, ...current].sort(byChronoId)
      messagesByPeer.set(pid, merged)
      requestAnimationFrame(() => {
        const el = scrollBox.value
        if (el) el.scrollTop = 1
      })
    }
  } finally {
    fetchingPrev.value = false
  }
}

function lastKnownIdForPeer(pid) {
  const arr = messagesByPeer.get(pid) || []
  for (let i = arr.length - 1; i >= 0; i--) {
    const id = Number(arr[i]?.id)
    if (id > 0) return id
  }
  return Number(lastIdByPeer.get(pid) || 0) || 0
}

async function fetchNewForActivePeer(pid) {
  const since = lastKnownIdForPeer(pid)
  const items = await getJSON(API.history_since, { user_id: pid, since_id: since, ...aiQueryParamsIfNeeded(pid) })
  const list = normalizeItems(items)
  if (!list.length) return

  const prev = messagesByPeer.get(pid) || []
  const adopted = adoptPendingFromServer(pid, list, prev)

  let newOnes = list.filter((x) => !prev.find((p) => p.id === x.id && !adopted.has(Number(x.id))))
  newOnes = dedupeNewWithPending(pid, newOnes)
  if (!newOnes.length) return

  const merged = [...prev, ...newOnes].sort(byChronoId)
  messagesByPeer.set(pid, merged)
  lastIdByPeer.set(pid, Number(merged[merged.length - 1].id) || since)

  const viewing = open.value && activePeer.value?.id === pid && isAtBottom(scrollBox.value) && document.hasFocus()
  if (!viewing) {
    // Do not count unread for AI Tutor.
    if (pid !== AI_PEER_ID) incUnread(pid, newOnes.filter((m) => !isMine(m)).length || 0)
  } else {
    maybeMarkAsReadOnView()
    requestAnimationFrame(() => {
      const el = scrollBox.value
      if (el) el.scrollTop = el.scrollHeight
    })
  }
}

/** Backoff scheduler (kept) */
function createBackoffScheduler({ onTick, onReset, getOverrideDelayMs } = {}) {
  const phases = [
    { ms: 1000, repeats: 5 },
    { ms: 5000, durationMs: 30000 },
    { ms: 10000, durationMs: 120000 },
    { ms: 30000, repeats: Infinity },
  ]
  let timer = null
  let aborted = false
  let idx = 0
  let ticks = 0
  let started = 0

  function phaseMs() {
    const p = phases[idx] || phases[phases.length - 1]
    const base = p.ms
    const override = getOverrideDelayMs?.(base)
    return Number.isFinite(override) && override > 0 ? override : base
  }

  function scheduleNext() {
    if (aborted) return
    timer = setTimeout(async () => {
      try {
        await onTick?.()
      } catch {
        // ignore
      }
      advancePhase()
      scheduleNext()
    }, phaseMs())
  }

  function advancePhase() {
    const p = phases[idx]
    ticks++
    if (p.repeats === Infinity) return
    if (typeof p.repeats === "number" && ticks >= p.repeats) {
      idx++
      ticks = 0
      started = performance.now()
      return
    }
    if (typeof p.durationMs === "number" && performance.now() - started >= p.durationMs) {
      idx++
      ticks = 0
      started = performance.now()
    }
  }

  function start() {
    aborted = false
    idx = 0
    ticks = 0
    started = performance.now()
    scheduleNext()
  }

  function reset() {
    stop()
    onReset?.()
    start()
  }

  function stop() {
    aborted = true
    if (timer) {
      clearTimeout(timer)
      timer = null
    }
  }

  return { start, stop, reset }
}

const lastHeartbeatId = ref(0)
let noChangeTicks = 0

function computeGlobalLastId() {
  let maxId = 0
  messagesByPeer.forEach((arr) => {
    if (arr?.length) maxId = Math.max(maxId, Number(arr[arr.length - 1].id) || 0)
  })
  return maxId
}

function shouldPoll() {
  return userStatus.value === 1
}

let lastSchedulerResetAt = 0
function maybeResetScheduler() {
  if (!open.value || !document.hasFocus()) return
  const now = Date.now()
  if (now - lastSchedulerResetAt < 5000) return
  lastSchedulerResetAt = now
  scheduler.reset()
}

function syncUnreadFromServer(payload) {
  const map = payload?.unread_by_peer
  if (!map) return

  Object.entries(map).forEach(([sid, count]) => {
    const pid = Number(sid)
    // Ignore non-positive peers (including AI Tutor = -1).
    if (!Number.isFinite(pid) || pid <= 0) return
    unreadByPeer.set(pid, Number(count) || 0)
  })

  requestAnimationFrame(() => repaintAllContactBadges())
}

async function heartbeatMinTick() {
  if (!shouldPoll()) return

  const presenceIds = collectVisibleContactIds()
  const presenceParam = presenceIds.length > 0 ? { presence_ids: JSON.stringify(presenceIds) } : {}

  const pid = activePeer.value?.id
  if (pid) {
    try {
      const since = lastKnownIdForPeer(pid)
      const params = { mode: "tiny", peer_id: pid, since_id: since, ...presenceParam }
      const r = await getJSON(API.heartbeat, params)

      const latest = Number(r?.last_id || 0)
      if (latest > since) {
        await fetchNewForActivePeer(pid)
        lastIdByPeer.set(pid, latest)
        noChangeTicks = 0
      } else {
        noChangeTicks++
      }

      if (r?.presence) paintPresenceOnContacts(r.presence)
    } catch {
      // ignore
    }
    return
  }

  try {
    const since = lastHeartbeatId.value || computeGlobalLastId()
    const params = { mode: "min", since_id: since, ...presenceParam }
    const r = await getJSON(API.heartbeat, params)

    syncUnreadFromServer(r)
    if (r?.presence) paintPresenceOnContacts(r.presence)

    const srvLast = Number(r?.last_id ?? 0)
    const hasNew = !!r?.has_new || srvLast > since

    // Prefer local computed total instead of trusting server "unread".
    fabUnread.value = unreadTotal.value

    if (hasNew) {
      lastHeartbeatId.value = Math.max(srvLast || 0, computeGlobalLastId(), since)
      noChangeTicks = 0
    } else {
      noChangeTicks++
    }
  } catch {
    // ignore
  }
}

const scheduler = createBackoffScheduler({
  onTick: heartbeatMinTick,
  onReset: () => {},
  getOverrideDelayMs: (baseMs) => {
    if (!open.value) return Math.min(Math.max(baseMs, 10000), 30000)
    return baseMs
  },
})

function startHeartbeat() {
  scheduler.stop()
  scheduler.start()
}
function stopHeartbeat() {
  scheduler.stop()
  if (hbTimer) clearTimeout(hbTimer)
  hbTimer = null
}

async function goOnline() {
  await post(API.status, { status: 1 })
  userStatus.value = 1
}
async function toggleStatus() {
  const newStatus = userStatus.value === 1 ? 0 : 1
  await post(API.status, { status: newStatus })
  userStatus.value = newStatus
  if (newStatus === 1) startHeartbeat()
  else stopHeartbeat()
}

const isAiThread = computed(() => Number(activePeer.value?.id || 0) === AI_PEER_ID)

const composerPlaceholder = computed(() => {
  if (isAiThread.value && tutorCtx.inTest) return t("AI tutor is disabled during tests")
  return t("Write a message")
})

const sendDisabled = computed(() => {
  if (!draft.value) return true
  if (sending.value) return true
  if (userStatus.value !== 1) return true
  if (isAiThread.value && tutorCtx.inTest) return true
  if (isAiThread.value && (!tutorCtx.enabled || !inCourse.value)) return true
  return false
})

async function send() {
  if (!activePeer.value || !draft.value) return

  const pid = Number(activePeer.value.id)
  const raw = draft.value

  if (pid === AI_PEER_ID) {
    if (tutorCtx.inTest) return
    if (!tutorCtx.enabled || !inCourse.value) return
  }

  const msgEscaped = escapeForHtml(raw)
  const nowSec = Math.floor(Date.now() / 1000)

  const tempId = -Date.now()
  const optimistic = {
    from_user_info: { id: me.id, complete_name: "me" },
    username: me.name,
    date: nowSec,
    f: me.id,
    message: msgEscaped,
    id: tempId,
    recd: 0,
    pending: true,
  }

  const arr = messagesByPeer.get(pid) || []
  messagesByPeer.set(pid, [...arr, optimistic])
  addPending(pid, tempId, raw, nowSec)

  requestAnimationFrame(() => {
    const el = scrollBox.value
    if (el) el.scrollTop = el.scrollHeight
  })

  draft.value = ""
  sending.value = true

  try {
    const extra = pid === AI_PEER_ID ? { ai_provider: tutorCtx.provider } : {}
    const res = await post(API.send, { to: pid, message: raw, chat_sec_token: me.secToken, ...extra })

    if (res?.assistant?.id) {
      const arr2 = messagesByPeer.get(pid) || []
      if (!arr2.find((m) => Number(m.id) === Number(res.assistant.id))) {
        messagesByPeer.set(pid, [...arr2, res.assistant].sort(byChronoId))
      }
      requestAnimationFrame(() => {
        const el = scrollBox.value
        if (el) el.scrollTop = el.scrollHeight
      })
    }

    if (res && typeof res === "object" && Number(res.id) > 0) {
      if (res.sec_token) me.secToken = res.sec_token
      replaceTempId(pid, tempId, { id: Number(res.id), recd: 2, date: nowSec })
      removePending(pid, tempId)
      return
    }
  } catch {
    // keep pending bubble
  } finally {
    sending.value = false
  }
}

async function clearConversation() {
  if (!activePeer.value) return
  if (!confirm(t("Are you sure you want to clear this conversation?"))) return
  clearing.value = true
  try {
    const pid = activePeer.value.id

    if (Number(pid) === AI_PEER_ID) {
      // Server reset for AI tutor (course-only)
      await post(API.tutor_reset, { ai_provider: tutorCtx.provider || "" })
      resetAiThreadCache()
      await getPreviousMessages()
    } else {
      const nowSec = Math.floor(Date.now() / 1000)
      clearedAtByPeer.set(pid, nowSec)
      messagesByPeer.set(pid, [])
      resetUnread(pid)
    }

    requestAnimationFrame(() => {
      const el = scrollBox.value
      if (el) el.scrollTop = el.scrollHeight
    })
  } catch {
    // ignore
  } finally {
    clearing.value = false
  }
}

const activeMessages = computed(() => {
  if (!activePeer.value) return []
  return messagesByPeer.get(activePeer.value.id) || []
})

/** Title blink */
const originalTitle = document.title
let blinkTimer = null
let blinkState = false

function applyBlinkFrame() {
  const total = unreadTotal.value
  if (!total || !document.hidden) {
    stopBlinkNow()
    return
  }
  blinkState = !blinkState
  document.title = blinkState ? `(${total}) ${t("New messages")}` : originalTitle
}
function maybeStartBlink() {
  if (!document.hidden) return
  if (blinkTimer) return
  blinkTimer = setInterval(applyBlinkFrame, 1200)
}
function maybeStopBlink() {
  if (unreadTotal.value > 0 && document.hidden) return
  stopBlinkNow()
}
function stopBlinkNow() {
  if (blinkTimer) {
    clearInterval(blinkTimer)
    blinkTimer = null
  }
  document.title = originalTitle
  blinkState = false
}

document.addEventListener("visibilitychange", () => {
  if (document.hidden) maybeStartBlink()
  else {
    maybeStopBlink()
    maybeMarkAsReadOnView()
  }
})
window.addEventListener("focus", () => {
  maybeStopBlink()
  maybeMarkAsReadOnView()
})
window.addEventListener("online", () => {
  if (userStatus.value === 1) startHeartbeat()
})
window.addEventListener("offline", () => scheduler.stop())

function registerLegacyChatGlobals() {
  const handler = (id, name) => {
    try {
      if (!open.value) toggleDock(true)
      openConversation({ id: Number(id), name: name || "User" })
    } catch {
      // ignore
    }
    return false
  }
  if (!("chatWith" in window)) window.chatWith = handler
  if (!("openChat" in window)) window.openChat = handler
  if (!("startChat" in window)) window.startChat = handler
}

function recomputeUnreadFromLocal() {
  let total = 0
  const pids = new Set([...Array.from(messagesByPeer.keys()), ...Array.from(lastSeenMsgIdByPeer.keys())])

  pids.forEach((pid0) => {
    const pid = Number(pid0)
    // Ignore AI tutor in local unread computation.
    if (pid <= 0) return

    const arr = messagesByPeer.get(pid) || []
    const lastSeen = Number(lastSeenMsgIdByPeer.get(pid) || 0)
    const cut = Number(clearedAtByPeer.get(pid) || 0)

    let c = 0
    for (const m of arr) {
      const mid = Number(m?.id) || 0
      if (mid <= 0) continue
      if (cut && Number(m?.date) <= cut) continue
      if (isMine(m)) continue
      if (mid > lastSeen) c++
    }

    unreadByPeer.set(pid, c)
    total += c
  })

  fabUnread.value = total
  requestAnimationFrame(() => repaintAllContactBadges())
}

/**
 * SPA navigation handler:
 * - update cid/sid/gid from route
 * - refresh tutor context
 * - refresh contacts (AI Tutor visibility)
 * - if AI thread is active, reload history for the new course
 */
let navDebounceTimer = null
async function onNavigationChanged() {
  readCidReqFromRouteAndLocation()

  if (!canRenderDock.value) return

  const aiActive = Number(activePeer.value?.id || 0) === AI_PEER_ID
  if (!open.value && !aiActive) {
    // Keep AI cache clean for next time.
    resetAiThreadCache()
    return
  }

  await loadTutorContext()

  try {
    await loadContacts()
  } catch {
    // ignore
  }

  if (aiActive) {
    if (!tutorCtx.enabled || !inCourse.value || tutorCtx.inTest) {
      activePeer.value = null
      resetAiThreadCache()
      return
    }
    resetAiThreadCache()
    await getPreviousMessages()
    requestAnimationFrame(() => {
      const el = scrollBox.value
      if (el) el.scrollTop = el.scrollHeight
      maybeMarkAsReadOnView()
    })
  } else {
    resetAiThreadCache()
  }
}

// Use vue-router signal (no history patching, no polling).
watch(
  () => route.fullPath,
  () => {
    clearTimeout(navDebounceTimer)
    navDebounceTimer = setTimeout(() => {
      onNavigationChanged().catch(() => {})
    }, 80)
  },
  { immediate: false },
)

async function toggleDock(v) {
  if (v === open.value) return
  if (v && !canRenderDock.value) return

  const wasOpen = open.value
  open.value = v

  if (v) {
    await loadTutorContext()

    if (!openedOnce.value) {
      await startSession()

      if (userStatus.value !== 1) {
        try {
          await goOnline()
        } catch {
          // ignore
        }
        userStatus.value = 1
      }

      const presenceIds = collectVisibleContactIds()
      const params = { mode: "min", since_id: lastHeartbeatId.value || 0, include_contacts: 1 }
      if (presenceIds.length > 0) params.presence_ids = JSON.stringify(presenceIds)

      const r = await getJSON(API.heartbeat, params)

      if (typeof r?.contacts_html === "string") {
        contactsHtml.value = normalizeContactsHtmlForAiTutor(String(r.contacts_html || ""))
        requestAnimationFrame(() => repaintAllContactBadges())
      }
      if (r?.presence) paintPresenceOnContacts(r.presence)

      syncUnreadFromServer(r)
      openedOnce.value = true
    } else {
      await loadContacts()
    }

    recomputeUnreadFromLocal()

    clearInterval(contactsTimer)
    contactsTimer = setInterval(loadContacts, 15000)

    startHeartbeat()
    try {
      await heartbeatMinTick()
    } catch {
      // ignore
    }

    if (!wasOpen) {
      if (AUTO_OPEN_LAST_PEER) {
        const last = Number(localStorage.getItem(LAST_PEER_KEY) || 0)
        if (last > 0 && !activePeer.value) openConversation({ id: last, name: "User" })
      } else {
        activePeer.value = null
      }
    }
  } else {
    activePeer.value = null
    clearInterval(contactsTimer)
    contactsTimer = null
    recomputeUnreadFromLocal()
  }
}

watch(open, (v) => {
  if (!v) activePeer.value = null
})

function onContactsScroll() {}
function onComposerKeydown() {}
function newline() {}

onMounted(async () => {
  registerLegacyChatGlobals()

  if (!canRenderDock.value) return

  // Ensure cid is set on initial mount (important for /course/ID/home routes).
  readCidReqFromRouteAndLocation()

  await loadTutorContext()
  await startSession()

  if (userStatus.value === 1) startHeartbeat()

  try {
    await heartbeatMinTick()
  } catch {
    // ignore
  }
})

onBeforeUnmount(() => {
  stopHeartbeat()
  clearInterval(contactsTimer)
  contactsTimer = null
  stopBlinkNow()
})
</script>

<style scoped>
.chd .chd-fab.has-unread::after {
  content: "";
  position: absolute;
  top: 6px;
  inset-inline-end: 6px;
  right: 6px;
  left: auto;
  width: 10px;
  height: 10px;
  background: #e53935;
  border-radius: 9999px;
  box-shadow: 0 0 0 2px #fff;
  z-index: 2;
}
html[dir="rtl"] .chd .chd-fab.has-unread::after {
  left: 6px;
  right: auto;
}
.chd .chd-contacts .chd-contact-row {
  position: relative;
  padding-right: 18px;
  overflow: visible;
  z-index: 0;
}
.chd .chd-contacts .chd-contact-dot {
  position: absolute;
  top: 50%;
  right: 8px;
  transform: translateY(-50%);
  width: 8px;
  height: 8px;
  background: #e53935;
  border-radius: 9999px;
  box-shadow: 0 0 0 2px #fff;
  z-index: 2;
}
html[dir="rtl"] .chd .chd-contacts .chd-contact-dot {
  left: 8px;
  right: auto;
}
.chd-avatar--fallback {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
/* AI contact styles (minimal and non-breaking) */
.chd-ai {
  padding: 8px 10px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}
.chd-ai__btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 10px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  background: #fff;
  cursor: pointer;
}
.chd-ai__btn.is-active {
  border-color: rgba(0, 0, 0, 0.18);
}
.chd-ai__btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.chd-ai__hint {
  margin: 6px 2px 0;
  font-size: 12px;
}
</style>
