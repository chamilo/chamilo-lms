<?php
namespace IMSGlobal\LTI;

class LTI_Lineitem {
    private $id;
    private $score_maximum;
    private $label;
    private $resource_id;
    private $tag;
    private $start_date_time;
    private $end_date_time;

    public function __construct(array $lineitem = null) {
        if (empty($lineitem)) {
            return;
        }
        $this->id = $lineitem["id"];
        $this->score_maximum = $lineitem["scoreMaximum"];
        $this->label = $lineitem["label"];
        $this->resource_id = $lineitem["resourceId"];
        $this->tag = $lineitem["tag"];
        $this->start_date_time = $lineitem["startDateTime"];
        $this->end_date_time = $lineitem["endDateTime"];
    }

    /**
     * Static function to allow for method chaining without having to assign to a variable first.
     */
    public static function new() {
        return new LTI_Lineitem();
    }

    public function get_id() {
        return $this->id;
    }

    public function set_id($value) {
        $this->id = $value;
        return $this;
    }

    public function get_label() {
        return $this->label;
    }

    public function set_label($value) {
        $this->label = $value;
        return $this;
    }

    public function get_score_maximum() {
        return $this->score_maximum;
    }

    public function set_score_maximum($value) {
        $this->score_maximum = $value;
        return $this;
    }

    public function get_resource_id() {
        return $this->resource_id;
    }

    public function set_resource_id($value) {
        $this->resource_id = $value;
        return $this;
    }

    public function get_tag() {
        return $this->tag;
    }

    public function set_tag($value) {
        $this->tag = $value;
        return $this;
    }

    public function get_start_date_time() {
        return $this->start_date_time;
    }

    public function set_start_date_time($value) {
        $this->start_date_time = $value;
        return $this;
    }

    public function get_end_date_time() {
        return $this->end_date_time;
    }

    public function set_end_date_time($value) {
        $this->end_date_time = $value;
        return $this;
    }

    public function __toString() {
        return json_encode(array_filter([
            "id" => $this->id,
            "scoreMaximum" => $this->score_maximum,
            "label" => $this->label,
            "resourceId" => $this->resource_id,
            "tag" => $this->tag,
            "startDateTime" => $this->start_date_time,
            "endDateTime" => $this->end_date_time,
        ]));
    }
}
?>