export default {
  path: '/resources/links/:node/',
  meta: { requiresAuth: true, showBreadcrumb: true },
  name: 'links',
  component: () => import('../components/links/LinksLayout.vue'),
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
      component: () => import('../views/links/CreateLink.vue')
    },
    {
      name: 'UpdateLink',
      path: 'edit/:id',
      component: () => import('../views/links/UpdateLink.vue')
    },
    {
      name: 'CreateLinkCategory',
      path: 'create_link_category',
      component: () => import('../views/links/CreateLinkCategory.vue')
    },
    {
      name: 'UpdateLinkCategory',
      path: 'update_link_category/:id',
      component: () => import('../views/links/UpdateLinkCategory.vue')
    },
  ]
};
