export default {
  path: '/resources/friends',
  meta: { requiresAuth: true },
  name: 'friends',
  component: () => import('../components/userreluser/Layout.vue'),
  redirect: { name: 'UserGroupList' },
  children: [
    {
      name: 'UserRelUserList',
      path: '',
      component: () => import('../views/userreluser/UserRelUserList.vue')
    },
    {
      name: 'UserRelUserAdd',
      //path: ':id',
      path: 'add',
      component: () => import('../views/userreluser/UserRelUserAdd.vue')
    }
  ]
};
