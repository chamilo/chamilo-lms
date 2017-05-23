<?php

namespace Ddeboer\DataImport\Step;

use Ddeboer\DataImport\Step;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
interface PriorityStep extends Step
{
    /**
     * @return integer
     */
    public function getPriority();
}
