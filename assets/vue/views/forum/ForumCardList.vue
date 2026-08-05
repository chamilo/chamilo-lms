<template>
  <div class="overflow-hidden rounded-lg border border-gray-20 bg-white">
    <article
      v-for="(forum, forumIndex) in forums"
      :key="forum.iid"
      class="border-b border-gray-20 last:border-b-0"
    >
      <div class="flex flex-col gap-4 p-4 md:flex-row md:items-center md:justify-between">
        <div class="flex min-w-0 flex-1 gap-4">
          <div class="flex h-16 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-15">
            <img
              v-if="getForumImage(forum)"
              :alt="forum.title || t('Forum image')"
              :src="getForumImage(forum)"
              class="h-full w-full object-cover"
              @error="markForumImageAsBroken(forum)"
            />
            <BaseIcon
              v-else
              :icon="Number(forum.locked || 0) ? 'lock' : 'comment'"
              size="big"
            />
          </div>

          <div class="min-w-0 flex-1">
            <router-link
              :to="{
                name: 'ForumThreadList',
                params: { node, forumId: forum.iid },
                query: route.query,
              }"
              class="text-base font-semibold text-gray-90 hover:text-primary hover:underline"
            >
              {{ forum.title }}
            </router-link>

            <div
              v-if="forum.forumComment"
              class="prose prose-sm mt-1 max-w-none text-sm leading-5 text-gray-600"
              v-html="sanitizeForumComment(forum.forumComment)"
            />

            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
              <span>{{ t("Threads") }}: {{ forum.forumThreads || 0 }}</span>
              <span>{{ t("Posts") }}: {{ forum.forumPosts || 0 }}</span>
              <span
                v-if="forum.locked"
                class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700"
              >
                {{ t("Locked") }}
              </span>
              <span
                v-if="!isForumVisible(forum)"
                class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700"
              >
                {{ t("Hidden") }}
              </span>
              <span
                v-if="forum.moderated"
                class="rounded-full bg-blue-100 px-2 py-0.5 text-blue-700"
              >
                {{ t("Moderated") }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center justify-end gap-1">
          <BaseButton
            :label="t('View threads')"
            :route="{
              name: 'ForumThreadList',
              params: { node, forumId: forum.iid },
              query: route.query,
            }"
            icon="comment"
            only-icon
            size="small"
            type="primary-text"
          />
          <BaseButton
            v-if="forum.canSubscribe"
            :label="forum.subscribed ? t('Stop notifying me') : t('Notify me')"
            :icon="forum.subscribed ? 'email-unread' : 'email-plus'"
            only-icon
            size="small"
            type="primary-text"
            @click="$emit('toggle-forum-notification', forum)"
          />
          <BaseButton
            v-if="canManage"
            :label="isForumVisible(forum) ? t('Hide') : t('Show')"
            :icon="isForumVisible(forum) ? 'eye-on' : 'eye-off'"
            only-icon
            size="small"
            type="primary-text"
            @click="$emit('toggle-forum-visibility', forum)"
          />
          <BaseButton
            v-if="canManage"
            :label="t('Edit')"
            icon="edit"
            only-icon
            size="small"
            type="secondary-text"
            @click="$emit('edit-forum', forum)"
          />
          <BaseButton
            v-if="canManage"
            :label="Number(forum.locked || 0) ? t('Unlock') : t('Lock')"
            :icon="Number(forum.locked || 0) ? 'unlock' : 'lock'"
            only-icon
            size="small"
            type="secondary-text"
            @click="$emit('toggle-forum-lock', forum)"
          />
          <BaseButton
            v-if="canManage"
            :label="t('Delete')"
            icon="delete"
            only-icon
            size="small"
            type="danger-text"
            @click="$emit('delete-forum', forum)"
          />
          <BaseButton
            v-if="canManage"
            :disabled="0 === forumIndex"
            :label="t('Move up')"
            icon="arrow-up"
            only-icon
            size="small"
            type="secondary-text"
            @click="$emit('move-forum', forum, 'up')"
          />
          <BaseButton
            v-if="canManage"
            :disabled="forumIndex >= forums.length - 1"
            :label="t('Move down')"
            icon="arrow-down"
            only-icon
            size="small"
            type="secondary-text"
            @click="$emit('move-forum', forum, 'down')"
          />
        </div>
      </div>
    </article>
  </div>
</template>

<script setup>
import { computed, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import { sanitizeHtml } from "../../utils/sanitizeHtml"

const { t } = useI18n()
const route = useRoute()
const node = computed(() => Number(route.params.node || 0))
const brokenForumImageIds = ref(new Set())

defineProps({
  forums: {
    type: Array,
    required: true,
  },
  canManage: {
    type: Boolean,
    default: false,
  },
})

defineEmits(["edit-forum", "delete-forum", "toggle-forum-lock", "toggle-forum-visibility", "move-forum", "toggle-forum-notification"])

function sanitizeForumComment(value) {
  return sanitizeHtml(value || "")
}

function getForumImage(forum) {
  const forumId = Number(forum?.iid || 0)
  if (forumId && brokenForumImageIds.value.has(forumId)) {
    return ""
  }

  return String(forum?.forumImage || forum?.forumImageUrl || "").trim()
}

function markForumImageAsBroken(forum) {
  const forumId = Number(forum?.iid || 0)
  if (!forumId) {
    return
  }

  const nextValue = new Set(brokenForumImageIds.value)
  nextValue.add(forumId)
  brokenForumImageIds.value = nextValue
}

function isForumVisible(forum) {
  if (forum?.forumVisible === undefined || forum?.forumVisible === null) {
    return true
  }

  return true === forum.forumVisible || 1 === forum.forumVisible || "1" === String(forum.forumVisible)
}
</script>
