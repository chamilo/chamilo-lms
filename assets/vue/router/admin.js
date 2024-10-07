export default {
    path: '/admin',
    name: 'admin',
    meta: { requiresAuth: true, showBreadcrumb: true },
    component: () => import('../components/admin/AdminLayout.vue'),
    children: [
        {
            path: '',
            name: 'AdminIndex',
            meta: { requiresAdmin: true, requiresSessionAdmin: true, showBreadcrumb: false },
            component: () => import('../views/admin/AdminIndex.vue'),
        },
      {
        name: 'AdminConfigurationColors',
        path: 'configuration/colors',
        meta: { requiresAdmin: true, requiresSessionAdmin: true, showBreadcrumb: true },
        component: () => import('../views/admin/AdminConfigureColors.vue'),
      }
    ],
};

