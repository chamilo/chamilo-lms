<template>
  <div>
    <Toolbar
      v-if="item && isCurrentTeacher"
      :handle-edit="editHandler"
      :handle-delete="del"
    >
      <template slot="left">
<!--        <v-toolbar-title v-if="item">-->
<!--          {{-->
<!--            `${$options.servicePrefix} ${item['@id']}`-->
<!--          }}-->
<!--        </v-toolbar-title>-->
      </template>
    </Toolbar>

    <p class="text-lg" v-if="item">
      {{ item['title'] }}
    </p>

    <div v-if="item" class="flex flex-row">
      <div class="w-1/2">
        <div class ="flex justify-center" v-if="item['resourceNode']['resourceFile']">
          <div class="w-64">
            <q-img
                spinner-color="primary"
                v-if="item['resourceNode']['resourceFile']['image']"
                :src="item['contentUrl'] + '&w=300'"
            />
            <span v-else-if="item['resourceNode']['resourceFile']['video']">
              <video controls>
                <source :src="item['contentUrl']" />
              </video>
            </span>
            <span v-else>
                <q-btn
                    class="btn btn-primary"
                    :to="item['downloadUrl']"
                >
                  <v-icon icon="mdi-file-download"/>
                  {{ $t('Download file') }}
                </q-btn>
              </span>
          </div>
        </div>
        <div class ="flex justify-center" v-else>
          <v-icon icon="mdi-folder"/>
        </div>
      </div>

      <span class="w-1/2">
        <q-markup-table>
          <tbody>
          <tr>
            <td><strong>{{ $t('Author') }}</strong></td>
            <td>
              {{ item['resourceNode'].creator.username }}
            </td>
            <td></td>
            <td />
          </tr>
          <tr>
            <td><strong>{{ $t('Comment') }}</strong></td>
            <td>
              {{ item['comment'] }}
            </td>
          </tr>
          <tr>
            <td><strong>{{ $t('Created at') }}</strong></td>
            <td>
              {{ item['resourceNode'] ? $luxonDateTime.fromISO(item['resourceNode'].createdAt).toRelative() : ''}}
            </td>
            <td />
          </tr>
          <tr>
            <td><strong>{{ $t('Updated at') }}</strong></td>
            <td>
              {{ item['resourceNode'] ? $luxonDateTime.fromISO(item['resourceNode'].updatedAt).toRelative() : ''}}
            </td>
            <td />
          </tr>
          <tr v-if="item['resourceNode']['resourceFile']">
            <td><strong>{{ $t('File') }}</strong></td>
            <td>
              <div>
                <a
                    class="btn btn-primary"
                    :href="item['downloadUrl']"
                >
                  <v-icon icon="mdi-file-download"/>
                  {{ $t('Download file') }}
                </a>
              </div>
            </td>
            <td />
          </tr>
          </tbody>
        </q-markup-table>

        <hr />

        <span v-if="item['resourceLinkListFromEntity']">
           <h2>{{ $t('Shared') }}</h2>
            <span
                v-for="link in item['resourceLinkListFromEntity']"
            >
             <q-markup-table>
              <tbody>
              <tr>
                 <td>
                   {{ $t('Status') }}
                 </td>
                 <td>
                   {{ link.visibilityName }}
                 </td>
              </tr>

              <tr v-if="link['course']">
                 <td>
                   {{ $t('Course') }}
                 </td>
                <td>
                  {{ link.course.resourceNode.title }}
                 </td>
              </tr>

              <tr v-if="link['session']">
                 <td>
                   {{ $t('Session') }}
                 </td>
                <td>
                 {{ link.session.name }}
                 </td>
              </tr>

              <tr v-if="link['group']">
                 <td>
                   {{ $t('Group') }}
                 </td>
                <td>
                 {{ link.group.resourceNode.title }}
                 </td>
              </tr>
              </tbody>
            </q-markup-table>
            </span>
        </span>
      </span>
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
const servicePrefix = 'PersonalFile';

export default {
  name: 'PersonalFileShow',
  components: {
      Loading,
      Toolbar
  },
  mixins: [ShowMixin],
  computed: {
    ...mapFields('personalfile', {
      isLoading: 'isLoading'
    }),
    ...mapGetters('personalfile', ['find']),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
      'isCurrentTeacher': 'security/isCurrentTeacher',
    }),
  },
  methods: {
    ...mapActions('personalfile', {
      deleteItem: 'del',
      reset: 'resetShow',
      retrieve: 'loadWithQuery'
    }),
  },
  servicePrefix
};
</script>
