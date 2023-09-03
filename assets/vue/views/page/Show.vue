<template>
  <div v-if="item">
    <p
      class="text-body-1 font-semibold"
      v-text="item.title"
    />

    <div class="flex flex-row gap-4">
      <div class="w-2/3 flex justify-center">
        <div class="w-4/5">
          <div v-html="item.content" />
        </div>
      </div>

      <div class="w-1/3">
        <dl class="grid grid-cols-2">
          <dt
            v-t="'Author'"
            class="font-semibold"
          />
          <dl v-text="item.creator.username" />

          <dt
            v-t="'Locale'"
            class="font-semibold"
          />
          <dl v-text="item.locale" />

          <dt
            v-t="'Enabled'"
            class="font-semibold"
          />
          <dl v-t="item.enabled ? 'Yes' : 'No'" />

          <dt
            v-t="'Category'"
            class="font-semibold"
          />
          <dl v-text="item.category.title" />

          <dt
            v-t="'Created at'"
            class="font-semibold"
          />
          <dl v-text="item.createdAt ? relativeDatetime(item.createdAt) : ''" />

          <dt
            v-t="'Updated at'"
            class="font-semibold"
          />
          <dl v-text="item.updatedAt ? relativeDatetime(item.updatedAt) : ''" />
        </dl>
      </div>
    </div>
  </div>

  <Loading :visible="isLoading" />
</template>

<script setup>
import Loading from "../../components/Loading.vue"
import { useFormatDate } from "../../composables/formatDate"
import { useConfirm } from "primevue/useconfirm"
import { useRoute, useRouter } from "vue-router"
import { inject, ref, watch } from "vue"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"
import pageService from "../../services/page"
import { useNotification } from "../../composables/notification"

const { relativeDatetime } = useFormatDate()

const securityStore = useSecurityStore()
const { isAdmin } = storeToRefs(securityStore)
const route = useRoute()
const router = useRouter()

const confirm = useConfirm()
const notification = useNotification()

const isLoading = ref(true)
const item = ref()

const layoutMenuItems = inject("layoutMenuItems")

watch(item, () => {
  if (!isAdmin.value) {
    return
  }

  layoutMenuItems.value = [
    {
      label: "Edit page",
      to: {
        name: "PageUpdate",
        query: { id: item.value["@id"] },
      },
    },

    {
      label: "Delete page",
      command() {
        confirm.require({
          header: "Confirmation",
          message: "Are you sure you want to delete it?",
          async accept() {
            await pageService.del(item.value)

            await router.push({ name: "PageList" })
          },
        })
      },
    },
  ]
})

pageService
  .find(route.query.id)
  .then((response) => response.json())
  .then((json) => (item.value = json))
  .catch((e) => notification.showErrorNotification(e))
  .finally(() => (isLoading.value = false))
</script>