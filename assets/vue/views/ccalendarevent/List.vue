<template>
  <div>
    <FullCalendar :options="calendarOptions" />

    <Loading :visible="isLoading" />

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
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn flat label="Add" @click="onSendForm" />
        </q-card-actions>
      </q-card>
    </q-dialog>

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
          <q-btn flat label="Close" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
import {mapActions, mapGetters, useStore} from 'vuex';
import { mapFields } from 'vuex-map-fields';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import {computed, ref, watch} from "vue";

//import '@fullcalendar/core/vdom' // solve problem with Vite
import FullCalendar, {EventClickArg} from '@fullcalendar/vue3';
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
          click: function() {
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
      eventClick: function (EventClickArg) {
        console.log(EventClickArg.event);

        item.value['title'] = EventClickArg.event.title;
        item.value['startDate'] = EventClickArg.event.startStr;
        item.value['endDate'] = EventClickArg.event.endStr;

        dialogShow.value = true;
      },
      dateClick: function(info) {
        item.value['allDay'] = info.allDay;
        item.value['startDate'] = info.dateStr;
        item.value['endDate'] = info.dateStr;
        item.value['parentResourceNodeId'] = currentUser.value.resourceNode['id'];
        dialog.value = true;
      },
      select: function(info) {
        item.value['allDay'] = info.allDay;
        item.value['startDate'] = info.startStr;
        item.value['endDate'] = info.endStr;
        item.value['parentResourceNodeId'] = currentUser.value.resourceNode['id'];
        dialog.value = true;
      },
      events: function(info, successCallback, failureCallback) {
        axios.get('/api/c_calendar_events',{
          params: {
            'startDate[after]': info.start.valueOf(),
            'endDate[before]': info.end.valueOf()
          }
        }).then(response => {
          let data = response.data;
          let events = data['hydra:member'];
          successCallback(
                Array.prototype.slice.call( // convert to array
                    events
                ).map(function(event) {
                  return {
                    title: event['title'],
                    start: event['startDate'],
                    end: event['endDate'],
                  }
                })
            )
          })
      },
    }

    return {calendarOptions, dialog, item, dialogShow};
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
