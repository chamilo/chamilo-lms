<template>
  <div class="space-y-6">
    <!-- Stepper -->
    <div class="flex items-center gap-3">
      <Step :index="1" :current="step" :label="t('Options')" />
      <Line />
      <Step :index="2" :current="step" :label="t('Select items')" />
      <Line />
      <Step :index="3" :current="step" :label="t('Apply recycle')" />
    </div>

    <CMAlert v-if="error" type="error" :text="error" />
    <CMAlert v-if="notice" type="success" :text="notice" />

    <!-- STEP 1: Options -->
    <section v-if="step===1" class="rounded-lg border border-gray-25 p-4 space-y-4">
      <h3 class="text-sm font-semibold text-gray-90">{{ t('Recycle options') }}</h3>

      <label class="flex items-center gap-2">
        <input type="radio" value="full_recycle" v-model="recycleOption" />
        <span class="text-sm text-gray-90">{{ t('Delete everything (irreversible)') }}</span>
      </label>
      <label class="flex items-center gap-2">
        <input type="radio" value="select_items" v-model="recycleOption" />
        <span class="text-sm text-gray-90">{{ t('Let me select learning objects') }}</span>
      </label>

      <div v-if="recycleOption==='full_recycle'" class="mt-2">
        <label class="block text-xs font-medium text-gray-60 mb-1">{{ t('Type the course code to confirm') }}</label>
        <input
          v-model.trim="confirm"
          class="w-64 rounded border border-gray-25 p-2 text-sm"
          :placeholder="courseCode"
          autocomplete="off"
        />
      </div>

      <CMAlert type="warning"
               :text="t('This will remove or reset selected resources. Action cannot be undone.')" />

      <div class="flex justify-end gap-3">
        <button class="btn-primary" @click="nextFromStep1" :disabled="loading">
          <i class="mdi" :class="recycleOption==='select_items' ? 'mdi-arrow-right' : 'mdi-recycle'"></i>
          {{ recycleOption==='select_items' ? t('Continue') : t('Recycle') }}
        </button>
      </div>
    </section>

    <!-- STEP 2: Tree selection -->
    <section v-if="step===2" class="space-y-4">
      <ResourceSelector
        :groups="tree"
        v-model="selections"
        :title="t('Select resources to recycle')"
        :emptyText="t('No resources available in this course.')"
      />

      <div class="flex justify-between">
        <button class="btn-secondary" @click="step=1" :disabled="loading">
          <i class="mdi mdi-arrow-left"></i> {{ t('Back') }}
        </button>
        <button class="btn-danger" @click="doRecycle" :disabled="loading">
          <i class="mdi mdi-recycle"></i> {{ t('Recycle selected') }}
        </button>
      </div>
    </section>

    <!-- STEP 3: Done -->
    <section v-if="step===3">
      <CMInfo :title="t('Recycle completed')" />
    </section>

    <CMLoader v-if="loading" />
  </div>
</template>

<script setup>
/* All strings/comments in English */
import { ref, onMounted, watch } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import svc, { courseContextParams } from "../../services/courseMaintenance"
import ResourceSelector from "../../components/coursemaintenance/ResourceSelector.vue"

const { t } = useI18n()
const route = useRoute()

/** Current resource node id (from route param) */
const node = ref(Number(route.params.node || 0))
watch(() => route.params.node, (v) => { node.value = Number(v || 0) })

/** Current course code (only for placeholder confirmation) */
const courseCode = ref(window?.chamilo?.course?.code || "")

/** UI state */
const step = ref(1)
const loading = ref(false)
const error = ref("")
const notice = ref("")

/** Options */
const recycleOption = ref("select_items") // 'full_recycle' | 'select_items'
const confirm = ref("")                   // confirmation text for full_recycle

/** Selection model for the tree: { [type]: { [id]: 1 } } */
const tree = ref([])        // groups from backend
const selections = ref({})  // v-model passed to ResourceSelector

onMounted(async () => {
  // If you later add an endpoint to load defaults, set them here.
  recycleOption.value = "select_items"
})

/** Step 1 -> Step 2 or execute immediately if full_recycle */
async function nextFromStep1() {
  error.value = ""; notice.value = ""
  if (recycleOption.value === "full_recycle") {
    return doRecycle()
  }
  try {
    loading.value = true

    const data = await svc.fetchRecycleResources(node.value)

    tree.value = Array.isArray(data.tree) ? data.tree : []
    // ResourceSelector normalizes the tree internally via its composable.
    step.value = 2
  } catch (e) {
    error.value = e?.response?.data?.error || e?.message || t("Error loading resources.")
  } finally {
    loading.value = false
  }
}

/** Execute recycle on backend */
async function doRecycle() {
  error.value = ""; notice.value = ""
  try {
    loading.value = true
    const payload = {
      // API expects something like: { recycleOption: 'full_recycle'|'select_items', confirm?: string, resources?: map }
      recycleOption: recycleOption.value,
      confirm: recycleOption.value === "full_recycle" ? confirm.value : undefined,
      resources: recycleOption.value === "select_items" ? selections.value : undefined,
    }
    const res = await svc.recycleExecute(node.value, payload)
    notice.value = res?.message || t("Recycle finished.")
    step.value = 3
  } catch (e) {
    error.value = e?.response?.data?.error || t("Failed to recycle course.")
  } finally {
    loading.value = false
  }
}
</script>

<script>
/* Inline lightweight UI helpers – same style you use elsewhere */
export default {
  components: {
    Step: {
      name: "Step",
      props: { index: Number, current: Number, label: String },
      computed: {
        state() { if (this.index < this.current) return "done"; if (this.index === this.current) return "active"; return "todo" },
        ringClass() {
          return { done: "bg-support-1 border-gray-25 text-gray-90", active: "bg-gray-10 border-gray-25 text-gray-90", todo: "bg-gray-10 border-gray-25 text-gray-50" }[this.state]
        },
        textClass() { return { done: "text-gray-90", active: "text-gray-90", todo: "text-gray-50" }[this.state] },
        icon() { return this.state === "done" ? "✓" : (this.state === "active" ? "•" : "○") },
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
          return {
            info: "bg-support-2 text-info border-gray-25",
            success: "bg-support-2 text-success border-gray-25",
            warning: "bg-support-6 text-warning border-gray-25",
            error: "bg-support-6 text-danger border-gray-25",
          }[this.type] || "bg-gray-10 text-gray-90 border-gray-25"
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
