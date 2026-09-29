<template>
  <div>
    <section
      v-for="group in groupedGlossaries"
      :key="group.key"
      class="mb-6"
    >
      <h2 class="mb-3 text-lg font-semibold text-gray-90">
        {{ group.title }}
      </h2>

      <BaseCard
        v-for="term in group.items"
        :key="term.id"
        class="mb-4 bg-white"
        plain
      >
        <template #header>
          <div class="-mb-2 flex items-center justify-between gap-2 bg-gray-15 px-4 py-2">
            <div class="flex items-center gap-2">
              <span>{{ term.title }}</span>
              <span
                v-if="term.ai_assisted"
                class="ms-2 inline-flex items-center gap-1 rounded-full border border-gray-300 bg-gray-10 px-2 py-[2px] text-xs text-gray-700"
                title="AI-assisted"
                aria-label="AI-assisted"
              >
                <span aria-hidden="true">🤖</span>
                <span class="font-semibold">AI</span>
              </span>
              <BaseIcon
                v-if="isAllowedToEdit && term.sessionId && term.sessionId === sid"
                :title="t('Session Item')"
                class="me-8"
                icon="session-star"
                size="small"
              />
            </div>
            <div v-if="securityStore.isAuthenticated && props.canEditGlossary && canEdit(term)">
              <BaseButton
                :label="t('Edit')"
                class="me-2"
                icon="edit"
                only-icon
                size="small"
                type="black"
                @click="emit('edit', term)"
              />
              <BaseButton
                :label="t('Delete')"
                class="me-2"
                icon="delete"
                only-icon
                size="small"
                type="danger"
                @click="emit('delete', term)"
              />
            </div>
          </div>
        </template>

        <hr class="-mx-4 -mt-2 mb-4" />

        <div
          class="prose max-w-none"
          dir="auto"
          v-html="sanitize(term.description)"
        ></div>
      </BaseCard>
    </section>

    <p v-if="!isLoading && glossaries.length === 0">
      {{ t("There are no terms that match the search: {0}", [searchTerm]) }}
    </p>
  </div>
</template>

<script setup>
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import BaseCard from "../basecomponents/BaseCard.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import { useSecurityStore } from "../../store/securityStore"
import { computed } from "vue"
import { useIsAllowedToEdit } from "../../composables/userPermissions"
import { getCourseContext } from "../../utils/courseContext"
import DOMPurify from "dompurify"

const { t } = useI18n()
const securityStore = useSecurityStore()
const isCurrentTeacher = computed(() => securityStore.isCurrentTeacher)

const { isAllowedToEdit } = useIsAllowedToEdit({ tutor: true, coach: true, sessionCoach: true })
const { sid } = getCourseContext()

const props = defineProps({
  glossaries: {
    type: Array,
    required: true,
  },
  searchTerm: {
    type: String,
    required: true,
  },
  isLoading: {
    type: Boolean,
    required: true,
  },
  canEditGlossary: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(["edit", "delete"])

const groupedGlossaries = computed(() => {
  const groups = new Map()

  for (const term of props.glossaries) {
    const categoryId = Number(term.category?.iid || term.category?.id || 0)
    const key = categoryId > 0 ? `category-${categoryId}` : "uncategorized"
    const title = categoryId > 0 ? term.category?.title || t("Category") : t("No category")

    if (!groups.has(key)) {
      groups.set(key, { key, title, categoryId, items: [] })
    }
    groups.get(key).items.push(term)
  }

  return [...groups.values()].sort((a, b) => {
    if (a.categoryId === 0) return 1
    if (b.categoryId === 0) return -1
    return a.title.localeCompare(b.title)
  })
})

const canEdit = (item) => {
  const sessionId = item.sessionId
  const isSessionDocument = sessionId && sessionId === sid
  const isBaseCourse = !sessionId

  return (isSessionDocument && isAllowedToEdit.value) || (isBaseCourse && !sid && isCurrentTeacher.value)
}

const sanitize = (html) => DOMPurify.sanitize(html ?? "", { ADD_ATTR: ["target", "rel"] })
</script>
