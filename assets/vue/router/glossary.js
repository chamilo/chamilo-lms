export default {
  path: '/resources/glossary/:node/',
  meta: { requiresAuth: true, showBreadcrumb: true },
  name: 'glossary',
  component: () => import('../components/glossary/GlossaryLayout.vue'),
  redirect: { name: 'GlossaryList' },
  children: [
    {
      name: 'GlossaryList',
      path: '',
      component: () => import('../views/glossary/GlossaryList.vue')
    },
    {
      name: 'CreateTerm',
      path: 'create',
      component: () => import('../views/glossary/GlossaryTermCreate.vue')
    },
    {
      name: 'UpdateTerm',
      path: 'edit/:id',
      component: () => import('../views/glossary/GlossaryTermUpdate.vue')
    },
    {
      name: 'ImportGlossary',
      path: '',
      component: () => import('../views/glossary/GlossaryImport.vue')
    },
    {
      name: 'ExportGlossary',
      path: '',
      component: () => import('../views/glossary/GlossaryExport.vue')
    },
  ]
};
