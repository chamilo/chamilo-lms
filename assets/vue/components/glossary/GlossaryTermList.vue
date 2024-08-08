<template>
  <ul>
    <li>
      <BaseCard
        v-for="term in glossaries"
        :key="term.id"
        class="mb-4 bg-white"
        plain
      >
        <template #header>
          <div class="-mb-2 flex items-center justify-between gap-2 bg-gray-15 px-4 py-2">
            <div class="flex items-center gap-2">
              <span>{{ term.title }}</span>
              <BaseIcon
                v-if="isAllowedToEdit && (term.sessionId && term.sessionId === sid)"
                icon="session-star"
                size="small"
                class="mr-8"
                title="Session Item"
              />
            </div>
            <div v-if="securityStore.isAuthenticated && canEdit(term)">
              <BaseButton
                :label="t('Edit')"
                class="mr-2"
                icon="edit"
                type="black"
                size="small"
                @click="emit('edit', term)"
              />
              <BaseButton
                :label="t('Delete')"
                class="mr-2"
                icon="delete"
                type="danger"
                size="small"
                @click="emit('delete', term)"
              />
            </div>
          </div>
        </template>

        <hr class="-mx-4 -mt-2 mb-4" />

        <div>
          {{ term.description }}
        </div>
      </BaseCard>
    </li>
    <li v-if="!isLoading && glossaries.length === 0">
      {{ t("There is no terms that matches the search: {searchTerm}", { searchTerm: searchTerm }) }}
    </li>
  </ul>
</template>

<script setup>
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import BaseCard from "../basecomponents/BaseCard.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import { useSecurityStore } from "../../store/securityStore"
import { useRoute } from "vue-router"
import { computed, onMounted, ref } from "vue"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
import { useCidReq } from "../../composables/cidReq"

const { t } = useI18n()
const securityStore = useSecurityStore()
const route = useRoute()
const isCurrentTeacher = computed(() => securityStore.isCurrentTeacher)

const isAllowedToEdit = ref(false)
const { cid, sid, gid } = useCidReq()

defineProps({
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
})

const emit = defineEmits(["edit", "delete"])

const canEdit = (item) => {
  const sessionId = item.sessionId;
  const isSessionDocument = sessionId && sessionId === sid;
  const isBaseCourse = !sessionId;

  return (
    (isSessionDocument && isAllowedToEdit.value) ||
    (isBaseCourse && !sid && isCurrentTeacher.value)
  );
}

onMounted(async () => {
  isAllowedToEdit.value = await checkIsAllowedToEdit(true, true, true)
})
</script>
