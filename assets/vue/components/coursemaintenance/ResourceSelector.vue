<template>
  <section class="space-y-4">
    <!-- Toolbar -->
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div class="flex items-center gap-2 text-sm">
        <h3 class="text-sm font-semibold text-gray-90">{{ title }}</h3>
        <span class="px-2 py-1 rounded-md bg-gray-15 text-gray-50"> {{ t("{0} selected", [selectedTotal]) }} </span>
      </div>

      <div class="flex flex-wrap gap-2">
        <div
          class="relative"
          v-if="searchable"
        >
          <input
            v-model.trim="query"
            :placeholder="$t('Search by title or path...')"
            class="w-64 rounded border border-gray-25 p-2 pr-8 text-sm"
            type="text"
          />
          <button
            v-if="query"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-50 hover:text-gray-90"
            @click="query = ''"
            :aria-label="$t('Clear')"
          >
            <i class="mdi mdi-close"></i>
          </button>
        </div>

        <button
          class="btn-secondary"
          @click="expandAll(true)"
        >
          <i class="mdi mdi-arrow-expand-vertical"></i> {{ $t("Expand") }}
        </button>
        <button
          class="btn-secondary"
          @click="expandAll(false)"
        >
          <i class="mdi mdi-arrow-collapse-vertical"></i> {{ $t("Collapse") }}
        </button>
        <button
          class="btn-secondary"
          @click="checkAll(true)"
        >
          <i class="mdi mdi-check-all"></i> {{ $t("Select all") }}
        </button>
        <button
          class="btn-secondary"
          @click="checkAll(false)"
        >
          <i class="mdi mdi-close-thick"></i> {{ $t("Clear") }}
        </button>
      </div>
    </div>

    <!-- Tree -->
    <div class="rounded-lg border border-gray-25">
      <div
        v-if="filteredGroups.length === 0"
        class="p-6 text-center text-sm text-gray-50"
      >
        {{ emptyText }}
      </div>

      <div
        v-else
        class="divide-y divide-gray-25"
      >
        <GroupBlock
          v-for="g in filteredGroups"
          :key="g.type"
          :group="g"
          :isChecked="isChecked"
          :toggleFn="toggleNode"
          :forceOpen="forceOpen"
          :count-selected="countSelected"
          :isNodeCheckable="isNodeCheckable"
          @select-group="(val) => toggleNode(g, val)"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { watch, toRefs } from "vue"
import useResourceSelection from "../../composables/coursemaintenance/useResourceSelection"

const props = defineProps({
  /** Array of groups from backend (will be normalized) */
  groups: { type: Array, default: () => [] },
  /** v-model: { [type]: { [id]: 1 } } */
  modelValue: { type: Object, default: () => ({}) },
  /** Title above toolbar */
  title: { type: String, default: "Select resources" },
  /** Text when there is no data */
  emptyText: { type: String, default: "No resources available." },
  /** Show search input */
  searchable: { type: Boolean, default: true },
})

const emit = defineEmits(["update:modelValue"])

// Shared selection logic (composable)
const sel = useResourceSelection()
const {
  tree,
  selections,
  query,
  forceOpen,
  normalizeTreeForSelection,
  filteredGroups,
  selectedTotal,
  countSelected,
  isNodeCheckable,
  isChecked,
  toggleNode,
  checkAll,
  expandAll,
} = sel

/**
 * Transform the "Documents" group into a real folder/file tree using relative paths.
 * - Real folders (filetype === 'folder' or path ends with '/') remain as selectable only if they were.
 * - Synthetic folders (created by this function) are NOT selectable and use string ids (__dir__<rel>).
 * - Adds node.meta with the relative path to improve search filtering.
 * - Keeps everything else unchanged for other groups.
 */
function treeifyDocuments(groups) {
  const out = Array.isArray(groups) ? groups : []
  for (const g of out) {
    if (!g || g.type !== "document") continue

    const flat = Array.isArray(g.children) ? g.children : Array.isArray(g.items) ? g.items : []
    if (!flat.length) continue

    // Compute relative path (strip "document/" prefix if present)
    const relOf = (n) => {
      const raw = String(n?.extra?.path || n?.label || "").trim()
      if (!raw) return null
      let rel = raw.replace(/^\/?document\/?/, "").replace(/\\/g, "/")
      const isFolder = String(n?.extra?.filetype || "").toLowerCase() === "folder" || /\/$/.test(rel)
      if (isFolder) rel = rel.replace(/\/+$/, "") + "/"
      return rel.replace(/^\/+/, "")
    }

    // Index existing (real) folders
    const folderMap = new Map() // rel => node
    for (const it of flat) {
      const rel = relOf(it)
      if (!rel) continue
      const isFolder = String(it?.extra?.filetype || "").toLowerCase() === "folder" || rel.endsWith("/")
      if (isFolder) {
        it.label = (rel.replace(/\/$/, "").split("/").pop() || "/") + "/"
        it.meta = rel
        it.children = Array.isArray(it.children) ? it.children : []
        folderMap.set(rel, it)
      }
    }

    // Create synthetic folder if missing for a given relative path
    const ensureFolder = (rel) => {
      if (folderMap.has(rel)) return folderMap.get(rel)
      const name = rel.replace(/\/$/, "").split("/").pop() || "/"
      const synthetic = {
        id: `__dir__${rel}`,
        type: "document",
        label: name + "/",
        meta: rel,
        selectable: false, // synthetic folders shouldn't be checkable
        children: [],
        extra: { filetype: "folder" },
      }
      folderMap.set(rel, synthetic)
      return synthetic
    }

    const parentRelOf = (rel, isFolder) => {
      const clean = isFolder ? rel.replace(/\/+$/, "") : rel
      const dir = clean.includes("/") ? clean.slice(0, clean.lastIndexOf("/")) : ""
      return dir ? dir + "/" : ""
    }

    const root = { children: [] }

    // Place each item under its parent folder chain
    for (const it of flat) {
      const rel = relOf(it)
      if (!rel) continue
      const isFolder = String(it?.extra?.filetype || "").toLowerCase() === "folder" || rel.endsWith("/")
      const parentRel = parentRelOf(rel, isFolder)
      const parent = parentRel ? ensureFolder(parentRel) : root

      if (isFolder) {
        const f = ensureFolder(rel)
        if (!parent.children.includes(f)) parent.children.push(f)
      } else {
        const n = { ...it, meta: rel, label: rel.split("/").pop() }
        parent.children.push(n)
      }
    }

    // Sort: folders first, then files (case-insensitive)
    const sortChildren = (list) => {
      list.sort((a, b) => {
        const af = (a.extra?.filetype || "").toLowerCase() === "folder" || /\/$/.test(a.label || "")
        const bf = (b.extra?.filetype || "").toLowerCase() === "folder" || /\/$/.test(b.label || "")
        if (af !== bf) return af ? -1 : 1
        return String(a.label || "").localeCompare(String(b.label || ""), undefined, { sensitivity: "base" })
      })
      for (const n of list) if (n.children?.length) sortChildren(n.children)
    }
    sortChildren(root.children)

    g.children = root.children
    delete g.items
  }
  return out
}

// sync input => internal tree
const { groups, modelValue } = toRefs(props)
watch(
  groups,
  (arr) => {
    // Normalize the incoming tree first (ensures leaves have id/type/selectable)
    let norm = normalizeTreeForSelection(Array.isArray(arr) ? JSON.parse(JSON.stringify(arr)) : [])
    // Build the folder/file hierarchy only for Documents
    norm = treeifyDocuments(norm)
    // Ensure top-level children exist
    tree.value = norm.map((g) =>
      Array.isArray(g.children) ? g : { ...g, children: Array.isArray(g.items) ? g.items : [] },
    )
  },
  { immediate: true },
)

// auto expand on first data load
watch(tree, (v) => {
  if (Array.isArray(v) && v.length) {
    forceOpen.value = true
    requestAnimationFrame(() => {
      forceOpen.value = null
    })
  }
})

let syncing = false

// v-model (in) -> local
watch(
  modelValue,
  (v) => {
    if (syncing) return
    syncing = true
    selections.value = { ...(v || {}) }
    queueMicrotask(() => {
      syncing = false
    })
  },
  { immediate: true },
)

// local -> v-model (out)
watch(
  selections,
  (v) => {
    if (syncing) return
    syncing = true
    emit("update:modelValue", { ...(v || {}) })
    queueMicrotask(() => {
      syncing = false
    })
  },
  { deep: true },
)
</script>

<script>
/* Inline child components to keep it self-contained */
export default {
  components: {
    GroupBlock: {
      name: "GroupBlock",
      props: {
        group: Object,
        isChecked: Function,
        toggleFn: Function,
        countSelected: Function,
        forceOpen: [Boolean, null],
        isNodeCheckable: Function,
      },
      emits: ["select-group"],
      components: {
        /* <-- Tree node item (no type badge; uses folder/file icon) */
        TreeNode: {
          name: "TreeNode",
          props: {
            node: Object,
            checked: Boolean,
            isChecked: Function,
            toggleFn: Function,
            forceOpen: [Boolean, null],
            isNodeCheckable: Function,
          },
          emits: ["toggle"],
          data() {
            return { open: true }
          },
          watch: {
            forceOpen: {
              immediate: true,
              handler(v) {
                if (v !== null) this.open = !!v
              },
            },
          },
          methods: {
            toggleOpen() {
              this.open = !this.open
            },
            onCheck(e) {
              this.$emit("toggle", e.target.checked)
            },
          },
          template: `
            <li class="p-3 rounded-lg hover:bg-gray-15 transition">
              <div class="flex items-start gap-3">

                <!-- Disclosure -->
                <div class="mt-0.5 w-5 flex items-center justify-center">
                  <button
                    class="text-gray-50 hover:text-gray-90"
                    :class="{'opacity-0 pointer-events-none': !(node.children && node.children.length)}"
                    @click="toggleOpen"
                    aria-label="toggle">
                    <i :class="open ? 'mdi mdi-chevron-down' : 'mdi mdi-chevron-right'"></i>
                  </button>
                </div>

                <!-- Checkbox only for checkable nodes -->
                <template v-if="isNodeCheckable(node)">
                  <input type="checkbox" :checked="checked" @change="onCheck" class="mt-0.5 chk-success"/>
                </template>
                <template v-else>
                  <span class="mt-0.5 w-5"></span>
                </template>

                <!-- Label with folder/file icon (no type badge) -->
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <i
                      :class="((node.extra && node.extra.filetype === 'folder') || /\\/$/.test(node.label || ''))
                        ? 'mdi mdi-folder'
                        : 'mdi mdi-file-outline'"></i>
                    <span class="text-sm text-gray-90 break-all">{{ node.label || node.title || '—' }}</span>
                    <span v-if="node.meta" class="text-xs text-gray-50 break-all">· {{ node.meta }}</span>
                  </div>

                  <ul v-if="open && node.children && node.children.length" class="mt-2 ml-7 space-y-2">
                    <TreeNode
                      v-for="c in node.children"
                      :key="c.uuid || (c.type+':'+(c.id ?? c.title))"
                      :node="c"
                      :checked="isChecked(c)"
                      :isChecked="isChecked"
                      :toggleFn="toggleFn"
                      :forceOpen="forceOpen"
                      :isNodeCheckable="isNodeCheckable"
                      @toggle="toggleFn(c, $event)"
                    />
                  </ul>
                </div>
              </div>
            </li>
          `,
        },
      },
      data() {
        return { open: true }
      },
      computed: {
        nodes() {
          return Array.isArray(this.group.children)
            ? this.group.children
            : Array.isArray(this.group.items)
              ? this.group.items
              : []
        },
        total() {
          return this.nodes.length
        },
      },
      watch: {
        forceOpen: {
          immediate: true,
          handler(v) {
            if (v !== null) this.open = !!v
          },
        },
      },
      methods: {
        toggleOpen() {
          this.open = !this.open
        },
        selectAll() {
          this.$emit("select-group", true)
        },
        selectNone() {
          this.$emit("select-group", false)
        },
      },
      template: `
        <section class="bg-white">
          <header class="flex items-center justify-between px-4 py-2 bg-gray-15">
            <div class="flex items-center gap-2">
              <button class="text-gray-50 hover:text-gray-90" @click="toggleOpen" :aria-label="'toggle '+(group.title||group.type)">
                <i :class="open ? 'mdi mdi-chevron-down' : 'mdi mdi-chevron-right'"></i>
              </button>
              <span class="font-medium text-gray-90">{{ group.title || group.type }}</span>
              <span class="text-xs text-gray-50">· {{ countSelected(group) }} / {{ total }}</span>
            </div>
            <div class="flex gap-2">
              <button class="btn-secondary" @click="selectAll"><i class="mdi mdi-check-all"></i> Select group</button>
              <button class="btn-secondary" @click="selectNone"><i class="mdi mdi-close-thick"></i> Clear group</button>
            </div>
          </header>

          <ul v-if="open && nodes && nodes.length" class="p-2">
            <TreeNode
              v-for="c in nodes"
              :key="c.uuid || ((c.type||group.type)+':'+(c.id ?? (c.title||c.label||'untitled')))"
              :node="c"
              :checked="isChecked(c)"
              :isChecked="isChecked"
              :toggleFn="toggleFn"
              :forceOpen="forceOpen"
              :isNodeCheckable="isNodeCheckable"
              @toggle="toggleFn(c, $event)"
            />
          </ul>
        </section>
      `,
    },
  },
}
</script>
