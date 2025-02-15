<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\Client;
use Orchestra\Testbench\TestCase;

final class ClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Model::preventAccessingMissingAttributes();
    }

    protected function tearDown(): void
    {
        Model::preventAccessingMissingAttributes(false);

        parent::tearDown();
    }

    public function testScopesWhenClientDoesNotHaveScope(): void
    {
        $client = new Client(['scopes' => ['bar']]);
        $client->exists = true;

        $this->assertFalse($client->hasScope('foo'));
    }

    public function testScopesWhenClientHasScope(): void
    {
        $client = new Client(['scopes' => ['foo', 'bar']]);
        $client->exists = true;

        $this->assertTrue($client->hasScope('foo'));
    }

    public function testScopesWhenColumnDoesNotExist(): void
    {
        $client = new Client();
        $client->exists = true;

        $this->assertTrue($client->hasScope('foo'));
    }

    public function testScopesWhenColumnIsNull(): void
    {
        $client = new Client(['scopes' => null]);
        $client->exists = true;

        $this->assertTrue($client->hasScope('foo'));
    }

    public function testGrantTypesWhenClientDoesNotHaveGrantType(): void
    {
        $client = new Client(['grant_types' => ['bar']]);
        $client->exists = true;

        $this->assertFalse($client->hasGrantType('foo'));
    }

    public function testGrantTypesWhenClientHasGrantType(): void
    {
        $client = new Client(['grant_types' => ['foo', 'bar']]);
        $client->exists = true;

        $this->assertTrue($client->hasGrantType('foo'));
    }

    public function testGrantTypesWhenColumnDoesNotExist(): void
    {
        $client = new Client();
        $client->exists = true;

        $this->assertTrue($client->hasGrantType('foo'));

        $client->personal_access_client = false;
        $client->password_client = false;

        $this->assertTrue($client->hasGrantType('authorization_code'));

        $client->personal_access_client = false;
        $client->password_client = true;

        $this->assertTrue($client->hasGrantType('password'));

        $client->personal_access_client = true;
        $client->password_client = false;
        $client->secret = 'secret';

        $this->assertTrue($client->hasGrantType('personal_access'));
        $this->assertTrue($client->hasGrantType('client_credentials'));
    }

    public function testGrantTypesWhenColumnIsNull(): void
    {
        $client = new Client(['grant_types' => null]);
        $client->exists = true;

        $this->assertTrue($client->hasGrantType('foo'));

        $client->personal_access_client = false;
        $client->password_client = false;
        $this->assertTrue($client->hasGrantType('authorization_code'));

        $client->personal_access_client = false;
        $client->password_client = true;
        $this->assertTrue($client->hasGrantType('password'));

        $client->personal_access_client = true;
        $client->password_client = false;
        $client->secret = 'secret';
        $this->assertTrue($client->hasGrantType('personal_access'));
        $this->assertTrue($client->hasGrantType('client_credentials'));
    }
}
