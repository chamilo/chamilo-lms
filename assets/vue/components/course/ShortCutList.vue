<template>
  <div class="course-tool">
    <BaseAppLink
      :url="resolvedUrl"
      :target="props.shortcut.target || '_self'"
      rel="noopener"
      class="course-tool__link"
      :aria-label="shortcut.title"
    >
      <!-- Custom image has priority -->
      <img
        v-if="shortcut.customImageUrl"
        :alt="shortcut.title"
        :src="shortcut.customImageUrl"
        class="course-tool__icon"
      />

      <!-- Icon from payload (or type-based fallback) -->
      <i
        v-else
        class="mdi course-tool__icon"
        :class="iconClasses"
        :title="shortcut.title"
        aria-hidden="true"
      ></i>
    </BaseAppLink>

    <BaseAppLink
      :url="resolvedUrl"
      :target="props.shortcut.target || '_self'"
      rel="noopener"
      class="course-tool__title"
    >
      {{ shortcut.title }}
    </BaseAppLink>
  </div>
</template>

<script setup>
import { computed } from "vue"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../../store/cidReq"

const cidReqStore = useCidReqStore()
const { course, session } = storeToRefs(cidReqStore)

const props = defineProps({
  shortcut: {
    type: Object,
    required: true,
  },
})

/**
 * URL resolver:
 * - Prefer urlOverride when present.
 * - If URL already has cid/sid/gid, keep them (do NOT append again).
 * - Otherwise, append current course/session context.
 */
const resolvedUrl = computed(() => {
  const base = props.shortcut.urlOverride || props.shortcut.url || "#"
  // If it's absolute or relative, normalize with URL helper and keep it relative.
  try {
    const u = new URL(base, window.location.origin)

    // If URL already has cid (and possibly sid/gid), leave it as-is.
    const hasCid = u.searchParams.has("cid")
    const hasSid = u.searchParams.has("sid")

    if (!hasCid && course.value?.id) {
      u.searchParams.set("cid", course.value.id)
    }
    if (!hasSid) {
      u.searchParams.set("sid", session.value?.id || 0)
    }

    // Keep it relative (like backend responses)
    return u.pathname + (u.search ? `?${u.searchParams.toString()}` : "")
  } catch {
    // Fallback
    return base
  }
})

/**
 * Icon logic:
 * - If backend provides `shortcut.icon`, use it.
 * - Else, if `type === 'blog'`, default to 'mdi-notebook-outline'.
 * - Else, fallback to the legacy file/link icon.
 *
 * NOTE: no "disabled" styling for blogs; use a normal/active look.
 */
const iconName = computed(() => {
  if (props.shortcut?.icon) return props.shortcut.icon
  if ((props.shortcut?.type || "").toLowerCase() === "blog") return "mdi-notebook-outline"
  return "mdi-file-link"
})

const isBlog = computed(() => (props.shortcut?.type || "").toLowerCase() === "blog")

// Tailwind/utility classes for the icon element
const iconClasses = computed(() => {
  // Always include the MDI glyph name
  const mdiGlyph = iconName.value

  // Visual style:
  // - Blogs: stronger (no disabled look)
  // - Others: neutral
  const tone = isBlog.value ? "text-primary" : "text-gray-600"
  return [mdiGlyph, tone]
})
</script>
