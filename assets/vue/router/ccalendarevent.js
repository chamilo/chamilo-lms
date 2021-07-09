export default {
  path: '/resources/ccalendarevent',
  meta: { requiresAuth: true },
  name: 'ccalendarevent',
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
    }
  ]
};
