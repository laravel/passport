<?php

namespace Laravel\Passport\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Laravel\Passport\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Get the name of the model that is generated by the factory.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    public function modelName()
    {
        return $this->model ?? Passport::clientModel();
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => null,
            'name' => $this->faker->company(),
            'secret' => Str::random(40),
            'redirect_uris' => [$this->faker->url()],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'revoked' => false,
        ];
    }

    /**
     * Use as a Password client.
     *
     * @return $this
     */
    public function asPasswordClient()
    {
        return $this->state([
            'grant_types' => ['password', 'refresh_token'],
        ]);
    }

    /**
     * Use as a Personal Access Token client.
     *
     * @return $this
     */
    public function asPersonalAccessTokenClient()
    {
        return $this->state([
            'grant_types' => ['personal_access'],
        ]);
    }

    /**
     * Use as a Client Credentials client.
     *
     * @return $this
     */
    public function asClientCredentials()
    {
        return $this->state([
            'grant_types' => ['client_credentials'],
        ]);
    }

    /**
     * Use as a Device Code client.
     */
    public function asDeviceCodeClient(): static
    {
        return $this->state([
            'grant_types' => ['urn:ietf:params:oauth:grant-type:device_code', 'refresh_token'],
        ]);
    }
}
