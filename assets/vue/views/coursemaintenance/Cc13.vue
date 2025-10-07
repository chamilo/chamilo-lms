<template>
  <div class="space-y-6">
    <section class="rounded-lg border border-gray-200 p-4">
      <h3 class="mb-3 text-sm font-semibold text-gray-800">{{ t("IMS Common Cartridge 1.3") }}</h3>

      <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <!-- Export -->
        <div class="rounded-lg border border-gray-100 p-4">
          <h4 class="mb-2 text-sm font-semibold text-gray-800">{{ t("Export to CC 1.3") }}</h4>
          <p class="mb-3 text-sm text-gray-600">
            {{ t("Generate a Common Cartridge package for this course.") }}
          </p>

          <div class="mb-3">
            <label class="mb-1 block text-xs font-medium text-gray-600">{{ t("Scope") }}</label>
            <select v-model="scope" class="w-full rounded border border-gray-300 p-2 text-sm">
              <option value="full">{{ t("Full course") }}</option>
              <option value="selected">{{ t("Selected items") }}</option>
            </select>
          </div>

          <!-- Tree -->
          <div v-if="scope==='selected'" class="rounded-md border border-gray-200">
            <div class="flex items-center justify-between border-b px-3 py-2">
              <div class="text-xs text-gray-600">{{ t("Select resources") }}</div>
              <div class="flex gap-2 text-xs">
                <button class="link" @click="checkAll(true)">{{ t("Select all") }}</button>
                <span class="text-gray-300">•</span>
                <button class="link" @click="checkAll(false)">{{ t("Clear") }}</button>
              </div>
            </div>

            <div v-if="loadingResources" class="p-3 text-xs text-gray-500">{{ t("Loading resources…") }}</div>
            <div v-else class="max-h-72 space-y-3 overflow-auto p-3">
              <div v-if="warnings.length" class="text-xs text-amber-700">
                <ul class="list-disc pl-4">
                  <li v-for="w in warnings" :key="w">{{ w }}</li>
                </ul>
              </div>

              <div v-for="group in tree" :key="group.type" class="rounded border border-gray-100">
                <button
                  class="flex w-full items-center justify-between px-3 py-2 text-left text-sm font-medium"
                  @click="toggle(group.type)"
                >
                  <span class="flex items-center gap-2">
                    <i class="mdi mdi-folder-outline"></i>{{ group.title }}
                  </span>
                  <i :class="open[group.type] ? 'mdi mdi-chevron-up' : 'mdi mdi-chevron-down'"></i>
                </button>

                <div v-show="open[group.type]" class="border-t p-2">
                  <div
                    v-for="item in group.items"
                    :key="`${group.type}:${item.id}`"
                    class="flex items-center gap-2 px-2 py-1 text-sm"
                  >
                    <input
                      type="checkbox"
                      :id="`res-${group.type}-${item.id}`"
                      :checked="isChecked(group.type, item.id)"
                      @change="toggleItem(group.type, item.id, $event.target.checked)"
                    />
                    <label class="cursor-pointer" :for="`res-${group.type}-${item.id}`">
                      {{ item.label }}
                    </label>
                    <span v-if="item.children" class="ml-2 text-xs text-gray-500">
                      ({{ t("contains {n} items",{n:item.children.length}) }})
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Export button -->
          <div class="mt-3 flex justify-end">
            <button class="btn-primary" :disabled="loadingExport" @click="doExport">
              <i class="mdi mdi-package-variant-closed"></i> {{ t("Export") }}
            </button>
          </div>

          <p v-if="serverMessage" class="mt-2 text-xs text-gray-500">
            {{ serverMessage }}
          </p>
        </div>

        <!-- Import -->
        <div class="rounded-lg border border-gray-100 p-4">
          <h4 class="mb-2 text-sm font-semibold text-gray-800">{{ t("Import from CC 1.3") }}</h4>
          <p class="mb-3 text-sm text-gray-600">
            {{ t("Upload a Common Cartridge 1.3 (.imscc or .zip) to import resources.") }}
          </p>
          <input
            type="file"
            accept=".imscc,.zip"
            @change="onFile"
            class="w-full rounded border border-gray-300 p-2 text-sm"
          />
          <div class="mt-3 flex justify-end">
            <button class="btn-primary" :disabled="!file || loadingImport" @click="doImport">
              <i class="mdi mdi-package-down"></i> {{ t("Import") }}
            </button>
          </div>
        </div>
      </div>
    </section>

    <CMAlert v-if="error" type="error" :text="error" />
    <CMAlert v-if="notice" type="success" :text="notice" />
    <CMLoader v-if="loadingExport || loadingImport" />
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import svc from "../../services/courseMaintenance"

const { t } = useI18n()
const route = useRoute()
const node = ref(Number(route.params.node || 0))

const file = ref(null)
const scope = ref("full") // 'full' | 'selected'

const loadingExport = ref(false)
const loadingImport = ref(false)
const loadingResources = ref(false)

const error = ref("")
const notice = ref("")
const serverMessage = ref("")

const tree = ref([])
const warnings = ref([])
const open = ref({})
const selected = ref({}) // { type: { id: true } }

function onFile(e) {
  file.value = e.target.files?.[0] || null
}

function toggle(type) {
  open.value[type] = !open.value[type]
}

function isChecked(type, id) {
  return !!selected.value?.[type]?.[String(id)]
}

function toggleItem(type, id, val) {
  if (!selected.value[type]) selected.value[type] = {}
  if (val) selected.value[type][String(id)] = true
  else delete selected.value[type][String(id)]
}

function checkAll(val) {
  if (val) {
    const out = {}
    for (const g of tree.value) {
      out[g.type] = {}
      for (const it of g.items || []) out[g.type][String(it.id)] = true
    }
    selected.value = out
  } else {
    selected.value = {}
  }
}

async function loadOptionsAndTree() {
  try {
    const opts = await svc.cc13ExportOptions(node.value)
    serverMessage.value = opts?.message || ""
  } catch (_) { /* ignore */ }

  try {
    loadingResources.value = true
    const res = await svc.cc13ExportResources(node.value)
    tree.value = res.tree || []
    warnings.value = res.warnings || []
    // open first few groups
    for (const g of tree.value.slice(0, 3)) open.value[g.type] = true
  } catch (e) {
    error.value = e?.response?.data?.error || t("Failed to load resources.")
  } finally {
    loadingResources.value = false
  }
}

async function doExport() {
  error.value = ""; notice.value = ""
  try {
    loadingExport.value = true
    const payload = { scope: scope.value }
    if (scope.value === "selected") payload.resources = selected.value
    const res = await svc.cc13ExportExecute(node.value, payload)
    // 200 => ok (might provide downloadUrl); 202 => under construction
    if (res.downloadUrl) window.location.href = res.downloadUrl
    notice.value = res.message || t("Export finished.")
  } catch (e) {
    error.value = e?.response?.data?.error || t("Failed to export.")
  } finally {
    loadingExport.value = false
  }
}

async function doImport() {
  error.value = ""; notice.value = ""
  try {
    if (!file.value) throw new Error(t("Please choose a .imscc or .zip file."))
    loadingImport.value = true
    const res = await svc.cc13Import(node.value, file.value)
    notice.value = res.message || t("Import completed.")
  } catch (e) {
    error.value = e?.response?.data?.error || e.message || t("Failed to import.")
  } finally {
    loadingImport.value = false
  }
}

onMounted(loadOptionsAndTree)
</script>

<script>
export default {
  components: {
    CMLoader: {
      template: `
        <div class="fixed inset-0 z-30 grid place-items-center bg-black/10">
          <div class="flex items-center gap-3 rounded-lg bg-white px-4 py-3 shadow">
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-sky-600 border-t-transparent"></span>
            <span class="text-sm text-gray-700">Working…</span>
          </div>
        </div>
      `,
    },
    CMAlert: {
      props: { type: { type: String, default: "info" }, text: String },
      computed: {
        tone() {
          return {
            info: "bg-sky-50 text-sky-800 border-sky-200",
            success: "bg-emerald-50 text-emerald-800 border-emerald-200",
            warning: "bg-amber-50 text-amber-800 border-amber-200",
            error: "bg-rose-50 text-rose-800 border-rose-200",
          }[this.type] || "bg-gray-50 text-gray-700 border-gray-200"
        },
      },
      template: `<div class="rounded-md border px-3 py-2 text-sm" :class="tone">{{ text }}</div>`,
    },
  },
}
</script>
