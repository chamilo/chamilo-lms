<script setup>
import { onMounted } from "vue"

import Skeleton from "primevue/skeleton"

import { useSkillWheel } from "../../composables/skill/skillWheel"

const { wheelContainer, isLoading, loadSkills, showRoot, showSkill } = useSkillWheel()

defineExpose({
  showRoot,
  showSkill,
})

onMounted(() => {
  loadSkills()
})
</script>

<template>
  <div class="aspect-square">
    <div ref="wheelContainer" />

    <Skeleton
      v-if="isLoading"
      shape="circle"
      size="100%"
    />
  </div>
</template>
