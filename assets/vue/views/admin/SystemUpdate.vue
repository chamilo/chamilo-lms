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
                {{ t("Verify a Chamilo update package before applying it. This screen does not replace files or run database migrations.") }}
              </p>
            </div>
          </div>
        </div>

        <span class="inline-flex items-center gap-2 rounded-full border border-primary bg-support-1 px-3 py-1 text-caption font-semibold text-primary">
          <i class="mdi mdi-lock-check-outline" />
          {{ t("Verification only") }}
        </span>
      </div>

      <dl class="mt-6 grid gap-4 md:grid-cols-3">
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
            placeholder="https://download.example.org/chamilo/stable.json or /path/to/manifest.json"
            type="text"
          />
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
            {{ t("Use local paths for development tests, or leave the package and signature paths empty to download them from the manifest URLs.") }}
          </p>
        </div>
      </div>

      <div class="mt-5 grid gap-4 md:grid-cols-2">
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

        <label class="block md:col-span-2">
          <span class="text-body-2 font-semibold text-gray-90">{{ t("Trusted public key") }}</span>
          <input
            v-model.trim="form.trustedPublicKey"
            class="mt-2 w-full rounded-xl border border-gray-25 px-3 py-2 text-body-2 text-gray-90 shadow-sm focus:border-primary focus:ring-primary"
            placeholder="RW..."
            type="text"
          />
        </label>
      </div>

      <label class="mt-4 flex items-center gap-2 text-body-2 text-gray-90">
        <input
          v-model="form.skipSignature"
          class="rounded border-gray-25 text-primary focus:ring-primary"
          type="checkbox"
        />
        <span>{{ t("Skip signature verification for local development tests") }}</span>
      </label>

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
import { computed, defineComponent, h, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import adminService from "../../services/adminService"

const { t } = useI18n()

const status = reactive({
  installedVersion: "",
  updateDirectory: "",
  stagingDirectory: "",
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
const staging = ref(null)
const errorMessage = ref("")
const isChecking = ref(false)
const isVerifying = ref(false)
const isRunningPreflight = ref(false)
const isStaging = ref(false)

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
  } catch (error) {
    console.error("[SystemUpdate] Failed to load update status:", error)
  }
})

async function checkManifest() {
  errorMessage.value = ""
  verification.value = null
  preflight.value = null
  staging.value = null
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

async function stagePackage() {
  errorMessage.value = ""
  isStaging.value = true

  try {
    const data = await adminService.stageSystemUpdatePackage({
      manifestSource: form.manifestSource,
      packagePath: form.packagePath || null,
      signaturePath: form.signaturePath || null,
      trustedPublicKey: form.trustedPublicKey || null,
      skipSignature: form.skipSignature,
    })

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
  } catch (error) {
    const responseData = error?.response?.data || null

    errorMessage.value = getErrorMessage(error)

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
