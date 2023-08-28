export default {
  path: '/resources/ccalendarevent',
  meta: { requiresAuth: true },
  name: 'ccalendarevent',
  redirect: { name: 'CCalendarEventList' },
  component: () => import('../components/ccalendarevent/CCalendarEventLayout.vue'),
  children: [
    {
      name: 'CCalendarEventShow',
      path: 'show',
      component: () => import('../views/ccalendarevent/CCalendarEventShow.vue')
    },
    {
      name: 'CCalendarEventCreate',
      path: 'new',
      component: () => import('../views/ccalendarevent/CCalendarEventCreate.vue')
    },
    {
      name: 'CCalendarEventList',
      path: '',
      component: () => import('../views/ccalendarevent/CCalendarEventList.vue')
    }
  ]
};
