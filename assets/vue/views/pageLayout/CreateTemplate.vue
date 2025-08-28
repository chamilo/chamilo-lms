<template>
  <SectionHeader :title="t('Create layout template')">
    <BaseButton
      icon="back"
      type="gray"
      only-icon
      :label="t('Back')"
      @click="goBack"
    />
  </SectionHeader>

  <form
    @submit.prevent="saveTemplate"
    class="p-6 space-y-6 w-full max-w-[1600px] mx-auto"
  >
    <div class="bg-white rounded shadow p-6 space-y-6 w-full">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
        <div class="md:col-span-2">
          <label class="block text-gray-700 font-semibold mb-1">
            {{ t("Layout template name") }}
          </label>
          <BaseInputText
            v-model.trim="templateName"
            :placeholder="t('e.g. 3 columns layout')"
            :error="submitted && !templateName"
            label=""
          />
          <small
            v-if="submitted && !templateName"
            class="text-red-500"
          >
            {{ t("Template name is required") }}
          </small>
        </div>

        <div class="md:col-span-2">
          <label class="block text-gray-700 font-semibold mb-1">
            {{ t("Layout type") }}
          </label>
          <BaseSelect
            v-model="selectedTemplateId"
            :options="templateOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Select a template"
            label=""
          />
        </div>
      </div>
    </div>

    <!-- Visual Editor -->
    <div class="bg-white rounded shadow p-6 w-full">
      <PageLayoutEditor
        v-model="layoutJson"
        :template-options="[]"
        :readonly="false"
      />
    </div>

    <div class="flex justify-end w-full">
      <BaseButton
        type="success"
        icon="save"
        :label="t('Save template')"
        @click="saveTemplate"
      />
    </div>
  </form>
</template>

<script setup>
import { ref, watch } from "vue"
import { useRouter } from "vue-router"
import { useI18n } from "vue-i18n"

import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import PageLayoutEditor from "../../components/pageLayout/PageLayoutEditor.vue"

import pageService from "../../services/pageService"

const { t } = useI18n()
const router = useRouter()

const submitted = ref(false)
const templateName = ref("")
const selectedTemplateId = ref(null)

const layoutJson = ref({
  page: {
    id: null,
    layout: {
      columns: [],
    },
  },
})

const templateOptions = [
  { label: "1 Column Layout", value: 1 },
  { label: "2 Columns Layout", value: 2 },
  { label: "3 Columns Layout", value: 3 },
]

// When a layout type is selected, load it visually
watch(selectedTemplateId, (newVal) => {
  if (!newVal) return

  const templatesData = {
    1: {
      page: {
        id: "tpl_1col",
        layout: {
          columns: [{ id: "col_1", width: "100%", blocks: [] }],
        },
      },
    },
    2: {
      page: {
        id: "tpl_2cols",
        layout: {
          columns: [
            { id: "col_1", width: "50%", blocks: [] },
            { id: "col_2", width: "50%", blocks: [] },
          ],
        },
      },
    },
    3: {
      page: {
        id: "tpl_3cols",
        layout: {
          columns: [
            { id: "col_1", width: "33.33%", blocks: [] },
            { id: "col_2", width: "33.33%", blocks: [] },
            { id: "col_3", width: "33.33%", blocks: [] },
          ],
        },
      },
    },
  }

  layoutJson.value = JSON.parse(JSON.stringify(templatesData[newVal]))
})

async function saveTemplate() {
  submitted.value = true

  if (!templateName.value) return

  await pageService.createPageLayoutTemplate({
    layout: JSON.stringify(layoutJson.value, null, 2),
  })

  router.push({ name: "PageLayoutTemplateList" })
}

function goBack() {
  router.push({ name: "PageLayoutTemplateList" })
}
</script>
