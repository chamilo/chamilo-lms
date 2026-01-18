<template>
  <BaseDialog
    v-model:isVisible="visibleProxy"
    :title="`${t('Move {0}', [fileTitle])}`"
    header-icon="file-swap"
    :width="'520px'"
  >
    <div class="space-y-4">
      <div class="text-sm text-gray-600">
        <span class="mr-1">{{ t('File') }}:</span>
        <strong>{{ fileTitle }}</strong>
      </div>

      <div>
        <label class="block text-sm mb-1">{{ t('Destination folder') }}</label>
        <BaseSelect
          v-model="targetCatId"
          :options="folderOptions"
          optionLabel="title"
          optionValue="id"
          label=""
        />
        <p class="text-xs text-gray-500 mt-2">
          {{ t("Only non-root folders are shown to avoid leaving items in root.") }}
        </p>
      </div>

      <div class="flex justify-end gap-2">
        <BaseButton
          type="black"
          icon="close"
          :label="t('Cancel')"
          @click="close"
        />
        <BaseButton
          type="primary"
          icon="check"
          :label="t('Move')"
          :disabled="saving || targetCatId === null"
          :isLoading="saving"
          @click="submit"
        />
      </div>
    </div>
  </BaseDialog>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import service from "../../services/dropbox"

const { t } = useI18n()

const props = defineProps({
  isVisible: { type: Boolean, default: false },
  fileId: { type: Number, required: true },
  fileTitle: { type: String, default: "" },
  area: { type: String, default: "sent" },
  currentCategoryId: { type: Number, default: 0 },
})

const emit = defineEmits(["update:isVisible", "moved"])

const visibleProxy = computed({
  get: () => props.isVisible,
  set: (v) => emit("update:isVisible", v),
})

const folderOptions = ref([])
const targetCatId = ref(null)
const saving = ref(false)

async function loadFolders() {
  // Load and exclude Root (id===0) so items are not left in root.
  const cats = await service.listCategories({ area: props.area })
  folderOptions.value = (cats || []).filter(c => c && Number(c.id) !== 0)

  // Preselect the first different folder from the current one
  const firstDifferent = folderOptions.value.find(c => Number(c.id) !== Number(props.currentCategoryId))
  targetCatId.value = firstDifferent ? Number(firstDifferent.id) : (folderOptions.value[0]?.id ?? null)
  if (targetCatId.value != null) targetCatId.value = Number(targetCatId.value)
}

onMounted(() => {
  if (props.isVisible) loadFolders()
})
watch(() => props.isVisible, (v) => { if (v) loadFolders() })

function close() {
  visibleProxy.value = false
}

async function submit() {
  if (targetCatId.value == null) return
  saving.value = true
  try {
    await service.moveFile({
      id: props.fileId,
      targetCatId: Number(targetCatId.value),
      area: props.area,
    })
    emit("moved")
    close()
  } finally {
    saving.value = false
  }
}
</script>
