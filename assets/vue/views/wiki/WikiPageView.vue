<template>
  <section class="space-y-6">
    <BaseToolbar class="border-b border-gray-25 bg-white">
      <template #start>
        <div class="flex items-center gap-2">
          <BaseButton
            icon="home"
            :label="t('Home')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getWikiRoute('index')"
          />
          <BaseButton
            v-if="wikiPage.canCreate"
            icon="plus"
            :label="t('Add new page')"
            only-icon
            size="large"
            type="success-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getCreateRoute()"
          />
          <BaseButton
            v-if="wikiPage.canEdit"
            icon="edit"
            :label="t('Edit page')"
            only-icon
            size="large"
            type="secondary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getEditRoute()"
          />
          <BaseButton
            icon="list"
            :label="t('All pages')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getReportRoute('all')"
          />
          <BaseButton
            icon="refresh"
            :label="t('Latest changes')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getReportRoute('recent')"
          />
          <BaseButton
            icon="search"
            :label="t('Search')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getReportRoute('search')"
          />
          <BaseButton
            v-if="wikiPage.canManageCategories"
            icon="folder-generic"
            :label="t('Manage categories')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getCategoryRoute()"
          />
          <BaseButton
            v-if="wikiPage.canManageSettings"
            icon="settings"
            :label="t('Wiki settings')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getSettingsRoute()"
          />
        </div>
      </template>

      <template #end>
        <div class="flex items-center gap-2">
          <BaseButton
            v-if="wikiPage.exists && Number(wikiPage.pageId) > 0"
            icon="restore"
            :label="t('History')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getHistoryRoute()"
          />
          <BaseButton
            v-if="wikiPage.canDiscuss && Number(wikiPage.pageId) > 0"
            icon="comment"
            :label="t('Discussion')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getDiscussionRoute()"
          />
          <BaseButton
            v-if="wikiPage.exists"
            icon="information"
            :label="t('What links here')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getReportRoute('backlinks', { target: wikiPage.reflink })"
          />
          <BaseButton
            v-if="wikiPage.canChangeVisibility"
            :icon="wikiPage.visible ? 'eye-on' : 'eye-off'"
            :is-loading="isManaging"
            :label="wikiPage.visible ? t('Hide page') : t('Show page')"
            only-icon
            size="large"
            type="secondary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="changeVisibility"
          />
          <BaseButton
            v-if="wikiPage.canChangeProtection"
            :icon="wikiPage.editLocked ? 'unlock' : 'lock'"
            :is-loading="isManaging"
            :label="wikiPage.editLocked ? t('Unlock page') : t('Lock page')"
            only-icon
            size="large"
            type="secondary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="changeProtection"
          />
          <BaseButton
            v-if="wikiPage.canChangeAddLock"
            icon="file-add"
            :is-loading="isManaging"
            :label="
              wikiPage.addLocked ? t('Allow new pages') : t('Block new pages')
            "
            only-icon
            size="large"
            :type="wikiPage.addLocked ? 'success-text' : 'danger-text'"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="changeAddLock"
          />
          <BaseButton
            v-if="wikiPage.canSubscribe"
            icon="notification"
            :is-loading="isManaging"
            :label="
              wikiPage.subscribed ? t('Stop notifying me') : t('Notify me')
            "
            only-icon
            size="large"
            :type="wikiPage.subscribed ? 'danger-text' : 'primary-text'"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="changeSubscription"
          />
          <BaseButton
            v-if="wikiPage.canExportPdf"
            icon="file-pdf"
            :is-loading="isExportingPdf"
            :label="t('Export to PDF')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="downloadPdf"
          />
          <BaseButton
            v-if="wikiPage.canExportToDocuments"
            icon="file-export"
            :is-loading="isExportingToDocuments"
            :label="t('Export to Documents')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="exportToDocuments"
          />
          <BaseButton
            v-if="wikiPage.canPrint"
            icon="file-text"
            :label="t('Print')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="printPage"
          />
          <BaseButton
            v-if="wikiPage.canDelete"
            icon="delete"
            :is-loading="isManaging"
            :label="t('Delete page')"
            only-icon
            size="large"
            type="danger-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="confirmDeletePage"
          />
          <BaseButton
            v-if="wikiPage.legacyUrl"
            icon="link-external"
            :label="t('Wiki')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :to-url="wikiPage.legacyUrl"
          />
        </div>
      </template>
    </BaseToolbar>

    <div
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
      role="status"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <template v-else>
      <div
        v-if="wikiPage.isInheritedFromCourse"
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
        v-if="wikiPage.assignmentNotStarted"
        class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800"
        role="status"
      >
        {{ t("The Wiki assignment has not started yet.") }}
      </div>

      <div
        v-if="wikiPage.assignmentClosed"
        class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"
        role="status"
      >
        {{ t("The Wiki assignment deadline has passed.") }}
      </div>

      <div
        v-else-if="wikiPage.assignmentLate"
        class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
        role="status"
      >
        {{
          t(
            "The Wiki assignment deadline has passed, but delayed submission is allowed.",
          )
        }}
      </div>

      <BaseCard v-if="wikiPage.hasTask">
        <template #title>
          <div class="flex items-center gap-2">
            <BaseIcon icon="file-text" size="small" />
            <span>{{ t("Description of the assignment") }}</span>
          </div>
        </template>

        <div
          class="break-words text-gray-90 [&_a]:font-medium [&_img]:max-w-full [&_table]:max-w-full"
          @click="handleContentClick"
          v-html="wikiPage.task"
        ></div>

        <template #footer>
          <div class="grid gap-2 text-sm text-gray-600 md:grid-cols-2">
            <span v-if="formattedAssignmentStart">
              {{ t("Start date") }}: {{ formattedAssignmentStart }}
            </span>
            <span v-if="formattedAssignmentEnd">
              {{ t("End date") }}: {{ formattedAssignmentEnd }}
            </span>
            <span>
              {{ t("Allow delayed sending") }}:
              {{ wikiPage.delayedSubmit ? t("Yes") : t("No") }}
            </span>
            <span v-if="Number(wikiPage.maxWords) > 0">
              {{ t("Maximum number of words") }}: {{ wikiPage.maxWords }}
            </span>
            <span v-if="Number(wikiPage.maxVersions) > 0">
              {{ t("Maximum number of versions") }}: {{ wikiPage.maxVersions }}
            </span>
            <span
              v-if="
                Number(wikiPage.assignment) === 2 &&
                wikiPage.assignmentOwnerName
              "
            >
              {{ t("Learner") }}: {{ wikiPage.assignmentOwnerName }}
            </span>
          </div>
        </template>
      </BaseCard>

      <div
        v-if="wikiPage.feedback"
        class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800"
        role="status"
      >
        <p class="font-semibold">{{ t("Feedback") }}</p>
        <p class="mt-1 whitespace-pre-line">{{ wikiPage.feedback }}</p>
      </div>

      <BaseCard>
        <template #title>
          <div class="flex min-w-0 items-center gap-2">
            <BaseIcon
              v-if="Number(wikiPage.assignment) === 1"
              icon="human-male-board"
              size="small"
              :tooltip="t('Assignment')"
            />
            <BaseIcon
              v-else-if="Number(wikiPage.assignment) === 2"
              icon="account"
              size="small"
              :tooltip="t('Learner')"
            />
            <BaseIcon
              v-if="wikiPage.hasTask"
              icon="file-text"
              size="small"
              :tooltip="t('Task')"
            />
            <h1
              class="min-w-0 flex-1 break-words text-xl font-semibold text-gray-90"
            >
              {{ wikiPage.title || t("Wiki") }}
            </h1>
            <BaseIcon
              v-if="wikiPage.editLocked"
              icon="lock"
              size="small"
              :tooltip="t('Locked')"
            />
            <BaseIcon
              v-if="wikiPage.exists && !wikiPage.visible"
              icon="eye-off"
              size="small"
              :tooltip="t('Invisible')"
            />
          </div>
        </template>

        <div
          v-if="wikiPage.exists && wikiPage.content"
          class="break-words text-gray-90 [&_a]:font-medium [&_img]:max-w-full [&_table]:max-w-full"
          @click="handleContentClick"
          v-html="wikiPage.content"
        ></div>

        <div v-else class="py-8 text-center">
          <BaseIcon class="mb-3 text-gray-500" icon="information" size="big" />
          <p class="text-sm italic text-gray-500">
            {{ t("No content") }}
          </p>
        </div>

        <template #footer>
          <div
            v-if="wikiPage.exists"
            class="space-y-3 border-t border-gray-20 pt-4"
          >
            <div
              v-if="wikiPage.categories?.length"
              class="flex flex-wrap items-center gap-2"
            >
              <span class="text-sm font-medium text-gray-700">
                {{ t("Categories") }}:
              </span>
              <router-link
                v-for="category in wikiPage.categories"
                :key="category.id"
                class="rounded-full border border-primary/30 bg-primary/5 px-3 py-1 text-xs font-medium text-primary hover:bg-primary/10"
                :title="category.pathTitle || category.title"
                :to="getCategorySearchRoute(category.id)"
              >
                {{ category.title }}
              </router-link>
            </div>

            <div
              class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-gray-600"
            >
              <span>{{ t("Progress") }}: {{ wikiPage.progress }}%</span>
              <span>{{ t("Rating") }}: {{ wikiPage.score ?? 0 }}</span>
              <span>{{ t("Words") }}: {{ wikiPage.wordCount }}</span>
              <span v-if="wikiPage.version"
                >{{ t("Version") }}: {{ wikiPage.version }}</span
              >
              <span v-if="wikiPage.authorName"
                >{{ t("Author") }}: {{ wikiPage.authorName }}</span
              >
              <span v-if="formattedUpdatedAt"
                >{{ t("Updated at") }}: {{ formattedUpdatedAt }}</span
              >
            </div>
          </div>
        </template>
      </BaseCard>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute, useRouter } from "vue-router";
import { useConfirmation } from "../../composables/useConfirmation";
import BaseButton from "../../components/basecomponents/BaseButton.vue";
import BaseCard from "../../components/basecomponents/BaseCard.vue";
import BaseIcon from "../../components/basecomponents/BaseIcon.vue";
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue";
import wikiService from "../../services/wikiService";

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const { requireConfirmation } = useConfirmation();

const isLoading = ref(false);
const isManaging = ref(false);
const isExportingPdf = ref(false);
const isExportingToDocuments = ref(false);
const errorMessage = ref("");
const successMessage = ref("");
const wikiPage = reactive(createEmptyPage());

const formattedUpdatedAt = computed(() => {
  if (!wikiPage.updatedAt) {
    return "";
  }

  const date = new Date(wikiPage.updatedAt);

  return Number.isNaN(date.getTime()) ? "" : date.toLocaleString();
});

const formattedAssignmentStart = computed(() =>
  formatDateTime(wikiPage.assignmentStartDate),
);
const formattedAssignmentEnd = computed(() =>
  formatDateTime(wikiPage.assignmentEndDate),
);

function formatDateTime(value) {
  if (!value) {
    return "";
  }

  const date = new Date(value);

  return Number.isNaN(date.getTime()) ? "" : date.toLocaleString();
}

function createEmptyPage() {
  return {
    pageId: null,
    reflink: "index",
    exists: false,
    title: "",
    content: "",
    assignment: 0,
    hasTask: false,
    task: "",
    feedback: "",
    assignmentOwnerName: "",
    assignmentStartDate: null,
    assignmentEndDate: null,
    delayedSubmit: false,
    maxWords: 0,
    maxVersions: 0,
    assignmentNotStarted: false,
    assignmentLate: false,
    assignmentClosed: false,
    progress: 0,
    score: null,
    wordCount: 0,
    version: null,
    authorName: "",
    updatedAt: null,
    visible: true,
    editLocked: false,
    addLocked: false,
    subscribed: false,
    isInheritedFromCourse: false,
    canCreate: false,
    canEdit: false,
    canChangeVisibility: false,
    canChangeProtection: false,
    canChangeAddLock: false,
    canSubscribe: false,
    canDiscuss: false,
    canDelete: false,
    canExportPdf: false,
    canExportToDocuments: false,
    canPrint: false,
    categoriesEnabled: false,
    canManageCategories: false,
    canManageSettings: false,
    categories: [],
    managementCsrfToken: "",
    legacyUrl: "",
  };
}

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value;
}

function getSharedQuery() {
  const query = {
    cid: getQueryValue(route.query.cid),
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

  return query;
}

function getContextParams() {
  return {
    ...getSharedQuery(),
    node: Number(route.params.node || 0),
    title: String(getQueryValue(route.query.title) || "index"),
  };
}

function getWikiRoute(reflink) {
  return {
    name: "WikiPage",
    params: { node: route.params.node },
    query: {
      ...getSharedQuery(),
      title: reflink,
    },
  };
}

function getCreateRoute(reflink = "") {
  const query = getSharedQuery();

  if (reflink) {
    query.title = reflink;
  }

  return {
    name: "WikiPageCreate",
    params: { node: route.params.node },
    query,
  };
}

function getReportRoute(report, extraQuery = {}) {
  return {
    name: "WikiReports",
    params: { node: route.params.node },
    query: {
      ...getSharedQuery(),
      report,
      ...extraQuery,
    },
  };
}

function getCategoryRoute() {
  return {
    name: "WikiCategories",
    params: { node: route.params.node },
    query: getSharedQuery(),
  };
}

function getSettingsRoute() {
  return {
    name: "WikiSettings",
    params: { node: route.params.node },
    query: getSharedQuery(),
  };
}

function getCategorySearchRoute(categoryId) {
  return getReportRoute("search", {
    categoryIds: Number(categoryId),
    page: 1,
  });
}

function getHistoryRoute() {
  return {
    name: "WikiPageHistory",
    params: {
      node: route.params.node,
      pageId: wikiPage.pageId,
    },
    query: getSharedQuery(),
  };
}

function getDiscussionRoute() {
  return {
    name: "WikiDiscussion",
    params: {
      node: route.params.node,
      pageId: wikiPage.pageId,
    },
    query: getSharedQuery(),
  };
}

function getEditRoute() {
  if (
    wikiPage.exists &&
    !wikiPage.isInheritedFromCourse &&
    Number(wikiPage.pageId) > 0
  ) {
    return {
      name: "WikiPageEdit",
      params: {
        node: route.params.node,
        pageId: wikiPage.pageId,
      },
      query: getSharedQuery(),
    };
  }

  return getCreateRoute(wikiPage.reflink);
}

function handleContentClick(event) {
  const target =
    event.target instanceof Element
      ? event.target.closest("a[data-wiki-reflink]")
      : null;

  if (!(target instanceof HTMLAnchorElement)) {
    return;
  }

  const reflink = target.dataset.wikiReflink;

  if (!reflink) {
    return;
  }

  event.preventDefault();

  if ("0" === target.dataset.wikiExists && wikiPage.canCreate) {
    router.push(getCreateRoute(reflink));
    return;
  }

  router.push(getWikiRoute(reflink));
}

async function executeManagementAction(action, successText) {
  isManaging.value = true;
  errorMessage.value = "";
  successMessage.value = "";

  try {
    await action();
    successMessage.value = t(successText);
    await loadPage();
  } catch (error) {
    console.error("Error managing Wiki page", error);
    errorMessage.value =
      error?.response?.data?.detail ||
      error?.response?.data?.["hydra:description"] ||
      error?.response?.data?.error ||
      t("An error occurred");
  } finally {
    isManaging.value = false;
  }
}

function changeVisibility() {
  return executeManagementAction(
    () =>
      wikiService.setPageVisibility(
        Number(wikiPage.pageId),
        !wikiPage.visible,
        getContextParams(),
        wikiPage.managementCsrfToken,
      ),
    wikiPage.visible ? "The page is now hidden" : "The page is now visible",
  );
}

function changeProtection() {
  return executeManagementAction(
    () =>
      wikiService.setPageProtection(
        Number(wikiPage.pageId),
        !wikiPage.editLocked,
        getContextParams(),
        wikiPage.managementCsrfToken,
      ),
    wikiPage.editLocked ? "The page is now unlocked" : "The page is now locked",
  );
}

function changeAddLock() {
  return executeManagementAction(
    () =>
      wikiService.setContextAddLock(
        !wikiPage.addLocked,
        getContextParams(),
        wikiPage.managementCsrfToken,
      ),
    wikiPage.addLocked
      ? "New Wiki pages are now allowed"
      : "New Wiki pages are now blocked",
  );
}

function changeSubscription() {
  return executeManagementAction(
    () =>
      wikiService.setPageSubscription(
        Number(wikiPage.pageId),
        !wikiPage.subscribed,
        getContextParams(),
        wikiPage.managementCsrfToken,
      ),
    wikiPage.subscribed
      ? "Wiki notifications disabled"
      : "Wiki notifications enabled",
  );
}

function confirmDeletePage() {
  const warning =
    "index" === wikiPage.reflink
      ? t(
          "Deleting the Wiki homepage is not recommended. You can recreate it later.",
        )
      : t("Are you sure you want to delete this page and its history?");

  requireConfirmation({
    message: warning,
    accept: deletePage,
  });
}

async function deletePage() {
  isManaging.value = true;
  errorMessage.value = "";

  try {
    await wikiService.deletePage(
      Number(wikiPage.pageId),
      getContextParams(),
      wikiPage.managementCsrfToken,
    );
    await router.push(getReportRoute("all"));
  } catch (error) {
    console.error("Error deleting Wiki page", error);
    errorMessage.value =
      error?.response?.data?.detail ||
      error?.response?.data?.["hydra:description"] ||
      error?.response?.data?.error ||
      t("An error occurred");
  } finally {
    isManaging.value = false;
  }
}

function getApiError(error) {
  return (
    error?.response?.data?.detail ||
    error?.response?.data?.["hydra:description"] ||
    error?.response?.data?.error ||
    t("An error occurred")
  );
}

function getDownloadFilename(response, fallback) {
  const disposition = String(response?.headers?.["content-disposition"] || "");
  const encodedMatch = disposition.match(/filename\*=UTF-8''([^;]+)/i);

  if (encodedMatch?.[1]) {
    try {
      return decodeURIComponent(encodedMatch[1].replace(/^"|"$/g, ""));
    } catch {
      return fallback;
    }
  }

  const basicMatch = disposition.match(/filename="?([^";]+)"?/i);

  return basicMatch?.[1] || fallback;
}

function triggerBlobDownload(blob, filename) {
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}

async function downloadPdf() {
  isExportingPdf.value = true;
  errorMessage.value = "";

  try {
    const response = await wikiService.downloadPagePdf(
      Number(wikiPage.pageId),
      getContextParams(),
    );
    const filename = getDownloadFilename(response, "wiki-page.pdf");
    triggerBlobDownload(response.data, filename);
  } catch (error) {
    console.error("Error exporting Wiki page to PDF", error);
    errorMessage.value = getApiError(error);
  } finally {
    isExportingPdf.value = false;
  }
}

async function exportToDocuments() {
  isExportingToDocuments.value = true;
  errorMessage.value = "";
  successMessage.value = "";

  try {
    await wikiService.exportPageToDocuments(
      Number(wikiPage.pageId),
      getContextParams(),
      wikiPage.managementCsrfToken,
    );
    successMessage.value = t("The page has been exported to the document tool");
  } catch (error) {
    console.error("Error exporting Wiki page to Documents", error);
    errorMessage.value = getApiError(error);
  } finally {
    isExportingToDocuments.value = false;
  }
}

function escapeHtml(value) {
  const element = document.createElement("div");
  element.textContent = String(value || "");

  return element.innerHTML;
}

function printPage() {
  const printWindow = window.open("", "_blank", "width=900,height=700");

  if (!printWindow) {
    errorMessage.value = t("The print window could not be opened");
    return;
  }

  const title = escapeHtml(wikiPage.title || t("Wiki"));
  const content = wikiPage.content || `<p>${escapeHtml(t("No content"))}</p>`;
  const documentHtml = `<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${title}</title>
  <style>
    @page { margin: 16mm; }
    body { margin: 0; color: #222; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; line-height: 1.45; }
    h1 { margin: 0 0 14px; padding-bottom: 8px; border-bottom: 1px solid #d9d9d9; font-size: 21pt; }
    img, video, svg { max-width: 100%; height: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 5px; border: 1px solid #d9d9d9; vertical-align: top; }
    pre, code { white-space: pre-wrap; overflow-wrap: anywhere; }
    a { color: #145f8c; text-decoration: underline; }
  </style>
</head>
<body>
  <main><h1>${title}</h1><div>${content}</div></main>
</body>
</html>`;

  printWindow.document.open();
  printWindow.document.write(documentHtml);
  printWindow.document.close();
  window.setTimeout(() => {
    printWindow.focus();
    printWindow.print();
  }, 250);
}

async function loadPage() {
  isLoading.value = true;
  errorMessage.value = "";

  try {
    const response = await wikiService.getPage(getContextParams());
    Object.assign(wikiPage, createEmptyPage(), response);
  } catch (error) {
    console.error("Error loading Wiki page", error);
    errorMessage.value =
      error?.response?.data?.detail ||
      error?.response?.data?.["hydra:description"] ||
      error?.response?.data?.error ||
      t("An error occurred");
  } finally {
    isLoading.value = false;
  }
}

onMounted(loadPage);

watch(
  () => [
    route.params.node,
    route.query.cid,
    route.query.sid,
    route.query.gid,
    route.query.title,
    route.query.isStudentView,
  ],
  loadPage,
);
</script>
