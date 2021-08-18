<?php
/* For licensing terms, see /license.txt */

abstract class cc_xml_namespace
{
    const xml = 'http://www.w3.org/XML/1998/namespace';
}

abstract class cc_qti_metadata
{
    // Assessment.
    const qmd_assessmenttype       = 'qmd_assessmenttype';
    const qmd_scoretype            = 'qmd_scoretype';
    const qmd_feedbackpermitted    = 'qmd_feedbackpermitted';
    const qmd_hintspermitted       = 'qmd_hintspermitted';
    const qmd_solutionspermitted   = 'qmd_solutionspermitted';
    const qmd_timelimit            = 'qmd_timelimit';
    const cc_allow_late_submission = 'cc_allow_late_submission';
    const cc_maxattempts           = 'cc_maxattempts';
    const cc_profile               = 'cc_profile';

    // Items.
    const cc_weighting         = 'cc_weighting';
    const qmd_scoringpermitted = 'qmd_scoringpermitted';
    const qmd_computerscored   = 'qmd_computerscored';
    const cc_question_category = 'cc_question_category';
}

abstract class cc_qti_profiletype
{
    const multiple_choice   = 'cc.multiple_choice.v0p1';
    const multiple_response = 'cc.multiple_response.v0p1';
    const true_false        = 'cc.true_false.v0p1';
    const field_entry       = 'cc.fib.v0p1';
    const pattern_match     = 'cc.pattern_match.v0p1';
    const essay             = 'cc.essay.v0p1';

    /**
     *
     * validates a profile value
     * @param string $value
     * @return boolean
     */
    public static function valid($value) {
        static $verification_values = array( self::essay,
                                             self::field_entry,
                                             self::multiple_choice,
                                             self::multiple_response,
                                             self::pattern_match,
                                             self::true_false
                                           );
        return in_array($value, $verification_values);
    }

}

abstract class cc_qti_values
{
    const exam_profile = 'cc.exam.v0p1';
    const Yes          = 'Yes';
    const No           = 'No';
    const Response     = 'Response';
    const Solution     = 'Solution';
    const Hint         = 'Hint';
    const Examination  = 'Examination';
    const Percentage   = 'Percentage';
    const unlimited    = 'unlimited';
    const Single       = 'Single';
    const Multiple     = 'Multiple';
    const Ordered      = 'Ordered';
    const Asterisk     = 'Asterisk';
    const Box          = 'Box';
    const Dashline     = 'Dashline';
    const Underline    = 'Underline';
    const Decimal      = 'Decimal';
    const Integer      = 'Integer';
    const Scientific   = 'Scientific';
    const String       = 'String';
    const SCORE        = 'SCORE';
    const Set          = 'Set';
    const Complete     = 'Complete';
    const texttype     = 'text/plain';
    const htmltype     = 'text/html';
}

abstract class cc_qti_tags
{
    const questestinterop = 'questestinterop';
    const assessment = 'assessment';
    const qtimetadata = 'qtimetadata';
    const qtimetadatafield = 'qtimetadatafield';
    const fieldlabel = 'fieldlabel';
    const fieldentry = 'fieldentry';
    const section = 'section';
    const ident = 'ident';
    const item = 'item';
    const title = 'title';
    const itemmetadata = 'itemmetadata';
    const presentation = 'presentation';
    const material = 'material';
    const mattext = 'mattext';
    const matref = 'matref';
    const matbreak = 'matbreak';
    const texttype = 'texttype';
    const response_lid = 'response_lid';
    const render_choice = 'render_choice';
    const response_label = 'response_label';
    const resprocessing = 'resprocessing';
    const outcomes = 'outcomes';
    const decvar = 'decvar';
    const respcondition = 'respcondition';
    const conditionvar = 'conditionvar';
    const other = 'other';
    const displayfeedback = 'displayfeedback';
    const maxvalue = 'maxvalue';
    const minvalue = 'minvalue';
    const varname = 'varname';
    const vartype = 'vartype';
    const continue_ = 'continue';
    const feedbacktype = 'feedbacktype';
    const linkrefid = 'linkrefid';
    const varequal = 'varequal';
    const respident = 'respident';
    const itemfeedback = 'itemfeedback';
    const flow_mat = 'flow_mat';
    const rcardinality = 'rcardinality';
    const charset = 'charset';
    const label = 'label';
    const uri = 'uri';
    const width = 'width';
    const height = 'height';
    const x0 = 'x0';
    const y0 = 'y0';
    const xml_lang = 'lang';
    const xml_space = 'space';
    const rubric = 'rubric';
    const altmaterial = 'altmaterial';
    const presentation_material = 'presentation_material';
    const t_class = 'class';
    const material_ref = 'material_ref';
    const rtiming = 'rtiming';
    const render_fib = 'render_fib';
    const shuffle = 'shuffle';
    const minnumber = 'minnumber';
    const maxnumber = 'maxnumber';
    const encoding = 'encoding';
    const maxchars = 'maxchars';
    const prompt = 'prompt';
    const fibtype = 'fibtype';
    const rows = 'rows';
    const columns = 'columns';
    const labelrefid = 'labelrefid';
    const rshuffle = 'rshuffle';
    const match_group = 'match_group';
    const match_max = 'match_max';
    const flow = 'flow';
    const response_str = 'response_str';
    const flow_label = 'flow_label';
    const setvar = 'setvar';
    const action = 'action';
    const and_ = 'and';
    const not_ = 'not';
    const case_ = 'case';
    const varsubstring = 'varsubstring';
    const hint = 'hint';
    const solution = 'solution';
    const feedbackstyle = 'feedbackstyle';
    const solutionmaterial = 'solutionmaterial';
    const hintmaterial = 'hintmaterial';
}

class cc_question_metadata_base
{

    protected $metadata = array();

    /**
     * @param string $setting
     * @param mixed $value
     */
    protected function set_setting($setting, $value = null) {
        $this->metadata[$setting] = $value;
    }

    /**
     * @param string $setting
     * @return mixed
     */
    protected function get_setting($setting) {
        $result = null;
        if (array_key_exists($setting, $this->metadata)) {
            $result = $this->metadata[$setting];
        }
        return $result;
    }

    /**
     * @param string $setting
     * @param string $namespace
     * @param string $value
     */
    protected function set_setting_wns($setting, $namespace, $value = null) {
        $this->metadata[$setting] = array($namespace => $value);
    }

    /**
     * @param string $setting
     * @param boolean $value
     */
    protected function enable_setting_yesno($setting, $value = true) {
        $svalue = $value ? cc_qti_values::Yes : cc_qti_values::No;
        $this->set_setting($setting, $svalue);
    }

    /**
     * @param XMLGenericDocument $doc
     * @param DOMNode $item
     * @param string $namespace
     */
    public function generate_attributes(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        foreach ($this->metadata as $attribute => $value) {
            if (!is_null($value)) {
                if (!is_array($value)) {
                    $doc->appendNewAttributeNs($item, $namespace, $attribute, $value);
                } else {
                    $ns = key($value);
                    $nval = current($value);
                    if (!is_null($nval)) {
                        $doc->appendNewAttributeNs($item, $ns, $attribute, $nval);
                    }
                }
            }
        }
    }

    /**
     * @param XMLGenericDocument $doc
     * @param DOMNode $item
     * @param string $namespace
     */
    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $qtimetadata = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::qtimetadata);
        foreach ($this->metadata as $label => $entry) {
            if (!is_null($entry)) {
                $qtimetadatafield = $doc->appendNewElementNs($qtimetadata, $namespace, cc_qti_tags::qtimetadatafield);
                $doc->appendNewElementNs($qtimetadatafield, $namespace, cc_qti_tags::fieldlabel, $label);
                $doc->appendNewElementNs($qtimetadatafield, $namespace, cc_qti_tags::fieldentry, $entry);
            }
        }
    }
}

class cc_question_metadata extends cc_question_metadata_base
{

    public function set_category($value) {
        $this->set_setting(cc_qti_metadata::cc_question_category, $value);
    }

    public function set_weighting($value) {
        $this->set_setting(cc_qti_metadata::cc_weighting, $value);
    }

    public function enable_scoringpermitted($value = true) {
        $this->enable_setting_yesno(cc_qti_metadata::qmd_scoringpermitted, $value);
    }

    public function enable_computerscored($value = true) {
        $this->enable_setting_yesno(cc_qti_metadata::qmd_computerscored, $value);
    }

    /**
     *
     * Constructs metadata
     * @param string $profile
     * @throws InvalidArgumentException
     */
    public function __construct($profile) {
        if (!cc_qti_profiletype::valid($profile)) {
            throw new InvalidArgumentException('Invalid profile type!');
        }
        $this->set_setting(cc_qti_metadata::cc_profile, $profile);
        $this->set_setting(cc_qti_metadata::cc_question_category);
        $this->set_setting(cc_qti_metadata::cc_weighting        );
        $this->set_setting(cc_qti_metadata::qmd_scoringpermitted);
        $this->set_setting(cc_qti_metadata::qmd_computerscored  );
    }
}


class CcAssesmentMetadata extends cc_question_metadata_base
{

    public function enableHints($value = true) {
        $this->enable_setting_yesno(cc_qti_metadata::qmd_hintspermitted, $value);
    }

    public function enableSolutions($value = true) {
        $this->enable_setting_yesno(cc_qti_metadata::qmd_solutionspermitted, $value);
    }

    public function enableLatesubmissions($value = true) {
        $this->enable_setting_yesno(cc_qti_metadata::cc_allow_late_submission, $value);
    }

    public function enableFeedback($value = true) {
        $this->enable_setting_yesno(cc_qti_metadata::qmd_feedbackpermitted, $value);
    }

    public function setTimelimit($value) {
        $ivalue = (int)$value;
        if (($ivalue < 0) || ($ivalue > 527401)) {
            throw new OutOfRangeException('Time limit value out of permitted range!');
        }

        $this->set_setting(cc_qti_metadata::qmd_timelimit, $value);
    }

    public function setMaxattempts($value) {
        $valid_values = array(cc_qti_values::Examination, cc_qti_values::unlimited, 1, 2, 3, 4, 5);
        if (!in_array($value, $valid_values)) {
            throw new OutOfRangeException('Max attempts has invalid value');
        }

        $this->set_setting(cc_qti_metadata::cc_maxattempts, $value);
    }

    public function __construct() {
        //prepared default values
        $this->set_setting(cc_qti_metadata::cc_profile        , cc_qti_values::exam_profile);
        $this->set_setting(cc_qti_metadata::qmd_assessmenttype, cc_qti_values::Examination );
        $this->set_setting(cc_qti_metadata::qmd_scoretype     , cc_qti_values::Percentage  );
        //optional empty values
        $this->set_setting(cc_qti_metadata::qmd_feedbackpermitted   );
        $this->set_setting(cc_qti_metadata::qmd_hintspermitted      );
        $this->set_setting(cc_qti_metadata::qmd_solutionspermitted  );
        $this->set_setting(cc_qti_metadata::qmd_timelimit           );
        $this->set_setting(cc_qti_metadata::cc_allow_late_submission);
        $this->set_setting(cc_qti_metadata::cc_maxattempts          );
    }

}

class cc_assesment_mattext extends cc_question_metadata_base
{
    protected $value = null;

    public function __construct($value = null) {
        $this->set_setting(cc_qti_tags::texttype, cc_qti_values::texttype);
        $this->set_setting(cc_qti_tags::charset);//, 'ascii-us');
        $this->set_setting(cc_qti_tags::label);
        $this->set_setting(cc_qti_tags::uri);
        $this->set_setting(cc_qti_tags::width);
        $this->set_setting(cc_qti_tags::height);
        $this->set_setting(cc_qti_tags::x0);
        $this->set_setting(cc_qti_tags::y0);
        $this->set_setting_wns(cc_qti_tags::xml_lang , cc_xml_namespace::xml);
        $this->set_setting_wns(cc_qti_tags::xml_space, cc_xml_namespace::xml);//, 'default');
        $this->value = $value;
    }

    public function set_label($value) {
        $this->set_setting(cc_qti_tags::label, $value);
    }

    public function set_uri($value) {
        $this->set_setting(cc_qti_tags::uri, $value);
    }

    public function set_width_height($width = null, $height = null) {
        $this->set_setting(cc_qti_tags::width, $width);
        $this->set_setting(cc_qti_tags::height, $height);
    }

    public function set_coor($x = null, $y = null) {
        $this->set_setting(cc_qti_tags::x0, $x);
        $this->set_setting(cc_qti_tags::y0, $y);
    }

    public function set_lang($lang = null) {
        $this->set_setting_wns(cc_qti_tags::xml_lang , cc_xml_namespace::xml, $lang);
    }

    public function setContent($content, $type = cc_qti_values::texttype, $charset = null) {
        $this->value = $content;
        $this->set_setting(cc_qti_tags::texttype, $type);
        $this->set_setting(cc_qti_tags::charset, $charset);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $mattext = $doc->appendNewElementNsCdata($item, $namespace, cc_qti_tags::mattext, $this->value);
        $this->generate_attributes($doc, $mattext, $namespace);
    }
}

class cc_assesment_matref
{
    protected $linkref = null;

    public function __construct($linkref) {
        $this->linkref = $linkref;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $doc->appendNewElementNs($item, $namespace, cc_qti_tags::matref, $this->linkref);
        $doc->appendNewAttributeNs($node, $namespace, cc_qti_tags::linkrefid, $this->linkref);
    }
}

class cc_assesment_response_matref extends cc_assesment_matref
{
    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::material_ref);
        $doc->appendNewAttributeNs($node, $namespace, cc_qti_tags::linkrefid, $this->linkref);
    }
}

class cc_assesment_matbreak
{
    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $doc->appendNewElementNs($item, $namespace, cc_qti_tags::matbreak);
    }
}

abstract class cc_assesment_material_base extends cc_question_metadata_base
{
    /**
    * @var mixed
    */
    protected $mattag = null;
    protected $tagname   = null;

    protected function set_tag_value($object) {
        $this->mattag  = $object;
    }

    public function set_mattext(cc_assesment_mattext $object) {
        $this->set_tag_value($object);
    }

    public function set_matref(cc_assesment_matref $object) {
        $this->set_tag_value($object);
    }

    public function set_matbreak(cc_assesment_matbreak $object) {
        $this->set_tag_value($object);
    }

    public function set_lang($value) {
        $this->set_setting_wns(cc_qti_tags::xml_lang , cc_xml_namespace::xml, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $material = $doc->appendNewElementNs($item, $namespace, $this->tagname);
        $this->generate_attributes($doc, $material, $namespace);
        if (!empty($this->mattag)) {
            $this->mattag->generate($doc, $material, $namespace);
        }
        return $material;
    }
}

class cc_assesment_altmaterial extends cc_assesment_material_base
{
    public function __construct($value = null) {
        $this->set_setting_wns(cc_qti_tags::xml_lang , cc_xml_namespace::xml);
        $this->tagname = cc_qti_tags::altmaterial;
    }
}

class cc_assesment_material extends cc_assesment_material_base
{

    protected $altmaterial = null;

    public function __construct($value = null) {
        $this->set_setting(cc_qti_tags::label);
        $this->set_setting_wns(cc_qti_tags::xml_lang , cc_xml_namespace::xml);
        $this->tagname = cc_qti_tags::material;
    }

    public function set_label($value) {
        $this->set_setting(cc_qti_tags::label, $value);
    }

    public function set_altmaterial(cc_assesment_altmaterial $object) {
        $this->altmaterial = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $material = parent::generate($doc, $item, $namespace);
        if (!empty($this->altmaterial)) {
            $this->altmaterial->generate($doc, $material, $namespace);
        }
    }
}

class cc_assesment_rubric_base extends cc_question_metadata_base
{

    protected $material = null;

    public function set_material($object) {
        $this->material = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $rubric = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::rubric);
        if (!empty($this->material)) {
            $this->material->generate($doc, $rubric, $namespace);
        }
    }
}

class cc_assesment_presentation_material_base extends cc_question_metadata_base
{
    protected $flowmats = array();

    public function add_flow_mat($object) {
        $this->flowmats[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::presentation_material);
        if (!empty($this->flowmats)) {
            foreach ($this->flowmats as $flow_mat) {
                $flow_mat->generate($doc, $node, $namespace);
            }
        }
    }
}

class cc_assesment_flow_mat_base extends cc_question_metadata_base
{

    protected $mattag = null;

    protected function set_tag_value($object) {
        $this->mattag  = $object;
    }

    public function set_flow_mat(cc_assesment_flow_mat_base $object) {
        $this->set_tag_value($object);
    }

    public function set_material(cc_assesment_material $object) {
        $this->set_tag_value($object);
    }

    public function set_material_ref(cc_assesment_matref $object) {
        $this->set_tag_value($object);
    }

    public function __construct($value = null) {
        $this->set_setting(cc_qti_tags::t_class);
    }

    public function set_class($value) {
        $this->set_setting(cc_qti_tags::t_class, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::flow_mat);
        $this->generate_attributes($doc, $node, $namespace);
        if (!empty($this->mattag)) {
            $this->mattag->generate($doc, $node, $namespace);
        }
    }

}

class CcAssesmentSection extends cc_question_metadata_base
{
    /**
     * @var array
     */
    protected $items = array();

    public function __construct() {
        $this->set_setting(cc_qti_tags::ident, CcHelpers::uuidgen('I_'));
        $this->set_setting(cc_qti_tags::title);
        $this->set_setting_wns(cc_qti_tags::xml_lang, cc_xml_namespace::xml);
    }

    public function set_ident($value) {
        $this->set_setting(cc_qti_tags::ident, $value);
    }

    public function setTitle($value) {
        $this->set_setting(cc_qti_tags::title, $value);
    }

    public function set_lang($value) {
        $this->set_setting_wns(cc_qti_tags::xml_lang, cc_xml_namespace::xml, $value);
    }

    public function addItem(cc_assesment_section_item $object) {
        $this->items[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::section);
        $this->generate_attributes($doc, $node, $namespace);
        if (!empty($this->items)) {
            foreach ($this->items as $item) {
                $item->generate($doc, $node, $namespace);
            }
        }
    }
}

class cc_assesment_itemmetadata extends cc_question_metadata_base
{
    public function add_metadata($object) {
        $this->metadata[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::itemmetadata);
        if (!empty($this->metadata)) {
            foreach ($this->metadata as $metaitem) {
                $metaitem->generate($doc, $node, $namespace);
            }
        }
    }
}

class cc_assesment_decvartype extends cc_question_metadata_base
{

    public function __construct() {
        $this->set_setting(cc_qti_tags::varname, cc_qti_values::SCORE);
        $this->set_setting(cc_qti_tags::vartype, cc_qti_values::Integer);
        $this->set_setting(cc_qti_tags::minvalue);
        $this->set_setting(cc_qti_tags::maxvalue);
    }

    public function set_vartype($value) {
        $this->set_setting(cc_qti_tags::vartype, $value);
    }

    public function set_limits($min = null, $max = null) {
        $this->set_setting(cc_qti_tags::minvalue, $min);
        $this->set_setting(cc_qti_tags::maxvalue, $max);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::decvar);
        $this->generate_attributes($doc, $node, $namespace);
    }
}


class cc_assignment_conditionvar_othertype extends cc_question_metadata_base
{
    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $doc->appendNewElementNs($item, $namespace, cc_qti_tags::other);
    }
}

class cc_assignment_conditionvar_varequaltype extends cc_question_metadata_base
{
    protected $tagname = null;
    protected $answerid = null;

    public function __construct($value = null) {
        if (is_null($value)) {
            throw new InvalidArgumentException('Must not pass null!');
        }
        $this->answerid = $value;
        $this->set_setting(cc_qti_tags::respident);
        $this->set_setting(cc_qti_tags::case_);//, cc_qti_values::No  );
        $this->tagname = cc_qti_tags::varequal;
    }

    public function set_respident($value) {
        $this->set_setting(cc_qti_tags::respident, $value);
    }

    public function enable_case($value = true) {
        $this->enable_setting_yesno(cc_qti_tags::case_, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, $this->tagname, $this->answerid);
        $this->generate_attributes($doc, $node, $namespace);
    }
}

class cc_assignment_conditionvar_varsubstringtype extends cc_assignment_conditionvar_varequaltype
{
    public function __construct($value) {
        parent::__construct($value);
        $this->tagname = cc_qti_tags::varsubstring;
    }
}


class cc_assignment_conditionvar_andtype extends cc_question_metadata_base
{
    protected $nots = array();
    protected $varequals = array();

    public function set_not(cc_assignment_conditionvar_varequaltype $object) {
        $this->nots[] = $object;
    }

    public function set_varequal(cc_assignment_conditionvar_varequaltype $object) {
        $this->varequals[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::and_);
        if (!empty($this->nots)) {
            foreach ($this->nots as $notv) {
                $not = $doc->appendNewElementNs($node, $namespace, cc_qti_tags::not_);
                $notv->generate($doc, $not, $namespace);
            }
        }

        if (!empty($this->varequals)) {
            foreach ($this->varequals as $varequal) {
                $varequal->generate($doc, $node, $namespace);
            }
        }
    }
}

class cc_assignment_conditionvar extends cc_question_metadata_base
{

    protected $and = null;
    protected $other = null;
    protected $varequal = array();
    protected $varsubstring = null;

    public function set_and(cc_assignment_conditionvar_andtype $object) {
        $this->and = $object;
    }

    public function set_other(cc_assignment_conditionvar_othertype $object) {
        $this->other = $object;
    }

    public function set_varequal(cc_assignment_conditionvar_varequaltype $object) {
        $this->varequal[] = $object;
    }

    public function set_varsubstring(cc_assignment_conditionvar_varsubstringtype $object) {
        $this->varsubstring = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::conditionvar);

        if (!empty($this->and)) {
            $this->and->generate($doc, $node, $namespace);
        }

        if (!empty($this->other)) {
            $this->other->generate($doc, $node, $namespace);
        }

        if (!empty($this->varequal)) {
            foreach ($this->varequal as $varequal) {
                $varequal->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->varsubstring)) {
            $this->varsubstring->generate($doc, $node, $namespace);
        }
    }
}

class cc_assignment_displayfeedbacktype extends cc_question_metadata_base
{
    public function __construct() {
        $this->set_setting(cc_qti_tags::feedbacktype);
        $this->set_setting(cc_qti_tags::linkrefid);
    }

    public function set_feedbacktype($value) {
        $this->set_setting(cc_qti_tags::feedbacktype, $value);
    }

    public function set_linkrefid($value) {
        $this->set_setting(cc_qti_tags::linkrefid, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::displayfeedback);
        $this->generate_attributes($doc, $node, $namespace);
    }
}


class cc_assignment_setvartype extends cc_question_metadata_base
{

    protected $tagvalue = null;

    public function __construct($tagvalue = 100) {
        $this->set_setting(cc_qti_tags::varname, cc_qti_values::SCORE);
        $this->set_setting(cc_qti_tags::action , cc_qti_values::Set  );
        $this->tagvalue = $tagvalue;
    }

    public function set_varname($value) {
        $this->set_setting(cc_qti_tags::varname, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::setvar, $this->tagvalue);
        $this->generate_attributes($doc, $node, $namespace);
    }
}

class cc_assesment_respconditiontype extends cc_question_metadata_base
{

    protected $conditionvar = null;
    protected $setvar = array();
    protected $displayfeedback = array();

    public function __construct() {
        $this->set_setting(cc_qti_tags::title);
        $this->set_setting(cc_qti_tags::continue_, cc_qti_values::No);
    }

    public function setTitle($value) {
        $this->set_setting(cc_qti_tags::title, $value);
    }

    public function enable_continue($value = true) {
        $this->enable_setting_yesno(cc_qti_tags::continue_, $value);
    }

    public function set_conditionvar(cc_assignment_conditionvar $object) {
        $this->conditionvar = $object;
    }

    public function add_setvar(cc_assignment_setvartype $object) {
        $this->setvar[] = $object;
    }

    public function add_displayfeedback(cc_assignment_displayfeedbacktype $object) {
        $this->displayfeedback[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::respcondition);
        $this->generate_attributes($doc, $node, $namespace);

        if (!empty($this->conditionvar)) {
            $this->conditionvar->generate($doc, $node, $namespace);
        }

        if (!empty($this->setvar)) {
            foreach ($this->setvar as $setvar) {
                $setvar->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->displayfeedback)) {
            foreach ($this->displayfeedback as $displayfeedback) {
                $displayfeedback->generate($doc, $node, $namespace);
            }
        }
    }
}


class cc_assesment_resprocessingtype extends cc_question_metadata_base
{

    protected $decvar = null;
    protected $respconditions = array();

    public function set_decvar(cc_assesment_decvartype $object) {
        $this->decvar = $object;
    }

    public function add_respcondition(cc_assesment_respconditiontype $object) {
        $this->respconditions[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::resprocessing);
        $outcomes = $doc->appendNewElementNs($node, $namespace, cc_qti_tags::outcomes);
        if (!empty($this->decvar)) {
            $this->decvar->generate($doc, $outcomes, $namespace);
        }
        if (!empty($this->respconditions)) {
            foreach ($this->respconditions as $rcond) {
                $rcond->generate($doc, $node, $namespace);
            }
        }
    }
}


class cc_assesment_itemfeedback_shintmaterial_base extends cc_question_metadata_base
{

    protected $tagname = null;
    protected $flow_mats = array();
    protected $materials = array();

    /**
     * @param cc_assesment_flow_mattype $object
     */
    public function add_flow_mat(cc_assesment_flow_mattype $object) {
        $this->flow_mats[] = $object;
    }

    /**
     * @param cc_assesment_material $object
     */
    public function add_material(cc_assesment_material $object) {
        $this->materials[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, $this->tagname);

        if (!empty($this->flow_mats)) {
            foreach ($this->flow_mats as $flow_mat) {
                $flow_mat->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->materials)) {
            foreach ($this->materials as $material) {
                $material->generate($doc, $node, $namespace);
            }
        }
    }
}

class cc_assesment_itemfeedback_hintmaterial extends cc_assesment_itemfeedback_shintmaterial_base
{
    public function __construct() {
        $this->tagname = cc_qti_tags::hint;
    }
}

class cc_assesment_itemfeedback_solutionmaterial extends cc_assesment_itemfeedback_shintmaterial_base
{
    public function __construct() {
        $this->tagname = cc_qti_tags::solutionmaterial;
    }
}

class cc_assesment_itemfeedback_shintype_base extends cc_question_metadata_base
{

    protected $tagname = null;
    protected $items = array();

    public function __construct() {
        $this->set_setting(cc_qti_tags::feedbackstyle, cc_qti_values::Complete);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, $this->tagname);
        $this->generate_attributes($doc, $node, $namespace);

        if (!empty($this->items)) {
            foreach ($this->items as $telement) {
                $telement->generate($doc, $node, $namespace);
            }
        }
    }
}

class cc_assesment_itemfeedback_solutiontype extends cc_assesment_itemfeedback_shintype_base
{
    public function __construct() {
        parent::__construct();
        $this->tagname = cc_qti_tags::solution;
    }

    /**
     * @param cc_assesment_itemfeedback_solutionmaterial $object
     */
    public function add_solutionmaterial(cc_assesment_itemfeedback_solutionmaterial $object) {
        $this->items[] = $object;
    }
}

class cc_assesment_itemfeedbac_hinttype extends cc_assesment_itemfeedback_shintype_base
{
    public function __construct() {
        parent::__construct();
        $this->tagname = cc_qti_tags::hint;
    }

    /**
     * @param cc_assesment_itemfeedback_hintmaterial $object
     */
    public function add_hintmaterial(cc_assesment_itemfeedback_hintmaterial $object) {
        $this->items[] = $object;
    }
}

class cc_assesment_itemfeedbacktype extends cc_question_metadata_base
{

    protected $flow_mat = null;
    protected $material = null;
    protected $solution = null;
    protected $hint = null;

    public function __construct() {
        $this->set_setting(cc_qti_tags::ident, CcHelpers::uuidgen('I_'));
        $this->set_setting(cc_qti_tags::title);
    }

    /**
     * @param string $value
     */
    public function set_ident($value) {
        $this->set_setting(cc_qti_tags::ident, $value);
    }

    /**
     * @param string $value
     */
    public function setTitle($value) {
        $this->set_setting(cc_qti_tags::title, $value);
    }

    /**
     * @param cc_assesment_flow_mattype $object
     */
    public function set_flow_mat(cc_assesment_flow_mattype $object) {
        $this->flow_mat = $object;
    }

    /**
     * @param cc_assesment_material $object
     */
    public function set_material(cc_assesment_material $object) {
        $this->material = $object;
    }

    /**
     * @param cc_assesment_itemfeedback_solutiontype $object
     */
    public function set_solution(cc_assesment_itemfeedback_solutiontype $object) {
        $this->solution = $object;
    }

    public function set_hint($object) {
        $this->hint = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::itemfeedback);
        $this->generate_attributes($doc, $node, $namespace);

        if (!empty($this->flow_mat) && empty($this->material)) {
            $this->flow_mat->generate($doc, $node, $namespace);
        }

        if (!empty($this->material) && empty($this->flow_mat)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->solution)) {
            $this->solution->generate($doc, $node, $namespace);
        }

        if (!empty($this->itemfeedback)) {
            $this->itemfeedback->generate($doc, $node, $namespace);
        }
    }
}

class cc_assesment_section_item extends CcAssesmentSection
{

    protected $itemmetadata = null;
    protected $presentation = null;
    protected $resprocessing = array();
    protected $itemfeedback = array();

    public function set_itemmetadata(cc_assesment_itemmetadata $object) {
        $this->itemmetadata = $object;
    }

    public function set_presentation(cc_assesment_presentation $object) {
        $this->presentation = $object;
    }

    public function add_resprocessing(cc_assesment_resprocessingtype $object) {
        $this->resprocessing[] = $object;
    }

    public function add_itemfeedback(cc_assesment_itemfeedbacktype $object) {
        $this->itemfeedback[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::item);
        $this->generate_attributes($doc, $node, $namespace);

        if (!empty($this->itemmetadata)) {
            $this->itemmetadata->generate($doc, $node, $namespace);
        }

        if (!empty($this->presentation)) {
            $this->presentation->generate($doc, $node, $namespace);
        }

        if (!empty($this->resprocessing)) {
            foreach ($this->resprocessing as $resprocessing) {
                $resprocessing->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->itemfeedback)) {
            foreach ($this->itemfeedback as $itemfeedback) {
                $itemfeedback->generate($doc, $node, $namespace);
            }
        }
    }
}

class cc_assesment_render_choicetype extends cc_question_metadata_base
{

    protected $materials = array();
    protected $material_refs = array();
    protected $response_labels = array();
    protected $flow_labels = array();

    public function __construct() {
        $this->set_setting(cc_qti_tags::shuffle, cc_qti_values::No);
        $this->set_setting(cc_qti_tags::minnumber);
        $this->set_setting(cc_qti_tags::maxnumber);
    }

    public function add_material(cc_assesment_material $object) {
        $this->materials[] = $object;
    }

    public function add_material_ref(cc_assesment_response_matref $object) {
        $this->material_refs[] = $object;
    }

    public function add_response_label(cc_assesment_response_labeltype $object) {
        $this->response_labels[] = $object;
    }

    public function add_flow_label($object) {
        $this->flow_labels[] = $object;
    }

    public function enable_shuffle($value = true) {
        $this->enable_setting_yesno(cc_qti_tags::shuffle, $value);
    }

    public function set_limits($min = null, $max = null) {
        $this->set_setting(cc_qti_tags::minnumber, $min);
        $this->set_setting(cc_qti_tags::maxnumber, $max);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::render_choice);
        $this->generate_attributes($doc, $node, $namespace);

        if (!empty($this->materials)) {
            foreach ($this->materials as $mattag) {
                $mattag->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->material_refs)) {
            foreach ($this->material_refs as $matreftag) {
                $matreftag->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->response_labels)) {
            foreach ($this->response_labels as $resplabtag) {
                $resplabtag->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->flow_labels)) {
            foreach ($this->flow_labels as $flowlabtag) {
                $flowlabtag->generate($doc, $node, $namespace);
            }
        }
    }

}

class cc_assesment_flow_mattype extends cc_question_metadata_base
{

    protected $material = null;
    protected $material_ref = null;
    protected $flow_mat = null;

    public function __construct() {
        $this->set_setting(cc_qti_tags::t_class);
    }

    public function set_class($value) {
        $this->set_setting(cc_qti_tags::t_class, $value);
    }

    public function set_material(cc_assesment_material $object) {
        $this->material = $object;
    }

    public function set_material_ref(cc_assesment_response_matref $object) {
        $this->material_ref = $object;
    }

    public function set_flow_mat(cc_assesment_flow_mattype $object) {
        $this->flow_mat = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::flow_mat);
        $this->generate_attributes($doc, $node, $namespace);

        if (!empty($this->flow_mat)) {
            $this->flow_mat->generate($doc, $node, $namespace);
        }

        if (!empty($this->material)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->material_ref)) {
            $this->material_ref->generate($doc, $node, $namespace);
        }
    }
}

class cc_assesment_response_labeltype extends cc_question_metadata_base
{

    protected $material = null;
    protected $material_ref = null;
    protected $flow_mat = null;

    public function __construct() {
        $this->set_setting(cc_qti_tags::ident, CcHelpers::uuidgen('I_'));
        $this->set_setting(cc_qti_tags::labelrefid);
        $this->set_setting(cc_qti_tags::rshuffle);
        $this->set_setting(cc_qti_tags::match_group);
        $this->set_setting(cc_qti_tags::match_max);
    }

    public function set_ident($value) {
        $this->set_setting(cc_qti_tags::ident, $value);
    }

    public function get_ident() {
        return $this->get_setting(cc_qti_tags::ident);
    }

    public function set_labelrefid($value) {
        $this->set_setting(cc_qti_tags::labelrefid, $value);
    }

    public function enable_rshuffle($value = true) {
        $this->enable_setting_yesno(cc_qti_tags::rshuffle, $value);
    }

    public function set_match_group($value) {
        $this->set_setting(cc_qti_tags::match_group, $value);
    }

    public function set_match_max($value) {
        $this->set_setting(cc_qti_tags::match_max, $value);
    }

    public function set_material(cc_assesment_material $object) {
        $this->material = $object;
    }

    public function set_material_ref(cc_assesment_response_matref $object) {
        $this->material_ref = $object;
    }

    public function set_flow_mat(cc_assesment_flow_mattype $object) {
        $this->flow_mat = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::response_label);
        $this->generate_attributes($doc, $node, $namespace);

        if (!empty($this->material)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->material_ref)) {
            $this->material_ref->generate($doc, $node, $namespace);
        }

        if (!empty($this->flow_mat)) {
            $this->flow_mat->generate($doc, $node, $namespace);
        }
    }
}

class cc_assesment_flow_labeltype extends cc_question_metadata_base
{

    protected $flow_label = null;
    protected $response_label = null;

    public function __construct() {
        $this->set_setting(cc_qti_tags::t_class);
    }

    public function set_class($value) {
        $this->set_setting(cc_qti_tags::t_class, $value);
    }

    public function set_flow_label(cc_assesment_flow_labeltype $object) {
        $this->flow_label = $object;
    }

    public function set_response_label(cc_assesment_response_labeltype $object) {
        $this->response_label = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::flow_label);
        $this->generate_attributes($doc, $node, $namespace);

        if (!empty($this->material)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->material_ref)) {
            $this->material_ref->generate($doc, $node, $namespace);
        }

        if (!empty($this->response_label)) {
            $this->response_label->generate($doc, $node, $namespace);
        }

        if (!empty($this->flow_label)) {
            $this->flow_label->generate($doc, $node, $namespace);
        }
    }

}


class cc_assesment_render_fibtype extends cc_question_metadata_base
{

    protected $material = null;
    protected $material_ref = null;
    protected $response_label = null;
    protected $flow_label = null;

    public function __construct() {
        $this->set_setting(cc_qti_tags::encoding );
        $this->set_setting(cc_qti_tags::charset  );
        $this->set_setting(cc_qti_tags::rows     );
        $this->set_setting(cc_qti_tags::columns  );
        $this->set_setting(cc_qti_tags::maxchars );
        $this->set_setting(cc_qti_tags::minnumber);
        $this->set_setting(cc_qti_tags::maxnumber);
        $this->set_setting(cc_qti_tags::prompt, cc_qti_values::Box);
        $this->set_setting(cc_qti_tags::fibtype, cc_qti_values::String);
    }

    public function set_encoding($value) {
        $this->set_setting(cc_qti_tags::encoding, $value);
    }
    public function set_charset($value) {
        $this->set_setting(cc_qti_tags::charset, $value);
    }

    public function set_rows($value) {
        $this->set_setting(cc_qti_tags::rows, $value);
    }

    public function set_columns($value) {
        $this->set_setting(cc_qti_tags::columns, $value);
    }

    public function set_maxchars($value) {
        $this->set_setting(cc_qti_tags::columns, $value);
    }

    public function set_limits($min = null, $max = null) {
        $this->set_setting(cc_qti_tags::minnumber, $min);
        $this->set_setting(cc_qti_tags::maxnumber, $max);
    }

    public function set_prompt($value) {
        $this->set_setting(cc_qti_tags::prompt, $value);
    }

    public function set_fibtype($value) {
        $this->set_setting(cc_qti_tags::fibtype, $value);
    }

    public function set_material(cc_assesment_material $object) {
        $this->material = $object;
    }

    public function set_material_ref(cc_assesment_response_matref $object) {
        $this->material_ref = $object;
    }

    public function set_response_label(cc_assesment_response_labeltype $object) {
        $this->response_label = $object;
    }

    public function set_flow_label($object) {
        $this->flow_label = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::render_fib);
        $this->generate_attributes($doc, $node, $namespace);

        if (!empty($this->material) && empty($this->material_ref)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->material_ref) && empty($this->material)) {
            $this->material_ref->generate($doc, $node, $namespace);
        }

        if (!empty($this->response_label)) {
            $this->response_label->generate($doc, $node, $namespace);
        }

        if (!empty($this->flow_label)) {
            $this->flow_label->generate($doc, $node, $namespace);
        }
    }
}

class cc_response_lidtype extends cc_question_metadata_base
{

    protected $tagname = null;
    protected $material = null;
    protected $material_ref = null;
    protected $render_choice = null;
    protected $render_fib = null;

    public function __construct() {
        $this->set_setting(cc_qti_tags::rcardinality, cc_qti_values::Single);
        $this->set_setting(cc_qti_tags::rtiming);
        $this->set_setting(cc_qti_tags::ident, CcHelpers::uuidgen('I_'));
        $this->tagname = cc_qti_tags::response_lid;
    }

    public function set_rcardinality($value) {
        $this->set_setting(cc_qti_tags::rcardinality, $value);
    }

    public function enable_rtiming($value = true) {
        $this->enable_setting_yesno(cc_qti_tags::rtiming, $value);
    }

    public function set_ident($value) {
        $this->set_setting(cc_qti_tags::ident, $value);
    }

    public function get_ident() {
        return $this->get_setting(cc_qti_tags::ident);
    }

    public function set_material_ref(cc_assesment_response_matref $object) {
        $this->material_ref = $object;
    }

    public function set_material(cc_assesment_material $object) {
        $this->material = $object;
    }

    public function set_render_choice(cc_assesment_render_choicetype $object) {
        $this->render_choice = $object;
    }

    public function set_render_fib(cc_assesment_render_fibtype $object) {
        $this->render_fib = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, $this->tagname);
        $this->generate_attributes($doc, $node, $namespace);

        if (!empty($this->material) && empty($this->material_ref)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->material_ref) && empty($this->material)) {
            $this->material_ref->generate($doc, $node, $namespace);
        }

        if (!empty($this->render_choice) && empty($this->render_fib)) {
            $this->render_choice->generate($doc, $node, $namespace);
        }

        if (!empty($this->render_fib) && empty($this->render_choice)) {
            $this->render_fib->generate($doc, $node, $namespace);
        }
    }
}

class cc_assesment_response_strtype extends cc_response_lidtype
{
    public function __construct() {
        $rtt = parent::__construct();
        $this->tagname = cc_qti_tags::response_str;
    }
}

class cc_assesment_flowtype extends cc_question_metadata_base
{

    protected $flow = null;
    protected $material = null;
    protected $material_ref = null;
    protected $response_lid = null;
    protected $response_str = null;

    public function __construct() {
        $this->set_setting(cc_qti_tags::t_class);
    }

    public function set_class($value) {
        $this->set_setting(cc_qti_tags::t_class, $value);
    }

    public function set_flow(cc_assesment_flowtype $object) {
        $this->flow = $object;
    }

    public function set_material(cc_assesment_material $object) {
        $this->material = $object;
    }

    public function set_material_ref(cc_assesment_response_matref $object) {
        $this->material_ref = $object;
    }

    public function set_response_lid(cc_response_lidtype $object) {
        $this->response_lid = $object;
    }

    public function set_response_str(cc_assesment_response_strtype $object) {
        $this->response_str = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::flow);
        $this->generate_attributes($doc, $node, $namespace);

        if (!empty($this->flow)) {
            $this->flow->generate($doc, $node, $namespace);
        }

        if (!empty($this->material)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->response_lid)) {
            $this->response_lid->generate($doc, $node, $namespace);
        }

        if (!empty($this->response_str)) {
            $this->response_str->generate($doc, $node, $namespace);
        }
    }
}

class cc_assesment_presentation extends cc_question_metadata_base
{

    protected $flow         = null;
    protected $material     = null;
    protected $response_lid = null;
    protected $response_str = null;

    public function __construct() {
        $this->set_setting(cc_qti_tags::label);
        $this->set_setting_wns(cc_qti_tags::xml_lang , cc_xml_namespace::xml);
        $this->set_setting(cc_qti_tags::x0);
        $this->set_setting(cc_qti_tags::y0);
        $this->set_setting(cc_qti_tags::width);
        $this->set_setting(cc_qti_tags::height);
    }

    public function set_label($value) {
        $this->set_setting(cc_qti_tags::label, $value);
    }

    public function set_lang($value) {
        $this->set_setting_wns(cc_qti_tags::xml_lang , cc_xml_namespace::xml, $value);
    }

    public function set_coor($x = null, $y = null) {
        $this->set_setting(cc_qti_tags::x0, $x);
        $this->set_setting(cc_qti_tags::y0, $y);
    }

    public function set_size($width = null, $height = null) {
        $this->set_setting(cc_qti_tags::width, $width);
        $this->set_setting(cc_qti_tags::height, $height);
    }

    public function set_flow(cc_assesment_flowtype $object) {
        $this->flow = $object;
    }

    public function set_material(cc_assesment_material $object) {
        $this->material = $object;
    }

    public function set_response_lid(cc_response_lidtype $object) {
        $this->response_lid = $object;
    }

    public function set_response_str(cc_assesment_response_strtype $object) {
        $this->response_str = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace) {
        $node = $doc->appendNewElementNs($item, $namespace, cc_qti_tags::presentation);
        $this->generate_attributes($doc, $node, $namespace);

        if (!empty($this->flow)) {
            $this->flow->generate($doc, $node, $namespace);
        }

        if (!empty($this->material) && empty($this->flow)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->response_lid) && empty($this->flow)) {
            $this->response_lid->generate($doc, $node, $namespace);
        }

        if (!empty($this->response_str) && empty($this->flow)) {
            $this->response_str->generate($doc, $node, $namespace);
        }
    }
}

class assesment1_resource_file extends CcGeneralFile
{
    const deafultname = 'assesment.xml';

    protected $rootns   = 'xmlns';
    protected $rootname = cc_qti_tags::questestinterop;
    protected $ccnamespaces = array('xmlns' => 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2',
                                    'xsi'   => 'http://www.w3.org/2001/XMLSchema-instance');
    protected $ccnsnames = array('xmlns' => 'http://www.imsglobal.org/profile/cc/ccv1p0/derived_schema/domainProfile_4/ims_qtiasiv1p2_localised.xsd');
    protected $assessment_title = 'Untitled';
    protected $metadata = null;
    protected $rubric = null;
    protected $presentation_material = null;
    protected $section = null;

    public function setMetadata(CcAssesmentMetadata $object) {
        $this->metadata = $object;
    }

    public function set_rubric(cc_assesment_rubric_base $object) {
        $this->rubric = $object;
    }

    public function set_presentation_material(cc_assesment_presentation_material_base $object) {
        $this->presentation_material = $object;
    }

    public function setSection(CcAssesmentSection $object) {
        $this->section = $object;
    }

    public function setTitle($value) {
        $this->assessment_title = self::safexml($value);
    }

    protected function onSave() {
        $rns = $this->ccnamespaces[$this->rootns];
        //root assesment element - required
        $assessment = $this->appendNewElementNs($this->root, $rns, cc_qti_tags::assessment);
        $this->appendNewAttributeNs($assessment, $rns, cc_qti_tags::ident, CcHelpers::uuidgen('QDB_'));
        $this->appendNewAttributeNs($assessment, $rns, cc_qti_tags::title, $this->assessment_title);

        //metadata - optional
        if (!empty($this->metadata)) {
            $this->metadata->generate($this, $assessment, $rns);
        }

        //rubric - optional
        if (!empty($this->rubric)) {
            $this->rubric->generate($this, $assessment, $rns);
        }

        //presentation_material - optional
        if (!empty($this->presentation_material)) {
            $this->presentation_material->generate($this, $assessment, $rns);
        }

        //section - required
        if (!empty($this->section)) {
            $this->section->generate($this, $assessment, $rns);
        }

        return true;
    }
}


class Assesment13ResourceFile extends assesment1_resource_file
{
    protected $ccnsnames = array('xmlns' => 'http://www.imsglobal.org/profile/cc/ccv1p3/ccv1p3_qtiasiv1p2p1_v1p0.xsd');
}

abstract class CcAssesmentHelper
{

    public static $correct_fb = null;
    public static $incorrect_fb = null;

    public static function add_feedback($qitem, $content, $content_type, $ident) {
        if (empty($content)) {
            return false;
        }
        $qitemfeedback = new cc_assesment_itemfeedbacktype();
        $qitem->add_itemfeedback($qitemfeedback);
        if (!empty($ident)) {
            $qitemfeedback->set_ident($ident);
        }
        $qflowmat = new cc_assesment_flow_mattype();
        $qitemfeedback->set_flow_mat($qflowmat);
        $qmaterialfb = new cc_assesment_material();
        $qflowmat->set_material($qmaterialfb);
        $qmattext = new cc_assesment_mattext();
        $qmaterialfb->set_mattext($qmattext);
        $qmattext->setContent($content, $content_type);
        return true;
    }

    public static function add_answer($qresponse_choice, $content, $content_type) {
        $qresponse_label = new cc_assesment_response_labeltype();
        $qresponse_choice->add_response_label($qresponse_label);
        $qrespmaterial = new cc_assesment_material();
        $qresponse_label->set_material($qrespmaterial);
        $qrespmattext = new cc_assesment_mattext();
        $qrespmaterial->set_mattext($qrespmattext);
        $qrespmattext->setContent($content, $content_type);
        return $qresponse_label;
    }

    public static function add_response_condition($node, $title, $ident, $feedback_refid, $respident) {
        $qrespcondition = new cc_assesment_respconditiontype();
        $node->add_respcondition($qrespcondition);
        //define rest of the conditions
        $qconditionvar = new cc_assignment_conditionvar();
        $qrespcondition->set_conditionvar($qconditionvar);
        $qvarequal = new cc_assignment_conditionvar_varequaltype($ident);
        $qvarequal->enable_case();
        $qconditionvar->set_varequal($qvarequal);
        $qvarequal->set_respident($respident);
        $qdisplayfeedback = new cc_assignment_displayfeedbacktype();
        $qrespcondition->add_displayfeedback($qdisplayfeedback);
        $qdisplayfeedback->set_feedbacktype(cc_qti_values::Response);
        $qdisplayfeedback->set_linkrefid($feedback_refid);
    }

    public static function addAssesmentDescription($rt, $content, $contenttype) {
        if (empty($rt) || empty($content)) {
            return;
        }
        $activity_rubric = new cc_assesment_rubric_base();
        $rubric_material = new cc_assesment_material();
        $activity_rubric->set_material($rubric_material);
        $rubric_mattext = new cc_assesment_mattext();
        $rubric_material->set_label('Summary');
        $rubric_material->set_mattext($rubric_mattext);
        $rubric_mattext->setContent($content, $contenttype);
        $rt->set_rubric($activity_rubric);
    }

    public static function add_respcondition($node, $title, $feedback_refid, $grade_value = null, $continue = false ) {
        $qrespcondition = new cc_assesment_respconditiontype();
        $qrespcondition->setTitle($title);
        $node->add_respcondition($qrespcondition);
        $qrespcondition->enable_continue($continue);
        //Add setvar if grade present
        if ($grade_value !== null) {
            $qsetvar = new cc_assignment_setvartype($grade_value);
            $qrespcondition->add_setvar($qsetvar);
        }
        //define the condition for success
        $qconditionvar = new cc_assignment_conditionvar();
        $qrespcondition->set_conditionvar($qconditionvar);
        $qother = new cc_assignment_conditionvar_othertype();
        $qconditionvar->set_other($qother);
        $qdisplayfeedback = new cc_assignment_displayfeedbacktype();
        $qrespcondition->add_displayfeedback($qdisplayfeedback);
        $qdisplayfeedback->set_feedbacktype(cc_qti_values::Response);
        $qdisplayfeedback->set_linkrefid($feedback_refid);
    }

    /**
     *
     * Enter description here ...
     * @param XMLGenericDocument $qdoc
     * @param unknown_type $manifest
     * @param cc_assesment_section $section
     * @param unknown_type $rootpath
     * @param unknown_type $contextid
     * @param unknown_type $outdir
     */
    public static function processQuestions(&$objQuizz, &$manifest, CcAssesmentSection &$section, $rootpath, $contextid, $outdir) {

        PkgResourceDependencies::instance()->reset();
        $questioncount = 0;
        foreach ($objQuizz['questions'] as $question) {
            $qtype = $question->quiz_type;
            /* Question type :
             * 1 : Unique Answer (Multiple choice, single response)
             * 2 : Multiple Answers (Multiple choice, multiple response)
             *
             */
            $question_processor = null;
            switch ($qtype) {
                case 1:
                        $question_processor = new cc_assesment_question_multichoice($objQuizz, $objQuizz['questions'], $manifest, $section, $question, $rootpath, $contextid, $outdir);
                        $question_processor->generate();
                        ++$questioncount;
                    break;
                case 2:
                        $question_processor = new cc_assesment_question_multichoice_multiresponse($objQuizz, $objQuizz['questions'], $manifest, $section, $question, $rootpath, $contextid, $outdir);
                        $question_processor->generate();
                        ++$questioncount;
                    break;
            }
        }
        //return dependencies
        return ($questioncount == 0)?false: PkgResourceDependencies::instance()->getDeps();
    }
}

class cc_assesment_question_proc_base
{

    protected $quiz = null;
    protected $questions = null;
    protected $manifest = null;
    protected $section = null;
    protected $question_node = null;
    protected $rootpath = null;
    protected $contextid = null;
    protected $outdir = null;
    protected $qtype = null;
    protected $qmetadata = null;
    protected $qitem = null;
    protected $qpresentation = null;
    protected $qresponse_lid = null;
    protected $qresprocessing = null;
    protected $correct_grade_value = null;
    protected $correct_answer_node_id = null;
    protected $correct_answer_ident = null;
    protected $total_grade_value = null;
    protected $answerlist = null;
    protected $general_feedback = null;
    protected $correct_feedbacks = array();
    protected $incorrect_feedbacks = array();

    /**
     * @param XMLGenericDocument $questions
     * @param CcManifest $manifest
     * @param cc_assesment_section $section
     * @param Object $question_node
     * @param string $rootpath
     * @param string $contextid
     * @param string $outdir
     */
    public function __construct(&$quiz, &$questions, CcManifest &$manifest, CcAssesmentSection &$section, &$question_node, $rootpath, $contextid, $outdir) {
        $this->quiz = $quiz;
        $this->questions = $questions;
        $this->manifest = $manifest;
        $this->section = $section;
        $this->question_node = $question_node;
        $this->rootpath = $rootpath;
        $this->contextid = $contextid;
        $this->outdir = $outdir;
        $qitem = new cc_assesment_section_item();
        $this->section->addItem($qitem);
        $qitem->setTitle($question_node->question);
        $this->qitem = $qitem;
    }

    public function on_generate_metadata() {

        if (empty($this->qmetadata)) {
            $this->qmetadata = new cc_question_metadata($this->qtype);
            //Get weighting value
            $weighting_value = $this->question_node->ponderation;

            if ($weighting_value > 1) {
                $this->qmetadata->set_weighting($weighting_value);
            }

            //Get category
            $question_category = $this->question_node->question_category;

            if (!empty($question_category)) {
                $this->qmetadata->set_category($question_category);
            }
            $rts = new cc_assesment_itemmetadata();
            $rts->add_metadata($this->qmetadata);
            $this->qitem->set_itemmetadata($rts);
        }

    }

    public function on_generate_presentation() {
        if (empty($this->qpresentation)) {
            $qpresentation = new cc_assesment_presentation();
            $this->qitem->set_presentation($qpresentation);
            //add question text
            $qmaterial = new cc_assesment_material();
            $qmattext = new cc_assesment_mattext();

            $question_text = $this->question_node->question;
            $result = CcHelpers::processLinkedFiles( $question_text,
                                                        $this->manifest,
                                                        $this->rootpath,
                                                        $this->contextid,
                                                        $this->outdir);

            $qmattext->setContent($result[0], cc_qti_values::htmltype);
            $qmaterial->set_mattext($qmattext);
            $qpresentation->set_material($qmaterial);
            $this->qpresentation = $qpresentation;
            PkgResourceDependencies::instance()->add($result[1]);
        }
    }

    public function on_generate_answers() {}
    public function on_generate_feedbacks() {

        $general_question_feedback = '';

        if (empty($general_question_feedback)) {
            return;
        }

        $name = 'general_fb';
        //Add question general feedback - the one that should be always displayed
        $result = CcHelpers::processLinkedFiles( $general_question_feedback,
                                                    $this->manifest,
                                                    $this->rootpath,
                                                    $this->contextid,
                                                    $this->outdir);

        CcAssesmentHelper::add_feedback($this->qitem,
                                          $result[0],
                                          cc_qti_values::htmltype,
                                          $name);

        PkgResourceDependencies::instance()->add($result[1]);
        $this->general_feedback = $name;
    }

    public function on_generate_response_processing() {

        $qresprocessing = new cc_assesment_resprocessingtype();
        $this->qitem->add_resprocessing($qresprocessing);
        $qdecvar = new cc_assesment_decvartype();
        $qresprocessing->set_decvar($qdecvar);
        //according to the Common Cartridge 1.1 Profile: Implementation document
        //this should always be set to 0, 100 in case of question type that is not essay
        $qdecvar->set_limits(0,100);
        $qdecvar->set_vartype(cc_qti_values::Decimal);

        $this->qresprocessing = $qresprocessing;

    }

    public function generate() {
        $this->on_generate_metadata();

        $this->on_generate_presentation();

        $this->on_generate_answers();

        $this->on_generate_feedbacks();

        $this->on_generate_response_processing();
    }

}

class cc_assesment_question_multichoice extends cc_assesment_question_proc_base
{
    public function __construct($quiz, $questions, $manifest, $section, $question, $rootpath, $contextid, $outdir) {
        parent::__construct($quiz, $questions, $manifest, $section, $question, $rootpath, $contextid, $outdir);
        $this->qtype = cc_qti_profiletype::multiple_choice;

        $correct_answer_node = 0;
        $question_score = 0;
        foreach ($question->answers as $answer) {
            if ($answer['correct'] > 0) {
                $correct_answer_node = 1;
                $this->correct_answer_node_id = $answer['id'];
                $question_score = $answer['ponderation'];
                break;
            }
        }
        if (empty($correct_answer_node)) {
            throw new RuntimeException('No correct answer!');
        }
        //$this->total_grade_value = ($question_score).'.0000000';
        $this->total_grade_value = $question_score;
    }

    public function on_generate_answers() {

        //add responses holder
        $qresponse_lid = new cc_response_lidtype();
        $this->qresponse_lid = $qresponse_lid;
        $this->qpresentation->set_response_lid($qresponse_lid);
        $qresponse_choice = new cc_assesment_render_choicetype();
        $qresponse_lid->set_render_choice($qresponse_choice);

        //Mark that question has only one correct answer -
        //which applies for multiple choice and yes/no questions
        $qresponse_lid->set_rcardinality(cc_qti_values::Single);

        //are we to shuffle the responses?
        $shuffle_answers = $this->quiz['random_answers'] > 0;

        $qresponse_choice->enable_shuffle($shuffle_answers);
        $answerlist = array();

        $qa_responses = $this->question_node->answers;

        foreach ($qa_responses as $node) {

            $answer_content = $node['answer'];
            $id = $node['id'];

            $result = CcHelpers::processLinkedFiles( $answer_content,
                                                        $this->manifest,
                                                        $this->rootpath,
                                                        $this->contextid,
                                                        $this->outdir);

            $qresponse_label = CcAssesmentHelper::add_answer( $qresponse_choice,
                                                                $result[0],
                                                                cc_qti_values::htmltype);

            PkgResourceDependencies::instance()->add($result[1]);

            $answer_ident = $qresponse_label->get_ident();
            $feedback_ident = $answer_ident.'_fb';
            if (empty($this->correct_answer_ident) && $id) {
                $this->correct_answer_ident = $answer_ident;
            }

            //add answer specific feedbacks if not empty
            $content = $node['comment'];

            if (!empty($content)) {
                $result = CcHelpers::processLinkedFiles( $content,
                                                            $this->manifest,
                                                            $this->rootpath,
                                                            $this->contextid,
                                                            $this->outdir);

                CcAssesmentHelper::add_feedback( $this->qitem,
                                                   $result[0],
                                                   cc_qti_values::htmltype,
                                                   $feedback_ident);

                PkgResourceDependencies::instance()->add($result[1]);
                $answerlist[$answer_ident] = $feedback_ident;
            }
        }
        $this->answerlist = $answerlist;
    }

    public function on_generate_feedbacks() {
        parent::on_generate_feedbacks();

        //Question combined feedbacks
        $correct_question_fb = '';
        $incorrect_question_fb = '';

        if (empty($correct_question_fb)) {
            //Hardcode some text for now
            $correct_question_fb = 'Well done!';
        }
        if (empty($incorrect_question_fb)) {
            //Hardcode some text for now
            $incorrect_question_fb = 'Better luck next time!';
        }

        $proc = array('correct_fb' => $correct_question_fb, 'general_incorrect_fb' => $incorrect_question_fb);
        foreach ($proc as $ident => $content) {
            if (empty($content)) {
                continue;
            }
            $result = CcHelpers::processLinkedFiles( $content,
                                                        $this->manifest,
                                                        $this->rootpath,
                                                        $this->contextid,
                                                        $this->outdir);
            CcAssesmentHelper::add_feedback( $this->qitem,
                                               $result[0],
                                               cc_qti_values::htmltype,
                                               $ident);
            PkgResourceDependencies::instance()->add($result[1]);
            if ($ident == 'correct_fb') {
                $this->correct_feedbacks[] = $ident;
            } else {
                $this->incorrect_feedbacks[] = $ident;
            }
        }
    }

    public function on_generate_response_processing() {
        parent::on_generate_response_processing();

        //respconditions
        /**
         * General unconditional feedback must be added as a first respcondition
         * without any condition and just displayfeedback (if exists)
         */
        if (!empty($this->general_feedback)) {
            $qrespcondition = new cc_assesment_respconditiontype();
            $qrespcondition->setTitle('General feedback');
            $this->qresprocessing->add_respcondition($qrespcondition);
            $qrespcondition->enable_continue();
            //define the condition for success
            $qconditionvar = new cc_assignment_conditionvar();
            $qrespcondition->set_conditionvar($qconditionvar);
            $qother = new cc_assignment_conditionvar_othertype();
            $qconditionvar->set_other($qother);
            $qdisplayfeedback = new cc_assignment_displayfeedbacktype();
            $qrespcondition->add_displayfeedback($qdisplayfeedback);
            $qdisplayfeedback->set_feedbacktype(cc_qti_values::Response);
            $qdisplayfeedback->set_linkrefid('general_fb');
        }

        //success condition
        $qrespcondition = new cc_assesment_respconditiontype();
        $qrespcondition->setTitle('Correct');
        $this->qresprocessing->add_respcondition($qrespcondition);
        $qrespcondition->enable_continue(false);
        $qsetvar = new cc_assignment_setvartype(100);
        $qrespcondition->add_setvar($qsetvar);
        //define the condition for success
        $qconditionvar = new cc_assignment_conditionvar();
        $qrespcondition->set_conditionvar($qconditionvar);
        $qvarequal = new cc_assignment_conditionvar_varequaltype($this->correct_answer_ident);
        $qconditionvar->set_varequal($qvarequal);
        $qvarequal->set_respident($this->qresponse_lid->get_ident());

        if (array_key_exists($this->correct_answer_ident, $this->answerlist)) {
            $qdisplayfeedback = new cc_assignment_displayfeedbacktype();
            $qrespcondition->add_displayfeedback($qdisplayfeedback);
            $qdisplayfeedback->set_feedbacktype(cc_qti_values::Response);
            $qdisplayfeedback->set_linkrefid($this->answerlist[$this->correct_answer_ident]);
        }

        foreach ($this->correct_feedbacks as $ident) {
            $qdisplayfeedback = new cc_assignment_displayfeedbacktype();
            $qrespcondition->add_displayfeedback($qdisplayfeedback);
            $qdisplayfeedback->set_feedbacktype(cc_qti_values::Response);
            $qdisplayfeedback->set_linkrefid($ident);
        }

        //rest of the conditions
        foreach ($this->answerlist as $ident => $refid) {
            if ($ident == $this->correct_answer_ident) {
                continue;
            }

            $qrespcondition = new cc_assesment_respconditiontype();
            $this->qresprocessing->add_respcondition($qrespcondition);
            $qsetvar = new cc_assignment_setvartype(0);
            $qrespcondition->add_setvar($qsetvar);
            //define the condition for fail
            $qconditionvar = new cc_assignment_conditionvar();
            $qrespcondition->set_conditionvar($qconditionvar);
            $qvarequal = new cc_assignment_conditionvar_varequaltype($ident);
            $qconditionvar->set_varequal($qvarequal);
            $qvarequal->set_respident($this->qresponse_lid->get_ident());

            $qdisplayfeedback = new cc_assignment_displayfeedbacktype();
            $qrespcondition->add_displayfeedback($qdisplayfeedback);
            $qdisplayfeedback->set_feedbacktype(cc_qti_values::Response);
            $qdisplayfeedback->set_linkrefid($refid);

            foreach ($this->incorrect_feedbacks as $ident) {
                $qdisplayfeedback = new cc_assignment_displayfeedbacktype();
                $qrespcondition->add_displayfeedback($qdisplayfeedback);
                $qdisplayfeedback->set_feedbacktype(cc_qti_values::Response);
                $qdisplayfeedback->set_linkrefid($ident);
            }
        }
    }
}

class cc_assesment_question_multichoice_multiresponse extends cc_assesment_question_proc_base
{

    protected $correct_answers = null;

    public function __construct($quiz, $questions, $manifest, $section, $question_node, $rootpath, $contextid, $outdir) {
        parent::__construct($quiz, $questions, $manifest, $section, $question_node, $rootpath, $contextid, $outdir);
        $this->qtype = cc_qti_profiletype::multiple_response;

        $correct_answer_nodes = [];
        $question_score = 0;
        foreach ($question_node->answers as $answer) {
            if ($answer['correct'] > 0) {
                $correct_answer_nodes[] = $answer;
                $question_score += $answer['ponderation'];
            }
        }
        if (count($correct_answer_nodes) == 0) {
            throw new RuntimeException('No correct answer!');
        }
        $this->correct_answers = $correct_answer_nodes;
        $this->total_grade_value = $question_score;
    }

    public function on_generate_answers() {
        //add responses holder
        $qresponse_lid = new cc_response_lidtype();
        $this->qresponse_lid = $qresponse_lid;
        $this->qpresentation->set_response_lid($qresponse_lid);
        $qresponse_choice = new cc_assesment_render_choicetype();
        $qresponse_lid->set_render_choice($qresponse_choice);
        //Mark that question has more than one correct answer
        $qresponse_lid->set_rcardinality(cc_qti_values::Multiple);
        //are we to shuffle the responses?

        $shuffle_answers = $this->quiz['random_answers'] > 0;
        $qresponse_choice->enable_shuffle($shuffle_answers);
        $answerlist = array();

        $qa_responses = $this->question_node->answers;
        foreach ($qa_responses as $node) {

            $answer_content = $node['answer'];
            $answer_grade_fraction = $node['ponderation'];

            $result = CcHelpers::processLinkedFiles( $answer_content,
                                                        $this->manifest,
                                                        $this->rootpath,
                                                        $this->contextid,
                                                        $this->outdir);

            $qresponse_label = CcAssesmentHelper::add_answer( $qresponse_choice,
                                                                $result[0],
                                                                cc_qti_values::htmltype);

            PkgResourceDependencies::instance()->add($result[1]);

            $answer_ident = $qresponse_label->get_ident();
            $feedback_ident = $answer_ident.'_fb';
            //add answer specific feedbacks if not empty
            $content = $node['comment'];
            if (!empty($content)) {
                $result = CcHelpers::processLinkedFiles( $content,
                                                            $this->manifest,
                                                            $this->rootpath,
                                                            $this->contextid,
                                                            $this->outdir);

                CcAssesmentHelper::add_feedback( $this->qitem,
                                                    $result[0],
                                                    cc_qti_values::htmltype,
                                                    $feedback_ident);

                PkgResourceDependencies::instance()->add($result[1]);
            }
            $answerlist[$answer_ident] = array($feedback_ident, ($answer_grade_fraction > 0));
        }
        $this->answerlist = $answerlist;
    }

    public function on_generate_feedbacks() {
        parent::on_generate_feedbacks();
        //Question combined feedbacks
        $correct_question_fb = '';
        $incorrect_question_fb = '';
        if (empty($correct_question_fb)) {
            //Hardcode some text for now
            $correct_question_fb = 'Well done!';
        }
        if (empty($incorrect_question_fb)) {
            //Hardcode some text for now
            $incorrect_question_fb = 'Better luck next time!';
        }

        $proc = array('correct_fb' => $correct_question_fb, 'incorrect_fb' => $incorrect_question_fb);
        foreach ($proc as $ident => $content) {
            if (empty($content)) {
                continue;
            }
            $result = CcHelpers::processLinkedFiles( $content,
                                                        $this->manifest,
                                                        $this->rootpath,
                                                        $this->contextid,
                                                        $this->outdir);

            CcAssesmentHelper::add_feedback( $this->qitem,
                                                $result[0],
                                                cc_qti_values::htmltype,
                                                $ident);

            PkgResourceDependencies::instance()->add($result[1]);
            if ($ident == 'correct_fb') {
                $this->correct_feedbacks[$ident] = $ident;
            } else {
                $this->incorrect_feedbacks[$ident] = $ident;
            }
        }

    }

    public function on_generate_response_processing() {
        parent::on_generate_response_processing();

        //respconditions
        /**
        * General unconditional feedback must be added as a first respcondition
        * without any condition and just displayfeedback (if exists)
        */
        CcAssesmentHelper::add_respcondition( $this->qresprocessing,
                                                'General feedback',
                                                $this->general_feedback,
                                                null,
                                                true
                                               );

        $qrespcondition = new cc_assesment_respconditiontype();
        $qrespcondition->setTitle('Correct');
        $this->qresprocessing->add_respcondition($qrespcondition);
        $qrespcondition->enable_continue(false);
        $qsetvar = new cc_assignment_setvartype(100);
        $qrespcondition->add_setvar($qsetvar);
        //define the condition for success
        $qconditionvar = new cc_assignment_conditionvar();
        $qrespcondition->set_conditionvar($qconditionvar);
        //create root and condition
        $qandcondition = new cc_assignment_conditionvar_andtype();
        $qconditionvar->set_and($qandcondition);
        foreach ($this->answerlist as $ident => $refid) {
            $qvarequal = new cc_assignment_conditionvar_varequaltype($ident);
            $qvarequal->enable_case();
            if ($refid[1]) {
                $qandcondition->set_varequal($qvarequal);
            } else {
                $qandcondition->set_not($qvarequal);
            }
            $qvarequal->set_respident($this->qresponse_lid->get_ident());
        }

        $qdisplayfeedback = new cc_assignment_displayfeedbacktype();
        $qrespcondition->add_displayfeedback($qdisplayfeedback);
        $qdisplayfeedback->set_feedbacktype(cc_qti_values::Response);
        //TODO: this needs to be fixed
        reset($this->correct_feedbacks);
        $ident = key($this->correct_feedbacks);
        $qdisplayfeedback->set_linkrefid($ident);


        //rest of the conditions
        foreach ($this->answerlist as $ident => $refid) {
            CcAssesmentHelper::add_response_condition( $this->qresprocessing,
                                                         'Incorrect feedback',
                                                         $refid[0],
                                                         $this->general_feedback,
                                                         $this->qresponse_lid->get_ident()
                                                       );
        }

        //Final element for incorrect feedback
        reset($this->incorrect_feedbacks);
        $ident = key($this->incorrect_feedbacks);
        CcAssesmentHelper::add_respcondition( $this->qresprocessing,
                                                'Incorrect feedback',
                                                $ident,
                                                0
                                              );

    }

}
