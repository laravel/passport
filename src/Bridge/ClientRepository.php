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
     * Create a new repository instance.
     */
    public function __construct(
        protected ClientModelRepository $clients,
        protected Hasher $hasher
    ) {
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
        $record = $this->clients->findActive($clientIdentifier);

        return $record && ! empty($clientSecret) && $this->hasher->check($clientSecret, $record->secret);
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
            $model->grant_types
        );
    }
}
