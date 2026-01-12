<template>
  <div class="grid gap-6 lg:grid-cols-[2fr,1fr]">
    <div class="rounded-lg border bg-white shadow-sm p-5">
      <template v-if="!loading">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h2 class="text-2xl font-bold">{{ post.title }}</h2>
            <div class="text-sm text-gray-500 mb-4">
              {{ t("By") }} {{ post.author }} · {{ formatDate(post.date) }}
            </div>
          </div>

          <div v-if="canEditPost" class="shrink-0 flex items-center gap-2">
            <BaseButton
              type="black"
              icon="edit"
              :label="t('Edit')"
              @click="openEditPost"
            />
            <BaseButton
              type="danger"
              icon="trash"
              :label="t('Delete')"
              @click="confirmDeletePost"
            />
          </div>
        </div>

        <div class="prose max-w-none" v-html="post.fullText"></div>

        <div v-if="post.attachments?.length" class="mt-6">
          <h4 class="text-sm font-semibold mb-2">{{ t("Attachments") }}</h4>
          <ul class="space-y-1">
            <li v-for="a in post.attachments" :key="a.id" class="flex items-center gap-2 text-sm">
              <i class="mdi mdi-paperclip"></i>
              <a :href="a.path" target="_blank" rel="noopener" class="text-blue-700 hover:underline">{{ a.name }}</a>
              <span class="text-gray-400">({{ humanSize(a.size) }})</span>
            </li>
          </ul>
        </div>

        <!-- Rating block with floating picker -->
        <div class="mt-6 flex items-center gap-3 relative">
          <span class="text-sm text-gray-600" aria-live="polite">
            {{ t("Rating") }}: {{ rating.average.toFixed(1) }} ★ ({{ rating.count }})
          </span>

          <!-- Trigger button to open the grid selector -->
          <button
            ref="triggerEl"
            type="button"
            class="h-9 px-3 rounded border text-sm bg-white hover:bg-gray-50 active:scale-[0.98] transition"
            :disabled="sendingRating"
            @click="togglePicker"
            :aria-expanded="showPicker ? 'true' : 'false'"
            :aria-haspopup="'dialog'"
          >
            {{ t("Rate") }}
          </button>

          <!-- Floating grid selector -->
          <div
            v-if="showPicker"
            ref="pickerEl"
            role="dialog"
            aria-label="Select rating"
            class="absolute z-20 mt-10 w-52 rounded-xl border bg-white shadow-lg p-3"
          >
            <div class="text-xs text-gray-500 mb-2">{{ t("Select a score") }} (1–{{ ratingScaleMax }})</div>
            <!-- 5x2 grid looks compact and not like pagination -->
            <div class="grid grid-cols-5 gap-2">
              <button
                v-for="n in ratingScaleMax"
                :key="n"
                type="button"
                class="h-9 rounded-lg border text-sm transition
                       hover:bg-yellow-50 hover:border-yellow-300
                       focus:outline-none focus:ring-2 focus:ring-yellow-300"
                :class="{ 'bg-yellow-100 border-yellow-300': hoverScore >= n }"
                :aria-label="`Rate ${n}`"
                :disabled="sendingRating"
                @mouseenter="hoverScore = n"
                @mouseleave="hoverScore = 0"
                @click="rate(n)"
              >
                {{ n }}
              </button>
            </div>
            <div class="mt-3 flex justify-end">
              <button
                type="button"
                class="text-xs text-gray-500 hover:text-gray-700"
                @click="showPicker=false"
              >
                {{ t("Close") }}
              </button>
            </div>
          </div>
        </div>
      </template>

      <div v-else class="animate-pulse space-y-3">
        <div class="h-6 w-60 bg-gray-200 rounded"></div>
        <div class="h-4 w-40 bg-gray-100 rounded"></div>
        <div class="h-4 w-full bg-gray-100 rounded"></div>
        <div class="h-4 w-3/4 bg-gray-100 rounded"></div>
      </div>
    </div>

    <div class="rounded-lg border bg-white shadow-sm p-5">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold">{{ t("Comments") }}</h3>
        <BaseButton type="primary" icon="comment" :label="t('Add comment')" @click="openComment" />
      </div>

      <div v-if="loadingComments" class="space-y-3">
        <div v-for="i in 3" :key="i" class="animate-pulse">
          <div class="h-4 w-40 bg-gray-100 rounded"></div>
          <div class="h-3 w-60 bg-gray-100 rounded mt-1"></div>
        </div>
      </div>
      <div v-else-if="!comments.length" class="text-sm text-gray-500">{{ t("No comment") }}</div>
      <ul v-else class="space-y-3">
        <li v-for="c in comments" :key="c.id" class="rounded bg-gray-20 p-3">
          <div class="flex items-start justify-between gap-2">
            <div class="text-sm whitespace-pre-wrap">{{ c.text }}</div>
            <div v-if="canEditComment(c)" class="shrink-0 flex gap-1">
              <BaseButton
                type="black"
                :onlyIcon="true"
                icon="edit"
                :tooltip="t('Edit')"
                aria-label="Edit comment"
                @click="openEditComment(c)"
              />
              <BaseButton
                type="danger"
                :onlyIcon="true"
                icon="trash"
                :tooltip="t('Delete')"
                aria-label="Delete comment"
                @click="confirmDeleteComment(c)"
              />
            </div>
          </div>
          <div class="text-xs text-gray-500 mt-1">— {{ c.author }} · {{ c.date }}</div>
        </li>
      </ul>
    </div>

    <CommentDialog
      v-if="showComment"
      @close="showComment=false"
      @submitted="onComment"
    />
    <CommentDialog
      v-if="showEditCommentDialog"
      :initialText="editCommentText"
      :dialogTitle="t('Edit comment')"
      :confirmLabel="t('Save')"
      :headerIcon="'pencil'"
      @close="showEditCommentDialog=false"
      @submitted="onEditCommentSubmitted"
    />

    <PostCreateDialog
      v-if="showEditPost"
      mode="edit"
      :initialTitle="editTitle"
      :initialFullText="editText"
      :dialogTitle="t('Edit post')"
      :confirmLabel="t('Save')"
      :headerIcon="'pencil'"
      :showFiles="false"
      @close="showEditPost=false"
      @save="onEditPostSave"
    />
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, computed } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import CommentDialog from "../../components/blog/CommentDialog.vue"
import PostCreateDialog from "../../components/blog/PostCreateDialog.vue"
import service from "../../services/blogs"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const blogId = Number(route.params.blogId)
const postId = Number(route.params.postId)

// Security / permissions
const securityStore = useSecurityStore()
const currentUserId = computed(() => securityStore.user?.id || null)
const isTeacherOrAdmin = computed(() => securityStore.isTeacher || securityStore.isAdmin)

// Data
const post = ref(null)
const comments = ref([])
const loading = ref(true)
const loadingComments = ref(true)
const showComment = ref(false)
const rating = ref({ average: 0, count: 0 })

// --- Rating popover state ---
const ratingScaleMax = 10 // change to 5 if you want a 5-star scale
const sendingRating = ref(false)
const showPicker = ref(false)
const triggerEl = ref(null)
const pickerEl = ref(null)
const hoverScore = ref(0)

// --- Edit post state ---
const showEditPost = ref(false)
const editTitle = ref("")
const editText = ref("")

// --- Edit comment state ---
const showEditCommentDialog = ref(false)
const editingComment = ref(null)
const editCommentText = ref("")

// Derived permissions
const canEditPost = computed(() => {
  const authorId = post.value?.authorId ?? post.value?.authorInfo?.id ?? null
  if (isTeacherOrAdmin.value) return true
  return authorId && currentUserId.value && authorId === currentUserId.value
})

function canEditComment(c) {
  const authorId = c.authorId ?? c.authorInfo?.id ?? null
  if (isTeacherOrAdmin.value) return true
  return authorId && currentUserId.value && authorId === currentUserId.value
}

// Close rating popover when clicking outside
function onDocClick(e) {
  const t = e.target
  if (!showPicker.value) return
  if (pickerEl.value?.contains(t) || triggerEl.value?.contains(t)) return
  showPicker.value = false
}
// Close with Esc
function onDocKey(e) {
  if (e.key === "Escape") showPicker.value = false
}

onMounted(() => {
  document.addEventListener("click", onDocClick, true)
  document.addEventListener("keydown", onDocKey, true)
})
onBeforeUnmount(() => {
  document.removeEventListener("click", onDocClick, true)
  document.removeEventListener("keydown", onDocKey, true)
})

function humanSize(bytes) {
  const u = ["B","KB","MB","GB"]
  let i = 0, n = bytes
  while (n > 1024 && i < u.length - 1) { n /= 1024; i++ }
  return `${n.toFixed(1)} ${u[i]}`
}
function formatDate(d){
  try { return new Date(d).toLocaleString() } catch { return d }
}

// --- Loaders ---
async function load() {
  loading.value = true
  try {
    post.value = await service.getPostApi(postId)
  } finally { loading.value = false }
}
async function loadComments() {
  loadingComments.value = true
  try {
    comments.value = await service.listComments(postId)
  } finally { loadingComments.value = false }
}
async function loadRating() {
  rating.value = await service.getPostRating(blogId, postId)
}

onMounted(async () => {
  await load()
  await loadComments()
  await loadRating()
})

// --- New comment flow ---
function openComment(){ showComment.value = true }
async function onComment(payload){
  showComment.value = false
  await service.addComment(postId, { text: payload.text, blogId })
  await loadComments()
}

// --- Post edit/delete ---
function openEditPost() {
  editTitle.value = post.value?.title || ""
  editText.value  = post.value?.fullText || ""
  showEditPost.value = true
}
async function onEditPostSave({ title, fullText }) {
  try {
    await service.updatePost(postId, { title, fullText })
    showEditPost.value = false
    await load()
  } catch (e) {
    alert(t("Failed to update the post."))
  }
}
async function confirmDeletePost() {
  if (!confirm(t("Delete this post? This action cannot be undone."))) return
  try {
    await service.deletePost(postId)
    router.push({ name: "BlogPosts", params: route.params, query: route.query })
  } catch (e) {
    alert(t("Failed to delete the post."))
  }
}

// --- Comment edit/delete ---
function openEditComment(c) {
  editingComment.value = c
  editCommentText.value = c.text || ""
  showEditCommentDialog.value = true
}
async function onEditCommentSubmitted({ text }) {
  if (!editingComment.value) return
  try {
    await service.updateComment(editingComment.value.id, { comment: text })
    showEditCommentDialog.value = false
    editingComment.value = null
    await loadComments()
  } catch (e) {
    alert(t("Failed to update the comment."))
  }
}
async function confirmDeleteComment(c) {
  if (!confirm(t("Delete this comment?"))) return
  try {
    await service.deleteComment(c.id)
    await loadComments()
  } catch (e) {
    alert(t("Failed to delete the comment."))
  }
}

// Toggle popover anchored to the trigger
function togglePicker(){
  if (sendingRating.value) return
  showPicker.value = !showPicker.value
}

// Send rating, clamp to scale, close popover and refresh
async function rate(score){
  if (sendingRating.value) return
  const s = Math.max(1, Math.min(ratingScaleMax, Number(score)))
  try {
    sendingRating.value = true
    await service.ratePost(blogId, postId, s)
    showPicker.value = false
    await loadRating()
  } finally {
    sendingRating.value = false
  }
}
</script>
