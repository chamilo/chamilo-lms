export default {
    path: '/socialnetwork',
    meta: {requiresAuth: true},
    name: 'SocialNetwork',
    component: () => import('../views/socialnetwork/Layout.vue'),
    props: route => ({uid: route.query.id}),
    children: [
        {
            name: 'SocialNetworkWall',
            path: '',
            component: () => import('../views/socialnetwork/Wall.vue')
        },
    ]
}