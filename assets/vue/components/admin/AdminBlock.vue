<template>
  <div
    class="admin-index__block-container"
    :class="id"
  >
    <div class="admin-index__block">
      <div class="flex gap-2 justify-between">
        <h4>
          <BaseIcon :icon="icon" />
          {{ props.title }}
        </h4>
      </div>

      <div class="space-y-4">
        <p
          v-if="props.description"
          class="text-body-4"
          v-text="props.description"
        />

        <form
          v-if="props.searchUrl"
          :action="props.searchUrl"
          method="get"
        >
          <BaseInputGroup
            :button-label="t('Search')"
            :input-placeholder="t('Keyword')"
            button-icon="search"
            input-name="keyword"
          />
        </form>
      </div>

      <div class="p-menu p-component p-ripple-disabled">
        <ul
          class="p-menu-list p-reset"
          role="menu"
        >
          <li
            v-for="(item, index) in visibleItems"
            :key="index"
            :aria-label="t(item.label)"
            :class="item.className"
            class="p-menuitem"
            role="menuitem"
          >
            <div class="p-menuitem-content">
              <BaseAppLink
                :to="item.route"
                :url="item.url"
                class="p-menuitem-link"
              >
                <span
                  class="p-menuitem-text"
                  v-text="item.label"
                />
              </BaseAppLink>
            </div>
          </li>
        </ul>
      </div>

      <AdminBlockExtraContent
        :id="id"
        v-model="modelExtraContent"
        :editable="editable"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import BaseInputGroup from "../basecomponents/BaseInputGroup.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import AdminBlockExtraContent from "./AdminBlockExtraContent.vue"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"

const { t } = useI18n()

const modelExtraContent = defineModel("extraContent", {
  type: Object,
})

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
  editable: {
    type: Boolean,
    required: false,
    default: false,
  },
  icon: { type: String, required: false, default: () => "admin-settings" },
  title: { type: String, require: true, default: () => "" },
  description: { type: String, required: false, default: () => null },
  searchUrl: { type: String, required: false, default: () => null },
  items: { type: Array, required: true, default: () => [] },
})

const visibleItems = computed(() =>
  props.items
    .map((item) => {
      if (!Object.keys(item).includes("visible")) {
        item.visible = true
      }

      return item
    })
    .filter((item) => item.visible),
)
</script>
