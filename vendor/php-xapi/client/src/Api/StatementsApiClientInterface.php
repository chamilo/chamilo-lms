<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Client\Api;

use Xabbuh\XApi\Common\Exception\ConflictException;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\Common\Exception\XApiException;
use Xabbuh\XApi\Model\Actor;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementResult;
use Xabbuh\XApi\Model\StatementsFilter;

/**
 * Client to access the statements API of an xAPI based learning record store.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
interface StatementsApiClientInterface
{
    /**
     * Stores a single {@link Statement}.
     *
     * @param Statement $statement The Statement to store
     *
     * @return Statement The Statement as it has been stored in the remote LRS,
     *                   this is not necessarily the same object that was
     *                   passed to storeStatement()
     *
     * @throws ConflictException if a Statement with the given id already exists
     *                           and the given Statement does not match the
     *                           stored Statement
     * @throws XApiException     for all other xAPI related problems
     */
    public function storeStatement(Statement $statement);

    /**
     * Stores a collection of {@link Statement Statements}.
     *
     * @param Statement[] $statements The statements to store
     *
     * @return Statement[] The stored Statements
     *
     * @throws \InvalidArgumentException if a given object is no Statement or
     *                                   if one of the Statements has an id
     * @throws XApiException             for all other xAPI related problems
     */
    public function storeStatements(array $statements);

    /**
     * Marks a {@link Statement} as voided.
     *
     * @param Statement $statement The Statement to void
     * @param Actor     $actor     The Actor voiding the given Statement
     *
     * @return Statement The Statement sent to the remote LRS to void the
     *                   given Statement
     *
     * @throws XApiException for all other xAPI related problems
     */
    public function voidStatement(Statement $statement, Actor $actor);

    /**
     * Retrieves a single {@link Statement Statement}.
     *
     * @param StatementId $statementId The Statement id
     * @param bool        $attachments Whether or not to request raw attachment data
     *
     * @return Statement The Statement
     *
     * @throws NotFoundException if no statement with the given id could be found
     * @throws XApiException     for all other xAPI related problems
     */
    public function getStatement(StatementId $statementId, $attachments = true);

    /**
     * Retrieves a voided {@link Statement Statement}.
     *
     * @param StatementId $statementId The id of the voided Statement
     * @param bool        $attachments Whether or not to request raw attachment data
     *
     * @return Statement The voided Statement
     *
     * @throws NotFoundException if no statement with the given id could be found
     * @throws XApiException     for all other xAPI related problems
     */
    public function getVoidedStatement(StatementId $statementId, $attachments = true);

    /**
     * Retrieves a collection of {@link Statement Statements}.
     *
     * @param StatementsFilter $filter      Optional Statements filter
     * @param bool             $attachments Whether or not to request raw attachment data
     *
     * @return StatementResult The {@link StatementResult}
     *
     * @throws XApiException in case of any problems related to the xAPI
     */
    public function getStatements(StatementsFilter $filter = null, $attachments = true);

    /**
     * Returns the next {@link Statement Statements} for a limited Statement
     * result.
     *
     * @param StatementResult $statementResult The former StatementResult
     *
     * @return StatementResult The {@link StatementResult}
     *
     * @throws XApiException in case of any problems related to the xAPI
     */
    public function getNextStatements(StatementResult $statementResult);
}
