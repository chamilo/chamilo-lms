<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"

import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import SkillWheelProfileList from "../../components/skill/SkillWheelProfileList.vue"
import SkillProfileDialog from "../../components/skill/SkillProfileDialog.vue"
import SkillWheelGraph from "../../components/skill/SkillWheelGraph.vue"

import { useNotification } from "../../composables/notification"

import * as skillService from "../../services/skillService"
import SkillProfileMatches from "../../components/skill/SkillProfileMatches.vue"

const { t } = useI18n()

const { showErrorNotification } = useNotification()

const profileListEL = ref()
const wheelEl = ref()

const foundSkills = ref([])

/**
 * @param {string} query
 * @returns {Promise<Object[]>}
 */
async function findSkills(query) {
  try {
    const { items } = await skillService.findAll({ title: query })

    return items.map((item) => ({ name: item.title, value: item["@id"], ...item }))
  } catch (e) {
    showErrorNotification(e)

    return []
  }
}

const showSkilProfileForm = ref(false)

const profileMatchesEl = ref()
const showProfileMatches = ref(false)

async function onClickSearchProfileMatches() {
  showProfileMatches.value = true

  await profileMatchesEl.value.searchProfileMatches(foundSkills.value)
}

async function onClickViewSkillWheel() {
  showProfileMatches.value = false

  wheelEl.value.showRoot()
}

async function onSearchProfile(profile) {
  const profileSkills = profile.skills.map((skillRelProfile) => skillRelProfile.skill)

  showProfileMatches.value = true

  await profileMatchesEl.value.searchProfileMatches(profileSkills)
}
</script>

<template>
  <SectionHeader :title="t('Skills wheel')" />

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="xl:col-span-1 skill-options flex flex-col gap-4">
      <SkillWheelProfileList
        ref="profileListEL"
        @search-profile="onSearchProfile"
      />

      <BaseCard>
        <template #title>{{ t("What skills are you looking for?") }}</template>

        <template #footer>
          <BaseButton
            :label="t('View skills wheel')"
            icon="wheel"
            type="secondary"
            @click="onClickViewSkillWheel()"
          />
        </template>

        <BaseAutocomplete
          id="skill_id"
          v-model="foundSkills"
          :label="t('Enter the skill name to search')"
          :search="findSkills"
          is-multiple
        >
          <template #chip="{ value }">
            {{ value.name }}

            <span
              class="p-autocomplete-token-icon"
              @click="wheelEl.showSkill(value.id)"
            >
              <BaseIcon
                icon="crosshairs"
                size="small"
              />
            </span>
          </template>
        </BaseAutocomplete>

        <BaseButton
          :disabled="!foundSkills.length"
          :label="t('Search profile matches')"
          icon="search"
          type="black"
          @click="onClickSearchProfileMatches"
        />

        <p v-t="'Is this what you were looking for?'" />

        <BaseButton
          :disabled="!foundSkills.length"
          :label="t('Save this search')"
          icon="search"
          type="black"
          @click="showSkilProfileForm = true"
        />
      </BaseCard>

      <BaseCard>
        <template #title>{{ t("Legend") }}</template>

        <ul class="fa-ul">
          <li>
            <BaseIcon
              class="skill-legend-basic"
              icon="square"
            />

            {{ t("Basic skills") }}
          </li>
          <li>
            <BaseIcon
              class="skill-legend-add"
              icon="square"
            />
            {{ t("Skills you can learn") }}
          </li>
          <li>
            <BaseIcon
              class="skill-legend-search"
              icon="square"
            />
            {{ t("Skills searched for") }}
          </li>
        </ul>
      </BaseCard>
    </div>

    <div class="xl:col-span-2">
      <SkillWheelGraph
        v-show="!showProfileMatches"
        ref="wheelEl"
      />
      <SkillProfileMatches
        v-show="showProfileMatches"
        ref="profileMatchesEl"
      />
    </div>
  </div>

  <SkillProfileDialog
    v-model:skills="foundSkills"
    v-model:visible="showSkilProfileForm"
    @saved="profileListEL.loadProfiles()"
  />
</template>
