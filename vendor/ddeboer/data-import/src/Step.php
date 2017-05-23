<?php

namespace Ddeboer\DataImport;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
interface Step
{
    /**
     * Any processing done on each item in the data stack
     *
     * @param mixed &$item
     *
     * @return boolean False return value means the item should be skipped
     */
    public function process(&$item);
}
