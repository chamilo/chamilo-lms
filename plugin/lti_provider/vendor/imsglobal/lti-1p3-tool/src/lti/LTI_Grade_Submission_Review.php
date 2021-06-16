<?php
namespace IMSGlobal\LTI;

class LTI_Grade_Submission_Review {
    private $reviewable_status;
    private $label;
    private $url;
    private $custom;

    /**
     * Static function to allow for method chaining without having to assign to a variable first.
     */
    public static function new() {
        return new LTI_Grade_Submission_Review();
    }

    public function get_reviewable_status() {
        return $this->reviewable_status;
    }

    public function set_reviewable_status($value) {
        $this->reviewable_status = $value;
        return $this;
    }

    public function get_label() {
        return $this->label;
    }

    public function set_label($value) {
        $this->label = $value;
        return $this;
    }

    public function get_url() {
        return $this->url;
    }

    public function set_url($url) {
        $this->url = $url;
        return $this;
    }

    public function get_custom() {
        return $this->custom;
    }

    public function set_custom($value) {
        $this->custom = $value;
        return $this;
    }

    public function __toString() {
        return json_encode(array_filter([
            "reviewableStatus" => $this->reviewable_status,
            "label" => $this->label,
            "url" => $this->url,
            "custom" => $this->custom,
        ]));
    }
}
?>