<template>
  <BaseDialog
    :isVisible="true"
    :title="t('New Post')"
    header-icon="plus"
    :width="'680px'"
    @close="$emit('close')"
  >
    <div class="space-y-3">
      <BaseInputText v-model="title" :label="t('Title')" :placeholder="t('Write a title…')" />
      <textarea v-model="fullText" class="w-full h-32 border rounded p-2" :placeholder="t('Write your content…')" />
      <div
        class="border-2 border-dashed rounded p-4 text-center"
        @dragover.prevent
        @drop.prevent="onDrop"
      >
        <p class="text-sm text-gray-600">{{ t('Drag & drop files here or click to select') }}</p>
        <input ref="fileInput" type="file" class="hidden" multiple @change="onPick" />
        <BaseButton type="black" icon="paperclip" :label="t('Choose files')" @click="$refs.fileInput.click()" />
      </div>

      <ul v-if="files.length" class="text-sm list-disc pl-5">
        <li v-for="(f, i) in files" :key="i">{{ f.name }} ({{ prettySize(f.size) }})</li>
      </ul>
    </div>

    <template #footer>
      <BaseButton type="black" icon="close" :label="t('Cancel')" @click="$emit('close')" />
      <BaseButton :disabled="submitting || !title.trim()" type="primary" icon="check" :label="t('Create')" @click="submit" />
    </template>
  </BaseDialog>
</template>

<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import service from "../../services/blogs"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseDialog from "../basecomponents/BaseDialog.vue"

const { t } = useI18n()

const emit = defineEmits(["close","created"])

const title = ref("")
const fullText = ref("")
const files = ref([])
const submitting = ref(false)

function onDrop(e){ files.value = [...files.value, ...Array.from(e.dataTransfer.files || [])] }
function onPick(e){ files.value = [...files.value, ...Array.from(e.target.files || [])]; e.target.value = "" }
function prettySize(n){ return `${(n/1024/1024).toFixed(2)} MB` }

async function submit(){
  submitting.value = true
  try {
    const blogId = Number(location.pathname.match(/\/blog\/(?:\d+\/)?(\d+)/)?.[1] || 0)
    await service.createPostWithFiles({ blogId, title: title.value.trim(), fullText: fullText.value, files: files.value })
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
