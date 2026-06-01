<template>
  <section class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">
            {{ t("System update") }}
          </h1>
          <p class="mt-2 max-w-3xl text-sm text-gray-600">
            {{ t("Verify a Chamilo update package before applying it. This screen does not replace files or run database migrations.") }}
          </p>
        </div>

        <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-700">
          {{ t("Verification only") }}
        </span>
      </div>

      <dl class="mt-6 grid gap-4 md:grid-cols-2">
        <div class="rounded-xl bg-gray-50 p-4">
          <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
            {{ t("Installed version") }}
          </dt>
          <dd class="mt-1 text-sm text-gray-900">
            {{ status.installedVersion || t("Unknown") }}
          </dd>
        </div>

        <div class="rounded-xl bg-gray-50 p-4">
          <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
            {{ t("Update directory") }}
          </dt>
          <dd class="mt-1 break-all text-sm text-gray-900">
            {{ status.updateDirectory || "var/update/downloads" }}
          </dd>
        </div>
      </dl>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-gray-900">
        {{ t("Manifest") }}
      </h2>

      <div class="mt-4 space-y-4">
        <label class="block">
          <span class="text-sm font-medium text-gray-700">{{ t("Manifest source") }}</span>
          <input
            v-model.trim="form.manifestSource"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
            placeholder="https://download.example.org/chamilo/stable.json or /path/to/manifest.json"
            type="text"
          />
        </label>

        <div class="flex flex-wrap gap-2">
          <button
            class="btn btn--primary"
            :disabled="isChecking || !form.manifestSource"
            type="button"
            @click="checkManifest"
          >
            <span class="mdi mdi-file-search-outline ch-tool-icon" />
            {{ isChecking ? t("Checking...") : t("Check manifest") }}
          </button>
        </div>
      </div>

      <div
        v-if="manifest"
        class="mt-6 overflow-hidden rounded-xl border border-gray-200"
      >
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <tbody class="divide-y divide-gray-200">
            <tr>
              <th class="bg-gray-50 px-4 py-3 text-left font-medium text-gray-600">{{ t("Channel") }}</th>
              <td class="px-4 py-3 text-gray-900">{{ manifest.channel }}</td>
            </tr>
            <tr>
              <th class="bg-gray-50 px-4 py-3 text-left font-medium text-gray-600">{{ t("Version") }}</th>
              <td class="px-4 py-3 text-gray-900">{{ manifest.version }}</td>
            </tr>
            <tr>
              <th class="bg-gray-50 px-4 py-3 text-left font-medium text-gray-600">{{ t("Released at") }}</th>
              <td class="px-4 py-3 text-gray-900">{{ manifest.releasedAt }}</td>
            </tr>
            <tr>
              <th class="bg-gray-50 px-4 py-3 text-left font-medium text-gray-600">{{ t("Package URL") }}</th>
              <td class="break-all px-4 py-3 text-gray-900">{{ manifest.packageUrl }}</td>
            </tr>
            <tr>
              <th class="bg-gray-50 px-4 py-3 text-left font-medium text-gray-600">{{ t("Package SHA-256") }}</th>
              <td class="break-all px-4 py-3 font-mono text-xs text-gray-900">{{ manifest.packageSha256 }}</td>
            </tr>
            <tr>
              <th class="bg-gray-50 px-4 py-3 text-left font-medium text-gray-600">{{ t("Signature") }}</th>
              <td class="px-4 py-3 text-gray-900">
                {{ manifest.signatureType || t("Not configured") }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-gray-900">
        {{ t("Package verification") }}
      </h2>

      <p class="mt-2 text-sm text-gray-600">
        {{ t("Use local paths for development tests, or leave the package and signature paths empty to download them from the manifest URLs.") }}
      </p>

      <div class="mt-4 grid gap-4 md:grid-cols-2">
        <label class="block">
          <span class="text-sm font-medium text-gray-700">{{ t("Package path") }}</span>
          <input
            v-model.trim="form.packagePath"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
            placeholder="/tmp/chamilo-update.zip"
            type="text"
          />
        </label>

        <label class="block">
          <span class="text-sm font-medium text-gray-700">{{ t("Signature path") }}</span>
          <input
            v-model.trim="form.signaturePath"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
            placeholder="/tmp/chamilo-update.zip.minisig"
            type="text"
          />
        </label>

        <label class="block md:col-span-2">
          <span class="text-sm font-medium text-gray-700">{{ t("Trusted public key") }}</span>
          <input
            v-model.trim="form.trustedPublicKey"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
            placeholder="RW..."
            type="text"
          />
        </label>
      </div>

      <label class="mt-4 flex items-center gap-2 text-sm text-gray-700">
        <input
          v-model="form.skipSignature"
          class="rounded border-gray-300"
          type="checkbox"
        />
        <span>{{ t("Skip signature verification for local development tests") }}</span>
      </label>

      <div class="mt-4 flex flex-wrap gap-2">
        <button
          class="btn btn--primary"
          :disabled="isVerifying || !form.manifestSource"
          type="button"
          @click="verifyPackage"
        >
          <span class="mdi mdi-shield-check-outline ch-tool-icon" />
          {{ isVerifying ? t("Verifying...") : t("Verify package") }}
        </button>
      </div>

      <div
        v-if="verification"
        class="mt-6 rounded-xl border p-4"
        :class="verification.result.valid ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'"
      >
        <div class="flex items-center gap-2 font-medium">
          <span
            class="mdi"
            :class="verification.result.valid ? 'mdi-check-circle text-green-700' : 'mdi-alert-circle text-red-700'"
          />
          <span :class="verification.result.valid ? 'text-green-800' : 'text-red-800'">
            {{ verification.result.valid ? t("Package verified successfully") : t("Package verification failed") }}
          </span>
        </div>

        <ul
          v-if="verification.result.errors.length"
          class="mt-3 list-disc pl-6 text-sm text-red-700"
        >
          <li
            v-for="error in verification.result.errors"
            :key="error"
          >
            {{ error }}
          </li>
        </ul>

        <ul
          v-if="verification.result.warnings.length"
          class="mt-3 list-disc pl-6 text-sm text-yellow-700"
        >
          <li
            v-for="warning in verification.result.warnings"
            :key="warning"
          >
            {{ warning }}
          </li>
        </ul>

        <details class="mt-4">
          <summary class="cursor-pointer text-sm font-medium text-gray-700">
            {{ t("Verification details") }}
          </summary>
          <pre class="mt-3 overflow-auto rounded-lg bg-white p-3 text-xs text-gray-800">{{ formattedVerificationDetails }}</pre>
        </details>
      </div>
    </div>


    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-gray-900">
        {{ t("Preflight checks") }}
      </h2>

      <p class="mt-2 text-sm text-gray-600">
        {{ t("Run environment checks before any future update application step. This does not modify files or the database.") }}
      </p>

      <div class="mt-4 flex flex-wrap gap-2">
        <button
          class="btn btn--primary"
          :disabled="isRunningPreflight || !form.manifestSource"
          type="button"
          @click="runPreflight"
        >
          <span class="mdi mdi-clipboard-check-outline ch-tool-icon" />
          {{ isRunningPreflight ? t("Checking...") : t("Run preflight checks") }}
        </button>
      </div>

      <div
        v-if="preflight"
        class="mt-6 rounded-xl border p-4"
        :class="preflight.result.valid ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'"
      >
        <div class="flex items-center gap-2 font-medium">
          <span
            class="mdi"
            :class="preflight.result.valid ? 'mdi-check-circle text-green-700' : 'mdi-alert-circle text-red-700'"
          />
          <span :class="preflight.result.valid ? 'text-green-800' : 'text-red-800'">
            {{ preflight.result.valid ? t("Preflight checks completed") : t("Preflight checks failed") }}
          </span>
        </div>

        <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 bg-white">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-600">{{ t("Status") }}</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">{{ t("Check") }}</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">{{ t("Message") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr
                v-for="check in preflight.result.checks"
                :key="check.key"
              >
                <td class="px-4 py-3">
                  <span
                    class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                    :class="getCheckStatusClass(check.status)"
                  >
                    {{ check.status }}
                  </span>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-gray-700">
                  {{ check.key }}
                </td>
                <td class="px-4 py-3 text-gray-900">
                  {{ check.message }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <ul
          v-if="preflight.result.errors.length"
          class="mt-3 list-disc pl-6 text-sm text-red-700"
        >
          <li
            v-for="error in preflight.result.errors"
            :key="error"
          >
            {{ error }}
          </li>
        </ul>

        <ul
          v-if="preflight.result.warnings.length"
          class="mt-3 list-disc pl-6 text-sm text-yellow-700"
        >
          <li
            v-for="warning in preflight.result.warnings"
            :key="warning"
          >
            {{ warning }}
          </li>
        </ul>

        <details class="mt-4">
          <summary class="cursor-pointer text-sm font-medium text-gray-700">
            {{ t("Preflight details") }}
          </summary>
          <pre class="mt-3 overflow-auto rounded-lg bg-white p-3 text-xs text-gray-800">{{ formattedPreflightDetails }}</pre>
        </details>
      </div>
    </div>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import adminService from "../../services/adminService"

const { t } = useI18n()

const status = reactive({
  installedVersion: "",
  updateDirectory: "",
})

const form = reactive({
  manifestSource: "docs/update-manifest.example.json",
  packagePath: "",
  signaturePath: "",
  trustedPublicKey: "",
  skipSignature: true,
})

const manifest = ref(null)
const verification = ref(null)
const preflight = ref(null)
const errorMessage = ref("")
const isChecking = ref(false)
const isVerifying = ref(false)
const isRunningPreflight = ref(false)

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

onMounted(async () => {
  try {
    const data = await adminService.findSystemUpdateStatus()
    status.installedVersion = data.installedVersion || ""
    status.updateDirectory = data.updateDirectory || ""
  } catch (error) {
    console.error("[SystemUpdate] Failed to load update status:", error)
  }
})

async function checkManifest() {
  errorMessage.value = ""
  verification.value = null
  preflight.value = null
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
  isVerifying.value = true

  try {
    const data = await adminService.verifySystemUpdatePackage({
      manifestSource: form.manifestSource,
      packagePath: form.packagePath || null,
      signaturePath: form.signaturePath || null,
      trustedPublicKey: form.trustedPublicKey || null,
      skipSignature: form.skipSignature,
    })

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
  isRunningPreflight.value = true

  try {
    const data = await adminService.runSystemUpdatePreflight({
      manifestSource: form.manifestSource,
      packagePath: form.packagePath || null,
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

function getCheckStatusClass(status) {
  if ("passed" === status) {
    return "bg-green-100 text-green-700"
  }

  if ("warning" === status) {
    return "bg-yellow-100 text-yellow-700"
  }

  if ("failed" === status) {
    return "bg-red-100 text-red-700"
  }

  return "bg-gray-100 text-gray-700"
}

function getErrorMessage(error) {
  return error?.response?.data?.error || error?.message || t("An unexpected error occurred")
}
</script>
