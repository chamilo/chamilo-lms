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
      </h6>
    </div>
    <div class="flex gap-2">
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
import { isVisible, VISIBLE } from "./linkVisibility"

const { t } = useI18n()

defineProps({
  link: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(["check", "edit", "toggle", "moveUp", "moveDown", "delete"])
</script>
