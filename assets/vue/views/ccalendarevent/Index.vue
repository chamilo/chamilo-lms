<template>
  <div>
    <Toolbar
      v-if="item"
      :handle-edit="editHandler"
      :handle-delete="del"
    >
    </Toolbar>


    <FullCalendar :options="calendarOptions" />

    <Loading :visible="isLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import {ref} from "vue";

//import '@fullcalendar/core/vdom' // solve problem with Vite
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import axios from "axios";

const servicePrefix = 'CCalendarEvent';

export default {
  name: 'CCalendarEventIndex',
  components: {
      Loading,
      Toolbar,
      FullCalendar
  },
  //mixins: [ShowMixin],
  setup() {
    const calendarOptions = ref([]);

    const events = [];

    calendarOptions.value = {
      plugins: [
        dayGridPlugin,
        timeGridPlugin,
        interactionPlugin
      ],
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      nowIndicator: true,
      initialView: 'dayGridMonth',
      //events: '/api/c_calendar_events',
      startParam: "startDate[after]",
      endParam: 'endDate[before]',
      events: function(info, successCallback, failureCallback) {
        axios.get('/api/c_calendar_events',{
          params: {
            'startDate[after]': info.start.valueOf(),
            'endDate[after]': info.end.valueOf()
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
      /*eventSourceSuccess: function(content, xhr) {
        console.log('aaa');
        console.log(content['hydra:member']);

        return content['hydra:member'];
      }*/
    }

    return {calendarOptions};
  },
  computed: {
    ...mapFields('ccalendarevent', {
      isLoading: 'isLoading'
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
      deleteItem: 'del',
      reset: 'resetShow',
      retrieve: 'loadWithQuery'
    }),
  },
  servicePrefix
};
</script>
