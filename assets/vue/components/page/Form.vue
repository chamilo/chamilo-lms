<template>
  <div>
    <BaseInputText
      id="item_title"
      v-model="v$.item.title.$model"
      :error-text="v$.item.title.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.title.$error"
      :label="t('Title')"
    />

    <BaseInputText
      id="slug"
      v-model="v$.item.slug.$model"
      :error-text="v$.item.slug.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.slug.$error"
      :label="t('Friendly URL')"
    />

    <div class="text-right my-3">
      <Button
        v-if="pageId || v$.item.content.$model"
        class="p-button-secondary"
        icon="mdi mdi-eye"
        :label="t('Preview')"
        type="button"
        @click="openPreview"
      />
    </div>

    <BaseCheckbox
      id="enabled"
      v-model="v$.item.enabled.$model"
      :label="t('Enabled')"
      name="enabled"
    />

    <BaseSelect
      v-model="v$.item.category.$model"
      :error-text="v$.item.category.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.category.$error"
      :label="t('Category')"
      :options="categories"
      id="category"
      name="category"
      option-label="title"
      option-value="@id"
    />

    <BaseSelect
      v-model="v$.item.locale.$model"
      :error-text="v$.item.locale.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.locale.$error"
      :label="t('Language')"
      :options="locales"
      id="locale"
      name="locale"
      option-label="originalName"
      option-value="isocode"
    />

    <BaseTinyEditor
      v-model="v$.item.content.$model"
      :title="t('Content')"
      editor-id="item_content"
      required
    />

    <div class="text-right">
      <Button
        :disabled="v$.item.$invalid"
        :label="t('Save')"
        icon="mdi mdi-content-save"
        type="button"
        @click="btnSaveOnClick"
      />
    </div>

    <Dialog
      v-model:visible="previewVisible"
      :header="t('Preview')"
      :modal="true"
      :style="{ width: '85vw', maxWidth: '1100px' }"
    >
      <div v-if="pageId" class="mb-3 flex items-center gap-2">
        <InputText
          :value="window.location.origin + previewUrl"
          readonly
          class="w-full cursor-pointer"
          @focus="$event.target.select()"
        />
        <Button
          class="p-button-text p-button-sm"
          icon="mdi mdi-content-copy"
          :title="t('Copy link')"
          @click="copyPreviewUrl"
        />
        <Button
          class="p-button-text p-button-sm"
          icon="mdi mdi-open-in-new"
          :title="t('Open in a new tab')"
          @click="openPreviewInNewTab"
        />
      </div>

      <div v-if="pageId">
        <iframe
          :src="previewUrlWithBust"
          style="width: 100%; height: 70vh; border: 0"
          @load="onPreviewLoaded"
        ></iframe>
      </div>
      <div v-else class="prose prose-lg max-w-none">
        <h1 class="text-3xl font-bold mb-4">
          {{ v$.item.title.$model || t('Untitled page') }}
        </h1>
        <article v-html="v$.item.content.$model"></article>
      </div>

      <template #footer>
        <Button class="p-button-primary" :label="t('Close')" @click="previewVisible = false" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, watch } from "vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import useVuelidate from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import isEmpty from "lodash/isEmpty"
import { useI18n } from "vue-i18n"
import pageCategoryService from "../../services/pageCategoryService"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
})

const emit = defineEmits(["update:modelValue", "submit"])

const { t } = useI18n()

const locales = ref(
  (window.languages || []).map((l) => ({
    originalName: l.originalName || l.original_name || l.english_name,
    isocode: l.isocode,
  }))
)
const categories = ref([])
const findAllPageCategories = async () => (categories.value = await pageCategoryService.findAll())

const pageId = computed(() => {
  const raw = props.modelValue?.['@id'] || ''
  const m = raw.match(/\/(\d+)(?:\?.*)?$/)
  return m ? m[1] : null
})

const previewVisible = ref(false)
const cacheBust = ref(0)
const previewUrl = computed(() => (pageId.value ? `/pages/${pageId.value}/preview` : ''))
const previewUrlWithBust = computed(() => (previewUrl.value ? `${previewUrl.value}?_=${cacheBust.value}` : ''))

function openPreview() {
  cacheBust.value = Date.now()
  previewVisible.value = true
}
function openPreviewInNewTab() {
  if (previewUrl.value) window.open(previewUrl.value, '_blank')
}
async function copyPreviewUrl() {
  if (!previewUrl.value) return
  const full = window.location.origin + previewUrl.value
  try {
    await navigator.clipboard.writeText(full)
  } catch {
    window.prompt(t('Copy this link'), full)
  }
}
function onPreviewLoaded() {}

findAllPageCategories()

const validations = {
  item: {
    title: {
      required,
    },
    enabled: {
      required,
    },
    content: {
      required,
    },
    locale: {
      required,
    },
    category: {
      required,
    },
    slug: {
      required,
    },
  },
}

const v$ = useVuelidate(validations, { item: computed(() => props.modelValue) })

watch(
  () => props.modelValue,
  async (newValue) => {
    if (!newValue) return

    await nextTick()

    if (!v$.value.item.slug.$model && newValue.slug) {
      v$.value.item.slug.$model = newValue.slug
    }

    if (!isEmpty(newValue.category) && !isEmpty(newValue.category["@id"])) {
      emit("update:modelValue", { ...newValue, category: newValue.category["@id"] })
    }
  },
  { immediate: true }
)

function btnSaveOnClick() {
  const item = { ...props.modelValue, ...v$.value.item.$model }

  emit("update:modelValue", item)

  emit("submit", item)
}
</script>
