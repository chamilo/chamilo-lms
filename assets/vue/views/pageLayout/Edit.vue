<template>
  <SectionHeader :title="t('Edit Page Layout')">
    <BaseButton
      icon="back"
      type="gray"
      only-icon
      :label="t('Back')"
      @click="goBack"
    />
  </SectionHeader>

  <form
    @submit.prevent="saveLayout"
    class="p-6 space-y-6 w-full max-w-[1600px] mx-auto"
  >
    <div class="bg-white rounded shadow p-6 space-y-6 w-full">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
        <div>
          <label class="block text-gray-700 font-semibold mb-1">
            {{ t("URL") }} <span class="text-red-500">*</span>
          </label>
          <BaseInputText
            v-model.trim="form.url"
            :placeholder="t('e.g. /index.php')"
            :error="submitted && !form.url"
            label=""
          />
          <small
            v-if="submitted && !form.url"
            class="text-red-500"
          >
            {{ t("URL is required") }}
          </small>
        </div>

        <div>
          <label class="block text-gray-700 font-semibold mb-1">
            {{ t("Roles") }}
          </label>
          <BaseInputText
            v-model.trim="form.roles"
            :placeholder="t('Optional - e.g. ROLE_TEACHER')"
            label=""
          />
        </div>

        <div class="md:col-span-2">
          <label class="block text-gray-700 font-semibold mb-1">
            {{ t("Layout Template") }}
          </label>
          <div
            v-if="loadingTemplates"
            class="text-gray-600"
          >
            {{ t("Loading templates...") }}
          </div>

          <div v-else>
            <BaseSelect
              v-model="form.pageLayoutTemplate"
              :options="templateOptions"
              optionLabel="label"
              optionValue="value"
              :placeholder="t('Select a template')"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Editor -->
    <div class="bg-white rounded shadow p-6 w-full">
      <PageLayoutEditor
        v-model="layoutJson"
        :template-options="templateOptionsData"
        :readonly="false"
      />
    </div>

    <div class="flex justify-end w-full">
      <BaseButton
        type="success"
        icon="save"
        :label="t('Save changes')"
        @click="saveLayout"
      />
    </div>
  </form>
</template>

<script setup>
import { ref, onMounted, watch } from "vue"
import { useRouter, useRoute } from "vue-router"
import { useI18n } from "vue-i18n"

import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import PageLayoutEditor from "../../components/pageLayout/PageLayoutEditor.vue"

import pageService from "../../services/pageService.js"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const submitted = ref(false)
const loadingTemplates = ref(true)

const templateOptions = ref([])
const templateOptionsData = ref([])

const form = ref({
  url: "",
  roles: "",
  pageLayoutTemplate: null,
  layout: "",
})

const layoutJson = ref({
  page: {
    id: null,
    title: "",
    layout: { columns: [] },
  },
})

onMounted(async () => {
  try {
    const templates = await pageService.getPageLayoutTemplates()
    const parsedTemplates = templates.map((tpl) => {
      const parsed = safeParse(tpl.layout)
      return {
        label: parsed?.page?.title || `Template #${tpl.id}`,
        value: tpl["@id"],
        data: parsed,
      }
    })

    templateOptions.value = parsedTemplates.map((tpl) => ({
      label: tpl.label,
      value: tpl.value,
    }))

    templateOptionsData.value = parsedTemplates
    await loadLayout(route.params.id)
  } catch (e) {
    console.error("❌ Error loading templates or layout:", e)
  } finally {
    loadingTemplates.value = false
  }
})

async function loadLayout(id) {
  try {
    const layout = await pageService.getPageLayout(id)

    form.value.url = layout.url
    form.value.roles = layout.roles
    form.value.pageLayoutTemplate = layout.pageLayoutTemplate

    if (layout.layout) {
      layoutJson.value = safeParse(layout.layout) || {
        page: { id: null, title: "", layout: { columns: [] } },
      }
    } else {
      layoutJson.value = {
        page: { id: null, title: "", layout: { columns: [] } },
      }
    }
  } catch (e) {
    console.error("❌ Error loading layout:", e)
  }
}

/**
 * Watch template selection → loads JSON into editor.
 */
watch(
  () => form.value.pageLayoutTemplate,
  (newTemplate) => {
    if (!newTemplate) {
      layoutJson.value = {
        page: { id: null, title: "", layout: { columns: [] } },
      }
      return
    }

    const selected = templateOptionsData.value.find(
      (tpl) => tpl.value === newTemplate
    )

    if (selected) {
      layoutJson.value = JSON.parse(JSON.stringify(selected.data))
    }
  },
  { immediate: false }
)

/**
 * Save layout.
 */
async function saveLayout() {
  submitted.value = true
  if (!form.value.url) return

  layoutJson.value.page.title ||= ""

  const payload = {
    url: form.value.url,
    roles: form.value.roles || null,
    pageLayoutTemplate: form.value.pageLayoutTemplate,
    layout: JSON.stringify(layoutJson.value, null, 2),
  }

  try {
    await pageService.updatePageLayout(`/api/page_layouts/${route.params.id}`, payload)
    router.push({ name: "PageLayoutList" })
  } catch (e) {
    console.error("❌ Error saving layout:", e)
    alert("Error saving page layout.")
  }
}

function goBack() {
  router.push({ name: "PageLayoutList" })
}

function safeParse(json) {
  try {
    return JSON.parse(json)
  } catch (e) {
    return null
  }
}
</script>
