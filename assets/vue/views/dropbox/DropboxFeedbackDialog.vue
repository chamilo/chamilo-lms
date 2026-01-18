<template>
  <BaseDialog
    v-model:isVisible="visibleProxy"
    :title="`${t('Feedback')} — ${fileTitle}`"
    header-icon="comment"
    :width="'680px'"
  >
    <div class="flex flex-col gap-3 max-h-[75vh]">
      <!-- Messages list -->
      <div
        ref="listRef"
        class="flex-1 overflow-auto rounded-md border border-gray-50 bg-white p-3"
      >
        <!-- Loading state -->
        <div v-if="loading" class="space-y-2">
          <div class="h-4 w-40 animate-pulse rounded bg-gray-20" />
          <div class="h-16 animate-pulse rounded bg-gray-50" />
          <div class="h-4 w-28 animate-pulse rounded bg-gray-20" />
        </div>

        <!-- Loaded -->
        <template v-else>
          <div
            v-for="item in feedback"
            :key="item.id"
            class="mb-3 last:mb-0 flex gap-2"
          >
            <div class="flex-shrink-0">
              <!-- Avatar: initials -->
              <div
                class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary font-medium text-xs uppercase"
              >
                {{ initials(item.authorName) }}
              </div>
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between">
                <div class="truncate text-sm font-medium">
                  {{ item.authorName }}
                </div>
                <div class="ml-3 whitespace-nowrap text-xs text-gray-500">
                  {{ formatWhen(item.date) }}
                </div>
              </div>
              <div
                class="mt-1 rounded-2xl bg-gray-20 px-3 py-2 text-sm leading-relaxed ring-1 ring-gray-50"
                v-html="item.text"
              />
            </div>
          </div>

          <div v-if="!feedback.length" class="flex items-center gap-2 text-gray-500">
            <i :class="chamiloIconToClass['comment']"></i>
            <span>{{ t("No feedback yet.") }}</span>
          </div>
        </template>
      </div>

      <!-- Composer -->
      <div class="rounded-md border border-gray-50 bg-white p-3">
        <!-- Prefer BaseTinyEditor when available -->
        <BaseTinyEditor
          v-if="hasTiny"
          v-model="text"
          editor-id="dbx-feedback-editor"
          :title="t('Add feedback')"
          :editor-config="tinyConfig"
          :full-page="false"
          :use-file-manager="false"
        />
        <!-- Fallback: simple textarea -->
        <textarea
          v-else
          v-model="text"
          rows="3"
          class="w-full rounded-md border border-gray-300 p-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
          :placeholder="t('Add feedback')"
          @keydown.ctrl.enter.prevent="trySend"
        />

        <div class="mt-2 flex items-center justify-between">
          <div class="text-xs text-gray-500">
            {{ t('Press Ctrl+Enter to send') }}
          </div>
          <div class="flex gap-2">
            <BaseButton
              type="black"
              icon="close"
              :label="t('Close')"
              @click="close"
            />
            <BaseButton
              type="primary"
              icon="send"
              :label="t('Send')"
              :disabled="!text.trim() || sending"
              :isLoading="sending"
              @click="trySend"
            />
          </div>
        </div>
      </div>
    </div>
  </BaseDialog>
</template>

<script setup>
import { computed, ref, watch, nextTick, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import { chamiloIconToClass } from "../../components/basecomponents/ChamiloIcons"
import service from "../../services/dropbox"

const { t } = useI18n()

const props = defineProps({
  isVisible: { type: Boolean, default: false },
  fileId: { type: Number, required: true },
  fileTitle: { type: String, default: "" },
})

const emit = defineEmits(["update:isVisible", "submitted"])

const visibleProxy = computed({
  get: () => props.isVisible,
  set: (v) => emit("update:isVisible", v),
})

const hasTiny = computed(() => !!BaseTinyEditor)

const listRef = ref(null)
const feedback = ref([])
const text = ref("")
const loading = ref(false)
const sending = ref(false)

/** Compact Tiny config for dialog (shorter height, minimal toolbar). */
const tinyConfig = {
  height: 120,
  menubar: false,
  toolbar: "bold italic underline | bullist numlist | link unlink | removeformat",
  statusbar: false,
  // Add Ctrl+Enter shortcut
  setup(editor) {
    editor.addShortcut("ctrl+enter", "Send", () => {
      trySend()
    })
  },
}

/** Ensure we always scroll to bottom after render */
async function scrollToBottom() {
  await nextTick()
  const el = listRef.value
  if (el) el.scrollTop = el.scrollHeight
}

/** Load feedback items */
async function load() {
  if (!props.fileId) return
  loading.value = true
  try {
    feedback.value = await service.listFeedback(props.fileId)
    await scrollToBottom()
  } finally {
    loading.value = false
  }
}

/** Send handler with guard */
async function trySend() {
  if (!text.value.trim() || sending.value) return
  sending.value = true
  try {
    await service.createFeedback(props.fileId, text.value.trim())
    text.value = ""
    await load()
    emit("submitted")
  } finally {
    sending.value = false
  }
}

function close() {
  visibleProxy.value = false
}

function formatWhen(iso) {
  if (!iso) return ""
  try {
    const d = new Date(iso)
    return d.toLocaleString()
  } catch {
    return iso
  }
}

/** Initials helper for avatar */
function initials(name) {
  const n = String(name || "").trim()
  if (!n) return "?"
  const parts = n.split(/\s+/).slice(0, 2)
  return parts.map(p => p[0]).join("").toUpperCase()
}

/** Load when: dialog opens, or fileId changes. Also handle initial mount if opened by default. */
watch(
  () => [props.isVisible, props.fileId],
  async ([vis]) => {
    if (vis) {
      await load()
    }
  },
  { immediate: true }
)

/** Extra: if dialog might be opened with v-model already true on mount */
onMounted(async () => {
  if (props.isVisible) {
    await load()
  }
})
</script>
<style scoped>
/* Compact Tiny look in this dialog */
:deep(.tox-tinymce) {
  border-radius: 10px;
}
:deep(.tox .tox-edit-area__iframe) {
  background: #fff;
}
:deep(.tox .tox-toolbar, .tox .tox-toolbar__primary) {
  border-radius: 10px 10px 0 0;
}
</style>
