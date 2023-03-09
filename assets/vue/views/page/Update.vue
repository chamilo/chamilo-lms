<template>
  <PageForm
    v-model="item"
    @submit="updateItem"
  />
  <Loading :visible="isLoading" />
</template>

<script setup>
import Loading from '../../components/Loading.vue';
import PageForm from '../../components/page/Form.vue';
import { useDatatableUpdate } from '../../composables/datatableUpdate';
import { ref, watch } from 'vue';

const item = ref({});

const {
  isLoading,
  retrieve,
  retrievedItem,
  updateItem,
  updated,
  onUpdated
} = useDatatableUpdate('Page');

retrieve();

watch(
  retrievedItem,
  (newValue) => {
    item.value = newValue;
  }
);

watch(
  updated,
  (newValue) => {
    if (!newValue) {
      return;
    }

    onUpdated(item);
  }
);
</script>
