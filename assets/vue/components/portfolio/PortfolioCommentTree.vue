<template>
  <div class="space-y-3">
    <article
      v-for="comment in comments"
      :id="`portfolio-comment-${comment.id}`"
      :key="comment.id"
      class="rounded-xl border border-gray-20 bg-white p-4"
    >
      <div class="flex items-start gap-3">
        <BaseUserAvatar
          :alt="comment.author?.fullName || t('User')"
          :image-url="comment.author?.imageUrl || ''"
        />

        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
              <div class="font-semibold text-gray-90">
                {{ comment.author?.fullName || t("User") }}
              </div>
              <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                <span>{{ formatDate(comment.date) }}</span>
                <span
                  v-if="comment.isImportant"
                  class="inline-flex items-center gap-1 font-medium text-warning"
                >
                  <BaseIcon icon="trophy" size="small" />
                  {{ t("Important") }}
                </span>
                <span v-if="comment.score !== null && comment.score !== undefined">
                  {{ t("Score") }}: {{ comment.score }}
                </span>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-1">
              <BaseButton
                v-if="comment.canReply"
                icon="reply"
                :label="t('Reply')"
                only-icon
                size="small"
                type="secondary-text"
                @click="$emit('reply', comment)"
              />
              <BaseButton
                v-if="comment.canEdit"
                icon="pencil"
                :label="t('Edit')"
                only-icon
                size="small"
                type="secondary-text"
                @click="$emit('edit', comment)"
              />
              <BaseButton
                v-if="comment.canMarkImportant"
                icon="trophy"
                :label="t('Important')"
                only-icon
                size="small"
                :type="comment.isImportant ? 'primary' : 'secondary-text'"
                @click="$emit('action', { comment, action: 'toggle_important' })"
              />
              <BaseButton
                v-if="comment.canUseAsTemplate"
                icon="copy"
                :label="t('Template')"
                only-icon
                size="small"
                type="secondary-text"
                @click="$emit('action', { comment, action: 'toggle_template' })"
              />
              <BaseButton
                v-if="comment.canQualify"
                icon="gradebook"
                :label="t('Score')"
                only-icon
                size="small"
                type="secondary-text"
                @click="$emit('score', comment)"
              />
              <BaseButton
                v-if="comment.canCopyToOwn"
                icon="copy"
                :label="t('Copy to my portfolio')"
                only-icon
                size="small"
                type="secondary-text"
                @click="$emit('action', { comment, action: 'copy_to_own' })"
              />
              <BaseButton
                v-if="comment.canCopyToStudent"
                icon="assign-users"
                :label="t('Copy to students')"
                only-icon
                size="small"
                type="secondary-text"
                @click="$emit('copy-students', comment)"
              />
              <BaseButton
                v-if="comment.canEdit"
                icon="eye-on"
                :label="t('Visibility')"
                only-icon
                size="small"
                type="secondary-text"
                @click="$emit('visibility', comment)"
              />
              <BaseButton
                v-if="comment.canDelete"
                icon="delete"
                :label="t('Delete')"
                only-icon
                size="small"
                type="danger-text"
                @click="$emit('delete', comment)"
              />
            </div>
          </div>

          <div class="mt-3 break-words" v-html="comment.content"></div>

          <div
            v-if="comment.attachments?.length"
            class="mt-3 space-y-2"
          >
            <div
              v-for="attachment in comment.attachments"
              :key="attachment.id"
              class="flex items-center gap-2"
            >
              <a
                :href="attachment.downloadUrl"
                class="inline-flex min-w-0 items-center gap-1 truncate text-sm text-primary hover:underline"
              >
                <BaseIcon icon="attachment" size="small" />
                {{ attachment.filename }}
              </a>
              <BaseButton
                v-if="attachment.canDelete"
                icon="delete"
                :label="t('Delete')"
                only-icon
                size="small"
                type="danger-text"
                @click="$emit('delete-attachment', { comment, attachment })"
              />
            </div>
          </div>
        </div>
      </div>

      <PortfolioCommentTree
        v-if="comment.children?.length"
        :comments="comment.children"
        class="ml-6 mt-4 border-l border-gray-20 pl-4"
        @action="$emit('action', $event)"
        @copy-students="$emit('copy-students', $event)"
        @delete="$emit('delete', $event)"
        @delete-attachment="$emit('delete-attachment', $event)"
        @edit="$emit('edit', $event)"
        @reply="$emit('reply', $event)"
        @score="$emit('score', $event)"
        @visibility="$emit('visibility', $event)"
      />
    </article>
  </div>
</template>

<script setup>
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import BaseUserAvatar from "../basecomponents/BaseUserAvatar.vue"

const { t } = useI18n()

defineProps({
  comments: {
    type: Array,
    required: true,
    default: () => [],
  },
})

defineEmits([
  "action",
  "copy-students",
  "delete",
  "delete-attachment",
  "edit",
  "reply",
  "score",
  "visibility",
])

function formatDate(value) {
  const date = new Date(value)

  return Number.isNaN(date.getTime())
    ? String(value || "")
    : new Intl.DateTimeFormat(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
      }).format(date)
}
</script>
