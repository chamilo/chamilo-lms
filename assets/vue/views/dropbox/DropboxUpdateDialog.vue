<template>
  <BaseDialog
    v-model:isVisible="visibleProxy"
    :title="`${t('Update file')} — ${fileTitle}`"
    header-icon="file-replace"
    :width="'720px'"
  >
    <div class="space-y-4">
      <!-- Summary -->
      <div class="text-sm text-gray-600">
        <span class="mr-2">{{ t("Current") }}:</span>
        <strong>{{ fileTitle }}</strong>
      </div>

      <!-- Uploader -->
      <div class="border rounded p-3">
        <div class="flex items-center justify-between">
          <div class="font-medium">{{ t("New version") }}</div>
          <BaseButton
            type="primary"
            icon="file-upload"
            :label="t('Choose file')"
            @click="showUploader = true"
          />
        </div>
        <div
          v-if="pickedName"
          class="text-xs text-gray-500 mt-2"
        >
          {{ t("{0} selected", [pickedName]) }}
        </div>

        <!-- Rename toggle -->
        <div class="mt-3 flex items-center gap-2">
          <input
            id="renameTitle"
            type="checkbox"
            class="h-4 w-4"
            v-model="renameTitle"
          />
          <label
            for="renameTitle"
            class="text-sm"
          >
            {{ t("Change title to selected filename") }}
          </label>
        </div>
      </div>

      <UppyModalUploader
        :visible="showUploader"
        @close="showUploader = false"
        @file-added="onFileChosen"
      />

      <!-- Folder -->
      <div class="grid gap-3 md:grid-cols-2">
        <div>
          <label class="block text-sm mb-1">{{ t("Folder") }}</label>
          <BaseSelect
            v-model="categoryId"
            :options="folderOptions"
            optionLabel="title"
            optionValue="id"
            label=""
          />
        </div>
        <div class="self-end text-right">
          <BaseButton
            type="black"
            icon="close"
            class="mr-2"
            :label="t('Close')"
            @click="close"
          />
          <BaseButton
            type="primary"
            icon="check"
            :label="t('Update')"
            :disabled="saving || (!pickedFile && categoryId === initialCategoryId)"
            :isLoading="saving"
            @click="submit"
          />
        </div>
      </div>

      <p class="text-xs text-gray-500">
        {{ t("You can replace the file, move it to another folder, or both.") }}
      </p>
    </div>
  </BaseDialog>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import UppyModalUploader from "../../components/dropbox/UppyModalUploader.vue"
import service from "../../services/dropbox"

const { t } = useI18n()

const props = defineProps({
  isVisible: { type: Boolean, default: false },
  fileId: { type: Number, required: true },
  fileTitle: { type: String, default: "" },
})

const emit = defineEmits(["update:isVisible", "updated"])

const visibleProxy = computed({
  get: () => props.isVisible,
  set: (v) => emit("update:isVisible", v),
})

const showUploader = ref(false)
const pickedFile = ref(null) // Blob/File
const pickedName = ref("")
const renameTitle = ref(true) // ✅ default checked

const folderOptions = ref([{ id: 0, title: "Root" }])
const categoryId = ref(0)
const initialCategoryId = ref(0)
const saving = ref(false)

onMounted(async () => {
  const f = await service.getFile(props.fileId)
  initialCategoryId.value = f?.categoryId ?? 0
  categoryId.value = initialCategoryId.value
  folderOptions.value = await service.listCategories({ area: "sent" })
})

function onFileChosen(fileObj) {
  const blob = fileObj?.data instanceof Blob ? fileObj.data : fileObj
  if (!(blob instanceof Blob)) return
  pickedFile.value = blob
  pickedName.value = fileObj?.name || blob?.name || ""
  showUploader.value = false
}

async function submit() {
  saving.value = true
  try {
    await service.updateFile({
      id: props.fileId,
      file: pickedFile.value ?? null,
      categoryId: categoryId.value,
      renameTitle: !!renameTitle.value,
      newTitle: pickedName.value || "",
    })
    emit("updated")
    close()
  } finally {
    saving.value = false
  }
}

function close() {
  visibleProxy.value = false
}
</script>
