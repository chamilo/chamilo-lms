import gql from 'graphql-tag';

export const GET_STICKY_COURSES = gql`
    query getStickyCourses {
        courses (sticky: true){
            edges {
                node {
                    _id
                    title
                    illustrationUrl
                    sticky
                }
            }
        }
    }
`;

