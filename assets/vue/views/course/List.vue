<template>
  <div class="course-list">
    <Toolbar :handle-add="addHandler" />

    <v-container
      fluid
      grid-list-xl
    >
      <v-layout
        row
        wrap
      >
        <!--        <v-flex sm12>-->
        <!--          <h1>Course List</h1>-->
        <!--        </v-flex>-->
        <v-flex lg12>
          <br />

          <v-data-table
            v-model="selected"
            v-model:items-per-page="options.itemsPerPage"
            v-model:options="options"
            :headers="headers"
            :items="items"
            :loading="isLoading"
            :loading-text="$t('Loading')"
            :server-items-length="totalItems"
            class="elevation-1"
            item-key="@id"
            show-select
            @update:options="onUpdateOptions"
          >
            <template
              slot="item.visibility"
              slot-scope="{ item }"
            >
              {{ item }}
            </template>

            <template
              slot="item.expirationDate"
              slot-scope="{ item }"
            >
              {{ item }}
            </template>

            <ActionCell
              slot="item.action"
              slot-scope="props"
              :handle-delete="() => deleteHandler(props.item)"
              :handle-edit="() => editHandler(props.item)"
              :handle-show="() => showHandler(props.item)"
            ></ActionCell>
          </v-data-table>
        </v-flex>
      </v-layout>
    </v-container>
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { mapFields } from "vuex-map-fields"
import ListMixin from "../../mixins/ListMixin"
import ActionCell from "../../components/ActionCell.vue"
import DataFilter from "../../components/DataFilter.vue"
import Toolbar from "../../components/Toolbar.vue"

export default {
  name: "CourseList",
  servicePrefix: "Course",
  mixins: [ListMixin],
  components: {
    Toolbar,
    ActionCell,
    CourseFilterForm,
    DataFilter,
  },
  data() {
    return {
      headers: [
        { text: "title", value: "title" },
        { text: "code", value: "code" },
        { text: "courseLanguage", value: "Language" },
        { text: "visibility", value: "visibility" },
        {
          text: "Actions",
          value: "action",
          sortable: false,
        },
      ],
      selected: [],
    }
  },
  computed: {
    ...mapGetters("course", {
      items: "list",
    }),
    ...mapFields("course", {
      deletedItem: "deleted",
      error: "error",
      isLoading: "isLoading",
      resetList: "resetList",
      totalItems: "totalItems",
      view: "view",
    }),
  },
  methods: {
    ...mapActions("course", {
      getPage: "fetchAll",
      deleteItem: "del",
    }),
  },
}
</script>
