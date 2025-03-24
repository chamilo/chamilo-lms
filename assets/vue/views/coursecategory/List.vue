<template>
  <div class="coursecategory-list">
    <Toolbar :handle-add="addHandler" />

    <v-container
      fluid
      grid-list-xl
    >
      <v-layout
        row
        wrap
      >
        <v-flex lg12>
          <DataFilter
            :handle-filter="onSendFilter"
            :handle-reset="resetFilter"
          >
            <template v-slot:filter>
              <CourseCategoryFilterForm
                ref="filterForm"
                :values="filters"
              />
            </template>
          </DataFilter>

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
import CourseCategoryFilterForm from "../../components/coursecategory/Filter.vue"
import DataFilter from "../../components/DataFilter.vue"
import Toolbar from "../../components/Toolbar.vue"

export default {
  name: "CourseCategoryList",
  servicePrefix: "CourseCategory",
  mixins: [ListMixin],
  components: {
    Toolbar,
    ActionCell,
    CourseCategoryFilterForm,
    DataFilter,
  },
  data() {
    return {
      headers: [
        { text: "title", value: "title" },
        { text: "code", value: "code" },
        //{ text: 'description', value: 'description' },
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
    ...mapGetters("coursecategory", {
      items: "list",
    }),
    ...mapFields("coursecategory", {
      deletedItem: "deleted",
      error: "error",
      isLoading: "isLoading",
      resetList: "resetList",
      totalItems: "totalItems",
      view: "view",
    }),
  },
  methods: {
    ...mapActions("coursecategory", {
      getPage: "fetchAll",
      deleteItem: "del",
    }),
  },
}
</script>
