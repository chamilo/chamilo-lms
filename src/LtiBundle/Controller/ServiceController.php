<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\IntegrationBundle\Controller;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\IntegrationBundle\Component\OutcomeDeleteRequest;
use Chamilo\IntegrationBundle\Component\OutcomeReadRequest;
use Chamilo\IntegrationBundle\Component\OutcomeReplaceRequest;
use Chamilo\IntegrationBundle\Component\OutcomeUnsupportedRequest;
use Chamilo\IntegrationBundle\Entity\ExternalTool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ServicesController.
 *
 * @package Chamilo\IntegrationBundle\Controller
 */
class ServiceController extends BaseController
{
    /**
     * @Route("/lti/os", name="chamilo_lti_os")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function outcomeServiceAction(Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        $toolRepo = $em->getRepository('ChamiloIntegrationBundle:ExternalTool');

        $headers = \OAuthUtil::get_headers();

        if (empty($headers['Authorization'])) {
            throw $this->createAccessDeniedException();
        }

        $authParams = \OAuthUtil::split_header($headers['Authorization']);

        if (empty($authParams) || empty($authParams['oauth_consumer_key']) || empty($authParams['oauth_signature'])) {
            throw $this->createAccessDeniedException();
        }

        $course = $this->getCourse();
        $tools = $toolRepo->findBy(['consumerKey' => $authParams['oauth_consumer_key']]);
        $url = $this->generateUrl('chamilo_lti_os', ['code' => $course->getCode()]);

        $toolIsFound = false;

        /** @var ExternalTool $tool */
        foreach ($tools as $tool) {
            $signatureIsValid = $this->compareRequestSignature(
                $url,
                $authParams['oauth_consumer_key'],
                $authParams['oauth_signature'],
                $tool
            );

            if ($signatureIsValid) {
                $toolIsFound = true;

                break;
            }
        }

        if (!$toolIsFound) {
            throw $this->createNotFoundException('External tool not found');
        }

        $body = file_get_contents('php://input');
        $bodyHash = base64_encode(sha1($body, true));

        if ($bodyHash !== $authParams['oauth_body_hash']) {
            throw $this->createAccessDeniedException('Request is not valid.');
        }

        $process = $this->processServiceRequest();

        $response = new Response($process);
        $response->headers->set('Content-Type', 'application/xml');

        return $response;
    }

    /**
     * @return \Chamilo\IntegrationBundle\Component\OutcomeResponse|null
     */
    private function processServiceRequest()
    {
        $requestContent = file_get_contents('php://input');

        if (empty($requestContent)) {
            return null;
        }

        $xml = new \SimpleXMLElement($requestContent);

        if (empty($xml)) {
            return null;
        }

        $bodyChildren = $xml->imsx_POXBody->children();

        if (empty($bodyChildren)) {
            return null;
        }

        $name = $bodyChildren->getName();

        switch ($name) {
            case 'replaceResultRequest':
                $serviceRequest = new OutcomeReplaceRequest($xml);
                break;
            case 'readResultRequest':
                $serviceRequest = new OutcomeReadRequest($xml);
                break;
            case 'deleteResultRequest':
                $serviceRequest = new OutcomeDeleteRequest($xml);
                break;
            default:
                $name = str_replace(['ResultRequest', 'Request'], '', $name);

                $serviceRequest = new OutcomeUnsupportedRequest($xml, $name);
                break;
        }

        $serviceRequest->setEntityManager($this->getDoctrine()->getManager());
        $serviceRequest->setTranslator($this->get('translator'));

        return $serviceRequest->process();
    }
}
