<template>
  <div class="space-y-6 cm-copy-course">
    <!-- Stepper -->
    <div class="flex items-center gap-3">
      <Step
        :index="1"
        :current="step"
        :label="t('Select source course')"
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
        :label="t('Copy course')"
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

    <!-- STEP 1: Source + options -->
    <section
      v-if="step === 1"
      class="grid grid-cols-1 gap-4 lg:grid-cols-2"
    >
      <div class="rounded-lg border border-gray-25 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-90">{{ t("Source") }}</h3>

        <div
          v-if="useUnifiedPicker"
          class="space-y-2"
        >
          <BaseSearchSelect
            v-model="sourceCourseId"
            :options="courseOptions"
            :label="t('Course to copy from')"
            :placeholder="t('Search by code or title...')"
            :emptyMessage="t('No courses match your search.')"
            :clearable="true"
            :virtual="true"
            :filterFields="['label', 'sublabel']"
            input-id="copycourse-source"
          />
        </div>

        <div
          v-else
          class="space-y-2"
        >
          <label class="mb-1 block text-xs font-medium text-gray-60">
            {{ t("Course to copy from") }}
          </label>

          <div class="relative">
            <input
              v-model.trim="courseQuery"
              :placeholder="t('Search by code or title...')"
              class="w-full rounded border border-gray-25 p-2 pr-8 text-sm"
              type="text"
            />
            <button
              v-if="courseQuery"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-50 hover:text-gray-90"
              @click="courseQuery = ''"
              :aria-label="t('Clear search')"
            >
              <i class="mdi mdi-close"></i>
            </button>
          </div>

          <select
            v-model="sourceCourseId"
            class="w-full rounded border border-gray-25 p-2 text-sm"
          >
            <option :value="''">{{ t("Select a course") }}</option>
            <option
              v-for="c in filteredCourses"
              :key="c.id"
              :value="c.id"
            >
              {{ c.code }} — {{ c.title }}
            </option>
          </select>

          <p
            v-if="!filteredCourses.length"
            class="text-xs text-gray-50"
          >
            {{ t("No courses match your search.") }}
          </p>
        </div>
      </div>

      <div class="rounded-lg border border-gray-25 p-4">
        <h3 class="mb-3 text-sm font-semibold text-gray-90">{{ t("Copy options") }}</h3>

        <div class="space-y-3">
          <label class="flex items-center gap-2">
            <input
              type="radio"
              value="full_copy"
              v-model="copyOption"
            />
            <span class="text-sm text-gray-90">{{ t("Full copy") }}</span>
          </label>
          <label class="flex items-center gap-2">
            <input
              type="radio"
              value="select_items"
              v-model="copyOption"
            />
            <span class="text-sm text-gray-90">{{ t("Let me select learning objects") }}</span>
          </label>

          <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label class="flex items-center gap-2">
              <input
                type="checkbox"
                v-model="includeUsers"
              />
              <span class="text-sm text-gray-90">{{ t("Include users (enrollments)") }}</span>
            </label>
            <label class="flex items-center gap-2">
              <input
                type="checkbox"
                v-model="resetDates"
              />
              <span class="text-sm text-gray-90">{{ t("Reset dates") }}</span>
            </label>
          </div>

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
                <span class="text-sm text-gray-90">
                  {{ t("Rename file (eg file.pdf becomes file_1.pdf)") }}
                </span>
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
          :disabled="loading || !sourceCourseId"
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
      <CMInfo :title="t('Ready to copy')">
        <template #body>
          <p class="text-sm text-gray-50">
            {{
              t("We will copy content from the selected course into this course. You can go back to change options.")
            }}
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
          @click="doCopy"
          :disabled="loading"
        >
          <i class="mdi mdi-content-copy"></i> {{ t("Start copying") }}
        </button>
      </div>
    </section>

    <!-- STEP 3: Resource selection (shared component) -->
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
        :title="t('Select resources to copy')"
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
          @click="doCopy"
          :disabled="loading"
        >
          <i class="mdi mdi-content-copy"></i> {{ t("Copy selected") }}
        </button>
      </div>
    </section>

    <!-- STEP 4: Done -->
    <section v-if="step === 4">
      <CMInfo :title="t('Copy completed.')" />
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
import BaseSearchSelect from "../../components/basecomponents/BaseSearchSelect.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const useUnifiedPicker = ref(true)

/* State */
const node = ref(Number(route.params.node || 0))
const step = ref(1)
const loading = ref(false)
const error = ref("")
const notice = ref("")

/* step 1 */
const courses = ref([]) // [{ id, code, title, ... }]
const sourceCourseId = ref("") // string|number
const courseQuery = ref("") // kept for legacy UI
const copyOption = ref("full_copy") // 'full_copy' | 'select_items'
const includeUsers = ref(false)
const resetDates = ref(true)
const sameFileNameOption = ref(2) // 1/2/3

/* step 3 */
const tree = ref([]) // groups from backend
const notices = ref([])
const selections = ref({}) // { [type]: { [id]: 1 } }

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
    const data = await svc.getCopyOptions(node.value)
    courses.value = Array.isArray(data.courses) ? data.courses : []
    copyOption.value = data?.defaults?.copyOption || "full_copy"
    includeUsers.value = !!data?.defaults?.includeUsers
    resetDates.value = data?.defaults?.resetDates ?? true
    sameFileNameOption.value =
      typeof data?.defaults?.sameFileNameOption !== "undefined" ? data.defaults.sameFileNameOption : 2
  } catch (e) {
    error.value = e?.response?.data?.error || t("Could not load options.")
  } finally {
    loading.value = false
  }
}

/* Normalize courses to dropdown options ({ id, label, sublabel }) */
const courseOptions = computed(() =>
  (courses.value || []).map((c) => ({
    id: c.id,
    label: `${c.code || ""} — ${c.title || ""}`.trim(),
    sublabel: c.category ? String(c.category) : "", // optional secondary line
    payload: c,
  })),
)

/* Filtering for course dropdown (kept intact) */
const filteredCourses = computed(() => {
  const q = courseQuery.value.trim().toLowerCase()
  if (!q) return courses.value
  return courses.value.filter(
    (c) => (c.code || "").toLowerCase().includes(q) || (c.title || "").toLowerCase().includes(q),
  )
})

/* Actions */
function resetAll() {
  sourceCourseId.value = ""
  courseQuery.value = ""
  copyOption.value = "full_copy"
  includeUsers.value = false
  resetDates.value = true
  sameFileNameOption.value = 2
  selections.value = {}
  error.value = ""
  notice.value = ""
}

async function nextFromStep1() {
  error.value = ""
  try {
    if (!sourceCourseId.value) throw new Error(t("Select source course"))
    loading.value = true

    if (copyOption.value === "select_items") {
      // Get resource tree of source; ResourceSelector normalizes internally
      const data = await svc.fetchCopyResources(node.value, sourceCourseId.value)
      tree.value = Array.isArray(data.tree) ? data.tree : []
      notices.value = data.warnings || []
      step.value = 3
    } else {
      step.value = 2
    }
  } catch (e) {
    error.value = e?.response?.data?.error || e?.message || t("Error loading resources.")
  } finally {
    loading.value = false
  }
}

async function doCopy() {
  error.value = ""
  notice.value = ""
  try {
    if (!sourceCourseId.value) throw new Error(t("Select source course"))
    loading.value = true

    const payload = {
      sourceCourseId: sourceCourseId.value,
      copyOption: copyOption.value,
      includeUsers: !!includeUsers.value,
      resetDates: !!resetDates.value,
      sameFileNameOption: sameFileNameOption.value,
      resources: selections.value,
    }

    const res = await svc.copyFromCourse(node.value, payload)
    notice.value = res.message || t("Copy completed.")
    step.value = 4

    if (res.redirectUrl) {
      const u = new URL(res.redirectUrl, window.location.origin)
      const backendQuery = Object.fromEntries(u.searchParams.entries())
      const keep = {}
      for (const k of ["gradebook", "origin"]) if (route.query[k] != null) keep[k] = route.query[k]
      router.push({ path: u.pathname, query: { ...backendQuery, ...keep } })
    }
  } catch (e) {
    error.value = e?.response?.data?.error || t("Copy failed")
  } finally {
    loading.value = false
  }
}
</script>

<script>
/* Minimal inline helpers to match your UI kit */
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
