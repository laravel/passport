<?php
/**
 * OAuth 2.0 Client credentials grant with support for Laravel Passport's Client entity.
 *
 * @author      Alex Bilbie <hello@alexbilbie.com>, Paul Chiu <paulchiu@gmail.com>
 * @copyright   Copyright (c) Alex Bilbie
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */

namespace Laravel\Passport\Bridge;

use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;

/**
 * Client credentials grant class.
 */
class ClientCredentialsGrant extends AbstractGrant
{
    /**
     * {@inheritdoc}
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        \DateInterval $accessTokenTTL
    ) {
        // Validate request
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request));

        // Get user id if available
        $userId = null;
        if ($client instanceof UserAwareInterface && $client->getUser()) {
            $userId = $client->getUser()->getIdentifier();
        }

        // Finalize the requested scopes
        $scopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client);

        // Issue and persist access token
        $accessToken = $this->issueAccessToken(
            $accessTokenTTL,
            $client,
            $userId,
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
        return 'client_credentials';
    }
}
