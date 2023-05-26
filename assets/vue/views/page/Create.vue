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
import { computed, ref, watch } from 'vue';
import { useStore } from 'vuex';

import PageForm from '../../components/page/Form.vue';
import Loading from '../../components/Loading.vue';

import { useDatatableCreate } from '../../composables/datatableCreate';
import { useToast } from 'primevue/usetoast';

const store = useStore();

const { createItem, onCreated } = useDatatableCreate('Page');

const toast = useToast();

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
  toast.add({
    severity: 'error',
    detail: newError,
    life: 3500,
  });
});
</script>
