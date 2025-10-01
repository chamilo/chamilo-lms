<template>
  <BaseDialog
    :isVisible="true"
    :title="dialogTitleComputed"
    :header-icon="headerIconComputed"
    :width="'680px'"
    @close="$emit('close')"
  >
    <div class="space-y-3">
      <BaseInputText
        v-model="title"
        :label="t('Title')"
        :placeholder="t('Write a title…')"
      />

      <BaseTinyEditor
        v-model="fullText"
        editor-id="post-fulltext"
        :title="t('Content')"
        :required="false"
        :use-file-manager="true"
        :full-page="true"
        :editor-config="tinyConfig"
        :help-text="t('Use the editor toolbar to format your content.')"
      />

      <div
        v-if="showFilesComputed"
        class="border-2 border-dashed rounded p-4 text-center"
        @dragover.prevent
        @drop.prevent="onDrop"
      >
        <p class="text-sm text-gray-600">{{ t('Drag & drop files here or click to select') }}</p>
        <input ref="fileInput" type="file" class="hidden" multiple @change="onPick" />
        <BaseButton type="black" icon="paperclip" :label="t('Choose files')" @click="$refs.fileInput.click()" />
      </div>

      <ul v-if="showFilesComputed && files.length" class="text-sm list-disc pl-5">
        <li v-for="(f, i) in files" :key="i">{{ f.name }} ({{ prettySize(f.size) }})</li>
      </ul>
    </div>

    <template #footer>
      <BaseButton type="black" icon="close" :label="t('Cancel')" @click="$emit('close')" />
      <BaseButton
        :disabled="submitting || !title.trim()"
        type="primary"
        :icon="confirmIconComputed"
        :label="confirmLabelComputed"
        @click="onPrimaryAction"
      />
    </template>
  </BaseDialog>
</template>

<script setup>
import { ref, computed } from "vue"
import { useI18n } from "vue-i18n"
import service from "../../services/blogs"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseDialog from "../basecomponents/BaseDialog.vue"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"

const { t } = useI18n()
const emit = defineEmits(["close","created","save"])

const props = defineProps({
  mode: { type: String, default: "create" }, // "create" | "edit"
  initialTitle: { type: String, default: "" },
  initialFullText: { type: String, default: "" },
  showFiles: { type: Boolean, default: undefined },
  dialogTitle: { type: String, default: "" },
  confirmLabel: { type: String, default: "" },
  headerIcon: { type: String, default: "" },
})

// Form state
const title = ref(props.initialTitle)
const fullText = ref(props.initialFullText)
const files = ref([])
const submitting = ref(false)

const tinyConfig = {
  height: 420,
}

// Computed UI
const isEdit = computed(() => props.mode === "edit")
const dialogTitleComputed = computed(() =>
  props.dialogTitle || (isEdit.value ? t("Edit Post") : t("New Post"))
)
const confirmLabelComputed = computed(() =>
  props.confirmLabel || (isEdit.value ? t("Save") : t("Create"))
)
const headerIconComputed = computed(() => props.headerIcon || (isEdit.value ? "pencil" : "plus"))
const confirmIconComputed = computed(() => (isEdit.value ? "check" : "check"))
const showFilesComputed = computed(() => {
  if (typeof props.showFiles === "boolean") return props.showFiles
  return !isEdit.value
})

// Files helpers
function onDrop(e){ files.value = [...files.value, ...Array.from(e.dataTransfer.files || [])] }
function onPick(e){ files.value = [...files.value, ...Array.from(e.target.files || [])]; e.target.value = "" }
function prettySize(n){ return `${(n/1024/1024).toFixed(2)} MB` }

// Primary action
async function onPrimaryAction(){
  if (!title.value.trim()) return

  if (isEdit.value) {
    // Emit to parent (BlogPostDetail) -> service.updatePost(...)
    emit("save", { title: title.value.trim(), fullText: fullText.value })
    return
  }

  // Create flow (se mantiene)
  submitting.value = true
  try {
    const blogId = Number(location.pathname.match(/\/blog\/(?:\d+\/)?(\d+)/)?.[1] || 0)
    await service.createPostWithFiles({
      blogId,
      title: title.value.trim(),
      fullText: fullText.value,
      files: files.value,
    })
    emit("created")
  } catch (e) {
    // eslint-disable-next-line no-console
    console.error("create post with files failed", e)
    alert(t("Failed to create the post. Please try again."))
  } finally {
    submitting.value = false
  }
}
</script>
