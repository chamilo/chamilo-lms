<template>
  <div>
    <FullCalendar ref="cal" :options="calendarOptions"/>

    <Loading :visible="isLoading"/>

    <!-- Add form-->
    <q-dialog v-model="dialog" persistent>
      <q-card style="min-width: 500px">
        <q-card-section>
          <div class="text-h6">Add event</div>
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
          <q-btn flat label="Add" @click="onCreateEventForm"/>
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Show form-->
    <q-dialog v-model="dialogShow" persistent>
      <q-card style="min-width: 500px">
        <q-card-section class="q-pt-none">
          <h3>{{ item.title }}</h3>
          <p>
            {{ item.startDate }}
          </p>
          <p>{{ item.endDate }}</p>
        </q-card-section>
        <q-card-actions align="right" class="text-primary">
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

const servicePrefix = 'CCalendarEvent';

export default {
  name: 'CCalendarEventList',
  components: {
    CCalendarEventForm,
    Loading,
    Toolbar,
    FullCalendar
  },

  mixins: [CreateMixin],
  //mixins: [ShowMixin],
  setup(props) {
    const calendarOptions = ref([]);
    const item = ref({});
    const dialog = ref(false);
    const dialogShow = ref(false);

    const store = useStore();
    const route = useRoute();
    const router = useRouter();
    const currentUser = computed(() => store.getters['security/getUser']);

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
            item.value['parentResourceNodeId'] = currentUser.value.resourceNode['id'];

            if (!!!item.value['collective']) {
              item.value['collective'] = false;
            }

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

        item.value['title'] = event.title;
        item.value['startDate'] = event.startStr;
        item.value['endDate'] = event.endStr;
        item.value['collective'] = event.extendedProps.collective;

        dialogShow.value = true;
      },
      dateClick(info) {
        item.value['allDay'] = info.allDay;
        item.value['startDate'] = info.dateStr;
        item.value['endDate'] = info.dateStr;
        item.value['parentResourceNodeId'] = currentUser.value.resourceNode['id'];
        item.value['collective'] = false;
        dialog.value = true;
      },
      select(info) {
        item.value['allDay'] = info.allDay;
        item.value['startDate'] = info.startStr;
        item.value['endDate'] = info.endStr;
        item.value['parentResourceNodeId'] = currentUser.value.resourceNode['id'];
        item.value['collective'] = false;
        dialog.value = true;
      },
      events(info, successCallback, failureCallback) {
        axios.get('/api/c_calendar_events', {
          params: {
            'startDate[after]': info.startStr,
            'endDate[before]': info.endStr
          }
        }).then(response => {
          let data = response.data;
          let events = data['hydra:member'];
          successCallback(
              Array.prototype.slice.call(events)
                  .map(event => ({
                    title: event.title,
                    start: event.startDate,
                    end: event.endDate,
                    collective: event.collective
                  }))
          )
        })
      },
    }

    function reFetch() {
      let calendarApi = this.$refs.cal.getApi();
      calendarApi.refetchEvents();
    }

    return {calendarOptions, dialog, item, dialogShow, reFetch};
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
        this.create(createForm.v$.item.$model);
        this.reFetch();
        this.dialog = false;
      }
    },
    ...mapActions('ccalendarevent', {
      create: 'create',
      deleteItem: 'del',
      reset: 'resetShow',
      retrieve: 'loadWithQuery'
    }),
  },
  servicePrefix
};
</script>
