<template>
  <main class="space-y-5 pb-8">
    <Message
      v-if="errorMessage"
      severity="error"
      :closable="false"
    >
      {{ errorMessage }}
    </Message>

    <GlobalReportingToolbar
      :show-print="true"
      :show-csv="hasCurrentReport && report.total > 0 && report.meta.canExportCsv"
      :csv-loading="exportFormat === 'csv'"
      @print="printReport"
      @export-csv="downloadExport('csv')"
    />

    <header class="border-b border-gray-25 pb-3">
      <h1 class="text-2xl font-semibold text-gray-90">{{ t("Admin view") }}</h1>
    </header>

    <section class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm md:p-5">
      <h2 class="mb-4 text-lg font-semibold text-gray-90">{{ t("Available reports") }}</h2>

      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <router-link
          v-for="adminReport in adminReports"
          :key="adminReport.section"
          :to="adminReportRoute(adminReport.section)"
          :class="adminReportClass(adminReport.section)"
        >
          <BaseIcon
            :icon="adminReport.icon"
            size="normal"
            zoom-trigger="group"
          />
          <span>
            {{ t(adminReport.label) }}
            <span
              v-if="adminReport.badge"
              class="text-xs font-normal text-gray-50"
            >
              ({{ t(adminReport.badge) }})
            </span>
          </span>
        </router-link>
      </div>
    </section>

    <p
      v-if="hasCurrentReport"
      class="text-sm text-gray-60"
    >
      {{ t("Current report") }}:
      <span class="font-semibold text-gray-90">{{ t(report.title) }}</span>
    </p>

    <section
      v-if="hasCurrentReport && showFilters"
      class="no-print rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
    >
      <div class="grid gap-4 lg:grid-cols-12 lg:items-end">
        <div
          v-if="report.meta.supportsKeyword"
          class="lg:col-span-6"
        >
          <BaseInputText
            id="global-reporting-admin-keyword"
            v-model="filters.keyword"
            :label="t('Keyword')"
            name="keyword"
            @keyup.enter="applyFilters"
          />
        </div>

        <div
          v-if="report.meta.supportsCourse"
          class="lg:col-span-4"
        >
          <BaseSelect
            id="global-reporting-admin-course"
            v-model="filters.courseId"
            :label="t('Course')"
            :options="courseOptions"
            option-value="id"
            allow-clear
            name="courseId"
            @change="handleCourseChange"
          />
        </div>

        <div
          v-if="report.meta.supportsSession"
          class="lg:col-span-4"
        >
          <BaseSelect
            id="global-reporting-admin-session"
            v-model="filters.sessionId"
            :label="t('Session')"
            :options="sessionOptions"
            option-value="id"
            allow-clear
            name="sessionId"
          />
        </div>

        <div
          v-if="report.meta.supportsProfile"
          class="lg:col-span-4"
        >
          <BaseSelect
            id="global-reporting-admin-profile"
            v-model="filters.status"
            :label="t('Profile')"
            :options="profileOptions"
            option-value="id"
            allow-clear
            name="profile"
            @change="handleProfileChange"
          />
        </div>

        <div
          v-if="report.meta.supportsUser"
          class="lg:col-span-4"
        >
          <BaseSelect
            id="global-reporting-admin-user"
            v-model="filters.userId"
            :label="t('User')"
            :options="userOptions"
            option-value="id"
            allow-clear
            name="userId"
          />
        </div>

        <div
          v-if="report.meta.supportsExercise"
          class="lg:col-span-4"
        >
          <BaseSelect
            id="global-reporting-admin-exercise"
            v-model="filters.exerciseId"
            :label="t('Tests')"
            :options="exerciseOptions"
            option-value="id"
            allow-clear
            name="exerciseId"
          />
        </div>

        <div
          v-if="report.meta.supportsStartDate"
          class="lg:col-span-4"
        >
          <BaseCalendar
            id="global-reporting-admin-start-date-only"
            v-model="startDateValue"
            :label="t('Start date')"
            name="startDate"
          />
        </div>

        <div
          v-if="report.meta.supportsLanguage"
          class="lg:col-span-4"
        >
          <BaseSelect
            id="global-reporting-admin-language"
            v-model="filters.language"
            :label="t('Language')"
            :options="languageOptions"
            allow-clear
            name="language"
          />
        </div>

        <div
          v-if="report.meta.supportsDateRange"
          class="lg:col-span-3"
        >
          <BaseCalendar
            id="global-reporting-admin-start-date"
            v-model="startDateValue"
            :label="t('Start date')"
            name="startDate"
          />
        </div>

        <div
          v-if="report.meta.supportsDateRange"
          class="lg:col-span-3"
        >
          <BaseCalendar
            id="global-reporting-admin-end-date"
            v-model="endDateValue"
            :label="t('End date')"
            name="endDate"
          />
        </div>

        <div class="flex flex-wrap items-center gap-2 lg:col-span-12 lg:justify-end">
          <BaseButton
            :label="t(report.meta.submitLabel || 'Search')"
            icon="search"
            type="primary"
            @click="applyFilters"
          />
          <BaseButton
            v-if="report.meta.supportsReset !== false"
            :label="t('Reset')"
            icon="refresh"
            type="plain"
            @click="resetFilters"
          />
          <BaseButton
            v-if="report.meta.canExportXlsx && report.total > 0"
            :label="t('Export to XLS')"
            icon="file-excel"
            only-icon
            type="primary-alternative"
            :is-loading="exportFormat === 'xlsx'"
            @click="downloadExport('xlsx')"
          />
        </div>
      </div>
    </section>

    <section
      v-if="hasCurrentReport && report.meta.renderMode === 'course-cards'"
      class="space-y-4"
    >
      <header class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-90">{{ t(report.title) }}</h2>
        <span class="text-sm text-gray-50">{{ report.total }} {{ t("Results") }}</span>
      </header>

      <div
        v-if="!loading && report.items.length === 0"
        class="rounded-xl border border-gray-25 bg-white p-8 text-center text-gray-50 shadow-sm"
      >
        {{ t("No results found") }}
      </div>

      <article
        v-for="course in report.items"
        :key="course.id"
        class="rounded-2xl border border-gray-25 bg-white p-4 shadow-sm md:p-6"
      >
        <div class="grid gap-6 md:grid-cols-12 md:items-start">
          <div class="flex flex-col gap-3 md:col-span-3">
            <div
              class="flex h-32 w-full items-center justify-center overflow-hidden rounded-xl border border-gray-25 bg-gray-10"
            >
              <img
                v-if="course.illustrationUrl"
                :src="course.illustrationUrl"
                :alt="course.title"
                class="h-full w-full object-cover"
              />
              <BaseIcon
                v-else
                icon="courses"
                size="big"
                class="text-gray-40"
              />
            </div>

            <div class="space-y-1">
              <h3 class="font-semibold text-gray-90">{{ course.title }}</h3>
              <p
                v-if="course.code"
                class="text-xs text-gray-50"
              >
                <span class="font-medium text-gray-70">{{ t("Course code") }}:</span>
                {{ course.code }}
              </p>
            </div>
          </div>

          <div class="space-y-4 md:col-span-5">
            <div class="grid gap-4 sm:grid-cols-2">
              <div class="rounded-xl border border-gray-25 bg-gray-10 px-3 py-3">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-50">
                  {{ t("Time spent in the course") }}
                </div>
                <div class="mt-2 flex items-center gap-2 font-semibold text-gray-90">
                  <BaseIcon
                    icon="tracking"
                    size="small"
                  />
                  {{ formatDuration(course.timeSeconds) }}
                </div>
              </div>

              <div class="rounded-xl border border-gray-25 bg-gray-10 px-3 py-3">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-50">
                  {{ t("Total score obtained for tests") }}
                </div>
                <div class="mt-2 font-semibold text-gray-90">{{ formatTestScore(course) }}</div>
              </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div class="flex flex-col items-center gap-2">
                <div class="relative h-24 w-24">
                  <svg
                    class="h-24 w-24 -rotate-90"
                    viewBox="0 0 120 120"
                    role="img"
                    :aria-label="`${t('Progress')}: ${formatPercent(course.progress)}`"
                  >
                    <circle
                      cx="60"
                      cy="60"
                      r="48"
                      fill="none"
                      stroke="#f2f2f2"
                      stroke-width="10"
                    />
                    <circle
                      cx="60"
                      cy="60"
                      r="48"
                      fill="none"
                      stroke="#30a5ff"
                      stroke-width="10"
                      stroke-linecap="round"
                      :stroke-dasharray="ringDash(course.progress)"
                    />
                  </svg>
                  <span class="absolute inset-0 flex items-center justify-center text-lg font-semibold text-gray-90">
                    {{ formatPercent(course.progress) }}
                  </span>
                </div>
                <span class="text-center text-xs text-gray-50">{{ t("Progress") }}</span>
              </div>

              <div class="flex flex-col items-center gap-2">
                <div class="relative h-24 w-24">
                  <svg
                    class="h-24 w-24 -rotate-90"
                    viewBox="0 0 120 120"
                    role="img"
                    :aria-label="courseScoreAriaLabel(course)"
                  >
                    <circle
                      cx="60"
                      cy="60"
                      r="48"
                      fill="none"
                      stroke="#f2f2f2"
                      stroke-width="10"
                    />
                    <circle
                      cx="60"
                      cy="60"
                      r="48"
                      fill="none"
                      stroke="#ffb53e"
                      stroke-width="10"
                      stroke-linecap="round"
                      :stroke-dasharray="ringDash(course.averageLearningPathScore)"
                    />
                  </svg>
                  <span class="absolute inset-0 flex items-center justify-center text-lg font-semibold text-gray-90">
                    {{ formatNullablePercent(course.averageLearningPathScore) }}
                  </span>
                </div>
                <span class="text-center text-xs text-gray-50">{{ t("Average score in learning paths") }}</span>
              </div>
            </div>
          </div>

          <dl class="space-y-3 text-sm md:col-span-4">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-gray-50">{{ t("Total number of messages") }}</dt>
              <dd class="flex items-center gap-2 font-medium text-gray-90">
                <BaseIcon
                  icon="list"
                  size="small"
                />
                {{ course.messages }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-gray-50">{{ t("Total number of assignments") }}</dt>
              <dd class="flex items-center gap-2 font-medium text-gray-90">
                <BaseIcon
                  icon="edit"
                  size="small"
                />
                {{ course.assignments }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-gray-50">{{ t("Number of tests answered") }}</dt>
              <dd class="flex items-center gap-2 font-medium text-gray-90">
                <BaseIcon
                  icon="file-text"
                  size="small"
                />
                {{ course.questionsAnswered }}
              </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-gray-50">{{ t("Latest login") }}</dt>
              <dd class="flex items-center gap-2 text-right font-medium text-gray-90">
                <BaseIcon
                  icon="agenda-event"
                  size="small"
                />
                {{ formatDateTime(course.lastAccess) }}
              </dd>
            </div>
          </dl>
        </div>
      </article>

      <div
        v-if="report.total > filters.itemsPerPage"
        class="rounded-xl border border-gray-25 bg-white p-3 shadow-sm"
      >
        <Paginator
          :first="(filters.page - 1) * filters.itemsPerPage"
          :rows="filters.itemsPerPage"
          :rows-per-page-options="[10, 20, 50, 100]"
          :total-records="report.total"
          template="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
          current-page-report-template="{first} - {last} / {totalRecords}"
          @page="onCardPage"
        />
      </div>
    </section>

    <section
      v-else-if="hasCurrentReport && report.meta.renderMode === 'user-cards'"
      class="space-y-4"
    >
      <div class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-70">{{ t("Legend") }}</h2>
        <div class="grid gap-2 text-sm text-gray-70 sm:grid-cols-2 lg:grid-cols-4">
          <span>{{ t("Progress") }}</span>
          <span>{{ t("Average score in learning paths") }}</span>
          <span>{{ t("Total number of messages") }}</span>
          <span>{{ t("Total number of assignments") }}</span>
          <span>{{ t("Total score obtained for tests") }}</span>
          <span>{{ t("Number of tests answered") }}</span>
          <span>{{ t("Latest login") }}</span>
        </div>
      </div>

      <header class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-90">{{ t(report.title) }}</h2>
        <span class="text-sm text-gray-50">{{ report.total }} {{ t("Results") }}</span>
      </header>

      <div
        v-if="!loading && report.items.length === 0"
        class="rounded-xl border border-gray-25 bg-white p-8 text-center text-gray-50 shadow-sm"
      >
        {{ t("No results found") }}
      </div>

      <article
        v-for="user in report.items"
        :key="user.id"
        class="rounded-2xl border border-gray-25 bg-white p-4 shadow-sm md:p-5"
      >
        <header class="flex items-center gap-4">
          <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-15">
            <img
              v-if="user.avatarUrl"
              :src="user.avatarUrl"
              :alt="user.fullName"
              class="h-full w-full object-cover"
            />
            <BaseIcon
              v-else
              icon="account"
              size="big"
              class="text-gray-40"
            />
          </div>
          <div>
            <h3 class="font-semibold text-gray-90">{{ user.fullName }}</h3>
            <p class="text-sm text-gray-50">@{{ user.username }}</p>
          </div>
        </header>

        <div
          v-if="!Array.isArray(user.courses) || user.courses.length === 0"
          class="mt-4 rounded-xl border border-gray-25 bg-white px-4 py-3 text-sm text-gray-50"
        >
          {{ t("No courses") }}
        </div>

        <div
          v-else
          class="mt-4 space-y-3"
        >
          <section
            v-for="course in user.courses"
            :key="`${user.id}-${course.id}`"
            class="rounded-xl bg-gray-10 p-4"
          >
            <div class="mb-3">
              <h4 class="font-semibold text-gray-90">{{ course.title }}</h4>
              <p class="text-xs text-gray-50">{{ t("Course code") }}: {{ course.code }}</p>
            </div>

            <div class="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-3">
              <AdminMetric
                :label="t('Time spent in the course')"
                :value="formatDuration(course.timeSeconds)"
                icon="tracking"
              />
              <AdminMetric
                :label="t('Progress')"
                :value="formatPercent(course.progress)"
                icon="graph"
              />
              <AdminMetric
                :label="t('Average score in learning paths')"
                :value="formatNullablePercent(course.averageLearningPathScore)"
                icon="star"
              />
              <AdminMetric
                :label="t('Total number of messages')"
                :value="String(course.messages)"
                icon="list"
              />
              <AdminMetric
                :label="t('Total number of assignments')"
                :value="String(course.assignments)"
                icon="edit"
              />
              <AdminMetric
                :label="t('Number of tests answered')"
                :value="String(course.testsAnswered)"
                icon="file-text"
              />
              <AdminMetric
                :label="t('Total score obtained for tests')"
                :value="formatPercent(course.testScore)"
                icon="graph"
              />
              <div class="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2">
                <span class="flex items-center gap-2 text-gray-60">
                  <BaseIcon
                    icon="agenda-event"
                    size="small"
                  />
                  {{ t("Latest login") }}
                </span>
                <span
                  class="font-medium"
                  :class="dateStatusClass(course.lastAccess)"
                >
                  {{ formatDateTime(course.lastAccess, true) }}
                </span>
              </div>
            </div>
          </section>
        </div>
      </article>

      <div
        v-if="report.total > filters.itemsPerPage"
        class="rounded-xl border border-gray-25 bg-white p-3 shadow-sm"
      >
        <Paginator
          :first="(filters.page - 1) * filters.itemsPerPage"
          :rows="filters.itemsPerPage"
          :rows-per-page-options="[10, 20, 50, 100]"
          :total-records="report.total"
          template="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
          current-page-report-template="{first} - {last} / {totalRecords}"
          @page="onCardPage"
        />
      </div>
    </section>

    <section
      v-else-if="hasCurrentReport && report.meta.renderMode === 'student-boss-cards'"
      class="space-y-4"
    >
      <header class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-90">{{ t(report.title) }}</h2>
        <span class="text-sm text-gray-50">{{ report.total }} {{ t("Results") }}</span>
      </header>

      <div
        v-if="!loading && report.items.length === 0"
        class="rounded-xl border border-gray-25 bg-white p-8 text-center text-gray-50 shadow-sm"
      >
        {{ t("No results found") }}
      </div>

      <article
        v-for="boss in report.items"
        :key="boss.id"
        class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm md:p-5"
      >
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-25 pb-3">
          <div>
            <h3 class="text-lg font-semibold text-gray-90">{{ boss.fullName }}</h3>
            <p class="mt-1 text-sm text-gray-50">
              {{ boss.username }}
              <span v-if="boss.locale"> · {{ formatLanguage(boss.locale) }}</span>
            </p>
          </div>

          <BaseButton
            :label="t('Add learner')"
            icon="account-multiple-plus"
            type="success"
            :to-url="boss.addLearnerUrl"
          />
        </header>

        <div class="mt-4">
          <h4 class="mb-3 text-sm font-semibold text-gray-90">{{ t("Learners") }}</h4>

          <div
            v-if="!Array.isArray(boss.learners) || boss.learners.length === 0"
            class="rounded-lg bg-gray-10 p-4 text-sm text-gray-50"
          >
            {{ t("No results found") }}
          </div>

          <div
            v-else
            class="overflow-x-auto"
          >
            <table class="w-full border-collapse text-sm">
              <thead>
                <tr class="border-b border-gray-25 bg-gray-10 text-left text-gray-70">
                  <th class="px-3 py-2 font-semibold">{{ t("Learner") }}</th>
                  <th class="px-3 py-2 font-semibold">{{ t("Username") }}</th>
                  <th class="px-3 py-2 font-semibold">{{ t("Active") }}</th>
                  <th class="px-3 py-2 text-right font-semibold">{{ t("Details") }}</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="learner in boss.learners"
                  :key="learner.id"
                  class="border-b border-gray-15 last:border-b-0"
                >
                  <td class="px-3 py-2 text-gray-90">{{ learner.fullName }}</td>
                  <td class="px-3 py-2 text-gray-70">{{ learner.username }}</td>
                  <td class="px-3 py-2">
                    <span
                      class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                      :class="learner.active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                    >
                      {{ t(learner.active ? "Active" : "Inactive") }}
                    </span>
                  </td>
                  <td class="px-3 py-2 text-right">
                    <BaseButton
                      :label="t('Details')"
                      icon="next"
                      only-icon
                      size="small"
                      type="primary-alternative"
                      :route="studentBossLearnerRoute(learner)"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </article>

      <div
        v-if="report.total > filters.itemsPerPage"
        class="rounded-xl border border-gray-25 bg-white p-3 shadow-sm"
      >
        <Paginator
          :first="(filters.page - 1) * filters.itemsPerPage"
          :rows="filters.itemsPerPage"
          :rows-per-page-options="[10, 20, 50, 100]"
          :total-records="report.total"
          template="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
          current-page-report-template="{first} - {last} / {totalRecords}"
          @page="onCardPage"
        />
      </div>
    </section>

    <section
      v-else-if="hasCurrentReport && report.meta.renderMode === 'tutor-planning'"
      class="space-y-4"
    >
      <header class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-xl font-semibold text-gray-90">{{ t(report.title) }}</h2>
          <p class="mt-1 text-sm text-gray-50">
            {{
              t("This report shows the weekly distribution of sessions per general tutor in the selected date range.")
            }}
          </p>
        </div>
        <span class="text-sm text-gray-50">{{ report.total }} {{ t("Results") }}</span>
      </header>

      <div
        v-if="!loading && report.items.length === 0"
        class="rounded-xl border border-gray-25 bg-white p-8 text-center text-gray-50 shadow-sm"
      >
        {{ t("No session matched") }}
      </div>

      <article
        v-for="tutor in report.items"
        :key="tutor.id"
        class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
      >
        <header class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-gray-25 pb-3">
          <div>
            <h3 class="font-semibold text-gray-90">{{ tutor.tutor }}</h3>
            <p class="text-sm text-gray-50">@{{ tutor.username }}</p>
          </div>
          <span class="rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-700">
            {{ tutor.sessions.length }} {{ t("Sessions") }}
          </span>
        </header>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <a
            v-for="session in tutor.sessions"
            :key="session.id"
            :href="session.url"
            class="rounded-xl border border-gray-25 bg-gray-10 p-3 transition hover:border-primary hover:bg-white"
          >
            <div class="font-medium text-gray-90">{{ session.title }}</div>
            <div class="mt-2 flex items-center justify-between gap-3 text-sm">
              <span :class="dateStatusClass(session.startDate)">
                {{ formatDateTime(session.startDate, true) }}
              </span>
              <span class="text-gray-40">→</span>
              <span :class="dateStatusClass(session.endDate)">
                {{ formatDateTime(session.endDate, true) }}
              </span>
            </div>
          </a>
        </div>
      </article>
    </section>

    <section
      v-else-if="hasCurrentReport"
      class="rounded-xl border border-gray-25 bg-white shadow-sm"
    >
      <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-25 p-4">
        <div>
          <h2 class="text-xl font-semibold text-gray-90">{{ t(report.title) }}</h2>
          <p
            v-if="report.meta.selectedUser"
            class="mt-1 text-sm text-gray-50"
          >
            {{ report.meta.selectedUser.fullName }} · @{{ report.meta.selectedUser.username }}
          </p>
        </div>
        <span class="text-sm text-gray-50">{{ report.total }} {{ t("Results") }}</span>
      </header>

      <div class="overflow-x-auto p-4">
        <BaseTable
          v-model:rows="filters.itemsPerPage"
          v-model:sort-field="filters.sort"
          v-model:sort-order="sortOrder"
          :values="report.items"
          :total-items="report.total"
          :is-loading="loading"
          :lazy="true"
          data-key="id"
          :text-for-empty="t('No results found')"
          @page="onTablePage"
          @sort="onSort"
        >
          <Column
            v-for="column in report.columns"
            :key="column.key"
            :field="column.key"
            :sortable="Boolean(column.sortable)"
          >
            <template #header>
              <span>{{ t(column.label) }}</span>
            </template>
            <template #body="{ data }">
              <span
                v-if="column.type === 'status'"
                class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                :class="statusClass(data[column.key])"
              >
                {{ t(String(data[column.key] || "-")) }}
              </span>
              <BaseButton
                v-else-if="column.type === 'learner-detail'"
                :label="t('Details')"
                icon="next"
                only-icon
                size="small"
                type="primary-alternative"
                :route="learnerDetailRoute(data)"
              />
              <a
                v-else-if="column.type === 'link' && data[column.urlKey]"
                :href="data[column.urlKey]"
                class="text-primary hover:underline"
              >
                {{ formatValue(data[column.key], column.type) }}
              </a>
              <span
                v-else-if="column.type === 'date-status'"
                :class="dateStatusClass(data[column.key])"
              >
                {{ formatDateTime(data[column.key], true) }}
              </span>
              <span v-else>{{ formatValue(data[column.key], column.type) }}</span>
            </template>
          </Column>
        </BaseTable>
      </div>
    </section>

    <div
      v-if="loading && !hasCurrentReport"
      class="flex justify-center py-8"
    >
      <ProgressSpinner />
    </div>
  </main>
</template>

<script setup>
import Column from "primevue/column"
import Message from "primevue/message"
import Paginator from "primevue/paginator"
import ProgressSpinner from "primevue/progressspinner"
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import globalReportingService from "../../services/globalReportingService"
import GlobalReportingToolbar from "./GlobalReportingToolbar.vue"
import AdminMetric from "./GlobalReportingAdminMetric.vue"

const props = defineProps({
  section: {
    type: String,
    required: true,
  },
})

const route = useRoute()
const router = useRouter()
const { locale, t } = useI18n()
const loading = ref(false)
const exportFormat = ref("")
const errorMessage = ref("")
const sortOrder = ref(1)
const startDateValue = ref(null)
const endDateValue = ref(null)
const report = reactive({
  title: "Admin view",
  total: 0,
  page: 1,
  itemsPerPage: 20,
  columns: [],
  items: [],
  meta: {},
})
const filters = reactive({
  page: 1,
  itemsPerPage: 20,
  keyword: "",
  sort: "",
  direction: "ASC",
  startDate: "",
  endDate: "",
  language: "",
  courseId: 0,
  sessionId: 0,
  userId: 0,
  exerciseId: 0,
  status: 0,
})

const routeNameBySection = {
  "admin-coaches": "GlobalReportingAdminCoaches",
  "admin-users": "GlobalReportingAdminUsers",
  "admin-sessions": "GlobalReportingAdminSessions",
  "admin-courses": "GlobalReportingAdminCourses",
  "learning-results": "GlobalReportingLearningResults",
  "session-results": "GlobalReportingSessionResults",
  "access-overview": "GlobalReportingAccessOverview",
  "exercise-categories": "GlobalReportingExerciseCategories",
  surveys: "GlobalReportingSurveys",
  "student-bosses": "GlobalReportingStudentBosses",
  "tutor-planning": "GlobalReportingTutorPlanning",
  "question-stats": "GlobalReportingQuestionStats",
  "question-stats-detail": "GlobalReportingQuestionStatsDetail",
  organization: "GlobalReportingOrganization",
  "learning-path-authors": "GlobalReportingLearningPathAuthors",
  "learning-path-item-authors": "GlobalReportingLearningPathItemAuthors",
}

const hasCurrentReport = computed(() => props.section !== "admin-index")
const activeAdminSection = computed(() => String(report.meta.activeAdminSection || props.section))
const adminReports = computed(() => (Array.isArray(report.meta.adminReports) ? report.meta.adminReports : []))
const showFilters = computed(() =>
  Boolean(
    report.meta.supportsKeyword ||
    report.meta.supportsDateRange ||
    report.meta.supportsStartDate ||
    report.meta.supportsLanguage ||
    report.meta.supportsCourse ||
    report.meta.supportsSession ||
    report.meta.supportsProfile ||
    report.meta.supportsUser ||
    report.meta.supportsExercise,
  ),
)
const courseOptions = computed(() => normalizeSelectOptions(report.meta.courseOptions))
const sessionOptions = computed(() => normalizeSelectOptions(report.meta.sessionOptions))
const userOptions = computed(() => normalizeSelectOptions(report.meta.userOptions))
const exerciseOptions = computed(() => normalizeSelectOptions(report.meta.exerciseOptions))
const profileOptions = computed(() =>
  normalizeSelectOptions(report.meta.profileOptions).map((option) => ({
    ...option,
    label: t(option.label),
  })),
)
const languageOptions = computed(() =>
  (Array.isArray(report.meta.languageOptions) ? report.meta.languageOptions : []).map((value) => ({
    label: formatLanguage(value),
    value,
  })),
)

function normalizeSelectOptions(options) {
  if (!Array.isArray(options)) {
    return []
  }

  return options.map((option) => ({
    ...option,
    id: Number(option.id || 0),
    label: String(option.label || ""),
  }))
}

async function handleCourseChange() {
  filters.page = 1
  filters.sessionId = 0
  filters.userId = 0
  filters.exerciseId = 0
  await replaceQuery()
}

async function handleProfileChange() {
  filters.page = 1
  filters.userId = 0
  await replaceQuery()
}

function adminReportRoute(section) {
  return { name: routeNameBySection[section] || "GlobalReportingAdmin" }
}

function adminReportClass(section) {
  const baseClasses = [
    "group flex min-h-16 items-center gap-3 rounded-xl border",
    "p-3 text-sm font-medium transition md:p-4 md:text-base",
  ]
  const stateClasses =
    section === activeAdminSection.value
      ? "border-primary bg-blue-50 text-primary shadow-sm"
      : [
          "border-gray-25 bg-gray-10 text-gray-90",
          "hover:border-primary hover:bg-white hover:text-primary hover:shadow-sm",
        ].join(" ")

  return [...baseClasses, stateClasses]
}

function hydrateFromRoute() {
  filters.page = Math.max(1, Number(route.query.page || 1))
  filters.itemsPerPage = Math.min(100, Math.max(10, Number(route.query.itemsPerPage || 20)))
  filters.keyword = String(route.query.keyword || "")
  filters.sort = String(route.query.sort || "")
  filters.direction = String(route.query.direction || "ASC")
  filters.startDate = String(route.query.startDate || "")
  filters.endDate = String(route.query.endDate || "")
  filters.language = String(route.query.language || "")
  filters.courseId = Math.max(0, Number(route.query.courseId || 0))
  filters.sessionId = Math.max(0, Number(route.query.sessionId || 0))
  filters.userId = Math.max(0, Number(route.query.userId || 0))
  filters.exerciseId = Math.max(0, Number(route.query.exerciseId || 0))
  filters.status = Math.max(0, Number(route.query.status || 0))
  sortOrder.value = filters.direction === "DESC" ? -1 : 1
  startDateValue.value = parseDate(filters.startDate)
  endDateValue.value = parseDate(filters.endDate)
}

function requestParams() {
  return {
    page: filters.page,
    itemsPerPage: filters.itemsPerPage,
    keyword: filters.keyword.trim(),
    sort: filters.sort,
    direction: filters.direction,
    startDate: filters.startDate,
    endDate: filters.endDate,
    language: filters.language,
    courseId: filters.courseId || undefined,
    sessionId: filters.sessionId || undefined,
    userId: filters.userId || undefined,
    exerciseId: filters.exerciseId || undefined,
    status: filters.status || undefined,
  }
}

async function loadReport() {
  loading.value = true
  errorMessage.value = ""

  try {
    const data = await globalReportingService.getSection(props.section, requestParams())
    report.title = data.title || "Admin view"
    report.total = Number(data.total || 0)
    report.page = Number(data.page || 1)
    report.itemsPerPage = Number(data.itemsPerPage || filters.itemsPerPage)
    report.columns = Array.isArray(data.columns) ? data.columns : []
    report.items = Array.isArray(data.items) ? data.items : []
    report.meta = data.meta || {}
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.message || t("Unable to load the report")
  } finally {
    loading.value = false
  }
}

async function replaceQuery() {
  const query = {
    page: filters.page > 1 ? filters.page : undefined,
    itemsPerPage: filters.itemsPerPage !== 20 ? filters.itemsPerPage : undefined,
    keyword: filters.keyword.trim() || undefined,
    sort: filters.sort || undefined,
    direction: filters.direction !== "ASC" ? filters.direction : undefined,
    startDate: filters.startDate || undefined,
    endDate: filters.endDate || undefined,
    language: filters.language || undefined,
    courseId: filters.courseId || undefined,
    sessionId: filters.sessionId || undefined,
    userId: filters.userId || undefined,
    exerciseId: filters.exerciseId || undefined,
    status: filters.status || undefined,
  }

  await router.push({ name: route.name, query })
}

async function applyFilters() {
  errorMessage.value = ""
  if (report.meta.requiresCourse && !filters.courseId) {
    errorMessage.value = t("Please select a course")
    return
  }
  if (report.meta.requiresProfile && !filters.status) {
    errorMessage.value = t("Please select a profile")
    return
  }
  if (report.meta.requiresSession && !filters.sessionId) {
    errorMessage.value = t("Please select a session")
    return
  }
  if (report.meta.requiresUser && !filters.userId) {
    errorMessage.value = t("Please select a user")
    return
  }

  filters.page = 1
  filters.startDate = toDateString(startDateValue.value)
  filters.endDate = toDateString(endDateValue.value)
  await replaceQuery()
}

async function resetFilters() {
  filters.page = 1
  filters.keyword = ""
  filters.sort = ""
  filters.direction = "ASC"
  filters.startDate = ""
  filters.endDate = ""
  filters.language = ""
  filters.courseId = 0
  filters.sessionId = 0
  filters.userId = 0
  filters.exerciseId = 0
  filters.status = 0
  startDateValue.value = null
  endDateValue.value = null
  await replaceQuery()
}

async function onTablePage(event) {
  filters.page = Number(event.page || 0) + 1
  filters.itemsPerPage = Number(event.rows || filters.itemsPerPage)
  await replaceQuery()
}

async function onCardPage(event) {
  filters.page = Number(event.page || 0) + 1
  filters.itemsPerPage = Number(event.rows || filters.itemsPerPage)
  await replaceQuery()
}

async function onSort(event) {
  filters.sort = String(event.sortField || "")
  filters.direction = Number(event.sortOrder || 1) < 0 ? "DESC" : "ASC"
  filters.page = 1
  await replaceQuery()
}

function learnerDetailRoute(data) {
  return {
    name: "GlobalReportingLearnerDetail",
    params: { userId: data.id },
    query: {
      returnTo: "global-reporting-admin-users",
      returnPage: filters.page,
      returnItemsPerPage: filters.itemsPerPage,
      returnKeyword: filters.keyword || undefined,
    },
  }
}

function studentBossLearnerRoute(learner) {
  return {
    name: "GlobalReportingLearnerDetail",
    params: { userId: learner.id },
    query: {
      returnTo: "global-reporting-admin-student-bosses",
      returnPage: filters.page,
      returnItemsPerPage: filters.itemsPerPage,
      returnLanguage: filters.language || undefined,
    },
  }
}

function formatLanguage(value) {
  return String(value || "-").replaceAll("_", "-")
}

function printReport() {
  window.print()
}

async function downloadExport(format) {
  exportFormat.value = format
  errorMessage.value = ""

  try {
    const response = await globalReportingService.downloadSection(props.section, format, requestParams())
    const blob = response.data instanceof Blob ? response.data : new Blob([response.data])
    const url = URL.createObjectURL(blob)
    const link = document.createElement("a")
    link.href = url
    link.download = exportFilename(response, `${props.section}.${format}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.message || t("Unable to export the report")
  } finally {
    exportFormat.value = ""
  }
}

function exportFilename(response, fallback) {
  const disposition = String(response?.headers?.["content-disposition"] || "")
  const match = disposition.match(/filename\*?=(?:UTF-8'')?([^;]+)/iu)

  return match?.[1] ? decodeURIComponent(match[1].replace(/"/gu, "")) : fallback
}

function ringDash(value) {
  const circumference = 2 * Math.PI * 48
  const ratio = Math.max(0, Math.min(1, Number(value || 0) / 100))

  return `${circumference * ratio} ${circumference}`
}

function formatTestScore(course) {
  const possible = Number(course.scorePossible || 0)
  if (possible <= 0) {
    return "-"
  }

  return `${formatNumber(course.scoreObtained)}/${formatNumber(possible)} (${formatNumber(course.scorePercentage)}%)`
}

function formatNumber(value) {
  return Number(value || 0)
    .toFixed(2)
    .replace(/\.00$/u, "")
    .replace(/(\.\d)0$/u, "$1")
}

function formatPercent(value) {
  return `${formatNumber(value)}%`
}

function formatNullablePercent(value) {
  return value === null || value === undefined ? "-" : formatPercent(value)
}

function courseScoreAriaLabel(course) {
  return `${t("Average score in learning paths")}: ${formatNullablePercent(course.averageLearningPathScore)}`
}

function formatValue(value, type) {
  if (value === null || value === undefined || value === "") {
    return "-"
  }
  if (type === "duration") {
    return formatDuration(value)
  }
  if (type === "percent" || type === "nullable-percent") {
    return formatPercent(value)
  }
  if (type === "datetime" || type === "date") {
    return formatDateTime(value, type === "date")
  }
  if (type === "html") {
    return String(value)
      .replace(/<[^>]*>/gu, " ")
      .replace(/\s+/gu, " ")
      .trim()
  }

  return String(value)
}

function formatDuration(value) {
  const seconds = Math.max(0, Number(value || 0))
  return [Math.floor(seconds / 3600), Math.floor((seconds % 3600) / 60), Math.floor(seconds % 60)]
    .map((part) => String(part).padStart(2, "0"))
    .join(":")
}

function formatDateTime(value, dateOnly = false) {
  if (!value) {
    return "-"
  }

  const date = new Date(String(value).replace(" ", "T"))
  if (Number.isNaN(date.getTime())) {
    return String(value)
  }

  return new Intl.DateTimeFormat(String(locale.value || "en-US").replace("_", "-"), {
    dateStyle: "medium",
    ...(dateOnly ? {} : { timeStyle: "short" }),
  }).format(date)
}

function dateStatusClass(value) {
  if (!value) {
    return "text-gray-50"
  }

  const date = new Date(String(value).replace(" ", "T"))
  if (Number.isNaN(date.getTime())) {
    return "text-gray-70"
  }

  const today = new Date()
  const dateKey = new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime()
  const todayKey = new Date(today.getFullYear(), today.getMonth(), today.getDate()).getTime()
  if (dateKey < todayKey) {
    return "font-semibold text-red-600"
  }
  if (dateKey > todayKey) {
    return "font-semibold text-green-700"
  }

  return "font-semibold text-gray-90"
}

function statusClass(value) {
  const normalized = String(value || "").toLowerCase()
  if (["active", "yes", "pass", "teacher", "learner"].includes(normalized)) {
    return "bg-green-100 text-green-700"
  }
  if (["inactive", "no", "fail"].includes(normalized)) {
    return "bg-red-100 text-red-700"
  }

  return "bg-gray-100 text-gray-700"
}

function parseDate(value) {
  if (!value) {
    return null
  }

  const date = new Date(`${value}T00:00:00`)
  return Number.isNaN(date.getTime()) ? null : date
}

function toDateString(value) {
  if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
    return ""
  }

  return [
    value.getFullYear(),
    String(value.getMonth() + 1).padStart(2, "0"),
    String(value.getDate()).padStart(2, "0"),
  ].join("-")
}

watch(
  () => route.fullPath,
  async () => {
    hydrateFromRoute()
    await loadReport()
  },
)

onMounted(async () => {
  hydrateFromRoute()
  await loadReport()
})
</script>
