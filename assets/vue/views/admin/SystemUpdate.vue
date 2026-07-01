<template>
  <section class="space-y-6">
    <div class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <div class="flex items-center gap-3">
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-support-1 text-primary">
              <i class="mdi mdi-shield-check text-2xl" />
            </span>
            <div>
              <h1 class="text-2xl font-semibold tracking-tight text-gray-90">
                {{ t("System update") }}
              </h1>
              <p class="mt-1 max-w-3xl text-body-2 text-gray-50">
                {{ t("Check for available Chamilo updates, verify packages, stage files and run controlled post-update steps.") }}
              </p>
            </div>
          </div>
        </div>

        <span class="inline-flex items-center gap-2 rounded-full border border-primary bg-support-1 px-3 py-1 text-caption font-semibold text-primary">
          <i class="mdi mdi-lock-check-outline" />
          {{ t("File apply available") }}
        </span>
      </div>

      <dl class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-20 bg-support-2 p-4">
          <dt class="flex items-center gap-2 text-caption font-semibold uppercase tracking-wide text-gray-50">
            <i class="mdi mdi-tag-outline text-primary" />
            {{ t("Installed version") }}
          </dt>
          <dd class="mt-2 text-body-2 font-semibold text-gray-90">
            {{ status.installedVersion || t("Unknown") }}
          </dd>
        </div>

        <div class="rounded-2xl border border-gray-20 bg-support-2 p-4">
          <dt class="flex items-center gap-2 text-caption font-semibold uppercase tracking-wide text-gray-50">
            <i class="mdi mdi-download-box-outline text-primary" />
            {{ t("Update directory") }}
          </dt>
          <dd class="mt-2 break-all font-mono text-caption text-gray-90">
            {{ status.updateDirectory || "var/update/downloads" }}
          </dd>
        </div>

        <div class="rounded-2xl border border-gray-20 bg-support-2 p-4">
          <dt class="flex items-center gap-2 text-caption font-semibold uppercase tracking-wide text-gray-50">
            <i class="mdi mdi-archive-arrow-up-outline text-primary" />
            {{ t("Staging directory") }}
          </dt>
          <dd class="mt-2 break-all font-mono text-caption text-gray-90">
            {{ status.stagingDirectory || "var/update/staging" }}
          </dd>
        </div>

        <div class="rounded-2xl border border-gray-20 bg-support-2 p-4">
          <dt class="flex items-center gap-2 text-caption font-semibold uppercase tracking-wide text-gray-50">
            <i class="mdi mdi-key-variant text-primary" />
            {{ t("Trusted signing key") }}
          </dt>
          <dd class="mt-2 text-body-2 font-semibold text-gray-90">
            {{ status.trustedPublicKeyConfigured ? t("Configured") : t("Not configured") }}
          </dd>
          <dd
            v-if="status.trustedPublicKeyFingerprint"
            class="mt-1 break-all font-mono text-caption text-gray-50"
          >
            {{ status.trustedPublicKeyFingerprint }}
          </dd>
        </div>

        <div class="rounded-2xl border border-gray-20 bg-support-2 p-4">
          <dt class="flex items-center gap-2 text-caption font-semibold uppercase tracking-wide text-gray-50">
            <i class="mdi mdi-test-tube text-primary" />
            {{ t("Local test options") }}
          </dt>
          <dd class="mt-2 text-body-2 font-semibold text-gray-90">
            {{ status.allowLocalPaths ? t("Enabled") : t("Disabled") }}
          </dd>
          <dd class="mt-1 text-caption text-gray-50">
            {{ status.productionMode ? t("Production mode") : t("Development mode") }}
          </dd>
        </div>
      </dl>
    </div>

    <div
      v-if="updateEntryNotice"
      class="rounded-3xl border p-5 shadow-sm"
      :class="updateEntryNotice.severity === 'warning' ? 'border-warning bg-white' : 'border-primary bg-support-1'"
    >
      <div class="flex items-start gap-3">
        <span
          class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
          :class="updateEntryNotice.severity === 'warning' ? 'bg-support-2 text-warning' : 'bg-white text-primary'"
        >
          <i
            class="mdi text-xl"
            :class="updateEntryNotice.severity === 'warning' ? 'mdi-alert-outline' : 'mdi-update'"
          />
        </span>
        <div>
          <h2 class="text-body-1 font-semibold text-gray-90">
            {{ updateEntryNotice.title }}
          </h2>
          <p class="mt-1 text-body-2 text-gray-50">
            {{ updateEntryNotice.description }}
          </p>
          <p
            v-if="form.manifestSource"
            class="mt-2 break-all font-mono text-caption text-gray-90"
          >
            {{ form.manifestSource }}
          </p>
        </div>
      </div>
    </div>

    <div
      v-if="localTestUpdateEntryPath"
      class="rounded-3xl border border-gray-20 bg-support-2 p-5 shadow-sm"
    >
      <div class="flex items-start gap-3">
        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-support-1 text-primary">
          <i class="mdi mdi-link-variant text-xl" />
        </span>
        <div>
          <h2 class="text-body-1 font-semibold text-gray-90">
            {{ t("Local test update notice link") }}
          </h2>
          <p class="mt-1 text-body-2 text-gray-50">
            {{ t("Use this link to simulate the future Chamilo.org update notice during development tests.") }}
          </p>
          <code class="mt-2 block break-all rounded-xl border border-gray-20 bg-white px-3 py-2 font-mono text-caption text-gray-90">
            {{ localTestUpdateEntryPath }}
          </code>
        </div>
      </div>
    </div>

    <div class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm">
      <div class="flex items-center gap-3">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-support-1 text-primary">
          <i class="mdi mdi-file-search-outline text-xl" />
        </span>
        <h2 class="text-xl font-semibold text-gray-90">
          {{ t("Manifest") }}
        </h2>
      </div>

      <div class="mt-5 space-y-4">
        <label class="block">
          <span class="text-body-2 font-semibold text-gray-90">{{ t("Manifest source") }}</span>
          <input
            v-model.trim="form.manifestSource"
            class="mt-2 w-full rounded-xl border border-gray-25 px-3 py-2 text-body-2 text-gray-90 shadow-sm focus:border-primary focus:ring-primary"
            :placeholder="manifestSourcePlaceholder"
            type="text"
          />
          <span class="mt-2 block text-caption text-gray-50">
            {{ manifestSourceHelp }}
          </span>
        </label>

        <BaseButton
          :disabled="isChecking || !form.manifestSource"
          :is-loading="isChecking"
          :label="isChecking ? t('Checking...') : t('Check manifest')"
          icon="search"
          type="primary"
          @click="checkManifest"
        />
      </div>

      <div
        v-if="manifest"
        class="mt-6 overflow-hidden rounded-2xl border border-gray-20 bg-white"
      >
        <dl class="divide-y divide-gray-20">
          <div
            v-for="item in manifestRows"
            :key="item.label"
            class="grid gap-1 px-4 py-3 md:grid-cols-4 md:gap-4"
          >
            <dt class="text-body-2 font-semibold text-gray-50">
              {{ item.label }}
            </dt>
            <dd
              class="text-body-2 text-gray-90 md:col-span-3"
              :class="{ 'break-all font-mono text-caption': item.mono }"
            >
              {{ item.value }}
            </dd>
          </div>
        </dl>
      </div>

      <div
        v-if="availability"
        class="mt-4 rounded-2xl border p-4"
        :class="availabilityClass"
      >
        <div class="flex items-start gap-3">
          <span class="mdi mt-0.5 text-xl" :class="availabilityIconClass" />
          <div>
            <h3 class="text-body-1 font-semibold">
              {{ t("Update availability") }}
            </h3>
            <p class="mt-1 text-body-2">
              {{ availability.message }}
            </p>
            <dl class="mt-3 grid gap-2 text-caption md:grid-cols-3">
              <div>
                <dt class="font-semibold">{{ t("Installed version") }}</dt>
                <dd>{{ availability.installedVersion }}</dd>
              </div>
              <div>
                <dt class="font-semibold">{{ t("Target version") }}</dt>
                <dd>{{ availability.targetVersion }}</dd>
              </div>
              <div>
                <dt class="font-semibold">{{ t("Next step") }}</dt>
                <dd>{{ availability.nextStep }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <div class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm">
      <div class="flex items-center gap-3">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-support-1 text-primary">
          <i class="mdi mdi-shield-check-outline text-xl" />
        </span>
        <div>
          <h2 class="text-xl font-semibold text-gray-90">
            {{ t("Package verification") }}
          </h2>
          <p class="mt-1 text-body-2 text-gray-50">
            {{ t("Leave package and signature paths empty to download them from the verified manifest URLs. Local paths are only available for development tests.") }}
          </p>
        </div>
      </div>

      <div
        v-if="status.allowLocalPaths"
        class="mt-5 grid gap-4 md:grid-cols-2"
      >
        <label class="block">
          <span class="text-body-2 font-semibold text-gray-90">{{ t("Package path") }}</span>
          <input
            v-model.trim="form.packagePath"
            class="mt-2 w-full rounded-xl border border-gray-25 px-3 py-2 text-body-2 text-gray-90 shadow-sm focus:border-primary focus:ring-primary"
            placeholder="/tmp/chamilo-update.zip"
            type="text"
          />
        </label>

        <label class="block">
          <span class="text-body-2 font-semibold text-gray-90">{{ t("Signature path") }}</span>
          <input
            v-model.trim="form.signaturePath"
            class="mt-2 w-full rounded-xl border border-gray-25 px-3 py-2 text-body-2 text-gray-90 shadow-sm focus:border-primary focus:ring-primary"
            placeholder="/tmp/chamilo-update.zip.minisig"
            type="text"
          />
        </label>

        <label
          v-if="showTrustedPublicKeyInput"
          class="block md:col-span-2"
        >
          <span class="text-body-2 font-semibold text-gray-90">{{ t("Trusted public key") }}</span>
          <input
            v-model.trim="form.trustedPublicKey"
            class="mt-2 w-full rounded-xl border border-gray-25 px-3 py-2 text-body-2 text-gray-90 shadow-sm focus:border-primary focus:ring-primary"
            placeholder="RW..."
            type="text"
          />
          <span class="mt-2 block text-caption text-gray-50">
            {{ t("Use this only for local development tests. Production updates must use the server configured key.") }}
          </span>
        </label>
      </div>

      <div
        v-else
        class="mt-5 rounded-2xl border border-gray-20 bg-support-2 p-4 text-body-2 text-gray-50"
      >
        <span class="mdi mdi-download-lock-outline mr-2 text-primary" />
        {{ t("Package and signature files will be downloaded from the manifest. Local paths are disabled in this environment.") }}
      </div>

      <label
        v-if="status.allowSkipSignature"
        class="mt-4 flex items-center gap-2 text-body-2 text-gray-90"
      >
        <input
          v-model="form.skipSignature"
          class="rounded border-gray-25 text-primary focus:ring-primary"
          type="checkbox"
        />
        <span>{{ t("Skip signature verification for local development tests") }}</span>
      </label>

      <p
        v-else
        class="mt-4 rounded-2xl border border-gray-20 bg-support-2 p-3 text-caption text-gray-50"
      >
        <span class="mdi mdi-shield-lock-outline mr-1 text-primary" />
        {{ t("Signature verification cannot be skipped in this environment.") }}
      </p>

      <div class="mt-4">
        <BaseButton
          :disabled="isVerifying || !canVerifyPackage"
          :is-loading="isVerifying"
          :label="isVerifying ? t('Verifying...') : t('Verify package')"
          icon="shield-check"
          type="primary"
          @click="verifyPackage"
        />
      </div>

      <ResultPanel
        v-if="verification"
        :details-label="t('Verification details')"
        :details-text="formattedVerificationDetails"
        :errors="verification.result.errors"
        :title="verification.result.valid ? t('Package verified successfully') : t('Package verification failed')"
        :valid="verification.result.valid"
        :warnings="verification.result.warnings"
      />
    </div>

    <div class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm">
      <div class="flex items-center gap-3">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-support-1 text-primary">
          <i class="mdi mdi-clipboard-pulse-outline text-xl" />
        </span>
        <div>
          <h2 class="text-xl font-semibold text-gray-90">
            {{ t("Preflight checks") }}
          </h2>
          <p class="mt-1 text-body-2 text-gray-50">
            {{ t("Run environment checks before any future update application step. This does not modify files or the database.") }}
          </p>
        </div>
      </div>

      <div class="mt-4">
        <BaseButton
          :disabled="isRunningPreflight || !form.manifestSource"
          :is-loading="isRunningPreflight"
          :label="isRunningPreflight ? t('Checking...') : t('Run preflight checks')"
          icon="health-check"
          type="primary"
          @click="runPreflight"
        />
      </div>

      <ResultPanel
        v-if="preflight"
        :details-label="t('Preflight details')"
        :details-text="formattedPreflightDetails"
        :errors="preflight.result.errors"
        :title="preflight.result.valid ? t('Preflight checks completed') : t('Preflight checks failed')"
        :valid="preflight.result.valid"
        :warnings="preflight.result.warnings"
      >
        <CheckTable
          :checks="preflight.result.checks"
          :status-class="getCheckStatusClass"
          :status-icon-class="getCheckStatusIconClass"
        />
      </ResultPanel>
    </div>

    <div class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm">
      <div class="flex items-center gap-3">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-support-1 text-primary">
          <i class="mdi mdi-archive-arrow-up-outline text-xl" />
        </span>
        <div>
          <h2 class="text-xl font-semibold text-gray-90">
            {{ t("Staging extraction") }}
          </h2>
          <p class="mt-1 text-body-2 text-gray-50">
            {{ t("Extract the verified update package into a staging directory for inspection. This does not replace files, run migrations, or modify the database.") }}
          </p>
        </div>
      </div>

      <div class="mt-4">
        <BaseButton
          :disabled="isStaging || !form.manifestSource"
          :is-loading="isStaging"
          :label="isStaging ? t('Preparing staging...') : t('Prepare staging')"
          icon="zip-unpack"
          type="primary"
          @click="stagePackage"
        />
      </div>

      <ResultPanel
        v-if="staging"
        :details-label="t('Staging details')"
        :details-text="formattedStagingDetails"
        :errors="staging.staging.errors"
        :title="staging.staging.valid ? t('Package staged successfully') : t('Package staging failed')"
        :valid="staging.staging.valid"
        :warnings="staging.staging.warnings"
      >
        <dl
          v-if="staging.staging.valid"
          class="mb-4 grid gap-3 text-body-2 md:grid-cols-2"
        >
          <div class="rounded-2xl border border-gray-20 bg-support-2 p-4">
            <dt class="font-semibold text-gray-50">{{ t("Staging directory") }}</dt>
            <dd class="mt-1 break-all font-mono text-caption text-gray-90">{{ staging.staging.stagingPath }}</dd>
          </div>

          <div class="rounded-2xl border border-gray-20 bg-support-2 p-4">
            <dt class="font-semibold text-gray-50">{{ t("Application path") }}</dt>
            <dd class="mt-1 break-all font-mono text-caption text-gray-90">{{ staging.staging.applicationPath }}</dd>
          </div>
        </dl>

        <CheckTable
          v-if="staging.staging.checks.length"
          :checks="staging.staging.checks"
          :status-class="getCheckStatusClass"
          :status-icon-class="getCheckStatusIconClass"
        />
      </ResultPanel>
    </div>


    <div class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm">
      <div class="flex items-center gap-3">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-support-1 text-primary">
          <i class="mdi mdi-file-document-check-outline text-xl" />
        </span>
        <div>
          <h2 class="text-xl font-semibold text-gray-90">
            {{ t("Apply preparation") }}
          </h2>
          <p class="mt-1 text-body-2 text-gray-50">
            {{ t("Build a safe apply plan from the staged package. This only checks files, permissions, backup path and lock status. It does not replace files.") }}
          </p>
        </div>
      </div>

      <div class="mt-5 space-y-2">
        <label
          class="block text-body-2 font-semibold text-gray-90"
          for="system-update-staging-path"
        >
          {{ t("Staging directory") }}
        </label>
        <input
          id="system-update-staging-path"
          v-model="form.stagingPath"
          class="w-full rounded-2xl border border-gray-25 px-4 py-3 text-body-2 text-gray-90 shadow-sm focus:border-primary focus:outline-none"
          type="text"
        />
        <p class="text-caption text-gray-50">
          {{ t("Use the staging directory generated by the previous step. The backend only accepts directories inside var/update/staging.") }}
        </p>
      </div>

      <div class="mt-4 rounded-2xl border border-warning bg-support-2 p-3 text-caption text-warning">
        <span class="mdi mdi-alert-outline mr-1" />
        {{ t("This step prepares a review only. File replacement happens later in the Apply staged files step, with lock, backup and rollback protection.") }}
      </div>

      <div class="mt-4">
        <BaseButton
          :disabled="isPlanningApply || !form.stagingPath"
          :is-loading="isPlanningApply"
          :label="isPlanningApply ? t('Building plan...') : t('Review apply plan')"
          icon="file-search"
          type="primary"
          @click="buildApplyPlan"
        />
      </div>

      <ResultPanel
        v-if="applyPlan"
        :details-label="t('Apply plan details')"
        :details-text="formattedApplyPlanDetails"
        :errors="applyPlan.applyPlan.errors"
        :title="applyPlan.applyPlan.valid ? t('Apply plan ready') : t('Apply plan failed')"
        :valid="applyPlan.applyPlan.valid"
        :warnings="applyPlan.applyPlan.warnings"
      >
        <dl
          v-if="applyPlan.applyPlan.valid"
          class="mb-4 grid gap-3 text-body-2 md:grid-cols-2 xl:grid-cols-4"
        >
          <div class="rounded-2xl border border-gray-20 bg-support-2 p-4">
            <dt class="font-semibold text-gray-50">{{ t("Files to replace") }}</dt>
            <dd class="mt-1 font-mono text-caption text-gray-90">{{ applyPlanFilePlan.files_to_replace || 0 }}</dd>
          </div>

          <div class="rounded-2xl border border-gray-20 bg-support-2 p-4">
            <dt class="font-semibold text-gray-50">{{ t("New files") }}</dt>
            <dd class="mt-1 font-mono text-caption text-gray-90">{{ applyPlanFilePlan.files_new || 0 }}</dd>
          </div>

          <div class="rounded-2xl border border-gray-20 bg-support-2 p-4">
            <dt class="font-semibold text-gray-50">{{ t("Planned backup path") }}</dt>
            <dd class="mt-1 break-all font-mono text-caption text-gray-90">{{ applyPlan.applyPlan.backupPath }}</dd>
          </div>

          <div class="rounded-2xl border border-gray-20 bg-support-2 p-4">
            <dt class="font-semibold text-gray-50">{{ t("Update lock path") }}</dt>
            <dd class="mt-1 break-all font-mono text-caption text-gray-90">{{ applyPlan.applyPlan.lockPath }}</dd>
          </div>
        </dl>

        <CheckTable
          v-if="applyPlan.applyPlan.checks.length"
          :checks="applyPlan.applyPlan.checks"
          :status-class="getCheckStatusClass"
          :status-icon-class="getCheckStatusIconClass"
        />
      </ResultPanel>
    </div>

    <div class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm">
      <div class="flex items-center gap-3">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-support-1 text-primary">
          <i class="mdi mdi-file-replace-outline text-xl" />
        </span>
        <div>
          <h2 class="text-xl font-semibold text-gray-90">
            {{ t("Apply staged files") }}
          </h2>
          <p class="mt-1 text-body-2 text-gray-50">
            {{ t("Replace Chamilo files from the staged package using the reviewed apply plan. This does not run database migrations, Composer, Yarn or cache commands.") }}
          </p>
        </div>
      </div>

      <div class="mt-4 rounded-2xl border border-danger bg-white p-4 text-body-2 text-danger">
        <span class="mdi mdi-alert-outline mr-1" />
        {{ t("This action modifies files in the Chamilo installation. Make sure the apply plan was reviewed and backups are enabled before continuing.") }}
      </div>

      <div class="mt-5 space-y-4">
        <label class="flex items-start gap-3 text-body-2 text-gray-90">
          <input
            v-model="form.confirmApply"
            class="mt-1 rounded border-gray-25 text-danger focus:ring-danger"
            type="checkbox"
          />
          <span>
            {{ t("I understand this will replace files in the Chamilo installation.") }}
          </span>
        </label>

        <label class="block">
          <span class="text-body-2 font-semibold text-gray-90">
            {{ t("Type APPLY UPDATE FILES to confirm") }}
          </span>
          <input
            v-model.trim="form.confirmationText"
            class="mt-2 w-full rounded-xl border border-gray-25 px-3 py-2 font-mono text-body-2 text-gray-90 shadow-sm focus:border-danger focus:ring-danger"
            placeholder="APPLY UPDATE FILES"
            type="text"
          />
        </label>

        <BaseButton
          :disabled="isApplyingFiles || !canApplyFiles"
          :is-loading="isApplyingFiles"
          :label="isApplyingFiles ? t('Applying files...') : t('Apply staged files')"
          icon="file-replace-outline"
          type="primary"
          @click="applyUpdateFiles"
        />
      </div>

      <div
        v-if="applyOperationId || applyProgressEvents.length"
        class="mt-6 overflow-hidden rounded-2xl border border-gray-20 bg-gray-90 shadow-sm"
      >
        <div class="flex items-center justify-between border-b border-gray-50 px-4 py-3">
          <div class="flex items-center gap-2 text-body-2 font-semibold text-white">
            <span class="mdi mdi-console-line text-lg text-info" />
            {{ t("Live update log") }}
          </div>
          <span class="font-mono text-caption text-gray-25">
            {{ applyOperationId }}
          </span>
        </div>

        <div class="max-h-80 overflow-auto p-4 font-mono text-caption">
          <div
            v-if="!applyProgressEvents.length"
            class="text-gray-25"
          >
            {{ t("Waiting for update progress events...") }}
          </div>

          <div
            v-for="(event, index) in applyProgressEvents"
            :key="`${event.time}-${event.step}-${index}`"
            class="grid gap-2 py-1 md:grid-cols-[5.5rem_5rem_8rem_1fr]"
          >
            <span class="text-gray-25">{{ formatOperationTime(event.time) }}</span>
            <span
              class="font-semibold uppercase"
              :class="getOperationLevelClass(event.level)"
            >
              {{ event.level }}
            </span>
            <span class="text-gray-25">{{ event.step }}</span>
            <span class="text-white">{{ event.message }}</span>
          </div>
        </div>
      </div>

      <ResultPanel
        v-if="applyFilesResult"
        :details-label="t('Apply files details')"
        :details-text="formattedApplyFilesDetails"
        :errors="applyFilesResult.applyFiles.errors"
        :title="applyFilesResult.applyFiles.valid ? t('Staged files applied successfully') : t('Staged file application failed')"
        :valid="applyFilesResult.applyFiles.valid"
        :warnings="applyFilesResult.applyFiles.warnings"
      >
        <dl
          v-if="applyFilesResult.applyFiles.valid"
          class="mb-4 grid gap-3 text-body-2 md:grid-cols-2"
        >
          <div class="rounded-2xl border border-gray-20 bg-support-2 p-4">
            <dt class="font-semibold text-gray-50">{{ t("Backup directory") }}</dt>
            <dd class="mt-1 break-all font-mono text-caption text-gray-90">{{ applyFilesResult.applyFiles.backupPath }}</dd>
          </div>

          <div class="rounded-2xl border border-gray-20 bg-support-2 p-4">
            <dt class="font-semibold text-gray-50">{{ t("Audit file") }}</dt>
            <dd class="mt-1 break-all font-mono text-caption text-gray-90">{{ applyFilesResult.applyFiles.auditPath }}</dd>
          </div>
        </dl>

        <CheckTable
          v-if="applyFilesResult.applyFiles.checks.length"
          :checks="applyFilesResult.applyFiles.checks"
          :status-class="getCheckStatusClass"
          :status-icon-class="getCheckStatusIconClass"
        />
      </ResultPanel>
    </div>

    <div class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm">
      <div class="flex items-center gap-3">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-support-1 text-primary">
          <i class="mdi mdi-clipboard-check-outline text-xl" />
        </span>
        <div>
          <h2 class="text-xl font-semibold text-gray-90">
            {{ t("Post-apply checks") }}
          </h2>
          <p class="mt-1 text-body-2 text-gray-50">
            {{ t("Review recommended manual commands after applying update files. This step does not run Composer, Yarn, migrations or cache commands.") }}
          </p>
        </div>
      </div>

      <div class="mt-4 rounded-2xl border border-info bg-support-2 p-3 text-caption text-info">
        <span class="mdi mdi-information-outline mr-1" />
        {{ t("Run these commands manually from the server after confirming the file update result and backups.") }}
      </div>

      <div class="mt-4">
        <BaseButton
          :disabled="isCheckingPostApply || !form.stagingPath || !applyFilesResult?.applyFiles?.valid"
          :is-loading="isCheckingPostApply"
          :label="isCheckingPostApply ? t('Checking post-apply actions...') : t('Review post-apply actions')"
          icon="clipboard-check-outline"
          type="primary"
          @click="runPostApplyChecks"
        />
      </div>

      <ResultPanel
        v-if="postApplyChecks"
        :details-label="t('Post-apply details')"
        :details-text="formattedPostApplyDetails"
        :errors="postApplyChecks.postApply.errors"
        :title="postApplyChecks.postApply.valid ? t('Post-apply actions ready') : t('Post-apply checks failed')"
        :valid="postApplyChecks.postApply.valid"
        :warnings="postApplyChecks.postApply.warnings"
      >
        <div
          v-if="postApplyActions.length"
          class="mb-4 space-y-3"
        >
          <div
            v-for="action in postApplyActions"
            :key="action.key"
            class="rounded-2xl border border-gray-20 bg-white p-4"
          >
            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
              <div>
                <h3 class="text-body-2 font-semibold text-gray-90">
                  {{ action.title }}
                </h3>
                <p class="mt-1 text-caption text-gray-50">
                  {{ action.description }}
                </p>
              </div>
              <span
                class="inline-flex w-fit rounded-full border px-2.5 py-1 text-caption font-semibold uppercase"
                :class="getActionSeverityClass(action.severity)"
              >
                {{ action.severity }}
              </span>
            </div>

            <div class="mt-3 space-y-2">
              <code
                v-for="command in action.commands"
                :key="command"
                class="block break-all rounded-xl border border-gray-20 bg-support-2 px-3 py-2 font-mono text-caption text-gray-90"
              >
                {{ command }}
              </code>
            </div>
          </div>
        </div>

        <CheckTable
          v-if="postApplyChecks.postApply.checks.length"
          :checks="postApplyChecks.postApply.checks"
          :status-class="getCheckStatusClass"
          :status-icon-class="getCheckStatusIconClass"
        />
      </ResultPanel>

      <div
        v-if="canReviewMigrationSafety"
        class="mt-6 rounded-2xl border border-warning bg-support-2 p-4"
      >
        <div class="flex items-start gap-3">
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white text-warning">
            <i class="mdi mdi-database-alert-outline text-xl" />
          </span>
          <div>
            <h3 class="text-body-1 font-semibold text-gray-90">
              {{ t("Database migration safety review") }}
            </h3>
            <p class="mt-1 text-body-2 text-gray-90">
              {{ t("Review staged Doctrine migrations and dry-run output before running database migrations.") }}
            </p>
          </div>
        </div>

        <div class="mt-4 rounded-2xl border border-danger bg-white p-3 text-caption font-semibold text-danger">
          <span class="mdi mdi-alert-outline mr-1" />
          {{ t("This updater does not create a database backup. Create and verify a backup before running migrations.") }}
        </div>

        <div class="mt-4">
          <BaseButton
            :disabled="isCheckingMigrationSafety || !canReviewMigrationSafety"
            :is-loading="isCheckingMigrationSafety"
            :label="isCheckingMigrationSafety ? t('Reviewing database migrations...') : t('Review database migrations')"
            icon="database-search-outline"
            type="primary"
            @click="runMigrationSafetyChecks"
          />
        </div>

        <ResultPanel
          v-if="migrationSafety"
          :details-label="t('Migration safety details')"
          :details-text="formattedMigrationSafetyDetails"
          :errors="migrationSafety.migrationSafety.errors"
          :title="migrationSafety.migrationSafety.valid ? t('Migration safety review ready') : t('Migration safety review failed')"
          :valid="migrationSafety.migrationSafety.valid"
          :warnings="migrationSafety.migrationSafety.warnings"
        >
          <div
            v-if="migrationSafety.migrationSafety.migrations.length"
            class="mb-4 space-y-3"
          >
            <div
              v-for="migration in migrationSafety.migrationSafety.migrations"
              :key="migration.class"
              class="rounded-2xl border border-warning bg-white p-4"
            >
              <h4 class="break-all text-body-2 font-semibold text-gray-90">
                {{ migration.class }}
              </h4>
              <p class="mt-1 break-all font-mono text-caption text-gray-90">
                {{ migration.path }}
              </p>
              <p class="mt-2 text-caption text-gray-90">
                {{ migration.description }}
              </p>
            </div>
          </div>


          <div
            v-if="migrationSafetyTarget"
            class="mb-4 rounded-2xl border border-info bg-white p-4"
          >
            <h4 class="text-body-2 font-semibold text-gray-90">
              {{ t("Migration target") }}
            </h4>
            <p class="mt-1 text-caption text-gray-90">
              {{ t("Doctrine will execute only the staged V210 migrations explicitly after the safety review passes.") }}
            </p>
            <code class="mt-2 block break-all rounded-xl border border-gray-20 bg-support-2 px-3 py-2 font-mono text-caption text-gray-90">
              {{ migrationSafetyTarget }}
            </code>
          </div>

          <div
            v-if="migrationSafetyBaseline"
            class="mb-4 rounded-2xl border border-gray-20 bg-white p-4"
          >
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
              <div>
                <h4 class="text-body-2 font-semibold text-gray-90">
                  {{ t("Database baseline") }}
                </h4>
                <p class="mt-1 text-caption text-gray-90">
                  {{
                    migrationSafetyBaseline.clean
                      ? t("The database migration baseline is clean.")
                      : t("Doctrine reported historical baseline warnings. The updater will still execute only staged V210 migrations after explicit confirmation.")
                  }}
                </p>
              </div>
              <span
                class="inline-flex w-fit rounded-full border px-2.5 py-1 text-caption font-semibold uppercase"
                :class="migrationSafetyBaseline.clean ? 'border-success text-success' : 'border-warning text-warning'"
              >
                {{ migrationSafetyBaseline.clean ? t("Baseline clean") : t("Baseline warnings") }}
              </span>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
              <div class="rounded-xl border border-gray-20 bg-support-2 p-3">
                <p class="text-caption font-semibold uppercase text-gray-50">
                  {{ t("Current migration") }}
                </p>
                <p class="mt-1 break-all font-mono text-caption text-gray-90">
                  {{ migrationSafetyBaseline.current || t("Not detected") }}
                </p>
              </div>
              <div class="rounded-xl border border-gray-20 bg-support-2 p-3">
                <p class="text-caption font-semibold uppercase text-gray-50">
                  {{ t("Next migration") }}
                </p>
                <p class="mt-1 break-all font-mono text-caption text-gray-90">
                  {{ migrationSafetyBaseline.next || t("Not detected") }}
                </p>
              </div>
              <div class="rounded-xl border border-gray-20 bg-support-2 p-3">
                <p class="text-caption font-semibold uppercase text-gray-50">
                  {{ t("Latest migration") }}
                </p>
                <p class="mt-1 break-all font-mono text-caption text-gray-90">
                  {{ migrationSafetyBaseline.latest || t("Not detected") }}
                </p>
              </div>
              <div class="rounded-xl border border-gray-20 bg-support-2 p-3">
                <p class="text-caption font-semibold uppercase text-gray-50">
                  {{ t("New migrations reported by Doctrine") }}
                </p>
                <p class="mt-1 font-mono text-caption text-gray-90">
                  {{ migrationSafetyBaseline.new_count ?? t("Unknown") }}
                </p>
              </div>
            </div>

            <div
              v-if="migrationSafetyExecutedUnavailable.length"
              class="mt-4 rounded-xl border border-danger bg-support-2 p-3"
            >
              <p class="text-body-2 font-semibold text-danger">
                {{ t("Executed unavailable migrations") }}
              </p>
              <ul class="mt-2 list-disc space-y-1 pl-5 font-mono text-caption text-gray-90">
                <li
                  v-for="migration in migrationSafetyExecutedUnavailable"
                  :key="migration"
                  class="break-all"
                >
                  {{ migration }}
                </li>
              </ul>
            </div>
            <div
              v-else
              class="mt-4 rounded-xl border border-success bg-support-2 p-3 text-caption font-semibold text-gray-90"
            >
              {{ t("No executed unavailable migration was reported.") }}
            </div>

            <div
              v-if="migrationSafetyPendingBeforeTarget.length"
              class="mt-4 rounded-xl border border-danger bg-support-2 p-3"
            >
              <p class="text-body-2 font-semibold text-danger">
                {{ t("Pending migrations before this update") }}
              </p>
              <ul class="mt-2 list-disc space-y-1 pl-5 font-mono text-caption text-gray-90">
                <li
                  v-for="migration in migrationSafetyPendingBeforeTarget"
                  :key="migration.class"
                  class="break-all"
                >
                  {{ migration.class }} — {{ migration.status }}
                </li>
              </ul>
            </div>
            <div
              v-else
              class="mt-4 rounded-xl border border-success bg-support-2 p-3 text-caption font-semibold text-gray-90"
            >
              {{ t("No pending migration was detected before the staged update target.") }}
            </div>
          </div>

          <div
            v-if="migrationSafety.migrationSafety.dryRunCommand"
            class="mb-4 rounded-2xl border border-gray-20 bg-white p-4"
          >
            <h4 class="text-body-2 font-semibold text-gray-90">
              {{ t("Doctrine dry-run command") }}
            </h4>
            <code class="mt-2 block break-all rounded-xl border border-gray-20 bg-support-2 px-3 py-2 font-mono text-caption text-gray-90">
              {{ migrationSafety.migrationSafety.dryRunCommand }}
            </code>
            <p class="mt-2 text-caption font-semibold text-gray-90">
              {{ t("Dry-run exit code") }}: {{ migrationSafety.migrationSafety.dryRunExitCode }}
            </p>
            <pre
              v-if="migrationSafety.migrationSafety.dryRunOutput"
              class="mt-3 max-h-96 overflow-auto rounded-xl border border-gray-20 bg-gray-90 p-3 text-caption text-white"
            >{{ migrationSafety.migrationSafety.dryRunOutput }}</pre>
          </div>

          <CheckTable
            v-if="migrationSafety.migrationSafety.checks.length"
            :checks="migrationSafety.migrationSafety.checks"
            :status-class="getCheckStatusClass"
            :status-icon-class="getCheckStatusIconClass"
          />
        </ResultPanel>
      </div>

      <div
        v-if="postApplyChecks?.postApply?.valid"
        class="mt-6 rounded-2xl border border-gray-20 bg-support-2 p-4"
      >
        <div class="flex items-start gap-3">
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white text-primary">
            <i class="mdi mdi-play-circle-outline text-xl" />
          </span>
          <div>
            <h3 class="text-body-1 font-semibold text-gray-90">
              {{ t("Run post-apply actions") }}
            </h3>
            <p class="mt-1 text-body-2 text-gray-50">
              {{ t("Run selected post-apply commands from the server using a controlled allowlist. No custom shell commands are accepted.") }}
            </p>
          </div>
        </div>

        <div
          v-if="!status.allowUiPostApplyCommands"
          class="mt-4 rounded-2xl border border-warning bg-support-2 p-3 text-caption font-semibold text-gray-90"
        >
          <span class="mdi mdi-alert-outline mr-1" />
          {{ t("Running post-apply actions from the UI is disabled in production mode.") }}
        </div>

        <div
          v-else
          class="mt-4 space-y-4"
        >
          <div class="rounded-2xl border border-danger bg-white p-3 text-caption text-danger">
            <span class="mdi mdi-alert-outline mr-1" />
            {{ t("These commands can modify dependencies, generated assets, cache or the database. Confirm backups before continuing.") }}
          </div>

          <div class="space-y-4">
            <div>
              <p class="text-body-2 font-semibold text-gray-90">
                {{ t("Safe actions") }}
              </p>
              <p class="mt-1 text-caption text-gray-50">
                {{ t("These actions are safe to run from the UI after reviewing the update result.") }}
              </p>

              <div class="mt-2 space-y-2">
                <label
                  v-for="action in postApplySafeExecutableActions"
                  :key="action.key"
                  class="flex items-start gap-3 rounded-2xl border border-success bg-white p-3 text-body-2 text-gray-90"
                >
                  <input
                    v-model="form.selectedPostApplyActionKeys"
                    :value="action.key"
                    class="mt-1 rounded border-gray-25 text-primary focus:ring-primary"
                    type="checkbox"
                  />
                  <span class="min-w-0">
                    <span class="block font-semibold">{{ action.title }}</span>
                    <span class="mt-1 block text-caption text-gray-50">{{ action.description }}</span>
                    <code class="mt-2 block break-all rounded-xl border border-gray-20 bg-support-2 px-3 py-2 font-mono text-caption text-gray-90">
                      {{ action.command }}
                    </code>
                  </span>
                </label>
              </div>
            </div>

            <div
              v-if="postApplyAdvancedExecutableActions.length"
              class="rounded-2xl border border-warning bg-white p-3"
            >
              <p class="text-body-2 font-semibold text-gray-90">
                {{ t("Advanced actions") }}
              </p>
              <p class="mt-1 text-caption font-semibold text-gray-90">
                {{ t("Composer, Yarn and database migration actions can modify dependencies, generated assets or the database. Select them only after confirming backups and permissions.") }}
              </p>

              <div class="mt-3 space-y-2">
                <label
                  v-for="action in postApplyAdvancedExecutableActions"
                  :key="action.key"
                  class="flex items-start gap-3 rounded-2xl border border-warning bg-support-2 p-3 text-body-2 text-gray-90"
                >
                  <input
                    v-model="form.selectedPostApplyActionKeys"
                    :value="action.key"
                    class="mt-1 rounded border-gray-25 text-warning focus:ring-warning"
                    type="checkbox"
                  />
                  <span class="min-w-0">
                    <span class="block font-semibold">{{ action.title }}</span>
                    <span class="mt-1 block text-caption text-gray-90">{{ action.description }}</span>
                    <code class="mt-2 block break-all rounded-xl border border-gray-20 bg-white px-3 py-2 font-mono text-caption text-gray-90">
                      {{ action.command }}
                    </code>
                  </span>
                </label>
              </div>
            </div>
          </div>

          <label
            v-if="hasSelectedAdvancedPostApplyActions"
            class="flex items-start gap-3 rounded-2xl border border-warning bg-white p-3 text-body-2 text-gray-90"
          >
            <input
              v-model="form.confirmAdvancedPostApplyRun"
              class="mt-1 rounded border-gray-25 text-warning focus:ring-warning"
              type="checkbox"
            />
            <span>
              <span class="block font-semibold">
                {{ t("I understand that advanced actions can modify dependencies, generated assets or the database.") }}
              </span>
              <span class="mt-1 block text-caption text-gray-90">
                {{ t("Do not enable this unless the update backup and server permissions were reviewed.") }}
              </span>
            </span>
          </label>

          <div
            v-if="hasSelectedDatabaseMigrationAction"
            class="space-y-3 rounded-2xl border border-danger bg-white p-3"
          >
            <label class="flex items-start gap-3 text-body-2 text-gray-90">
              <input
                v-model="form.confirmDatabaseBackup"
                class="mt-1 rounded border-gray-25 text-danger focus:ring-danger"
                type="checkbox"
              />
              <span>
                <span class="block font-semibold">
                  {{ t("I confirm that a database backup exists and was verified before running migrations.") }}
                </span>
                <span class="mt-1 block text-caption text-gray-90">
                  {{ t("The updater does not create or restore database backups.") }}
                </span>
              </span>
            </label>

            <label class="block">
              <span class="text-body-2 font-semibold text-gray-90">
                {{ t("Type RUN DATABASE MIGRATIONS to confirm database migrations") }}
              </span>
              <input
                v-model.trim="form.databaseMigrationConfirmationText"
                class="mt-2 w-full rounded-xl border border-gray-20 px-3 py-2 font-mono text-caption text-gray-90 focus:border-primary focus:ring-primary"
                placeholder="RUN DATABASE MIGRATIONS"
                type="text"
              />
            </label>

            <div
              v-if="!migrationSafety?.migrationSafety?.valid"
              class="rounded-xl border border-warning bg-support-2 p-3 text-caption font-semibold text-gray-90"
            >
              <span class="mdi mdi-alert-outline mr-1" />
              {{ t("Run and pass the migration safety review before executing database migrations.") }}
            </div>
          </div>

          <label class="flex items-start gap-3 text-body-2 text-gray-90">
            <input
              v-model="form.confirmPostApplyRun"
              class="mt-1 rounded border-gray-25 text-danger focus:ring-danger"
              type="checkbox"
            />
            <span>
              {{ t("I understand this will run update commands on the server.") }}
            </span>
          </label>

          <label class="block">
            <span class="text-body-2 font-semibold text-gray-90">
              {{ t("Type RUN POST UPDATE ACTIONS to confirm") }}
            </span>
            <input
              v-model.trim="form.postApplyRunConfirmationText"
              class="mt-2 w-full rounded-xl border border-gray-25 px-3 py-2 font-mono text-body-2 text-gray-90 shadow-sm focus:border-danger focus:ring-danger"
              placeholder="RUN POST UPDATE ACTIONS"
              type="text"
            />
          </label>

          <BaseButton
            :disabled="isRunningPostApplyActions || !canRunPostApplyActions"
            :is-loading="isRunningPostApplyActions"
            :label="isRunningPostApplyActions ? t('Running post-apply actions...') : t('Run selected post-apply actions')"
            icon="play-circle-outline"
            type="primary"
            @click="runPostApplyActions"
          />
        </div>

        <div
          v-if="postApplyRunOperationId || postApplyRunProgressEvents.length"
          class="mt-6 overflow-hidden rounded-2xl border border-gray-20 bg-gray-90 shadow-sm"
        >
          <div class="flex items-center justify-between border-b border-gray-50 px-4 py-3">
            <div class="flex items-center gap-2 text-body-2 font-semibold text-white">
              <span class="mdi mdi-console-line text-lg text-info" />
              {{ t("Live command log") }}
            </div>
            <span class="font-mono text-caption text-gray-25">
              {{ postApplyRunOperationId }}
            </span>
          </div>

          <div class="max-h-80 overflow-auto p-4 font-mono text-caption">
            <div
              v-if="!postApplyRunProgressEvents.length"
              class="text-gray-25"
            >
              {{ t("Waiting for command progress events...") }}
            </div>

            <div
              v-for="(event, index) in postApplyRunProgressEvents"
              :key="`${event.time}-${event.step}-${index}`"
              class="grid gap-2 py-1 md:grid-cols-[5.5rem_5rem_12rem_1fr]"
            >
              <span class="text-gray-25">{{ formatOperationTime(event.time) }}</span>
              <span
                class="font-semibold uppercase"
                :class="getOperationLevelClass(event.level)"
              >
                {{ event.level }}
              </span>
              <span class="text-gray-25">{{ event.step }}</span>
              <span class="text-white">{{ event.message }}</span>
            </div>
          </div>
        </div>

        <ResultPanel
          v-if="postApplyRunResult"
          :details-label="t('Post-apply run details')"
          :details-text="formattedPostApplyRunDetails"
          :errors="postApplyRunResult.postApplyRun.errors"
          :title="postApplyRunResult.postApplyRun.valid ? t('Post-apply actions completed') : t('Post-apply actions failed')"
          :valid="postApplyRunResult.postApplyRun.valid"
          :warnings="postApplyRunResult.postApplyRun.warnings"
        >
          <CheckTable
            v-if="postApplyRunResult.postApplyRun.checks.length"
            :checks="postApplyRunResult.postApplyRun.checks"
            :status-class="getCheckStatusClass"
            :status-icon-class="getCheckStatusIconClass"
          />
        </ResultPanel>
      </div>
    </div>

    <div
      v-if="errorMessage"
      class="rounded-2xl border border-danger bg-white p-4 text-body-2 text-danger"
    >
      <span class="mdi mdi-alert-circle-outline mr-2" />
      {{ errorMessage }}
    </div>
  </section>
</template>

<script setup>
import { computed, defineComponent, h, onBeforeUnmount, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import adminService from "../../services/adminService"

const { t } = useI18n()

const status = reactive({
  installedVersion: "",
  updateDirectory: "",
  stagingDirectory: "",
  backupDirectory: "",
  lockPath: "",
  defaultManifestSource: "",
  officialManifestSource: "",
  localTestManifestSource: "",
  localTestPackagePath: "",
  allowLocalPaths: false,
  allowSkipSignature: false,
  productionMode: false,
  trustedPublicKeyConfigured: false,
  trustedPublicKeyFingerprint: "",
  allowUiPostApplyCommands: false,
  commandTimeout: 900,
  csrfToken: "",
})

const form = reactive({
  manifestSource: "",
  packagePath: "",
  signaturePath: "",
  trustedPublicKey: "",
  skipSignature: false,
  stagingPath: "",
  confirmApply: false,
  confirmationText: "",
  selectedPostApplyActionKeys: [],
  confirmPostApplyRun: false,
  confirmAdvancedPostApplyRun: false,
  confirmDatabaseBackup: false,
  databaseMigrationConfirmationText: "",
  postApplyRunConfirmationText: "",
  csrfToken: "",
})

const manifest = ref(null)
const availability = ref(null)
const verification = ref(null)
const preflight = ref(null)
const staging = ref(null)
const applyPlan = ref(null)
const applyFilesResult = ref(null)
const postApplyChecks = ref(null)
const migrationSafety = ref(null)
const postApplyRunResult = ref(null)
const errorMessage = ref("")
const isChecking = ref(false)
const isVerifying = ref(false)
const isRunningPreflight = ref(false)
const isStaging = ref(false)
const isPlanningApply = ref(false)
const isApplyingFiles = ref(false)
const isCheckingPostApply = ref(false)
const isCheckingMigrationSafety = ref(false)
const isRunningPostApplyActions = ref(false)
const applyOperationId = ref("")
const applyProgress = ref(null)
const applyProgressTimer = ref(null)
const postApplyRunOperationId = ref("")
const postApplyRunProgress = ref(null)
const postApplyRunProgressTimer = ref(null)
const updateEntrySource = ref("")
const updateEntryAutoCheck = ref(false)

const applyProgressEvents = computed(() => applyProgress.value?.events || [])
const postApplyRunProgressEvents = computed(() => postApplyRunProgress.value?.events || [])

const updateEntryNotice = computed(() => {
  if ("official" === updateEntrySource.value) {
    return {
      title: t("Official Chamilo update channel"),
      description: status.officialManifestSource
        ? t("This screen was opened from an update notice. Only the manifest check is started automatically.")
        : t("This screen was opened from an official update notice, but no official manifest source is configured yet."),
      severity: status.officialManifestSource ? "info" : "warning",
    }
  }

  if ("local-test" === updateEntrySource.value) {
    return {
      title: t("Local update notice simulation"),
      description: t("This development entry simulates the future Chamilo.org update notice using local test package paths."),
      severity: "warning",
    }
  }

  return null
})

const localTestUpdateEntryPath = computed(() => {
  if (!status.allowLocalPaths || !status.localTestManifestSource) {
    return ""
  }

  return "/admin/system-update?source=local-test&check=1"
})

const showTrustedPublicKeyInput = computed(() => {
  return status.allowLocalPaths && !status.trustedPublicKeyConfigured
})

const manifestSourcePlaceholder = computed(() => {
  if (status.defaultManifestSource) {
    return status.defaultManifestSource
  }

  if (status.allowLocalPaths) {
    return "https://download.example.org/chamilo/stable.json or /path/to/manifest.json"
  }

  return "https://download.example.org/chamilo/stable.json"
})

const manifestSourceHelp = computed(() => {
  if (status.defaultManifestSource) {
    return t("The configured official manifest URL is prefilled. You can change it to another HTTPS manifest if needed.")
  }

  if (status.allowLocalPaths) {
    return t("No official manifest URL is configured yet. Use an HTTPS URL or a local path for development tests.")
  }

  return t("Configure CHAMILO_UPDATE_MANIFEST_URL on the server or provide an HTTPS manifest URL.")
})

const manifestRows = computed(() => {
  if (!manifest.value) {
    return []
  }

  return [
    { label: t("Channel"), value: manifest.value.channel },
    { label: t("Version"), value: manifest.value.version },
    { label: t("Released at"), value: manifest.value.releasedAt },
    { label: t("Package URL"), value: manifest.value.packageUrl, mono: true },
    { label: t("Package SHA-256"), value: manifest.value.packageSha256, mono: true },
    { label: t("Signature"), value: manifest.value.signatureType || t("Not configured") },
  ]
})

const availabilityClass = computed(() => {
  if (!availability.value) {
    return "border-gray-20 bg-support-2 text-gray-90"
  }

  if (availability.value.updateAvailable) {
    return "border-success bg-support-2 text-success"
  }

  if (availability.value.downgrade) {
    return "border-danger bg-white text-danger"
  }

  if (availability.value.sameVersion) {
    return "border-warning bg-support-2 text-gray-90"
  }

  return "border-warning bg-support-2 text-gray-90"
})

const availabilityIconClass = computed(() => {
  if (!availability.value) {
    return "mdi-information"
  }

  if (availability.value.updateAvailable) {
    return "mdi-update"
  }

  if (availability.value.downgrade) {
    return "mdi-block-helper"
  }

  if (availability.value.sameVersion) {
    return "mdi-check-circle"
  }

  return "mdi-alert-circle"
})

const canVerifyPackage = computed(() => {
  return Boolean(form.manifestSource && (!availability.value || !availability.value.downgrade))
})

const formattedVerificationDetails = computed(() => {
  if (!verification.value) {
    return ""
  }

  return JSON.stringify(verification.value.result.details || {}, null, 2)
})

const formattedPreflightDetails = computed(() => {
  if (!preflight.value) {
    return ""
  }

  return JSON.stringify(preflight.value.result.details || {}, null, 2)
})

const formattedStagingDetails = computed(() => {
  if (!staging.value) {
    return ""
  }

  return JSON.stringify(staging.value.staging.details || {}, null, 2)
})

const formattedApplyPlanDetails = computed(() => {
  if (!applyPlan.value) {
    return ""
  }

  return JSON.stringify(applyPlan.value.applyPlan.details || {}, null, 2)
})

const formattedApplyFilesDetails = computed(() => {
  if (!applyFilesResult.value) {
    return ""
  }

  return JSON.stringify(applyFilesResult.value.applyFiles.details || {}, null, 2)
})

const formattedPostApplyDetails = computed(() => {
  if (!postApplyChecks.value) {
    return ""
  }

  return JSON.stringify(postApplyChecks.value.postApply.details || {}, null, 2)
})

const formattedMigrationSafetyDetails = computed(() => {
  if (!migrationSafety.value) {
    return ""
  }

  return JSON.stringify(migrationSafety.value.migrationSafety.details || {}, null, 2)
})

const migrationSafetyDetails = computed(() => {
  return migrationSafety.value?.migrationSafety?.details || {}
})

const migrationSafetyTarget = computed(() => {
  return migrationSafety.value?.migrationSafety?.migrationTarget || migrationSafetyDetails.value.migration_target || ""
})

const migrationSafetyBaseline = computed(() => {
  return migrationSafety.value?.migrationSafety?.baseline || migrationSafetyDetails.value.baseline || null
})

const migrationSafetyPendingBeforeTarget = computed(() => {
  return migrationSafetyBaseline.value?.pending_before_target || []
})

const migrationSafetyExecutedUnavailable = computed(() => {
  return migrationSafetyBaseline.value?.executed_unavailable_migrations || []
})

const formattedPostApplyRunDetails = computed(() => {
  if (!postApplyRunResult.value) {
    return ""
  }

  return JSON.stringify(postApplyRunResult.value.postApplyRun.details || {}, null, 2)
})

const postApplyActions = computed(() => {
  return postApplyChecks.value?.postApply?.actions || []
})

const postApplyExecutableActions = computed(() => {
  return buildExecutablePostApplyActions(postApplyActions.value)
})

const postApplySafeExecutableActions = computed(() => {
  return postApplyExecutableActions.value.filter((action) => !action.advanced)
})

const postApplyAdvancedExecutableActions = computed(() => {
  return postApplyExecutableActions.value.filter((action) => action.advanced)
})

const hasSelectedAdvancedPostApplyActions = computed(() => {
  return postApplyExecutableActions.value.some(
    (action) => action.advanced && form.selectedPostApplyActionKeys.includes(action.key),
  )
})

const hasDatabaseMigrationAction = computed(() => {
  return postApplyActions.value.some((action) => "database_migrations" === action.key)
})

const hasSelectedDatabaseMigrationAction = computed(() => {
  return form.selectedPostApplyActionKeys.includes("doctrine_migrations")
})

const canReviewMigrationSafety = computed(() => {
  return Boolean(postApplyChecks.value?.postApply?.valid && hasDatabaseMigrationAction.value && form.stagingPath)
})

const canRunPostApplyActions = computed(() => {
  return Boolean(
    status.allowUiPostApplyCommands &&
      postApplyChecks.value?.postApply?.valid &&
      form.selectedPostApplyActionKeys.length > 0 &&
      form.confirmPostApplyRun &&
      (!hasSelectedAdvancedPostApplyActions.value || form.confirmAdvancedPostApplyRun) &&
      (!hasSelectedDatabaseMigrationAction.value ||
        (migrationSafety.value?.migrationSafety?.valid &&
          form.confirmDatabaseBackup &&
          "RUN DATABASE MIGRATIONS" === form.databaseMigrationConfirmationText)) &&
      "RUN POST UPDATE ACTIONS" === form.postApplyRunConfirmationText,
  )
})

const applyPlanFilePlan = computed(() => {
  return applyPlan.value?.applyPlan?.details?.file_plan || {}
})

const canApplyFiles = computed(() => {
  return Boolean(
    applyPlan.value?.applyPlan?.valid &&
      form.confirmApply &&
      "APPLY UPDATE FILES" === form.confirmationText,
  )
})

const ResultPanel = defineComponent({
  name: "UpdateResultPanel",
  props: {
    detailsLabel: {
      type: String,
      required: true,
    },
    detailsText: {
      type: String,
      required: true,
    },
    errors: {
      type: Array,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },
    valid: {
      type: Boolean,
      required: true,
    },
    warnings: {
      type: Array,
      required: true,
    },
  },
  setup(props, { slots }) {
    return () =>
      h(
        "div",
        {
          class: [
            "mt-6 rounded-2xl border p-4",
            props.valid ? "border-success bg-support-2" : "border-danger bg-white",
          ],
        },
        [
          h("div", { class: "flex items-center gap-2 font-semibold" }, [
            h("span", {
              class: [
                "mdi text-xl",
                props.valid ? "mdi-check-circle text-success" : "mdi-alert-circle text-danger",
              ],
            }),
            h("span", { class: props.valid ? "text-success" : "text-danger" }, props.title),
          ]),
          slots.default ? h("div", { class: "mt-4" }, slots.default()) : null,
          props.errors.length
            ? h(
                "ul",
                { class: "mt-3 list-disc pl-6 text-body-2 text-danger" },
                props.errors.map((error) => h("li", { key: error }, error)),
              )
            : null,
          props.warnings.length
            ? h(
                "ul",
                { class: "mt-3 list-disc pl-6 text-body-2 text-gray-90" },
                props.warnings.map((warning) => h("li", { key: warning }, warning)),
              )
            : null,
          h("details", { class: "mt-4" }, [
            h("summary", { class: "cursor-pointer text-body-2 font-semibold text-primary" }, props.detailsLabel),
            h(
              "pre",
              { class: "mt-3 overflow-auto rounded-2xl border border-gray-20 bg-white p-3 font-mono text-caption text-gray-90" },
              props.detailsText,
            ),
          ]),
        ],
      )
  },
})

const CheckTable = defineComponent({
  name: "UpdateCheckTable",
  props: {
    checks: {
      type: Array,
      required: true,
    },
    statusClass: {
      type: Function,
      required: true,
    },
    statusIconClass: {
      type: Function,
      required: true,
    },
  },
  setup(props) {
    return () =>
      h("div", { class: "overflow-hidden rounded-2xl border border-gray-20 bg-white" }, [
        h("table", { class: "min-w-full divide-y divide-gray-20 text-body-2" }, [
          h("thead", { class: "bg-support-2" }, [
            h("tr", [
              h("th", { class: "px-4 py-3 text-left font-semibold text-gray-50" }, t("Status")),
              h("th", { class: "px-4 py-3 text-left font-semibold text-gray-50" }, t("Check")),
              h("th", { class: "px-4 py-3 text-left font-semibold text-gray-50" }, t("Message")),
            ]),
          ]),
          h(
            "tbody",
            { class: "divide-y divide-gray-20" },
            props.checks.map((check) =>
              h("tr", { key: check.key, class: "bg-white" }, [
                h("td", { class: "px-4 py-3" }, [
                  h(
                    "span",
                    {
                      class: [
                        "inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-caption font-semibold",
                        props.statusClass(check.status),
                      ],
                    },
                    [
                      h("span", { class: ["mdi", props.statusIconClass(check.status)] }),
                      check.status,
                    ],
                  ),
                ]),
                h("td", { class: "px-4 py-3 font-mono text-caption text-gray-50" }, check.key),
                h("td", { class: "px-4 py-3 text-gray-90" }, check.message),
              ]),
            ),
          ),
        ]),
      ])
  },
})

onMounted(async () => {
  try {
    const data = await adminService.findSystemUpdateStatus()
    status.installedVersion = data.installedVersion || ""
    status.updateDirectory = data.updateDirectory || ""
    status.stagingDirectory = data.stagingDirectory || ""
    status.backupDirectory = data.backupDirectory || ""
    status.lockPath = data.lockPath || ""
    status.defaultManifestSource = data.defaultManifestSource || ""
    status.officialManifestSource = data.officialManifestSource || ""
    status.localTestManifestSource = data.localTestManifestSource || ""
    status.localTestPackagePath = data.localTestPackagePath || ""
    status.allowLocalPaths = Boolean(data.allowLocalPaths)
    status.allowSkipSignature = Boolean(data.allowSkipSignature)
    status.productionMode = Boolean(data.productionMode)
    status.trustedPublicKeyConfigured = Boolean(data.trustedPublicKeyConfigured)
    status.trustedPublicKeyFingerprint = data.trustedPublicKeyFingerprint || ""
    status.allowUiPostApplyCommands = Boolean(data.allowUiPostApplyCommands)
    status.commandTimeout = Number(data.commandTimeout || 900)
    status.csrfToken = data.csrfToken || ""
    form.csrfToken = data.csrfToken || ""

    applyUpdateEntryQuery()

    if (!form.manifestSource && status.defaultManifestSource) {
      form.manifestSource = status.defaultManifestSource
    }

    if (!status.allowSkipSignature) {
      form.skipSignature = false
    }

    if (updateEntryAutoCheck.value && form.manifestSource) {
      await checkManifest()
    }
  } catch (error) {
    console.error("[SystemUpdate] Failed to load update status:", error)
  }
})

onBeforeUnmount(() => {
  stopApplyProgressPolling()
  stopPostApplyRunProgressPolling()
})

function applyUpdateEntryQuery() {
  const params = new URLSearchParams(window.location.search)
  const source = params.get("source") || ""
  const shouldCheck = ["1", "true", "yes", "on"].includes((params.get("check") || "").toLowerCase())

  updateEntryAutoCheck.value = shouldCheck

  if (!source) {
    return
  }

  if ("official" === source) {
    updateEntrySource.value = "official"

    if (status.officialManifestSource) {
      form.manifestSource = status.officialManifestSource
    }

    return
  }

  if ("local-test" === source) {
    updateEntrySource.value = "local-test"

    if (!status.allowLocalPaths) {
      errorMessage.value = t("Local update notice simulation is only available in development mode.")
      return
    }

    form.manifestSource = status.localTestManifestSource || "/tmp/chamilo-update-slow-manifest.json"
    form.packagePath = status.localTestPackagePath || "/tmp/chamilo-update-slow.zip"

    if (status.allowSkipSignature) {
      form.skipSignature = true
    }

    return
  }

  errorMessage.value = t("Unknown update notice source.")
}

async function checkManifest() {
  errorMessage.value = ""
  availability.value = null
  verification.value = null
  preflight.value = null
  staging.value = null
  applyPlan.value = null
  applyFilesResult.value = null
  postApplyChecks.value = null
  resetApplyProgress()
  isChecking.value = true

  try {
    const data = await adminService.checkSystemUpdateManifest(withCsrfPayload({
      manifestSource: form.manifestSource,
    }))

    manifest.value = data.manifest
    availability.value = data.availability || null
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    isChecking.value = false
  }
}

async function verifyPackage() {
  errorMessage.value = ""
  applyPlan.value = null
  applyFilesResult.value = null
  postApplyChecks.value = null
  resetApplyProgress()
  isVerifying.value = true

  try {
    const data = await adminService.verifySystemUpdatePackage(buildUpdatePayload())

    manifest.value = data.manifest
    verification.value = data
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
    verification.value = null
  } finally {
    isVerifying.value = false
  }
}

async function runPreflight() {
  errorMessage.value = ""
  applyPlan.value = null
  applyFilesResult.value = null
  postApplyChecks.value = null
  resetApplyProgress()
  isRunningPreflight.value = true

  try {
    const data = await adminService.runSystemUpdatePreflight(withCsrfPayload({
      manifestSource: form.manifestSource,
      packagePath: status.allowLocalPaths ? form.packagePath || null : null,
    }))

    manifest.value = data.manifest
    preflight.value = data
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
    preflight.value = null
  } finally {
    isRunningPreflight.value = false
  }
}

async function stagePackage() {
  errorMessage.value = ""
  applyFilesResult.value = null
  postApplyChecks.value = null
  migrationSafety.value = null
  postApplyRunResult.value = null
  resetPostApplyRunProgress()
  isStaging.value = true

  try {
    const data = await adminService.stageSystemUpdatePackage(buildUpdatePayload())

    manifest.value = data.manifest
    verification.value = {
      manifest: data.manifest,
      packagePath: data.packagePath,
      signaturePath: data.signaturePath,
      result: data.verification,
    }
    preflight.value = {
      manifest: data.manifest,
      packagePath: data.packagePath,
      result: data.preflight,
    }
    staging.value = data
    form.stagingPath = data.staging?.stagingPath || ""
    applyPlan.value = null
    applyFilesResult.value = null
    resetApplyProgress()
  } catch (error) {
    const responseData = error?.response?.data || null

    errorMessage.value = getErrorMessage(error)
    applyPlan.value = null

    if (responseData?.verification) {
      verification.value = {
        manifest: responseData.manifest,
        packagePath: responseData.packagePath,
        signaturePath: responseData.signaturePath,
        result: responseData.verification,
      }
    }

    if (responseData?.preflight) {
      preflight.value = {
        manifest: responseData.manifest,
        packagePath: responseData.packagePath,
        result: responseData.preflight,
      }
    }

    if (responseData?.staging) {
      staging.value = {
        manifest: responseData.manifest,
        packagePath: responseData.packagePath,
        signaturePath: responseData.signaturePath,
        staging: responseData.staging,
      }
    } else {
      staging.value = null
    }
  } finally {
    isStaging.value = false
  }
}


async function buildApplyPlan() {
  errorMessage.value = ""
  isPlanningApply.value = true

  try {
    const data = await adminService.buildSystemUpdateApplyPlan(withCsrfPayload({
      stagingPath: form.stagingPath,
    }))

    applyPlan.value = data
    applyFilesResult.value = null
    postApplyChecks.value = null
    postApplyRunResult.value = null
    resetPostApplyRunProgress()
    form.confirmApply = false
    form.confirmationText = ""
    resetApplyProgress()
  } catch (error) {
    const responseData = error?.response?.data || null

    errorMessage.value = getErrorMessage(error)
    applyPlan.value = responseData?.applyPlan
      ? {
          applyPlan: responseData.applyPlan,
        }
      : null
  } finally {
    isPlanningApply.value = false
  }
}


async function applyUpdateFiles() {
  errorMessage.value = ""
  isApplyingFiles.value = true
  applyFilesResult.value = null
  postApplyChecks.value = null
  postApplyRunResult.value = null
  resetPostApplyRunProgress()
  applyOperationId.value = createOperationId()
  applyProgress.value = {
    operationId: applyOperationId.value,
    exists: false,
    events: [],
    completed: false,
  }
  startApplyProgressPolling()

  try {
    const data = await adminService.applySystemUpdateFiles(withCsrfPayload({
      stagingPath: form.stagingPath,
      confirmApply: form.confirmApply,
      confirmationText: form.confirmationText,
      operationId: applyOperationId.value,
    }))

    applyFilesResult.value = data
    if (data.operationId) {
      applyOperationId.value = data.operationId
    }
    await refreshApplyProgress()
  } catch (error) {
    const responseData = error?.response?.data || null

    errorMessage.value = getErrorMessage(error)
    applyFilesResult.value = responseData?.applyFiles
      ? {
          applyFiles: responseData.applyFiles,
        }
      : null
    await refreshApplyProgress()
  } finally {
    stopApplyProgressPolling()
    isApplyingFiles.value = false
  }
}


async function runPostApplyChecks() {
  errorMessage.value = ""
  isCheckingPostApply.value = true

  try {
    const data = await adminService.runSystemUpdatePostApplyChecks(withCsrfPayload({
      stagingPath: form.stagingPath,
    }))

    postApplyChecks.value = data
    migrationSafety.value = null
    postApplyRunResult.value = null
    resetPostApplyRunProgress()
    form.selectedPostApplyActionKeys = buildExecutablePostApplyActions(data.postApply?.actions || [])
      .filter((action) => !action.advanced)
      .map((action) => action.key)
    form.confirmPostApplyRun = false
    form.confirmAdvancedPostApplyRun = false
    form.confirmDatabaseBackup = false
    form.databaseMigrationConfirmationText = ""
    form.postApplyRunConfirmationText = ""
  } catch (error) {
    const responseData = error?.response?.data || null

    errorMessage.value = getErrorMessage(error)
    postApplyChecks.value = responseData?.postApply
      ? {
          postApply: responseData.postApply,
        }
      : null
  } finally {
    isCheckingPostApply.value = false
  }
}



async function runMigrationSafetyChecks() {
  errorMessage.value = ""
  isCheckingMigrationSafety.value = true
  migrationSafety.value = null

  try {
    const data = await adminService.runSystemUpdateMigrationSafetyChecks(withCsrfPayload({
      stagingPath: form.stagingPath,
    }))

    migrationSafety.value = data
    form.confirmDatabaseBackup = false
    form.databaseMigrationConfirmationText = ""
  } catch (error) {
    const responseData = error?.response?.data || null

    errorMessage.value = getErrorMessage(error)
    migrationSafety.value = responseData?.migrationSafety
      ? {
          migrationSafety: responseData.migrationSafety,
        }
      : null
  } finally {
    isCheckingMigrationSafety.value = false
  }
}


async function runPostApplyActions() {
  errorMessage.value = ""
  isRunningPostApplyActions.value = true
  postApplyRunResult.value = null
  postApplyRunOperationId.value = createOperationId()
  postApplyRunProgress.value = {
    operationId: postApplyRunOperationId.value,
    exists: false,
    events: [],
    completed: false,
  }
  startPostApplyRunProgressPolling()

  try {
    const data = await adminService.runSystemUpdatePostApplyActions(withCsrfPayload({
      stagingPath: form.stagingPath,
      actions: form.selectedPostApplyActionKeys,
      confirmPostApplyRun: form.confirmPostApplyRun,
      confirmAdvancedPostApplyRun: form.confirmAdvancedPostApplyRun,
      confirmDatabaseBackup: form.confirmDatabaseBackup,
      databaseMigrationConfirmationText: form.databaseMigrationConfirmationText,
      postApplyRunConfirmationText: form.postApplyRunConfirmationText,
      operationId: postApplyRunOperationId.value,
    }))

    postApplyRunResult.value = data
    if (data.operationId) {
      postApplyRunOperationId.value = data.operationId
    }
    await refreshPostApplyRunProgress()
  } catch (error) {
    const responseData = error?.response?.data || null

    await refreshPostApplyRunProgress()

    const recoveredResult = buildRecoveredPostApplyRunResult(error, responseData)

    if (recoveredResult) {
      postApplyRunResult.value = recoveredResult
      errorMessage.value = ""
    } else {
      errorMessage.value = getErrorMessage(error)
      postApplyRunResult.value = responseData?.postApplyRun
        ? {
            postApplyRun: responseData.postApplyRun,
          }
        : null
    }
  } finally {
    stopPostApplyRunProgressPolling()
    isRunningPostApplyActions.value = false
  }
}

function buildRecoveredPostApplyRunResult(error, responseData) {
  if (responseData?.postApplyRun) {
    return {
      postApplyRun: responseData.postApplyRun,
    }
  }

  if (!postApplyProgressCompletedSuccessfully()) {
    return null
  }

  const httpError = getErrorMessage(error)
  const warning = `${t("The selected post-apply commands completed successfully, but the final HTTP response failed. Refresh the page and verify runtime cache permissions if the page does not load correctly.")} ${httpError}`

  return {
    postApplyRun: {
      valid: true,
      stagingPath: form.stagingPath,
      metadataPath: null,
      operationId: postApplyRunOperationId.value,
      checks: [
        {
          key: "post_apply_operation_log",
          status: "passed",
          message: t("The operation log reports that post-apply actions completed successfully."),
        },
        {
          key: "post_apply_http_response",
          status: "warning",
          message: t("The final HTTP response failed after the operation completed. This is usually caused by runtime cache permissions or a web server restart during the request."),
          details: {
            http_error: httpError,
          },
        },
      ],
      actions: [],
      errors: [],
      warnings: [warning],
      details: {
        recovered_from_operation_log: true,
        http_error: httpError,
        operation_id: postApplyRunOperationId.value,
      },
    },
  }
}

function postApplyProgressCompletedSuccessfully() {
  return postApplyRunProgressEvents.value.some((event) => {
    if ("success" !== event.level) {
      return false
    }

    if ("done" === event.step) {
      return true
    }

    if ("post_apply_commands" === event.step) {
      return String(event.message || "").toLowerCase().includes("completed successfully")
    }

    return false
  })
}

function buildExecutablePostApplyActions(actions) {
  const executableActions = []

  for (const action of actions || []) {
    if ("composer_install" === action.key) {
      executableActions.push({
        key: "composer_install",
        title: t("Composer install"),
        description: action.description,
        command: status.productionMode
          ? "composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader"
          : "composer install --no-interaction --prefer-dist",
        severity: action.severity,
        advanced: true,
        category: "advanced",
      })
    }

    if ("frontend_build" === action.key) {
      executableActions.push({
        key: "yarn_install",
        title: t("Yarn install"),
        description: t("Install frontend dependencies before rebuilding assets."),
        command: "yarn install --frozen-lockfile",
        severity: action.severity,
        advanced: true,
        category: "advanced",
      })
      executableActions.push({
        key: "yarn_build",
        title: t("Yarn build"),
        description: t("Build production frontend assets."),
        command: 'NODE_OPTIONS="--max-old-space-size=8192" yarn build',
        severity: action.severity,
        advanced: true,
        category: "advanced",
      })
    }

    if ("database_migrations" === action.key) {
      executableActions.push({
        key: "doctrine_migrations",
        title: t("Database migrations"),
        description: action.description,
        command: "php bin/console doctrine:migrations:execute <staged-migration-class> --up --no-interaction",
        severity: action.severity,
        advanced: true,
        category: "advanced",
      })
    }

    if ("cache_clear" === action.key) {
      executableActions.push({
        key: "cache_clear",
        title: t("Symfony cache clear"),
        description: action.description,
        command: "php bin/console cache:clear",
        severity: action.severity,
        advanced: false,
        category: "safe",
      })
    }
  }

  return executableActions
}

function createOperationId() {
  if (window.crypto?.randomUUID) {
    return window.crypto.randomUUID()
  }

  return `${Date.now()}-${Math.random().toString(16).slice(2)}`
}

function resetApplyProgress() {
  stopApplyProgressPolling()
  applyOperationId.value = ""
  applyProgress.value = null
}

function startApplyProgressPolling() {
  stopApplyProgressPolling()
  applyProgressTimer.value = window.setInterval(refreshApplyProgress, 1000)
}

function stopApplyProgressPolling() {
  if (applyProgressTimer.value) {
    window.clearInterval(applyProgressTimer.value)
    applyProgressTimer.value = null
  }
}

async function refreshApplyProgress() {
  if (!applyOperationId.value) {
    return
  }

  try {
    const data = await adminService.findSystemUpdateProgress(applyOperationId.value)
    applyProgress.value = data.progress
  } catch (error) {
    console.error("[SystemUpdate] Failed to refresh update progress:", error)
  }
}

function resetPostApplyRunProgress() {
  stopPostApplyRunProgressPolling()
  postApplyRunOperationId.value = ""
  postApplyRunProgress.value = null
}

function startPostApplyRunProgressPolling() {
  stopPostApplyRunProgressPolling()
  postApplyRunProgressTimer.value = window.setInterval(refreshPostApplyRunProgress, 1000)
}

function stopPostApplyRunProgressPolling() {
  if (postApplyRunProgressTimer.value) {
    window.clearInterval(postApplyRunProgressTimer.value)
    postApplyRunProgressTimer.value = null
  }
}

async function refreshPostApplyRunProgress() {
  if (!postApplyRunOperationId.value) {
    return
  }

  try {
    const data = await adminService.findSystemUpdateProgress(postApplyRunOperationId.value)
    postApplyRunProgress.value = data.progress
  } catch (error) {
    console.error("[SystemUpdate] Failed to refresh post-apply progress:", error)
  }
}

function formatOperationTime(value) {
  if (!value) {
    return ""
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }

  return date.toLocaleTimeString()
}

function getOperationLevelClass(level) {
  if ("success" === level) {
    return "text-success"
  }

  if ("warning" === level) {
    return "text-warning"
  }

  if ("error" === level) {
    return "text-danger"
  }

  return "text-info"
}

function buildUpdatePayload() {
  return withCsrfPayload({
    manifestSource: form.manifestSource,
    packagePath: status.allowLocalPaths ? form.packagePath || null : null,
    signaturePath: status.allowLocalPaths ? form.signaturePath || null : null,
    trustedPublicKey: showTrustedPublicKeyInput.value ? form.trustedPublicKey || null : null,
    skipSignature: status.allowSkipSignature && form.skipSignature,
  })
}

function withCsrfPayload(payload = {}) {
  return {
    ...payload,
    csrfToken: form.csrfToken,
  }
}


function getActionSeverityClass(severity) {
  if ("danger" === severity) {
    return "border-danger text-danger bg-white"
  }

  if ("warning" === severity) {
    return "border-warning text-gray-90 bg-support-2"
  }

  if ("success" === severity) {
    return "border-success text-success bg-white"
  }

  return "border-info text-info bg-white"
}

function getCheckStatusClass(status) {
  if ("passed" === status) {
    return "border-success text-success bg-white"
  }

  if ("warning" === status) {
    return "border-warning text-gray-90 bg-support-2"
  }

  if ("failed" === status) {
    return "border-danger text-danger bg-white"
  }

  return "border-gray-20 text-gray-50 bg-white"
}

function getCheckStatusIconClass(status) {
  if ("passed" === status) {
    return "mdi-check-circle"
  }

  if ("warning" === status) {
    return "mdi-alert-circle"
  }

  if ("failed" === status) {
    return "mdi-close-circle"
  }

  return "mdi-information"
}

function getErrorMessage(error) {
  return error?.response?.data?.error || error?.message || t("An unexpected error occurred")
}
</script>
