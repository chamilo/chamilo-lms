<template>
  <div class="course-tool">
    <BaseAppLink
      :aria-label="shortcut.title"
      :target="props.shortcut.target || '_self'"
      :url="resolvedUrl"
      class="course-tool__link"
      rel="noopener"
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
        :class="iconClasses"
        :title="shortcut.title"
        aria-hidden="true"
        class="mdi course-tool__icon"
      ></i>
    </BaseAppLink>

    <BaseAppLink
      :target="props.shortcut.target || '_self'"
      :url="resolvedUrl"
      class="course-tool__title"
      rel="noopener noreferrer"
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
  try {
    // Absolute http(s) URLs are external — return as-is.
    if (/^https?:\/\//i.test(base)) {
      return base
    }

    // Relative/internal URL: append course/session context when missing.
    const [withoutHash, hash = ""] = base.split("#")
    const [path, rawQuery = ""] = withoutHash.split("?")
    const sp = new URLSearchParams(rawQuery)

    if (!sp.has("cid") && course.value?.id) {
      sp.set("cid", course.value.id)
    }

    if (!sp.has("sid")) {
      sp.set("sid", session.value?.id || 0)
    }

    const qs = sp.toString()

    return path + (qs ? `?${qs}` : "") + (hash ? `#${hash}` : "")
  } catch {
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
