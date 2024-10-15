<?php

namespace Laravel\Passport\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;
use Laravel\Passport\Http\Controllers\ConvertsPsrResponses;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ResponseInterface;

class OAuthServerException extends HttpResponseException
{
    use ConvertsPsrResponses;

    /**
     * Create a new OAuthServerException.
     */
    public function __construct(LeagueException $e, bool $useFragment = false)
    {
        parent::__construct($this->convertResponse(
            $e->generateHttpResponse(app(ResponseInterface::class), $useFragment)
        ), $e);
    }

    /**
     * Create a new OAuthServerException for when login is required.
     */
    public static function loginRequired(AuthorizationRequestInterface $authRequest): static
    {
        $exception = new LeagueException(
            'The authorization server requires end-user authentication.',
            9,
            'login_required',
            401,
            'The user is not authenticated',
            $authRequest->getRedirectUri() ?? Arr::wrap($authRequest->getClient()->getRedirectUri())[0]
        );

        $exception->setPayload([
            'state' => $authRequest->getState(),
            ...$exception->getPayload(),
        ]);

        return new static($exception, $authRequest->getGrantTypeId() === 'implicit');
    }

    /**
     * Create a new OAuthServerException for when consent is required.
     */
    public static function consentRequired(AuthorizationRequestInterface $authRequest): static
    {
        $exception = new LeagueException(
            'The authorization server requires end-user consent.',
            9,
            'consent_required',
            401,
            null,
            $authRequest->getRedirectUri() ?? Arr::wrap($authRequest->getClient()->getRedirectUri())[0]
        );

        $exception->setPayload([
            'state' => $authRequest->getState(),
            ...$exception->getPayload(),
        ]);

        return new static($exception, $authRequest->getGrantTypeId() === 'implicit');
    }
}
