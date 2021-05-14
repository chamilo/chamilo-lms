export default {
  path: '/account',
  meta: { requiresAuth: true },
  name: 'account',
  component: () => import('../components/course/Layout.vue'),
  children: [
    {
      name: 'AccountHome',
      path: 'home',
      component: () => import('../views/account/Home.vue'),
      meta: {requiresAuth: true},
    },
  ]
};
