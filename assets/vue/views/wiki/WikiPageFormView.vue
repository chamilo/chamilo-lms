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

    <form v-else class="space-y-6" novalidate @submit.prevent="savePage">
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
        {{
          t(
            "This page comes from the base course. Saving it will create a version for the current session.",
          )
        }}
      </div>

      <div
        v-if="form.requiresLock && form.lockAcquired"
        class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
        role="status"
      >
        {{
          t(
            "You have 20 minutes to edit this page before the editing lock expires.",
          )
        }}
      </div>

      <div
        v-if="!form.isNew && Number(form.assignment) === 1"
        class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
        role="status"
      >
        {{
          t(
            "You can edit this assignment page, but learner work pages will not be modified.",
          )
        }}
      </div>

      <BaseCard>
        <template #title>
          <div class="flex items-center gap-2">
            <BaseIcon :icon="form.isNew ? 'plus' : 'edit'" size="normal" />
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
            <p
              class="text-xs font-semibold uppercase tracking-wide text-gray-500"
            >
              {{ t("Title") }}
            </p>
            <p class="mt-1 break-words text-base font-semibold text-gray-90">
              {{ form.title }}
            </p>
            <input name="title" type="hidden" :value="form.title" />
          </div>

          <div
            v-if="Number(form.assignment) === 2 && form.assignmentOwnerName"
            class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800"
          >
            {{ t("Learner") }}: {{ form.assignmentOwnerName }}
          </div>

          <div
            class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800"
          >
            {{
              t(
                "Use [[Page]] or [[Page|Visible text]] to create internal Wiki links.",
              )
            }}
          </div>

          <BaseTinyEditor
            v-model="form.content"
            editor-id="wiki_page_content"
            :editor-config="editorConfig"
            :full-page="false"
            :title="t('Content')"
            use-file-manager
          />
          <input name="content" type="hidden" :value="form.content" />

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

          <div v-if="form.categoriesEnabled" class="space-y-3">
            <div
              v-if="form.categories.length"
              class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-end"
            >
              <BaseMultiSelect
                input-id="wiki_page_categories"
                :label="t('Categories')"
                :model-value="form.categoryIds"
                name="categories"
                option-label="label"
                option-value="id"
                :options="form.categories"
                @update:model-value="updateCategories"
              />
              <BaseButton
                v-if="form.canManageCategories"
                icon="folder-generic"
                :label="t('Manage categories')"
                only-icon
                size="small"
                type="primary-text"
                :route="getCategoryRoute()"
              />
            </div>

            <div
              v-else
              class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800"
              role="status"
            >
              <span>{{ t("No Wiki categories are available yet.") }}</span>
              <BaseButton
                v-if="form.canManageCategories"
                icon="folder-plus"
                :label="t('Manage categories')"
                type="primary"
                :route="getCategoryRoute()"
              />
            </div>
            <input
              name="categoryIds"
              type="hidden"
              :value="form.categoryIds.join(',')"
            />
          </div>

          <BaseAdvancedSettingsButton
            v-if="showAdvancedSection"
            v-model="showAdvancedSettings"
          >
            <div class="space-y-6">
              <BaseSelect
                v-if="form.languages.length > 1"
                id="wiki_page_language"
                v-model="form.language"
                :label="t('Language')"
                name="language"
                option-label="label"
                option-value="value"
                :options="form.languages"
              />

              <template v-if="form.canConfigureAssignment">
                <div class="border-t border-gray-20 pt-6">
                  <div class="mb-4 flex items-center gap-2">
                    <BaseIcon icon="human-male-board" size="normal" />
                    <h2 class="text-lg font-semibold text-gray-90">
                      {{ t("Assignment") }}
                    </h2>
                  </div>

                  <BaseCheckbox
                    v-if="form.isNew"
                    id="wiki_create_assignment"
                    v-model="form.createAssignment"
                    :label="t('Create individual Wiki work pages for learners')"
                    name="createAssignment"
                  />

                  <div
                    v-if="form.isNew && form.createAssignment"
                    class="mt-4 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800"
                    role="status"
                  >
                    {{
                      t(
                        "This will create individual work pages for {0} learners.",
                        [Number(form.assignmentTargetCount || 0)],
                      )
                    }}
                  </div>
                </div>

                <BaseTinyEditor
                  v-model="form.task"
                  editor-id="wiki_assignment_task"
                  :editor-config="assignmentEditorConfig"
                  :full-page="false"
                  :title="t('Description of the assignment')"
                  use-file-manager
                />
                <input name="task" type="hidden" :value="form.task" />

                <div class="space-y-4">
                  <p class="text-sm text-gray-600">
                    {{
                      t(
                        "Add guidance messages associated with the progress on the page",
                      )
                    }}
                  </p>

                  <div
                    v-for="index in 3"
                    :key="index"
                    class="grid gap-4 rounded-lg border border-gray-20 p-4 lg:grid-cols-[minmax(0,1fr)_16rem]"
                  >
                    <div>
                      <label
                        class="mb-1 block text-sm font-medium text-gray-700"
                        :for="`wiki_feedback_${index}`"
                      >
                        {{ feedbackLabel(index) }}
                      </label>
                      <textarea
                        :id="`wiki_feedback_${index}`"
                        v-model="form[`feedback${index}`]"
                        class="min-h-24 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        :name="`feedback${index}`"
                      ></textarea>
                    </div>

                    <BaseSelect
                      :id="`wiki_feedback_progress_${index}`"
                      v-model="form[`feedbackProgress${index}`]"
                      :label="t('Progress')"
                      :name="`feedbackProgress${index}`"
                      option-label="label"
                      option-value="value"
                      :options="form.progressOptions"
                    />
                  </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                  <div class="space-y-3 rounded-lg border border-gray-20 p-4">
                    <BaseCheckbox
                      id="wiki_use_start_date"
                      v-model="useStartDate"
                      :label="t('Start date')"
                      name="useStartDate"
                    />
                    <BaseCalendar
                      v-if="useStartDate"
                      id="wiki_assignment_start_date"
                      v-model="startDateValue"
                      :label="t('Start date')"
                      show-time
                    />
                    <input
                      name="startDate"
                      type="hidden"
                      :value="serializeDate(startDateValue)"
                    />
                  </div>

                  <div class="space-y-3 rounded-lg border border-gray-20 p-4">
                    <BaseCheckbox
                      id="wiki_use_end_date"
                      v-model="useEndDate"
                      :label="t('End date')"
                      name="useEndDate"
                    />
                    <BaseCalendar
                      v-if="useEndDate"
                      id="wiki_assignment_end_date"
                      v-model="endDateValue"
                      :label="t('End date')"
                      show-time
                    />
                    <input
                      name="endDate"
                      type="hidden"
                      :value="serializeDate(endDateValue)"
                    />
                  </div>
                </div>

                <BaseCheckbox
                  id="wiki_delayed_submit"
                  v-model="form.delayedSubmit"
                  :label="t('Allow delayed sending')"
                  name="delayedSubmit"
                />

                <div class="grid gap-4 lg:grid-cols-2">
                  <BaseInputText
                    id="wiki_max_words"
                    v-model="form.maxWords"
                    :label="t('Maximum number of words')"
                    min="0"
                    name="maxWords"
                    type="number"
                  />
                  <BaseInputText
                    id="wiki_max_versions"
                    v-model="form.maxVersions"
                    :label="t('Maximum number of versions')"
                    min="0"
                    name="maxVersions"
                    type="number"
                  />
                </div>
              </template>
            </div>
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
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute, useRouter } from "vue-router";
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue";
import BaseButton from "../../components/basecomponents/BaseButton.vue";
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue";
import BaseCard from "../../components/basecomponents/BaseCard.vue";
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue";
import BaseIcon from "../../components/basecomponents/BaseIcon.vue";
import BaseInputText from "../../components/basecomponents/BaseInputText.vue";
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue";
import BaseSelect from "../../components/basecomponents/BaseSelect.vue";
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue";
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue";
import wikiService from "../../services/wikiService";

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const isLoading = ref(false);
const isSaving = ref(false);
const formSubmitted = ref(false);
const loadErrorMessage = ref("");
const formErrorMessage = ref("");
const showAdvancedSettings = ref(false);
const isLeavingAfterSave = ref(false);
const useStartDate = ref(false);
const useEndDate = ref(false);
const startDateValue = ref(null);
const endDateValue = ref(null);

const form = ref(createEmptyForm());

const editorConfig = computed(() => ({
  height: 360,
  paste_as_text: Boolean(form.value.settings?.forcePasteAsPlainText),
}));

const assignmentEditorConfig = computed(() => ({
  height: 240,
  paste_as_text: Boolean(form.value.settings?.forcePasteAsPlainText),
}));

const showAdvancedSection = computed(
  () => form.value.languages.length > 1 || form.value.canConfigureAssignment,
);

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
    createAssignment: false,
    canConfigureAssignment: false,
    assignmentTargetCount: 0,
    assignmentOwnerName: "",
    task: "",
    feedback1: "",
    feedback2: "",
    feedback3: "",
    feedbackProgress1: 0,
    feedbackProgress2: 0,
    feedbackProgress3: 0,
    startDate: null,
    endDate: null,
    delayedSubmit: false,
    maxWords: "0",
    maxVersions: "0",
    isNew: true,
    isInheritedFromCourse: false,
    canManage: false,
    categoriesEnabled: false,
    canManageCategories: false,
    categoryIds: [],
    categories: [],
    requiresLock: false,
    lockAcquired: false,
    lockTimeoutMinutes: 20,
    languages: [],
    progressOptions: [],
    settings: {},
  };
}

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value;
}

function getContextParams() {
  const params = {
    cid: Number(getQueryValue(route.query.cid) || 0),
    node: Number(route.params.node || 0),
  };
  const sid = Number(getQueryValue(route.query.sid) || 0);
  const gid = Number(getQueryValue(route.query.gid) || 0);

  if (sid > 0) {
    params.sid = sid;
  }

  if (gid > 0) {
    params.gid = gid;
  }

  if (Object.prototype.hasOwnProperty.call(route.query, "isStudentView")) {
    params.isStudentView = getQueryValue(route.query.isStudentView);
  }

  return params;
}

function getFormParams() {
  const params = getContextParams();
  const pageId = Number(route.params.pageId || 0);
  const title = String(getQueryValue(route.query.title) || "");

  if (pageId > 0) {
    params.pageId = pageId;
  }

  if (title) {
    params.title = title;
  }

  return params;
}

function getCategoryRoute() {
  const query = { ...getContextParams() };
  delete query.node;

  return {
    name: "WikiCategories",
    params: { node: route.params.node },
    query,
  };
}

function updateCategories(value) {
  form.value.categoryIds = Array.isArray(value)
    ? value.map(Number).filter((categoryId) => categoryId > 0)
    : [];
}

function getPageRoute(reflink = "index") {
  const query = {
    cid: getQueryValue(route.query.cid),
    title: reflink || "index",
  };
  const sid = Number(getQueryValue(route.query.sid) || 0);
  const gid = Number(getQueryValue(route.query.gid) || 0);

  if (sid > 0) {
    query.sid = sid;
  }

  if (gid > 0) {
    query.gid = gid;
  }

  if (Object.prototype.hasOwnProperty.call(route.query, "isStudentView")) {
    query.isStudentView = getQueryValue(route.query.isStudentView);
  }

  return {
    name: "WikiPage",
    params: { node: route.params.node },
    query,
  };
}

function getErrorMessage(error) {
  return (
    error?.response?.data?.detail ||
    error?.response?.data?.["hydra:description"] ||
    error?.response?.data?.error ||
    t("An error occurred")
  );
}

function parseDate(value) {
  if (!value) {
    return null;
  }

  const date = new Date(value);

  return Number.isNaN(date.getTime()) ? null : date;
}

function serializeDate(value) {
  return value instanceof Date && !Number.isNaN(value.getTime())
    ? value.toISOString()
    : "";
}

function feedbackLabel(index) {
  const labels = [t("First message"), t("Second message"), t("Third message")];

  return labels[index - 1];
}

function hasAssignmentConfiguration() {
  return (
    Boolean(form.value.createAssignment) ||
    Boolean(form.value.task?.trim()) ||
    Boolean(form.value.feedback1?.trim()) ||
    Boolean(form.value.feedback2?.trim()) ||
    Boolean(form.value.feedback3?.trim()) ||
    Number(form.value.feedbackProgress1 || 0) > 0 ||
    Number(form.value.feedbackProgress2 || 0) > 0 ||
    Number(form.value.feedbackProgress3 || 0) > 0 ||
    useStartDate.value ||
    useEndDate.value ||
    Boolean(form.value.delayedSubmit) ||
    Number(form.value.maxWords || 0) > 0 ||
    Number(form.value.maxVersions || 0) > 0
  );
}

async function acquireLock() {
  if (!form.value.requiresLock || !form.value.pageId) {
    return;
  }

  const response = await wikiService.acquireLock(
    form.value.pageId,
    getContextParams(),
    form.value.csrfToken,
  );
  form.value.lockAcquired = Boolean(response.lockAcquired);
}

async function releaseLock() {
  if (
    !form.value.requiresLock ||
    !form.value.lockAcquired ||
    !form.value.pageId ||
    !form.value.csrfToken
  ) {
    return;
  }

  try {
    await wikiService.releaseLock(
      form.value.pageId,
      getContextParams(),
      form.value.csrfToken,
    );
  } catch (error) {
    console.error("Error releasing Wiki page lock", error);
  } finally {
    form.value.lockAcquired = false;
  }
}

async function loadForm() {
  isLoading.value = true;
  loadErrorMessage.value = "";
  formErrorMessage.value = "";

  try {
    const response = await wikiService.getForm(getFormParams());
    form.value = {
      ...createEmptyForm(),
      ...response,
      progress: Number(response.progress || 0),
      feedbackProgress1: Number(response.feedbackProgress1 || 0),
      feedbackProgress2: Number(response.feedbackProgress2 || 0),
      feedbackProgress3: Number(response.feedbackProgress3 || 0),
      maxWords: String(Math.max(0, Number(response.maxWords || 0))),
      maxVersions: String(Math.max(0, Number(response.maxVersions || 0))),
      progressOptions: Array.isArray(response.progressOptions)
        ? response.progressOptions
        : [],
      languages: Array.isArray(response.languages) ? response.languages : [],
      categoryIds: Array.isArray(response.categoryIds)
        ? response.categoryIds
            .map(Number)
            .filter((categoryId) => categoryId > 0)
        : [],
      categories: Array.isArray(response.categories) ? response.categories : [],
    };
    startDateValue.value = parseDate(response.startDate);
    endDateValue.value = parseDate(response.endDate);
    useStartDate.value = startDateValue.value instanceof Date;
    useEndDate.value = endDateValue.value instanceof Date;
    showAdvancedSettings.value = hasAssignmentConfiguration();
    await acquireLock();
  } catch (error) {
    console.error("Error loading Wiki page form", error);
    loadErrorMessage.value = getErrorMessage(error);
  } finally {
    isLoading.value = false;
  }
}

function validateAssignmentLimits() {
  const maxWords = Number(form.value.maxWords || 0);
  const maxVersions = Number(form.value.maxVersions || 0);

  if (!Number.isInteger(maxWords) || maxWords < 0) {
    formErrorMessage.value = t(
      "The maximum number of words must be a non-negative integer",
    );
    return false;
  }

  if (!Number.isInteger(maxVersions) || maxVersions < 0) {
    formErrorMessage.value = t(
      "The maximum number of versions must be a non-negative integer",
    );
    return false;
  }

  return true;
}

function validateAssignmentDates() {
  if (useStartDate.value && !(startDateValue.value instanceof Date)) {
    formErrorMessage.value = t("The start date is invalid");
    return false;
  }

  if (useEndDate.value && !(endDateValue.value instanceof Date)) {
    formErrorMessage.value = t("The end date is invalid");
    return false;
  }

  if (
    useStartDate.value &&
    useEndDate.value &&
    startDateValue.value.getTime() > endDateValue.value.getTime()
  ) {
    formErrorMessage.value = t("The end date cannot be before the start date");
    return false;
  }

  return true;
}

async function savePage() {
  formSubmitted.value = true;
  formErrorMessage.value = "";

  if (form.value.isNew && !form.value.title.trim()) {
    return;
  }

  if (
    form.value.canConfigureAssignment &&
    (!validateAssignmentDates() || !validateAssignmentLimits())
  ) {
    return;
  }

  if (
    form.value.requiresLock &&
    !form.value.lockAcquired &&
    !form.value.canManage
  ) {
    formErrorMessage.value = t(
      "The Wiki page edition lock is required before saving.",
    );
    return;
  }

  isSaving.value = true;

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
    createAssignment: Boolean(form.value.createAssignment),
    task: form.value.task || "",
    feedback1: form.value.feedback1 || "",
    feedback2: form.value.feedback2 || "",
    feedback3: form.value.feedback3 || "",
    feedbackProgress1: Number(form.value.feedbackProgress1 || 0),
    feedbackProgress2: Number(form.value.feedbackProgress2 || 0),
    feedbackProgress3: Number(form.value.feedbackProgress3 || 0),
    startDate: useStartDate.value ? serializeDate(startDateValue.value) : null,
    endDate: useEndDate.value ? serializeDate(endDateValue.value) : null,
    delayedSubmit: Boolean(form.value.delayedSubmit),
    maxWords: Math.max(0, Number(form.value.maxWords || 0)),
    maxVersions: Math.max(0, Number(form.value.maxVersions || 0)),
    categoryIds: Array.isArray(form.value.categoryIds)
      ? form.value.categoryIds
          .map(Number)
          .filter((categoryId) => categoryId > 0)
      : [],
  };

  try {
    const response = form.value.isNew
      ? await wikiService.createPage(getContextParams(), payload)
      : await wikiService.updatePage(
          form.value.pageId,
          getContextParams(),
          payload,
        );

    isLeavingAfterSave.value = true;
    form.value.lockAcquired = false;
    await router.push(
      getPageRoute(response.reflink || form.value.reflink || form.value.title),
    );
  } catch (error) {
    console.error("Error saving Wiki page", error);
    formErrorMessage.value = getErrorMessage(error);
  } finally {
    isSaving.value = false;
  }
}

async function cancelForm() {
  await releaseLock();
  await router.push(getPageRoute(form.value.reflink || "index"));
}

onMounted(loadForm);

onBeforeUnmount(() => {
  if (!isLeavingAfterSave.value) {
    void releaseLock();
  }
});
</script>
