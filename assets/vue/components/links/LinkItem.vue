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
          v-if="isAllowedToEdit && (link.sessionId && link.sessionId === sid)"
          icon="session-star"
          size="small"
          class="mr-8"
          title="Session Item"
        />
        <BaseIcon
          v-if="isLinkValid.isValid"
          icon="check"
          size="small"
          class="text-green-500"
          :title="t('Link is valid')"
        />
        <BaseIcon
          v-else-if="isLinkValid.isValid === false"
          icon="alert"
          size="small"
          class="text-red-500"
          :title="t('Link is not valid')"
        />
      </h6>
    </div>
    <div class="flex gap-2" v-if="securityStore.isAuthenticated && canEdit(link)">
      <BaseButton
        type="black"
        icon="check"
        size="small"
        :label="t('Check link')"
        @click="emit('check', link)"
      />
      <BaseButton
        type="black"
        icon="edit"
        size="small"
        :label="t('Edit')"
        @click="emit('edit', link)"
      />
      <BaseButton
        type="black"
        :icon="isVisible(link.linkVisible) ? 'eye-on' : 'eye-off'"
        size="small"
        :label="t('Toggle visibility')"
        @click="emit('toggle', link)"
      />
      <BaseButton
        type="black"
        icon="up"
        size="small"
        :label="t('Move up')"
        @click="emit('moveUp', link)"
      />
      <BaseButton
        type="black"
        icon="down"
        size="small"
        :label="t('Move down')"
        @click="emit('moveDown', link)"
      />
      <BaseButton
        type="danger"
        icon="delete"
        size="small"
        :label="t('Delete')"
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
import { checkIsAllowedToEdit } from "../../composables/userPermissions";
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
    default: () => ({})
  },
});

const emit = defineEmits(["check", "edit", "toggle", "moveUp", "moveDown", "delete"])

const isAllowedToEdit = ref(false)

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
