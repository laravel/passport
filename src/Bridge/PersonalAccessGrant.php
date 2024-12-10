<?php

namespace Laravel\Passport\Bridge;

use DateInterval;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
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
    ): ResponseTypeInterface {
        // Validate request
        if (! $userIdentifier = $this->getRequestParameter('user_id', $request)) {
            throw OAuthServerException::invalidRequest('user_id');
        }

        if (! $provider = $this->getRequestParameter('provider', $request)) {
            throw OAuthServerException::invalidRequest('provider');
        }

        $client = $this->clientRepository->getPersonalAccessClientEntity($provider);

        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request, $this->defaultScope));

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

        // Send event to emitter
        $this->getEmitter()->emit(new RequestAccessTokenEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request, $accessToken));

        // Persist access token's name
        Passport::token()->newQuery()->whereKey($accessToken->getIdentifier())->update([
            'name' => $this->getRequestParameter('name', $request),
        ]);

        // Inject access token into response type
        $responseType->setAccessToken($accessToken);

        return $responseType;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return 'personal_access';
    }
}
