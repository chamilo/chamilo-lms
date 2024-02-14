<template>
  <BaseCard plain>
    <div class="p-4 text-center">
      <img
        :src="groupInfo.image"
        class="mb-4 w-24 h-24 mx-auto rounded-full"
        alt="Group picture"
      />
      <hr />
      <BaseButton
        :label="t('Edit this group')"
        type="primary"
        class="mt-4"
        @click="editGroup"
        icon="edit"
      />
    </div>
  </BaseCard>
</template>

<script setup>
import { computed, inject, onMounted, ref, watch } from "vue"
import { useStore } from 'vuex'
import BaseCard from "../basecomponents/BaseCard.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"

const { t } = useI18n()
const store = useStore()
const route = useRoute()
const groupInfo = inject('group-info')
const isGroup = inject('is-group')

const editGroup = () => {
  window.location = "/account/edit"
}
</script>
