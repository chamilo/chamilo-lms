<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <BaseIcon
        icon="back"
        size="big"
        @click="goBack"
        :title="t('Back')"
      />
    </div>
    <hr />

    <h1 class="text-2xl font-bold">{{ t("Upload your assignment") }} – {{ publicationTitle }}</h1>

    <p
      v-if="allowedExtensions.length > 0"
      class="text-gray-600"
    >
      <span class="font-semibold">{{ t("Allowed file formats: {0}", [allowedExtensions.map(ext => '.' + ext).join(', ')]) }}</span>
    </p>

    <div
      v-if="allowText && !allowFile"
      class="space-y-4"
    >
      <BaseInputText
        v-model="submissionTitle"
        :label="t('Submission title')"
        :placeholder="t('Enter a title for your submission')"
      />
      <label class="font-semibold">{{ t("Your answer") }}</label>
      <textarea
        v-model="text"
        class="w-full border rounded p-2"
        rows="10"
        :placeholder="t('Write your answer here...')"
      />
      <BaseButton
        :label="t('Submit')"
        type="primary"
        @click="submitText"
      />
    </div>

    <div v-else-if="allowFile && !allowText">
      <Dashboard
        :uppy="uppy"
        :props="{ width: '100%', height: 300 }"
      />
    </div>

    <div
      v-else
      class="space-y-4"
    >
      <div class="border-b flex gap-4">
        <button
          v-if="allowText"
          :class="tabClass('text')"
          @click="activeTab = 'text'"
        >
          {{ t("Text") }}
        </button>
        <button
          v-if="allowFile"
          :class="tabClass('file')"
          @click="activeTab = 'file'"
        >
          {{ t("File") }}
        </button>
      </div>

      <div
        v-if="activeTab === 'text'"
        class="space-y-2"
      >
        <BaseInputText
          v-model="submissionTitle"
          :label="t('Submission title')"
          :placeholder="t('Enter a title for your submission')"
        />
        <textarea
          v-model="text"
          class="w-full border rounded p-2"
          rows="8"
          :placeholder="t('Write your answer here...')"
        />
      </div>

      <div
        v-else
        class="space-y-2"
      >
        <Dashboard
          :uppy="uppy"
          :props="{ width: '100%', height: 200 }"
        />
      </div>

      <BaseButton
        :label="t('Submit')"
        type="primary"
        @click="submitMixed"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useCidReq } from "../../composables/cidReq"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import { Dashboard } from "@uppy/vue"
import Uppy from "@uppy/core"
import XHRUpload from "@uppy/xhr-upload"
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { cid, sid, gid } = useCidReq()
const { showSuccessNotification, showErrorNotification } = useNotification()
const allowText = route.query.allowText === "1"
const allowFile = route.query.allowFile === "1"
const publicationId = Number(route.params.id)
const parentResourceNodeId = Number(route.params.node)
const publicationTitle = ref("")
const text = ref("")
const submissionTitle = ref("")
const activeTab = ref(allowText ? "text" : "file")
const allowedExtensions = ref([])

onMounted(loadPublicationTitle)
async function loadPublicationTitle() {
  try {
    const { data } = await axios.get(`${ENTRYPOINT}c_student_publications/${publicationId}`, {
      params: { cid, ...(sid && { sid }), ...(gid && { gid }) },
    })
    publicationTitle.value = data.title
    submissionTitle.value = data.title

    if (data.extensions) {
      allowedExtensions.value = data.extensions
        .split(' ')
        .map(ext => ext.trim().toLowerCase())
        .filter(ext => ext.length > 0) }
  } catch (e) {
    console.error("Error loading publication metadata", e)
  }
}

function isFileExtensionAllowed(filename) {
  if (allowedExtensions.value.length === 0) {
    return true
  }

  const fileExtension = filename.split('.').pop().toLowerCase()
  return allowedExtensions.value.includes(fileExtension)
}


const queryParams = new URLSearchParams({
  cid,
  ...(sid && { sid }),
  ...(gid && { gid }),
}).toString()

const uppy = new Uppy({
  restrictions: { maxNumberOfFiles: 1 },
  autoProceed: true,
})
uppy.use(XHRUpload, {
  endpoint: `/api/c_student_publications/upload?${queryParams}`,
  formData: true,
  fieldName: "uploadFile",
})
uppy.on("file-added", (file) => {
  if (!isFileExtensionAllowed(file.name)) {
    uppy.removeFile(file.id)
    showErrorNotification(
      t("File type not allowed. Allowed extensions: {0}", [allowedExtensions.value.map(ext => '.' + ext).join(', ')])
    )
    return
  }
  uppy.setMeta({
    title: file.name,
    filetype: "file",
    parentResourceNodeId,
    parentId: publicationId,
    resourceLinkList: JSON.stringify([{ cid, sid, gid, visibility: 2 }]),
  })
})
uppy.on("upload-success", () => {
  showSuccessNotification(t("File uploaded successfully"))
  router.back()
})
uppy.on("upload-error", () => {
  showErrorNotification(t("Failed to upload file"))
})

async function submitText() {
  if (!submissionTitle.value.trim() || !text.value.trim()) {
    return showErrorNotification(t("Please provide a title and some text"))
  }

  const blob = new Blob([text.value], { type: "text/plain" })
  const formData = new FormData()
  formData.append("title", submissionTitle.value)
  formData.append("description", text.value)
  formData.append("uploadFile", blob, `${submissionTitle.value}.txt`)
  formData.append("filetype", "file") // ahora sí "file"
  formData.append("parentId", publicationId)
  formData.append("parentResourceNodeId", parentResourceNodeId)
  formData.append("resourceLinkList", JSON.stringify([{ cid, sid, gid, visibility: 2 }]))

  try {
    await axios.post(`/api/c_student_publications/upload?${queryParams}`, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    })
    showSuccessNotification(t("Text submitted successfully"))
    router.back()
  } catch (e) {
    showErrorNotification(e)
  }
}

function submitMixed() {
  if (activeTab.value === "text") {
    return submitText()
  }
}

function goBack() {
  router.push({
    name: "AssignmentDetail",
    params: { id: publicationId },
    query: route.query,
  })
}

function tabClass(tab) {
  return ["px-4 py-2 -mb-px", activeTab.value === tab ? "border-b-2 border-primary font-semibold" : "text-gray-600"]
}
</script>
