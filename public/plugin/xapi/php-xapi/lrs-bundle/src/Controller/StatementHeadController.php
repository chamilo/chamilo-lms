<?php

/*
 * This file is part of the xAPI package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\LrsBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StatementHeadController extends StatementGetController
{
    /**
     * @throws BadRequestHttpException if the query parameters does not comply with xAPI specification
     *
     * @return Response
     */
    public function getStatement(Request $request)
    {
        return parent::getStatement($request)->setContent('');
    }
}
