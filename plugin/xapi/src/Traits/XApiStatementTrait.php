<?php
/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User as UserEntity;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\Uuid;
use Xabbuh\XApi\Model\Verb;

/**
 * Trait XApiStatementTrait.
 */
trait XApiStatementTrait
{
    /**
     * @param UserEntity $user
     *
     * @return \Xabbuh\XApi\Model\Agent
     */
    protected function generateActor(UserEntity $user)
    {
        $mboxIri = IRI::fromString(
            'mailto:'.$user->getEmail()
        );

        return new Agent(
            InverseFunctionalIdentifier::withMbox($mboxIri),
            $user->getCompleteName()
        );
    }

    /**
     * @param string $word
     * @param string $uri
     *
     * @return \Xabbuh\XApi\Model\Verb
     */
    protected function generateVerb($word, $uri)
    {
        $languageMap = XApiPlugin::create()->getLangMap($word);

        return new Verb(
            IRI::fromString($uri),
            LanguageMap::create($languageMap)
        );
    }

    /**
     * @param string $type
     * @param string $value
     *
     * @return \Xabbuh\XApi\Model\StatementId
     */
    protected function generateId($type, $value)
    {
        $uuid = Uuid::uuid5(
            XApiPlugin::create()->get(XApiPlugin::SETTING_UUID_NAMESPACE),
            "$type/$value"
        );

        return StatementId::fromUuid($uuid);
    }
}
