<template>
  <main class="space-y-5 pb-8">
    <Message
      v-if="errorMessage"
      :closable="false"
      severity="error"
    >
      {{ errorMessage }}
    </Message>
    <Message
      v-if="successMessage"
      :closable="false"
      severity="success"
    >
      {{ successMessage }}
    </Message>

    <header class="border-b border-gray-25 pb-3">
      <h1 class="text-2xl font-semibold text-gray-90">{{ t("Statistics") }}</h1>
    </header>

    <div class="flex flex-wrap gap-2">
      <BaseButton
        icon="format-list-bulleted"
        :label="t('Reports catalog')"
        to-url="/main/admin/reports_catalog.php"
        type="tertiary-alternative"
      />
      <BaseButton
        icon="arrow-left"
        :label="t('Back')"
        to-url="/main/admin/index.php"
        type="tertiary-alternative"
      />
    </div>

    <nav class="w-full">
      <div class="overflow-x-auto pb-0.5">
        <div class="stats-menu-grid">
          <section
            v-for="group in reportGroups"
            :key="group.label"
            class="h-fit min-w-0 self-start rounded-2xl border bg-white p-4 shadow-sm"
            :class="sectionHasActive(group) ? 'border-primary/30 ring-1 ring-primary/20' : 'border-gray-25'"
          >
            <h2 class="flex items-center gap-2 text-sm font-semibold text-gray-90">
              <span
                class="h-2 w-2 rounded-full"
                :class="sectionHasActive(group) ? 'bg-primary' : 'bg-gray-50'"
              />
              {{ t(group.label) }}
            </h2>

            <ul class="mt-3 space-y-1">
              <li
                v-for="item in group.items"
                :key="`${item.report}-${item.type || ''}`"
              >
                <router-link
                  v-if="isModernReport(item.report)"
                  :to="modernReportRoute(item)"
                  :aria-current="isActiveItem(item) ? 'page' : undefined"
                  class="group flex items-start justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-primary/30"
                  :class="
                    isActiveItem(item)
                      ? 'bg-primary/10 text-primary ring-1 ring-primary/25'
                      : 'text-gray-90 hover:bg-gray-15 hover:text-gray-90'
                  "
                >
                  <span class="flex min-w-0 items-start gap-2">
                    <span
                      class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full"
                      :class="isActiveItem(item) ? 'bg-primary' : 'bg-gray-50 group-hover:bg-primary/60'"
                    />
                    <span class="break-words leading-5">{{ reportLabel(item) }}</span>
                  </span>
                  <span
                    v-if="isActiveItem(item)"
                    class="inline-flex items-center rounded-full bg-primary/15 px-2 py-0.5 text-xs font-semibold text-primary"
                  >
                    {{ t("Active") }}
                  </span>
                </router-link>
                <a
                  v-else
                  :href="legacyReportUrl(item)"
                  class="group flex items-start justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium text-gray-90 transition hover:bg-gray-15 hover:text-gray-90 focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
                  <span class="flex min-w-0 items-start gap-2">
                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-50 group-hover:bg-primary/60" />
                    <span class="break-words leading-5">{{ reportLabel(item) }}</span>
                  </span>
                </a>
              </li>
            </ul>
          </section>
        </div>
      </div>

      <div
        v-if="activeMenuInfo"
        class="mt-4 flex flex-wrap items-center gap-2 text-sm text-gray-90"
      >
        <span class="font-semibold">{{ t("You are here") }}:</span>
        <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
          {{ t(activeMenuInfo.section) }} · {{ reportLabel(activeMenuInfo.item) }}
        </span>
      </div>
    </nav>

    <div class="my-6 h-px w-full bg-gray-25" />

    <section
      v-if="showFilters"
      class="space-y-4"
    >
      <div
        v-if="activeReport === 'tool_usage'"
        class="space-y-4"
      >
        <Message
          v-if="report.meta.noToolsAvailable"
          :closable="false"
          severity="info"
        >
          {{ t("No tool available for this report") }}
        </Message>
        <template v-else>
          <h3 class="text-lg font-semibold text-gray-90">{{ t("Tool-based resource count") }}</h3>
          <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div class="w-full lg:max-w-2xl">
              <BaseMultiSelect
                v-model="filters.toolIds"
                :filter="true"
                input-id="admin-statistics-tool-ids"
                :label="t('Select tools')"
                :options="toolOptions"
              />
            </div>
            <BaseButton
              :disabled="filters.toolIds.length === 0"
              icon="search"
              :label="t('Generate report')"
              type="primary"
              @click="applyToolUsageFilter"
            />
          </div>
        </template>
      </div>

      <div
        v-else-if="activeReport === 'courselastvisit'"
        class="flex flex-col gap-4 sm:flex-row sm:items-end"
      >
        <div class="w-full sm:max-w-48">
          <BaseInputNumber
            id="admin-statistics-date-diff"
            v-model="filters.dateDiff"
            :label="t('Days')"
            :min="1"
            :max="36500"
          />
        </div>
        <BaseButton
          icon="search"
          :label="t('Search')"
          type="primary"
          @click="applyLastVisitFilter"
        />
      </div>

      <div
        v-else-if="activeReport === 'recentlogins'"
        class="flex flex-col gap-4 sm:flex-row sm:items-end"
      >
        <div class="w-full sm:max-w-72">
          <BaseSelect
            id="admin-statistics-session-duration"
            v-model="filters.sessionDuration"
            :label="`${t('Session min duration')} (${t('Minutes')})`"
            name="session_duration"
            :options="sessionDurationOptions"
          />
        </div>
        <BaseButton
          :label="t('Filter')"
          type="primary"
          @click="applyRecentLoginsFilter"
        />
      </div>

      <div
        v-else-if="activeReport === 'zombies'"
        class="flex flex-col gap-4 lg:flex-row lg:items-end"
      >
        <div class="w-full lg:max-w-xs">
          <BaseCalendar
            id="admin-statistics-zombie-ceiling"
            v-model="filters.zombieCeiling"
            :label="t('Latest access')"
          />
        </div>
        <BaseCheckbox
          id="admin-statistics-zombie-active-only"
          v-model="filters.zombieActiveOnly"
          :label="t('Active only')"
          name="active_only"
        />
        <BaseButton
          icon="search"
          :label="t('Search')"
          type="primary"
          @click="applyZombieFilter"
        />
      </div>

      <div
        v-else-if="activeReport === 'duplicated_users'"
        class="space-y-4"
      >
        <div class="flex flex-wrap gap-2">
          <BaseButton
            :label="t('By name')"
            :type="filters.duplicateMode === 'name' ? 'primary' : 'tertiary'"
            @click="setDuplicateMode('name')"
          />
          <BaseButton
            :label="t('By email')"
            :type="filters.duplicateMode === 'email' ? 'primary' : 'tertiary'"
            @click="setDuplicateMode('email')"
          />
          <BaseButton
            :label="t('By extra field')"
            :type="filters.duplicateMode === 'extra' ? 'primary' : 'tertiary'"
            @click="setDuplicateMode('extra')"
          />
        </div>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
          <div
            v-if="filters.duplicateMode === 'extra'"
            class="w-full lg:max-w-md"
          >
            <BaseSelect
              id="admin-statistics-duplicate-extra-field"
              v-model="filters.duplicateExtraFieldId"
              :label="t('Profile field')"
              name="extra_field_id"
              :options="duplicateExtraFieldOptions"
            />
          </div>
          <BaseButton
            v-if="filters.duplicateMode === 'extra'"
            :disabled="Number(filters.duplicateExtraFieldId) <= 0"
            icon="search"
            :label="t('Search')"
            type="primary"
            @click="applyDuplicateFilter"
          />
          <BaseButton
            icon="file-delimited-outline"
            :is-loading="exporting"
            :label="t('Export to CSV')"
            type="primary-alternative"
            @click="downloadCurrentReport('csv')"
          />
          <BaseButton
            icon="file-excel"
            :is-loading="exporting"
            :label="t('Export as XLS')"
            type="primary-alternative"
            @click="downloadCurrentReport('xls')"
          />
        </div>
      </div>

      <div
        v-else-if="activeReport === 'session_by_date'"
        class="flex flex-col gap-4 xl:flex-row xl:items-end"
      >
        <div class="w-full xl:max-w-md">
          <BaseCalendar
            id="admin-statistics-session-range"
            v-model="filters.sessionRange"
            :label="t('Date range')"
            type="range"
          />
        </div>
        <div class="w-full xl:max-w-64">
          <BaseSelect
            id="admin-statistics-session-status"
            v-model="filters.statusId"
            :label="t('Session status')"
            name="status_id"
            :options="statusOptions"
          />
        </div>
        <BaseButton
          icon="search"
          :label="t('Search')"
          type="primary"
          @click="applySessionFilter"
        />
      </div>

      <div
        v-else-if="usesDateRange"
        class="flex flex-col gap-4 xl:flex-row xl:items-end"
      >
        <div class="w-full xl:max-w-md">
          <BaseCalendar
            id="admin-statistics-date-range"
            v-model="filters.dateRange"
            :label="t('Date range')"
            type="range"
          />
        </div>
        <BaseButton
          v-if="activeReport === 'user_session'"
          :label="t('Last week')"
          type="plain"
          @click="setUserSessionLastWeek"
        />
        <BaseButton
          icon="search"
          :label="t('Search')"
          type="primary"
          @click="applyDateRangeFilter"
        />
        <BaseButton
          v-if="report.meta.canExportCsv"
          icon="file-delimited-outline"
          :is-loading="exporting"
          :label="t('Export to CSV')"
          type="primary-alternative"
          @click="downloadCurrentReport('csv')"
        />
        <BaseButton
          v-if="report.meta.canExportXls && activeReport !== 'user_session'"
          icon="file-excel"
          :is-loading="exporting"
          :label="t('Export to XLS')"
          type="primary-alternative"
          @click="downloadCurrentReport('xls')"
        />
      </div>
    </section>

    <section
      v-if="loading"
      class="flex min-h-48 items-center justify-center rounded-xl border border-gray-25 bg-white p-6 shadow-sm"
    >
      <ProgressSpinner />
    </section>

    <template v-else>
      <h2
        v-if="report.meta.contentTitle"
        class="mb-[18px] text-xl font-semibold text-gray-90"
      >
        {{ report.meta.contentTitle }}
      </h2>

      <h2
        v-if="activeReport === 'no_login_users' && Number(report.meta.totalUsers) >= 0"
        class="mb-4 text-lg font-semibold text-gray-90"
      >
        {{ `${t("Number of users")}: ${legacyInteger(report.meta.totalUsers)}` }}
      </h2>

      <section
        v-if="report.title && !legacyTitlelessReports.has(activeReport)"
        class="space-y-1"
      >
        <h2 class="text-xl font-semibold text-gray-90">{{ report.title }}</h2>
        <p
          v-if="report.description"
          class="text-sm text-gray-60"
        >
          {{ report.description }}
        </p>
      </section>

      <section
        v-if="report.stats.length && activeReport !== 'session_by_date'"
        class="overflow-x-auto"
      >
        <table class="w-full border-collapse border border-gray-25 text-sm">
          <thead>
            <tr class="bg-gray-10 text-left text-gray-90">
              <th
                class="border border-gray-25 px-3 py-2 font-semibold"
                :colspan="report.meta.showStatsPercentage ? 4 : 3"
              >
                {{ report.meta.statsTitle || report.title }}
              </th>
            </tr>
            <tr class="border-b border-gray-25 text-left text-gray-90">
              <th class="border border-gray-25 px-3 py-2 font-semibold">{{ t("Name") }}</th>
              <th class="border border-gray-25 px-3 py-2 font-semibold">{{ t("Distribution") }}</th>
              <th class="border border-gray-25 px-3 py-2 text-right font-semibold">{{ t("Count") }}</th>
              <th
                v-if="report.meta.showStatsPercentage"
                class="border border-gray-25 px-3 py-2 text-right font-semibold"
              >
                {{ t("Percentage") }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="stat in report.stats"
              :key="`${stat.label}-${stat.value}`"
              class="border-b border-gray-15 odd:bg-white even:bg-gray-5"
            >
              <td class="whitespace-pre-line border border-gray-25 px-3 py-2 align-top">{{ stat.label }}</td>
              <td class="border border-gray-25 px-3 py-2 align-middle">
                <div
                  class="flex items-center gap-2.5"
                  :title="`${legacyPercentage(stat.value)}%`"
                >
                  <div class="h-2.5 min-w-36 flex-1 overflow-hidden rounded-full bg-gray-20">
                    <div
                      class="h-full rounded-full bg-primary"
                      :style="{ width: `${legacyBarPercent(stat.value)}%` }"
                    />
                  </div>
                  <div class="min-w-14 whitespace-nowrap text-right text-xs text-gray-60">
                    {{ legacyPercentage(stat.value) }}%
                  </div>
                </div>
              </td>
              <td class="border border-gray-25 px-3 py-2 text-right align-top">{{ legacyInteger(stat.value) }}</td>
              <td
                v-if="report.meta.showStatsPercentage"
                class="border border-gray-25 px-3 py-2 text-right align-top"
              >
                {{ legacyPercentage(stat.value) }}%
              </td>
            </tr>
          </tbody>
        </table>
      </section>


      <section
        v-if="report.statsGroups.length && !report.meta.legacyStatsGroups"
        class="grid gap-4 lg:grid-cols-2"
      >
        <article
          v-for="group in report.statsGroups"
          :key="group.title"
          class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
        >
          <h3 class="mb-3 text-lg font-semibold text-gray-90">{{ group.title }}</h3>
          <div class="divide-y divide-gray-20">
            <div
              v-for="item in group.items || []"
              :key="`${item.label}-${item.value}`"
              class="flex items-center justify-between gap-3 py-2 text-sm"
            >
              <span class="text-gray-70">{{ stripHtml(item.label) }}</span>
              <span class="font-semibold text-gray-90">{{ formatNumber(item.value) }}</span>
            </div>
          </div>
        </article>
      </section>

      <section
        v-if="activeReport === 'users_online' && onlineCards.length"
        class="mx-auto w-full max-w-6xl"
      >
        <div class="mb-4 flex items-center justify-between gap-4">
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Users online") }}</h2>
          <div class="text-sm text-gray-50">{{ report.meta.generatedAt }}</div>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <article
            v-for="card in onlineCards"
            :key="`online-${card.minutes}`"
            :class="onlineCardClasses(card.minutes)"
          >
            <div class="flex items-center gap-3">
              <div :class="onlineIconClasses(card.minutes)">
                <BaseIcon
                  :icon="onlineCardIcon(card.minutes)"
                  size="normal"
                />
              </div>
              <div class="min-w-0">
                <div class="text-sm text-gray-50">{{ card.label }}</div>
                <div class="text-2xl font-semibold text-gray-90">{{ legacyInteger(card.value) }}</div>
              </div>
            </div>
          </article>
        </div>

        <h3 class="mb-4 text-lg font-semibold text-gray-90">{{ t("Users active in a test") }}</h3>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <article
            v-for="card in testCards"
            :key="`test-${card.minutes}`"
            :class="onlineCardClasses(card.minutes)"
          >
            <div class="flex items-center gap-3">
              <div :class="onlineIconClasses(card.minutes)">
                <BaseIcon
                  :icon="onlineCardIcon(card.minutes)"
                  size="normal"
                />
              </div>
              <div class="min-w-0">
                <div class="text-sm text-gray-50">{{ card.label }}</div>
                <div class="text-2xl font-semibold text-gray-90">{{ legacyInteger(card.value) }}</div>
              </div>
            </div>
          </article>
        </div>
      </section>

      <section
        v-if="activeReport === 'session_by_date' && report.meta.legacySessionByDate && hasSessionDateRange"
        class="space-y-4"
      >
        <h3 class="text-lg font-semibold text-gray-90">{{ report.meta.statsTitle || t("Global statistics") }}</h3>

        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-sm">
            <tbody>
              <tr
                v-for="stat in report.stats"
                :key="`session-stat-${stat.label}`"
                class="border-b border-gray-15 odd:bg-white even:bg-gray-5"
              >
                <td class="px-3 py-2">{{ stat.label }}</td>
                <td class="px-3 py-2">{{ stat.value }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          v-if="Number(report.table.totalItems || 0) > 0 && report.charts.length"
          class="grid grid-cols-1 gap-4 lg:grid-cols-3"
        >
          <div
            v-for="chart in report.charts"
            :key="`session-table-${chart.title}`"
            class="overflow-x-auto"
          >
            <h4 class="mb-2 text-base font-semibold text-gray-90">{{ chart.title }}</h4>
            <ChartDataTable :chart="chart" />
          </div>
        </div>

        <BaseTable
          :total-items="courseSessionRows.length"
          :values="courseSessionRows"
        >
          <Column
            field="course"
            :header="t('Course')"
          />
          <Column
            field="sessionsCount"
            :header="t('Sessions count')"
          />
        </BaseTable>

        <div
          v-if="Number(report.table.totalItems || 0) > 0 && report.charts.length"
          class="grid grid-cols-1 gap-4 lg:grid-cols-3"
        >
          <div
            v-for="chart in report.charts"
            :key="`session-chart-${chart.title}`"
            class="h-[360px]"
          >
            <Chart
              :data="chart.data"
              :options="chartOptions(chart.title)"
              :type="chart.type || 'pie'"
              class="h-full"
            />
          </div>
        </div>

        <BaseTable
          :total-items="Number(report.table.totalItems || 0)"
          :values="report.table.items || []"
        >
          <Column
            v-for="column in report.table.columns || []"
            :key="column.key"
            :field="column.key"
            :header="column.label"
          >
            <template #body="{ data }">
              <span>{{ data[column.key] }}</span>
            </template>
          </Column>
        </BaseTable>

        <BaseButton
          v-if="report.meta.canExportXls"
          icon="file-excel"
          :is-loading="exporting"
          :label="t('Export to XLS')"
          type="plain"
          @click="downloadCurrentReport('xls')"
        />
      </section>

      <section
        v-if="hasChart"
        :class="legacyFlatChart ? '' : 'rounded-xl border border-gray-25 bg-white p-4 shadow-sm'"
      >
        <div :class="chartWrapperClass">
          <Chart
            :data="report.chart.data"
            :options="chartOptions(report.chart.title)"
            :type="report.chart.type || 'pie'"
            class="h-full"
          />
        </div>
        <ChartDataTable
          v-if="showChartDataTables"
          :chart="report.chart"
        />
        <div
          v-if="activeReport === 'new_user_registrations' && route.query.month"
          class="mt-2"
        >
          <BaseButton
            :label="t('Back to months')"
            type="primary"
            @click="clearRegistrationDrilldown"
          />
        </div>
      </section>

      <section
        v-if="report.meta.legacyUsersActive && report.charts.length"
        class="space-y-[18px]"
      >
        <h2 class="text-lg font-semibold text-gray-90">
          {{ `${t("Total number of students")}: ${legacyInteger(report.meta.studentCount)}` }}
        </h2>

        <div class="space-y-[18px]">
          <div
            v-for="chart in report.charts"
            :key="`users-active-table-${chart.title}`"
          >
            <h3 class="mb-2 text-lg font-semibold text-gray-90">{{ chart.title }}</h3>
            <ChartDataTable :chart="chart" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
          <div
            v-for="chart in report.charts.slice(0, 3)"
            :key="`users-active-chart-a-${chart.title}`"
            class="mb-5 mt-5 h-[360px]"
          >
            <Chart
              :data="chart.data"
              :options="chartOptions(chart.title)"
              :type="chart.type || 'pie'"
              class="h-full"
            />
          </div>
        </div>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div
            v-for="chart in report.charts.slice(3, 5)"
            :key="`users-active-chart-b-${chart.title}`"
            class="mb-5 mt-5 h-[360px]"
          >
            <Chart
              :data="chart.data"
              :options="chartOptions(chart.title)"
              :type="chart.type || 'pie'"
              class="h-full"
            />
          </div>
        </div>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div
            v-for="chart in report.charts.slice(5, 7)"
            :key="`users-active-chart-c-${chart.title}`"
            class="mb-5 mt-5 h-[360px]"
          >
            <Chart
              :data="chart.data"
              :options="chartOptions(chart.title)"
              :type="chart.type || 'pie'"
              class="h-full"
            />
          </div>
        </div>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div
            v-for="chart in report.charts.slice(7, 8)"
            :key="`users-active-chart-d-${chart.title}`"
            class="mb-5 mt-5 h-[360px]"
          >
            <Chart
              :data="chart.data"
              :options="chartOptions(chart.title)"
              :type="chart.type || 'pie'"
              class="h-full"
            />
          </div>
        </div>
      </section>

      <Message
        v-if="activeReport === 'new_user_registrations' && report.meta.noData"
        :closable="false"
        severity="info"
      >
        {{ t("No data available for the selected date range") }}
      </Message>

      <section
        v-if="report.meta.legacyRegistrationCharts && report.charts.length && !route.query.month"
        class="space-y-5"
      >
        <div class="h-[360px] w-full">
          <Chart
            :data="report.charts[0].data"
            :options="chartOptions(report.charts[0].title)"
            :type="report.charts[0].type || 'bar'"
            class="h-full"
            @select="handleRegistrationChartSelect"
          />
        </div>
        <template v-if="report.charts[1]">
          <hr />
          <div class="mx-auto h-[520px] w-full max-w-[700px]">
            <Chart
              :data="report.charts[1].data"
              :options="chartOptions(report.charts[1].title)"
              :type="report.charts[1].type || 'pie'"
              class="h-full"
            />
          </div>
        </template>
      </section>

      <section
        v-if="report.charts.length && !report.meta.legacyUsersActive && !report.meta.legacyRegistrationCharts && activeReport !== 'session_by_date'"
        :class="report.meta.legacyFlatCharts ? legacyChartsGridClass : 'grid gap-4 lg:grid-cols-2'"
      >
        <article
          v-for="chart in report.charts"
          :key="chart.title"
          :class="report.meta.legacyFlatCharts ? '' : 'rounded-xl border border-gray-25 bg-white p-4 shadow-sm'"
        >
          <div :class="report.meta.legacyFlatCharts ? 'mb-5 h-[360px]' : 'h-[360px]'">
            <Chart
              :data="chart.data"
              :options="chartOptions(chart.title)"
              :type="chart.type || 'pie'"
              class="h-full"
            />
          </div>
          <ChartDataTable
            v-if="showChartDataTables"
            :chart="chart"
          />
        </article>
      </section>

      <section
        v-if="report.statsGroups.length && report.meta.legacyStatsGroups"
        class="space-y-4"
      >
        <div
          v-for="group in report.statsGroups"
          :key="group.title"
          class="overflow-x-auto"
        >
          <table class="w-full border-collapse border border-gray-25 text-sm">
            <thead>
              <tr class="bg-gray-10 text-left text-gray-90">
                <th
                  class="border border-gray-25 px-3 py-2 font-semibold"
                  colspan="3"
                >
                  {{ group.title }}
                </th>
              </tr>
              <tr class="border-b border-gray-25 text-left text-gray-90">
                <th class="border border-gray-25 px-3 py-2 font-semibold">{{ t("Name") }}</th>
                <th class="border border-gray-25 px-3 py-2 font-semibold">{{ t("Distribution") }}</th>
                <th class="border border-gray-25 px-3 py-2 text-right font-semibold">{{ t("Count") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in group.items || []"
                :key="`${item.label}-${item.value}`"
                class="border-b border-gray-15 odd:bg-white even:bg-gray-5"
              >
                <td class="border border-gray-25 px-3 py-2 align-top">
                  <div class="flex items-center justify-between gap-3">
                    <span>{{ item.label }}</span>
                    <span
                      v-if="item.detail"
                      class="mr-[5px] text-sm text-gray-50"
                    >
                      {{ item.detail }}
                    </span>
                  </div>
                </td>
                <td class="border border-gray-25 px-3 py-2 align-middle">
                  <div
                    class="flex items-center gap-2.5"
                    :title="`${legacyGroupPercentage(group.items, item.value)}%`"
                  >
                    <div class="h-2.5 min-w-36 flex-1 overflow-hidden rounded-full bg-gray-20">
                      <div
                        class="h-full rounded-full bg-primary"
                        :style="{ width: `${legacyGroupBarPercent(group.items, item.value)}%` }"
                      />
                    </div>
                    <div class="min-w-14 whitespace-nowrap text-right text-xs text-gray-60">
                      {{ legacyGroupPercentage(group.items, item.value) }}%
                    </div>
                  </div>
                </td>
                <td class="border border-gray-25 px-3 py-2 text-right align-top">{{ legacyInteger(item.value) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section
        v-if="activeReport === 'zombies' && hasTable"
        class="ch-zombies-wrap space-y-4"
      >
        <div class="flex flex-wrap gap-2">
          <BaseButton
            :disabled="!selectedZombieUsers.length"
            icon="toggle-switch"
            :is-loading="maintenanceLoading"
            :label="t('Activate')"
            type="success"
            @click="confirmZombieAction('activate')"
          />
          <BaseButton
            :disabled="!selectedZombieUsers.length"
            icon="toggle-switch-off"
            :is-loading="maintenanceLoading"
            :label="t('Deactivate')"
            type="secondary"
            @click="confirmZombieAction('deactivate')"
          />
          <BaseButton
            :disabled="!selectedZombieUsers.length"
            icon="delete"
            :is-loading="maintenanceLoading"
            :label="t('Delete')"
            type="danger"
            @click="confirmZombieAction('delete')"
          />
        </div>
        <BaseTable
          v-model:rows="tableRows"
          v-model:selected-items="selectedZombieUsers"
          v-model:sort-field="zombieSortField"
          v-model:sort-order="zombieSortOrder"
          data-key="id"
          :is-loading="loading"
          :lazy="true"
          :text-for-empty="t('No results found')"
          :total-items="Number(report.table.totalItems || 0)"
          :values="report.table.items || []"
          @page="handlePage"
          @sort="handleZombieSort"
        >
          <Column selection-mode="multiple" />
          <Column
            v-for="column in report.table.columns || []"
            :key="column.key"
            :field="column.key"
            :header="column.label"
            :sort-field="zombieSortFieldForColumn(column.key)"
            :sortable="zombieSortableColumns.includes(column.key)"
          >
            <template #body="{ data }">
              <a
                v-if="column.key === 'email' && data.email"
                :href="`mailto:${encodeURIComponent(data.email)}`"
                class="text-primary underline hover:text-primary/80"
              >
                {{ data.email }}
              </a>
              <span v-else-if="['registeredDate', 'lastAccess'].includes(column.key)">
                {{ formatLegacyShortDate(data[column.key]) }}
              </span>
              <BaseIcon
                v-else-if="column.key === 'activeLabel'"
                class="ch-tool-icon"
                :icon="Number(data.active) === 1 ? 'check-circle' : 'close-circle'"
                size="small"
                :title="Number(data.active) === 1 ? t('Yes') : t('No')"
              />
              <span v-else>{{ data[column.key] }}</span>
            </template>
          </Column>
        </BaseTable>
      </section>

      <section
        v-if="activeReport === 'duplicated_users'"
        class="space-y-4"
      >
        <Message
          :closable="false"
          severity="info"
        >
          {{ duplicateModeDescription }}
        </Message>
        <article class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm">
          <h3 class="font-semibold text-gray-90">{{ t("How to use this report") }}</h3>
          <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-gray-70">
            <li>
              <strong>{{ t("Disable / Enable") }}</strong>:
              {{ t("Only blocks or restores login. It does not delete the user and does not remove subscriptions.") }}
            </li>
            <li>
              <strong>{{ t("Unify") }}</strong>:
              {{
                t(
                  "Click Unify on the account that should remain. The system will merge all other accounts in the same duplicate group into it. Merged accounts will be permanently deleted and will disappear from this report. This action cannot be undone.",
                )
              }}
            </li>
            <li>
              <strong>{{ t("Permanent deletion") }}</strong>:
              {{
                t(
                  "Unify already permanently deletes merged accounts. Use the Users list only if you want to delete additional accounts manually.",
                )
              }}
            </li>
          </ul>
        </article>
        <article
          v-for="group in duplicateGroups"
          :key="group.key"
          class="overflow-hidden rounded border border-info/30 bg-info/5"
        >
          <header class="flex flex-wrap items-center gap-3 border-b border-info/30 bg-info/10 px-3 py-2">
            <h3 class="font-semibold text-gray-90">{{ group.label }}</h3>
            <span class="rounded-full bg-info px-2 py-1 text-xs font-semibold text-white">
              {{ group.items?.length || 0 }} {{ t("Users") }}
            </span>
          </header>
          <div class="p-3">
            <BaseTable
              :total-items="group.items?.length || 0"
              :values="group.items || []"
            >
              <Column
                v-for="column in duplicateColumns"
                :key="column.key"
                :field="column.key"
                :header="column.label"
              >
                <template #body="{ data }">
                  <span>{{ data[column.key] }}</span>
                </template>
              </Column>
              <Column :header="t('Actions')">
                <template #body="{ data }">
                  <div class="flex flex-wrap gap-2">
                    <BaseButton
                      :label="t('Details')"
                      size="small"
                      :to-url="data.detailsUrl"
                      type="plain"
                    />
                    <BaseButton
                      :is-loading="maintenanceLoading"
                      :label="Number(data.active) === 1 ? t('Deactivate') : t('Enable')"
                      size="small"
                      :type="Number(data.active) === 1 ? 'danger' : 'success'"
                      @click="confirmDuplicateStatus(data)"
                    />
                    <BaseButton
                      :is-loading="maintenanceLoading"
                      :label="t('Unify')"
                      size="small"
                      type="plain"
                      @click="confirmDuplicateUnify(data)"
                    />
                  </div>
                </template>
              </Column>
            </BaseTable>
          </div>
        </article>
      </section>

      <section
        v-if="activeReport === 'quarterly_report' && quarterlyCards.length"
        class="space-y-4"
      >
        <div class="flex justify-end">
          <BaseButton
            icon="eye"
            :is-loading="loadingAllQuarterly"
            :label="`${t('Show')}: ${t('All')}`"
            type="primary"
            @click="loadAllQuarterlySections"
          />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
          <article
            v-for="card in quarterlyCards"
            :key="card.id"
            class="rounded-xl border border-gray-25 bg-white shadow-sm"
          >
            <header class="flex items-start justify-between gap-3 border-b border-gray-20 p-4">
              <h3 class="text-base font-semibold text-gray-90">{{ card.title }}</h3>
              <div class="flex gap-2">
                <BaseButton
                  icon="eye"
                  :is-loading="Boolean(quarterlyLoading[card.id])"
                  :label="t('Show')"
                  type="primary-alternative"
                  @click="toggleQuarterlySection(card.id)"
                />
                <BaseButton
                  icon="refresh"
                  :is-loading="Boolean(quarterlyLoading[card.id])"
                  :label="t('Refresh')"
                  type="secondary"
                  @click="loadQuarterlySection(card.id, true)"
                />
              </div>
            </header>

            <div
              v-if="quarterlyVisible[card.id]"
              class="space-y-4 p-4"
            >
              <ProgressSpinner v-if="quarterlyLoading[card.id]" />
              <Message
                v-else-if="quarterlyErrors[card.id]"
                :closable="false"
                severity="error"
              >
                {{ quarterlyErrors[card.id] }}
              </Message>
              <template v-else-if="quarterlySections[card.id]">
                <p
                  v-if="quarterlySections[card.id].message"
                  class="text-sm text-gray-70"
                >
                  {{ quarterlySections[card.id].message }}
                </p>

                <BaseTable
                  v-if="quarterlySections[card.id].columns"
                  :total-items="quarterlySections[card.id].items?.length || 0"
                  :values="quarterlySections[card.id].items || []"
                >
                  <Column
                    v-for="column in quarterlySections[card.id].columns"
                    :key="column.key"
                    :field="column.key"
                    :header="column.label"
                  />
                </BaseTable>

                <div
                  v-for="(table, tableIndex) in quarterlySections[card.id].tables || []"
                  :key="`${card.id}-table-${tableIndex}`"
                  class="space-y-2"
                >
                  <h4
                    v-if="table.title"
                    class="font-semibold text-gray-90"
                  >
                    {{ table.title }}
                  </h4>
                  <BaseTable
                    :total-items="table.items?.length || 0"
                    :values="table.items || []"
                  >
                    <Column
                      v-for="column in table.columns || []"
                      :key="column.key"
                      :field="column.key"
                      :header="column.label"
                    >
                      <template #body="{ data }">
                        <a
                          v-if="column.key === 'course' && data.courseUrl"
                          :href="data.courseUrl"
                          class="text-primary underline hover:text-primary/80"
                        >
                          {{ data[column.key] }}
                        </a>
                        <span v-else>{{ data[column.key] }}</span>
                      </template>
                    </Column>
                  </BaseTable>
                </div>

                <Message
                  v-if="quarterlySections[card.id].warning"
                  :closable="false"
                  severity="warn"
                >
                  {{ quarterlySections[card.id].warning }}
                </Message>
              </template>
            </div>
          </article>
        </div>
      </section>

      <p
        v-if="activeReport === 'courselastvisit' && report.meta.legacySummary"
        class="text-sm text-gray-90"
      >
        {{ report.meta.legacySummary }}
      </p>

      <h4
        v-if="activeReport === 'courses_usage' && report.meta.contentTitle"
        class="text-lg font-semibold text-gray-90"
      >
        {{ report.meta.contentTitle }}
      </h4>

      <section
        v-if="hasTable && !['zombies', 'duplicated_users', 'session_by_date'].includes(activeReport) && !(activeReport === 'tool_usage' && !filters.toolIds.length)"
        :class="legacyCourseReports.has(activeReport) || report.meta.legacyFlatTable ? '' : 'rounded-xl border border-gray-25 bg-white p-4 shadow-sm'"
      >
        <BaseTable
          v-model:rows="tableRows"
          v-model:sort-field="reportSortField"
          v-model:sort-order="reportSortOrder"
          :is-loading="loading"
          :lazy="Boolean(report.table.lazy)"
          :text-for-empty="tableEmptyText"
          :total-items="Number(report.table.totalItems || 0)"
          :values="report.table.items"
          @page="handlePage"
          @sort="handleReportSort"
        >
          <Column
            v-for="column in report.table.columns"
            :key="column.key"
            :field="column.key"
            :header="column.label"
            :sortable="Boolean(column.sortable)"
          >
            <template #body="{ data }">
              <a
                v-if="activeReport === 'tool_usage' && column.key === 'toolName' && data.link && data.link !== '-'"
                :href="data.link"
                class="text-primary underline hover:text-primary/80"
              >
                {{ data[column.key] }}
              </a>
              <router-link
                v-else-if="activeReport === 'courselastvisit' && column.key === 'courseTitle'"
                :to="`/course/${Number(data.courseId)}/home`"
                class="text-primary underline hover:text-primary/80"
              >
                {{ data[column.key] }}
              </router-link>
              <a
                v-else-if="activeReport === 'user_session' && column.key === 'session' && data.sessionUrl"
                :href="data.sessionUrl"
                class="text-primary underline hover:text-primary/80"
              >
                {{ data[column.key] }}
              </a>
              <a
                v-else-if="activeReport === 'users_online' && column.key === 'fullName' && data.detailsUrl"
                :href="data.detailsUrl"
                class="text-primary underline hover:text-primary/80"
              >
                {{ data[column.key] }}
              </a>
              <span v-else-if="activeReport === 'tool_usage' && column.key === 'lastUpdated'">
                {{ data[column.key] }}
              </span>
              <span v-else-if="isDateColumn(column.key)">
                {{ formatDateTime(data[column.key]) }}
              </span>
              <span v-else>{{ data[column.key] }}</span>
            </template>
          </Column>
        </BaseTable>

        <div
          v-if="activeReport === 'user_session' && report.meta.canExportXls"
          class="mt-4"
        >
          <BaseButton
            icon="file-excel"
            :is-loading="exporting"
            :label="t('Export to XLS')"
            type="plain"
            @click="downloadCurrentReport('xls')"
          />
        </div>
      </section>

      <BaseTable
        v-if="activeReport === 'session_by_date' && report.meta.legacySessionByDate && !hasSessionDateRange"
        :total-items="Number(report.table.totalItems || 0)"
        :values="report.table.items || []"
      >
        <Column
          v-for="column in report.table.columns || []"
          :key="`session-empty-${column.key}`"
          :field="column.key"
          :header="column.label"
        />
      </BaseTable>


    </template>
  </main>
</template>

<script setup>
import Chart from "primevue/chart"
import Column from "primevue/column"
import Message from "primevue/message"
import ProgressSpinner from "primevue/progressspinner"
import { computed, defineComponent, h, onBeforeUnmount, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import adminStatisticsService from "../../services/adminStatisticsService"
import { useConfirmation } from "../../composables/useConfirmation"

const route = useRoute()
const router = useRouter()
const { locale, t } = useI18n()
const { requireConfirmation } = useConfirmation()

const modernReports = new Set([
  "courses",
  "tools",
  "tool_usage",
  "courselastvisit",
  "coursebylanguage",
  "courses_usage",
  "users",
  "recentlogins",
  "logins",
  "pictures",
  "logins_by_date",
  "no_login_users",
  "zombies",
  "users_active",
  "users_online",
  "new_user_registrations",
  "subscription_by_day",
  "duplicated_users",
  "user_session",
  "quarterly_report",
  "messagesent",
  "messagereceived",
  "friends",
  "session_by_date",
])

const dateRangeReports = new Set([
  "logins_by_date",
  "users_active",
  "new_user_registrations",
  "subscription_by_day",
  "user_session",
])

const legacyCourseReports = new Set(["courses", "tools", "tool_usage", "courselastvisit", "coursebylanguage", "courses_usage"])
const legacyCoreUserReports = new Set([
  "users",
  "recentlogins",
  "logins",
  "pictures",
  "no_login_users",
  "users_online",
  "users_active",
  "new_user_registrations",
  "subscription_by_day",
])
const legacyTitlelessReports = new Set([
  ...legacyCourseReports,
  ...legacyCoreUserReports,
  "session_by_date",
  "user_session",
  "messagesent",
  "messagereceived",
  "friends",
  "zombies",
  "duplicated_users",
])

const reportGroups = [
  {
    label: "Courses",
    items: [
      { report: "courses", label: "Courses" },
      { report: "tools", label: "Tools access" },
      { report: "tool_usage", label: "Tool-based resource count" },
      { report: "courselastvisit", label: "Latest access" },
      { report: "coursebylanguage", label: "Number of courses by language" },
      { report: "courses_usage", label: "Courses usage" },
    ],
  },
  {
    label: "Users",
    items: [
      { report: "users_online", label: "Users online" },
      { report: "users", label: "Number of users" },
      { report: "recentlogins", label: "Logins" },
      { report: "logins", type: "month", label: "Logins" },
      { report: "logins", type: "day", label: "Logins" },
      { report: "logins", type: "hour", label: "Logins" },
      { report: "pictures", label: "Number of users", suffix: "Picture" },
      { report: "logins_by_date", label: "Logins by date" },
      { report: "no_login_users", label: "Not logged in for some time" },
      { report: "zombies", label: "Zombies" },
      { report: "users_active", label: "Users statistics" },
      { report: "new_user_registrations", label: "New users registrations" },
      { report: "subscription_by_day", label: "Course/Session subscriptions by day" },
      { report: "duplicated_users", label: "Duplicate users" },
    ],
  },
  {
    label: "System",
    items: [
      { report: "user_session", label: "Portal user session stats" },
      { report: "quarterly_report", label: "Quarterly report" },
    ],
  },
  {
    label: "Social",
    items: [
      { report: "messagereceived", label: "Number of messages received" },
      { report: "messagesent", label: "Number of messages sent" },
      { report: "friends", label: "Contacts count" },
    ],
  },
  {
    label: "Session",
    items: [{ report: "session_by_date", label: "Sessions by date" }],
  },
]

const loading = ref(false)
const exporting = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const selectedZombieUsers = ref([])
const zombieSortField = ref("firstname")
const zombieSortOrder = ref(-1)
const maintenanceLoading = ref(false)
const tableRows = ref(20)
const ONLINE_REFRESH_INTERVAL_MS = 15_000
let usersOnlineRefreshTimer = null
let usersOnlineRefreshInFlight = false
const courseLastVisitSortField = ref("courseId")
const courseLastVisitSortOrder = ref(1)
const userSessionSortField = ref("url")
const userSessionSortOrder = ref(1)
const loadingAllQuarterly = ref(false)
const quarterlySections = reactive({})
const quarterlyLoading = reactive({})
const quarterlyErrors = reactive({})
const quarterlyVisible = reactive({})
const filters = reactive({
  toolIds: [],
  dateDiff: 60,
  sessionRange: null,
  statusId: 0,
  sessionDuration: 0,
  dateRange: null,
  zombieCeiling: new Date(),
  zombieActiveOnly: false,
  duplicateMode: "name",
  duplicateExtraFieldId: 0,
})
const report = reactive({
  report: "",
  title: "",
  description: "",
  chart: {},
  charts: [],
  stats: [],
  statsGroups: [],
  table: {},
  filters: {},
  meta: {},
})

const activeReport = computed(() => String(route.query.report || ""))
const reportSortField = computed({
  get: () => (activeReport.value === "user_session" ? userSessionSortField.value : courseLastVisitSortField.value),
  set: (value) => {
    if (activeReport.value === "user_session") {
      userSessionSortField.value = String(value || "url")
      return
    }
    courseLastVisitSortField.value = String(value || "courseId")
  },
})
const reportSortOrder = computed({
  get: () => (activeReport.value === "user_session" ? userSessionSortOrder.value : courseLastVisitSortOrder.value),
  set: (value) => {
    if (activeReport.value === "user_session") {
      userSessionSortOrder.value = Number(value || 1)
      return
    }
    courseLastVisitSortOrder.value = Number(value || 1)
  },
})
const activeMenuInfo = computed(() => {
  for (const group of reportGroups) {
    const item = group.items.find((entry) => isActiveItem(entry))
    if (item) {
      return { section: group.label, item }
    }
  }
  return null
})
const toolOptions = computed(() => (Array.isArray(report.filters.tools) ? report.filters.tools : []))
const statusOptions = computed(() => (Array.isArray(report.filters.statusOptions) ? report.filters.statusOptions : []))
const hasChart = computed(() => Boolean(report.chart?.data && Array.isArray(report.chart.data.labels)))
const tableEmptyText = computed(() =>
  activeReport.value === "courselastvisit" ? t("No search results") : t("No results found"),
)
const legacyCourseChart = computed(() => ["courses", "tools", "coursebylanguage"].includes(activeReport.value))
const legacyFlatChart = computed(() => legacyCourseChart.value || Boolean(report.meta.legacyFlatChart))
const legacyChartsGridClass = computed(() =>
  Number(report.meta.legacyChartsColumns) === 3 ? "grid grid-cols-1 gap-4 lg:grid-cols-3" : "grid gap-4 lg:grid-cols-2",
)
const chartWrapperClass = computed(() => {
  if (activeReport.value === "courses") {
    return "mb-5 h-[520px] max-h-[70vh] w-full max-lg:h-[420px]"
  }
  if (activeReport.value === "tools") {
    return "mx-auto mb-5 h-[420px] max-w-[980px] max-md:h-[320px]"
  }
  if (activeReport.value === "coursebylanguage") {
    return "mx-auto mb-5 h-[520px] max-w-[1100px] max-lg:h-[420px] max-md:h-[320px]"
  }
  if (activeReport.value === "recentlogins") {
    return "mb-5 h-[320px] w-full"
  }
  if (activeReport.value === "subscription_by_day") {
    return "my-3 mb-5 h-[360px] max-h-[60vh] w-full max-md:h-[280px]"
  }
  if (activeReport.value === "new_user_registrations") {
    return route.query.month ? "h-[360px] w-full" : "h-[360px] w-full"
  }
  return "mx-auto h-[420px] max-w-5xl"
})
const hasTable = computed(() => Array.isArray(report.table?.columns) && report.table.columns.length > 0)
const courseSessionRows = computed(() => (Array.isArray(report.meta.courseSessions) ? report.meta.courseSessions : []))
const requiresDateRange = computed(() => Boolean(report.meta.requiresDateRange))
const usesDateRange = computed(() => dateRangeReports.has(activeReport.value))
const hasSessionDateRange = computed(() => isCompleteDateRange(filters.sessionRange))
const hasDateRange = computed(() => isCompleteDateRange(filters.dateRange))
const onlineCards = computed(() => (Array.isArray(report.meta.onlineCards) ? report.meta.onlineCards : []))
const testCards = computed(() => (Array.isArray(report.meta.testCards) ? report.meta.testCards : []))
const quarterlyCards = computed(() => (Array.isArray(report.meta.quarterlyCards) ? report.meta.quarterlyCards : []))
const duplicateGroups = computed(() => (Array.isArray(report.meta.duplicateGroups) ? report.meta.duplicateGroups : []))
const duplicateExtraFieldOptions = computed(() =>
  Array.isArray(report.filters.extraFieldOptions) ? report.filters.extraFieldOptions : [],
)
const duplicateColumns = computed(() =>
  Array.isArray(report.table.columns) ? report.table.columns.filter((column) => column.key !== "actions") : [],
)
const duplicateModeDescription = computed(() => {
  if (filters.duplicateMode === "email") {
    return t("This report only lists users that have the same e-mail address.")
  }
  if (filters.duplicateMode === "extra") {
    return t("This report only lists users that share the same value for the selected profile field.")
  }
  return t("This report only lists users that have the same firstname and lastname.")
})
const sessionDurationOptions = computed(() => {
  const values = Array.isArray(report.filters.sessionDurationOptions)
    ? report.filters.sessionDurationOptions
    : [0, 5, 15, 30, 60]
  return values.map((value) => ({
    label: Number(value) === 0 ? "" : String(value),
    value: Number(value),
  }))
})
const showFilters = computed(
  () =>
    ["tool_usage", "courselastvisit", "recentlogins", "session_by_date", "zombies", "duplicated_users"].includes(
      activeReport.value,
    ) || usesDateRange.value,
)
const showChartDataTables = computed(() => false)

const ChartDataTable = defineComponent({
  name: "AdminStatisticsChartDataTable",
  props: {
    chart: {
      type: Object,
      required: true,
    },
  },
  setup(props) {
    return () => {
      const labels = Array.isArray(props.chart?.data?.labels) ? props.chart.data.labels : []
      const dataset = Array.isArray(props.chart?.data?.datasets) ? props.chart.data.datasets[0] : null
      const values = Array.isArray(dataset?.data) ? dataset.data : []
      if (!labels.length || labels.length !== values.length) {
        return null
      }
      const total = values.reduce((sum, value) => sum + (Number(value) || 0), 0)
      return h(
        "div",
        { class: "mt-4 overflow-x-auto" },
        h("table", { class: "w-full text-sm" }, [
          h("thead", [
            h("tr", { class: "border-b border-gray-25 text-left text-gray-60" }, [
              h("th", { class: "px-2 py-2 font-semibold" }, t("Name")),
              h("th", { class: "px-2 py-2 font-semibold" }, "#"),
              h("th", { class: "px-2 py-2 font-semibold" }, "%"),
            ]),
          ]),
          h(
            "tbody",
            labels.map((label, index) => {
              const value = Number(values[index]) || 0
              const percentage = total > 0 ? `${((value / total) * 100).toFixed(2)} %` : "0 %"
              return h("tr", { class: "border-b border-gray-15" }, [
                h("td", { class: "px-2 py-2" }, String(label)),
                h("td", { class: "px-2 py-2" }, String(value)),
                h("td", { class: "px-2 py-2" }, percentage),
              ])
            }),
          ),
        ]),
      )
    }
  },
})

function sectionHasActive(group) {
  return group.items.some((item) => isActiveItem(item))
}

function isModernReport(name) {
  return modernReports.has(name)
}

function reportLabel(item) {
  if (item.report === "logins" && item.type) {
    const typeLabel = item.type.charAt(0).toUpperCase() + item.type.slice(1)
    return `${t("Logins")} (${t(typeLabel)})`
  }
  if (item.suffix) {
    return `${t(item.label)} (${t(item.suffix)})`
  }
  return t(item.label)
}

function isActiveItem(item) {
  if (activeReport.value !== item.report) {
    return false
  }
  if (!item.type) {
    return true
  }
  return String(route.query.type || "month") === item.type
}

function modernReportRoute(item) {
  return {
    name: "AdminStatistics",
    query: {
      report: item.report,
      ...(item.type ? { type: item.type } : {}),
    },
  }
}

function legacyReportUrl(item, sourceQuery = {}) {
  const params = new URLSearchParams()
  params.set("report", item.report)
  if (item.type) {
    params.set("type", item.type)
  }

  for (const [key, value] of Object.entries(sourceQuery)) {
    if (["report", "type"].includes(key) || value === undefined || value === null || value === "") {
      continue
    }
    if (Array.isArray(value)) {
      value.forEach((itemValue) => params.append(key, String(itemValue)))
    } else {
      params.set(key, String(value))
    }
  }

  return `/main/admin/statistics/index.php?${params.toString()}`
}

function routeItemForCurrentReport() {
  for (const group of reportGroups) {
    const item = group.items.find(
      (entry) =>
        entry.report === activeReport.value && (!entry.type || entry.type === String(route.query.type || "month")),
    )
    if (item) {
      return item
    }
  }

  return { report: activeReport.value, label: activeReport.value }
}

function queryParameters(pageOverride = null, rowsOverride = null) {
  const parameters = { report: activeReport.value }

  if (activeReport.value === "tool_usage" && filters.toolIds.length) {
    parameters.toolIds = filters.toolIds.join(",")
  }
  if (activeReport.value === "courselastvisit") {
    parameters.dateDiff = filters.dateDiff
    parameters.page = pageOverride || Number(route.query.page_nr || 1)
    parameters.itemsPerPage = rowsOverride || Number(route.query.per_page || 50)
    parameters.column = normalizeNonNegativeInt(route.query.column, 0)
    parameters.direction = [3, 4].includes(Number(route.query.direction)) ? Number(route.query.direction) : 4
  }
  if (activeReport.value === "courses_usage") {
    parameters.page =
      pageOverride || Number(route.query.page_nr || route.query.table_courses_usage_page_nr || 1)
    parameters.itemsPerPage =
      rowsOverride || Number(route.query.per_page || route.query.table_courses_usage_per_page || 20)
  }
  if (activeReport.value === "recentlogins") {
    parameters.sessionDuration = Number(filters.sessionDuration || 0)
  }
  if (activeReport.value === "logins") {
    parameters.type = String(route.query.type || "month")
  }
  if (activeReport.value === "session_by_date" && hasSessionDateRange.value) {
    parameters.rangeStart = formatDateForRequest(filters.sessionRange[0])
    parameters.rangeEnd = formatDateForRequest(filters.sessionRange[1])
    parameters.statusId = Number(filters.statusId || 0)
  }
  if (usesDateRange.value && hasDateRange.value) {
    parameters.rangeStart = formatDateForRequest(filters.dateRange[0])
    parameters.rangeEnd = formatDateForRequest(filters.dateRange[1])
  }
  if (activeReport.value === "users_online") {
    parameters.page = pageOverride || Number(route.query.page_nr || 1)
    parameters.itemsPerPage = rowsOverride || Number(route.query.per_page || 10)
  }
  if (activeReport.value === "users_active") {
    parameters.page = pageOverride || Number(route.query.page_nr || route.query.table_users_active_page_nr || 1)
    parameters.itemsPerPage =
      rowsOverride || Number(route.query.per_page || route.query.table_users_active_per_page || 10)
  }
  if (activeReport.value === "user_session") {
    parameters.sortOrder = String(route.query.sord || (userSessionSortOrder.value === -1 ? "desc" : "asc"))
  }
  if (activeReport.value === "new_user_registrations" && route.query.month) {
    parameters.month = String(route.query.month)
  }
  if (activeReport.value === "zombies") {
    parameters.ceiling = formatDateForRequest(
      filters.zombieCeiling instanceof Date ? filters.zombieCeiling : new Date(),
    )
    parameters.activeOnly = Boolean(filters.zombieActiveOnly)
    parameters.page = pageOverride || Number(route.query.page_nr || route.query.zombie_users_page_nr || 1)
    parameters.itemsPerPage = rowsOverride || Number(route.query.per_page || route.query.zombie_users_per_page || 50)
    parameters.sortField = String(route.query.sort_field || zombieSortField.value || "firstname")
    parameters.sortOrder = String(route.query.sort_order || (zombieSortOrder.value === 1 ? "ASC" : "DESC"))
  }
  if (activeReport.value === "duplicated_users") {
    parameters.dupMode = filters.duplicateMode
    if (filters.duplicateMode === "extra" && Number(filters.duplicateExtraFieldId) > 0) {
      parameters.extraFieldId = Number(filters.duplicateExtraFieldId)
    }
    const additionalFields = normalizeIdList(route.query.additional_profile_field)
    if (additionalFields.length) {
      parameters.additionalProfileFields = additionalFields.join(",")
    }
  }

  return parameters
}

function resetReportData() {
  report.report = ""
  report.title = ""
  report.description = ""
  report.chart = {}
  report.charts = []
  report.stats = []
  report.statsGroups = []
  report.table = {}
  report.filters = {}
  report.meta = {}
}

function applyResponse(data) {
  report.report = String(data?.report || activeReport.value)
  report.title = String(data?.title || "")
  report.description = String(data?.description || "")
  report.chart = data?.chart && typeof data.chart === "object" ? data.chart : {}
  report.charts = Array.isArray(data?.charts) ? data.charts : []
  report.stats = Array.isArray(data?.stats) ? data.stats : []
  report.statsGroups = Array.isArray(data?.statsGroups) ? data.statsGroups : []
  report.table = data?.table && typeof data.table === "object" ? data.table : {}
  report.filters = data?.filters && typeof data.filters === "object" ? data.filters : {}
  report.meta = data?.meta && typeof data.meta === "object" ? data.meta : {}

  if (activeReport.value === "tool_usage" && !filters.toolIds.length && Array.isArray(report.filters.selectedToolIds)) {
    filters.toolIds = [...report.filters.selectedToolIds]
  }
  if (activeReport.value === "courselastvisit") {
    filters.dateDiff = Number(report.filters.dateDiff || filters.dateDiff)
    const legacyColumn = Number(report.filters.column || 0)
    courseLastVisitSortField.value = ["courseId", "courseTitle", "lastAccess"][legacyColumn] || "courseId"
    courseLastVisitSortOrder.value = Number(report.filters.direction || 4) === 3 ? -1 : 1
  }
  if (activeReport.value === "recentlogins") {
    filters.sessionDuration = Number(report.filters.sessionDuration ?? filters.sessionDuration)
  }
  if (activeReport.value === "session_by_date") {
    filters.statusId = Number(report.filters.statusId || 0)
  }
  if (activeReport.value === "user_session") {
    userSessionSortField.value = "url"
    userSessionSortOrder.value = String(report.filters.sortOrder || "asc").toLowerCase() === "desc" ? -1 : 1
  }
  if (activeReport.value === "zombies") {
    filters.zombieCeiling = parseDateFromQuery(report.filters.ceiling) || filters.zombieCeiling
    filters.zombieActiveOnly = Boolean(report.filters.activeOnly)
    zombieSortField.value = String(report.filters.sortField || "firstname")
    zombieSortOrder.value = String(report.filters.sortOrder || "DESC") === "ASC" ? 1 : -1
  }
  if (activeReport.value === "duplicated_users") {
    filters.duplicateMode = ["name", "email", "extra"].includes(String(report.filters.dupMode))
      ? String(report.filters.dupMode)
      : "name"
    filters.duplicateExtraFieldId = Number(report.filters.extraFieldId || 0)
  }
  if (dateRangeReports.has(activeReport.value) && report.filters.rangeStart && report.filters.rangeEnd) {
    filters.dateRange = [
      parseDateFromQuery(report.filters.rangeStart),
      parseDateFromQuery(report.filters.rangeEnd),
    ].filter(Boolean)
  }
  if (Number(report.table.itemsPerPage || 0) > 0) {
    tableRows.value = Number(report.table.itemsPerPage)
  }
}

function initializeFiltersFromRoute() {
  filters.toolIds = normalizeIdList(route.query.tool_ids)
  filters.dateDiff = normalizePositiveInt(route.query.date_diff, 60)
  filters.statusId = normalizeNonNegativeInt(route.query.status_id, 0)
  filters.sessionDuration = normalizeAllowedInt(
    route.query.session_duration ?? route.query.sessionDuration,
    [0, 5, 15, 30, 60],
    0,
  )
  filters.zombieCeiling = parseDateFromQuery(route.query.ceiling) || new Date()
  filters.zombieActiveOnly = parseBooleanQuery(route.query.active_only ?? route.query.activeOnly)
  userSessionSortOrder.value = String(route.query.sord || "asc").toLowerCase() === "desc" ? -1 : 1
  filters.duplicateMode = ["name", "email", "extra"].includes(
    String(route.query.dup_mode || route.query.dupMode || "name"),
  )
    ? String(route.query.dup_mode || route.query.dupMode || "name")
    : "name"
  filters.duplicateExtraFieldId = normalizeNonNegativeInt(route.query.extra_field_id ?? route.query.extraFieldId, 0)
  const legacyZombieColumn = Number(route.query.zombie_users_column)
  const zombieColumnMap = {
    1: "officialCode",
    2: "firstname",
    3: "lastname",
    4: "username",
    5: "email",
    6: "profile",
    8: "registeredDate",
  }
  zombieSortField.value = String(route.query.sort_field || zombieColumnMap[legacyZombieColumn] || "firstname")
  const legacyZombieDirection = Number(route.query.zombie_users_direction)
  zombieSortOrder.value =
    String(route.query.sort_order || (legacyZombieDirection === 3 ? "DESC" : "ASC")).toUpperCase() === "ASC" ? 1 : -1

  const sessionStart = parseDateFromQuery(route.query.range_start)
  const sessionEnd = parseDateFromQuery(route.query.range_end)
  filters.sessionRange = sessionStart && sessionEnd ? [sessionStart, sessionEnd] : null

  const rangeStart = parseDateFromQuery(route.query.daterange_start ?? route.query.range_start)
  const rangeEnd = parseDateFromQuery(route.query.daterange_end ?? route.query.range_end)
  filters.dateRange = rangeStart && rangeEnd ? [rangeStart, rangeEnd] : null

  if (activeReport.value === "user_session" && !hasDateRange.value) {
    const today = new Date()
    filters.dateRange = [today, today]
  }
}

async function loadReport(pageOverride = null, rowsOverride = null) {
  if (!activeReport.value) {
    errorMessage.value = ""
    successMessage.value = ""
    resetReportData()
    return
  }

  if (activeReport.value === "activities") {
    const params = new URLSearchParams()
    for (const [key, value] of Object.entries(route.query)) {
      if (key === "report" || value === undefined || value === null || value === "") {
        continue
      }
      if (Array.isArray(value)) {
        value.forEach((itemValue) => params.append(key, String(itemValue)))
      } else {
        params.set(key, String(value))
      }
    }
    const queryString = params.toString()
    window.location.assign(`/main/admin/activities_audit.php${queryString ? `?${queryString}` : ""}`)
    return
  }

  if (!isModernReport(activeReport.value)) {
    resetReportData()
    return
  }

  loading.value = true
  errorMessage.value = ""
  successMessage.value = ""
  resetReportData()

  try {
    const data = await adminStatisticsService.getReport(queryParameters(pageOverride, rowsOverride))
    applyResponse(data)
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.message || t("Unable to load the report")
  } finally {
    loading.value = false
  }
}

async function replaceQuery(query) {
  await router.replace({ name: "AdminStatistics", query })
}

async function applyToolUsageFilter() {
  await replaceQuery({ report: "tool_usage", tool_ids: filters.toolIds.join(",") })
}

async function applyLastVisitFilter() {
  await replaceQuery({
    report: "courselastvisit",
    date_diff: filters.dateDiff,
  })
}

async function applyRecentLoginsFilter() {
  await replaceQuery({ report: "recentlogins", session_duration: Number(filters.sessionDuration || 0) })
}

async function applySessionFilter() {
  if (!hasSessionDateRange.value) {
    return
  }
  await replaceQuery({
    report: "session_by_date",
    range_start: formatDateForRequest(filters.sessionRange[0]),
    range_end: formatDateForRequest(filters.sessionRange[1]),
    ...(Number(filters.statusId || 0) > 0 ? { status_id: Number(filters.statusId) } : {}),
  })
}

function setUserSessionLastWeek() {
  const today = new Date()
  const localeName = String(locale.value || "en_US").replace("_", "-")
  let firstDay = 1

  try {
    const localeInfo = new Intl.Locale(localeName)
    const weekInfo = localeInfo.weekInfo || localeInfo.getWeekInfo?.()
    if (weekInfo?.firstDay) {
      firstDay = Number(weekInfo.firstDay) % 7
    }
  } catch {
    firstDay = 1
  }

  const currentDay = today.getDay()
  const daysFromWeekStart = (currentDay - firstDay + 7) % 7
  const currentWeekStart = new Date(today.getFullYear(), today.getMonth(), today.getDate() - daysFromWeekStart)
  const lastWeekStart = new Date(
    currentWeekStart.getFullYear(),
    currentWeekStart.getMonth(),
    currentWeekStart.getDate() - 7,
  )
  const lastWeekEnd = new Date(
    currentWeekStart.getFullYear(),
    currentWeekStart.getMonth(),
    currentWeekStart.getDate() - 1,
  )
  filters.dateRange = [lastWeekStart, lastWeekEnd]
}

async function applyDateRangeFilter() {
  if (!hasDateRange.value) {
    return
  }
  const start = formatDateForRequest(filters.dateRange[0])
  const end = formatDateForRequest(filters.dateRange[1])
  const rangeKeys =
    activeReport.value === "user_session" ? ["range_start", "range_end"] : ["daterange_start", "daterange_end"]
  await replaceQuery({
    report: activeReport.value,
    [rangeKeys[0]]: start,
    [rangeKeys[1]]: end,
    ...(activeReport.value === "users_active" ? { page_nr: 1, per_page: tableRows.value || 10 } : {}),
  })
}

async function handleRegistrationChartSelect(event) {
  if (activeReport.value !== "new_user_registrations" || !report.meta.registrationMonthly) {
    return
  }
  const index = Number(event?.element?.index)
  const labels = Array.isArray(report.charts?.[0]?.data?.labels) ? report.charts[0].data.labels : []
  const month = Number.isInteger(index) ? String(labels[index] || "") : ""
  if (!/^\d{4}-\d{2}$/u.test(month)) {
    return
  }
  await replaceQuery({ ...route.query, report: "new_user_registrations", month })
}


async function clearRegistrationDrilldown() {
  const query = { ...route.query }
  delete query.month
  await replaceQuery(query)
}

async function applyZombieFilter() {
  await replaceQuery({
    report: "zombies",
    ceiling: formatDateForRequest(filters.zombieCeiling instanceof Date ? filters.zombieCeiling : new Date()),
    active_only: filters.zombieActiveOnly ? "true" : "false",
    page_nr: 1,
    per_page: tableRows.value || 50,
    sort_field: zombieSortField.value || "firstname",
    sort_order: zombieSortOrder.value === 1 ? "ASC" : "DESC",
  })
}

async function setDuplicateMode(mode) {
  const normalized = ["name", "email", "extra"].includes(mode) ? mode : "name"
  const query = { ...route.query, report: "duplicated_users", dup_mode: normalized }
  delete query.action
  delete query.user_id
  delete query.unify_user_id
  if (normalized !== "extra") {
    delete query.extra_field_id
  }
  await replaceQuery(query)
}

async function applyDuplicateFilter() {
  const query = { ...route.query, report: "duplicated_users", dup_mode: filters.duplicateMode }
  if (filters.duplicateMode === "extra" && Number(filters.duplicateExtraFieldId) > 0) {
    query.extra_field_id = Number(filters.duplicateExtraFieldId)
  } else {
    delete query.extra_field_id
  }
  await replaceQuery(query)
}

async function runMaintenanceAction(payload) {
  maintenanceLoading.value = true
  errorMessage.value = ""
  successMessage.value = ""
  try {
    const result = await adminStatisticsService.runAction({
      ...payload,
      csrfToken: String(report.meta.csrfToken || ""),
    })
    successMessage.value = String(result?.message || "")
    selectedZombieUsers.value = []
    await loadReport()
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    maintenanceLoading.value = false
  }
}

function confirmZombieAction(action) {
  const ids = selectedZombieUsers.value.map((item) => Number(item.id)).filter((id) => Number.isInteger(id) && id > 0)
  if (!ids.length) {
    return
  }
  const messages = {
    activate: t("Activate"),
    deactivate: t("Deactivate"),
    delete: t("Delete"),
  }
  requireConfirmation({
    message: `${messages[action] || action}: ${ids.length}`,
    accept: () =>
      runMaintenanceAction({
        report: "zombies",
        action,
        ids,
        ceiling: formatDateForRequest(filters.zombieCeiling instanceof Date ? filters.zombieCeiling : new Date()),
        activeOnly: Boolean(filters.zombieActiveOnly),
      }),
  })
}

function confirmDuplicateStatus(item) {
  const action = Number(item.active) === 1 ? "deactivate" : "activate"
  requireConfirmation({
    message: action === "deactivate" ? t("Deactivate this user?") : t("Enable this user?"),
    accept: () =>
      runMaintenanceAction({
        report: "duplicated_users",
        action,
        userId: Number(item.id),
        dupMode: filters.duplicateMode,
        extraFieldId: Number(filters.duplicateExtraFieldId || 0),
      }),
  })
}

function confirmDuplicateUnify(item) {
  requireConfirmation({
    message: [
      t("Unify this duplicate group into user #%s?", [String(Number(item.id))]),
      t("This will merge all other accounts in the same group into this account."),
      t("Merged accounts will be permanently deleted and will disappear from this report."),
      t("This action cannot be undone."),
    ].join(" "),
    accept: () =>
      runMaintenanceAction({
        report: "duplicated_users",
        action: "unify",
        targetUserId: Number(item.id),
        dupMode: filters.duplicateMode,
        extraFieldId: Number(filters.duplicateExtraFieldId || 0),
      }),
  })
}

const zombieSortableColumns = ["officialCode", "firstname", "lastname", "username", "email", "profile", "registeredDate"]

function zombieSortFieldForColumn(columnKey) {
  return columnKey === "profile" ? "status" : columnKey
}

async function handleZombieSort(event) {
  const allowed = ["officialCode", "firstname", "lastname", "username", "email", "status", "registeredDate"]
  const requestedSortField = String(event.sortField || "firstname")
  const sortField = allowed.includes(requestedSortField) ? requestedSortField : "firstname"
  const sortOrder = Number(event.sortOrder || -1)
  zombieSortField.value = sortField
  zombieSortOrder.value = sortOrder
  await replaceQuery({
    ...route.query,
    report: "zombies",
    page_nr: 1,
    sort_field: sortField,
    sort_order: sortOrder === 1 ? "ASC" : "DESC",
  })
}

async function handleReportSort(event) {
  if (activeReport.value === "user_session") {
    if (String(event.sortField || "url") !== "url") {
      return
    }
    const sortOrder = Number(event.sortOrder || 1) === -1 ? "desc" : "asc"
    userSessionSortField.value = "url"
    userSessionSortOrder.value = sortOrder === "desc" ? -1 : 1
    await replaceQuery({ ...route.query, report: "user_session", sord: sortOrder })
    return
  }

  if (activeReport.value !== "courselastvisit") {
    return
  }

  const columns = { courseId: 0, courseTitle: 1, lastAccess: 2 }
  const sortField = String(event.sortField || "courseId")
  const column = Object.prototype.hasOwnProperty.call(columns, sortField) ? columns[sortField] : 0
  const direction = Number(event.sortOrder || 1) === -1 ? 3 : 4

  await replaceQuery({
    ...route.query,
    report: "courselastvisit",
    page_nr: 1,
    column,
    direction,
  })
}

async function handlePage(event) {
  if (!report.table.lazy) {
    return
  }
  const page = Number(event.page || 0) + 1
  const defaultRows = ["zombies", "courselastvisit"].includes(activeReport.value) ? 50 : 20
  const rows = Number(event.rows || tableRows.value || defaultRows)
  await replaceQuery({ ...route.query, page_nr: page, per_page: rows })
}

async function downloadCurrentReport(format) {
  exporting.value = true
  errorMessage.value = ""
  try {
    const params = queryParameters()
    delete params.page
    delete params.itemsPerPage
    const response =
      activeReport.value === "session_by_date"
        ? await adminStatisticsService.downloadSessionByDate(params, format)
        : await adminStatisticsService.downloadReport(activeReport.value, format, params)
    const blob = response.data instanceof Blob ? response.data : new Blob([response.data])
    const url = URL.createObjectURL(blob)
    const link = document.createElement("a")
    link.href = url
    link.download = exportFilename(response, `admin-statistics-${activeReport.value}.${format}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.message || t("Unable to export the report")
  } finally {
    exporting.value = false
  }
}

async function toggleQuarterlySection(section) {
  if (quarterlySections[section] && !quarterlyLoading[section]) {
    quarterlyVisible[section] = !quarterlyVisible[section]
    return
  }
  quarterlyVisible[section] = true
  await loadQuarterlySection(section)
}

async function loadQuarterlySection(section, force = false) {
  if (quarterlyLoading[section] || (quarterlySections[section] && !force)) {
    return
  }
  quarterlyLoading[section] = true
  quarterlyErrors[section] = ""
  quarterlyVisible[section] = true
  try {
    const data = await adminStatisticsService.getReport({ report: "quarterly_report", section })
    quarterlySections[section] = data?.meta?.quarterlySectionData || {}
  } catch (error) {
    quarterlyErrors[section] = error?.response?.data?.detail || error?.message || t("Unable to load the report")
  } finally {
    quarterlyLoading[section] = false
  }
}

async function loadAllQuarterlySections() {
  if (loadingAllQuarterly.value) {
    return
  }
  loadingAllQuarterly.value = true
  try {
    for (const card of quarterlyCards.value) {
      await loadQuarterlySection(card.id, true)
    }
  } finally {
    loadingAllQuarterly.value = false
  }
}

function stopUsersOnlineRefresh() {
  if (usersOnlineRefreshTimer !== null) {
    window.clearInterval(usersOnlineRefreshTimer)
    usersOnlineRefreshTimer = null
  }
}

async function refreshUsersOnlineReport() {
  if (activeReport.value !== "users_online" || loading.value || usersOnlineRefreshInFlight) {
    return
  }

  usersOnlineRefreshInFlight = true
  try {
    const data = await adminStatisticsService.getReport(queryParameters())
    if (activeReport.value === "users_online") {
      applyResponse(data)
    }
  } catch {
    // Keep the last successful data visible when a background refresh fails.
  } finally {
    usersOnlineRefreshInFlight = false
  }
}

function startUsersOnlineRefresh() {
  stopUsersOnlineRefresh()
  if (activeReport.value !== "users_online") {
    return
  }

  usersOnlineRefreshTimer = window.setInterval(refreshUsersOnlineReport, ONLINE_REFRESH_INTERVAL_MS)
}

function exportFilename(response, fallback) {
  const disposition = String(response?.headers?.["content-disposition"] || "")
  const match = disposition.match(/filename\*?=(?:UTF-8'')?([^;]+)/iu)
  return match?.[1] ? decodeURIComponent(match[1].replace(/"/gu, "")) : fallback
}

function chartOptions(title) {
  const legendPosition = ["courses", "tools"].includes(activeReport.value) ? "left" : "top"

  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: legendPosition },
      title: { display: Boolean(title), text: title || "" },
    },
  }
}

function normalizeIdList(value) {
  const values = Array.isArray(value) ? value : String(value || "").split(/[;,]/u)
  return [...new Set(values.map((item) => Number(item)).filter((item) => Number.isInteger(item) && item > 0))]
}

function normalizePositiveInt(value, fallback) {
  const number = Number(value)
  return Number.isInteger(number) && number > 0 ? number : fallback
}

function normalizeNonNegativeInt(value, fallback) {
  const number = Number(value)
  return Number.isInteger(number) && number >= 0 ? number : fallback
}

function normalizeAllowedInt(value, allowed, fallback) {
  const number = Number(value)
  return allowed.includes(number) ? number : fallback
}

function parseBooleanQuery(value) {
  return ["1", "true", "yes", "on"].includes(String(value || "").toLowerCase())
}

function parseDateFromQuery(value) {
  const match = String(value || "").match(/^(\d{4})-(\d{2})-(\d{2})$/u)
  if (!match) {
    return null
  }
  const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
  return Number.isNaN(date.getTime()) ? null : date
}

function isCompleteDateRange(range) {
  return Array.isArray(range) && range.length === 2 && range[0] instanceof Date && range[1] instanceof Date
}

function formatDateForRequest(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, "0")
  const day = String(date.getDate()).padStart(2, "0")
  return `${year}-${month}-${day}`
}

function legacyStatsTotal() {
  return report.stats.reduce((sum, item) => sum + (Number(item.value) || 0), 0)
}

function legacyStatsMax() {
  return report.stats.reduce((maximum, item) => Math.max(maximum, Number(item.value) || 0), 0)
}

function legacyPercentage(value) {
  const total = legacyStatsTotal()
  const percentage = total > 0 ? (100 * (Number(value) || 0)) / total : 0
  return percentage.toFixed(1).replace(".", ",")
}

function legacyBarPercent(value) {
  const maximum = legacyStatsMax()
  const percentage = maximum > 0 ? (100 * (Number(value) || 0)) / maximum : 0
  return Math.max(0, Math.min(100, percentage))
}

function legacyInteger(value) {
  const number = Math.round(Number(value) || 0)
  return new Intl.NumberFormat("de-DE", { maximumFractionDigits: 0 }).format(number)
}

function legacyGroupTotal(items) {
  return (Array.isArray(items) ? items : []).reduce((sum, item) => sum + (Number(item.value) || 0), 0)
}

function legacyGroupMax(items) {
  return (Array.isArray(items) ? items : []).reduce((maximum, item) => Math.max(maximum, Number(item.value) || 0), 0)
}

function legacyGroupPercentage(items, value) {
  const total = legacyGroupTotal(items)
  const percentage = total > 0 ? (100 * (Number(value) || 0)) / total : 0
  return percentage.toFixed(1).replace(".", ",")
}

function legacyGroupBarPercent(items, value) {
  const maximum = legacyGroupMax(items)
  const percentage = maximum > 0 ? (100 * (Number(value) || 0)) / maximum : 0
  return Math.max(0, Math.min(100, percentage))
}

function onlineCardClasses(minutes) {
  const classes = {
    3: "rounded-xl border border-danger/20 bg-danger/10 p-4 shadow-sm",
    5: "rounded-xl border border-warning/20 bg-warning/10 p-4 shadow-sm",
    30: "rounded-xl border border-info/20 bg-info/10 p-4 shadow-sm",
    120: "rounded-xl border border-success/20 bg-success/10 p-4 shadow-sm",
  }
  return classes[Number(minutes)] || "rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
}

function onlineIconClasses(minutes) {
  const classes = {
    3: "flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-danger/20 text-danger",
    5: "flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-warning/20 text-warning",
    30: "flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-info/20 text-info",
    120: "flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-success/20 text-success",
  }
  return classes[Number(minutes)] || "flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-20"
}

function onlineCardIcon(minutes) {
  if (Number(minutes) === 3) {
    return "rocket-launch"
  }
  if (Number(minutes) === 5) {
    return "alert"
  }
  if (Number(minutes) === 30) {
    return "information"
  }
  return "check"
}

function formatLegacyShortDate(value) {
  if (!value) {
    return ""
  }
  const normalizedValue =
    typeof value === "string" && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/u.test(value) ? value.replace(" ", "T") : value
  const date = new Date(normalizedValue)
  if (Number.isNaN(date.getTime())) {
    return String(value)
  }
  return new Intl.DateTimeFormat(String(locale.value || "en_US").replace("_", "-"), {
    day: "numeric",
    month: "short",
    year: "2-digit",
  }).format(date)
}

function formatDateTime(value) {
  if (!value) {
    return "-"
  }
  const normalizedValue =
    typeof value === "string" && /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/u.test(value) ? value.replace(" ", "T") : value
  const date = new Date(normalizedValue)
  if (Number.isNaN(date.getTime())) {
    return String(value)
  }
  return new Intl.DateTimeFormat(String(locale.value || "en_US").replace("_", "-"), {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date)
}

function isDateColumn(key) {
  return ["lastAccess", "lastUpdated", "lastActivity", "startDate", "endDate"].includes(key)
}

function formatNumber(value) {
  const number = Number(value)
  if (!Number.isFinite(number)) {
    return value
  }
  return new Intl.NumberFormat(String(locale.value || "en_US").replace("_", "-"), {
    maximumFractionDigits: 2,
  }).format(number)
}

function stripHtml(value) {
  return String(value || "")
    .replace(/<[^>]*>/gu, " ")
    .replace(/\s+/gu, " ")
    .trim()
}

watch(
  () => route.query,
  async () => {
    stopUsersOnlineRefresh()
    initializeFiltersFromRoute()
    await loadReport()
    startUsersOnlineRefresh()
  },
  { deep: true, immediate: true },
)

onBeforeUnmount(stopUsersOnlineRefresh)
</script>

<style scoped>
.stats-menu-grid {
  --stats-cols: 5;
  display: grid;
  gap: 1rem;
  align-items: start;
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 640px) {
  .stats-menu-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 768px) {
  .stats-menu-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  .stats-menu-grid {
    grid-template-columns: repeat(var(--stats-cols), minmax(240px, 1fr));
  }
}
</style>
