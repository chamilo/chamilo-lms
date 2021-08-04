<?php
/* For licensing terms, see /license.txt */

/**
 * Class PortfolioLink.
 */
class PortfolioLink extends EvalLink
{
    public function __construct()
    {
        parent::__construct();

        $this->set_type(LINK_PORTFOLIO);
    }

    public function get_type_name()
    {
        return get_lang('Portfolio');
    }

    public function is_allowed_to_change_name()
    {
        return false;
    }

    public function get_icon_name()
    {
        return 'portfolio';
    }

    protected function get_evaluation()
    {
        $this->evaluation = parent::get_evaluation();
        $this->evaluation->set_type('portfolio');

        return $this->evaluation;
    }
}
