<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\LrsBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Xabbuh\XApi\Model\Statement;

/**
 * @author Jérôme Parmentier <jerome.parmentier@acensi.fr>
 */
final class StatementPostController
{
    public function postStatement(Request $request, Statement $statement)
    {
    }

    /**
     * @param Statement[] $statements
     */
    public function postStatements(Request $request, array $statements)
    {
    }
}
