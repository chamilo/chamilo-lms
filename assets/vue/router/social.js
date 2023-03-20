export default {
    path: '/social',
    meta: {requiresAuth: true},
    name: 'Social',
    component: () => import('../views/social/Layout.vue'),
    children: [
        {
            name: 'SocialWall',
            path: '',
            component: () => import('../views/social/Wall.vue')
        },
    ]
}