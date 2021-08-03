<template>
  <div>
    <FullCalendar ref="cal" :options="calendarOptions"/>

    <Loading :visible="isLoading"/>

    <!-- Add form-->
    <q-dialog v-model="dialog" persistent>
      <q-card style="min-width: 500px">
        <q-card-section>
          <div class="text-h6">{{ item['@id'] ? 'Edit event' : 'Add event' }}</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <!--          <q-input dense v-model="address" autofocus @keyup.enter="dialog = false" />-->

          <!--              :errors="violations"-->
          <CCalendarEventForm
              v-if="dialog"
              ref="createForm"
              :values="item"
          >
          </CCalendarEventForm>
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn v-close-popup flat label="Cancel"/>
          <q-btn :label="item['@id'] ? 'Edit' : 'Add'" flat @click="onCreateEventForm"/>
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Show form-->
    <q-dialog v-model="dialogShow" persistent>
      <q-card style="min-width: 500px">
        <q-card-section>
          <CCalendarEventInfo :event="item" />
        </q-card-section>
        <q-card-actions align="right" class="text-primary">
          <q-btn color="primary" flat label="Delete" @click="confirmDelete"/>
          <q-btn v-if="isEventEditable" color="primary" flat label="Edit" @click="dialog = true"/>
          <q-btn v-close-popup flat label="Close"/>
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
import {mapActions, mapGetters, useStore} from 'vuex';
import {mapFields} from 'vuex-map-fields';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import {computed, ref} from "vue";

//import '@fullcalendar/core/vdom' // solve problem with Vite
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import axios from "axios";
import CCalendarEventForm from "../../components/ccalendarevent/Form.vue";
import CreateMixin from "../../mixins/CreateMixin";
import {useRoute, useRouter} from "vue-router";
import {useQuasar} from 'quasar';
import CCalendarEventInfo from "../../components/ccalendarevent/Info";

const servicePrefix = 'CCalendarEvent';

export default {
  name: 'CCalendarEventList',
  components: {
    CCalendarEventInfo,
    CCalendarEventForm,
    Loading,
    Toolbar,
    FullCalendar
  },

  mixins: [CreateMixin],
  //mixins: [ShowMixin],
  setup(props) {
    const $q = useQuasar();

    const calendarOptions = ref([]);
    const item = ref({});
    const dialog = ref(false);
    const dialogShow = ref(false);
    const isEventEditable = ref(false);

    const store = useStore();
    const route = useRoute();
    const router = useRouter();
    const currentUser = computed(() => store.getters['security/getUser']);

    let currentEvent = null;

    async function getCalendarEvents({startStr, endStr}) {
      const calendarEvents = await axios.get('/api/c_calendar_events', {
        params: {
          'startDate[after]': startStr,
          'endDate[before]': endStr
        }
      });

      return calendarEvents.data['hydra:member'];
    }

    async function getSessions({startStr, endStr}) {
      const sessions = await axios.get(`/api/users/${currentUser.value['id']}/sessions_rel_users`, {
        params: {
          'displayStartDate[after]': startStr,
          'displayEndDate[before]': endStr
        }
      });

      return sessions.data['hydra:member'];
    }

    calendarOptions.value = {
      plugins: [
        dayGridPlugin,
        timeGridPlugin,
        interactionPlugin
      ],
      customButtons: {
        addEvent: {
          text: 'Add event',
          click: function () {
            item.value = {};
            item.value['parentResourceNodeId'] = currentUser.value.resourceNode['id'];
            item.value['collective'] = false;

            dialog.value = true;
          }
        }
      },
      headerToolbar: {
        left: 'prev,next today,addEvent',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      nowIndicator: true,
      initialView: 'dayGridMonth',
      startParam: "startDate[after]",
      endParam: 'endDate[before]',
      selectable: true,
      eventClick(EventClickArg) {
        let event = EventClickArg.event;

        if (event.extendedProps.type && event.extendedProps.type === 'session') {
          EventClickArg.jsEvent.preventDefault();

          return;
        }

        currentEvent = event;

        item.value = {...event.extendedProps};

        item.value['title'] = event.title;
        item.value['startDate'] = event.startStr;
        item.value['endDate'] = event.endStr;
        item.value['parentResourceNodeId'] = event.extendedProps.resourceNode.creator.id;

        isEventEditable.value = item.value['parentResourceNodeId'] === currentUser.value['id'];

        if (!isEventEditable.value
            && event.extendedProps.collective
            && event.extendedProps.resourceLinkListFromEntity
        ) {
          const resourceLink = event.extendedProps.resourceLinkListFromEntity.find(linkEntity => linkEntity.user.id === currentUser.value.id);

          if (resourceLink) {
            isEventEditable.value = true;
          }
        }

        dialogShow.value = true;
      },
      dateClick(info) {
        item.value = {};
        item.value['parentResourceNodeId'] = currentUser.value.resourceNode['id'];
        item.value['collective'] = false;
        item.value['allDay'] = info.allDay;
        item.value['startDate'] = info.startStr;
        item.value['endDate'] = info.endStr;

        dialog.value = true;
      },
      select(info) {
        item.value = {};
        item.value['parentResourceNodeId'] = currentUser.value.resourceNode['id'];
        item.value['collective'] = false;
        item.value['allDay'] = info.allDay;
        item.value['startDate'] = info.startStr;
        item.value['endDate'] = info.endStr;

        dialog.value = true;
      },
      events(info, successCallback, failureCallback) {
        Promise
            .all([getCalendarEvents(info), getSessions(info)])
            .then(values => {
              const calendarEvents = Array.prototype.slice.call(values[0])
                  .map(event => ({
                    ...event,
                    start: event.startDate,
                    end: event.endDate,
                  }));

              const sessionEvents = values[1].map(sessionRelUser => (
                  {
                    title: sessionRelUser.session.name,
                    start: sessionRelUser.session.displayStartDate,
                    end: sessionRelUser.session.displayEndDate,
                    type: 'session'
                  }
              ));

              const events = [...calendarEvents, ...sessionEvents];

              successCallback(events);
            });
      },
    }

    function reFetch() {
      let calendarApi = this.$refs.cal.getApi();
      calendarApi.refetchEvents();
    }

    function confirmDelete() {
      $q
          .dialog({
            title: 'Delete',
            message: 'Are you sure you want to delete this event?',
            persistent: true,
            cancel: true
          })
          .onOk(function () {
            if (item.value['parentResourceNodeId'] === currentUser.value['id']) {
              store.dispatch('ccalendarevent/del', item.value)
            } else {
              let filteredLinks = item.value['resourceLinkListFromEntity']
                  .filter(resourceLinkFromEntity => resourceLinkFromEntity['user']['id'] === currentUser.value['id']);

              if (filteredLinks.length > 0) {
                store.dispatch('resourcelink/del', {'@id': `/api/resource_links/${filteredLinks[0]['id']}`})

                currentEvent.remove();
                dialogShow.value = false;
              }
            }
          });
    }

    return {calendarOptions, dialog, item, dialogShow, reFetch, isEventEditable, confirmDelete};
  },
  computed: {
    ...mapFields('ccalendarevent', {
      isLoading: 'isLoading',
      created: 'created',
      violations: 'violations',
    }),
    ...mapGetters('ccalendarevent', ['find']),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
      'isCurrentTeacher': 'security/isCurrentTeacher',
    }),
  },
  methods: {
    onCreateEventForm() {
      const createForm = this.$refs.createForm;
      createForm.v$.$touch();
      if (!createForm.v$.$invalid) {
        let itemModel = createForm.v$.item.$model;

        if (itemModel['@id']) {
          this.updateItem(itemModel);
        } else {
          this.create(itemModel);
        }

        this.reFetch();
        this.dialog = false;
      }
    },
    ...mapActions('ccalendarevent', {
      create: 'create',
      deleteItem: 'del',
      reset: 'resetShow',
      retrieve: 'loadWithQuery',
      updateItem: 'update'
    }),
  },
  servicePrefix
};
</script>
