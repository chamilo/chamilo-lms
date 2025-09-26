<template>
  <Teleport to="body">
    <div class="chd">
      <!-- FAB -->
      <button
        class="chd-fab"
        :class="{ 'has-unread': unreadTotal > 0 }"
        :title="t('Chat')"
        aria-label="Open chat"
        @click="toggleDock(true)"
      >
        <i class="mdi mdi-message-text-outline" />
      </button>

      <!-- Dock -->
      <div v-if="open" class="chd-dock" role="dialog" aria-label="Chat dock">
        <header class="chd-header">
          <div class="chd-title">
            <i class="mdi mdi-chat-outline" />
            <span>{{ t('Chat') }}</span>
          </div>
          <div class="chd-actions">
            <button class="chd-btn" @click="toggleStatus">
              <span class="chd-dot" :class="userStatus === 1 ? 'chd-dot--on' : 'chd-dot--off'" />
              {{ userStatus === 1 ? t('Online') : t('Offline') }}
            </button>
            <button class="chd-btn chd-btn--ghost" @click="toggleDock(false)" aria-label="Close">
              <i class="mdi mdi-close" />
            </button>
          </div>
        </header>

        <section class="chd-body">
          <!-- Contacts -->
          <aside class="chd-sidebar">
            <div class="chd-sidebar__head">
              <strong>{{ t('Contacts') }}</strong>
              <button class="chd-btn chd-btn--ghost chd-btn--xs" @click="loadContacts" :disabled="loadingContacts">
                <i class="mdi mdi-refresh" />
              </button>
            </div>

            <div class="chd-contacts" @scroll.passive="onContactsScroll">
              <template v-if="contactsHtml">
                <div
                  class="chd-contacts-html chd-legacy"
                  v-html="contactsHtml"
                  @click.prevent="onContactsClick"
                />
              </template>
              <p v-else class="chd-text--muted">{{ t('No contacts to show') }}</p>
            </div>
          </aside>

          <!-- Conversation -->
          <main class="chd-chat">
            <div class="chd-chat__head">
              <div class="chd-peer">
                <template v-if="activePeer">
                  <img :src="activePeer.image || ''" class="chd-avatar" alt="" />
                  <div class="chd-peer__meta">
                    <strong class="chd-truncate">{{ activePeer.name }}</strong>
                    <span class="chd-presence" :class="activePeer.online ? 'on' : 'off'"></span>
                  </div>
                </template>
                <template v-else>
                  <strong>{{ t('Select a contact') }}</strong>
                </template>
              </div>
            </div>

            <!-- Scrollable messages area -->
            <div
              class="chd-chat__body"
              ref="scrollBox"
              @scroll.passive="onScrollUpLoadMore; onBodyScrollForRead"
            >
              <template v-if="activePeer">
                <div v-for="msg in activeMessages" :key="msg.id" :class="bubbleClass(msg)">
                  <div class="chd-bubble">
                    <div class="chd-bubble__content" v-html="renderMessage(msg.message)" />
                    <div class="chd-bubble__meta">
                      <span class="chd-bubble__date">{{ formatTs(msg.date) }}</span>
                      <span
                        v-if="isMine(msg)"
                        class="chd-bubble__ack"
                        :title="ackTitle(msg)"
                      >{{ ackGlyph(msg) }}</span>
                    </div>
                  </div>
                </div>
                <div v-if="activeMessages.length === 0" class="chd-text--muted chd-center chd-py-8">
                  {{ t('No messages yet') }}
                </div>
              </template>
              <div v-else class="chd-text--muted chd-center chd-py-16">
                {{ t('Pick someone from the left to start chatting') }}
              </div>
            </div>

            <!-- Composer -->
            <div class="chd-composer" v-if="activePeer">
              <textarea
                v-model.trim="draft"
                class="chd-input"
                :placeholder="t('Write…')"
                rows="2"
                @keydown.enter.prevent.exact="send"
                @keydown.enter.shift.exact="newline"
              />
              <div class="chd-composer__actions">
                <span class="chd-hint">{{ t('Enter to send · Shift+Enter for newline') }}</span>
                <div class="chd-spacer" />
                <button class="chd-btn chd-btn--danger-outline" @click="clearConversation" :disabled="sending || clearing">
                  {{ t('Reset') }}
                </button>
                <button class="chd-btn chd-btn--primary" @click="send" :disabled="!draft || sending || userStatus !== 1">
                  <i class="mdi mdi-send" /> {{ t('Send') }}
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

const { t } = useI18n({ useScope: "global" })

/** ===== Endpoints ===== */
function R(name, fallback) {
  try { return window.Routing?.generate(name) || fallback } catch { return fallback }
}
const API = {
  start:     R('chat_api_start',     '/account/chat/api/start'),
  contacts:  R('chat_api_contacts',  '/account/chat/api/contacts'),
  heartbeat: R('chat_api_heartbeat', '/account/chat/api/heartbeat'),
  send:      R('chat_api_send',      '/account/chat/api/send'),
  status:    R('chat_api_status',    '/account/chat/api/status'),
  history:   R('chat_api_history',   '/account/chat/api/history'),
  preview:   R('chat_api_preview',   '/account/chat/api/preview'),
  presence:  R('chat_api_presence',  '/account/chat/api/presence'),
  ack:       R('chat_api_ack',       '/account/chat/api/ack'),
}

/** ===== Heartbeat timing ===== */
const HEARTBEAT_MIN = 800
const HEARTBEAT_MAX = 30000
function nextHbDelay() {
  if (userStatus.value !== 1) return HEARTBEAT_MAX
  if (document.hidden) return 8000
  if (activePeer.value) return 800 + Math.random() * 400
  return 2500
}

/** ===== State ===== */
const open = ref(false)
const userStatus = ref(0)
const me = reactive({ id: 0, name: "", secToken: "" })

const contactsHtml = ref("")
const loadingContacts = ref(false)

const activePeer = ref(null) // { id, name, online, image }
const messagesByPeer = reactive(new Map())
const fetchingPrev = ref(false)

const draft = ref("")
const sending = ref(false)
const clearing = ref(false)

const scrollBox = ref(null)

/** Unread tracking (client-side only) */
const unreadByPeer = reactive(new Map()) // peerId -> count
const clearedAtByPeer = reactive(new Map()) // peerId -> epoch seconds
const lastSeenMsgIdByPeer = reactive(new Map()) // peerId -> last msg id seen

let hbTimer = null
let hbDelay = HEARTBEAT_MIN
let contactsTimer = null

/** ===== Utils ===== */
function qs(obj) { return new URLSearchParams(obj).toString() }
function linkify(str) {
  return (str || "").replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>')
}
function renderMessage(html) { return linkify(html) }
function formatTs(ts) {
  const d = Number(ts) * 1000
  if (!Number.isFinite(d)) return ""
  try {
    return new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" }).format(new Date(d))
  } catch { return "" }
}
function bubbleClass(msg) {
  const mine = Number(msg.from_user_info?.id) === me.id || Number(msg?.f) === me.id
  return mine ? "chd-row chd-row--me" : "chd-row chd-row--peer"
}
function escapeForHtml(s) {
  return (s || "").replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]))
}
function isMine(m) { return Number(m?.from_user_info?.id) === me.id || Number(m?.f) === me.id }

/** ===== Acks: helpers de UI ===== */
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

/** API helpers */
async function getJSON(url, params) {
  const full = params ? `${url}?${qs(params)}` : url
  const r = await fetch(full, { credentials: "same-origin" })
  if (!r.ok) throw new Error("net")
  return r.json()
}
async function post(url, params, expectJson = true) {
  const r = await fetch(url, {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
    body: new URLSearchParams(params || {}),
  })
  if (!r.ok) throw new Error("net")
  return expectJson ? r.json() : r.text()
}

/** Normalize + filters */
function normalizeItems(raw) {
  const list = Array.isArray(raw) ? raw.slice() : Object.values(raw || {})
  return list.sort((a,b)=>Number(a.id) - Number(b.id))
}
function filterAfterClear(pid, list) {
  const cut = clearedAtByPeer.get(pid)
  if (!cut) return list
  return list.filter(m => Number(m?.date) > cut)
}

/** Unread helpers */
const unreadTotal = computed(() => {
  let t = 0
  unreadByPeer.forEach(v => t += Number(v || 0))
  return t
})
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
  const pid = activePeer.value.id
  const arr = messagesByPeer.get(pid) || []
  if (!arr.length) return

  const last = arr[arr.length - 1].id
  const lastSeenPrev = Number(lastSeenMsgIdByPeer.get(pid) || 0)
  if (last <= lastSeenPrev) return

  lastSeenMsgIdByPeer.set(pid, last)
  resetUnread(pid)

  try {
    await post(API.ack, { peer_id: pid, last_seen_id: last })
  } catch {}
}

function extractPeerIdFromNode(node) {
  if (!node) return 0
  const ds = node.dataset || {}
  const cand = Number(ds.user || ds.id || ds.userId || ds.idUser || 0)
  if (cand) return cand

  if (node.matches?.('a[href]')) {
    try {
      const u = new URL(node.getAttribute('href'), location.origin)
      const p = Number(u.searchParams.get('user') || u.searchParams.get('id') || 0)
      if (p) return p
    } catch {}
  }

  const oc = node.getAttribute?.('onclick') || ''
  if (oc) {
    const m = oc.match(/(?:chatWith|openChat|startChat)\s*\(\s*['"]?(\d+)['"]?/i)
    if (m) return Number(m[1])
  }

  const idAttr = node.id || ''
  if (idAttr) {
    const m2 = idAttr.match(/(?:friend|user|contact)[_-]?(\d+)/i)
    if (m2) return Number(m2[1])
  }

  return 0
}
function findContactNodesByPeerId(root, pid) {
  if (!root || !pid) return []
  const candidates = Array.from(
    root.querySelectorAll('[data-user],[data-id],[data-user-id],[data-id-user],a[href],[onclick],[id]')
  )
  return candidates
    .filter(el => extractPeerIdFromNode(el) === pid)
    .map(el => {
      const row = el.closest('.chd-contact-row') ||
        el.closest('.list-group-item, .panel, .panel-body, li, div') || el
      row.classList.add('chd-contact-row')
      return row
    })
}
function setRowUnreadDot(row, hasUnread) {
  if (!row) return
  let dot = row.querySelector(':scope > .chd-contact-dot')
  if (hasUnread) {
    if (!dot) {
      dot = document.createElement('span')
      dot.className = 'chd-contact-dot'
      row.appendChild(dot)
    }
  } else {
    if (dot) dot.remove()
  }
}
function updateContactUnreadBadge(pid) {
  const count = Number(unreadByPeer.get(pid) || 0)
  const root = document.querySelector('.chd .chd-contacts .chd-contacts-html')
  if (!root) return
  const rows = findContactNodesByPeerId(root, pid)
  rows.forEach(row => setRowUnreadDot(row, count > 0))
}
function repaintAllContactBadges() {
  const root = document.querySelector('.chd .chd-contacts .chd-contacts-html')
  if (!root) return
  root.querySelectorAll('.chd-contact-dot').forEach(n => n.remove())
  unreadByPeer.forEach((count, pid) => {
    if (Number(count || 0) > 0) updateContactUnreadBadge(Number(pid))
  })
}

/** ===== Presence helpers ===== */
function resolveOnlineFromInfo(ui) {
  if (!ui) return false;
  let v = ui.user_is_online_in_chat ?? ui.user_is_online ?? ui.online ?? ui.is_online ?? ui.presence;
  if (v !== undefined && v !== null) {
    if (typeof v === 'string') return /^(1|true|online|on)$/i.test(v);
    return !!Number(v) || v === true;
  }
  const ts = ui.last_seen_ts ?? ui.last_active_ts ?? ui.last_active_at ?? null;
  if (ts) {
    const now = Date.now();
    const tms = Number(ts) > 1e12 ? Number(ts) : Number(ts) * 1000;
    return now - tms <= 120_000;
  }
  return false;
}
function collectVisibleContactIds() {
  const root = document.querySelector('.chd .chd-contacts .chd-contacts-html');
  if (!root) return [];
  const ids = new Set();
  root.querySelectorAll('[data-user],[data-id],[data-user-id],[data-id-user],a[href],[onclick],[id]')
    .forEach(el => { const id = extractPeerIdFromNode(el); if (id) ids.add(id); });
  if (activePeer.value?.id) ids.add(activePeer.value.id);
  return Array.from(ids);
}
async function refreshPresence() {
  const ids = collectVisibleContactIds();
  if (!ids.length) return;
  try {
    const r = await fetch(API.presence, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams({ ids: JSON.stringify(ids) })
    });
    if (!r.ok) return;
    const data = await r.json();
    const map = data?.presence || {};
    if (activePeer.value?.id && map[activePeer.value.id] !== undefined) {
      activePeer.value.online = !!map[activePeer.value.id];
    }
    paintPresenceOnContacts(map)
  } catch {}
}
function paintPresenceOnContacts(map) {
  const root = document.querySelector('.chd .chd-contacts .chd-contacts-html')
  if (!root) return
  Object.entries(map).forEach(([sid, online]) => {
    const pid = Number(sid)
    const rows = findContactNodesByPeerId(root, pid)
    rows.forEach(row => {
      row.querySelectorAll(':scope > .chd-presence-dot').forEach(n => n.remove())
      const icon =
        row.querySelector('i.mdi-account-check') ||
        row.querySelector('i.mdi-account-outline') ||
        row.querySelector('i[class*="mdi-account"]')
      if (icon) {
        icon.classList.toggle('mdi-account-check', !!online)
        icon.classList.toggle('mdi-account-outline', !online)
        icon.classList.toggle('is-online', !!online)
        icon.classList.toggle('is-offline', !online)
      }
    })
  })
}

/** ===== Flows ===== */
async function startSession() {
  const data = await getJSON(API.start)
  if (!data) return
  me.name = data.me || ""
  me.id = Number(data.user_id || 0)
  userStatus.value = Number(data.user_status || 0)
  me.secToken = data.sec_token || ""

  if (data.items) {
    Object.entries(data.items).forEach(([peerId, userItems]) => {
      const pid = Number(peerId)
      const arr = filterAfterClear(pid, normalizeItems(userItems?.items ?? []))
      messagesByPeer.set(pid, arr)
      if (activePeer.value?.id === pid && userItems?.window_user_info) {
        activePeer.value.online = resolveOnlineFromInfo(userItems.window_user_info);
      }
      if (arr.length) lastSeenMsgIdByPeer.set(pid, arr[arr.length - 1].id)
    })
  }
  await loadContacts()
}
async function loadContacts() {
  loadingContacts.value = true
  try {
    const html = await post(API.contacts, { to: "user_id" }, false)
    contactsHtml.value = String(html || "")
    requestAnimationFrame(() => {
      repaintAllContactBadges()
      refreshPresence()
    })
  } finally {
    loadingContacts.value = false
  }
}
/** Contacts click handler */
function onContactsClick(e) {
  const a = e.target.closest('a[href]')
  if (a) { e.preventDefault(); e.stopPropagation() }

  let el = e.target.closest('[data-user],[data-id],[data-user-id],[data-id-user]')
  if (el) {
    const userId = Number(el.dataset.user || el.dataset.id || el.dataset.userId || el.dataset.idUser || 0)
    const name  = el.getAttribute('data-name') || el.textContent?.trim() || 'User'
    const image = el.getAttribute('data-image') || ''
    if (userId) return openConversation({ id: userId, name, image })
  }

  el = e.target.closest('[onclick]')
  if (el) {
    const oc = el.getAttribute('onclick') || ''
    const m  = oc.match(/chatWith\s*\(\s*['"]?(\d+)['"]?\s*[,\)]/i)
      || oc.match(/openChat\s*\(\s*['"]?(\d+)['"]?\s*[,\)]/i)
      || oc.match(/startChat\s*\(\s*['"]?(\d+)['"]?\s*[,\)]/i)
    if (m) {
      const userId = Number(m[1])
      const name = el.getAttribute('data-name') || el.textContent?.trim() || 'User'
      return openConversation({ id: userId, name })
    }
  }

  if (a) {
    try {
      const u = new URL(a.getAttribute('href'), location.origin)
      const id = Number(u.searchParams.get('user') || u.searchParams.get('id') || 0)
      if (id) {
        const name = a.getAttribute('data-name') || a.textContent?.trim() || 'User'
        return openConversation({ id, name })
      }
    } catch {}
  }

  el = e.target.closest('[id]')
  if (el) {
    const m = (el.id || '').match(/(?:friend|user|contact)[_-]?(\d+)/i)
    if (m) {
      const userId = Number(m[1])
      const name = el.getAttribute('title') || el.getAttribute('data-name') || el.textContent?.trim() || 'User'
      return openConversation({ id: userId, name })
    }
  }
}
function pickOnline() { return undefined }

async function openConversation(peer) {
  activePeer.value = {
    id: Number(peer.id),
    name: peer.name || "User",
    image: peer.image || "",
    online: pickOnline(peer.id) ?? false,
  }
  resetUnread(activePeer.value.id)

  if (!messagesByPeer.get(activePeer.value.id)) {
    await getPreviousMessages()
  }
  requestAnimationFrame(() => {
    const el = scrollBox.value
    if (el) el.scrollTop = el.scrollHeight
    if (isAtBottom(el)) markActiveAsRead()
  })
}
function onScrollUpLoadMore(e) {
  const el = e.target
  if (!activePeer.value || fetchingPrev.value) return
  if (el.scrollTop <= 0) getPreviousMessages()
}
function onBodyScrollForRead() {
  if (!activePeer.value) return
  if (isAtBottom(scrollBox.value)) markActiveAsRead()
}
async function getPreviousMessages() {
  if (!activePeer.value) return
  const pid = activePeer.value.id
  const current = messagesByPeer.get(pid) || []
  const visible = current.length
  fetchingPrev.value = true
  try {
    const items = await getJSON(API.history, { user_id: pid, visible_messages: visible })
    const list = filterAfterClear(pid, normalizeItems(items))
    if (list.length) {
      const merged = [...list, ...current].sort((a,b)=>a.id - b.id)
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

let lastPresenceAt = 0
async function heartbeat() {
  try {
    const data = await getJSON(API.heartbeat)

    if (data?.items) {
      for (const [peerId, userItems] of Object.entries(data.items)) {
        const pid = Number(peerId)
        const prev = messagesByPeer.get(pid) || []
        const pruned = filterAfterClear(pid, normalizeItems(userItems?.items ?? []))
        const newOnes = pruned.filter(x => !prev.find(p => p.id === x.id))

        if (newOnes.length) {
          const merged = [...prev, ...newOnes].sort((a,b)=>a.id - b.id)
          messagesByPeer.set(pid, merged)

          const incoming = newOnes.filter(m => !isMine(m))
          if (incoming.length) {
            const isActivePeer = activePeer.value?.id === pid
            const viewing = isActivePeer && open.value && isAtBottom(scrollBox.value) && document.hasFocus()

            if (!viewing) {
              incUnread(pid, incoming.length)
            } else {
              lastSeenMsgIdByPeer.set(pid, merged[merged.length - 1].id)
              resetUnread(pid)
              try {
                await post(API.ack, { peer_id: pid, last_seen_id: merged[merged.length - 1].id })
              } catch {}
            }
          }

          if (activePeer.value?.id === pid) {
            requestAnimationFrame(() => {
              const el = scrollBox.value
              if (el) el.scrollTop = el.scrollHeight
            })
          }
        }

        if (userItems.window_user_info && activePeer.value?.id === Number(peerId)) {
          activePeer.value.online = resolveOnlineFromInfo(userItems.window_user_info)
        }
      }
    }

    const now = Date.now()
    if (now - lastPresenceAt > 5000) {
      lastPresenceAt = now
      refreshPresence()
    }
    hbDelay = nextHbDelay()
  } catch {
    hbDelay = Math.min((hbDelay || HEARTBEAT_MIN) * 1.5, HEARTBEAT_MAX)
  } finally {
    hbTimer = setTimeout(heartbeat, hbDelay)
  }
}

function startHeartbeat() { stopHeartbeat(); hbDelay = 250; hbTimer = setTimeout(heartbeat, hbDelay) }
function stopHeartbeat() { if (hbTimer) clearTimeout(hbTimer); hbTimer = null }

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

async function send() {
  if (!activePeer.value || !draft.value) return
  const msg = draft.value
  draft.value = ""
  sending.value = true
  try {
    const res = await post(API.send, {
      to: activePeer.value.id,
      message: msg,
      chat_sec_token: me.secToken,
    })
    if (res && typeof res === "object") {
      if (res.sec_token) me.secToken = res.sec_token
      if (res.id > 0) {
        const item = {
          from_user_info: { id: me.id, complete_name: "me" },
          username: me.name,
          date: Math.floor(Date.now() / 1000),
          f: me.id,
          message: escapeForHtml(msg),
          id: res.id,
          recd: 0,
        }
        const arr = messagesByPeer.get(activePeer.value.id) || []
        messagesByPeer.set(activePeer.value.id, [...arr, item])
        requestAnimationFrame(() => {
          const el = scrollBox.value
          if (el) el.scrollTop = el.scrollHeight
        })
      }
    }
  } finally {
    sending.value = false
    if (hbTimer) clearTimeout(hbTimer)
    hbDelay = 300
    hbTimer = setTimeout(heartbeat, hbDelay)
  }
}

function newline() { /* Shift+Enter */ }

async function clearConversation() {
  if (!activePeer.value) return
  if (!confirm(t("Are you sure you want to clear this conversation?"))) return
  clearing.value = true
  try {
    const pid = activePeer.value.id
    const nowSec = Math.floor(Date.now() / 1000)
    clearedAtByPeer.set(pid, nowSec)
    messagesByPeer.set(pid, [])
    resetUnread(pid)
    requestAnimationFrame(() => {
      const el = scrollBox.value
      if (el) el.scrollTop = el.scrollHeight
    })
  } finally {
    clearing.value = false
  }
}

/** Computed */
const activeMessages = computed(() => {
  if (!activePeer.value) return []
  return messagesByPeer.get(activePeer.value.id) || []
})

const originalTitle = document.title
let blinkTimer = null
let blinkState = false
function applyBlinkFrame() {
  const total = unreadTotal.value
  if (!total || !document.hidden) { stopBlinkNow(); return }
  blinkState = !blinkState
  document.title = blinkState ? `(${total}) ${t('New messages')}` : originalTitle
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
  if (blinkTimer) { clearInterval(blinkTimer); blinkTimer = null }
  document.title = originalTitle
  blinkState = false
}
document.addEventListener('visibilitychange', () => {
  if (document.hidden) maybeStartBlink()
  else maybeStopBlink()
})
window.addEventListener('focus', maybeStopBlink)

/** Lifecycle */
async function toggleDock(v) {
  open.value = v
  if (v) {
    await startSession()
    if (userStatus.value !== 1) {
      try { await goOnline() } catch {}
    }
    await loadContacts()
    clearInterval(contactsTimer)
    contactsTimer = setInterval(loadContacts, 15000)
    startHeartbeat()
  } else {
    clearInterval(contactsTimer)
    contactsTimer = null
  }
}
onMounted(async () => {
  await startSession()
  if (userStatus.value !== 1) {
    try { await goOnline() } catch {}
    userStatus.value = 1
  }
  startHeartbeat()
})
onBeforeUnmount(() => {
  stopHeartbeat()
  clearInterval(contactsTimer)
  contactsTimer = null
  stopBlinkNow()
})
watch(open, (v) => { if (!v) activePeer.value = null })
function onContactsScroll() {}
</script>
