<?php
namespace IMSGlobal\LTI;

class LTI_Grade {
    private $score_given;
    private $score_maximum;
    private $comment;
    private $activity_progress;
    private $grading_progress;
    private $timestamp;
    private $user_id;
    private $submission_review;

    /**
     * Static function to allow for method chaining without having to assign to a variable first.
     */
    public static function new() {
        return new LTI_Grade();
    }

    public function get_score_given() {
        return $this->score_given;
    }

    public function set_score_given($value) {
        $this->score_given = $value;
        return $this;
    }

    public function get_score_maximum() {
        return $this->score_maximum;
    }

    public function set_score_maximum($value) {
        $this->score_maximum = $value;
        return $this;
    }

    public function get_comment() {
        return $this->comment;
    }

    public function set_comment($comment) {
        $this->comment = $comment;
        return $this;
    }

    public function get_activity_progress() {
        return $this->activity_progress;
    }

    public function set_activity_progress($value) {
        $this->activity_progress = $value;
        return $this;
    }

    public function get_grading_progress() {
        return $this->grading_progress;
    }

    public function set_grading_progress($value) {
        $this->grading_progress = $value;
        return $this;
    }

    public function get_timestamp() {
        return $this->timestamp;
    }

    public function set_timestamp($value) {
        $this->timestamp = $value;
        return $this;
    }

    public function get_user_id() {
        return $this->user_id;
    }

    public function set_user_id($value) {
        $this->user_id = $value;
        return $this;
    }

    public function get_submission_review() {
        return $this->submission_review;
    }

    public function set_submission_review($value) {
        $this->submission_review = $value;
        return $this;
    }

    public function __toString() {
        return json_encode(array_filter([
            "scoreGiven" => 0 + $this->score_given,
            "scoreMaximum" => 0 + $this->score_maximum,
            "comment" => $this->comment,
            "activityProgress" => $this->activity_progress,
            "gradingProgress" => $this->grading_progress,
            "timestamp" => $this->timestamp,
            "userId" => $this->user_id,
            "submissionReview" => $this->submission_review,
        ]));
    }
}
?>