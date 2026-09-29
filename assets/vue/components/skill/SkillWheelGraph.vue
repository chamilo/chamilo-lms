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
  <div class="w-full flex justify-center">
    <div class="w-full max-w-[64rem]">
      <div
        ref="wheelContainer"
        class="w-full"
      />

      <Skeleton
        v-if="isLoading"
        class="w-full aspect-square"
        shape="circle"
        size="100%"
      />
    </div>
  </div>
</template>
