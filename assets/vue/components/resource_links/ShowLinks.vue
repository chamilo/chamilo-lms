<template>
  <div
    v-for="(link, index) in item.resourceLinkListFromEntity"
    :key="index"
    class="field space-y-2"
  >
    <div
      v-if="link.course"
      :class="{ 'text-right text-body-2': editStatus }"
    >
      <span class="mdi mdi-book" />
      {{ t('Course: {0}', [ link.course.resourceNode.title ]) }}
    </div>

    <div
      v-if="link.session"
      :class="{ 'text-right text-body-2': editStatus }"
    >
      <span class="mdi mdi-book-open" />
      {{ t('Session: {0}', [ link.session.name ]) }}
    </div>

    <div
      v-if="link.group"
      :class="{ 'text-right text-body-2': editStatus }"
    >
      <span class="mdi mdi-people" />
      {{ t('Group: {0}', [ link.group.resourceNode.title ]) }}
    </div>

    <div
      v-if="link.userGroup"
      v-t="{ path: 'Class: {0}', args: [ link.userGroup.resourceNode.title ] }"
    />

    <div
      v-if="link.user"
    >
      <span class="mdi mdi-account" />
      <!--  @todo add avatar        -->
      <!--  <q-avatar size="32px">-->
      <!--    <img :src="link.user.illustrationUrl + '?w=80&h=80&fit=crop'" />-->
      <!--  </q-avatar>-->
      {{ link.user.username }}
    </div>

    <div
      v-if="showStatus"
      v-t="{ path: 'Status: {0}', args: [link.visibilityName] }"
    />

    <div
      v-if="editStatus"
    >
      <div class="p-float-label">
        <Dropdown
          v-model="link.visibility"
          :input-id="`link-${link.id}-status`"
          :options="visibilityOptions"
          option-label="label"
          option-value="value"
        />
        <label
          v-t="'Status'"
          :for="`link-${link.id}-status`"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { RESOURCE_LINK_PUBLISHED, RESOURCE_LINK_DRAFT } from "./visibility";
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
  item: {
    type: Object,
    required: true,
    default: () => ({
      resourceLinkListFromEntity: [],
    })
  },
  showStatus: {
    type: Boolean,
    required: false,
    default: true
  },
  editStatus: {
    type: Boolean,
    required: false,
    default: false
  }
});

const visibilityOptions = [
  { value: RESOURCE_LINK_PUBLISHED, label: t('Published') },
  { value: RESOURCE_LINK_DRAFT, label: t('Draft') },
];
</script>
