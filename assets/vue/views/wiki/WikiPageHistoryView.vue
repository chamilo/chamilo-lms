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
            :route="getPageRoute('index')"
          />
          <BaseButton
            icon="eye-on"
            :label="t('View')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getPageRoute(historyData.reflink || 'index')"
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
        </div>
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
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <template v-else>
      <div
        v-if="historyData.isInheritedFromCourse"
        class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800"
        role="status"
      >
        {{ t("This history belongs to the base course. Restore is available after creating a session version.") }}
      </div>

      <BaseCard>
        <template #title>
          <div class="flex min-w-0 items-center gap-2">
            <BaseIcon
              icon="restore"
              size="normal"
            />
            <h1 class="min-w-0 flex-1 break-words text-xl font-semibold text-gray-90">
              {{ t("History") }}: {{ historyData.title }}
            </h1>
          </div>
        </template>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto_auto] lg:items-end">
          <BaseSelect
            id="wiki_history_old_version"
            v-model="oldVersionIid"
            :label="t('Old version')"
            name="old_version"
            option-label="label"
            option-value="value"
            :options="comparisonVersionOptions"
          />
          <BaseSelect
            id="wiki_history_new_version"
            v-model="newVersionIid"
            :label="t('New version')"
            name="new_version"
            option-label="label"
            option-value="value"
            :options="comparisonVersionOptions"
          />
          <BaseButton
            icon="list"
            :label="t('Compare selected versions line by line')"
            name="compare_line"
            type="primary"
            :disabled="!canCompare"
            @click="compareVersions('line')"
          />
          <BaseButton
            icon="file-text"
            :label="t('Compare selected versions word by word')"
            name="compare_word"
            type="primary"
            :disabled="!canCompare"
            @click="compareVersions('word')"
          />
        </div>
      </BaseCard>

      <BaseCard>
        <template #title>{{ t("Versions") }}</template>

        <BaseTable
          :text-for-empty="t('No results found')"
          :total-items="historyData.versions.length"
          :values="historyData.versions"
          data-key="iid"
        >
          <Column
            :header="t('Version')"
            field="version"
          >
            <template #body="slotProps">
              <div class="flex items-center gap-2">
                <span class="font-medium">{{ slotProps.data.version }}</span>
                <span
                  v-if="slotProps.data.isCurrent"
                  class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700"
                >
                  {{ t("Current version") }}
                </span>
              </div>
            </template>
          </Column>

          <Column
            :header="t('Date')"
            field="updatedAt"
          >
            <template #body="slotProps">
              <router-link
                class="font-medium text-primary hover:underline"
                :to="getVersionRoute(slotProps.data.iid)"
              >
                {{ formatDate(slotProps.data.updatedAt) }}
              </router-link>
            </template>
          </Column>

          <Column
            :header="t('Author')"
            field="authorName"
          />

          <Column
            :header="t('Progress')"
            field="progress"
          >
            <template #body="slotProps">{{ slotProps.data.progress }}%</template>
          </Column>

          <Column
            :header="t('Comment')"
            field="comment"
          />

          <Column :exportable="false">
            <template #body="slotProps">
              <div class="flex justify-end gap-2">
                <BaseButton
                  icon="eye-on"
                  :label="t('View version')"
                  only-icon
                  size="small"
                  type="primary-text"
                  :route="getVersionRoute(slotProps.data.iid)"
                />
                <BaseButton
                  v-if="historyData.canRestore && !slotProps.data.isCurrent"
                  icon="restore"
                  :label="t('Restore')"
                  only-icon
                  size="small"
                  type="secondary-text"
                  :disabled="isRestoring"
                  @click="confirmRestore(slotProps.data)"
                />
              </div>
            </template>
          </Column>
        </BaseTable>
      </BaseCard>

      <BaseCard v-if="historyData.selectedVersion">
        <template #title>
          {{ t("Version") }} {{ historyData.selectedVersion.version }}
        </template>

        <div class="space-y-4">
          <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
            {{ t("You are viewing a historical version of this page") }}.
          </div>

          <div
            class="break-words text-gray-90 [&_a]:font-medium [&_img]:max-w-full [&_table]:max-w-full"
            @click="handleContentClick"
            v-html="historyData.selectedVersion.content"
          ></div>

          <div class="flex flex-wrap gap-x-6 gap-y-2 border-t border-gray-20 pt-4 text-sm text-gray-600">
            <span>{{ t("Author") }}: {{ historyData.selectedVersion.authorName }}</span>
            <span>{{ t("Progress") }}: {{ historyData.selectedVersion.progress }}%</span>
            <span>{{ t("Words") }}: {{ historyData.selectedVersion.wordCount }}</span>
            <span>{{ t("Comment") }}: {{ historyData.selectedVersion.comment }}</span>
            <span>{{ t("Date") }}: {{ formatDate(historyData.selectedVersion.updatedAt) }}</span>
          </div>
        </div>
      </BaseCard>

      <BaseCard v-if="historyData.comparison">
        <template #title>
          {{ t("Compare versions") }} {{ historyData.comparison.oldVersion.version }} →
          {{ historyData.comparison.newVersion.version }}
        </template>

        <div
          v-if="'line' === historyData.comparison.mode"
          class="overflow-x-auto"
        >
          <table class="w-full border-collapse text-sm">
            <tbody>
              <tr
                v-for="(change, index) in historyData.comparison.lineChanges"
                :key="`${index}-${change.type}`"
              >
                <td
                  class="w-28 border border-gray-20 px-3 py-2 align-top font-semibold"
                  :data-change-type="change.type"
                  :style="getLineCellStyle(change.type, true)"
                >
                  {{ t(getLineLabel(change.type)) }}
                </td>
                <td
                  class="border border-gray-20 px-3 py-2 align-top"
                  :data-change-type="change.type"
                  :style="getLineCellStyle(change.type)"
                  v-html="change.content"
                ></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-else
          class="whitespace-pre-wrap rounded-xl border border-gray-20 bg-white p-4 text-sm leading-7 text-gray-90"
        >
          <template
            v-for="(change, index) in historyData.comparison.wordChanges"
            :key="`${index}-${change.type}`"
          >
            <del
              v-if="'deleted' === change.type"
              class="rounded-sm px-1 py-0.5 font-medium decoration-2"
              :data-change-type="change.type"
              :style="getWordChangeStyle(change.type)"
            >{{ change.text }}</del>
            <ins
              v-else-if="'added' === change.type"
              class="rounded-sm px-1 py-0.5 font-medium no-underline"
              :data-change-type="change.type"
              :style="getWordChangeStyle(change.type)"
            >{{ change.text }}</ins>
            <span v-else>{{ change.text }}</span>
          </template>
        </div>
      </BaseCard>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import wikiService from "../../services/wikiService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()

const isLoading = ref(false)
const isRestoring = ref(false)
const errorMessage = ref("")
const oldVersionIid = ref(null)
const newVersionIid = ref(null)
const historyData = reactive(createEmptyHistory())

const comparisonVersionOptions = computed(() =>
  historyData.versions.map((version) => ({
    value: Number(version.iid),
    label: `${t("Version")} ${version.version} — ${formatDate(version.updatedAt)}`,
  })),
)

const canCompare = computed(() => {
  const oldVersion = findVersion(oldVersionIid.value)
  const newVersion = findVersion(newVersionIid.value)

  return Boolean(oldVersion && newVersion && Number(oldVersion.version) < Number(newVersion.version))
})

function createEmptyHistory() {
  return {
    pageId: null,
    reflink: "index",
    title: "",
    isInheritedFromCourse: false,
    currentIid: null,
    currentVersion: 0,
    canRestore: false,
    csrfToken: "",
    versions: [],
    selectedVersion: null,
    comparison: null,
  }
}

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getSharedQuery() {
  const query = {
    cid: getQueryValue(route.query.cid),
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

  return query
}

function getContextParams() {
  return {
    ...getSharedQuery(),
    node: Number(route.params.node || 0),
    versionIid: Number(getQueryValue(route.query.versionIid) || 0) || undefined,
    oldIid: Number(getQueryValue(route.query.oldIid) || 0) || undefined,
    newIid: Number(getQueryValue(route.query.newIid) || 0) || undefined,
    mode: String(getQueryValue(route.query.mode) || "") || undefined,
  }
}

function getPageRoute(reflink = "index") {
  return {
    name: "WikiPage",
    params: { node: route.params.node },
    query: {
      ...getSharedQuery(),
      title: reflink || "index",
    },
  }
}

function getReportRoute(report) {
  return {
    name: "WikiReports",
    params: { node: route.params.node },
    query: {
      ...getSharedQuery(),
      report,
    },
  }
}

function getVersionRoute(versionIid) {
  return {
    name: "WikiPageHistory",
    params: {
      node: route.params.node,
      pageId: route.params.pageId,
    },
    query: {
      ...getSharedQuery(),
      versionIid,
    },
  }
}

function findVersion(iid) {
  return historyData.versions.find((version) => Number(version.iid) === Number(iid))
}

function initializeComparisonSelection() {
  const requestedOld = Number(getQueryValue(route.query.oldIid) || 0)
  const requestedNew = Number(getQueryValue(route.query.newIid) || 0)

  if (findVersion(requestedOld) && findVersion(requestedNew)) {
    oldVersionIid.value = requestedOld
    newVersionIid.value = requestedNew
    return
  }

  oldVersionIid.value = historyData.versions[1]?.iid ?? null
  newVersionIid.value = historyData.versions[0]?.iid ?? null
}

function compareVersions(mode) {
  if (!canCompare.value) {
    return
  }

  void router.push({
    name: "WikiPageHistory",
    params: {
      node: route.params.node,
      pageId: route.params.pageId,
    },
    query: {
      ...getSharedQuery(),
      oldIid: oldVersionIid.value,
      newIid: newVersionIid.value,
      mode,
    },
  })
}

function confirmRestore(version) {
  requireConfirmation({
    message: `${t("Restore version")} ${version.version}?`,
    accept: () => restoreVersion(version),
  })
}

async function restoreVersion(version) {
  isRestoring.value = true
  errorMessage.value = ""

  try {
    const response = await wikiService.restoreVersion(
      Number(route.params.pageId),
      Number(version.iid),
      {
        ...getSharedQuery(),
        node: Number(route.params.node || 0),
      },
      historyData.csrfToken,
    )

    await router.push(getPageRoute(response.reflink || historyData.reflink))
  } catch (error) {
    console.error("Error restoring Wiki version", error)
    errorMessage.value = getErrorMessage(error)
  } finally {
    isRestoring.value = false
  }
}

function handleContentClick(event) {
  const target = event.target instanceof Element ? event.target.closest("a[data-wiki-reflink]") : null

  if (!(target instanceof HTMLAnchorElement)) {
    return
  }

  const reflink = target.dataset.wikiReflink
  if (!reflink) {
    return
  }

  event.preventDefault()
  void router.push(getPageRoute(reflink))
}

function formatDate(value) {
  if (!value) {
    return "—"
  }

  const date = new Date(value)

  return Number.isNaN(date.getTime()) ? "—" : date.toLocaleString()
}

function getLineCellStyle(type, isLabel = false) {
  const styles = {
    equal: {
      backgroundColor: "#f9fafb",
      color: "#374151",
      borderLeftColor: "#9ca3af",
    },
    added: {
      backgroundColor: "#dcfce7",
      color: "#166534",
      borderLeftColor: "#22c55e",
    },
    deleted: {
      backgroundColor: "#fee2e2",
      color: "#991b1b",
      borderLeftColor: "#ef4444",
    },
    moved: {
      backgroundColor: "#fef9c3",
      color: "#854d0e",
      borderLeftColor: "#eab308",
    },
  }

  const style = styles[type] || styles.equal

  return isLabel
    ? {
        ...style,
        borderLeftStyle: "solid",
        borderLeftWidth: "4px",
      }
    : style
}

function getWordChangeStyle(type) {
  return {
    added: {
      backgroundColor: "#bbf7d0",
      color: "#14532d",
      textDecoration: "none",
    },
    deleted: {
      backgroundColor: "#fecaca",
      color: "#7f1d1d",
      textDecoration: "line-through",
      textDecorationThickness: "2px",
    },
  }[type]
}

function getLineLabel(type) {
  return {
    equal: "Unchanged",
    added: "Added",
    deleted: "Deleted",
    moved: "Moved",
  }[type]
}

function getErrorMessage(error) {
  return error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
}

async function loadHistory() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await wikiService.getHistory(Number(route.params.pageId), getContextParams())
    Object.assign(historyData, createEmptyHistory(), response)
    initializeComparisonSelection()
  } catch (error) {
    console.error("Error loading Wiki history", error)
    errorMessage.value = getErrorMessage(error)
  } finally {
    isLoading.value = false
  }
}

onMounted(loadHistory)
watch(() => route.fullPath, loadHistory)
</script>
