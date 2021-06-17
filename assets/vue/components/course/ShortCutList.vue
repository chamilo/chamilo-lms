<template>
  <div
      class="bg-gray-100 rounded-xl p-2 shadow-md"
  >
    <div class="flex flex-col flex-center">
      <div class="mx-auto">
        <a :href="goToShortCut(shortcut)">
          <img
              :alt="shortcut.name"
              :src="'/img/tools/' + shortcut.type + '.png'"
              class="w-32 h-32 object-contain"
          />
        </a>
      </div>

      <div class="flex flex-row gap-2 text-gray-500 pt-3">
        <a

        >
          {{ shortcut.name }}
        </a>
      </div>
    </div>
  </div>
</template>

<script>

import {mapGetters} from "vuex";

export default {
  name: 'ShortCutList',
  props: {
    shortcut: Object,
    goToShortCut: {
      type: Function,
      required: true
    },
    changeVisibility: {
      type: Function,
      required: false
    },
  },
  computed: {
    ...mapGetters({
      'isCurrentTeacher': 'security/isCurrentTeacher',
    }),
  },
};
</script>
