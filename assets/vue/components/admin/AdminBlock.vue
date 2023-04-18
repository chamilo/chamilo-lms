<template>
  <div class="p-4 rounded-lg shadow-lg space-y-4">
    <div class="flex flex-col lg:flex-row gap-3 items-center">
      <div class="lg:basis-2/3 space-y-3 text-center lg:text-left">
        <h4>
          <span :class="`mdi-${props.icon}`" aria-hidden="true" class="mdi" />
          {{ props.title }}
        </h4>

        <p
          v-if="props.description"
          class="text-body-2"
          v-text="props.description"
        />
      </div>

      <form
        v-if="props.searchUrl"
        :action="props.searchUrl"
        class="lg:basis-1/3"
        method="get"
      >
        <div class="p-inputgroup flex-1">
          <InputText :placeholder="t('Keyword')" name="keyword" type="text" />
          <Button :label="t('Search')" icon="pi pi-search" type="submit" />
        </div>
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
      </ul>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import Button from "primevue/button";
import InputText from "primevue/inputtext";

const { t } = useI18n();

const props = defineProps({
  icon: { type: String, required: false, default: () => "cogs" },
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
