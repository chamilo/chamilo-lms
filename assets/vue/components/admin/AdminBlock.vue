<template>
  <div class="admin-index__block-container">
    <div class="admin-index__block">
      <div class="flex gap-2 justify-between">
        <h4>
          <BaseIcon :icon="icon" />
          {{ props.title }}
        </h4>
      </div>

      <div class="space-y-4">
        <p v-if="props.description" class="text-body-4" v-text="props.description" />

        <form v-if="props.searchUrl" :action="props.searchUrl" method="get">
          <BaseInputGroup
            :button-label="t('Search')"
            :input-placeholder="t('Keyword')"
            button-icon="search"
            input-name="keyword"
          />
        </form>
      </div>

    <div class="p-menu p-component p-ripple-disabled">
      <ul class="p-menu-list p-reset" role="menu">
        <li
          v-for="(item, index) in visibleItems"
          :key="index"
          :aria-label="t(item.label)"
          :class="item.className"
          class="p-menuitem"
          role="menuitem"
        >
          <div class="p-menuitem-content">
            <a :href="item.url" class="p-menuitem-link">
              <span class="p-menuitem-text" v-text="item.label" />
            </a>
          </div>
        </li>
        <slot></slot>
      </ul>
      <div class="p-menu p-component p-ripple-disabled">
        <ul class="p-menu-list p-reset" role="menu">
          <li
            v-for="(item, index) in visibleItems"
            :key="index"
            :aria-label="t(item.label)"
            :class="item.className"
            class="p-menuitem"
            role="menuitem"
          >
            <div class="p-menuitem-content">
              <a :href="item.url" class="p-menuitem-link">
                <span class="p-menuitem-text" v-text="item.label" />
              </a>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import BaseInputGroup from "../basecomponents/BaseInputGroup.vue";
import BaseIcon from "../basecomponents/BaseIcon.vue";

const { t } = useI18n();

const props = defineProps({
  icon: { type: String, required: false, default: () => "admin-settings" },
  title: { type: String, require: true, default: () => "" },
  description: { type: String, required: false, default: () => null },
  searchUrl: { type: String, required: false, default: () => null },
  items: { type: Array, required: true, default: () => [] },
});

const visibleItems = computed(() =>
  props.items
    .map((item) => {
      if (!Object.keys(item).includes("visible")) {
        item.visible = true;
      }

      return item;
    })
    .filter((item) => item.visible)
);
</script>
