export default {
  path: '/resources/sessions',
  meta: { requiresAuth: true },
  name: 'sessions',
  component: () => import('../components/session/SessionCardList.vue'),
  redirect: { name: 'Sessions' },
  children: [
    {
      name: 'Sessions',
      path: '',
      component: () => import('../views/user/sessions/List.vue')
    }
  ]
};
