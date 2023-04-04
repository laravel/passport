<?php

namespace Laravel\Passport\Bridge;

use DateInterval;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

class PersonalAccessGrant extends AbstractGrant
{
    /**
     * {@inheritdoc}
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ) {
        // Validate request
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request));
        $userIdentifier = $this->getRequestParameter('user_id', $request);

        // Finalize the requested scopes
        $scopes = $this->scopeRepository->finalizeScopes(
            $scopes,
            $this->getIdentifier(),
            $client,
            $userIdentifier
        );

        // Issue and persist access token
        $accessToken = $this->issueAccessToken(
            $accessTokenTTL,
            $client,
            $userIdentifier,
            $scopes
        );

        // Inject access token into response type
        $responseType->setAccessToken($accessToken);

        return $responseType;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'personal_access';
    }
}
