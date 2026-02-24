<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"

import BaseCard from "../basecomponents/BaseCard.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import Skeleton from "primevue/skeleton"

import * as skillProfileService from "../../services/skillProfileService"

import { useNotification } from "../../composables/notification"
import { useConfirm } from "primevue/useconfirm"

const { t } = useI18n()
const { showErrorNotification, showSuccessNotification } = useNotification()
const confirm = useConfirm()

const isLoading = ref(true)

const profileList = ref([])

async function loadProfiles() {
  try {
    isLoading.value = true

    const { items } = await skillProfileService.findAll()

    profileList.value = items
  } catch (e) {
    showErrorNotification(e)
  } finally {
    isLoading.value = false
  }
}

defineExpose({
  loadProfiles,
})

onMounted(() => {
  loadProfiles()
})

async function onClickDeleteProfile(profile) {
  confirm.require({
    message: t('Are you sure you want to delete {0}?', [profile.title]),
    header: t("Delete skill profile"),
    icon: "mdi mdi-alert",
    async accept() {
      if (!profile) {
        return
      }

      try {
        isLoading.value = true

        await skillProfileService.deleteProfile(profile["@id"])

        showSuccessNotification(t("Skill profile deleted"))
      } catch (e) {
        showErrorNotification(e)
      } finally {
        isLoading.value = false
      }

      await loadProfiles()
    },
  })
}

const emit = defineEmits(["searchProfile"])
</script>

<template>
  <BaseCard>
    <template #title>{{ t("Skill profiles") }}</template>

    <div
      v-if="isLoading"
      class="space-y-2"
    >
      <div
        v-for="v in 3"
        :key="v"
        class="flex flex-row gap-2 items-center"
      >
        <Skeleton
          class="mr-auto"
          width="10rem"
        />
        <Skeleton size="2.5rem" />
        <Skeleton size="2.5rem" />
      </div>
    </div>

    <ul
      v-else-if="profileList.length > 0"
      class="space-y-2"
    >
      <li
        v-for="(profile, i) in profileList"
        :key="i"
        class="flex flex-row gap-2 items-center"
      >
        <span
          class="mr-auto"
          v-text="profile.title"
        />

        <BaseButton
          :label="t('Search')"
          icon="search"
          only-icon
          type="black"
          @click="emit('searchProfile', profile)"
        />
        <BaseButton
          :label="t('Delete')"
          icon="delete"
          only-icon
          type="danger"
          @click="onClickDeleteProfile(profile)"
        />
      </li>
    </ul>

    <p
      v-else
      v-text="t('No skill profile')"
    />
  </BaseCard>
</template>
