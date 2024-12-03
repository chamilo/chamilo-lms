<template>
  <div>
    <Toolbar
      :handle-delete="del"
      :handle-list="list"
    >
      <template slot="left">
        <v-toolbar-title v-if="item">{{ `${$options.servicePrefix} ${item["@id"]}` }} </v-toolbar-title>
      </template>
    </Toolbar>
    <br />
    <div
      v-if="item"
      class="table-course-show"
    >
      <v-simple-table>
        <template slot="default">
          <table>
          <thead>
            <tr>
              <th>Field</th>
              <th>Value</th>
              <th>Field</th>
              <th>Value</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <strong>{{ $t("title") }}</strong>
              </td>
              <td>
                {{ item["title"] }}
              </td>

              <td>
                <strong>{{ $t("code") }}</strong>
              </td>
              <td>
                {{ item["code"] }}
              </td>
            </tr>

            <tr>
              <td>
                <strong>{{ $t("Course language") }}</strong>
              </td>
              <td>
                {{ item["courseLanguage"] }}
              </td>

              <td>
                <strong>{{ $t("category") }}</strong>
              </td>
              <td>
                <div v-if="item['category']">
                  {{ item["category"].title }}
                </div>
                <div v-else>-</div>
              </td>
            </tr>

            <tr>
              <td>
                <strong>{{ $t("visibility") }}</strong>
              </td>
              <td>
                {{ $n(item["visibility"]) }}
              </td>

              <td>
                <strong>{{ $t("Department") }}</strong>
              </td>
              <td>
                {{ item["departmentName"] }}
              </td>
            </tr>

            <tr>
              <td>
                <strong>{{ $t("Department URL") }}</strong>
              </td>
              <td>
                {{ item["departmentUrl"] }}
              </td>

              <td>
                <strong>{{ $t("Expiration date") }}</strong>
              </td>
              <td>
                {{ formatDateTime(item["expirationDate"], "long") }}
              </td>
            </tr>
          </tbody>
          </table>
        </template>
      </v-simple-table>
    </div>
    <Loading :visible="isLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { mapFields } from "vuex-map-fields"
import Loading from "../../components/Loading.vue"
import ShowMixin from "../../mixins/ShowMixin"
import Toolbar from "../../components/Toolbar.vue"

const servicePrefix = "Course"

export default {
  name: "CourseShow",
  servicePrefix,
  components: {
    Loading,
    Toolbar,
  },
  mixins: [ShowMixin],
  computed: {
    ...mapFields("course", {
      isLoading: "isLoading",
    }),
    ...mapGetters("course", ["find"]),
  },
  methods: {
    ...mapActions("course", {
      deleteItem: "del",
      reset: "resetShow",
      retrieve: "load",
    }),
  },
}
</script>
