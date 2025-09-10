<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import lpService from "../../services/lpService"

const { t } = useI18n()

const props = defineProps({
  show: { type: Boolean, default: false },
  lpId: { type: Number, required: true },
  cid: { type: [Number, String], default: 0 },
  sid: { type: [Number, String], default: 0 },
})

const emit = defineEmits(["close"])

const loading = ref(false)
const error = ref("")
const items = ref([])
const selected = ref(new Set())

const selectedCount = computed(() => selected.value.size)
const allSelected = computed(() => items.value.length > 0 && selectedCount.value === items.value.length)
const noneAvailable = computed(() => !loading.value && !error.value && items.value.length === 0)

function toggleAll() {
  if (allSelected.value) {
    selected.value = new Set()
  } else {
    selected.value = new Set(items.value.map((i) => i.id))
  }
}

function toggleOne(id) {
  const s = new Set(selected.value)
  if (s.has(id)) s.delete(id)
  else s.add(id)
  selected.value = s
}

async function fetchExportables() {
  loading.value = true
  error.value = ""
  items.value = []
  selected.value = new Set()

  try {
    const qs = new URLSearchParams({ a: "get_lp_export_items", lp_id: String(props.lpId) })
    const cidNum = Number(props.cid || 0)
    const sidNum = Number(props.sid || 0)
    if (cidNum) qs.append("cid", String(cidNum))
    if (sidNum) qs.append("sid", String(sidNum))

    const res = await fetch(`/main/inc/ajax/lp.ajax.php?${qs.toString()}`, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
      credentials: "same-origin",
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const data = await res.json().catch(() => ({}))

    const arr = Array.isArray(data) ? data : Array.isArray(data.items) ? data.items : []
    const norm = arr
      .map((x) => ({ id: Number(x.id ?? x.iid ?? 0), title: String(x.title ?? "") }))
      .filter((x) => x.id > 0)

    items.value = norm
    selected.value = new Set(norm.map((i) => i.id))
  } catch (e) {
    console.error("[ExportPdfDialog] fetchExportables error:", e)
    error.value = t("Could not load exportable items.")
  } finally {
    loading.value = false
  }
}

function exportNow() {
  if (selected.value.size === 0) return
  const cidNum = Number(props.cid || 0)
  const sidNum = Number(props.sid || 0)

  window.location.href = lpService.buildLegacyActionUrl(props.lpId, "export_to_pdf", {
    cid: cidNum || undefined,
    sid: sidNum || undefined,
    params: { items: Array.from(selected.value).join(",") },
  })
  emit("close")
}

function onClose() {
  emit("close")
}

watch(
  () => props.show,
  (open) => {
    if (open) fetchExportables()
  },
)
onMounted(() => {
  if (props.show) fetchExportables()
})
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 z-[1000]"
  >
    <div
      class="absolute inset-0 bg-black/40"
      @click="onClose"
    />
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="w-full max-w-xl bg-white rounded-2xl shadow-xl border border-gray-25">
        <header class="px-5 py-4 border-b border-gray-15 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-90">{{ t("Export to PDF") }}</h3>
          <button
            class="w-8 h-8 grid place-content-center rounded-lg hover:bg-gray-15"
            @click="onClose"
            :aria-label="t('Close')"
          >
            <i class="mdi mdi-close text-xl" />
          </button>
        </header>

        <section class="p-5 max-h-[60vh] overflow-auto">
          <div
            v-if="loading"
            class="animate-pulse text-gray-50"
          >
            {{ t("Loading...") }}
          </div>
          <div
            v-else-if="error"
            class="text-danger"
          >
            {{ error }}
          </div>

          <div v-else>
            <div
              v-if="noneAvailable"
              class="text-gray-60"
            >
              {{ t("No exportable items found.") }}
            </div>

            <div
              v-else
              class="space-y-3"
            >
              <div class="flex items-center justify-between">
                <div class="text-body-2 text-gray-70">
                  {{ t("Select which items to include in the PDF") }}
                </div>
                <button
                  class="px-3 py-1.5 rounded-lg border border-gray-25 hover:bg-gray-15"
                  @click="toggleAll"
                >
                  {{ allSelected ? t("Unselect all") : t("Select all") }}
                </button>
              </div>

              <ul class="divide-y divide-gray-15 rounded-xl border border-gray-15">
                <li
                  v-for="it in items"
                  :key="it.id"
                  class="px-3 py-2 flex items-center gap-3"
                >
                  <input
                    type="checkbox"
                    class="w-4 h-4"
                    :checked="selected.has(it.id)"
                    @change="toggleOne(it.id)"
                    :aria-label="it.title || '#' + it.id"
                  />
                  <span class="truncate">{{ it.title || "#" + it.id }}</span>
                </li>
              </ul>
            </div>
          </div>
        </section>

        <footer class="px-5 py-4 border-t border-gray-15 flex items-center justify-end gap-2">
          <button
            class="px-4 py-2 rounded-xl border border-gray-25 hover:bg-gray-15"
            @click="onClose"
          >
            {{ t("Cancel") }}
          </button>
          <button
            class="px-4 py-2 rounded-xl bg-gray-90 text-white hover:opacity-90 disabled:opacity-40"
            :disabled="selectedCount === 0 || loading"
            @click="exportNow"
          >
            {{ t("Export") }} ({{ selectedCount }})
          </button>
        </footer>
      </div>
    </div>
  </div>
</template>
