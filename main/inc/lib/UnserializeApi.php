<?php
/* For licensing terms, see /license.txt */

use Brumann\Polyfill\Unserialize;

/**
 * Class UnserializeApi.
 */
class UnserializeApi
{
    /**
     * Unserialize content using Brummann\Polyfill\Unserialize.
     *
     * @param string $type
     * @param string $serialized
     *
     * @return mixed
     */
    public static function unserialize($type, $serialized, $ignoreErrors = false)
    {
        $allowedClasses = [];

        switch ($type) {
            case 'career':
            case 'sequence_graph':
                $allowedClasses = [
                    \Fhaculty\Graph\Graph::class,
                    \Fhaculty\Graph\Set\VerticesMap::class,
                    \Fhaculty\Graph\Set\Vertices::class,
                    \Fhaculty\Graph\Set\Edges::class,
                    \Fhaculty\Graph\Vertex::class,
                    \Fhaculty\Graph\Edge\Base::class,
                    \Fhaculty\Graph\Edge\Directed::class,
                    \Fhaculty\Graph\Edge\Undirected::class,
                ];
                break;
            case 'course':
                $allowedClasses = [
                    \Chamilo\CourseBundle\Component\CourseCopy\Course::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Announcement::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Asset::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Attendance::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\CalendarEvent::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseCopyLearnpath::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseCopyTestCategory::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseDescription::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseSession::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Document::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Forum::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumCategory::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumPost::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumTopic::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Glossary::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\GradeBookBackup::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\LearnPathCategory::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Link::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\LinkCategory::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Quiz::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\QuizQuestion::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\QuizQuestionOption::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\ScormDocument::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Survey::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\SurveyInvitation::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\SurveyQuestion::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Thematic::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\ToolIntro::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Wiki::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\Work::class,
                    \Chamilo\CourseBundle\Component\CourseCopy\Resources\XapiTool::class,
                    \Chamilo\CourseBundle\Entity\CLpCategory::class,
                    stdClass::class,
                    Category::class,
                    AttendanceLink::class,
                    DropboxLink::class,
                    Evaluation::class,
                    ExerciseLink::class,
                    ForumThreadLink::class,
                    LearnpathLink::class,
                    LinkFactory::class,
                    Result::class,
                    StudentPublicationLink::class,
                    SurveyLink::class,
                ];
            // no break
            case 'lp':
                $allowedClasses = array_merge(
                    $allowedClasses,
                    [
                        learnpath::class,
                        learnpathItem::class,
                        aicc::class,
                        aiccBlock::class,
                        aiccItem::class,
                        aiccObjective::class,
                        aiccResource::class,
                        scorm::class,
                        scormItem::class,
                        scormMetadata::class,
                        scormOrganization::class,
                        scormResource::class,
                        Link::class,
                        LpItem::class,
                    ]
                );
                break;
            case 'not_allowed_classes':
            default:
                $allowedClasses = false;
        }

        if ($ignoreErrors) {
            return @Unserialize::unserialize(
                $serialized,
                ['allowed_classes' => $allowedClasses]
            );
        }

        return Unserialize::unserialize(
            $serialized,
            ['allowed_classes' => $allowedClasses]
        );
    }
}
