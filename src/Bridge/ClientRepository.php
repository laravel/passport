<?php

namespace Laravel\Passport\Bridge;

use Illuminate\Contracts\Hashing\Hasher;
use Laravel\Passport\Client as ClientModel;
use Laravel\Passport\ClientRepository as ClientModelRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * The client model repository.
     */
    protected ClientModelRepository $clients;

    /**
     * The hasher implementation.
     */
    protected Hasher $hasher;

    /**
     * Create a new repository instance.
     */
    public function __construct(ClientModelRepository $clients, Hasher $hasher)
    {
        $this->clients = $clients;
        $this->hasher = $hasher;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        $record = $this->clients->findActive($clientIdentifier);

        return $record ? $this->fromClientModel($record) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        // First, we will verify that the client exists and is authorized to create personal
        // access tokens. Generally personal access tokens are only generated by the user
        // from the main interface. We'll only let certain clients generate the tokens.
        $record = $this->clients->findActive($clientIdentifier);

        if (! $record || ! $this->handlesGrant($record, $grantType)) {
            return false;
        }

        return ! $record->confidential() || $this->verifySecret($clientSecret, $record->secret);
    }

    /**
     * Determine if the given client can handle the given grant type.
     */
    protected function handlesGrant(ClientModel $record, string $grantType): bool
    {
        return $record->hasGrantType($grantType);
    }

    /**
     * Verify the client secret is valid.
     */
    protected function verifySecret(string $clientSecret, string $storedHash): bool
    {
        return $this->hasher->check($clientSecret, $storedHash);
    }

    /**
     * Get the personal access client for the given provider.
     */
    public function getPersonalAccessClientEntity(string $provider): ?ClientEntityInterface
    {
        return $this->fromClientModel(
            $this->clients->personalAccessClient($provider)
        );
    }

    /**
     * Create a new client entity from the given client model instance.
     */
    protected function fromClientModel(ClientModel $model): ClientEntityInterface
    {
        return new Client(
            $model->getKey(),
            $model->name,
            $model->redirect_uris,
            $model->confidential(),
            $model->provider,
            isset($model['scopes']) ? $model->scopes : null
        );
    }
}
