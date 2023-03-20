import gql from 'graphql-tag';

// The extension SessionRelUserExtension.php will be loaded.
export const GET_SESSION_REL_USER_CURRENT = gql`
    query getCurrentSessions($user: String!, $afterStartDate: String, $afterEndDate: String, $beforeStartDate: String, $beforeEndDate: String) {
        sessionRelUsers(
            user: $user
            session_accessStartDate: {after: $afterStartDate, before: $beforeStartDate}
            session_accessEndDate: {after: $afterEndDate, before: $beforeEndDate}
        ) {
            edges {
                node {
                    session {
                        _id
                        name
                        category {
                            _id
                            id
                            name
                        }
                        displayStartDate
                        displayEndDate
                        users(user: $user) {
                            edges {
                                node {
                                    user {
                                        id
                                    }
                                    relationType
                                }
                            }
                        }
                        courses {
                            edges {
                                node {
                                    course {
                                        _id
                                        title
                                        illustrationUrl
                                    }
                                }
                            }
                        }
                        sessionRelCourseRelUsers(user: $user) {
                            edges {
                                node {
                                    course {
                                        _id
                                        title
                                        illustrationUrl
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
`;

export const GET_SESSION_REL_USER = gql`
    query getSessions($user: String!, $afterStartDate: String, $afterEndDate: String, $beforeStartDate: String, $beforeEndDate: String) {
        sessionRelUsers(
            user: $user
            session_accessStartDate: {after: $afterStartDate, before: $beforeStartDate}
            session_accessEndDate: {after: $afterEndDate, before: $beforeEndDate}
        ) {
            edges {
                node {
                    session {
                        _id
                        name
                        category {
                            _id
                            id
                            name
                        }
                        displayStartDate
                        displayEndDate
                        users(user: $user) {
                            edges {
                                node {
                                    user {
                                        id
                                    }
                                    relationType
                                }
                            }
                        }
                        courses {
                            edges {
                                node {
                                    course {
                                        _id
                                        title
                                        illustrationUrl
                                    }
                                }
                            }
                        }
                        sessionRelCourseRelUsers(user: $user) {
                            edges {
                                node {
                                    course {
                                        _id
                                        title
                                        illustrationUrl
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
`;

