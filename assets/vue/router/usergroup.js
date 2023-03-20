export default {
  path: '/resources/usergroups',
  meta: { requiresAuth: true },
  name: 'usergroups',
  component: () => import('../components/usergroup/Layout.vue'),
  redirect: { name: 'UserGroupList' },
  children: [
    {
      name: 'UserGroupList',
      path: '',
      component: () => import('../views/usergroup/List.vue')
    },
    {
      name: 'UserGroupShow',
      //path: ':id',
      path: 'show',
      component: () => import('../views/usergroup/Show.vue')
    }
  ]
};
