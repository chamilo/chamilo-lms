<script setup>
import { onMounted } from "vue"

import Skeleton from "primevue/skeleton"

import { useSkillWheel } from "../../composables/skill/skillWheel"

const emit = defineEmits(["skill-detail"])

const { wheelContainer, isLoading, loadSkills, showRoot, showSkill } = useSkillWheel({
  onSkillDetail: (detail) => emit("skill-detail", detail),
})

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
