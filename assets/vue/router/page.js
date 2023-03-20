export default {
  path: '/resources/pages',
  meta: { requiresAuth: true },
  name: 'pages',
  component: () => import('../components/page/Layout.vue'),
  redirect: { name: 'PageList' },
  children: [
    {
      name: 'PageList',
      path: '',
      component: () => import('../views/page/List.vue')
    },
    {
      name: 'PageCreate',
      path: 'new',
      component: () => import('../views/page/Create.vue')
    },
    {
      name: 'PageUpdate',
      //path: ':id/edit',
      path: 'edit',
      component: () => import('../views/page/Update.vue')
    },
    {
      name: 'PageShow',
      //path: ':id',
      path: 'show',
      component: () => import('../views/page/Show.vue')
    }
  ]
};
