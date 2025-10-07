<template>
  <section class="space-y-4">
    <!-- Toolbar -->
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div class="flex items-center gap-2 text-sm">
        <h3 class="text-sm font-semibold text-gray-90">{{ title }}</h3>
        <span class="px-2 py-1 rounded-md bg-gray-15 text-gray-50">
          {{ selectedTotal }} {{ $t('selected') }}
        </span>
      </div>

      <div class="flex flex-wrap gap-2">
        <div class="relative" v-if="searchable">
          <input
            v-model.trim="query"
            :placeholder="$t('Search by title or path…')"
            class="w-64 rounded border border-gray-25 p-2 pr-8 text-sm"
            type="text"
          />
          <button
            v-if="query"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-50 hover:text-gray-90"
            @click="query=''"
            :aria-label="$t('Clear search')">
            <i class="mdi mdi-close"></i>
          </button>
        </div>

        <button class="btn-secondary" @click="expandAll(true)">
          <i class="mdi mdi-arrow-expand-vertical"></i> {{ $t("Expand all") }}
        </button>
        <button class="btn-secondary" @click="expandAll(false)">
          <i class="mdi mdi-arrow-collapse-vertical"></i> {{ $t("Collapse all") }}
        </button>
        <button class="btn-secondary" @click="checkAll(true)">
          <i class="mdi mdi-check-all"></i> {{ $t("Select all") }}
        </button>
        <button class="btn-secondary" @click="checkAll(false)">
          <i class="mdi mdi-close-thick"></i> {{ $t("Select none") }}
        </button>
      </div>
    </div>

    <!-- Tree -->
    <div class="rounded-lg border border-gray-25">
      <div v-if="filteredGroups.length===0" class="p-6 text-center text-sm text-gray-50">
        {{ emptyText }}
      </div>

      <div v-else class="divide-y divide-gray-25">
        <GroupBlock
          v-for="g in filteredGroups"
          :key="g.type"
          :group="g"
          :isChecked="isChecked"
          :toggleFn="toggleNode"
          :forceOpen="forceOpen"
          :count-selected="countSelected"
          :isNodeCheckable="isNodeCheckable"
          @select-group="(val)=>toggleNode(g, val)"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { watch, toRefs, nextTick } from "vue"
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

// hook with shared logic
const sel = useResourceSelection()
const { tree, selections, query, forceOpen,
  normalizeTreeForSelection, filteredGroups, selectedTotal,
  countSelected, isNodeCheckable, isChecked, toggleNode, checkAll, expandAll } = sel

// sync in/out
const { groups, modelValue } = toRefs(props)
watch(groups, (arr) => {
  const norm = normalizeTreeForSelection(Array.isArray(arr) ? JSON.parse(JSON.stringify(arr)) : [])
  // ensure top-level children
  tree.value = norm.map(g =>
    Array.isArray(g.children) ? g : { ...g, children: Array.isArray(g.items) ? g.items : [] }
  )
}, { immediate: true })

// auto expand on first data
watch(tree, (v) => {
  if (Array.isArray(v) && v.length) {
    forceOpen.value = true
    requestAnimationFrame(() => { forceOpen.value = null })
  }
})

let syncing = false

watch(modelValue, (v) => {
  if (syncing) return
  syncing = true
  selections.value = { ...(v || {}) }
  queueMicrotask(() => { syncing = false })
}, { immediate: true })

watch(selections, (v) => {
  if (syncing) return
  syncing = true
  emit("update:modelValue", { ...(v || {}) })
  queueMicrotask(() => { syncing = false })
}, { deep: true })
</script>

<script>
/* inline child components to keep it self-contained */
export default {
  components: {
    GroupBlock: {
      name: "GroupBlock",
      props: { group: Object, isChecked: Function, toggleFn: Function, countSelected: Function, forceOpen: [Boolean, null], isNodeCheckable: Function },
      emits: ["select-group"],
      components: {   /* <-- REGISTER TreeNode LOCALLY HERE */
        TreeNode: {
          name: "TreeNode",
          props: { node: Object, checked: Boolean, isChecked: Function, toggleFn: Function, forceOpen: [Boolean, null], isNodeCheckable: Function },
          emits: ["toggle"],
          data(){ return { open: true } },
          watch: { forceOpen: { immediate: true, handler(v){ if (v!==null) this.open = !!v } } },
          methods: {
            toggleOpen(){ this.open = !this.open },
            onCheck(e){ this.$emit("toggle", e.target.checked) },
            badgeTone(){ return "bg-gray-10 text-gray-90 ring-gray-25" },
          },
          template: `
            <li class="p-3 rounded-lg hover:bg-gray-15 transition">
              <div class="flex items-start gap-3">
                <button v-if="node.children && node.children.length"
                        class="mt-0.5 text-gray-50 hover:text-gray-90" @click="toggleOpen" aria-label="toggle">
                  <i :class="open ? 'mdi mdi-chevron-down' : 'mdi mdi-chevron-right'"></i>
                </button>

                <template v-if="isNodeCheckable(node)">
                  <input type="checkbox" :checked="checked" @change="onCheck" class="mt-0.5 chk-success"/>
                </template>
                <template v-else>
                  <span class="mt-0.5 w-4"></span>
                </template>

                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <span class="rounded px-2 py-0.5 text-xs font-semibold ring-1 ring-inset" :class="badgeTone()">
                      {{ (node.titleType || node.type || '').toUpperCase() }}
                    </span>
                    <span class="text-sm text-gray-90">{{ node.label || node.title || '—' }}</span>
                    <span v-if="node.meta" class="text-xs text-gray-50">· {{ node.meta }}</span>
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
      data(){ return { open: true } },
      computed:{
        nodes(){
          return Array.isArray(this.group.children) ? this.group.children
            : Array.isArray(this.group.items) ? this.group.items : []
        },
        total(){ return this.nodes.length },
      },
      watch: { forceOpen: { immediate: true, handler(v){ if (v!==null) this.open = !!v } } },
      methods:{
        toggleOpen(){ this.open = !this.open },
        selectAll(){ this.$emit("select-group", true) },
        selectNone(){ this.$emit("select-group", false) },
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
