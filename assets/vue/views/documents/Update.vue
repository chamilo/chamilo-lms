<template>
  <div v-if="item && isCurrentTeacher">
    <DocumentsForm
      v-model="item"
      @submit="updateItemWithFormData"
    >
      <EditLinks
        :item="item"
        links-type="users"
        :show-status="false"
        :show-share-with-user="false"
      />
    </DocumentsForm>
    <Loading :visible="isLoading" />
  </div>
</template>

<script setup>
import { useStore } from 'vuex';
import DocumentsForm from '../../components/documents/Form.vue';
import Loading from '../../components/Loading.vue';
import EditLinks from "../../components/resource_links/EditLinks.vue";
import { useDatatableUpdate } from '../../composables/datatableUpdate';
import { computed } from 'vue';

const store = useStore();

const isCurrentTeacher = computed(() => store.getters['security/isCurrentTeacher']);

const {
  item,
  retrieve,
  updateItemWithFormData,
  isLoading,
} = useDatatableUpdate('Documents');

retrieve();
</script>
