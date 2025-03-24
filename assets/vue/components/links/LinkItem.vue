<template>
  <div class="flex flex-col pb-4 xl:flex-row xl:justify-between">
    <div class="pb-2">
      <h6>
        <a :href="link.url">
          <BaseIcon
            icon="link-external"
            size="small"
          />
          {{ link.title }}
        </a>
        <BaseIcon
          v-if="isAllowedToEdit && link.sessionId && link.sessionId === sid"
          class="mr-8"
          icon="session-star"
          size="small"
          title="Session Item"
        />
        <BaseIcon
          v-if="isLinkValid.isValid"
          :title="t('Link is valid')"
          class="text-green-500"
          icon="check"
          size="small"
        />
        <BaseIcon
          v-else-if="isLinkValid.isValid === false"
          :title="t('Link is not valid')"
          class="text-red-500"
          icon="alert"
          size="small"
        />
      </h6>
    </div>
    <div
      v-if="securityStore.isAuthenticated && canEdit(link)"
      class="flex gap-2"
    >
      <BaseButton
        :label="t('Check link')"
        icon="check"
        size="small"
        type="black"
        @click="emit('check', link)"
      />
      <BaseButton
        :label="t('Edit')"
        icon="edit"
        size="small"
        type="black"
        @click="emit('edit', link)"
      />
      <BaseButton
        :icon="isVisible(link.linkVisible) ? 'eye-on' : 'eye-off'"
        :label="t('Toggle visibility')"
        size="small"
        type="black"
        @click="emit('toggle', link)"
      />
      <BaseButton
        :label="t('Move up')"
        icon="up"
        size="small"
        type="black"
        @click="emit('moveUp', link)"
      />
      <BaseButton
        :label="t('Move down')"
        icon="down"
        size="small"
        type="black"
        @click="emit('moveDown', link)"
      />
      <BaseButton
        :label="t('Delete')"
        icon="delete"
        size="small"
        type="danger"
        @click="emit('delete', link)"
      />
    </div>
  </div>
</template>

<script setup>
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import { isVisible } from "./linkVisibility"
import { useSecurityStore } from "../../store/securityStore"
import { computed, onMounted, ref } from "vue"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
import { useRoute } from "vue-router"
import { useCidReq } from "../../composables/cidReq"

const securityStore = useSecurityStore()
const isCurrentTeacher = computed(() => securityStore.isCurrentTeacher)
const route = useRoute()
const { t } = useI18n()
const { cid, sid, gid } = useCidReq()

defineProps({
  link: {
    type: Object,
    required: true,
  },
  isLinkValid: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(["check", "edit", "toggle", "moveUp", "moveDown", "delete"])

const isAllowedToEdit = ref(false)

const canEdit = (item) => {
  const sessionId = item.sessionId
  const isSessionDocument = sessionId && sessionId === sid
  const isBaseCourse = !sessionId

  return (isSessionDocument && isAllowedToEdit.value) || (isBaseCourse && !sid && isCurrentTeacher.value)
}

onMounted(async () => {
  isAllowedToEdit.value = await checkIsAllowedToEdit(true, true, true)
})
</script>
