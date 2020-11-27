<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\LrsBundle\Model;

use Symfony\Component\HttpFoundation\ParameterBag;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\StatementsFilter;
use Xabbuh\XApi\Model\Verb;
use Xabbuh\XApi\Serializer\ActorSerializerInterface;

/**
 * @author Jérôme Parmentier <jerome.parmentier@acensi.fr>
 */
class StatementsFilterFactory
{
    private $actorSerializer;

    public function __construct(ActorSerializerInterface $actorSerializer)
    {
        $this->actorSerializer = $actorSerializer;
    }

    /**
     * @return StatementsFilter
     */
    public function createFromParameterBag(ParameterBag $parameters)
    {
        $filter = new StatementsFilter();

        if (($actor = $parameters->get('agent')) !== null) {
            $filter->byActor($this->actorSerializer->deserializeActor($actor));
        }

        if (($verbId = $parameters->get('verb')) !== null) {
            $filter->byVerb(new Verb(IRI::fromString($verbId)));
        }

        if (($activityId = $parameters->get('activity')) !== null) {
            $filter->byActivity(new Activity(IRI::fromString($activityId)));
        }

        if (($registration = $parameters->get('registration')) !== null) {
            $filter->byRegistration($registration);
        }

        if ($parameters->filter('related_activities', false, FILTER_VALIDATE_BOOLEAN)) {
            $filter->enableRelatedActivityFilter();
        } else {
            $filter->disableRelatedActivityFilter();
        }

        if ($parameters->filter('related_agents', false, FILTER_VALIDATE_BOOLEAN)) {
            $filter->enableRelatedAgentFilter();
        } else {
            $filter->disableRelatedAgentFilter();
        }

        if (($since = $parameters->get('since')) !== null) {
            $filter->since(\DateTime::createFromFormat(\DateTime::ATOM, $since));
        }

        if (($until = $parameters->get('until')) !== null) {
            $filter->until(\DateTime::createFromFormat(\DateTime::ATOM, $until));
        }

        if ($parameters->filter('ascending', false, FILTER_VALIDATE_BOOLEAN)) {
            $filter->ascending();
        } else {
            $filter->descending();
        }

        $filter->limit($parameters->getInt('limit'));

        return $filter;
    }
}
