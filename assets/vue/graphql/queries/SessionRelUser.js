import gql from 'graphql-tag';

export const GET_SESSION_REL_USER = gql`
    query getSessions($user: String!, $afterStartDate: String, $beforeEndDate: String) {
        sessionRelUsers(
            user: $user
            session_displayStartDate: {after: $afterStartDate}
            session_displayEndDate: {before: $beforeEndDate}
        ) {
            edges {
                node {
                    session {
                        _id
                        name
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
        sessionRelCourseRelUsers(
            user: $user
            session_displayStartDate: {after: $afterStartDate}
            session_displayEndDate: {before: $beforeEndDate}
        ) {
            edges {
                node {
                    session {
                        _id
                        name
                        displayStartDate
                        displayEndDate
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

