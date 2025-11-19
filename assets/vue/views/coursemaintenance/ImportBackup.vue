<template>
  <div class="space-y-6 cm-import-backup">
    <!-- Stepper -->
    <div class="flex items-center gap-3">
      <Step
        :index="1"
        :current="step"
        :label="t('Select source')"
      />
      <Line />
      <Step
        :index="2"
        :current="step"
        :label="t('Options')"
      />
      <Line />
      <Step
        :index="3"
        :current="step"
        :label="t('Select items')"
      />
      <Line />
      <Step
        :index="4"
        :current="step"
        :label="t('Restore')"
      />
    </div>

    <CMAlert
      v-if="error"
      type="error"
      :text="error"
    />
    <CMAlert
      v-if="notice"
      type="success"
      :text="notice"
    />

    <!-- STEP 1: Source -->
    <section
      v-if="step === 1"
      class="grid grid-cols-1 gap-4 lg:grid-cols-2"
    >
      <div class="rounded-lg border border-gray-25 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-90">{{ t("Source") }}</h3>

        <div class="space-y-3">
          <!-- Local archive -->
          <label class="flex items-center gap-2">
            <input
              type="radio"
              class="peer"
              value="local"
              v-model="backupType"
            />
            <span class="text-sm text-gray-90">{{ t("Local file (.zip, .mbz, .tgz, .gz)") }}</span>
          </label>
          <input
            v-if="backupType === 'local'"
            type="file"
            accept=".zip,.mbz,.tgz,.gz"
            @change="onLocalFile"
            class="w-full rounded border border-gray-25 p-2 text-sm"
          />
          <div
            v-if="backupType === 'local' && localFile"
            class="mt-1 text-tiny text-gray-50"
          >
            {{ localFile.name }}
          </div>

          <!-- Server archive -->
          <label class="mt-4 flex items-center gap-2">
            <input
              type="radio"
              class="peer"
              value="server"
              v-model="backupType"
            />
            <span class="text-sm text-gray-90">{{ t("Server file") }}</span>
          </label>
          <select
            v-if="backupType === 'server'"
            v-model="serverFilename"
            class="w-full rounded border border-gray-25 p-2 text-sm"
          >
            <option value="">{{ t("Select a backup") }}</option>
            <option
              v-for="b in backups"
              :key="b.file"
              :value="b.file"
            >
              {{ b.label }}
            </option>
          </select>
        </div>
      </div>

      <!-- Import options -->
      <div class="rounded-lg border border-gray-25 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-90">{{ t("Import options") }}</h3>

        <div class="space-y-3">
          <label class="flex items-center gap-2">
            <input
              type="radio"
              value="full_backup"
              v-model="importOption"
            />
            <span class="text-sm text-gray-90">{{ t("Import full backup") }}</span>
          </label>
          <label class="flex items-center gap-2">
            <input
              type="radio"
              value="select_items"
              v-model="importOption"
            />
            <span class="text-sm text-gray-90">{{ t("Let me select learning objects") }}</span>
          </label>

          <div class="mt-4">
            <p class="mb-2 text-sm font-medium text-gray-90">
              {{ t("When a file with the same name exists") }}
            </p>
            <div class="space-y-2">
              <label class="flex items-center gap-2">
                <input
                  type="radio"
                  :value="1"
                  v-model.number="sameFileNameOption"
                />
                <span class="text-sm text-gray-90">{{ t("Skip same file name") }}</span>
              </label>
              <label class="flex items-center gap-2">
                <input
                  type="radio"
                  :value="2"
                  v-model.number="sameFileNameOption"
                />
                <span class="text-sm text-gray-90">{{ t("Rename file (eg file.pdf becomes file_1.pdf)") }}</span>
              </label>
              <label class="flex items-center gap-2">
                <input
                  type="radio"
                  :value="3"
                  v-model.number="sameFileNameOption"
                />
                <span class="text-sm text-gray-90">{{ t("Overwrite file") }}</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="col-span-full flex justify-end gap-3">
        <button
          class="btn-secondary"
          @click="resetAll"
          :disabled="loading"
        >
          <i class="mdi mdi-refresh"></i> {{ t("Reset") }}
        </button>
        <button
          class="btn-primary"
          @click="nextFromStep1"
          :disabled="loading || !canContinueFromStep1"
        >
          <i class="mdi mdi-arrow-right"></i> {{ t("Continue") }}
        </button>
      </div>
    </section>

    <!-- STEP 2: Review -->
    <section
      v-if="step === 2"
      class="space-y-4"
    >
      <CMInfo :title="t('Ready to import')">
        <template #body>
          <p class="text-sm text-gray-50">
            {{ t("We will restore the backup into this course. You can go back to change options.") }}
          </p>
        </template>
      </CMInfo>
      <div class="flex justify-between">
        <button
          class="btn-secondary"
          @click="step = 1"
          :disabled="loading"
        >
          <i class="mdi mdi-arrow-left"></i> {{ t("Back") }}
        </button>
        <button
          class="btn-primary"
          @click="doRestore"
          :disabled="loading"
        >
          <i class="mdi mdi-database-import-outline"></i> {{ t("Start import") }}
        </button>
      </div>
    </section>

    <!-- STEP 3: Tree selection -->
    <section
      v-if="step === 3"
      class="space-y-4"
    >
      <CMAlert
        v-for="n in notices"
        :key="n"
        type="warning"
        :text="n"
      />

      <ResourceSelector
        :groups="tree"
        v-model="selections"
        :title="t('Select resources to import')"
        :emptyText="t('No resources available in this backup.')"
      />

      <div class="flex justify-between">
        <button
          class="btn-secondary"
          @click="step = 1"
          :disabled="loading"
        >
          <i class="mdi mdi-arrow-left"></i> {{ t("Back") }}
        </button>
        <button
          class="btn-primary"
          @click="doRestore"
          :disabled="loading"
        >
          <i class="mdi mdi-database-import-outline"></i> {{ t("Import selected") }}
        </button>
      </div>
    </section>

    <!-- STEP 4: Done -->
    <section v-if="step === 4">
      <CMInfo :title="t('Import completed')" />
    </section>

    <CMLoader v-if="loading" />
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import svc from "../../services/courseMaintenance"
import ResourceSelector from "../../components/coursemaintenance/ResourceSelector.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

/* State */
const node = ref(Number(route.params.node || 0))
const step = ref(1)
const loading = ref(false)
const error = ref("")
const notice = ref("")

const backups = ref([])
const backupType = ref("local")
const localFile = ref(null)
const serverFilename = ref("")

const importOption = ref("full_backup")
const sameFileNameOption = ref(2) // rename

const backupId = ref("")
const tree = ref([]) // groups as returned by backend
const notices = ref([])
const selections = ref({}) // { [type]: { [id]: 1 } }

const canContinueFromStep1 = computed(() => {
  if (backupType.value === "local") return !!localFile.value
  if (backupType.value === "server") return !!serverFilename.value
  return false
})

watch(backupType, (v) => {
  if (v === "local") serverFilename.value = ""
  else localFile.value = null
})

/* Lifecycle */
onMounted(bootstrap)
watch(
  () => route.params.node,
  (v) => {
    node.value = Number(v || 0)
    bootstrap()
  },
)

async function bootstrap() {
  error.value = ""
  try {
    loading.value = true
    const data = await svc.getOptions(node.value)
    backups.value = (data.backups || []).map((b) => ({
      file: b.file,
      label: b.label || (b.course_code || "") + " (" + (b.date || "") + ")",
    }))
    importOption.value = data?.defaults?.importOption || "full_backup"
    sameFileNameOption.value =
      typeof data?.defaults?.sameFileNameOption !== "undefined" ? data.defaults.sameFileNameOption : 2
  } catch (e) {
    error.value = e?.response?.data?.error || t("Could not load options.")
  } finally {
    loading.value = false
  }
}

/* Handlers */
function onLocalFile(e) {
  localFile.value = e?.target?.files?.[0] || null
}

function resetAll() {
  backupType.value = "local"
  localFile.value = null
  serverFilename.value = ""
  importOption.value = "full_backup"
  sameFileNameOption.value = 2
  selections.value = {}
  error.value = ""
  notice.value = ""
}

function pushWithQuery(targetPathOrName) {
  if (!targetPathOrName) return
  if (typeof targetPathOrName === "string") {
    if (targetPathOrName.startsWith("http")) {
      window.location.href = targetPathOrName
    } else if (targetPathOrName.startsWith("/")) {
      router.push({ path: targetPathOrName, query: route.query })
    } else {
      router.push({ path: `/${targetPathOrName}`, query: route.query })
    }
  } else {
    router.push({ ...targetPathOrName, query: { ...route.query, ...(targetPathOrName.query || {}) } })
  }
}

async function nextFromStep1() {
  error.value = ""
  try {
    loading.value = true

    // Standard archive flows (local/server): .zip/.mbz/.tgz/.gz
    let res
    if (backupType.value === "local") {
      if (!localFile.value) throw new Error(t("Please select a backup file (.zip, .mbz, .tgz, .gz)."))
      res = await svc.uploadFile(node.value, localFile.value)
    } else {
      if (!serverFilename.value) throw new Error(t("Please choose a server backup."))
      res = await svc.chooseServerFile(node.value, serverFilename.value)
    }
    backupId.value = res.backupId

    if (importOption.value === "select_items") {
      const data = await svc.fetchResources(node.value, backupId.value)
      const groups = Array.isArray(data.tree) ? data.tree : []
      tree.value = groups
      // accept either .notices or .warnings (backend sends 'warnings')
      notices.value = data.notices || data.warnings || []
      step.value = 3
    } else {
      step.value = 2
    }
  } catch (e) {
    error.value = e?.response?.data?.error || e?.message || t("Error selecting source.")
  } finally {
    loading.value = false
  }
}

async function doRestore() {
  error.value = ""
  notice.value = ""
  try {
    loading.value = true
    const payload = {
      importOption: importOption.value,
      sameFileNameOption: sameFileNameOption.value,
      resources: selections.value,
    }
    const res = await svc.restoreBackup(node.value, backupId.value, payload)
    notice.value = res.message || t("Import finished")
    step.value = 4
    if (res.redirectUrl) pushWithQuery(res.redirectUrl)
  } catch (e) {
    error.value = e?.response?.data?.error || t("Failed to import backup.")
  } finally {
    loading.value = false
  }
}
</script>

<script>
/* Small inline helpers to match your UI kit */
export default {
  components: {
    Step: {
      name: "Step",
      props: { index: Number, current: Number, label: String },
      computed: {
        state() {
          if (this.index < this.current) return "done"
          if (this.index === this.current) return "active"
          return "todo"
        },
        ringClass() {
          return {
            done: "bg-support-1 border-gray-25 text-gray-90",
            active: "bg-gray-10 border-gray-25 text-gray-90",
            todo: "bg-gray-10 border-gray-25 text-gray-50",
          }[this.state]
        },
        textClass() {
          return { done: "text-gray-90", active: "text-gray-90", todo: "text-gray-50" }[this.state]
        },
        icon() {
          return this.state === "done" ? "✓" : this.state === "active" ? "•" : "○"
        },
      },
      template: `
        <div class="flex items-center gap-2" :class="textClass">
          <span class="h-6 w-6 rounded-full text-center text-xs leading-6 border" :class="ringClass">{{ icon }}</span>
          <span class="text-sm font-medium">{{ label }}</span>
        </div>
      `,
    },
    Line: { name: "Line", template: `<div class="h-px flex-1 bg-gray-25"></div>` },
    CMLoader: {
      name: "CMLoader",
      template: `
        <div class="fixed inset-0 z-30 grid place-items-center bg-black/10">
          <div class="flex items-center gap-3 rounded-lg bg-white px-4 py-3 shadow">
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"></span>
            <span class="text-sm text-gray-90">Loading…</span>
          </div>
        </div>
      `,
    },
    CMAlert: {
      name: "CMAlert",
      props: { type: { type: String, default: "info" }, text: String },
      computed: {
        tone() {
          return (
            {
              info: "bg-support-2 text-info border-gray-25",
              success: "bg-support-2 text-success border-gray-25",
              warning: "bg-support-6 text-warning border-gray-25",
              error: "bg-support-6 text-danger border-gray-25",
            }[this.type] || "bg-gray-10 text-gray-90 border-gray-25"
          )
        },
      },
      template: `<div class="rounded-md border px-3 py-2 text-sm" :class="tone">{{ text }}</div>`,
    },
    CMInfo: {
      name: "CMInfo",
      props: { title: String },
      template: `
        <div class="rounded-lg border border-gray-25 p-4">
          <h3 class="mb-2 text-sm font-semibold text-gray-90">{{ title }}</h3>
          <slot name="body"><p class="text-sm text-gray-50">—</p></slot>
        </div>
      `,
    },
  },
}
</script>
