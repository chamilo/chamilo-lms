<template>
  <BaseCard class="social-side-menu mt-4">
    <template #header>
      <div class="px-4 py-2 -mb-2 bg-gray-15">
        <h2 class="text-h5">{{ t('Social Group') }}</h2>
      </div>
    </template>
    <hr class="-mt-2 mb-4 -mx-4">
      <ul class="menu-list">
        <li class="menu-item">
          <router-link to="/social">
            <i class="mdi mdi-home" aria-hidden="true"></i>
            {{ t("Home") }}
          </router-link>
        </li>
        <li class="menu-item">
          <router-link :to="{ name: '', params: { group_id: groupInfo.id } }">
            <i class="mdi mdi-account-edit" aria-hidden="true"></i>
            {{ t("Edit this group") }}
          </router-link>
        </li>
        <li class="menu-item">
          <router-link :to="{ name: '', params: { group_id: groupInfo.id } }">
            <i class="mdi mdi-account-multiple-outline" aria-hidden="true"></i>
            {{ t("Waiting list") }}
          </router-link>
        </li>
        <li class="menu-item">
          <router-link :to="{ name: '', params: { group_id: groupInfo.id } }">
            <i class="mdi mdi-account-plus" aria-hidden="true"></i>
            {{ t("Invite friends") }}
          </router-link>
        </li>
        <li class="menu-item">
          <router-link :to="{ name: '', params: { group_id: groupInfo.id } }">
            <i class="mdi mdi-exit-to-app" aria-hidden="true"></i>
            {{ t("Leave group") }}
          </router-link>
        </li>
      </ul>
  </BaseCard>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue"
import { useRoute } from 'vue-router'
import { useI18n } from "vue-i18n"
import { onMounted, computed, ref, inject, watchEffect } from "vue"
import { useStore } from "vuex"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const route = useRoute()
const store = useStore()
const securityStore = useSecurityStore()

const groupInfo = inject('group-info')
const isGroup = inject('is-group')

const isActive = (path, filterType = null) => {
  const pathMatch = route.path.startsWith(path)
  const hasQueryParams = Object.keys(route.query).length > 0
  const filterMatch = filterType ? (route.query.filterType === filterType && hasQueryParams) : !hasQueryParams
  return pathMatch && filterMatch
}
</script>
