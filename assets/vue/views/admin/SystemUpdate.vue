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
                {{ t("Verify, stage and apply Chamilo update files. Database migrations, Composer, Yarn and cache commands are not executed here.") }}
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
          :disabled="isVerifying || !form.manifestSource"
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
        {{ t("This step prepares a review only. The real file replacement will be implemented after lock, backup and rollback are validated.") }}
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
  allowLocalPaths: false,
  allowSkipSignature: false,
  productionMode: false,
  trustedPublicKeyConfigured: false,
  trustedPublicKeyFingerprint: "",
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
})

const manifest = ref(null)
const verification = ref(null)
const preflight = ref(null)
const staging = ref(null)
const applyPlan = ref(null)
const applyFilesResult = ref(null)
const errorMessage = ref("")
const isChecking = ref(false)
const isVerifying = ref(false)
const isRunningPreflight = ref(false)
const isStaging = ref(false)
const isPlanningApply = ref(false)
const isApplyingFiles = ref(false)
const applyOperationId = ref("")
const applyProgress = ref(null)
const applyProgressTimer = ref(null)

const applyProgressEvents = computed(() => applyProgress.value?.events || [])

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
                { class: "mt-3 list-disc pl-6 text-body-2 text-warning" },
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
    status.allowLocalPaths = Boolean(data.allowLocalPaths)
    status.allowSkipSignature = Boolean(data.allowSkipSignature)
    status.productionMode = Boolean(data.productionMode)
    status.trustedPublicKeyConfigured = Boolean(data.trustedPublicKeyConfigured)
    status.trustedPublicKeyFingerprint = data.trustedPublicKeyFingerprint || ""

    if (!form.manifestSource && status.defaultManifestSource) {
      form.manifestSource = status.defaultManifestSource
    }

    if (!status.allowSkipSignature) {
      form.skipSignature = false
    }
  } catch (error) {
    console.error("[SystemUpdate] Failed to load update status:", error)
  }
})

onBeforeUnmount(() => {
  stopApplyProgressPolling()
})

async function checkManifest() {
  errorMessage.value = ""
  verification.value = null
  preflight.value = null
  staging.value = null
  applyPlan.value = null
  applyFilesResult.value = null
  resetApplyProgress()
  isChecking.value = true

  try {
    const data = await adminService.checkSystemUpdateManifest({
      manifestSource: form.manifestSource,
    })

    manifest.value = data.manifest
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
  resetApplyProgress()
  isRunningPreflight.value = true

  try {
    const data = await adminService.runSystemUpdatePreflight({
      manifestSource: form.manifestSource,
      packagePath: status.allowLocalPaths ? form.packagePath || null : null,
    })

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
    const data = await adminService.buildSystemUpdateApplyPlan({
      stagingPath: form.stagingPath,
    })

    applyPlan.value = data
    applyFilesResult.value = null
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
  applyOperationId.value = createOperationId()
  applyProgress.value = {
    operationId: applyOperationId.value,
    exists: false,
    events: [],
    completed: false,
  }
  startApplyProgressPolling()

  try {
    const data = await adminService.applySystemUpdateFiles({
      stagingPath: form.stagingPath,
      confirmApply: form.confirmApply,
      confirmationText: form.confirmationText,
      operationId: applyOperationId.value,
    })

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
  return {
    manifestSource: form.manifestSource,
    packagePath: status.allowLocalPaths ? form.packagePath || null : null,
    signaturePath: status.allowLocalPaths ? form.signaturePath || null : null,
    trustedPublicKey: showTrustedPublicKeyInput.value ? form.trustedPublicKey || null : null,
    skipSignature: status.allowSkipSignature && form.skipSignature,
  }
}

function getCheckStatusClass(status) {
  if ("passed" === status) {
    return "border-success text-success bg-white"
  }

  if ("warning" === status) {
    return "border-warning text-warning bg-white"
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
