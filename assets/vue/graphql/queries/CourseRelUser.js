import gql from 'graphql-tag';

export const GET_COURSE_REL_USER = gql`
    query getCourses($user: String!) {
        courseRelUsers(user: $user) {
            edges {
                node {
                    course {
                        _id,
                        title,
                        illustrationUrl
                        users(status: 1, first: 4) {
                            edges {
                                node {
                                    id
                                    status
                                    user {
                                        illustrationUrl,
                                        username,
                                        firstname,
                                        lastname
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

