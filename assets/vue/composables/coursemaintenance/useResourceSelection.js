import { ref, computed } from "vue"

/** Normalize, filter and manage a resource-selection tree */
export default function useResourceSelection() {
  // state
  const tree = ref([]) // groups from backend
  const selections = ref({}) // { [type]: { [id]: 1 } }
  const query = ref("")
  const forceOpen = ref(null) // true/false or null (release)

  // --- helpers ---------------------------------------------------------------
  const singular = (t) =>
    ({
      documents: "document",
      links: "link",
      announcements: "announcement",
      events: "event",
      forums: "forum",
      forum_category: "forum_category",
      thread: "thread",
      post: "post",
      course_descriptions: "course_description",
      quizzes: "quiz",
      survey: "survey",
      surveys: "survey",
      learnpaths: "learnpath",
      scorm_documents: "document",
      tool_intro: "tool_intro",
    })[t] || (t ? t.replace(/s$/, "") : "document")

  const getKids = (n) => (Array.isArray(n?.children) ? n.children : Array.isArray(n?.items) ? n.items : [])

  const resolveId = (node) => {
    const raw = node?.id ?? node?.iid ?? node?.uuid ?? node?.source_id ?? null
    if (raw == null) return null
    const n = Number(raw)
    return Number.isFinite(n) ? n : raw
  }

  /** Ensure each leaf has {id,type,selectable}, and move items -> children */
  function normalizeTreeForSelection(groups) {
    const walk = (node, itemTypeFromParent) => {
      if (!node || typeof node !== "object") return
      const kids = getKids(node)
      const ownType = node.type || node.titleType || null
      const nextItemType = kids.length ? singular(ownType || node.itemType || "documents") : itemTypeFromParent

      if (!kids.length) {
        const id = resolveId(node)
        if (id != null) node.id = id
        if (!node.type) node.type = nextItemType || "document"
        if (typeof node.selectable === "undefined") node.selectable = true
      } else {
        for (const child of kids) walk(child, singular(ownType || nextItemType || "documents"))
        // Unify child container
        if (!Array.isArray(node.children) && Array.isArray(node.items)) node.children = node.items
      }
    }
    ;(groups || []).forEach((g) => walk(g, null))
    return groups
  }

  const isNodeCheckable = (n) => !!(n && n.id != null && n.selectable !== false)
  const isChecked = (n) => !!(n && isNodeCheckable(n) && selections.value?.[n.type]?.[n.id])

  // --- SELECTION: bulk apply + bump -----------------------------------------
  function markOne(n, checked) {
    if (!selections.value[n.type]) selections.value[n.type] = {}
    if (checked) selections.value[n.type][n.id] = 1
    else delete selections.value[n.type][n.id]
  }

  function applyDeep(n, checked) {
    if (!n) return
    if (isNodeCheckable(n)) markOne(n, checked)
    const kids = getKids(n)
    for (const c of kids) applyDeep(c, checked)
  }

  /** Re-assign to force render (immutable update) */
  function bump() {
    selections.value = { ...selections.value }
  }

  /** Toggle a node (and its descendants) with a single bump */
  function toggleNode(n, checked) {
    applyDeep(n, checked)
    bump()
  }

  /** Select/Deselect everything: iterate groups and do a single bump */
  function checkAll(all) {
    for (const g of tree.value) applyDeep(g, all)
    bump()
  }

  /** Expand/Collapse all */
  function expandAll(v) {
    forceOpen.value = v
  }

  // --- search/filter ---------------------------------------------------------
  function matchNode(node, q) {
    if (!q) return true
    const t = (node.label || node.title || "").toLowerCase()
    const p = (node.meta || "").toLowerCase()
    const s = q.toLowerCase()
    return t.includes(s) || p.includes(s)
  }
  function deepFilterNode(node, q) {
    if (matchNode(node, q)) return node
    const kids = getKids(node)
      .map((c) => deepFilterNode(c, q))
      .filter(Boolean)
    if (kids.length > 0) return { ...node, children: kids }
    return null
  }
  function deepFilterGroup(group, q) {
    if (!q) return group
    const kids = getKids(group)
      .map((c) => deepFilterNode(c, q))
      .filter(Boolean)
    if (kids.length > 0) return { ...group, children: kids }
    return null
  }
  const filteredGroups = computed(() => {
    const q = query.value.trim()
    if (!q) return tree.value
    return tree.value.map((g) => deepFilterGroup(g, q)).filter(Boolean)
  })

  const selectedTotal = computed(() =>
    Object.values(selections.value).reduce((a, g) => a + Object.keys(g || {}).length, 0),
  )
  function countSelected(group) {
    const map = selections.value || {}
    const pool = getKids(group)
    let n = 0
    for (const c of pool) n += countSelectedRecursive(c, map)
    return n
  }
  function countSelectedRecursive(n, map) {
    let x = isNodeCheckable(n) && map?.[n.type]?.[n.id] ? 1 : 0
    for (const c of getKids(n)) x += countSelectedRecursive(c, map)
    return x
  }

  return {
    // state
    tree,
    selections,
    query,
    forceOpen,
    // normalize
    normalizeTreeForSelection,
    // filters + ui
    filteredGroups,
    selectedTotal,
    countSelected,
    // actions
    isNodeCheckable,
    isChecked,
    toggleNode,
    checkAll,
    expandAll,
  }
}
