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

const {
  item,
  isLoading,
  retrieve,
  updateItem
} = useDatatableUpdate('Page');

retrieve();
</script>
