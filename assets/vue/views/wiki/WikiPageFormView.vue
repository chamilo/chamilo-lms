<template>
  <section class="space-y-6">
    <BaseToolbar class="border-b border-gray-25 bg-white">
      <template #start>
        <BaseButton
          icon="back"
          :label="t('Back')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="cancelForm"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="loadErrorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ loadErrorMessage }}
    </div>

    <form
      v-else
      class="space-y-6"
      novalidate
      @submit.prevent="savePage"
    >
      <div
        v-if="formErrorMessage"
        class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        role="alert"
        aria-live="polite"
      >
        {{ formErrorMessage }}
      </div>

      <div
        v-if="form.isInheritedFromCourse"
        class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800"
        role="status"
      >
        {{ t("This page comes from the base course. Saving it will create a version for the current session.") }}
      </div>

      <div
        v-if="form.requiresLock && form.lockAcquired"
        class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
        role="status"
      >
        {{ t("You have 20 minutes to edit this page before the editing lock expires.") }}
      </div>

      <BaseCard>
        <template #title>
          <div class="flex items-center gap-2">
            <BaseIcon
              :icon="form.isNew ? 'plus' : 'edit'"
              size="normal"
            />
            <span>{{ form.isNew ? t("Add new page") : t("Edit page") }}</span>
          </div>
        </template>

        <div class="space-y-5">
          <BaseInputText
            v-if="form.isNew"
            id="wiki_page_title"
            v-model="form.title"
            :error-text="t('Title is required')"
            :form-submitted="formSubmitted"
            :is-invalid="formSubmitted && !form.title.trim()"
            :label="t('Title')"
            name="title"
            required
          />

          <div
            v-else
            class="rounded-lg border border-gray-20 bg-gray-10 px-4 py-3"
          >
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
              {{ t("Title") }}
            </p>
            <p class="mt-1 break-words text-base font-semibold text-gray-90">
              {{ form.title }}
            </p>
            <input
              name="title"
              type="hidden"
              :value="form.title"
            />
          </div>

          <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
            {{ t("Use [[Page]] or [[Page|Visible text]] to create internal Wiki links.") }}
          </div>

          <BaseTinyEditor
            v-model="form.content"
            editor-id="wiki_page_content"
            :editor-config="editorConfig"
            :full-page="false"
            :title="t('Content')"
            use-file-manager
          />
          <input
            name="content"
            type="hidden"
            :value="form.content"
          />

          <BaseInputText
            id="wiki_page_comment"
            v-model="form.comment"
            :label="t('Comment')"
            name="comment"
          />

          <BaseSelect
            id="wiki_page_progress"
            v-model="form.progress"
            :label="t('Progress')"
            name="progress"
            option-label="label"
            option-value="value"
            :options="form.progressOptions"
          />

          <BaseAdvancedSettingsButton
            v-if="form.languages.length > 1"
            v-model="showAdvancedSettings"
          >
            <BaseSelect
              id="wiki_page_language"
              v-model="form.language"
              :label="t('Language')"
              name="language"
              option-label="label"
              option-value="value"
              :options="form.languages"
            />
          </BaseAdvancedSettingsButton>
        </div>
      </BaseCard>

      <div class="flex flex-wrap justify-end gap-2">
        <BaseButton
          icon="back"
          :label="t('Cancel')"
          type="plain"
          @click="cancelForm"
        />
        <BaseButton
          icon="save"
          :is-loading="isSaving"
          :label="t('Save')"
          name="save"
          type="success"
          is-submit
        />
      </div>
    </form>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import wikiService from "../../services/wikiService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const isSaving = ref(false)
const formSubmitted = ref(false)
const loadErrorMessage = ref("")
const formErrorMessage = ref("")
const showAdvancedSettings = ref(false)
const isLeavingAfterSave = ref(false)

const form = ref(createEmptyForm())

const editorConfig = computed(() => ({
  height: 360,
  paste_as_text: Boolean(form.value.settings?.forcePasteAsPlainText),
}))

function createEmptyForm() {
  return {
    iid: null,
    pageId: null,
    reflink: "",
    title: "",
    content: "<p>&nbsp;</p>",
    comment: "",
    progress: 0,
    language: "",
    csrfToken: "",
    baseVersion: 0,
    version: 0,
    assignment: 0,
    isNew: true,
    isInheritedFromCourse: false,
    canManage: false,
    requiresLock: false,
    lockAcquired: false,
    lockTimeoutMinutes: 20,
    languages: [],
    progressOptions: [],
    settings: {},
  }
}

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: Number(getQueryValue(route.query.cid) || 0),
    node: Number(route.params.node || 0),
  }
  const sid = Number(getQueryValue(route.query.sid) || 0)
  const gid = Number(getQueryValue(route.query.gid) || 0)

  if (sid > 0) {
    params.sid = sid
  }

  if (gid > 0) {
    params.gid = gid
  }

  if (Object.prototype.hasOwnProperty.call(route.query, "isStudentView")) {
    params.isStudentView = getQueryValue(route.query.isStudentView)
  }

  return params
}

function getFormParams() {
  const params = getContextParams()
  const pageId = Number(route.params.pageId || 0)
  const title = String(getQueryValue(route.query.title) || "")

  if (pageId > 0) {
    params.pageId = pageId
  }

  if (title) {
    params.title = title
  }

  return params
}

function getPageRoute(reflink = "index") {
  const query = {
    cid: getQueryValue(route.query.cid),
    title: reflink || "index",
  }
  const sid = Number(getQueryValue(route.query.sid) || 0)
  const gid = Number(getQueryValue(route.query.gid) || 0)

  if (sid > 0) {
    query.sid = sid
  }

  if (gid > 0) {
    query.gid = gid
  }

  if (Object.prototype.hasOwnProperty.call(route.query, "isStudentView")) {
    query.isStudentView = getQueryValue(route.query.isStudentView)
  }

  return {
    name: "WikiPage",
    params: { node: route.params.node },
    query,
  }
}

function getErrorMessage(error) {
  return error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
}

async function acquireLock() {
  if (!form.value.requiresLock || !form.value.pageId) {
    return
  }

  const response = await wikiService.acquireLock(
    form.value.pageId,
    getContextParams(),
    form.value.csrfToken,
  )
  form.value.lockAcquired = Boolean(response.lockAcquired)
}

async function releaseLock() {
  if (!form.value.requiresLock || !form.value.lockAcquired || !form.value.pageId || !form.value.csrfToken) {
    return
  }

  try {
    await wikiService.releaseLock(form.value.pageId, getContextParams(), form.value.csrfToken)
  } catch (error) {
    console.error("Error releasing Wiki page lock", error)
  } finally {
    form.value.lockAcquired = false
  }
}

async function loadForm() {
  isLoading.value = true
  loadErrorMessage.value = ""
  formErrorMessage.value = ""

  try {
    const response = await wikiService.getForm(getFormParams())
    form.value = {
      ...createEmptyForm(),
      ...response,
      progress: Number(response.progress || 0),
      progressOptions: Array.isArray(response.progressOptions) ? response.progressOptions : [],
      languages: Array.isArray(response.languages) ? response.languages : [],
    }
    await acquireLock()
  } catch (error) {
    console.error("Error loading Wiki page form", error)
    loadErrorMessage.value = getErrorMessage(error)
  } finally {
    isLoading.value = false
  }
}

async function savePage() {
  formSubmitted.value = true
  formErrorMessage.value = ""

  if (form.value.isNew && !form.value.title.trim()) {
    return
  }

  if (form.value.requiresLock && !form.value.lockAcquired && !form.value.canManage) {
    formErrorMessage.value = t("The Wiki page edition lock is required before saving.")
    return
  }

  isSaving.value = true

  const payload = {
    pageId: form.value.pageId,
    reflink: form.value.reflink,
    title: form.value.title,
    content: form.value.content,
    comment: form.value.comment,
    progress: Number(form.value.progress || 0),
    language: form.value.language,
    csrfToken: form.value.csrfToken,
    baseVersion: Number(form.value.baseVersion || 0),
  }

  try {
    const response = form.value.isNew
      ? await wikiService.createPage(getContextParams(), payload)
      : await wikiService.updatePage(form.value.pageId, getContextParams(), payload)

    isLeavingAfterSave.value = true
    form.value.lockAcquired = false
    await router.push(getPageRoute(response.reflink || form.value.reflink || form.value.title))
  } catch (error) {
    console.error("Error saving Wiki page", error)
    formErrorMessage.value = getErrorMessage(error)
  } finally {
    isSaving.value = false
  }
}

async function cancelForm() {
  await releaseLock()
  await router.push(getPageRoute(form.value.reflink || "index"))
}

onMounted(loadForm)

onBeforeUnmount(() => {
  if (!isLeavingAfterSave.value) {
    void releaseLock()
  }
})
</script>
