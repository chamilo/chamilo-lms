export default {
    path: '/admin',
    name: 'admin',
    meta: { requiresAuth: true },
    component: () => import('../components/admin/AdminLayout.vue'),
    children: [
        {
            path: '',
            name: 'AdminIndex',
            meta: { requiresAdmin: true, requiresSessionAdmin: true },
            component: () => import('../views/admin/AdminIndex.vue')
        },
    ],
};
