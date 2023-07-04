export default {
  path: '/resources/links/:node/',
  meta: { requiresAuth: true, showBreadcrumb: true },
  name: 'links',
  component: () => import('../views/links/LinksLayout.vue'),
  redirect: { name: 'LinksList' },
  children: [
    {
      name: 'LinksList',
      path: '',
      component: () => import('../views/links/LinksList.vue')
    },
    {
      name: 'CreateLink',
      path: 'create',
      component: () => import('../views/links/LinksCreate.vue')
    },
    {
      name: 'UpdateLink',
      path: 'edit/:id',
      component: () => import('../views/links/LinksUpdate.vue')
    },
    {
      name: 'CreateLinkCategory',
      path: 'create_link_category',
      component: () => import('../views/links/LinksCategoryCreate.vue')
    },
    {
      name: 'UpdateLinkCategory',
      path: 'update_link_category/:id',
      component: () => import('../views/links/LinksCategoryUpdate.vue')
    },
  ]
};
