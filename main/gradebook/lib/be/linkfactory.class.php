<?php
/* For licensing terms, see /license.txt */

/**
 * Class LinkFactory
 * Factory for link objects.
 *
 * @author Bert Steppé
 */
class LinkFactory
{
    /**
     * Retrieve links and return them as an array of extensions of AbstractLink.
     *
     * @param int    $id          link id
     * @param int    $type        link type
     * @param int    $ref_id      reference id
     * @param int    $user_id     user id (link owner)
     * @param string $course_code course code
     * @param int    $category_id parent category
     * @param int    $visible     visible
     *
     * @return array
     */
    public static function load(
        $id = null,
        $type = null,
        $ref_id = null,
        $user_id = null,
        $course_code = null,
        $category_id = null,
        $visible = null
    ) {
        return AbstractLink::load(
            $id,
            $type,
            $ref_id,
            $user_id,
            $course_code,
            $category_id,
            $visible
        );
    }

    /**
     * Get the link object referring to an evaluation.
     */
    public function get_evaluation_link($eval_id)
    {
        $links = AbstractLink::load(null, null, $eval_id);
        foreach ($links as $link) {
            if (is_a($link, 'EvalLink')) {
                return $link;
            }
        }

        return null;
    }

    /**
     * Find links by name.
     *
     * @param string $name_mask search string
     *
     * @return array link objects matching the search criterium
     */
    public function find_links($name_mask, $selectcat)
    {
        return AbstractLink::find_links($name_mask, $selectcat);
    }

    /**
     * Static method to create specific link objects.
     *
     * @param int $type link type
     */
    public static function create($type)
    {
        $type = (int) $type;
        switch ($type) {
            case LINK_EXERCISE:
                return new ExerciseLink();
            case LINK_HOTPOTATOES:
                return new ExerciseLink(1);
            case LINK_DROPBOX:
                return new DropboxLink();
            case LINK_STUDENTPUBLICATION:
                return new StudentPublicationLink();
            case LINK_LEARNPATH:
                return new LearnpathLink();
            case LINK_FORUM_THREAD:
                return new ForumThreadLink();
            case LINK_ATTENDANCE:
                return new AttendanceLink();
            case LINK_SURVEY:
                return new SurveyLink();
            case LINK_PORTFOLIO:
                return new PortfolioLink();
        }

        return null;
    }

    /**
     * Return an array of all known link types.
     *
     * @return array
     */
    public static function get_all_types()
    {
        $types = [
            LINK_EXERCISE,
            //LINK_DROPBOX,
            LINK_HOTPOTATOES,
            LINK_STUDENTPUBLICATION,
            LINK_LEARNPATH,
            LINK_FORUM_THREAD,
            LINK_ATTENDANCE,
            LINK_SURVEY,
        ];

        if (api_get_configuration_value('allow_portfolio_tool')) {
            $types[] = LINK_PORTFOLIO;
        }

        return $types;
    }

    public function delete()
    {
    }
}
