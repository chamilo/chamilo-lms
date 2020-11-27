<?php

namespace spec\XApi\LrsBundle\Controller;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\DataFixtures\StatementFixtures;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementResult;
use Xabbuh\XApi\Model\StatementsFilter;
use Xabbuh\XApi\Serializer\StatementResultSerializerInterface;
use Xabbuh\XApi\Serializer\StatementSerializerInterface;
use XApi\Fixtures\Json\StatementJsonFixtures;
use XApi\Fixtures\Json\StatementResultJsonFixtures;
use XApi\LrsBundle\Model\StatementsFilterFactory;
use XApi\Repository\Api\StatementRepositoryInterface;

class StatementGetControllerSpec extends ObjectBehavior
{
    function let(StatementRepositoryInterface $repository, StatementSerializerInterface $statementSerializer, StatementResultSerializerInterface $statementResultSerializer, StatementsFilterFactory $statementsFilterFactory)
    {
        $statement = StatementFixtures::getAllPropertiesStatement();
        $voidedStatement = StatementFixtures::getVoidingStatement()->withStored(new \DateTime());
        $statementCollection = StatementFixtures::getStatementCollection();
        $statementsFilter = new StatementsFilter();

        $statementsFilterFactory->createFromParameterBag(Argument::type('\Symfony\Component\HttpFoundation\ParameterBag'))->willReturn($statementsFilter);

        $repository->findStatementById(StatementId::fromString(StatementFixtures::DEFAULT_STATEMENT_ID))->willReturn($statement);
        $repository->findVoidedStatementById(StatementId::fromString(StatementFixtures::DEFAULT_STATEMENT_ID))->willReturn($voidedStatement);
        $repository->findStatementsBy($statementsFilter)->willReturn($statementCollection);

        $statementSerializer->serializeStatement(Argument::type('\Xabbuh\XApi\Model\Statement'))->willReturn(StatementJsonFixtures::getTypicalStatement());

        $statementResultSerializer->serializeStatementResult(Argument::type('\Xabbuh\XApi\Model\StatementResult'))->willReturn(StatementResultJsonFixtures::getStatementResult());

        $this->beConstructedWith($repository, $statementSerializer, $statementResultSerializer, $statementsFilterFactory);
    }

    function it_throws_a_badrequesthttpexception_if_the_request_has_given_statement_id_and_voided_statement_id()
    {
        $request = new Request();
        $request->query->set('statementId', StatementFixtures::DEFAULT_STATEMENT_ID);
        $request->query->set('voidedStatementId', StatementFixtures::DEFAULT_STATEMENT_ID);

        $this
            ->shouldThrow('\Symfony\Component\HttpKernel\Exception\BadRequestHttpException')
            ->during('getStatement', array($request));
    }

    function it_throws_a_badrequesthttpexception_if_the_request_has_statement_id_and_format_and_attachements_and_any_other_parameters()
    {
        $request = new Request();
        $request->query->set('statementId', StatementFixtures::DEFAULT_STATEMENT_ID);
        $request->query->set('format', 'ids');
        $request->query->set('attachments', false);
        $request->query->set('related_agents', false);

        $this
            ->shouldThrow('\Symfony\Component\HttpKernel\Exception\BadRequestHttpException')
            ->during('getStatement', array($request));
    }

    function it_throws_a_badrequesthttpexception_if_the_request_has_voided_statement_id_and_format_and_any_other_parameters_except_attachments()
    {
        $request = new Request();
        $request->query->set('voidedStatementId', StatementFixtures::DEFAULT_STATEMENT_ID);
        $request->query->set('format', 'ids');
        $request->query->set('related_agents', false);

        $this
            ->shouldThrow('\Symfony\Component\HttpKernel\Exception\BadRequestHttpException')
            ->during('getStatement', array($request));
    }

    function it_throws_a_badrequesthttpexception_if_the_request_has_statement_id_and_attachments_and_any_other_parameters_except_format()
    {
        $request = new Request();
        $request->query->set('statementId', StatementFixtures::DEFAULT_STATEMENT_ID);
        $request->query->set('attachments', false);
        $request->query->set('related_agents', false);

        $this
            ->shouldThrow('\Symfony\Component\HttpKernel\Exception\BadRequestHttpException')
            ->during('getStatement', array($request));
    }

    function it_throws_a_badrequesthttpexception_if_the_request_has_voided_statement_id_and_any_other_parameters_except_format_and_attachments()
    {
        $request = new Request();
        $request->query->set('voidedStatementId', StatementFixtures::DEFAULT_STATEMENT_ID);
        $request->query->set('related_agents', false);

        $this
            ->shouldThrow('\Symfony\Component\HttpKernel\Exception\BadRequestHttpException')
            ->during('getStatement', array($request));
    }

    function it_sets_a_X_Experience_API_Consistent_Through_header_to_the_response()
    {
        $request = new Request();
        $request->query->set('statementId', StatementFixtures::DEFAULT_STATEMENT_ID);

        $response = $this->getStatement($request);

        /** @var ResponseHeaderBag $headers */
        $headers = $response->headers;

        $headers->has('X-Experience-API-Consistent-Through')->shouldBe(true);
    }

    function it_includes_a_Last_Modified_Header_if_a_single_statement_is_fetched()
    {
        $request = new Request();
        $request->query->set('statementId', StatementFixtures::DEFAULT_STATEMENT_ID);

        $response = $this->getStatement($request);

        /** @var ResponseHeaderBag $headers */
        $headers = $response->headers;

        $headers->has('Last-Modified')->shouldBe(true);

        $request = new Request();
        $request->query->set('voidedStatementId', StatementFixtures::DEFAULT_STATEMENT_ID);

        $response = $this->getStatement($request);

        /** @var ResponseHeaderBag $headers */
        $headers = $response->headers;

        $headers->has('Last-Modified')->shouldBe(true);
    }

    function it_returns_a_multipart_response_if_attachments_parameter_is_true()
    {
        $request = new Request();
        $request->query->set('attachments', true);

        $this->getStatement($request)->shouldReturnAnInstanceOf('XApi\LrsBundle\Response\MultipartResponse');
    }

    function it_returns_a_jsonresponse_if_attachments_parameter_is_false_or_not_set()
    {
        $request = new Request();

        $this->getStatement($request)->shouldReturnAnInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse');

        $request->query->set('attachments', false);

        $this->getStatement($request)->shouldReturnAnInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse');
    }

    function it_should_fetch_a_statement(StatementRepositoryInterface $repository)
    {
        $request = new Request();
        $request->query->set('statementId', StatementFixtures::DEFAULT_STATEMENT_ID);

        $repository->findStatementById(StatementId::fromString(StatementFixtures::DEFAULT_STATEMENT_ID))->shouldBeCalled();

        $this->getStatement($request);
    }

    function it_should_fetch_a_voided_statement_id(StatementRepositoryInterface $repository)
    {
        $request = new Request();
        $request->query->set('voidedStatementId', StatementFixtures::DEFAULT_STATEMENT_ID);

        $repository->findVoidedStatementById(StatementId::fromString(StatementFixtures::DEFAULT_STATEMENT_ID))->shouldBeCalled();

        $this->getStatement($request);
    }

    function it_should_filter_all_statements_if_no_statement_id_or_voided_statement_id_is_provided(StatementRepositoryInterface $repository)
    {
        $request = new Request();

        $repository->findStatementsBy(Argument::type('\Xabbuh\XApi\Model\StatementsFilter'))->shouldBeCalled();

        $this->getStatement($request);
    }

    function it_should_build_an_empty_statement_result_response_if_no_statement_is_found(StatementRepositoryInterface $repository, StatementResultSerializerInterface $statementResultSerializer)
    {
        $request = new Request();
        $request->query->set('statementId', StatementFixtures::DEFAULT_STATEMENT_ID);

        $repository->findStatementById(StatementId::fromString(StatementFixtures::DEFAULT_STATEMENT_ID))->willThrow('\Xabbuh\XApi\Common\Exception\NotFoundException');

        $statementResultSerializer->serializeStatementResult(new StatementResult(array()))->shouldBeCalled()->willReturn(StatementResultJsonFixtures::getStatementResult());

        $this->getStatement($request);
    }
}
