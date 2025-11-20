<template>
  <Teleport to="body">
    <div class="chd">
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
                    ></span>
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
                :placeholder="t('Write a message')"
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
                  :disabled="!draft || sending || userStatus !== 1"
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

const { t } = useI18n({ useScope: "global" })
const openedOnce = ref(false)
const AUTO_OPEN_LAST_PEER = false
const queuedBadgePids = new Set()

/** Active peer must exist before we watch it (avoid ReferenceError). */
const activePeer = ref(null) // { id, name, online, image }

/** Persist last opened peer locally (nice UX). */
const LAST_PEER_KEY = "chd:lastPeerId"
watch(activePeer, (v) => {
  if (v?.id) localStorage.setItem(LAST_PEER_KEY, String(v.id))
})

/** ===== Endpoints ===== */
function RG(candidates, fallback) {
  for (const name of candidates) {
    try {
      const u = window.Routing?.generate(name)
      if (u) return u
    } catch {}
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

/** Unread tracking (client-side only) */
const unreadByPeer = reactive(new Map()) // peerId -> count
const clearedAtByPeer = reactive(new Map()) // peerId -> epoch seconds
const lastSeenMsgIdByPeer = reactive(new Map()) // peerId -> last msg id seen

/** FAB global unread from /heartbeat?mode=min */
const fabUnread = ref(0)
const fabHasUnread = computed(() => unreadTotal.value > 0 || fabUnread.value > 0)
const unreadTotal = computed(() => {
  let t = 0
  unreadByPeer.forEach((v) => (t += Number(v || 0)))
  return t
})

let hbTimer = null
let hbLegacyLoop = false
let contactsTimer = null

/** ===== Utils ===== */
function qs(obj) {
  return new URLSearchParams(obj).toString()
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

/** ===== Acks: UI helpers ===== */
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
  return list.sort(byChronoId)
}
function filterAfterClear(pid, list) {
  const cut = clearedAtByPeer.get(pid)
  if (!cut) return list
  return list.filter((m) => Number(m?.date) > cut)
}

/** Pending + dedupe (optimistic UI) */
const pendingByPeer = reactive(new Map()) // peerId -> [{ id: tempId, msg, ts }]
function addPending(pid, tempId, msg, ts) {
  const arr = pendingByPeer.get(pid) || []
  arr.push({ id: tempId, msg: String(msg || ""), ts: Number(ts) })
  pendingByPeer.set(pid, arr)
}
function removePending(pid, tempId) {
  const arr = pendingByPeer.get(pid) || []
  const idx = arr.findIndex((x) => x.id === tempId)
  if (idx >= 0) {
    arr.splice(idx, 1)
    pendingByPeer.set(pid, arr)
  }
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
  return Math.abs(ta - tb) <= 10 // tolerate minor clock drift
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

  const cand = Number(ds.user || ds.id || ds.userId || ds.idUser || ds.uid || ds.friend || ds.contactId || 0)
  if (cand) return cand

  if (node.matches?.("a[href]")) {
    try {
      const u = new URL(node.getAttribute("href"), location.origin)
      const p = Number(
        u.searchParams.get("user") ||
          u.searchParams.get("id") ||
          u.searchParams.get("uid") ||
          u.searchParams.get("friend") ||
          u.searchParams.get("contact") ||
          0,
      )
      if (p) return p
    } catch {}
  }

  const oc = node.getAttribute?.("onclick") || ""
  if (oc) {
    const m = oc.match(/(?:chatWith|openChat|startChat)\s*\(\s*['"]?(\d+)['"]?/i)
    if (m) return Number(m[1])
  }

  // id="friend_123" / "contact-123"
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

/** Presence helpers */
function resolveOnlineFromInfo(ui) {
  if (!ui) return false
  let v = ui.user_is_online_in_chat ?? ui.user_is_online ?? ui.online ?? ui.is_online ?? ui.presence
  if (v !== undefined && v !== null) {
    if (typeof v === "string") return /^(1|true|online|on)$/i.test(v)
    return !!Number(v) || v === true
  }
  const ts = ui.last_seen_ts ?? ui.last_active_ts ?? ui.last_active_at ?? null
  if (ts) {
    const now = Date.now()
    const tms = Number(ts) > 1e12 ? Number(ts) : Number(ts) * 1000
    return now - tms <= 120000
  }
  return false
}
function collectVisibleContactIds() {
  const root = document.querySelector(".chd .chd-contacts .chd-contacts-html")
  if (!root) return []
  const ids = new Set()
  root.querySelectorAll("[data-user],[data-id],[data-user-id],[data-id-user],a[href],[onclick],[id]").forEach((el) => {
    const id = extractPeerIdFromNode(el)
    if (id) ids.add(id)
  })
  if (activePeer.value?.id) ids.add(activePeer.value.id)
  return Array.from(ids)
}
async function refreshPresence() {
  // No-op: presence is now handled inside heartbeat responses
}

function paintPresenceOnContacts(map) {
  const root = document.querySelector(".chd .chd-contacts .chd-contacts-html")
  if (!root) return
  Object.entries(map).forEach(([sid, online]) => {
    const pid = Number(sid)
    const rows = findContactNodesByPeerId(root, pid)
    rows.forEach((row) => {
      row.querySelectorAll(":scope > .chd-presence-dot").forEach((n) => n.remove())
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

/** Flows */
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
        activePeer.value.online = resolveOnlineFromInfo(userItems.window_user_info)
      }
      if (arr.length) lastSeenMsgIdByPeer.set(pid, arr[arr.length - 1].id)
    })
  }
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
      const u = new URL(a.getAttribute("href"), location.origin)
      const id = Number(u.searchParams.get("user") || u.searchParams.get("id") || 0)
      if (id) {
        const name = a.getAttribute("data-name") || a.textContent?.trim() || "User"
        return openConversation({ id, name })
      }
    } catch {}
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

async function openConversation(peer) {
  activePeer.value = {
    id: Number(peer.id),
    name: peer.name || "User",
    image: peer.image || "",
    online: pickOnline(peer.id) ?? false,
  }

  if (!messagesByPeer.get(activePeer.value.id)) {
    await getPreviousMessages()
  }
  requestAnimationFrame(() => {
    const el = scrollBox.value
    if (el) el.scrollTop = el.scrollHeight
    maybeMarkAsReadOnView()
  })

  // Kick fast phase when opening a conversation
  lastIdByPeer.set(activePeer.value.id, lastKnownIdForPeer(activePeer.value.id))
  maybeResetScheduler()
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
  if (isActuallyViewingActivePeer()) {
    debounceMarkAsRead()
  }
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

let lastPresenceAt = 0
// Heartbeat that does not rely on server-provided unread_by_peer.
// It merges new items, handles acks, updates presence throttled,
// and finally recomputes unread counts locally for FAB + contact dots.
async function heartbeat() {
  try {
    const data = await getJSON(API.heartbeat)

    if (data?.items) {
      for (const [peerId, userItems] of Object.entries(data.items)) {
        const pid = Number(peerId)
        const prev = messagesByPeer.get(pid) || []
        const pruned = filterAfterClear(pid, normalizeItems(userItems?.items ?? []))

        // Adopt optimistic bubbles that the server just acknowledged
        const adopted = adoptPendingFromServer(pid, pruned, prev)

        // Dedupe vs existing and pending
        let newOnes = pruned.filter((x) => !prev.find((p) => p.id === x.id && !adopted.has(Number(x.id))))
        newOnes = dedupeNewWithPending(pid, newOnes)

        if (newOnes.length) {
          const merged = [...prev, ...newOnes].sort(byChronoId)
          messagesByPeer.set(pid, merged)

          // Handle incoming messages (not mine)
          const incoming = newOnes.filter((m) => !isMine(m))
          if (incoming.length) {
            const isActivePeer = activePeer.value?.id === pid
            const viewing = false
            if (!isActivePeer) {
              // Let the UI react immediately (blink/title), exact counts will be reconciled below
              incUnread(pid, incoming.length)
            } else {
              // If the user is seeing the bottom of the active conversation, mark as read and ack
              const lastId = merged[merged.length - 1].id
              lastSeenMsgIdByPeer.set(pid, lastId)
              resetUnread(pid)
              try {
                await post(API.ack, { peer_id: pid, last_seen_id: lastId })
              } catch {
                /* best effort */
              }
            }
          }

          maybeMarkAsReadOnView()
          // Auto-scroll if this peer is the active one
          if (activePeer.value?.id === pid) {
            requestAnimationFrame(() => {
              const el = scrollBox.value
              if (el) el.scrollTop = el.scrollHeight
            })
          }
        }

        // Optional presence hint from server
        if (userItems.window_user_info && activePeer.value?.id === pid) {
          activePeer.value.online = resolveOnlineFromInfo(userItems.window_user_info)
        }
      }
    }

    // Throttled presence refresh
    const now = Date.now()
    if (now - lastPresenceAt > 5000) {
      lastPresenceAt = now
      refreshPresence()
    }

    recomputeUnreadFromLocal()
  } finally {
    if (hbLegacyLoop && !hbTimer) {
      hbTimer = setTimeout(heartbeat, 30000)
    }
  }
}

/** per-peer last id cache and incremental fetch */
const lastIdByPeer = reactive(new Map())
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
  const items = await getJSON(API.history_since, { user_id: pid, since_id: since })
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
    incUnread(pid, newOnes.filter((m) => !isMine(m)).length || 0)
  } else {
    maybeMarkAsReadOnView()
    requestAnimationFrame(() => {
      const el = scrollBox.value
      if (el) el.scrollTop = el.scrollHeight
    })
  }
}

/** Progressive backoff scheduler with override (1s→5s→10s→30s) */
function createBackoffScheduler({ onTick, onReset, getOverrideDelayMs } = {}) {
  const phases = [
    { ms: 1000, repeats: 5 },
    { ms: 5000, durationMs: 30000 },
    { ms: 10000, durationMs: 120000 },
    { ms: 30000, repeats: Infinity },
  ]
  let timer = null,
    aborted = false,
    idx = 0,
    ticks = 0,
    started = 0
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
      } catch {}
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
      return
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

/** Minimal heartbeat tick (fast path + safety net) */
const lastHeartbeatId = ref(0)
let noChangeTicks = 0 // counts consecutive min ticks without server-reported changes

function computeGlobalLastId() {
  let maxId = 0
  messagesByPeer.forEach((arr) => {
    if (arr?.length) maxId = Math.max(maxId, Number(arr[arr.length - 1].id) || 0)
  })
  return maxId
}
function shouldPoll() {
  // Only poll when online. Dock openness handled by override below.
  return userStatus.value === 1
}

/** Gate resets so we don't restart fast phase when dock is closed */
function maybeResetScheduler() {
  // Force fast phase only when it makes sense (dock open & focused).
  if (open.value && document.hasFocus()) {
    scheduler.reset()
  }
}

function syncUnreadFromServer(payload) {
  const map = payload?.unread_by_peer
  if (!map) return
  Object.entries(map).forEach(([sid, count]) => {
    unreadByPeer.set(Number(sid), Number(count) || 0)
  })
  requestAnimationFrame(() => repaintAllContactBadges())
}

async function heartbeatMinTick() {
  if (!shouldPoll()) return

  const presenceIds = collectVisibleContactIds()
  const presenceParam = presenceIds.length > 0 ? { presence_ids: JSON.stringify(presenceIds) } : {}

  // Ultra-light path when a conversation is active
  const pid = activePeer.value?.id
  if (pid) {
    try {
      const since = lastKnownIdForPeer(pid)
      const params = {
        mode: "tiny",
        peer_id: pid,
        since_id: since,
        ...presenceParam,
      }

      const r = await getJSON(API.heartbeat, params)

      const latest = Number(r?.last_id || 0)
      if (latest > since) {
        await fetchNewForActivePeer(pid)
        lastIdByPeer.set(pid, latest)
        noChangeTicks = 0
        if (open.value) scheduler.reset()
      } else {
        noChangeTicks++
      }

      // update presence from heartbeat payload
      if (r?.presence) {
        paintPresenceOnContacts(r.presence)
        if (activePeer.value?.id && r.presence[activePeer.value.id] !== undefined) {
          activePeer.value.online = !!r.presence[activePeer.value.id]
        }
      }

      const now = Date.now()
      if (now - lastPresenceAt > 5000) {
        lastPresenceAt = now
      }
    } catch {}
    return
  }

  // No active peer: ultra-light global (for FAB)
  try {
    const since = lastHeartbeatId.value || computeGlobalLastId()
    const params = {
      mode: "min",
      since_id: since,
      ...presenceParam,
    }

    const r = await getJSON(API.heartbeat, params)

    syncUnreadFromServer(r)

    if (r?.presence) {
      paintPresenceOnContacts(r.presence)
    }

    const srvLast = Number(r?.last_id ?? 0)
    const hasNew = !!r?.has_new || srvLast > since

    if (typeof r?.unread === "number") {
      fabUnread.value = r.unread
    }

    if (hasNew) {
      lastHeartbeatId.value = Math.max(srvLast || 0, computeGlobalLastId(), since)
      noChangeTicks = 0
      if (open.value) scheduler.reset()
    } else {
      noChangeTicks++
    }
  } catch {}
}

// Keep instant responsiveness only when user is focused AND dock open+active
const scheduler = createBackoffScheduler({
  onTick: heartbeatMinTick,
  onReset: () => {},
  getOverrideDelayMs: (baseMs) => {
    // Force slow passive mode when the dock is closed
    if (!open.value) return 30000
    // Fast lane when dock is open, a peer is selected, and the tab is focused
    if (open.value && activePeer.value && document.hasFocus()) return 1000
    return baseMs
  },
})

/** Start/stop heartbeat */
function startHeartbeat() {
  stopHeartbeat()
  scheduler.start()
}
function stopHeartbeat() {
  scheduler.stop()
  if (hbTimer) clearTimeout(hbTimer)
  hbTimer = null
}

/** Send / Status / Clear */
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
  const pid = activePeer.value.id
  const raw = draft.value
  const msgEscaped = escapeForHtml(raw)
  const nowSec = Math.floor(Date.now() / 1000)

  // Optimistic bubble
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
    const res = await post(API.send, { to: pid, message: raw, chat_sec_token: me.secToken })
    if (res && typeof res === "object" && Number(res.id) > 0) {
      if (res.sec_token) me.secToken = res.sec_token
      replaceTempId(pid, tempId, { id: Number(res.id), recd: 0, date: nowSec })
      removePending(pid, tempId)
      maybeResetScheduler()
      return
    }
  } catch {
    // keep pending bubble
  } finally {
    sending.value = false
  }
}
function onComposerKeydown() {
  maybeResetScheduler()
}
function newline() {
  /* Shift+Enter */
}

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

/** Title blink for unread  */
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
  if (document.hidden) {
    maybeStartBlink()
  } else {
    maybeStopBlink()
    maybeResetScheduler()
    maybeMarkAsReadOnView()
  }
})
window.addEventListener("focus", () => {
  maybeStopBlink()
  maybeResetScheduler()
  maybeMarkAsReadOnView()
})
window.addEventListener("online", () => maybeResetScheduler())
window.addEventListener("offline", () => scheduler.stop())

/** Legacy compatibility layer */
function registerLegacyChatGlobals() {
  const handler = (id, name) => {
    try {
      // Only open the dock if it's closed; do not re-trigger init paths
      if (!open.value) toggleDock(true)
      openConversation({ id: Number(id), name: name || "User" })
    } catch (e) {}
    return false
  }
  if (!("chatWith" in window)) window.chatWith = handler
  if (!("openChat" in window)) window.openChat = handler
  if (!("startChat" in window)) window.startChat = handler
}

function startPassiveHeartbeat() {
  stopPassiveHeartbeat()
  hbTimer = setInterval(passiveTick, 10000)
}
async function passiveTick() {
  try {
    const since = lastHeartbeatId.value || computeGlobalLastId()
    const r = await getJSON(API.heartbeat, { mode: "min", since_id: since })

    syncUnreadFromServer(r)
    if (typeof r?.unread === "number") fabUnread.value = r.unread

    const srvLast = Number(r?.last_id ?? 0)
    const hasNew = !!r?.has_new || srvLast > since
    if (hasNew) {
      lastHeartbeatId.value = Math.max(srvLast || 0, since)
      await heartbeat()

      if (!r?.unread_by_peer) {
        recomputeUnreadFromLocal()
      }
    }
  } catch {}
}

// Recompute per-peer unread counts from local state and repaint badges + FAB.
function recomputeUnreadFromLocal() {
  let total = 0
  // Consider peers that have messages or a lastSeen stored
  const pids = new Set([...Array.from(messagesByPeer.keys()), ...Array.from(lastSeenMsgIdByPeer.keys())])

  pids.forEach((pid0) => {
    const pid = Number(pid0)
    const arr = messagesByPeer.get(pid) || []
    const lastSeen = Number(lastSeenMsgIdByPeer.get(pid) || 0)
    const cut = Number(clearedAtByPeer.get(pid) || 0)

    let c = 0
    for (const m of arr) {
      const mid = Number(m?.id) || 0
      if (mid <= 0) continue // ignore temp (optimistic) bubbles
      if (cut && Number(m?.date) <= cut) continue
      if (isMine(m)) continue
      if (mid > lastSeen) c++
    }
    unreadByPeer.set(pid, c)
    total += c
  })

  fabUnread.value = total
  // Paint red dots on the contact list when available
  requestAnimationFrame(() => repaintAllContactBadges())
}

function stopPassiveHeartbeat() {
  if (hbTimer) {
    clearInterval(hbTimer)
    hbTimer = null
  }
}

/** Lifecycle */
async function toggleDock(v) {
  if (v === open.value) return

  const wasOpen = open.value
  open.value = v

  if (v) {
    stopPassiveHeartbeat()

    if (!openedOnce.value) {
      await startSession()

      if (userStatus.value !== 1) {
        try {
          await goOnline()
        } catch {}
        userStatus.value = 1
      }

      // First open: ask heartbeat for contacts + presence in one go
      const presenceIds = collectVisibleContactIds()
      const params = {
        mode: "min",
        since_id: lastHeartbeatId.value || 0,
        include_contacts: 1,
      }
      if (presenceIds.length > 0) {
        params.presence_ids = JSON.stringify(presenceIds)
      }

      const r = await getJSON(API.heartbeat, params)

      if (typeof r?.contacts_html === "string") {
        contactsHtml.value = r.contacts_html
        requestAnimationFrame(() => repaintAllContactBadges())
      }

      if (r?.presence) {
        paintPresenceOnContacts(r.presence)
      }

      // Sync unread from server if available
      syncUnreadFromServer(r)

      openedOnce.value = true
    } else {
      await loadContacts()
    }

    recomputeUnreadFromLocal()

    clearInterval(contactsTimer)
    contactsTimer = setInterval(loadContacts, 15000)

    startHeartbeat()
    maybeResetScheduler()
    try {
      await heartbeatMinTick()
    } catch {}

    if (!wasOpen) {
      if (AUTO_OPEN_LAST_PEER) {
        const last = Number(localStorage.getItem(LAST_PEER_KEY) || 0)
        if (last > 0 && !activePeer.value) {
          openConversation({ id: last, name: "User" })
        }
      } else {
        activePeer.value = null
      }
    }
  } else {
    // CLOSE
    activePeer.value = null
    clearInterval(contactsTimer)
    contactsTimer = null
    stopHeartbeat()
    startPassiveHeartbeat()
    recomputeUnreadFromLocal()
  }
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

onMounted(async () => {
  registerLegacyChatGlobals()
  await startSession()
  startPassiveHeartbeat()
  try {
    await heartbeatMinTick()
  } catch {}
})

onBeforeUnmount(() => {
  stopHeartbeat()
  clearInterval(contactsTimer)
  contactsTimer = null
  stopBlinkNow()
})
watch(open, (v) => {
  if (!v) activePeer.value = null
})
function onContactsScroll() {}
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
</style>
