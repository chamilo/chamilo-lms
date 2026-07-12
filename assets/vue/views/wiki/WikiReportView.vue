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
            v-if="reportData.canCreate"
            icon="plus"
            :label="t('Add new page')"
            only-icon
            size="large"
            type="success-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getCreateRoute()"
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
            v-if="reportData.canManage"
            icon="information"
            :label="t('Statistics')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getReportRoute('statistics')"
          />
        </div>
      </template>

      <template #end>
        <div class="flex items-center gap-2">
          <BaseButton
            v-if="reportData.canSubscribeAll && 'recent' === reportData.report"
            icon="notification"
            :is-loading="isManaging"
            :label="
              reportData.allChangesSubscribed
                ? t('Stop notifying me')
                : t('Notify me')
            "
            only-icon
            size="large"
            :type="
              reportData.allChangesSubscribed ? 'danger-text' : 'primary-text'
            "
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="changeContextSubscription"
          />
          <BaseButton
            v-if="reportData.canDeleteWiki"
            icon="delete"
            :is-loading="isManaging"
            :label="t('Delete Wiki')"
            only-icon
            size="large"
            type="danger-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="confirmDeleteWiki"
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
      <BaseCard>
        <template #title>
          <div class="flex min-w-0 items-center gap-2">
            <BaseIcon :icon="reportIcon" size="normal" />
            <h1
              class="min-w-0 flex-1 break-words text-xl font-semibold text-gray-90"
            >
              {{ reportHeading }}
            </h1>
          </div>
        </template>

        <div class="space-y-5">
          <BaseSelect
            id="wiki_report_selector"
            v-model="selectedReport"
            :label="t('Report')"
            name="report"
            option-label="label"
            option-value="value"
            :options="reportOptions"
            @change="changeReport"
          />

          <form
            v-if="isSearchReport"
            class="grid gap-4 md:grid-cols-[minmax(0,2fr)_minmax(12rem,1fr)_minmax(12rem,1fr)_auto] md:items-end"
            @submit.prevent="submitSearch"
          >
            <BaseInputText
              id="wiki_search_text"
              v-model="searchForm.text"
              :label="t('Search')"
              name="search"
            />
            <BaseSelect
              id="wiki_search_scope"
              v-model="searchForm.scope"
              :label="t('Search in')"
              name="search_scope"
              option-label="label"
              option-value="value"
              :options="searchScopeOptions"
            />
            <BaseSelect
              id="wiki_search_versions"
              v-model="searchForm.versions"
              :label="t('Versions')"
              name="search_versions"
              option-label="label"
              option-value="value"
              :options="versionScopeOptions"
            />
            <BaseButton
              icon="search"
              :label="t('Search')"
              name="submit_search"
              type="primary"
              is-submit
            />

            <div
              v-if="reportData.categories.length"
              class="space-y-3 md:col-span-4"
            >
              <BaseMultiSelect
                input-id="wiki_search_categories"
                :label="t('Categories')"
                :model-value="searchForm.categoryIds"
                option-label="title"
                option-value="id"
                :options="reportData.categories"
                @update:model-value="updateSearchCategories"
              />
              <input
                name="categories"
                type="hidden"
                :value="searchForm.categoryIds.join(',')"
              />
              <BaseCheckbox
                v-if="searchForm.categoryIds.length > 1"
                id="wiki_search_match_all_categories"
                v-model="searchForm.matchAllCategories"
                :label="t('Must be in ALL the selected categories')"
                name="match_all_categories"
              />
            </div>
          </form>

          <p v-if="isBacklinkReport" class="text-sm text-gray-600">
            {{ t("Pages that link to this page") }}:
            <router-link
              class="font-medium text-primary hover:underline"
              :to="getPageRoute(reportData.targetReflink)"
            >
              {{ reportData.targetTitle }}
            </router-link>
          </p>

          <p v-if="isUserContributionReport" class="text-sm text-gray-600">
            {{ t("User contributions") }}:
            <strong>{{ reportData.userName }}</strong>
          </p>
        </div>
      </BaseCard>

      <div
        v-if="isSearchReport && !reportData.search"
        class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800"
        role="status"
      >
        {{ t("Enter a search term to find Wiki pages") }}
      </div>

      <div v-else-if="isStatisticsReport" class="grid gap-6 xl:grid-cols-2">
        <BaseCard>
          <template #title>{{ t("General") }}</template>
          <dl class="divide-y divide-gray-20">
            <div
              v-for="row in generalStatistics"
              :key="row.label"
              class="grid gap-2 py-3 sm:grid-cols-[minmax(0,2fr)_minmax(8rem,1fr)]"
            >
              <dt class="text-sm text-gray-700">{{ t(row.label) }}</dt>
              <dd class="text-sm font-semibold text-gray-90 sm:text-right">
                {{ row.value }}
              </dd>
            </div>
          </dl>
        </BaseCard>

        <BaseCard>
          <template #title>{{ t("Pages") }} / {{ t("Versions") }}</template>
          <dl class="divide-y divide-gray-20">
            <div
              v-for="row in pageStatistics"
              :key="row.label"
              class="grid gap-2 py-3 sm:grid-cols-[minmax(0,2fr)_minmax(8rem,1fr)]"
            >
              <dt class="text-sm text-gray-700">{{ t(row.label) }}</dt>
              <dd class="text-sm font-semibold text-gray-90 sm:text-right">
                {{ row.value }}
              </dd>
            </div>
          </dl>
        </BaseCard>

        <BaseCard class="xl:col-span-2">
          <template #title>{{
            t("Information about the content of the pages")
          }}</template>
          <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
              <thead>
                <tr class="border-b border-gray-30 text-left">
                  <th class="px-3 py-2 font-semibold text-gray-90">
                    {{ t("Metric") }}
                  </th>
                  <th class="px-3 py-2 text-right font-semibold text-gray-90">
                    {{ t("Latest version") }}
                  </th>
                  <th class="px-3 py-2 text-right font-semibold text-gray-90">
                    {{ t("All versions") }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in contentStatistics"
                  :key="row.label"
                  class="border-b border-gray-20"
                >
                  <td class="px-3 py-2 text-gray-700">{{ t(row.label) }}</td>
                  <td class="px-3 py-2 text-right font-medium text-gray-90">
                    {{ row.latest }}
                  </td>
                  <td class="px-3 py-2 text-right font-medium text-gray-90">
                    {{ row.allVersions }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </BaseCard>
      </div>

      <BaseTable
        v-else-if="showPageTable"
        v-model:rows="rows"
        v-model:sort-field="sortField"
        v-model:sort-order="sortOrder"
        :is-loading="isLoading"
        :text-for-empty="t('No results found')"
        :total-items="reportData.totalItems"
        :values="reportData.items"
        data-key="iid"
        lazy
        @page="onPage"
        @sort="onSort"
      >
        <Column :header="t('Type')" :sortable="false">
          <template #body="slotProps">
            <div class="flex items-center gap-1">
              <BaseIcon
                v-if="Number(slotProps.data.assignment) === 1"
                icon="human-male-board"
                size="small"
                :tooltip="t('Assignment')"
              />
              <BaseIcon
                v-else-if="Number(slotProps.data.assignment) === 2"
                icon="account"
                size="small"
                :tooltip="t('Learner')"
              />
              <BaseIcon
                v-if="slotProps.data.hasTask"
                icon="file-text"
                size="small"
                :tooltip="t('Task')"
              />
            </div>
          </template>
        </Column>

        <Column :header="t('Title')" :sortable="true" field="title">
          <template #body="slotProps">
            <div class="flex flex-col gap-1">
              <router-link
                class="font-medium text-primary hover:underline"
                :to="getPageRoute(slotProps.data.reflink)"
              >
                {{ slotProps.data.title }}
              </router-link>
              <span
                v-if="slotProps.data.categories?.length"
                class="text-xs text-gray-500"
              >
                {{
                  slotProps.data.categories
                    .map((category) => category.title)
                    .join(", ")
                }}
              </span>
            </div>
          </template>
        </Column>

        <Column
          v-if="showAuthorColumn"
          :header="t('Author')"
          :sortable="true"
          field="authorName"
        />

        <Column
          v-if="showDateColumn"
          :header="t('Date')"
          :sortable="true"
          field="updatedAt"
        >
          <template #body="slotProps">
            {{ formatDate(slotProps.data.updatedAt) }}
          </template>
        </Column>

        <Column v-if="showChangeColumn" :header="t('Change')">
          <template #body="slotProps">
            {{ Number(slotProps.data.version) > 1 ? t("Edited") : t("Added") }}
          </template>
        </Column>

        <Column
          v-if="showVersionColumn"
          :header="t('Version')"
          :sortable="true"
          field="version"
        />

        <Column
          v-if="isUserContributionReport"
          :header="t('Comment')"
          field="comment"
        />

        <Column
          v-if="isUserContributionReport"
          :header="t('Progress')"
          field="progress"
        >
          <template #body="slotProps">{{ slotProps.data.progress }}%</template>
        </Column>

        <Column
          v-if="isUserContributionReport"
          :header="t('Rating')"
          field="score"
        />

        <Column :exportable="false">
          <template #body="slotProps">
            <div class="flex justify-end gap-2">
              <BaseButton
                icon="eye-on"
                :label="t('View')"
                only-icon
                size="small"
                type="primary-text"
                :route="getPageRoute(slotProps.data.reflink)"
              />
              <BaseButton
                v-if="slotProps.data.canEdit"
                icon="pencil"
                :label="t('Edit')"
                only-icon
                size="small"
                type="secondary-text"
                :route="getEditRoute(slotProps.data)"
              />
              <BaseButton
                v-if="Number(slotProps.data.pageId) > 0"
                icon="restore"
                :label="t('History')"
                only-icon
                size="small"
                type="primary-text"
                :route="getHistoryRoute(slotProps.data)"
              />
              <BaseButton
                icon="information"
                :label="t('What links here')"
                only-icon
                size="small"
                type="primary-text"
                :route="getBacklinkRoute(slotProps.data.reflink)"
              />
              <BaseButton
                v-if="canDeletePageItem(slotProps.data)"
                icon="delete"
                :is-loading="isManaging"
                :label="t('Delete page')"
                only-icon
                size="small"
                type="danger-text"
                @click="confirmDeletePage(slotProps.data)"
              />
            </div>
          </template>
        </Column>
      </BaseTable>

      <BaseTable
        v-else-if="isActiveUsersReport"
        v-model:rows="rows"
        v-model:sort-field="sortField"
        v-model:sort-order="sortOrder"
        :text-for-empty="t('No results found')"
        :total-items="reportData.totalItems"
        :values="reportData.items"
        data-key="id"
        lazy
        @page="onPage"
        @sort="onSort"
      >
        <Column :header="t('Author')" :sortable="true" field="authorName" />
        <Column
          :header="t('Contributions')"
          :sortable="true"
          field="contributions"
        />
        <Column :exportable="false">
          <template #body="slotProps">
            <div class="flex justify-end">
              <BaseButton
                icon="eye-on"
                :label="t('User contributions')"
                only-icon
                size="small"
                type="primary-text"
                :route="getUserContributionRoute(slotProps.data.userId)"
              />
            </div>
          </template>
        </Column>
      </BaseTable>

      <BaseTable
        v-else-if="isMetricReport"
        v-model:rows="rows"
        v-model:sort-field="sortField"
        v-model:sort-order="sortOrder"
        :text-for-empty="t('No results found')"
        :total-items="reportData.totalItems"
        :values="reportData.items"
        data-key="reflink"
        lazy
        @page="onPage"
        @sort="onSort"
      >
        <Column :header="t('Type')">
          <template #body="slotProps">
            <div class="flex items-center gap-1">
              <BaseIcon
                v-if="Number(slotProps.data.assignment) === 1"
                icon="human-male-board"
                size="small"
                :tooltip="t('Assignment')"
              />
              <BaseIcon
                v-else-if="Number(slotProps.data.assignment) === 2"
                icon="account"
                size="small"
                :tooltip="t('Learner')"
              />
              <BaseIcon
                v-if="slotProps.data.hasTask"
                icon="file-text"
                size="small"
                :tooltip="t('Task')"
              />
            </div>
          </template>
        </Column>
        <Column :header="t('Title')" :sortable="true" field="title">
          <template #body="slotProps">
            <router-link
              class="font-medium text-primary hover:underline"
              :to="getPageRoute(slotProps.data.reflink)"
            >
              {{ slotProps.data.title }}
            </router-link>
          </template>
        </Column>
        <Column
          v-if="metricField"
          :header="t(metricHeader)"
          :sortable="true"
          :field="metricField"
        />
      </BaseTable>

      <BaseTable
        v-else-if="isWantedReport"
        v-model:rows="rows"
        v-model:sort-field="sortField"
        v-model:sort-order="sortOrder"
        :text-for-empty="t('No results found')"
        :total-items="reportData.totalItems"
        :values="reportData.items"
        data-key="reflink"
        lazy
        @page="onPage"
        @sort="onSort"
      >
        <Column :header="t('Title')" :sortable="true" field="title" />
        <Column :exportable="false">
          <template #body="slotProps">
            <div class="flex justify-end">
              <BaseButton
                v-if="slotProps.data.canCreate"
                icon="plus"
                :label="t('Add new page')"
                only-icon
                size="small"
                type="success-text"
                :route="getCreateRoute(slotProps.data.reflink)"
              />
            </div>
          </template>
        </Column>
      </BaseTable>
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
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue";
import BaseIcon from "../../components/basecomponents/BaseIcon.vue";
import BaseInputText from "../../components/basecomponents/BaseInputText.vue";
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue";
import BaseSelect from "../../components/basecomponents/BaseSelect.vue";
import BaseTable from "../../components/basecomponents/BaseTable.vue";
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue";
import wikiService from "../../services/wikiService";

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const { requireConfirmation } = useConfirmation();

const isLoading = ref(false);
const isManaging = ref(false);
const errorMessage = ref("");
const selectedReport = ref("all");
const rows = ref(20);
const sortField = ref(null);
const sortOrder = ref(null);
const reportData = reactive(createEmptyReport());
const searchForm = reactive({
  text: "",
  scope: "title",
  versions: "latest",
  categoryIds: [],
  matchAllCategories: false,
});

const isSearchReport = computed(() => "search" === reportData.report);
const isBacklinkReport = computed(() => "backlinks" === reportData.report);
const isUserContributionReport = computed(
  () => "user-contributions" === reportData.report,
);
const isStatisticsReport = computed(() => "statistics" === reportData.report);
const isActiveUsersReport = computed(
  () => "active-users" === reportData.report,
);
const isWantedReport = computed(() => "wanted" === reportData.report);
const isMetricReport = computed(() =>
  ["most-changed", "most-visited", "most-linked", "orphaned"].includes(
    reportData.report,
  ),
);
const showPageTable = computed(
  () =>
    ["all", "recent", "search", "backlinks", "user-contributions"].includes(
      reportData.report,
    ) &&
    (!isSearchReport.value || Boolean(reportData.search)),
);
const showAuthorColumn = computed(() =>
  ["all", "recent", "search", "backlinks"].includes(reportData.report),
);
const showDateColumn = computed(() =>
  ["all", "recent", "search", "backlinks", "user-contributions"].includes(
    reportData.report,
  ),
);
const showChangeColumn = computed(() => "recent" === reportData.report);
const showVersionColumn = computed(
  () =>
    "recent" === reportData.report ||
    isUserContributionReport.value ||
    (isSearchReport.value && reportData.allVersions),
);

const reportHeading = computed(() => {
  if (isBacklinkReport.value && reportData.targetTitle) {
    return `${t(reportData.title)}: ${reportData.targetTitle}`;
  }

  if (isUserContributionReport.value && reportData.userName) {
    return `${t(reportData.title)}: ${reportData.userName}`;
  }

  return t(reportData.title || "Wiki");
});

const reportIcon = computed(() => {
  if ("search" === reportData.report) {
    return "search";
  }

  if ("recent" === reportData.report || "most-changed" === reportData.report) {
    return "refresh";
  }

  if ("statistics" === reportData.report || "backlinks" === reportData.report) {
    return "information";
  }

  if (
    "active-users" === reportData.report ||
    "user-contributions" === reportData.report
  ) {
    return "account-multiple";
  }

  return "list";
});

const reportOptions = computed(() => {
  const options = reportData.availableReports.map((item) => ({
    value: item.value,
    label: t(item.label),
  }));

  if (isBacklinkReport.value) {
    options.unshift({ value: "backlinks", label: t("What links here") });
  }

  if (isUserContributionReport.value) {
    options.unshift({
      value: "user-contributions",
      label: t("User contributions"),
    });
  }

  return options;
});

const searchScopeOptions = computed(() => [
  { value: "title", label: t("Title") },
  { value: "content", label: t("Title and content") },
]);

const versionScopeOptions = computed(() => [
  { value: "latest", label: t("Latest version") },
  { value: "all", label: t("All versions") },
]);

const metricField = computed(() => {
  if ("most-changed" === reportData.report) {
    return "changes";
  }

  if ("most-visited" === reportData.report) {
    return "hits";
  }

  return "";
});

const metricHeader = computed(() => {
  if ("most-changed" === reportData.report) {
    return "Changes";
  }

  if ("most-visited" === reportData.report) {
    return "Visits";
  }

  return "";
});

const generalStatistics = computed(() => {
  const values = reportData.statistics?.general || {};

  return [
    {
      label: "Learners can add new pages to the Wiki",
      value: formatBoolean(values.learnersCanAddPages),
    },
    {
      label: "Creation date of the oldest Wiki page",
      value: formatDate(values.firstCreatedAt),
    },
    {
      label: "Date of most recent edition of Wiki",
      value: formatDate(values.lastUpdatedAt),
    },
    {
      label: "Average rating of all pages",
      value: formatPercent(values.averageScore),
    },
    {
      label: "Mean estimated progress by users on their pages",
      value: formatPercent(values.averageProgress),
    },
    {
      label: "Total users that have participated in this Wiki",
      value: formatNumber(values.contributors),
    },
    {
      label: "Total different IP addresses that have contributed to Wiki",
      value: formatNumber(values.contributorIpAddresses),
    },
  ];
});

const pageStatistics = computed(() => {
  const values = reportData.statistics?.pages || {};

  return [
    {
      label: "Pages",
      value: formatPair(values.pages, values.versions, "Versions"),
    },
    {
      label: "Total of empty pages",
      value: formatPair(values.emptyPages, values.emptyVersions, "Versions"),
    },
    {
      label: "Number of visits",
      value: formatPair(values.visits, values.versionVisits, "Versions"),
    },
    {
      label: "Total pages edited at this time",
      value: formatNumber(values.editingNow),
    },
    { label: "Total hidden pages", value: formatNumber(values.hiddenPages) },
    {
      label: "Number of protected pages",
      value: formatNumber(values.protectedPages),
    },
    {
      label: "Number of discussion pages blocked",
      value: formatNumber(values.discussionLockedPages),
    },
    {
      label: "Number of discussion pages hidden",
      value: formatNumber(values.discussionHiddenPages),
    },
    {
      label: "Total comments on various versions of the pages",
      value: formatNumber(values.versionComments),
    },
    {
      label: "Total pages can only be scored by a teacher",
      value: formatNumber(values.teacherRatingOnlyPages),
    },
    {
      label: "Total pages that can be scored by other learners",
      value: formatNumber(values.learnerRatingPages),
    },
    {
      label: "Number of assignments pages proposed by a teacher",
      value: formatNumber(values.teacherAssignmentPages),
    },
    {
      label: "Number of individual assignments learner pages",
      value: formatNumber(values.learnerAssignmentPages),
    },
    { label: "Number of tasks", value: formatNumber(values.taskPages) },
  ];
});

const contentStatistics = computed(() => {
  const latest = reportData.statistics?.content?.latest || {};
  const allVersions = reportData.statistics?.content?.allVersions || {};
  const rows = [
    ["Number of words", "words"],
    [
      "Number of external html links inserted (text, images, ...).",
      "externalLinks",
    ],
    ["Anchors", "anchors"],
    ["Mail links", "mailLinks"],
    ["FTP links", "ftpLinks"],
    ["IRC links", "ircLinks"],
    ["News links", "newsLinks"],
    ["Number of wiki links", "wikiLinks"],
    ["Number of inserted images", "images"],
    ["Number of inserted flash files", "flashFiles"],
    ["Number of mp3 audio files inserted", "mp3Files"],
    ["Number of FLV video files inserted", "flvFiles"],
    ["Number of Youtube video embedded", "youtubeVideos"],
    [
      "Number of audio and video files inserted (except mp3 and flv)",
      "multimediaFiles",
    ],
    ["Number of tables inserted", "tables"],
  ];

  return rows.map(([label, key]) => ({
    label,
    latest: formatNumber(latest[key]),
    allVersions: formatNumber(allVersions[key]),
  }));
});

function createEmptyReport() {
  return {
    report: "all",
    title: "All pages",
    canManage: false,
    canCreate: false,
    canDeleteWiki: false,
    canSubscribeAll: false,
    allChangesSubscribed: false,
    managementCsrfToken: "",
    page: 1,
    itemsPerPage: 20,
    totalItems: 0,
    sortBy: "title",
    sortOrder: "asc",
    search: "",
    searchContent: false,
    allVersions: false,
    categoryIds: [],
    matchAllCategories: false,
    categories: [],
    targetReflink: "",
    targetTitle: "",
    userId: null,
    userName: "",
    items: [],
    statistics: {},
    availableReports: [],
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

function getManagementParams() {
  return {
    ...getSharedQuery(),
    node: Number(route.params.node || 0),
  };
}

function getReportParams() {
  return {
    ...getSharedQuery(),
    node: Number(route.params.node || 0),
    report: String(getQueryValue(route.query.report) || "all"),
    page: Number(getQueryValue(route.query.page) || 1),
    itemsPerPage: Number(
      getQueryValue(route.query.itemsPerPage) || rows.value || 20,
    ),
    sortBy: String(getQueryValue(route.query.sortBy) || ""),
    sortOrder: String(getQueryValue(route.query.sortOrder) || ""),
    search: String(getQueryValue(route.query.search) || ""),
    searchContent:
      "1" === String(getQueryValue(route.query.searchContent) || "0")
        ? 1
        : undefined,
    allVersions:
      "1" === String(getQueryValue(route.query.allVersions) || "0")
        ? 1
        : undefined,
    categoryIds: String(getQueryValue(route.query.categoryIds) || ""),
    matchAllCategories:
      "1" === String(getQueryValue(route.query.matchAllCategories) || "0")
        ? 1
        : undefined,
    target: String(getQueryValue(route.query.target) || ""),
    userId: Number(getQueryValue(route.query.userId) || 0) || undefined,
  };
}

function getPageRoute(reflink = "index") {
  return {
    name: "WikiPage",
    params: { node: route.params.node },
    query: {
      ...getSharedQuery(),
      title: reflink || "index",
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

function getEditRoute(item) {
  return {
    name: "WikiPageEdit",
    params: {
      node: route.params.node,
      pageId: item.pageId,
    },
    query: getSharedQuery(),
  };
}

function getHistoryRoute(item) {
  return {
    name: "WikiPageHistory",
    params: {
      node: route.params.node,
      pageId: item.pageId,
    },
    query: getSharedQuery(),
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

function getBacklinkRoute(reflink) {
  return getReportRoute("backlinks", { target: reflink });
}

function getUserContributionRoute(userId) {
  return getReportRoute("user-contributions", { userId: userId || 0 });
}

function changeReport() {
  void router.push(getReportRoute(selectedReport.value));
}

function updateSearchCategories(value) {
  searchForm.categoryIds = Array.isArray(value)
    ? value.map(Number).filter((item) => item > 0)
    : [];
}

function submitSearch() {
  void router.push(
    getReportRoute("search", {
      search: searchForm.text.trim(),
      searchContent: "content" === searchForm.scope ? 1 : undefined,
      allVersions: "all" === searchForm.versions ? 1 : undefined,
      categoryIds: searchForm.categoryIds.length
        ? searchForm.categoryIds.join(",")
        : undefined,
      matchAllCategories:
        searchForm.categoryIds.length > 1 && searchForm.matchAllCategories
          ? 1
          : undefined,
      page: 1,
    }),
  );
}

function onPage(event) {
  void updateTableQuery({
    page: Number(event.page || 0) + 1,
    itemsPerPage: Number(event.rows || rows.value || 20),
  });
}

function onSort(event) {
  void updateTableQuery({
    page: 1,
    sortBy: String(event.sortField || ""),
    sortOrder: Number(event.sortOrder) < 0 ? "desc" : "asc",
  });
}

async function updateTableQuery(values) {
  await router.push({
    name: "WikiReports",
    params: { node: route.params.node },
    query: {
      ...route.query,
      ...values,
    },
  });
}

function formatDate(value) {
  if (!value) {
    return "—";
  }

  const date = new Date(value);

  return Number.isNaN(date.getTime()) ? "—" : date.toLocaleString();
}

function formatNumber(value) {
  const number = Number(value || 0);

  return Number.isFinite(number) ? number.toLocaleString() : "0";
}

function formatBoolean(value) {
  return value ? t("Yes") : t("No");
}

function formatPercent(value) {
  return `${Number(value || 0).toLocaleString()}%`;
}

function formatPair(primary, secondary, secondaryLabel) {
  return `${formatNumber(primary)} (${t(secondaryLabel)}: ${formatNumber(secondary)})`;
}

function canDeletePageItem(item) {
  return (
    reportData.canManage &&
    Number(item?.pageId || 0) > 0 &&
    !reportData.allVersions &&
    ["all", "search", "backlinks"].includes(reportData.report)
  );
}

function confirmDeletePage(item) {
  const message =
    "index" === item.reflink
      ? t(
          "Deleting the Wiki homepage is not recommended. You can recreate it later.",
        )
      : t("Are you sure you want to delete this page and its history?");

  requireConfirmation({
    message,
    accept: () => deletePage(item),
  });
}

async function deletePage(item) {
  isManaging.value = true;
  errorMessage.value = "";

  try {
    await wikiService.deletePage(
      Number(item.pageId),
      getManagementParams(),
      reportData.managementCsrfToken,
    );
    await loadReport();
  } catch (error) {
    console.error("Error deleting Wiki page", error);
    errorMessage.value = getErrorMessage(error);
  } finally {
    isManaging.value = false;
  }
}

async function changeContextSubscription() {
  isManaging.value = true;
  errorMessage.value = "";

  try {
    await wikiService.setContextSubscription(
      !reportData.allChangesSubscribed,
      getManagementParams(),
      reportData.managementCsrfToken,
    );
    await loadReport();
  } catch (error) {
    console.error("Error changing Wiki context subscription", error);
    errorMessage.value = getErrorMessage(error);
  } finally {
    isManaging.value = false;
  }
}

function confirmDeleteWiki() {
  requireConfirmation({
    message: t("Are you sure you want to delete this Wiki?"),
    accept: deleteWiki,
  });
}

async function deleteWiki() {
  isManaging.value = true;
  errorMessage.value = "";

  try {
    await wikiService.deleteContext(
      getManagementParams(),
      reportData.managementCsrfToken,
    );
    await router.push(getPageRoute("index"));
  } catch (error) {
    console.error("Error deleting Wiki context", error);
    errorMessage.value = getErrorMessage(error);
  } finally {
    isManaging.value = false;
  }
}

function getErrorMessage(error) {
  return (
    error?.response?.data?.detail ||
    error?.response?.data?.["hydra:description"] ||
    error?.response?.data?.error ||
    t("An error occurred")
  );
}

async function loadReport() {
  isLoading.value = true;
  errorMessage.value = "";

  try {
    const response = await wikiService.getReport(getReportParams());
    Object.assign(reportData, createEmptyReport(), response);
    selectedReport.value = reportData.report;
    rows.value = Number(reportData.itemsPerPage || 20);
    sortField.value = reportData.sortBy || null;
    sortOrder.value = "desc" === reportData.sortOrder ? -1 : 1;
    searchForm.text = reportData.search || "";
    searchForm.scope = reportData.searchContent ? "content" : "title";
    searchForm.versions = reportData.allVersions ? "all" : "latest";
    searchForm.categoryIds = Array.isArray(reportData.categoryIds)
      ? reportData.categoryIds.map(Number)
      : [];
    searchForm.matchAllCategories = Boolean(reportData.matchAllCategories);
  } catch (error) {
    console.error("Error loading Wiki report", error);
    errorMessage.value = getErrorMessage(error);
  } finally {
    isLoading.value = false;
  }
}

onMounted(loadReport);
watch(() => route.fullPath, loadReport);
</script>
