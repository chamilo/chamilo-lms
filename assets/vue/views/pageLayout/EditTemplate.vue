<template>
  <SectionHeader :title="t('Edit Layout Template')">
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
            {{ t("Layout Template Name") }}
          </label>
          <BaseInputText
            v-model.trim="templateName"
            :placeholder="t('e.g. 3 Columns Layout')"
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
            {{ t("Replace layout with predefined type?") }}
          </label>
          <BaseSelect
            v-model="selectedTemplateId"
            :options="templateOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="(Keep existing layout)"
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
        :label="t('Save changes')"
        @click="saveTemplate"
      />
    </div>
  </form>
</template>

<script setup>
import { ref, watch, onMounted } from "vue"
import { useRouter, useRoute } from "vue-router"
import { useI18n } from "vue-i18n"

import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import PageLayoutEditor from "../../components/pageLayout/PageLayoutEditor.vue"

import pageService from "../../services/pageService"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const id = route.params.id

const submitted = ref(false)
const templateName = ref("")
const selectedTemplateId = ref(null)

const layoutJson = ref({
  page: {
    id: null,
    title: "",
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

watch(selectedTemplateId, (newVal) => {
  if (!newVal) return

  const templatesData = {
    1: {
      page: {
        id: "tpl_1col",
        title: "1 Column Layout",
        layout: {
          columns: [{ id: "col_1", width: "100%", blocks: [] }],
        },
      },
    },
    2: {
      page: {
        id: "tpl_2cols",
        title: "2 Columns Layout",
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
        title: "3 Columns Layout",
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
  templateName.value = layoutJson.value.page.title
})

onMounted(async () => {
  try {
    const tpl = await pageService.getPageLayoutTemplate(id)

    if (tpl.layout) {
      const layoutObj = JSON.parse(tpl.layout)

      templateName.value =
        layoutObj?.page?.title || `Template #${tpl.id}`

      layoutJson.value = layoutObj
    }
  } catch (e) {
    console.error("Failed to load template", e)
  }
})

async function saveTemplate() {
  submitted.value = true

  if (!templateName.value) return

  // Save the name into the JSON!
  layoutJson.value.page.title = templateName.value

  const payload = {
    layout: JSON.stringify(layoutJson.value, null, 2),
  }

  await pageService.updatePageLayoutTemplate(
    `/api/page_layout_templates/${id}`,
    payload
  )

  router.push({ name: "PageLayoutTemplateList" })
}

function goBack() {
  router.push({ name: "PageLayoutTemplateList" })
}
</script>
