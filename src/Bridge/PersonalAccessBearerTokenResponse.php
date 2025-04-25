<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;

class PersonalAccessBearerTokenResponse extends BearerTokenResponse
{
    /**
     * {@inheritdoc}
     */
    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        return [
            'access_token_id' => $accessToken->getIdentifier(),
        ];
    }
}
