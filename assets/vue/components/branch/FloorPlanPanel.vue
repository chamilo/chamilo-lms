<template>
  <section class="mt-5 rounded border p-4">
    <h3 class="font-semibold">{{ t("Floor plan") }}</h3>
    <p class="mt-1 text-sm text-gray-600">
      {{ t("Upload a PNG/JPEG floor plan, or select an existing image from Documents.") }}
    </p>

    <div class="mt-4 flex flex-wrap items-end gap-3 rounded border p-3">
      <div class="min-w-52 flex-1">
        <label for="branch_floor_plan_upload" class="mb-1 block text-sm">{{ t("Upload floor plan") }}</label>
        <input
          id="branch_floor_plan_upload"
          ref="uploadInput"
          name="branch_floor_plan_upload"
          type="file"
          accept="image/png,image/jpeg,.png,.jpg,.jpeg"
          class="block w-full text-sm"
          :disabled="busy || searching"
          @change="onFileSelected"
        />
      </div>
      <BaseButton
        :label="t('Upload floor plan')"
        icon="file-upload"
        type="primary"
        :is-loading="busy"
        :disabled="!selectedFile || busy || searching"
        @click="uploadPlan"
      />
      <p class="w-full text-xs text-gray-600">{{ t("PNG or JPEG, maximum 10 MB") }}</p>
    </div>

    <div class="mt-4 flex flex-wrap items-end gap-2">
      <div class="min-w-48 flex-1">
        <label for="floor_plan_search" class="mb-1 block text-sm">
          {{ t("Search images by name") }}
        </label>
        <input
          id="floor_plan_search"
          v-model.trim="search"
          name="floor_plan_search"
          type="search"
          maxlength="80"
          class="w-full rounded border p-2"
          :placeholder="t('Image name')"
          @keydown.enter.prevent="loadOptions"
        />
      </div>
      <BaseButton
        :label="t('Search')"
        icon="search"
        type="secondary"
        :is-loading="searching"
        :disabled="busy"
        @click="loadOptions"
      />
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3">
      <div class="min-w-64 flex-1">
        <BaseSelect
          id="floor_plan_image"
          v-model="nodeId"
          name="floor_plan_image"
          :label="t('Image')"
          :options="options"
          option-label="name"
          option-value="id"
          :is-loading="searching"
          :disabled="busy"
          :placeholder="t('Select an image')"
        />
      </div>
      <BaseButton
        :label="planId ? t('Change floor plan') : t('Attach floor plan')"
        icon="check"
        :is-loading="busy"
        :disabled="!Number.isInteger(nodeId) || nodeId < 1 || searching || busy || nodeId === planId"
        @click="attach"
      />
    </div>

    <p v-if="error" role="alert" class="mt-2 text-sm text-red-600">{{ error }}</p>
    <p v-if="!searching && !options.length" class="mt-2 text-sm text-gray-600">
      {{ t("No matching images found in Documents.") }}
    </p>

    <div v-if="planId" class="mt-4">
      <p class="mb-2 text-sm text-gray-600">
        {{ t("Current floor plan") }}: {{ currentPlanName }}
      </p>
      <img
        :key="previewUrl"
        :src="previewUrl"
        :alt="t('Floor plan')"
        class="max-h-96 max-w-full rounded border"
      />
      <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-600">
          {{ t("Floor plan changes are saved automatically.") }}
        </p>
        <BaseButton
          :label="t('Remove floor plan')"
          icon="delete"
          type="danger"
          :disabled="busy"
          @click="confirmDetach"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import baseService from "../../services/baseService"

const props = defineProps({ branch: { type: Object, default: () => ({}) } })
const { t } = useI18n()
const { requireConfirmation } = useConfirmation()
const { showSuccessNotification } = useNotification()
const busy = ref(false)
const searching = ref(false)
const error = ref("")
const search = ref("")
const options = ref([])
const nodeId = ref(null)
const planId = ref(null)
const refresh = ref(0)
const selectedFile = ref(null)
const uploadInput = ref(null)
const id = computed(() => Number(props.branch.id || String(props.branch["@id"] || "").split("/").pop() || 0))
const url = computed(() => `/api/branches/${id.value}/floor-plan`)
// The attached resource changes behind one endpoint. Give every revision a distinct image URL.
const previewUrl = computed(() => `${url.value}?node=${planId.value || 0}&revision=${refresh.value}`)
const currentPlanName = computed(() => {
  const current = options.value.find((item) => Number(item.id) === Number(planId.value))
  return current?.name || `#${planId.value}`
})

watch(() => props.branch.floorPlanId, (value) => {
  nodeId.value = value || null
  planId.value = value || null
}, { immediate: true })
watch(id, (value) => { if (value > 0) loadOptions() }, { immediate: true })

async function loadOptions() {
  if (!id.value) return
  searching.value = true
  error.value = ""
  try {
    const data = await baseService.get(`${url.value}/options`, { q: search.value })
    options.value = data.items || []
  } catch (e) {
    error.value = e?.response?.data?.detail || e?.message || t("Cannot load images")
  } finally {
    searching.value = false
  }
}

async function attach() {
  if (!id.value || !Number.isInteger(nodeId.value) || busy.value) return
  busy.value = true
  error.value = ""
  try {
    const data = await baseService.put(url.value, { resourceNodeId: nodeId.value })
    planId.value = data.floorPlanId
    refresh.value++
    showSuccessNotification(t("Floor plan saved"))
  } catch (e) {
    error.value = e?.response?.data?.detail || e?.message || t("Cannot attach floor plan")
  } finally {
    busy.value = false
  }
}

function onFileSelected(event) {
  selectedFile.value = event.target.files?.[0] || null
}

async function uploadPlan() {
  if (!id.value || !selectedFile.value || busy.value) return
  error.value = ""
  const file = selectedFile.value
  if (!(["image/png", "image/jpeg"].includes(file.type)) || file.size > 10485760 || file.size < 1) {
    error.value = t("Only PNG/JPEG images up to 10MB are supported.")
    return
  }
  busy.value = true
  try {
    const form = new FormData()
    form.append("file", file)
    const data = await baseService.post(`${url.value}/upload`, form)
    planId.value = Number(data.floorPlanId)
    nodeId.value = planId.value
    refresh.value++
    selectedFile.value = null
    if (uploadInput.value) uploadInput.value.value = ""
    await loadOptions()
    showSuccessNotification(t("Floor plan saved"))
  } catch (e) {
    error.value = e?.response?.data?.detail || e?.message || t("Cannot upload floor plan")
  } finally {
    busy.value = false
  }
}

function confirmDetach() {
  requireConfirmation({
    title: t("Remove floor plan"),
    message: t("Are you sure you want to remove the floor plan?"),
    accept: detach,
  })
}

async function detach() {
  if (!id.value || busy.value) return
  busy.value = true
  error.value = ""
  try {
    await baseService.delete(url.value)
    planId.value = null
    nodeId.value = null
    refresh.value++
    showSuccessNotification(t("Floor plan removed"))
  } catch (e) {
    error.value = e?.response?.data?.detail || e?.message || t("Cannot remove floor plan")
  } finally {
    busy.value = false
  }
}
</script>
