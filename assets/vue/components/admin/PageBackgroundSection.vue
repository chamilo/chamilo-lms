<script setup>
import { ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseFileUpload from "../basecomponents/BaseFileUpload.vue"
import themePageBackgroundService from "../../services/themePageBackgroundService"
import { usePlatformConfig } from "../../store/platformConfig"

const props = defineProps({
  slug: { type: String, required: true },
})

const MAX_BYTES = 2 * 1024 * 1024
const ACCEPTED_TYPES = ["image/png", "image/jpeg", "image/webp"]

const { t } = useI18n()
const { showErrorNotification, showSuccessNotification } = useNotification()
const { requireConfirmation } = useConfirmation()
const platformConfigStore = usePlatformConfig()

// Where each slot is shown, as the pages that display it
const slotLabels = {
  landscape: ["My courses", "My sessions"],
  cityscape: ["Administration"],
  fieldscape: ["Sessions management"],
  seascape: ["Social network"],
}

const backgrounds = ref([])
const selectedFiles = ref({})
// Changing the key empties the file field once its image is uploaded
const uploadKeys = ref({})
const isLoading = ref(false)
const savingSlot = ref(null)

/**
 * Keeps the backgrounds of the current page in step with what was just saved.
 */
function applyBackgrounds(items) {
  backgrounds.value = items
  platformConfigStore.pageBackgrounds = Object.fromEntries(
    items.map((item) => [item.slot, item.hidden ? null : item.imageUrl]),
  )
}

async function refresh() {
  const slug = props.slug
  isLoading.value = true
  try {
    const items = await themePageBackgroundService.list(slug)
    if (slug === props.slug) backgrounds.value = items
  } catch (error) {
    if (slug === props.slug) showErrorNotification(error)
  } finally {
    if (slug === props.slug) isLoading.value = false
  }
}

watch(
  () => props.slug,
  () => {
    selectedFiles.value = {}
    refresh()
  },
  { immediate: true },
)

async function runAction(slot, action, successMessage) {
  savingSlot.value = slot
  try {
    applyBackgrounds(await action())
    showSuccessNotification(successMessage)

    return true
  } catch (error) {
    showErrorNotification(error)

    return false
  } finally {
    savingSlot.value = null
  }
}

async function upload(slot) {
  const file = selectedFiles.value[slot]
  if (!file) return
  if (!ACCEPTED_TYPES.includes(file.type) || file.size > MAX_BYTES) {
    showErrorNotification(t("Select a PNG, JPEG or WebP image smaller than 2 MB."))
    return
  }

  const uploaded = await runAction(slot, () => themePageBackgroundService.upload(props.slug, slot, file), t("Saved"))
  if (!uploaded) return
  selectedFiles.value = { ...selectedFiles.value, [slot]: null }
  uploadKeys.value = { ...uploadKeys.value, [slot]: (uploadKeys.value[slot] || 0) + 1 }
}

function setHidden(item, hidden) {
  runAction(item.slot, () => themePageBackgroundService.setHidden(props.slug, item.slot, hidden), t("Saved"))
}

function restoreDefault(item) {
  requireConfirmation({
    message: t("Delete the uploaded image and restore the image of the theme?"),
    accept: () =>
      runAction(
        item.slot,
        () => themePageBackgroundService.restoreDefault(props.slug, item.slot),
        t("Theme image restored"),
      ),
  })
}
</script>

<template>
  <div class="space-y-4">
    <p class="text-sm opacity-80">
      {{
        t(
          "Images shown at the bottom of some pages, repeated horizontally. Upload a PNG, JPEG or WebP image (2 MB maximum) to replace one, or hide it.",
        )
      }}
    </p>

    <div
      v-if="isLoading"
      class="text-sm text-gray-600"
    >
      {{ t("Loading") }}
    </div>

    <div
      v-else
      class="grid grid-cols-1 gap-6 md:grid-cols-2"
    >
      <div
        v-for="item in backgrounds"
        :key="item.slot"
        class="space-y-4 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
      >
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h5 class="text-base font-semibold">{{ slotLabels[item.slot].map((label) => t(label)).join(" / ") }}</h5>
          <span
            :class="item.hidden ? 'bg-gray-100 text-gray-700' : 'bg-green-100 text-green-700'"
            class="rounded-full px-2 py-0.5 text-xs"
          >
            {{ item.hidden ? t("Hidden") : t("Visible") }}
          </span>
        </div>

        <div
          :class="{ 'opacity-40': item.hidden }"
          :style="item.imageUrl ? { backgroundImage: `url('${item.imageUrl}')` } : {}"
          class="h-24 rounded-xl border border-gray-100 bg-gray-20 bg-bottom bg-repeat-x [background-size:auto_96px]"
        />
        <p class="text-xs opacity-70">
          {{ item.custom ? t("Uploaded image") : t("Image of the theme") }}
        </p>

        <div class="flex flex-wrap items-end gap-2 [&_.field]:mb-0">
          <BaseFileUpload
            :key="`${item.slot}-${uploadKeys[item.slot] || 0}`"
            :label="t('Choose file')"
            :name="`page_background_${item.slot}`"
            accept="image/png,image/jpeg,image/webp"
            size="small"
            @file-selected="selectedFiles = { ...selectedFiles, [item.slot]: $event }"
          />
          <BaseButton
            :disabled="!selectedFiles[item.slot] || null !== savingSlot"
            :is-loading="savingSlot === item.slot"
            :label="t('Upload')"
            :name="`page_background_upload_${item.slot}`"
            icon="upload"
            size="small"
            type="success"
            @click="upload(item.slot)"
          />
        </div>

        <div class="flex flex-wrap gap-2">
          <BaseButton
            v-if="item.hidden"
            :disabled="null !== savingSlot"
            :label="t('Show')"
            :name="`page_background_show_${item.slot}`"
            icon="eye-on"
            size="small"
            type="primary"
            @click="setHidden(item, false)"
          />
          <BaseButton
            v-else
            :disabled="null !== savingSlot"
            :label="t('Hide')"
            :name="`page_background_hide_${item.slot}`"
            icon="eye-off"
            size="small"
            type="danger"
            @click="setHidden(item, true)"
          />
          <BaseButton
            v-if="item.custom"
            :disabled="null !== savingSlot"
            :label="t('Restore the image of the theme')"
            :name="`page_background_restore_${item.slot}`"
            icon="restore"
            size="small"
            type="secondary"
            @click="restoreDefault(item)"
          />
        </div>
      </div>
    </div>
  </div>
</template>
