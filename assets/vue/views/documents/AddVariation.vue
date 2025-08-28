<template>
  <div class="p-4 space-y-8">
    <SectionHeader :title="t('Add file variation')">
      <BaseButton
        :label="t('Back to Documents')"
        icon="back"
        type="gray"
        @click="goBack"
      />
    </SectionHeader>

    <div
      v-if="originalFile"
      class="bg-gray-100 p-4 rounded-md shadow-md"
    >
      <h3 class="text-lg font-semibold">{{ t("Original file") }}</h3>
      <p>
        <strong>{{ t("Title:") }}</strong> {{ originalFile.originalName }}
      </p>
      <p>
        <strong>{{ t("Format:") }}</strong> {{ originalFile.mimeType }}
      </p>
      <p>
        <strong>{{ t("Size:") }}</strong> {{ prettyBytes(originalFile.size) }}
      </p>
    </div>

    <div class="space-y-6">
      <h3 class="text-xl font-bold">{{ t("Upload new variation") }}</h3>

      <form
        @submit.prevent="uploadVariation"
        class="flex flex-col space-y-4"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <BaseFileUpload
            @file-selected="onFileSelected"
            :label="t('Choose file')"
            accept=".pdf,.html,.docx,.mp4"
            required
            class="w-full"
          />

          <Dropdown
            v-model="selectedAccessUrl"
            :options="accessUrls"
            optionLabel="url"
            optionValue="id"
            placeholder="Select a URL"
            class="w-full"
          />
        </div>

        <div class="flex justify-end">
          <BaseButton
            :label="t('Upload')"
            icon="file-upload"
            type="success"
            :disabled="!file"
            @click="uploadVariant(file, originalFile?.resourceNode?.id, selectedAccessUrl)"
          />
        </div>
      </form>
    </div>

    <div>
      <h3 class="text-xl font-bold mb-4">{{ t("Current variations") }}</h3>
      <DataTable
        :value="variations"
        class="w-full"
      >
        <Column
          field="title"
          :header="t('Title')"
        />
        <Column
          field="mimeType"
          :header="t('Format')"
        />
        <Column
          field="size"
          :header="t('Size')"
        >
          <template #body="slotProps">
            {{ prettyBytes(slotProps.data.size) }}
          </template>
        </Column>
        <Column
          field="updatedAt"
          :header="t('Updated at')"
        />
        <Column
          field="url"
          :header="t('URL')"
        >
          <template #body="slotProps">
            <video
              v-if="slotProps.data.mimeType.startsWith('video/')"
              controls
              class="max-w-xs"
            >
              <source :src="slotProps.data.path" />
            </video>
            <a
              v-else
              :href="slotProps.data.path"
              target="_blank"
              class="text-blue-500 hover:underline"
            >
              {{ t("View") }}
            </a>
          </template>
        </Column>
        <Column
          field="creator"
          :header="t('Creator')"
        />
        <Column
          field="accessUrl"
          :header="t('Associated URL')"
        >
          <template #body="slotProps">
            <span>
              {{ slotProps.data.url ? slotProps.data.url : t("Default (No URL)") }}
            </span>
          </template>
        </Column>
        <Column>
          <template #header>{{ t("Actions") }}</template>
          <template #body="slotProps">
            <BaseButton
              :label="t('Delete')"
              icon="delete"
              type="danger"
              @click="deleteVariant(slotProps.data.id)"
            />
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import axios from "axios"
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import prettyBytes from "pretty-bytes"
import { useCidReq } from "../../composables/cidReq"
import { useSecurityStore } from "../../store/securityStore"

const securityStore = useSecurityStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const { cid, sid, gid } = useCidReq()
const file = ref(null)
const variations = ref([])
const originalFile = ref(null)
const resourceFileId = route.params.resourceFileId
const selectedAccessUrl = ref(null)
const accessUrls = ref([])
const isAdmin = computed(() => securityStore.isAdmin)

onMounted(async () => {
  if (!isAdmin.value) {
    await router.push({ name: "DocumentsList" })
    return
  }

  await fetchOriginalFile()
  await fetchVariations()
  await fetchAccessUrls()
})

async function fetchVariations() {
  if (!originalFile.value?.resourceNode?.id) {
    console.error("ResourceNodeId is undefined. Cannot fetch variations.")
    return
  }

  try {
    const resourceNodeId = originalFile.value.resourceNode.id
    const response = await axios.get(`/r/resource_files/${resourceNodeId}/variants`)
    variations.value = response.data
  } catch (error) {
    console.error("Error fetching variations:", error)
  }
}

async function fetchAccessUrls() {
  try {
    const response = await axios.get("/api/access_urls")
    if (Array.isArray(response.data["hydra:member"])) {
      const currentAccessUrlId = window.access_url_id

      accessUrls.value = response.data["hydra:member"].filter((url) => url.id !== currentAccessUrlId)
    } else {
      accessUrls.value = []
    }
  } catch (error) {
    console.error("Error fetching access URLs:", error)
    accessUrls.value = []
  }
}

async function fetchOriginalFile() {
  try {
    const response = await axios.get(`/api/resource_files/${resourceFileId}`)
    originalFile.value = response.data
  } catch (error) {
    console.error("Error fetching original file:", error)
  }
}

async function uploadVariant(file, resourceNodeId, accessUrlId) {
  if (!resourceNodeId) {
    console.error("ResourceNodeId is undefined. Check originalFile:", originalFile.value)
    return
  }

  const formData = new FormData()
  formData.append("file", file)
  formData.append("resourceNodeId", resourceNodeId)
  if (accessUrlId) {
    formData.append("accessUrlId", accessUrlId)
  }

  try {
    const response = await axios.post("/api/resource_files/add_variant", formData)
    console.log("Variant uploaded or updated successfully:", response.data)

    await fetchVariations()
    file.value = null
    selectedAccessUrl.value = null
  } catch (error) {
    console.error("Error uploading variant:", error)
  }
}

async function deleteVariant(variantId) {
  try {
    await axios.delete(`/r/resource_files/${variantId}/delete_variant`)
    console.log("Variant deleted successfully.")
    await fetchVariations()
  } catch (error) {
    console.error("Error deleting variant:", error)
  }
}

function onFileSelected(selectedFile) {
  file.value = selectedFile
}

function goBack() {
  let queryParams = { cid, sid, gid }
  router.push({ name: "DocumentsList", params: { node: parent.id }, query: queryParams })
}
</script>
