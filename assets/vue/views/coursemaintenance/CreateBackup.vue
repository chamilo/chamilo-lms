<template>
  <div class="space-y-6">
    <!-- Stepper -->
    <div class="flex items-center gap-3">
      <Step
        :index="1"
        :current="step"
        :label="t('Options')"
      />
      <Line />
      <Step
        :index="2"
        :current="step"
        :label="t('Select items')"
      />
      <Line />
      <Step
        :index="3"
        :current="step"
        :label="t('Create .mbz')"
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

    <!-- STEP 1: Options -->
    <section
      v-if="step === 1"
      class="grid grid-cols-1 gap-4 lg:grid-cols-2"
    >
      <div class="rounded-lg border border-gray-25 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-90">{{ t("Backup scope") }}</h3>

        <label class="mb-2 flex items-center gap-2">
          <input
            type="radio"
            value="full"
            v-model="scope"
          />
          <span class="text-sm text-gray-90">{{ t("Full course") }}</span>
        </label>
        <label class="flex items-center gap-2">
          <input
            type="radio"
            value="selected"
            v-model="scope"
          />
          <span class="text-sm text-gray-90">{{ t("Let me select learning objects") }}</span>
        </label>

        <CMAlert
          type="warning"
          class="mt-3"
          :text="t('Selected items will be the only ones included in the exported file.')"
        />
      </div>

      <div class="rounded-lg border border-gray-25 p-4 space-y-3">
        <h3 class="mb-1 text-sm font-semibold text-gray-90">{{ t("Moodle export") }}</h3>

        <label class="block text-xs font-medium text-gray-60">{{ t("Moodle version") }}</label>
        <select
          v-model="moodleVersion"
          class="w-full rounded border border-gray-25 p-2 text-sm"
        >
          <option value="3">Moodle 3.x</option>
          <option value="4">Moodle 4.x</option>
        </select>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="sm:col-span-1">
            <label class="block text-xs font-medium text-gray-60 mb-1">{{ t("Admin ID") }} *</label>
            <input
              v-model.trim="adminId"
              class="w-full rounded border border-gray-25 p-2 text-sm"
              :placeholder="t('Internal integer user id')"
            />
            <p class="mt-1 text-[11px] leading-4 text-gray-50">
              {{ t("Moodle requires an internal user id stored in XML. If in doubt, use 1.") }}
            </p>
          </div>

          <div class="sm:col-span-1">
            <label class="block text-xs font-medium text-gray-60 mb-1">{{ t("Administrator login") }} *</label>
            <input
              v-model.trim="adminLogin"
              class="w-full rounded border border-gray-25 p-2 text-sm"
              :placeholder="t('Username to appear as owner of imported items')"
            />
          </div>

          <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-gray-60 mb-1">{{ t("Administrator e-mail") }} *</label>
            <input
              v-model.trim="adminEmail"
              class="w-full rounded border border-gray-25 p-2 text-sm"
              type="email"
              :placeholder="t('Email to use in the archive metadata')"
            />
          </div>
        </div>

        <CMAlert
          class="mt-2"
          :text="t('A .mbz file will be generated. You will be able to download it once finished.')"
        />
      </div>

      <div class="col-span-full flex justify-end gap-3">
        <button
          class="btn-secondary"
          :disabled="loading"
          @click="resetAll"
        >
          <i class="mdi mdi-refresh"></i> {{ t("Reset") }}
        </button>
        <button
          class="btn-primary"
          :disabled="loading || !canContinueFromStep1"
          @click="nextFromStep1"
        >
          <i class="mdi mdi-arrow-right"></i> {{ scope === "selected" ? t("Continue") : t("Create .mbz") }}
        </button>
      </div>
    </section>

    <!-- STEP 2: Select items -->
    <section
      v-if="step === 2"
      class="space-y-4"
    >
      <ResourceSelector
        :groups="tree"
        v-model="selections"
        :title="t('Select resources to include')"
        :emptyText="t('No resources available in this course.')"
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
          @click="doExport"
          :disabled="loading"
        >
          <i class="mdi mdi-download"></i> {{ t("Create .mbz") }}
        </button>
      </div>
    </section>

    <!-- STEP 3: Done -->
    <section v-if="step === 3">
      <CMInfo :title="t('Export completed')">
        <template #body>
          <p class="text-sm text-gray-50">
            {{ t("Your Moodle backup has been created. You can find it in the course backups list.") }}
          </p>
        </template>
      </CMInfo>
    </section>

    <CMLoader v-if="loading" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import svc from "../../services/courseMaintenance"
import ResourceSelector from "../../components/coursemaintenance/ResourceSelector.vue"

const { t } = useI18n()
const route = useRoute()
const node = ref(Number(route.params.node || 0))

/* UI */
const step = ref(1)
const loading = ref(false)
const error = ref("")
const notice = ref("")

/* Options */
const scope = ref("full") // 'full' | 'selected'
const moodleVersion = ref("4") // '3' | '4'
const adminId = ref("")
const adminLogin = ref("")
const adminEmail = ref("")

/* Selector (Step 2) */
const tree = ref([]) // groups from backend
const selections = ref({}) // v-model for ResourceSelector

const canContinueFromStep1 = computed(() => {
  const idOk = /^\d+$/.test(adminId.value.trim())
  return idOk && adminLogin.value.trim() !== "" && adminEmail.value.trim() !== ""
})

onMounted(async () => {
  try {
    loading.value = true
    const data = await svc.moodleExportOptions(node.value)
    moodleVersion.value = String(data?.defaults?.moodleVersion || "4")

    const adm = data?.defaults?.admin || {}
    if (!adminId.value) adminId.value = String(adm.id || "")
    if (!adminLogin.value) adminLogin.value = String(adm.username || "")
    if (!adminEmail.value) adminEmail.value = String(adm.email || "")
  } catch {
  } finally {
    loading.value = false
  }
})

function resetAll() {
  scope.value = "full"
  moodleVersion.value = "4"
  adminId.value = ""
  adminLogin.value = ""
  adminEmail.value = ""
  selections.value = {}
  step.value = 1
  error.value = ""
  notice.value = ""
}

async function nextFromStep1() {
  error.value = ""
  notice.value = ""
  if (!canContinueFromStep1.value) {
    error.value = t("Please complete all required fields.")
    return
  }

  if (scope.value === "selected") {
    try {
      loading.value = true
      const data = await svc.moodleExportResources(node.value)
      tree.value = Array.isArray(data.tree) ? data.tree : []
      step.value = 2
    } catch (e) {
      error.value = e?.response?.data?.error || e?.message || t("Error loading resources.")
    } finally {
      loading.value = false
    }
  } else {
    await doExport()
  }
}

async function doExport() {
  error.value = ""
  notice.value = ""
  try {
    loading.value = true
    const payload = {
      moodleVersion: moodleVersion.value, // '3' | '4'
      scope: scope.value, // 'full' | 'selected'
      adminId: adminId.value.trim(),
      adminLogin: adminLogin.value.trim(),
      adminEmail: adminEmail.value.trim(),
      resources: scope.value === "selected" ? selections.value : undefined,
    }

    const res = await svc.moodleExportExecute(node.value, payload)

    notice.value = res?.message || t("Export finished.")
    step.value = 3
  } catch (e) {
    error.value = e?.response?.data?.error || e?.message || t("Failed to create Moodle backup.")
  } finally {
    loading.value = false
  }
}
</script>

<script>
/* Lightweight inline helpers to keep the style consistent */
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
            <span class="text-sm text-gray-90">Working…</span>
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
