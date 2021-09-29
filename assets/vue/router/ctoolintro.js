export default {
  path: '/resources/ctoolintro',
  meta: { requiresAuth: true },
  name: 'ctoolintro',
  component: () => import('../components/ctoolintro/Layout.vue'),
  redirect: { name: 'ToolIntroShow' },
  children: [
    {
      name: 'ToolIntroCreate',
      path: 'new',
      component: () => import('../views/ctoolintro/Create.vue')
    },
    {
      name: 'ToolIntroUpdate',
      path: ':id/edit',
      component: () => import('../views/ctoolintro/Update.vue')
    },
    {
      name: 'ToolIntroShow',
      path: '',
      component: () => import('../views/ctoolintro/Show.vue')
    }
  ]
};
