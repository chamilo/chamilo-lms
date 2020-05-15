<template>
  <div>
    <v-row justify="space-around">
      <v-icon v-if="handleShow" small class="mr-2" @click="handleShow">mdi-eye</v-icon>
      <v-icon v-if="handleEdit" small class="mr-2" @click="handleEdit">mdi-pencil</v-icon>
      <v-icon v-if="handleDelete" small @click="confirmDelete = true">mdi-delete</v-icon>
    </v-row>
    <ConfirmDelete
      v-if="handleDelete"
      :visible="confirmDelete"
      :handle-delete="handleDelete"
      @close="confirmDelete = false"
    />
  </div>
</template>

<script>
import ConfirmDelete from './ConfirmDelete';

export default {
  name: 'ActionCell',
  components: {
    ConfirmDelete
  },
  data() {
    return {
      confirmDelete: false
    };
  },
  props: {
    handleShow: {
      type: Function,
      required: false
    },
    handleEdit: {
      type: Function,
      required: false
    },
    handleDelete: {
      type: Function,
      required: false
    }
  }
};
</script>
