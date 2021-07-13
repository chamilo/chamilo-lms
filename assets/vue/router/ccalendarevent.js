export default {
  path: '/resources/ccalendarevent',
  meta: { requiresAuth: true },
  name: 'ccalendarevent',
  redirect: { name: 'CCalendarEventList' },
  component: () => import('../components/ccalendarevent/Layout.vue'),
  children: [
    {
      name: 'CCalendarEventShow',
      path: 'show',
      component: () => import('../views/ccalendarevent/Show.vue')
    },
    {
      name: 'CCalendarEventCreate',
      path: 'new',
      component: () => import('../views/ccalendarevent/Create.vue')
    },
    {
      name: 'CCalendarEventList',
      path: '',
      component: () => import('../views/ccalendarevent/List.vue')
    }
  ]
};
