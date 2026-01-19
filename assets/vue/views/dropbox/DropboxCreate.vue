<template>
  <div class="w-full">
    <BaseToolbar>
      <template #start>
        <div class="flex items-center gap-2">
          <h3 class="font-semibold text-lg">{{ t("Share a new file") }}</h3>
          <span class="text-gray-400">·</span>
          <span class="text-sm text-gray-500">{{ t("Upload first, organize later") }}</span>
        </div>
      </template>
      <template #end>
        <RouterLink :to="returnRoute">
          <BaseButton
            type="black"
            icon="arrow-left"
            :label="t('Back')"
          />
        </RouterLink>
      </template>
    </BaseToolbar>

    <div
      class="grid mt-5 gap-6"
      style="grid-template-columns: 1.6fr 1fr"
    >
      <!-- LEFT: files -->
      <div>
        <div class="rounded-lg border border-gray-50 bg-white shadow-sm">
          <div class="px-4 py-3 border-b text-sm font-medium">{{ t("Files") }}</div>
          <div class="p-4">
            <div class="uppy-wrap">
              <Dashboard
                v-if="uppy"
                :uppy="uppy"
                class="w-full"
                :props="{
                  proudlyDisplayPoweredByUppy: false,
                  showProgressDetails: true,
                  hideUploadButton: true,
                  height: 360,
                  width: '100%',
                  note: t('Drag & drop or browse to add files'),
                }"
              />
            </div>

            <div
              v-if="pickedFiles.length"
              class="mt-3"
            >
              <div class="text-sm text-gray-600 mb-1">{{ t("Selected files") }}</div>
              <ul class="text-sm space-y-1">
                <li
                  v-for="f in pickedFiles"
                  :key="f.name + ':' + f.size"
                  class="flex items-center justify-between gap-3 rounded border px-2 py-1.5"
                >
                  <div class="truncate">
                    <span class="font-medium">{{ f.name }}</span>
                    <span class="text-gray-500 ml-2">({{ humanSize(f.size) }})</span>
                  </div>
                  <button
                    class="text-gray-400 hover:text-red-600"
                    :title="t('Remove')"
                    @click="removePicked(f)"
                  >
                    ✕
                  </button>
                </li>
              </ul>
            </div>

            <div
              v-else
              class="mt-3 text-sm text-gray-500"
            >
              {{ t("No file selected.") }}
            </div>
          </div>
        </div>

        <div class="rounded-lg border border-gray-50 bg-white shadow-sm mt-6">
          <div class="px-4 py-3 border-b text-sm font-medium">{{ t("Details") }}</div>
          <div class="p-4">
            <BaseInputText
              id="dbx-desc"
              :label="t('Description')"
              v-model="description"
              :form-submitted="submitted"
              :is-invalid="false"
            />

            <label class="inline-flex items-center gap-3 mt-3">
              <input
                id="overwrite"
                type="checkbox"
                v-model="overwrite"
              />
              <span class="text-sm">{{ t("Overwrite previous versions of same document?") }}</span>
            </label>
          </div>
        </div>
      </div>

      <!-- RIGHT: recipients + submit -->
      <div>
        <div class="rounded-lg border border-gray-50 bg-white shadow-sm">
          <div class="px-4 py-3 border-b text-sm font-medium">{{ t("Sharing") }}</div>
          <div class="p-4">
            <label class="block text-sm mb-1"> {{ t("Recipients") }} <span class="text-red-600">*</span> </label>
            <BaseSelect
              v-model="recipients"
              :options="recipientOptions"
              optionLabel="label"
              optionValue="value"
              multiple
              :placeholder="t('Choose recipients')"
              style="width: 100%"
              label=""
              :class="{ 'ring-1 ring-red-500 rounded-md': submitted && !hasSelectedRecipient }"
            />
            <small class="text-gray-500 block mt-1">
              {{ t("Tip: choose “— Just upload —” to store without sending to anyone.") }}
            </small>
            <div
              v-if="submitted && !hasSelectedRecipient"
              class="text-sm text-red-600 mt-1"
            >
              {{ t("Please select at least one recipient (“— Just upload —” or any user)") }}
            </div>

            <div class="flex justify-end gap-2 mt-6">
              <RouterLink :to="returnRoute">
                <BaseButton
                  type="black"
                  icon="xmark"
                  :label="t('Cancel')"
                />
              </RouterLink>
              <BaseButton
                type="primary"
                icon="check"
                :label="t('Upload')"
                :disabled="!canSubmit || isUploading"
                @click="submit"
              />
            </div>
          </div>
        </div>

        <div
          v-if="isUploading"
          class="text-xs text-gray-500 mt-2 text-right"
        >
          {{ t("Uploading... please keep this tab open.") }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, markRaw, onBeforeUnmount, onMounted, ref, shallowRef } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import Uppy from "@uppy/core"
import { Dashboard } from "@uppy/vue"
import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"

import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"

import service from "../../services/dropbox"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const returnRouteName = computed(() => (route.query?.from === "received" ? "DropboxListReceived" : "DropboxListSent"))
const returnRoute = computed(() => ({ name: returnRouteName.value, params: route.params, query: route.query }))
const uppy = shallowRef(null)

const pickedFiles = ref([])
const description = ref("")
const overwrite = ref(false)
const submitted = ref(false)
const isUploading = ref(false)

// recipients
const recipients = ref([])
const recipientOptions = ref([])
let syncPicked = null

onMounted(() => {
  uppy.value = markRaw(
    new Uppy({
      autoProceed: false,
      allowMultipleUploads: true,
      restrictions: { maxNumberOfFiles: null },
    }),
  )

  syncPicked = () => {
    const files = uppy.value?.getFiles?.() ?? []
    pickedFiles.value = files.map((f) => f.data)
  }
  uppy.value?.on?.("file-added", syncPicked)
  uppy.value?.on?.("file-removed", syncPicked)
})

onBeforeUnmount(() => {
  if (!uppy.value) return

  try {
    if (syncPicked) {
      uppy.value?.off?.("file-added", syncPicked)
      uppy.value?.off?.("file-removed", syncPicked)
    }
  } catch {}

  try {
    uppy.value.cancelAll()
  } catch {}
  try {
    uppy.value.reset()
  } catch {}

  try {
    uppy.value.close?.()
  } catch {}

  uppy.value = null
})

function removePicked(file) {
  // remove by id match in uppy store
  const list = uppy.value?.getFiles?.() ?? []
  const record = list.find((r) => r.data === file)
  if (record) uppy.value?.removeFile?.(record.id)
  else pickedFiles.value = pickedFiles.value.filter((f) => f !== file)
}

// utils
function humanSize(bytes) {
  const units = ["B", "KB", "MB", "GB", "TB"]
  const i = bytes > 0 ? Math.floor(Math.log(bytes) / Math.log(1024)) : 0
  return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${units[i]}`
}

// normalization
function toTokens(value) {
  if (value == null) return []
  const arr = Array.isArray(value) ? value : [value]
  return arr
    .map((v) => {
      if (typeof v === "string") return v
      if (v && typeof v === "object") {
        if ("value" in v && typeof v.value === "string") return v.value
        if ("value" in v && typeof v.value === "number") return String(v.value)
        if ("id" in v) return String(v.id)
      }
      return null
    })
    .filter(Boolean)
}

const normalizedRecipientTokens = computed(() => toTokens(recipients.value))
const hasSelectedRecipient = computed(() => normalizedRecipientTokens.value.length > 0)
const canSubmit = computed(() => pickedFiles.value.length > 0 && hasSelectedRecipient.value)

// submit
async function submit() {
  submitted.value = true
  if (!canSubmit.value || isUploading.value) return

  isUploading.value = true
  try {
    const tokens = normalizedRecipientTokens.value
    const context = {
      cid: Number(new URLSearchParams(window.location.search).get("cid") || 0),
      sid: Number(new URLSearchParams(window.location.search).get("sid") || 0),
      gid: Number(new URLSearchParams(window.location.search).get("gid") || 0),
    }

    for (const file of pickedFiles.value) {
      await service.uploadFile({
        file,
        description: description.value,
        overwrite: overwrite.value,
        recipients: tokens,
        area: "sent",
        context,
      })
    }

    alert(t("File upload succeeded!"))
    // Reset minimal state
    description.value = ""
    overwrite.value = false
    recipients.value = []
    try {
      uppy.value?.reset?.()
    } catch {}
    pickedFiles.value = []

    // Navigate back to list (sent)
    router.push(returnRoute.value)
  } catch (e) {
    console.error(e)
    alert(t("Upload failed"))
  } finally {
    isUploading.value = false
  }
}

// initial data
onMounted(async () => {
  recipientOptions.value = await service.listRecipients()
})
</script>
