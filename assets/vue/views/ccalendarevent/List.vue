<template>
  <div>
    <FullCalendar
      ref="cal"
      :options="calendarOptions"
    />

    <Loading :visible="isLoading" />

    <!-- Add form-->
    <Dialog
      v-model:visible="dialog"
      :header="item['@id'] ? t('Edit event') : t('Add event')"
      :modal="true"
    >
      <CCalendarEventForm
        v-if="dialog"
        ref="createForm"
        :values="item"
      />
      <template #footer>
        <Button
          :label="t('Cancel')"
          class="p-button-outlined p-button-plain"
          icon="pi pi-times"
          @click="dialog = false"
        />
        <Button
          :label="item['@id'] ? t('Edit') : t('Add')"
          class="p-button-secondary"
          @click="onCreateEventForm"
        />
      </template>
    </Dialog>

    <!-- Show form-->
    <Dialog
      v-model:visible="dialogShow"
      :header="t('Event')"
      :modal="true"
    >
      <CCalendarEventInfo :event="item" />

      <template #footer>
        <Button
          :label="t('Cancel')"
          class="p-button-outlined p-button-plain"
          icon="pi pi-times"
          @click="dialogShow = false"
        />
        <Button
          :label="t('Delete')"
          class="p-button-outlined p-button-danger"
          icon="pi pi-trash"
          @click="confirmDelete"
        />
        <Button
          v-if="isEventEditable"
          :label="t('Edit')"
          class="p-button-secondary"
          @click="dialog = true"
        />
      </template>
    </Dialog>

    <!-- Show form-->
    <Dialog
      v-model:visible="sessionState.showSessionDialog"
      :header="t('Session')"
      :modal="true"
    >
      <div class="flex flex-col gap-4">
        <h5>{{ sessionState.sessionAsEvent.title }}</h5>
        <p
          v-if="sessionState.sessionAsEvent.start"
          v-t="{ path: 'From: {date}', args: { 'date': useAbbreviatedDatetime(sessionState.sessionAsEvent.start) } }"
        />
        <p
          v-if="sessionState.sessionAsEvent.end"
          v-t="{ path: 'Until: {date}', args: { 'date': useAbbreviatedDatetime(sessionState.sessionAsEvent.end) } }"
        />
      </div>

      <template #footer>
        <a
          v-t="'Go to session'"
          :href="`/sessions/${sessionState.sessionAsEvent.id}/about`"
          class="btn btn--secondary"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, inject, reactive, ref, watch } from 'vue';
import { useStore } from 'vuex';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useConfirm } from 'primevue/useconfirm';
import { useAbbreviatedDatetime } from '../../composables/formatDate.js';

import Loading from '../../components/Loading.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import CCalendarEventForm from '../../components/ccalendarevent/Form.vue';
import CCalendarEventInfo from '../../components/ccalendarevent/Info';
import { ENTRYPOINT } from '../../config/entrypoint';
import allLocales from '@fullcalendar/core/locales-all';
import toInteger from 'lodash/toInteger';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const store = useStore();
const route = useRoute();
const confirm = useConfirm();

const item = ref({});
const dialog = ref(false);
const dialogShow = ref(false);
const isEventEditable = ref(false);

const currentUser = computed(() => store.getters['security/getUser']);
const { t, locale } = useI18n();

let currentEvent = null;

const sessionState = reactive({
  sessionAsEvent: {
    id: '',
    title: '',
    start: '',
    end: '',
    extendedProps: {},
  },
  showSessionDialog: false
});

const cid = toInteger(route.query.cid);
const sid = toInteger(route.query.sid);
const gid = toInteger(route.query.gid);

if (cid) {
  let courseIri = '/api/courses/' + cid;
  store.dispatch('course/findCourse', { id: courseIri });
}

async function getCalendarEvents ({ startStr, endStr }) {
  const calendarEvents = await axios.get(
    ENTRYPOINT + 'c_calendar_events',
    {
      params: {
        'startDate': startStr,
        'endDate': endStr,
        //          'startDate[after]': startStr,
        //          'startDate[before]': startStr,
        //'startDate[between]': startStr+'..'+endStr,
        'cid': cid,
        'sid': sid,
        'gid': gid,
      }
    }
  );

  return calendarEvents.data['hydra:member'].map(event => ({
      ...event,
      start: event.startDate,
      end: event.endDate,
    })
  );
}

async function getSessions ({ startStr, endStr }) {
  if ('true' !== window.config['agenda.personal_calendar_show_sessions_occupation']) {
    return [];
  }

  const sessions = await axios.get(
    ENTRYPOINT + `session_rel_users`,
    {
      params: {
        'user': currentUser.value['@id'],
        'displayStartDate[after]': startStr,
        'displayEndDate[before]': endStr,
        'relationType': 3
      }
    }
  );

  return sessions.data['hydra:member'].map(sessionRelUser => ({
      ...sessionRelUser.session,
      title: sessionRelUser.session.name,
      start: sessionRelUser.session.displayStartDate,
      end: sessionRelUser.session.displayEndDate,
    })
  );
}

// @todo fix locale connection between fullcalendar + chamilo

if ('en_US' === locale.value) {
  locale.value = 'en';
}

if ('fr_FR' === locale.value) {
  locale.value = 'fr';
}

if ('pl_PL' === locale.value) {
  locale.value = 'pl';
}

const calendarOptions = ref({
  plugins: [
    dayGridPlugin,
    timeGridPlugin,
    interactionPlugin
  ],
  locales: allLocales,
  locale: locale.value,
  customButtons: {
    addEvent: {
      text: t('Add event'),
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
  startParam: 'startDate[after]',
  endParam: 'endDate[before]',
  selectable: true,
  eventClick (EventClickArg) {
    let event = EventClickArg.event.toPlainObject();

    if (event.extendedProps['@type'] && event.extendedProps['@type'] === 'Session') {
      sessionState.sessionAsEvent = event;
      sessionState.showSessionDialog = true;
      EventClickArg.jsEvent.preventDefault();

      return;
    }

    currentEvent = event;

    item.value = { ...event.extendedProps };

    item.value['title'] = event.title;
    item.value['startDate'] = event.start;
    item.value['endDate'] = event.end;
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
  dateClick (info) {
    item.value = {};
    item.value['parentResourceNodeId'] = currentUser.value.resourceNode['id'];
    item.value['collective'] = false;
    item.value['allDay'] = info.allDay;
    item.value['startDate'] = info.startStr;
    item.value['endDate'] = info.endStr;

    dialog.value = true;
  },
  select (info) {
    item.value = {};
    item.value['parentResourceNodeId'] = currentUser.value.resourceNode['id'];
    item.value['collective'] = false;
    item.value['allDay'] = info.allDay;
    item.value['startDate'] = info.startStr;
    item.value['endDate'] = info.endStr;

    dialog.value = true;
  },
  events (info, successCallback) {
    Promise
      .all([getCalendarEvents(info), getSessions(info)])
      .then(values => {
        const events = values[0].concat(values[1]);

        successCallback(events);
      });
  },
});

const cal = ref(null);

function reFetch () {
  cal.value.getApi().refetchEvents();
}

function confirmDelete () {
  confirm.require({
    message: t('Are you sure you want to delete this event?'),
    header: t('Delete'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    rejectClass: 'p-button-plain p-button-outlined',
    accept () {
      if (item.value['parentResourceNodeId'] === currentUser.value['id']) {
        store.dispatch('ccalendarevent/del', item.value);

        dialogShow.value = false;
        dialog.value = false;
        reFetch();
      } else {
        let filteredLinks = item.value['resourceLinkListFromEntity']
          .filter(resourceLinkFromEntity => resourceLinkFromEntity['user']['id'] === currentUser.value['id']);

        if (filteredLinks.length > 0) {
          store.dispatch('resourcelink/del', { '@id': `/api/resource_links/${filteredLinks[0]['id']}` });

          currentEvent.remove();
          dialogShow.value = false;
          dialog.value = false;
          reFetch();
        }
      }
    },
  });
}

const isLoading = computed(() => store.getters['ccalendarevent/isLoading']);

const createForm = ref(null);

function onCreateEventForm () {
  if (createForm.value.v$.$invalid) {
    return;
  }

  let itemModel = createForm.value.v$.item.$model;

  if (itemModel['@id']) {
    store.dispatch('ccalendarevent/update', itemModel);
  } else {
    store.dispatch('ccalendarevent/create', itemModel);

  }

  dialog.value = false;
}

const flashMessageList = inject('flashMessageList');

watch(
  () => store.state.ccalendarevent.created,
  (created) => {
    flashMessageList.value.push({
      severity: 'success',
      detail: t(
        '{resource} created',
        { 'resource': created.resourceNode.title }
      ),
    });

    reFetch();
  }
);

watch(
  () => store.state.ccalendarevent.updated,
  (updated) => {
    flashMessageList.value.push({
      severity: 'success',
      detail: t(
        '{resource} updated',
        { 'resource': updated.resourceNode.title }
      ),
    });

    reFetch();
  }
);
</script>
