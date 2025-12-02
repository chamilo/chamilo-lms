<template>
  <div id="social-wall-container">
    <div
      class="flex justify-center mb-6 space-x-4"
      role="tablist"
      aria-label="Social wall filters"
    >
      <button
        :class="tabClasses(null)"
        role="tab"
        :aria-selected="!filterType"
        @click="filterMessages(null)"
        type="button"
      >
        {{ t("All Messages") }}
      </button>
      <button
        :class="tabClasses('promoted')"
        role="tab"
        :aria-selected="filterType === 'promoted'"
        @click="filterMessages('promoted')"
        type="button"
      >
        {{ t("Promoted Messages") }}
      </button>
    </div>

    <SocialWallPostForm
      v-if="!hidePostForm && isCurrentUser && (!filterType || isAdmin)"
      class="mb-6"
      @post-created="refreshPosts"
    />
    <SocialWallPostList
      ref="postListRef"
      class="mb-6"
    />
  </div>
</template>

<script setup>
import { inject, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import SocialWallPostForm from "../../components/social/SocialWallPostForm.vue"
import SocialWallPostList from "../../components/social/SocialWallPostList.vue"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()

const props = defineProps({
  hidePostForm: {
    type: Boolean,
    default: false,
  },
})

const postListRef = ref(null)
const isCurrentUser = inject("is-current-user")
const route = useRoute()
const router = useRouter()
const filterType = ref(route.query.filterType || null)
const securityStore = useSecurityStore()
const isAdmin = securityStore.isAdmin

watch(
  () => route.query.filterType,
  (newFilterType) => {
    filterType.value = newFilterType || null
    postListRef.value?.refreshPosts()
  },
)

function refreshPosts() {
  if (postListRef.value) {
    postListRef.value.refreshPosts()
  }
}

function filterMessages(type) {
  if (type === null) {
    router.push({ path: "/social" })
  } else {
    router.push({ path: "/social", query: { filterType: type } })
  }
}

function tabClasses(type) {
  const isActive = type ? filterType.value === type : !filterType.value
  return [
    "inline-flex items-center rounded-full border px-4 py-2 text-body-2 font-medium transition-colors duration-150",
    "focus:outline-none focus:ring-2 focus:ring-primary",
    isActive
      ? "bg-primary border-primary text-white shadow-sm hover:bg-primary/90"
      : "bg-white border-gray-25 text-gray-90 hover:bg-gray-15",
  ]
}
</script>
