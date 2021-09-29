export default {
  path: '/resources/toolintro',
  meta: { requiresAuth: true },
  name: 'toolintro',
  component: () => import('../components/toolintro/Layout.vue'),
  redirect: { name: 'ToolIntroShow' },
  children: [
    {
      name: 'ToolIntroCreate',
      path: 'new',
      component: () => import('../views/toolintro/Create.vue')
    },
    {
      name: 'ToolIntroUpdate',
      path: ':id/edit',
      component: () => import('../views/toolintro/Update.vue')
    },
    {
      name: 'ToolIntroShow',
      path: '',
      component: () => import('../views/toolintro/Show.vue')
    }
  ]
};
