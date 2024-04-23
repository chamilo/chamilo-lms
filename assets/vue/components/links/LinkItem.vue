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
    <div class="flex gap-2" v-if="securityStore.isAuthenticated && isCurrentTeacher">
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

const securityStore = useSecurityStore()
const isCurrentTeacher = securityStore.isCurrentTeacher

const { t } = useI18n()

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
</script>
