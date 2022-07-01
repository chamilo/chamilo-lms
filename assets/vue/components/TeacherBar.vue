<template>
  <div
    class="teacher-bar"
    :class="{ 'teacher-bar--simple': isSimpleLayout }"
  >
    <div
      v-for="user in teachers"
      :key="user.id"
      class="teacher-bar__item"
    >
      <Avatar
        :image="`${user.illustrationUrl}?w=80&h=80&fit=crop`"
        shape="circle"
      />
      <div
        v-if="isSimpleLayout"
        class="teacher-bar__item-caption"
      >
        <span v-text="user.fullName" />
        <span v-t="'Coach'" />
      </div>
    </div>
  </div>
</template>

<script setup>
import Avatar from 'primevue/avatar';

// eslint-disable-next-line no-undef
const props = defineProps({
  teachers: {
    required: true,
    type: Array
  }
});

const isSimpleLayout = props.teachers.length < 3;
</script>
