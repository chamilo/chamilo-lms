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
            <div
              role="group"
              aria-label="Export scope"
              class="inline-flex rounded-md border border-gray-300 divide-x divide-gray-300"
            >
              <button
                type="button"
                :aria-pressed="scope === 'full'"
                class="px-3 py-1.5 text-sm focus:outline-none focus-visible:ring"
                :class="segClass('full')"
                @click="scope = 'full'"
              >
                {{ t("Full course") }}
              </button>
              <button
                type="button"
                :aria-pressed="scope === 'selected'"
                class="px-3 py-1.5 text-sm focus:outline-none focus-visible:ring -ml-px"
                :class="segClass('selected')"
                @click="scope = 'selected'"
              >
                {{ t("Selected items") }}
              </button>
            </div>
            <p
              v-if="scope === 'selected'"
              class="mt-1 text-xs text-gray-500"
            >
              {{ t("{n} selected", { n: selectedCount }) }}
            </p>
          </div>

          <!-- Tree -->
          <div
            v-if="scope === 'selected'"
            class="rounded-md border border-gray-200"
          >
            <div class="flex items-center justify-between border-b px-3 py-2">
              <div class="text-xs text-gray-600">{{ t("Select resources") }}</div>
              <div class="flex gap-2 text-xs">
                <button
                  class="link"
                  @click="checkAll(true)"
                >
                  {{ t("Select all") }}
                </button>
                <span class="text-gray-300">•</span>
                <button
                  class="link"
                  @click="checkAll(false)"
                >
                  {{ t("Clear") }}
                </button>
              </div>
            </div>

            <div
              v-if="loadingResources"
              class="p-3 text-xs text-gray-500"
            >
              {{ t("Loading resources…") }}
            </div>

            <div
              v-else
              class="max-h-72 space-y-3 overflow-auto p-3"
            >
              <div
                v-if="warnings.length"
                class="text-xs text-amber-700"
              >
                <ul class="list-disc pl-4">
                  <li
                    v-for="w in warnings"
                    :key="w"
                  >
                    {{ w }}
                  </li>
                </ul>
              </div>

              <div
                v-for="group in tree"
                :key="group.type"
                class="rounded border border-gray-100"
              >
                <button
                  class="flex w-full items-center justify-between px-3 py-2 text-left text-sm font-medium hover:bg-gray-50"
                  @click="toggle(group.type)"
                >
                  <span class="flex items-center gap-2"> <i class="mdi mdi-folder-outline"></i>{{ group.title }} </span>
                  <i :class="open[group.type] ? 'mdi mdi-chevron-up' : 'mdi mdi-chevron-down'"></i>
                </button>

                <div
                  v-show="open[group.type]"
                  class="border-t p-2"
                >
                  <!-- Documents -->
                  <template v-if="group.type === 'document'">
                    <div
                      v-for="item in group.items"
                      :key="`${group.type}:${item.id}`"
                      class="flex items-center gap-2 px-2 py-1 text-sm"
                    >
                      <input
                        v-if="item.selectable"
                        type="checkbox"
                        :id="`res-${group.type}-${item.id}`"
                        :checked="isChecked('document', item.id)"
                        :disabled="isFolder(item)"
                        @change="toggleItem('document', item.id, $event.target.checked, item)"
                      />
                      <label
                        v-if="item.selectable"
                        class="cursor-pointer"
                        :for="`res-${group.type}-${item.id}`"
                        :class="{ 'opacity-50': isFolder(item) }"
                      >
                        {{ item.label }}
                        <span
                          v-if="isFolder(item)"
                          class="ml-2 text-[11px] text-gray-400"
                        >
                          (folder — not exportable)
                        </span>
                      </label>

                      <template v-else>
                        <i class="mdi mdi-folder-outline"></i>
                        <span>{{ item.label }}</span>
                      </template>
                    </div>
                  </template>

                  <!-- Links & Forums (now exportable) -->
                  <template v-else>
                    <div
                      v-for="cat in group.items"
                      :key="`${group.type}:${cat.id}`"
                      class="px-2 py-1"
                    >
                      <div class="flex items-center gap-2 text-sm font-medium text-gray-700">
                        <i class="mdi mdi-folder-outline"></i>
                        <span>{{ cat.label }}</span>
                        <span
                          v-if="kids(cat).length"
                          class="ml-2 text-xs text-gray-500"
                        >
                          ({{ t("contains {n} items", { n: kids(cat).length }) }})
                        </span>
                      </div>

                      <!-- children -->
                      <div
                        v-for="child in kids(cat)"
                        :key="`${group.type}:${cat.id}:${child.id}`"
                        class="ml-6 flex items-center gap-2 px-2 py-1 text-sm"
                      >
                        <template v-if="child.selectable">
                          <input
                            type="checkbox"
                            :id="`res-${group.type}-${cat.id}-${child.id}`"
                            :checked="isChecked(group.type, child.id)"
                            @change="toggleItem(group.type, child.id, $event.target.checked, child)"
                          />
                          <label
                            class="cursor-pointer"
                            :for="`res-${group.type}-${cat.id}-${child.id}`"
                          >
                            {{ child.label }}
                          </label>
                        </template>
                        <template v-else>
                          <i class="mdi mdi-subdirectory-arrow-right"></i>
                          <span>{{ child.label }}</span>
                        </template>
                      </div>

                      <!-- grandchildren (if any) -->
                      <div
                        v-for="grand in kids(kids(cat)[0])"
                        v-if="Array.isArray(kids(cat)) && kids(cat).some(n => Array.isArray(kids(n)) && kids(n).length)"
                        :key="`${group.type}:${cat.id}:${grand.id}`"
                        class="ml-10 flex items-center gap-2 px-2 py-0.5 text-xs text-gray-600"
                      >
                        <template v-if="grand.selectable">
                          <input
                            type="checkbox"
                            :id="`res-${group.type}-${cat.id}-${grand.id}`"
                            :checked="isChecked(group.type, grand.id)"
                            @change="toggleItem(group.type, grand.id, $event.target.checked, grand)"
                          />
                          <label
                            class="cursor-pointer"
                            :for="`res-${group.type}-${cat.id}-${grand.id}`"
                          >
                            {{ grand.label }}
                          </label>
                        </template>
                        <template v-else>
                          <i class="mdi mdi-subdirectory-arrow-right"></i>
                          <span>{{ grand.label }}</span>
                        </template>
                      </div>
                    </div>
                  </template>
                </div>
              </div>
            </div>
          </div>

          <!-- Export button -->
          <div class="mt-3 flex justify-end">
            <button
              class="btn-primary"
              :disabled="loadingExport || (scope === 'selected' && selectedCount === 0)"
              @click="doExport"
            >
              <i class="mdi mdi-package-variant-closed"></i> {{ t("Export") }}
            </button>
          </div>

          <p
            v-if="serverMessage"
            class="mt-2 text-xs text-gray-500"
          >
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
            <button
              class="btn-primary"
              :disabled="!file || loadingImport"
              @click="doImport"
            >
              <i class="mdi mdi-package-down"></i> {{ t("Import") }}
            </button>
          </div>
        </div>
      </div>
    </section>

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
    <CMLoader v-if="loadingExport || loadingImport" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
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
const selected = ref({})

/** CC 1.3 UI gate: documents (webcontent), links (imswl), discussions (imsdt) */
const CC13_ALLOWED_TYPES = ["document", "link", "forum"]

/** Payload key mapping expected by backend CourseBuilder */
const PAYLOAD_KEYS = {
  document: "documents",
  link: "links",
  forum: "forums",
}

/** File picker */
function onFile(e) {
  file.value = e.target.files?.[0] || null
}

/** Expand/collapse a group */
function toggle(type) {
  open.value[type] = !open.value[type]
}

/** Selection state helper per group type */
function isChecked(type, id) {
  return !!selected.value?.[type]?.[String(id)]
}

/** The backend uses "items" (not "children"). */
function kids(node) {
  return Array.isArray(node?.items) ? node.items : []
}

/** Detect if a document node is a folder (non-exportable) */
function isFolder(node) {
  const ft = String(node?.extra?.filetype || node?.extra?.file_type || "").toLowerCase()
  const p = String(node?.extra?.path || "")
  return ft === "folder" || p.endsWith("/")
}

/** Toggle for any allowed group. For documents, ignore folders. */
function toggleItem(type, id, val, nodeObj) {
  if (!CC13_ALLOWED_TYPES.includes(type)) return
  if (type === "document" && nodeObj && isFolder(nodeObj)) return
  if (!selected.value[type]) selected.value[type] = {}
  if (val) selected.value[type][String(id)] = true
  else delete selected.value[type][String(id)]
}

/** DFS gather only exportable leaves */
function collectSelectable(nodes, out, type) {
  for (const n of nodes || []) {
    // In documents, only file leaves; in others rely on "selectable"
    if (n?.selectable && (type !== "document" || !isFolder(n))) {
      out[String(n.id)] = true
    }
    const k = kids(n)
    if (k.length) collectSelectable(k, out, type)
  }
}

/** Select all across allowed groups; Clear resets all */
function checkAll(val) {
  if (val) {
    const out = {}
    for (const g of tree.value) {
      if (!CC13_ALLOWED_TYPES.includes(g.type)) continue
      out[g.type] = {}
      collectSelectable(g.items || [], out[g.type], g.type)
      // If group had no selectable items, drop the empty object
      if (Object.keys(out[g.type]).length === 0) delete out[g.type]
    }
    selected.value = out
  } else {
    selected.value = {}
  }
}

const selectedCount = computed(() => {
  let n = 0
  for (const t of CC13_ALLOWED_TYPES) {
    if (selected.value[t]) n += Object.keys(selected.value[t]).length
  }
  return n
})

/** Segmented control styling */
function segClass(val) {
  const active = scope.value === val
  return [
    active ? "bg-sky-600 text-white border-sky-600" : "bg-white text-gray-700 hover:bg-gray-50",
    "border border-transparent",
  ]
}

/** Load export options + tree. */
async function loadOptionsAndTree() {
  try {
    const opts = await svc.cc13ExportOptions(node.value)
    serverMessage.value = opts?.message || ""
  } catch (_) {
    /* ignore */
  }

  try {
    loadingResources.value = true
    const res = await svc.cc13ExportResources(node.value)
    tree.value = res.tree || []
    warnings.value = res.warnings || []
    for (const g of tree.value.slice(0, 3)) open.value[g.type] = true
  } catch (e) {
    error.value = e?.response?.data?.error || t("Failed to load resources.")
  } finally {
    loadingResources.value = false
  }
}

/** Build CC 1.3-safe payload:
 * {
 *   resources: {
 *     documents: { "12": true, ... },
 *     weblinks: { "8": true, ... },
 *     discussions: { "34": true, ... }
 *   }
 * }
 */
function buildCc13SelectionPayload() {
  const resources = {}
  for (const t of CC13_ALLOWED_TYPES) {
    const ids = Object.keys(selected.value?.[t] || {})
    if (!ids.length) continue
    const key = PAYLOAD_KEYS[t] || t // default fallback
    resources[key] = {}
    for (const id of ids) resources[key][String(id)] = true
  }
  return resources
}

/** Execute export */
async function doExport() {
  error.value = ""
  notice.value = ""
  try {
    loadingExport.value = true
    const payload = { scope: scope.value }

    if (scope.value === "selected") {
      const resSel = buildCc13SelectionPayload()
      const total =
        Object.values(resSel).reduce((acc, obj) => acc + Object.keys(obj || {}).length, 0)
      if (total === 0) {
        throw new Error(t("Please select at least one exportable resource (documents, web links, or discussions)."))
      }
      payload.resources = resSel
    }

    const res = await svc.cc13ExportExecute(node.value, payload)
    if (res.downloadUrl) window.location.href = res.downloadUrl
    notice.value = res.message || t("Export finished.")
  } catch (e) {
    error.value = e?.response?.data?.error || e.message || t("Failed to export.")
  } finally {
    loadingExport.value = false
  }
}

/** Execute import */
async function doImport() {
  error.value = ""
  notice.value = ""
  try {
    if (!file.value) throw new Error(t("Please choose a .imscc or .zip file."))
    loadingImport.value = true
    const res = await svc.cc13Import(node.value, file.value)
    notice.value = res.message || t("Import finished")
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
          return (
            {
              info: "bg-sky-50 text-sky-800 border-sky-200",
              success: "bg-emerald-50 text-emerald-800 border-emerald-200",
              warning: "bg-amber-50 text-amber-800 border-amber-200",
              error: "bg-rose-50 text-rose-800 border-rose-200",
            }[this.type] || "bg-gray-50 text-gray-700 border-gray-200"
          )
        },
      },
      template: `<div class="rounded-md border px-3 py-2 text-sm" :class="tone">{{ text }}</div>`,
    },
  },
}
</script>
