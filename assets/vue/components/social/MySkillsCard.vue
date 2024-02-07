<template>
  <BaseCard plain class="my-skills-card bg-white">
    <template #header>
      <div class="px-4 py-2 -mb-2 bg-gray-15">
        <h2 class="text-h5">{{ t('Skills') }}</h2>
      </div>
    </template>
    <hr class="-mt-2 mb-4 -mx-4">
    <div v-if="skills.length > 0">
      <div class="skills-container">
        <div v-for="skill in skills" :key="skill.id" class="skill-item">
          <img :src="skill.image" alt="skill.name" class="skill-badge">
          <div class="skill-name">{{ skill.name }}</div>
        </div>
      </div>
      <div class="skills-links mt-2">
        <a href="/main/social/skills_wheel.php">{{ t('Skills Wheel') }}</a> |
        <a href="/main/social/skills_ranking.php">{{ t('Your skill ranking') }}</a>
      </div>
    </div>
    <div v-else>
      <p>{{ t('Without achieved skills') }}</p>
    </div>
  </BaseCard>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue"
import { useI18n } from "vue-i18n"
import { ref, onMounted, inject, watchEffect } from "vue"
import axios from 'axios'
import { ENTRYPOINT } from "../../config/entrypoint"

const { t } = useI18n()
const skills = ref([])
const user = inject('social-user')

watchEffect(() => {
  if (user.value && user.value.id) {
    fetchSkills(user.value.id)
  }
})
async function fetchSkills(userId) {
  try {
    const response = await axios.get(`${ENTRYPOINT}users/${userId}/skills`)
    skills.value = response.data.map(skill => ({
      id: skill.id,
      name: skill.name,
      image: skill.image
    }))
  } catch (error) {
    console.error('Error fetching skills:', error)
  }
}
</script>
