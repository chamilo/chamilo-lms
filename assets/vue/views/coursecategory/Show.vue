<template>
  <div>
    <Toolbar :handle-edit="editHandler"  :handle-delete="del">
      <template slot="left">
        <v-toolbar-title v-if="item">{{
          `${$options.servicePrefix} ${item['@id']}`
        }}</v-toolbar-title>
      </template>
    </Toolbar>

    <br />

    <div v-if="item" class="table-coursecategory-show">
      <v-simple-table>
        <template slot="default">
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
              <td><strong>{{ $t('name') }}</strong></td>
              <td>
                                    {{ item['name'] }}
              </td>
            
              <td><strong>{{ $t('code') }}</strong></td>
              <td>
                                    {{ item['code'] }}
              </td>
            </tr>
            
            <tr>
              <td><strong>{{ $t('description') }}</strong></td>
              <td>
                                    {{ item['description'] }}
              </td>
            
              <td></td>
            </tr>
            
          </tbody>
        </template>
      </v-simple-table>
    </div>

    <Loading :visible="isLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import Loading from '../../components/Loading.vue';
import ShowMixin from '../../mixins/ShowMixin';
import Toolbar from '../../components/Toolbar.vue';

const servicePrefix = 'CourseCategory';

export default {
  name: 'CourseCategoryShow',
  servicePrefix,
  components: {
      Loading,
      Toolbar
  },
  mixins: [ShowMixin],
  computed: {
    ...mapFields('coursecategory', {
      isLoading: 'isLoading'
    }),
    ...mapGetters('coursecategory', ['find'])
  },
  methods: {
    ...mapActions('coursecategory', {
      deleteItem: 'del',
      reset: 'resetShow',
      retrieve: 'load'
    })
  }
};
</script>
