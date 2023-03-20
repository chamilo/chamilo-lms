<template>
  <div>
    <PageForm
      v-model="item"
      @submit="createItem"
    />
    <Loading :visible="isLoading" />
  </div>
</template>

<script setup>
import { computed, inject, ref, watch } from 'vue';
import { useStore } from 'vuex';

import PageForm from '../../components/page/Form.vue';
import Loading from '../../components/Loading.vue';

import { useDatatableCreate } from '../../composables/datatableCreate';

const store = useStore();

const { createItem, onCreated } = useDatatableCreate('Page');

const flashMessageList = inject('flashMessageList');

const error = computed(() => store.state['page'].error);
const isLoading = computed(() => store.state['page'].isLoading);
const created = computed(() => store.state['page'].created);

const item = ref({
  enabled: true,
});

watch(created, (newCreated) => {
  if (!newCreated) {
    return;
  }

  onCreated(item);
});

watch(error, (newError) => {
  flashMessageList.value.push({
    severity: 'error',
    detail: newError,
  });
});
</script>
