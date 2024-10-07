<script setup>
import BaseUserAvatar from "./BaseUserAvatar.vue"
import { useI18n } from "vue-i18n"
import Avatar from "primevue/avatar"
import { useAvatarList } from "../../composables/useAvatarList"

const props = defineProps({
  users: {
    required: true,
    type: Array,
  },
  shortSeveral: {
    required: false,
    type: Boolean,
    default: true,
  },
  countSeveral: {
    required: false,
    type: Number,
    default: 2,
  },
})

const { several, userList, plusText } = useAvatarList(props)

const { t } = useI18n()
</script>

<template>
  <ul
    class="avatar-list"
    :class="{ 'avatar-list--several': several }"
  >
    <li
      v-for="(user, idx) in userList"
      :key="idx"
      class="avatar-container"
      :title="user.fullName"
    >
      <BaseUserAvatar
        :image-url="user.illustrationUrl"
        :alt="t('%s\'s picture', [user.username])"
      />
      <div class="avatar-info">
        <p v-text="user.fullName" />
        <p v-text="user.username" />
      </div>
    </li>
    <li v-if="several">
      <Avatar
        :label="plusText"
        shape="circle"
      />
    </li>
  </ul>
</template>
