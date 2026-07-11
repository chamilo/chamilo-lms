<script setup>
import { computed, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"
import LpExtraFields from "./LpExtraFields.vue"
import { useNotification } from "../../composables/notification"
import lpService from "../../services/lpService"

const FIELD_CHECKBOX = 13
const FIELD_INTEGER = 15
const FIELD_FLOAT = 17
const FIELD_DURATION = 28
const FIELD_DATE = 6
const FIELD_DATETIME = 7
const FIELD_MULTI_SELECT = 5
const FIELD_TAG = 10

const props = defineProps({
  context: { type: Object, required: true },
  csrfToken: { type: String, required: true },
  item: { type: Object, required: true },
  lpId: { type: Number, required: true },
  parentOptions: { type: Array, required: true },
  titleAsHtml: { type: Boolean, default: false },
})

const emit = defineEmits(["saved"])
const { t } = useI18n()
const { showErrorNotification, showSuccessNotification } = useNotification()

const saving = ref(false)
const formSubmitted = ref(false)
const contentEditorId = "lp-builder-item-content"
const contentEditor = ref(null)
const extraFieldFiles = reactive({})
const extraFieldValues = reactive({})
const form = reactive({
  title: "",
  parentId: null,
  content: null,
  exportAllowed: false,
})

const hasValidTitle = computed(() => Boolean(String(form.title || "").replace(/<[^>]*>/g, "").trim()))
const canEditContent = computed(() => Boolean(props.item?.editableContent))
const showExportAllowed = computed(() => Boolean(props.item?.exportConfigurable))
const saveLabel = computed(() => {
  if ("dir" === props.item?.itemType) {
    return t("Save section")
  }
  if ("forum" === props.item?.itemType) {
    return t("Edit the current forum")
  }

  return t("Save")
})
const contentEditorConfig = {
  height: 420,
  setup: (editor) => {
    contentEditor.value = editor
  },
}
const titleEditorConfig = {
  height: 120,
  menubar: false,
  toolbar: "bold italic underline subscript superscript removeformat",
}

watch(
  () => props.item,
  (item) => {
    form.title = String(item?.title || "")
    form.parentId = Number(item?.parentId || 0)
    form.content = item?.editableContent ? String(item?.content || "") : null
    form.exportAllowed = Boolean(item?.exportAllowed)
    initializeExtraFields(item?.extraFields || [])
    Object.keys(extraFieldFiles).forEach((key) => delete extraFieldFiles[key])
    formSubmitted.value = false
  },
  { immediate: true, deep: true },
)

function getCurrentEditorContent() {
  return contentEditor.value?.getContent?.() ?? String(form.content || "")
}

function initializeExtraFields(fields) {
  Object.keys(extraFieldValues).forEach((key) => delete extraFieldValues[key])
  fields.forEach((field) => {
    const value = field.value
    if (field.valueType === FIELD_CHECKBOX) {
      extraFieldValues[field.id] = [true, 1, "1", "true"].includes(value)
      return
    }
    if ([FIELD_INTEGER, FIELD_FLOAT, FIELD_DURATION].includes(field.valueType)) {
      extraFieldValues[field.id] = Number(value || 0)
      return
    }
    if ([FIELD_DATE, FIELD_DATETIME].includes(field.valueType)) {
      extraFieldValues[field.id] = parseExtraFieldDate(field.valueType, value)
      return
    }
    if ([FIELD_MULTI_SELECT, FIELD_TAG].includes(field.valueType)) {
      extraFieldValues[field.id] = Array.isArray(value) ? value : String(value || "").split(";").filter(Boolean)
      return
    }
    extraFieldValues[field.id] = value ?? ""
  })
}

function serializeExtraFields() {
  const result = {}
  const fields = props.item?.extraFields || []
  fields.forEach((field) => {
    const value = extraFieldValues[field.id]
    if (value instanceof Date) {
      result[field.id] = field.valueType === FIELD_DATE ? toLocalDate(value) : toLocalDateTime(value)
      return
    }
    result[field.id] = value
  })
  return result
}

function onExtraFileSelected(fieldId, file) {
  extraFieldFiles[fieldId] = file
}

function parseExtraFieldDate(valueType, value) {
  if (!value) {
    return null
  }

  if (valueType === FIELD_DATE) {
    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})$/)
    if (match) {
      return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
    }
  }

  const normalized = String(value).replace(" ", "T")
  const date = new Date(normalized)

  return Number.isNaN(date.getTime()) ? null : date
}

function toLocalDate(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, "0")
  const day = String(date.getDate()).padStart(2, "0")
  return `${year}-${month}-${day}`
}

function toLocalDateTime(date) {
  const hours = String(date.getHours()).padStart(2, "0")
  const minutes = String(date.getMinutes()).padStart(2, "0")
  return `${toLocalDate(date)} ${hours}:${minutes}`
}

async function saveItem() {
  formSubmitted.value = true
  if (!hasValidTitle.value) {
    return
  }

  if (canEditContent.value) {
    form.content = getCurrentEditorContent()
  }

  saving.value = true
  try {
    await lpService.updateBuilderItem(
      props.lpId,
      Number(props.item.id),
      props.context,
      {
        title: form.title,
        parentId: Number(form.parentId || 0),
        content: canEditContent.value ? String(form.content || "") : null,
        exportAllowed: showExportAllowed.value && Boolean(form.exportAllowed),
        extraFields: serializeExtraFields(),
        csrfToken: props.csrfToken,
      },
      extraFieldFiles,
    )
    showSuccessNotification(t("Updated"))
    emit("saved", Number(props.item.id))
  } catch (error) {
    showErrorNotification(error)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="space-y-4">
    <div class="text-h4 font-semibold text-gray-90">
      {{ t("Edit") }}
    </div>

    <BaseTinyEditor
      v-if="titleAsHtml"
      editor-id="lp-builder-item-title"
      v-model="form.title"
      :editor-config="titleEditorConfig"
      :title="t('Title')"
      required
    />
    <BaseInputText
      v-else
      id="lp-builder-item-title"
      v-model="form.title"
      :error-text="t('This field cannot be empty')"
      :form-submitted="formSubmitted"
      :is-invalid="formSubmitted && !hasValidTitle"
      :label="t('Title')"
      name="title"
      required
    />

    <BaseSelect
      id="lp-builder-item-parent"
      v-model="form.parentId"
      :label="t('Parent')"
      :options="parentOptions"
      name="parentId"
      option-label="label"
      option-value="value"
    />

    <BaseInputText
      v-if="item.itemType === 'link'"
      id="lp-builder-item-url"
      :disabled="true"
      :label="t('URL')"
      :model-value="String(item.resourceUrl || '')"
      name="url"
    />

    <BaseTinyEditor
      v-if="canEditContent"
      :editor-id="contentEditorId"
      v-model="form.content"
      :editor-config="contentEditorConfig"
      :title="t('Content')"
    />

    <BaseCheckbox
      v-if="showExportAllowed"
      id="lp-builder-item-export-pdf"
      v-model="form.exportAllowed"
      :label="t('Export to PDF')"
      name="exportAllowed"
    />

    <LpExtraFields
      v-model="extraFieldValues"
      :fields="item.extraFields || []"
      @file-selected="onExtraFileSelected"
    />

    <div class="flex justify-end">
      <BaseButton
        :is-loading="saving"
        :label="saveLabel"
        icon="save"
        type="success"
        @click="saveItem"
      />
    </div>
  </div>
</template>
