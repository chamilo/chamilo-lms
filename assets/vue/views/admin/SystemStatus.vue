<template>
  <section class="space-y-6">
    <div class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm">
      <div class="flex items-center gap-3">
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-support-1 text-primary">
          <i class="mdi mdi-heart-pulse text-2xl" />
        </span>
        <div>
          <h1 class="text-2xl font-semibold tracking-tight text-gray-90">
            {{ t("System status") }}
          </h1>
          <p class="mt-1 max-w-3xl text-body-2 text-gray-50">
            {{ activeSectionInfo }}
          </p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
      <button
        v-for="section in sections"
        :key="section.key"
        type="button"
        class="group block rounded-2xl bg-white p-4 text-left shadow-sm transition hover:shadow-md"
        :class="
          section.key === currentSection
            ? 'ring-2 ring-primary/80 bg-primary/5'
            : 'ring-1 ring-gray-200 hover:ring-gray-300'
        "
        @click="selectSection(section.key)"
      >
        <div class="flex items-center gap-3">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-10 group-hover:bg-gray-30">
            <i
              class="mdi text-2xl"
              :class="[section.icon, section.key === currentSection ? 'text-primary' : 'text-gray-70']"
            />
          </div>
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <h3
                class="truncate text-sm font-semibold"
                :class="section.key === currentSection ? 'text-primary' : 'text-gray-90'"
              >
                {{ section.label }}
              </h3>
              <span
                v-if="section.key === currentSection"
                class="ml-auto rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
              >
                {{ t("Active") }}
              </span>
            </div>
            <p class="mt-0.5 line-clamp-2 text-xs text-gray-50">
              {{ section.info }}
            </p>
          </div>
        </div>
      </button>
    </div>

    <div
      v-if="activeSectionInfo"
      class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-blue-800"
    >
      <div class="flex items-start gap-3">
        <BaseIcon
          icon="information"
          size="small"
        />
        <p class="text-sm">
          {{ activeSectionInfo }}
        </p>
      </div>
    </div>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-gray-20 bg-white p-8 text-center text-gray-50 shadow-sm"
    >
      {{ t("Loading") }}...
    </div>

    <div
      v-else-if="errorMessage"
      class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800"
    >
      {{ errorMessage }}
    </div>

    <template v-else>
      <div
        v-if="currentSection === 'php'"
        class="space-y-4"
      >
        <div class="rounded-2xl border border-gray-20 bg-white shadow-sm">
          <button
            type="button"
            name="php-cache-toggle"
            class="flex w-full items-center justify-between gap-3 rounded-2xl px-5 py-4 text-left transition hover:bg-support-2"
            :aria-expanded="phpCacheExpanded ? 'true' : 'false'"
            aria-controls="php-cache-panel"
            @click="togglePhpCache"
          >
            <div class="flex min-w-0 items-center gap-3">
              <span
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-support-1 text-primary"
              >
                <i class="mdi mdi-memory text-xl" />
              </span>
              <div class="min-w-0">
                <h2 class="text-xl font-semibold text-gray-90">
                  {{ t("PHP cache") }}
                </h2>
                <p class="mt-0.5 text-caption text-gray-50">
                  {{
                    phpCacheExpanded
                      ? t("Live OPcache and APCu diagnostics")
                      : t("Click to show OPcache and APCu diagnostics")
                  }}
                </p>
              </div>
            </div>
            <i
              class="mdi text-2xl text-gray-50"
              :class="phpCacheExpanded ? 'mdi-chevron-up' : 'mdi-chevron-down'"
            />
          </button>

          <div
            v-if="phpCacheExpanded"
            id="php-cache-panel"
            class="space-y-4 border-t border-gray-20 px-5 pb-5 pt-4"
          >
            <div class="flex flex-wrap items-center justify-end gap-3">
              <BaseCheckbox
                id="php-cache-auto-refresh"
                v-model="cacheAutoRefresh"
                name="php_cache_auto_refresh"
                :label="t('Auto-refresh every 5 seconds')"
              />
              <span
                v-if="cacheFetchedAt"
                class="text-caption text-gray-50"
              >
                {{ t("Last updated") }}: {{ formatLastVisit(cacheFetchedAt) }}
              </span>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
              <div class="rounded-2xl border border-gray-20 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-start justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <span
                      class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-support-1 text-primary"
                    >
                      <i class="mdi mdi-memory text-xl" />
                    </span>
                    <div>
                      <h3 class="text-body-1 font-semibold text-gray-90">OPcache</h3>
                      <p class="text-caption text-gray-50">
                        {{ cacheStatusLabel(cacheData?.opcache) }}
                      </p>
                    </div>
                  </div>
                  <BaseButton
                    :label="t('Refresh')"
                    icon="refresh"
                    type="primary-text"
                    only-icon
                    size="small"
                    :is-loading="cacheLoading"
                    @click="loadCacheData"
                  />
                </div>
                <div
                  v-if="cacheLoading && !cacheData"
                  class="text-caption text-gray-50"
                >
                  {{ t("Loading") }}...
                </div>
                <template v-else-if="cacheData?.opcache?.enabled">
                  <div
                    v-if="opcacheMemoryBar"
                    class="mb-4"
                  >
                    <div class="mb-1 flex items-center justify-between gap-2 text-caption">
                      <span class="font-semibold text-gray-70">
                        {{ t("Memory used") }}
                      </span>
                      <span class="font-mono text-gray-90">
                        {{ formatBytes(opcacheMemoryBar.used) }}
                        /
                        {{ formatBytes(opcacheMemoryBar.total) }}
                        ({{ formatPercent(opcacheMemoryBar.percent) }})
                      </span>
                    </div>
                    <div
                      class="h-3 w-full overflow-hidden rounded-full bg-gray-20"
                      role="progressbar"
                      :aria-valuenow="opcacheMemoryBar.percent"
                      aria-valuemin="0"
                      aria-valuemax="100"
                      :aria-label="t('Memory used')"
                    >
                      <div
                        class="h-full rounded-full transition-all duration-300"
                        :style="memoryBarFillStyle(opcacheMemoryBar.percent)"
                      ></div>
                    </div>
                  </div>
                  <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div
                      v-for="metric in opcacheMetrics"
                      :key="metric.label"
                      class="rounded-xl border border-gray-20 bg-support-2 p-3"
                    >
                      <dt class="text-caption font-semibold uppercase tracking-wide text-gray-50">
                        {{ metric.label }}
                      </dt>
                      <dd class="mt-1 font-mono text-body-2 text-gray-90">
                        {{ metric.value }}
                      </dd>
                    </div>
                  </dl>
                </template>
                <p
                  v-else
                  class="text-body-2 text-gray-50"
                >
                  {{
                    cacheData?.opcache?.available
                      ? t("OPcache is installed but not enabled.")
                      : t("OPcache extension is not available on this server.")
                  }}
                </p>
              </div>

              <div class="rounded-2xl border border-gray-20 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-start justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <span
                      class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-support-1 text-primary"
                    >
                      <i class="mdi mdi-database-outline text-xl" />
                    </span>
                    <div>
                      <h3 class="text-body-1 font-semibold text-gray-90">APCu</h3>
                      <p class="text-caption text-gray-50">
                        {{ cacheStatusLabel(cacheData?.apcu) }}
                      </p>
                    </div>
                  </div>
                  <BaseButton
                    :label="t('Refresh')"
                    icon="refresh"
                    type="primary-text"
                    only-icon
                    size="small"
                    :is-loading="cacheLoading"
                    @click="loadCacheData"
                  />
                </div>
                <div
                  v-if="cacheLoading && !cacheData"
                  class="text-caption text-gray-50"
                >
                  {{ t("Loading") }}...
                </div>
                <template v-else-if="cacheData?.apcu?.enabled">
                  <div
                    v-if="apcuMemoryBar"
                    class="mb-4"
                  >
                    <div class="mb-1 flex items-center justify-between gap-2 text-caption">
                      <span class="font-semibold text-gray-70">
                        {{ t("Memory used") }}
                      </span>
                      <span class="font-mono text-gray-90">
                        {{ formatBytes(apcuMemoryBar.used) }}
                        /
                        {{ formatBytes(apcuMemoryBar.total) }}
                        ({{ formatPercent(apcuMemoryBar.percent) }})
                      </span>
                    </div>
                    <div
                      class="h-3 w-full overflow-hidden rounded-full bg-gray-20"
                      role="progressbar"
                      :aria-valuenow="apcuMemoryBar.percent"
                      aria-valuemin="0"
                      aria-valuemax="100"
                      :aria-label="t('Memory used')"
                    >
                      <div
                        class="h-full rounded-full transition-all duration-300"
                        :style="memoryBarFillStyle(apcuMemoryBar.percent)"
                      ></div>
                    </div>
                  </div>
                  <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div
                      v-for="metric in apcuMetrics"
                      :key="metric.label"
                      class="rounded-xl border border-gray-20 bg-support-2 p-3"
                    >
                      <dt class="text-caption font-semibold uppercase tracking-wide text-gray-50">
                        {{ metric.label }}
                      </dt>
                      <dd class="mt-1 font-mono text-body-2 text-gray-90">
                        {{ metric.value }}
                      </dd>
                    </div>
                  </dl>
                </template>
                <p
                  v-else
                  class="text-body-2 text-gray-50"
                >
                  {{
                    cacheData?.apcu?.available
                      ? t("APCu is installed but not enabled.")
                      : t("APCu extension is not available on this server.")
                  }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="currentSection === 'database'"
        class="space-y-4"
      >
        <div class="rounded-2xl border border-gray-20 bg-white shadow-sm">
          <button
            id="database-load-toggle"
            type="button"
            name="database-load-toggle"
            class="flex w-full items-center justify-between gap-3 rounded-2xl px-5 py-4 text-left transition hover:bg-support-2"
            :aria-expanded="dbExpanded ? 'true' : 'false'"
            aria-controls="database-load-panel"
            @click="toggleDbLoad"
          >
            <div class="flex min-w-0 items-center gap-3">
              <span
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-support-1 text-primary"
              >
                <i class="mdi mdi-chart-line text-xl" />
              </span>
              <div class="min-w-0">
                <h2 class="text-xl font-semibold text-gray-90">
                  {{ t("Database load") }}
                </h2>
                <p class="mt-0.5 text-caption text-gray-50">
                  {{
                    dbExpanded
                      ? t("Live MySQL and MariaDB server load metrics")
                      : t("Click to show database server load metrics")
                  }}
                </p>
              </div>
            </div>
            <i
              class="mdi text-2xl text-gray-50"
              :class="dbExpanded ? 'mdi-chevron-up' : 'mdi-chevron-down'"
            />
          </button>

          <div
            v-if="dbExpanded"
            id="database-load-panel"
            class="space-y-4 border-t border-gray-20 px-5 pb-5 pt-4"
          >
            <div class="flex flex-wrap items-center justify-end gap-3">
              <BaseCheckbox
                id="database-load-auto-refresh"
                v-model="dbAutoRefresh"
                name="database_load_auto_refresh"
                :label="t('Auto-refresh every 5 seconds')"
              />
              <span
                v-if="dbFetchedAt"
                class="text-caption text-gray-50"
              >
                {{ t("Last updated") }}: {{ formatLastVisit(dbFetchedAt) }}
              </span>
              <BaseButton
                :label="t('Refresh')"
                icon="refresh"
                type="primary-text"
                only-icon
                size="small"
                :is-loading="dbLoading"
                @click="loadDbData"
              />
            </div>

            <div
              v-if="dbLoading && !dbData"
              class="text-caption text-gray-50"
            >
              {{ t("Loading") }}...
            </div>
            <template v-else-if="dbData?.server?.available">
              <div
                v-if="dbConnectionBar"
                class="mb-2"
              >
                <div class="mb-1 flex items-center justify-between gap-2 text-caption">
                  <span class="font-semibold text-gray-70">
                    {{ t("Threads connected") }}
                  </span>
                  <span class="font-mono text-gray-90">
                    {{ formatNumber(dbData.server.counters?.Threads_connected) }}
                    /
                    {{ formatNumber(dbMaxConnections) }}
                    ({{ formatPercent(dbConnectionBar.percent) }})
                  </span>
                </div>
                <div
                  class="h-3 w-full overflow-hidden rounded-full bg-gray-20"
                  role="progressbar"
                  :aria-valuenow="dbConnectionBar.percent"
                  aria-valuemin="0"
                  aria-valuemax="100"
                  :aria-label="t('Threads connected')"
                >
                  <div
                    class="h-full rounded-full transition-all duration-300"
                    :style="memoryBarFillStyle(dbConnectionBar.percent)"
                  ></div>
                </div>
              </div>

              <div
                v-if="dbTmpDiskBar"
                class="mb-2"
              >
                <div class="mb-1 flex items-center justify-between gap-2 text-caption">
                  <span class="font-semibold text-gray-70">
                    {{ t("Temporary tables on disk") }}
                  </span>
                  <span class="font-mono text-gray-90">
                    {{ formatNumber(dbData.server.counters?.Created_tmp_disk_tables) }}
                    /
                    {{ formatNumber(dbData.server.counters?.Created_tmp_tables) }}
                    ({{ formatPercent(dbTmpDiskBar.percent) }})
                  </span>
                </div>
                <div
                  class="h-3 w-full overflow-hidden rounded-full bg-gray-20"
                  role="progressbar"
                  :aria-valuenow="dbTmpDiskBar.percent"
                  aria-valuemin="0"
                  aria-valuemax="100"
                  :aria-label="t('Temporary tables on disk')"
                >
                  <div
                    class="h-full rounded-full transition-all duration-300"
                    :style="memoryBarFillStyle(dbTmpDiskBar.percent)"
                  ></div>
                </div>
              </div>

              <dl
                v-if="dbMetrics.length"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
              >
                <div
                  v-for="metric in dbMetrics"
                  :key="metric.label"
                  class="rounded-xl border border-gray-20 bg-support-2 p-3"
                >
                  <dt class="text-caption font-semibold uppercase tracking-wide text-gray-50">
                    {{ metric.label }}
                  </dt>
                  <dd class="mt-1 font-mono text-body-2 text-gray-90">
                    {{ metric.value }}
                  </dd>
                </div>
              </dl>
              <p
                v-else
                class="text-body-2 text-gray-50"
              >
                {{ t("Not available") }}
              </p>

              <div
                v-if="dbData.server.queryCache?.available && dbQueryCacheMetrics.length"
                class="rounded-2xl border border-gray-20 bg-white p-4 shadow-sm"
              >
                <h3 class="mb-3 text-body-1 font-semibold text-gray-90">
                  {{ t("Query cache") }}
                </h3>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <div
                    v-for="metric in dbQueryCacheMetrics"
                    :key="metric.label"
                    class="rounded-xl border border-gray-20 bg-support-2 p-3"
                  >
                    <dt class="text-caption font-semibold uppercase tracking-wide text-gray-50">
                      {{ metric.label }}
                    </dt>
                    <dd class="mt-1 font-mono text-body-2 text-gray-90">
                      {{ metric.value }}
                    </dd>
                  </div>
                </dl>
              </div>

              <div
                v-if="dbPrivilegeNote"
                class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800"
              >
                <p class="font-semibold">{{ t("Privilege scope") }}: {{ dbPrivilegeNote.scopeLabel }}</p>
                <p
                  v-if="dbPrivilegeNote.unavailableList"
                  class="mt-1 text-caption"
                >
                  {{ t("Not measurable with the current database user") }}:
                  {{ dbPrivilegeNote.unavailableList }}
                </p>
              </div>
            </template>
            <p
              v-else
              class="text-body-2 text-gray-50"
            >
              {{ dbUnavailableReason }}
            </p>
          </div>
        </div>
      </div>

      <div
        v-if="currentSection === 'webserver'"
        class="space-y-4"
      >
        <div class="rounded-2xl border border-gray-20 bg-white shadow-sm">
          <button
            id="webserver-load-toggle"
            type="button"
            name="webserver-load-toggle"
            class="flex w-full items-center justify-between gap-3 rounded-2xl px-5 py-4 text-left transition hover:bg-support-2"
            :aria-expanded="wsExpanded ? 'true' : 'false'"
            aria-controls="webserver-load-panel"
            @click="toggleWsLoad"
          >
            <div class="flex min-w-0 items-center gap-3">
              <span
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-support-1 text-primary"
              >
                <i class="mdi mdi-chart-timeline-variant text-xl" />
              </span>
              <div class="min-w-0">
                <h2 class="text-xl font-semibold text-gray-90">
                  {{ t("Web server load") }}
                </h2>
                <p class="mt-0.5 text-caption text-gray-50">
                  {{
                    wsExpanded
                      ? t("Live Apache or Nginx status metrics")
                      : t("Click to show web server load metrics")
                  }}
                </p>
              </div>
            </div>
            <i
              class="mdi text-2xl text-gray-50"
              :class="wsExpanded ? 'mdi-chevron-up' : 'mdi-chevron-down'"
            />
          </button>

          <div
            v-if="wsExpanded"
            id="webserver-load-panel"
            class="space-y-4 border-t border-gray-20 px-5 pb-5 pt-4"
          >
            <div class="flex flex-wrap items-center justify-end gap-3">
              <BaseCheckbox
                id="webserver-load-auto-refresh"
                v-model="wsAutoRefresh"
                name="webserver_load_auto_refresh"
                :label="t('Auto-refresh every 5 seconds')"
              />
              <span
                v-if="wsFetchedAt"
                class="text-caption text-gray-50"
              >
                {{ t("Last updated") }}: {{ formatLastVisit(wsFetchedAt) }}
                <span
                  v-if="wsFetchedAt"
                  class="ml-1 font-mono"
                >
                  ({{ wsFetchedAt }})
                </span>
              </span>
              <BaseButton
                :label="t('Refresh')"
                icon="refresh"
                type="primary-text"
                only-icon
                size="small"
                :is-loading="wsLoading"
                @click="loadWsData"
              />
            </div>

            <div class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
              <p class="font-semibold">
                {{ t("Requires a local web server status module") }}
              </p>
              <p class="mt-1 text-caption">
                {{ t("Web server load is read from Apache mod_status or Nginx stub_status on localhost only. The status page must allow requests from 127.0.0.1 (or ::1).") }}
              </p>
              <p class="mt-1 text-caption">
                {{ t("Apache: enable mod_status (ExtendedStatus On) and allow localhost to /server-status. Nginx: enable stub_status on a localhost-only location such as /nginx_status or /stub_status.") }}
              </p>
              <p
                v-if="wsScannedPathsLabel"
                class="mt-2 text-caption"
              >
                <span class="font-semibold">{{ t("Paths scanned") }}:</span>
                {{ wsScannedPathsLabel }}
              </p>
              <p
                v-if="wsData?.software || wsData?.detected"
                class="mt-1 text-caption"
              >
                <span class="font-semibold">{{ t("Detected") }}:</span>
                {{ wsData?.software || wsDetectedLabel }}
              </p>
            </div>

            <div
              v-if="wsLoading && !wsData"
              class="text-caption text-gray-50"
            >
              {{ t("Loading") }}...
            </div>
            <template v-else-if="wsData?.status?.available">
              <div
                v-if="wsBusyBar"
                class="mb-2"
              >
                <div class="mb-1 flex items-center justify-between gap-2 text-caption">
                  <span class="font-semibold text-gray-70">
                    {{ t("Busy workers") }}
                  </span>
                  <span class="font-mono text-gray-90">
                    {{ formatNumber(wsBusyBar.busy) }}
                    /
                    {{ formatNumber(wsBusyBar.total) }}
                    ({{ formatPercent(wsBusyBar.percent) }})
                  </span>
                </div>
                <div
                  class="h-3 w-full overflow-hidden rounded-full bg-gray-20"
                  role="progressbar"
                  :aria-valuenow="wsBusyBar.percent"
                  aria-valuemin="0"
                  aria-valuemax="100"
                  :aria-label="t('Busy workers')"
                >
                  <div
                    class="h-full rounded-full transition-all duration-300"
                    :style="memoryBarFillStyle(wsBusyBar.percent)"
                  ></div>
                </div>
              </div>

              <p
                v-if="wsData.status.path"
                class="text-caption text-gray-50"
              >
                <span class="font-semibold">{{ t("Status path") }}:</span>
                <span class="font-mono">{{ wsData.status.path }}</span>
              </p>

              <dl
                v-if="wsMetrics.length"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
              >
                <div
                  v-for="metric in wsMetrics"
                  :key="metric.label"
                  class="rounded-xl border border-gray-20 bg-support-2 p-3"
                >
                  <dt class="text-caption font-semibold uppercase tracking-wide text-gray-50">
                    {{ metric.label }}
                  </dt>
                  <dd class="mt-1 font-mono text-body-2 text-gray-90">
                    {{ metric.value }}
                  </dd>
                </div>
              </dl>
            </template>
            <p
              v-else
              class="text-body-2 text-gray-50"
            >
              {{ wsUnavailableReason }}
            </p>
          </div>
        </div>
      </div>

      <BaseTable
        v-if="rowType === 'generic'"
        :values="rows"
        data-key="title"
      >
        <Column :header="t('Status')">
          <template #body="{ data }">
            <span
              class="inline-flex items-center gap-1"
              :title="data.status"
            >
              <i
                class="mdi text-xl"
                :class="statusIconClass(data.status)"
              />
            </span>
          </template>
        </Column>
        <Column
          field="section"
          :header="t('Section')"
        />
        <Column :header="t('Setting')">
          <template #body="{ data }">
            <a
              v-if="data.url"
              :href="data.url"
              target="_blank"
              rel="noopener noreferrer"
              class="text-primary hover:underline"
            >
              {{ data.title }}
            </a>
            <span v-else>{{ data.title }}</span>
          </template>
        </Column>
        <Column
          field="current"
          :header="t('Current')"
        >
          <template #body="{ data }">
            <span class="font-mono text-caption">{{ data.current }}</span>
          </template>
        </Column>
        <Column
          field="expected"
          :header="t('Expected')"
        >
          <template #body="{ data }">
            <span class="font-mono text-caption">{{ data.expected }}</span>
          </template>
        </Column>
        <Column
          field="comment"
          :header="t('Comment')"
        />
      </BaseTable>

      <BaseTable
        v-else-if="rowType === 'paths'"
        :values="rows"
        data-key="constant"
      >
        <Column
          field="path"
          :header="t('Path')"
        >
          <template #body="{ data }">
            <span class="break-all font-mono text-caption">{{ data.path }}</span>
          </template>
        </Column>
        <Column
          field="constant"
          :header="t('Constant')"
        >
          <template #body="{ data }">
            <span class="font-mono text-caption">{{ data.constant }}</span>
          </template>
        </Column>
      </BaseTable>

      <BaseTable
        v-else-if="rowType === 'coursesSpace'"
        :values="rows"
        data-key="id"
      >
        <Column header="">
          <template #body>
            <BaseIcon
              icon="home"
              size="small"
            />
          </template>
        </Column>
        <Column
          field="code"
          :header="t('Course code')"
        />
        <Column
          field="usedMb"
          :header="t('Space used on disk (MB)')"
        />
        <Column
          field="quotaMb"
          :header="t('Set max course space (MB)')"
        />
        <Column :header="t('Edit')">
          <template #body="{ data }">
            <BaseButton
              :label="t('Edit')"
              icon="pencil"
              type="secondary-text"
              only-icon
              size="small"
              :to-url="courseEditUrl(data.id)"
            />
          </template>
        </Column>
        <Column
          field="lastVisit"
          :header="t('Latest visit')"
        >
          <template #body="{ data }">
            {{ formatLastVisit(data.lastVisit) }}
          </template>
        </Column>
      </BaseTable>
    </template>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import baseService from "../../services/baseService"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { showErrorNotification } = useNotification()

const sections = ref([])
const currentSection = ref("chamilo")
const rowType = ref("generic")
const rows = ref([])
const isLoading = ref(true)
const errorMessage = ref("")

const cacheData = ref(null)
const cacheFetchedAt = ref(null)
const cacheLoading = ref(false)
const cacheAutoRefresh = ref(false)
/** Folded by default — live cache stats load only when expanded. */
const phpCacheExpanded = ref(false)
let cacheRefreshTimer = null

const dbData = ref(null)
const dbPrevSample = ref(null)
const dbFetchedAt = ref(null)
const dbLoading = ref(false)
const dbAutoRefresh = ref(false)
/** Folded by default — live DB load stats load only when expanded. */
const dbExpanded = ref(false)
let dbRefreshTimer = null

const wsData = ref(null)
const wsPrevSample = ref(null)
const wsFetchedAt = ref(null)
const wsLoading = ref(false)
const wsAutoRefresh = ref(false)
/** Folded by default — live web server load stats load only when expanded. */
const wsExpanded = ref(false)
let wsRefreshTimer = null

const activeSectionInfo = computed(() => {
  const match = sections.value.find((s) => s.key === currentSection.value)

  return match?.info || ""
})

const opcacheMemoryBar = computed(() => {
  const o = cacheData.value?.opcache
  if (!o?.enabled) {
    return null
  }

  const used = Number(o.memoryUsedBytes)
  const free = Number(o.memoryFreeBytes)
  const wasted = Number(o.memoryWastedBytes ?? 0)
  if (Number.isNaN(used) || Number.isNaN(free)) {
    return null
  }

  // Total allocated OPcache pool = used + free + wasted.
  const total = used + free + (Number.isNaN(wasted) ? 0 : wasted)
  if (total <= 0) {
    return null
  }

  // Occupancy includes wasted memory (still reserved in the pool).
  const occupied = used + (Number.isNaN(wasted) ? 0 : wasted)
  const percent =
    o.memoryUsedPercent !== null && o.memoryUsedPercent !== undefined && !Number.isNaN(Number(o.memoryUsedPercent))
      ? Number(o.memoryUsedPercent)
      : Math.round((10000 * occupied) / total) / 100

  return { used, total, percent }
})

const apcuMemoryBar = computed(() => {
  const a = cacheData.value?.apcu
  if (!a?.enabled) {
    return null
  }

  const used = Number(a.memoryUsedBytes)
  const total = Number(a.memorySizeBytes)
  if (Number.isNaN(used) || Number.isNaN(total) || total <= 0) {
    return null
  }

  const percent =
    a.memoryUsedPercent !== null && a.memoryUsedPercent !== undefined && !Number.isNaN(Number(a.memoryUsedPercent))
      ? Number(a.memoryUsedPercent)
      : Math.round((10000 * used) / total) / 100

  return { used, total, percent }
})

const opcacheMetrics = computed(() => {
  const o = cacheData.value?.opcache
  if (!o?.enabled) {
    return []
  }

  return [
    { label: t("Memory used"), value: formatBytes(o.memoryUsedBytes) },
    { label: t("Memory free"), value: formatBytes(o.memoryFreeBytes) },
    { label: t("Memory wasted"), value: formatBytes(o.memoryWastedBytes) },
    { label: t("Memory used %"), value: formatPercent(o.memoryUsedPercent) },
    { label: t("Cached scripts"), value: formatNumber(o.cachedScripts) },
    { label: t("Cached keys"), value: formatKeysRatio(o.cachedKeys, o.maxCachedKeys) },
    { label: t("Hits"), value: formatNumber(o.hits) },
    { label: t("Misses"), value: formatNumber(o.misses) },
    { label: t("Hit rate"), value: formatPercent(o.hitRatePercent) },
    { label: t("Cache full"), value: formatYesNo(o.full) },
    { label: t("OOM restarts"), value: formatNumber(o.oomRestarts) },
    { label: t("Hash restarts"), value: formatNumber(o.hashRestarts) },
    { label: t("Manual restarts"), value: formatNumber(o.manualRestarts) },
    { label: t("Interned strings used"), value: formatBytes(o.internedStringsUsedBytes) },
    { label: t("Interned strings free"), value: formatBytes(o.internedStringsFreeBytes) },
    { label: t("Interned strings count"), value: formatNumber(o.internedStringsNumber) },
  ]
})

const apcuMetrics = computed(() => {
  const a = cacheData.value?.apcu
  if (!a?.enabled) {
    return []
  }

  return [
    { label: t("Memory used"), value: formatBytes(a.memoryUsedBytes) },
    { label: t("Memory available"), value: formatBytes(a.memoryAvailableBytes) },
    { label: t("Memory size"), value: formatBytes(a.memorySizeBytes) },
    { label: t("Memory used %"), value: formatPercent(a.memoryUsedPercent) },
    { label: t("Entries"), value: formatNumber(a.numEntries) },
    { label: t("Slots"), value: formatNumber(a.numSlots) },
    { label: t("Hits"), value: formatNumber(a.numHits) },
    { label: t("Misses"), value: formatNumber(a.numMisses) },
    { label: t("Hit rate"), value: formatPercent(a.hitRatePercent) },
    { label: t("Inserts"), value: formatNumber(a.numInserts) },
    { label: t("Expunges"), value: formatNumber(a.numExpunges) },
    { label: t("Cache start time"), value: formatLastVisit(a.startTime) },
  ]
})

/**
 * Client-side rates from consecutive polls (mytop-style).
 * Guard against server restart / counter reset and missing baseline.
 */
const dbRates = computed(() => {
  const empty = {
    questionsPerSec: null,
    queriesPerSec: null,
    slowQueriesPerSec: null,
  }
  const curr = dbData.value?.server
  const prev = dbPrevSample.value?.server
  if (!curr?.available || !prev?.available) {
    return empty
  }

  const currUptime = Number(curr.counters?.Uptime)
  const prevUptime = Number(prev.counters?.Uptime)
  if (Number.isNaN(currUptime) || Number.isNaN(prevUptime) || currUptime < prevUptime) {
    return empty
  }

  const currMs = Date.parse(dbData.value?.fetchedAt || "")
  const prevMs = Date.parse(dbPrevSample.value?.fetchedAt || "")
  if (Number.isNaN(currMs) || Number.isNaN(prevMs)) {
    return empty
  }

  const dt = (currMs - prevMs) / 1000
  if (dt <= 0) {
    return empty
  }

  const rateFor = (key) => {
    const c = Number(curr.counters?.[key])
    const p = Number(prev.counters?.[key])
    if (Number.isNaN(c) || Number.isNaN(p) || c < p) {
      return null
    }

    return (c - p) / dt
  }

  return {
    questionsPerSec: rateFor("Questions"),
    queriesPerSec: rateFor("Queries"),
    slowQueriesPerSec: rateFor("Slow_queries"),
  }
})

const dbMaxConnections = computed(() => {
  const raw = dbData.value?.server?.variables?.max_connections
  if (raw === null || raw === undefined || raw === "" || Number.isNaN(Number(raw))) {
    return null
  }

  return Number(raw)
})

const dbConnectionBar = computed(() => {
  const percent = dbData.value?.server?.derived?.threadsConnectedPercent
  if (percent === null || percent === undefined || Number.isNaN(Number(percent))) {
    return null
  }

  return { percent: Number(percent) }
})

const dbTmpDiskBar = computed(() => {
  const percent = dbData.value?.server?.derived?.tmpTablesOnDiskPercent
  if (percent === null || percent === undefined || Number.isNaN(Number(percent))) {
    return null
  }

  return { percent: Number(percent) }
})

const dbUnavailableReason = computed(() => {
  const code = dbData.value?.server?.reason
  // Map machine codes to existing/generic labels; avoid leaking raw errors.
  if ("unsupported_platform" === code) {
    return t("Database load metrics are only available for MySQL and MariaDB.")
  }

  return t("Not available")
})

const wsScannedPathsLabel = computed(() => {
  const paths = wsData.value?.scannedPaths
  if (!Array.isArray(paths) || !paths.length) {
    // Default expected paths before first response (Apache is most common).
    return "/server-status?auto · /nginx_status · /stub_status"
  }

  return paths.join(" · ")
})

const wsDetectedLabel = computed(() => {
  const d = wsData.value?.detected
  if ("apache" === d) {
    return "Apache"
  }
  if ("nginx" === d) {
    return "Nginx"
  }

  return t("Unknown")
})

const wsUnavailableReason = computed(() => {
  const code = wsData.value?.status?.reason
  if ("unsupported_server" === code) {
    return t("Web server load metrics are only available for Apache and Nginx.")
  }
  if ("status_unavailable" === code) {
    return t("None of the scanned status paths responded on localhost.")
  }

  return t("Not available")
})

/**
 * Live rates from consecutive polls (screenshot-friendly quantification over time).
 */
const wsRates = computed(() => {
  const empty = { requestsPerSec: null, bytesPerSec: null }
  const curr = wsData.value?.status
  const prev = wsPrevSample.value?.status
  if (!curr?.available || !prev?.available) {
    return empty
  }

  const currMs = Date.parse(wsData.value?.fetchedAt || "")
  const prevMs = Date.parse(wsPrevSample.value?.fetchedAt || "")
  if (Number.isNaN(currMs) || Number.isNaN(prevMs)) {
    return empty
  }

  const dt = (currMs - prevMs) / 1000
  if (dt <= 0) {
    return empty
  }

  let requestsPerSec = null
  let bytesPerSec = null

  if ("apache" === curr.engine && curr.apache && prev.apache) {
    const cAcc = Number(curr.apache.totalAccesses)
    const pAcc = Number(prev.apache.totalAccesses)
    if (!Number.isNaN(cAcc) && !Number.isNaN(pAcc) && cAcc >= pAcc) {
      requestsPerSec = (cAcc - pAcc) / dt
    }
    const cKb = Number(curr.apache.totalKBytes)
    const pKb = Number(prev.apache.totalKBytes)
    if (!Number.isNaN(cKb) && !Number.isNaN(pKb) && cKb >= pKb) {
      bytesPerSec = ((cKb - pKb) * 1024) / dt
    }
  }

  if ("nginx" === curr.engine && curr.nginx && prev.nginx) {
    const cReq = Number(curr.nginx.requests)
    const pReq = Number(prev.nginx.requests)
    if (!Number.isNaN(cReq) && !Number.isNaN(pReq) && cReq >= pReq) {
      requestsPerSec = (cReq - pReq) / dt
    }
  }

  return { requestsPerSec, bytesPerSec }
})

const wsBusyBar = computed(() => {
  const a = wsData.value?.status?.apache
  if (!a) {
    return null
  }
  const percent = a.workersBusyPercent
  const busy = a.busyWorkers
  const idle = a.idleWorkers
  if (percent === null || percent === undefined || Number.isNaN(Number(percent))) {
    return null
  }
  const total =
    busy !== null && busy !== undefined && idle !== null && idle !== undefined
      ? Number(busy) + Number(idle)
      : null

  return {
    percent: Number(percent),
    busy: busy !== null && busy !== undefined ? Number(busy) : null,
    total,
  }
})

const wsMetrics = computed(() => {
  const status = wsData.value?.status
  if (!status?.available) {
    return []
  }

  if ("apache" === status.engine && status.apache) {
    const a = status.apache
    const sb = a.scoreboard
    const metrics = [
      { label: t("Uptime"), value: formatDuration(a.uptimeSeconds) },
      { label: t("Server MPM"), value: a.serverMpm || "—" },
      { label: t("Busy workers"), value: formatNumber(a.busyWorkers) },
      { label: t("Idle workers"), value: formatNumber(a.idleWorkers) },
      { label: t("Graceful workers"), value: formatNumber(a.gracefulWorkers) },
      { label: t("Requests per second (live)"), value: formatRate(wsRates.value.requestsPerSec) },
      { label: t("Requests per second (since start)"), value: formatRate(a.reqPerSec) },
      { label: t("Bytes per second (live)"), value: formatBytesPerSec(wsRates.value.bytesPerSec) },
      { label: t("Bytes per second (since start)"), value: formatBytesPerSec(a.bytesPerSec) },
      { label: t("Bytes per request"), value: formatBytes(a.bytesPerReq) },
      { label: t("Total accesses"), value: formatNumber(a.totalAccesses) },
      { label: t("Total traffic"), value: formatApacheTotalKBytes(a.totalKBytes) },
      { label: t("CPU load"), value: formatRate(a.cpuLoad) },
      { label: t("Load average (1 min)"), value: formatRate(a.load1) },
      { label: t("Load average (5 min)"), value: formatRate(a.load5) },
      { label: t("Load average (15 min)"), value: formatRate(a.load15) },
    ]
    if (sb) {
      metrics.push(
        { label: t("Scoreboard sending"), value: formatNumber(sb.sending) },
        { label: t("Scoreboard reading"), value: formatNumber(sb.reading) },
        { label: t("Scoreboard keepalive"), value: formatNumber(sb.keepalive) },
        { label: t("Scoreboard waiting"), value: formatNumber(sb.waiting) },
        { label: t("Scoreboard open slots"), value: formatNumber(sb.open) },
      )
    }

    return metrics
  }

  if ("nginx" === status.engine && status.nginx) {
    const n = status.nginx

    return [
      { label: t("Active connections"), value: formatNumber(n.activeConnections) },
      { label: t("Reading"), value: formatNumber(n.reading) },
      { label: t("Writing"), value: formatNumber(n.writing) },
      { label: t("Waiting"), value: formatNumber(n.waiting) },
      { label: t("Requests per second (live)"), value: formatRate(wsRates.value.requestsPerSec) },
      { label: t("Total requests"), value: formatNumber(n.requests) },
      { label: t("Accepts"), value: formatNumber(n.accepts) },
      { label: t("Handled"), value: formatNumber(n.handled) },
    ]
  }

  return []
})

const dbMetrics = computed(() => {
  const s = dbData.value?.server
  if (!s?.available) {
    return []
  }

  const slow = s.slowQueries || {}
  const slowLogRaw = slow.slowQueryLog
  const slowLog =
    slowLogRaw !== null && slowLogRaw !== undefined && String(slowLogRaw).trim() !== ""
      ? String(slowLogRaw).toUpperCase()
      : "—"
  const longTime =
    slow.longQueryTime !== null && slow.longQueryTime !== undefined && String(slow.longQueryTime).trim() !== ""
      ? `${slow.longQueryTime}s`
      : "—"

  // Prefer existing i18n keys where they match: Version, Questions, Hit rate.
  // "Questions" follows MySQL SHOW GLOBAL STATUS naming (client statements).
  return [
    { label: t("Uptime"), value: formatDuration(s.counters?.Uptime) },
    { label: t("Version"), value: s.version || "—" },
    {
      label: t("Queries per second"),
      value: formatRate(dbRates.value.questionsPerSec ?? dbRates.value.queriesPerSec),
    },
    { label: t("Slow queries"), value: formatNumber(slow.count) },
    { label: t("Slow query time limit"), value: longTime },
    { label: t("Slow query log"), value: slowLog },
    { label: t("Slow queries per second"), value: formatRate(dbRates.value.slowQueriesPerSec) },
    { label: t("Threads connected"), value: formatNumber(s.counters?.Threads_connected) },
    { label: t("Threads running"), value: formatNumber(s.counters?.Threads_running) },
    { label: t("Threads cached"), value: formatNumber(s.counters?.Threads_cached) },
    { label: t("Hit rate"), value: formatPercent(s.derived?.bufferPoolHitRatePercent) },
    { label: t("Temporary tables on disk"), value: formatPercent(s.derived?.tmpTablesOnDiskPercent) },
    { label: t("Table lock waits"), value: formatPercent(s.derived?.tableLockWaitPercent) },
    { label: t("Row lock waits"), value: formatNumber(s.counters?.Innodb_row_lock_waits) },
    { label: t("Aborted connections"), value: formatNumber(s.counters?.Aborted_connects) },
    { label: t("Opened tables"), value: formatNumber(s.counters?.Opened_tables) },
    { label: t("Questions"), value: formatNumber(s.counters?.Questions) },
  ]
})

const dbQueryCacheMetrics = computed(() => {
  const s = dbData.value?.server
  if (!s?.queryCache?.available) {
    return []
  }

  const cacheSizeRaw = s.variables?.query_cache_size
  const cacheSize =
    cacheSizeRaw !== null && cacheSizeRaw !== undefined && cacheSizeRaw !== "" && !Number.isNaN(Number(cacheSizeRaw))
      ? Number(cacheSizeRaw)
      : null

  // Reuse Type / Size / Hits / Inserts — the parent heading is "Query cache".
  return [
    { label: t("Type"), value: s.variables?.query_cache_type || "—" },
    { label: t("Size"), value: formatBytes(cacheSize) },
    { label: t("Hits"), value: formatNumber(s.counters?.Qcache_hits) },
    { label: t("Inserts"), value: formatNumber(s.counters?.Qcache_inserts) },
  ]
})

const dbPrivilegeNote = computed(() => {
  const p = dbData.value?.privileges
  if (!p) {
    return null
  }

  let scopeLabel = t("Unknown")
  if (false !== p.resolved) {
    scopeLabel = p.hasGlobalPrivileges ? t("Global") : t("Database")
  }

  const unavailableList = (p.unavailable || [])
    .map((item) => {
      const capability = item?.capability
      if (!capability) {
        return null
      }
      // Technical identifiers: t() returns the key when no translation exists.
      return t(capability)
    })
    .filter(Boolean)
    .join(", ")

  return { scopeLabel, unavailableList }
})

function statusIconClass(status) {
  switch (status) {
    case "ok":
      return "mdi-check-circle text-success"
    case "warning":
      return "mdi-alert text-warning"
    case "error":
      return "mdi-alert-circle text-danger"
    case "info":
    default:
      return "mdi-information text-info"
  }
}

function cacheStatusLabel(block) {
  if (!block) {
    return t("Loading") + "..."
  }
  if (!block.available) {
    return t("Not available")
  }

  return block.enabled ? t("Enabled") : t("Disabled")
}

/**
 * Inline styles for the bar fill.
 *
 * Chamilo's Tailwind config replaces the default color palette, so classes like
 * bg-green-500 do not exist. Dynamic :class utilities can also miss the
 * stylesheet when only generated for statically scanned tokens. RGB CSS
 * variables from the active theme always resolve.
 */
function memoryBarFillStyle(percent) {
  const p = Number(percent)
  const clamped = Number.isNaN(p) ? 0 : Math.min(100, Math.max(0, p))

  let colorVar = "--color-success-base"
  if (clamped >= 90) {
    colorVar = "--color-danger-base"
  } else if (clamped >= 70) {
    colorVar = "--color-warning-base"
  }

  return {
    width: `${clamped}%`,
    backgroundColor: `rgb(var(${colorVar}))`,
    minWidth: clamped > 0 ? "2px" : "0",
  }
}

function courseEditUrl(id) {
  return `/main/admin/course_edit.php?id=${Number(id)}`
}

function formatLastVisit(value) {
  if (!value) {
    return "—"
  }

  try {
    return new Date(value).toLocaleString()
  } catch {
    return value
  }
}

function formatBytes(bytes) {
  if (bytes === null || bytes === undefined || Number.isNaN(Number(bytes))) {
    return "—"
  }
  const n = Number(bytes)
  if (n < 1024) {
    return `${n} B`
  }
  if (n < 1024 * 1024) {
    return `${(n / 1024).toFixed(1)} KB`
  }
  if (n < 1024 * 1024 * 1024) {
    return `${(n / (1024 * 1024)).toFixed(2)} MB`
  }

  return `${(n / (1024 * 1024 * 1024)).toFixed(2)} GB`
}

function formatNumber(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return "—"
  }

  return Number(value).toLocaleString()
}

function formatPercent(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return "—"
  }

  return `${Number(value).toFixed(2)} %`
}

function formatKeysRatio(used, max) {
  if (used === null || used === undefined) {
    return "—"
  }
  if (max === null || max === undefined) {
    return formatNumber(used)
  }

  return `${formatNumber(used)} / ${formatNumber(max)}`
}

function formatYesNo(value) {
  if (value === null || value === undefined) {
    return "—"
  }

  return value ? t("Yes") : t("No")
}

function formatDuration(seconds) {
  if (seconds === null || seconds === undefined || Number.isNaN(Number(seconds))) {
    return "—"
  }

  let remaining = Math.max(0, Math.floor(Number(seconds)))
  const days = Math.floor(remaining / 86400)
  remaining %= 86400
  const hours = Math.floor(remaining / 3600)
  remaining %= 3600
  const minutes = Math.floor(remaining / 60)
  const secs = remaining % 60

  if (days > 0) {
    return `${days}d ${hours}h ${minutes}m`
  }
  if (hours > 0) {
    return `${hours}h ${minutes}m ${secs}s`
  }
  if (minutes > 0) {
    return `${minutes}m ${secs}s`
  }

  return `${secs}s`
}

function formatRate(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return "—"
  }

  const n = Number(value)
  if (n >= 100) {
    return n.toFixed(1)
  }
  if (n >= 10) {
    return n.toFixed(2)
  }

  return n.toFixed(3)
}

function formatBytesPerSec(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return "—"
  }

  return `${formatBytes(value)}/s`
}

function formatApacheTotalKBytes(kbytes) {
  if (kbytes === null || kbytes === undefined || Number.isNaN(Number(kbytes))) {
    return "—"
  }

  return formatBytes(Number(kbytes) * 1024)
}

function selectSection(key) {
  if (key === currentSection.value) {
    return
  }

  router.push({ name: "AdminSystemStatus", query: { section: key } })
}

function stopCacheAutoRefresh() {
  if (cacheRefreshTimer !== null) {
    clearInterval(cacheRefreshTimer)
    cacheRefreshTimer = null
  }
}

function startCacheAutoRefresh() {
  stopCacheAutoRefresh()
  if (!cacheAutoRefresh.value || currentSection.value !== "php" || !phpCacheExpanded.value) {
    return
  }
  cacheRefreshTimer = setInterval(() => {
    loadCacheData({ silent: true })
  }, 5000)
}

function stopDbAutoRefresh() {
  if (dbRefreshTimer !== null) {
    clearInterval(dbRefreshTimer)
    dbRefreshTimer = null
  }
}

function startDbAutoRefresh() {
  stopDbAutoRefresh()
  if (!dbAutoRefresh.value || currentSection.value !== "database" || !dbExpanded.value) {
    return
  }
  dbRefreshTimer = setInterval(() => {
    loadDbData({ silent: true })
  }, 5000)
}

function stopWsAutoRefresh() {
  if (wsRefreshTimer !== null) {
    clearInterval(wsRefreshTimer)
    wsRefreshTimer = null
  }
}

function startWsAutoRefresh() {
  stopWsAutoRefresh()
  if (!wsAutoRefresh.value || currentSection.value !== "webserver" || !wsExpanded.value) {
    return
  }
  wsRefreshTimer = setInterval(() => {
    loadWsData({ silent: true })
  }, 5000)
}

async function loadCacheData({ silent = false } = {}) {
  if (!silent) {
    cacheLoading.value = true
  }

  try {
    const data = await baseService.get("/admin/system-status-cache-data")
    cacheData.value = data
    cacheFetchedAt.value = data.fetchedAt || new Date().toISOString()
  } catch (e) {
    if (!silent) {
      showErrorNotification(e)
    }
  } finally {
    if (!silent) {
      cacheLoading.value = false
    }
  }
}

async function loadDbData({ silent = false } = {}) {
  if (!silent) {
    dbLoading.value = true
  }

  try {
    const data = await baseService.get("/admin/system-status-database-data")
    // Guard against partial / unexpected payloads so the panel never crashes.
    const safe = {
      fetchedAt: data?.fetchedAt || new Date().toISOString(),
      server:
        data?.server && typeof data.server === "object"
          ? data.server
          : { available: false, reason: "status_unavailable" },
      privileges: data?.privileges && typeof data.privileges === "object" ? data.privileges : null,
    }
    if (dbData.value?.server?.available && safe.server?.available) {
      dbPrevSample.value = dbData.value
    } else if (!safe.server?.available) {
      dbPrevSample.value = null
    }
    dbData.value = safe
    dbFetchedAt.value = safe.fetchedAt
  } catch (e) {
    dbPrevSample.value = null
    dbData.value = {
      fetchedAt: new Date().toISOString(),
      server: { available: false, reason: "status_unavailable" },
      privileges: null,
    }
    if (!silent) {
      showErrorNotification(e)
    }
  } finally {
    if (!silent) {
      dbLoading.value = false
    }
  }
}

async function togglePhpCache() {
  phpCacheExpanded.value = !phpCacheExpanded.value

  if (phpCacheExpanded.value) {
    await loadCacheData()
    startCacheAutoRefresh()
  } else {
    stopCacheAutoRefresh()
  }
}

async function toggleDbLoad() {
  dbExpanded.value = !dbExpanded.value

  if (dbExpanded.value) {
    await loadDbData()
    startDbAutoRefresh()
  } else {
    stopDbAutoRefresh()
  }
}

async function loadWsData({ silent = false } = {}) {
  if (!silent) {
    wsLoading.value = true
  }

  try {
    const data = await baseService.get("/admin/system-status-webserver-data")
    const safe = {
      fetchedAt: data?.fetchedAt || new Date().toISOString(),
      detected: data?.detected ?? null,
      software: data?.software ?? null,
      scannedPaths: Array.isArray(data?.scannedPaths) ? data.scannedPaths : [],
      status:
        data?.status && typeof data.status === "object"
          ? data.status
          : { available: false, reason: "status_unavailable" },
    }
    if (wsData.value?.status?.available && safe.status?.available) {
      wsPrevSample.value = wsData.value
    } else if (!safe.status?.available) {
      wsPrevSample.value = null
    }
    wsData.value = safe
    wsFetchedAt.value = safe.fetchedAt
  } catch (e) {
    wsPrevSample.value = null
    wsData.value = {
      fetchedAt: new Date().toISOString(),
      detected: null,
      software: null,
      scannedPaths: [],
      status: { available: false, reason: "status_unavailable" },
    }
    if (!silent) {
      showErrorNotification(e)
    }
  } finally {
    if (!silent) {
      wsLoading.value = false
    }
  }
}

async function toggleWsLoad() {
  wsExpanded.value = !wsExpanded.value

  if (wsExpanded.value) {
    await loadWsData()
    startWsAutoRefresh()
  } else {
    stopWsAutoRefresh()
  }
}

async function loadSection(sectionKey) {
  isLoading.value = true
  errorMessage.value = ""
  stopCacheAutoRefresh()
  stopDbAutoRefresh()
  stopWsAutoRefresh()
  // Reset fold state when navigating sections so live panels start collapsed again.
  phpCacheExpanded.value = false
  cacheAutoRefresh.value = false
  dbExpanded.value = false
  dbAutoRefresh.value = false
  wsExpanded.value = false
  wsAutoRefresh.value = false
  // Clear rate baseline so a stale sample cannot produce a bogus first rate.
  dbPrevSample.value = null
  wsPrevSample.value = null

  try {
    const data = await baseService.get("/admin/system-status-data", {
      section: sectionKey || "chamilo",
    })

    sections.value = data.sections || []
    currentSection.value = data.currentSection || "chamilo"
    rowType.value = data.rowType || "generic"
    rows.value = data.rows || []

    if (currentSection.value !== "php") {
      cacheData.value = null
      cacheFetchedAt.value = null
    }
    if (currentSection.value !== "database") {
      dbData.value = null
      dbFetchedAt.value = null
      dbPrevSample.value = null
    }
    if (currentSection.value !== "webserver") {
      wsData.value = null
      wsFetchedAt.value = null
      wsPrevSample.value = null
    }
  } catch (e) {
    errorMessage.value = t("An unexpected error occurred.")
    showErrorNotification(e)
  } finally {
    isLoading.value = false
  }
}

watch(
  () => route.query.section,
  (section) => {
    loadSection(typeof section === "string" ? section : "chamilo")
  },
  { immediate: true },
)

watch(cacheAutoRefresh, () => {
  startCacheAutoRefresh()
})

watch(dbAutoRefresh, () => {
  startDbAutoRefresh()
})

watch(wsAutoRefresh, () => {
  startWsAutoRefresh()
})

onBeforeUnmount(() => {
  stopCacheAutoRefresh()
  stopDbAutoRefresh()
  stopWsAutoRefresh()
})
</script>
