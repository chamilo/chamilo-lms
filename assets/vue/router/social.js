export default {
    path: '/social',
    meta: {requiresAuth: true},
    name: 'Social',
    component: () => import('../views/social/SocialLayout.vue'),
    children: [
        {
            name: 'SocialWall',
            path: '',
            component: () => import('../views/social/SocialWall.vue')
        },
    ]
}
