<?php

namespace Laravel\Passport\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Passport\Client;

class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => null,
            'name' => $this->faker->company,
            'secret' => Str::random(40),
            'redirect' => $this->faker->url,
            'personal_access_client' => false,
            'password_client' => false,
            'revoked' => false,
        ];
    }

    /**
     * Use as Password Client.
     *
     * @return $this
     */
    public function asPasswordClient()
    {
        return $this->state([
            'personal_access_client' => false,
            'password_client' => true,
        ]);
    }

    /**
     * Use as Client Credentials.
     *
     * @return $this
     */
    public function asClientCredentials()
    {
        return $this->state([
            'personal_access_client' => false,
            'password_client' => false,
        ]);
    }
}
