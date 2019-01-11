<?php

namespace Laravel\Passport\Tests;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\Http\Rules\RedirectRule;

class RedirectRuleTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_it_passes_with_a_single_valid_url()
    {
        $rule = $this->rule($fails = false);

        $this->assertTrue($rule->passes('redirect', 'https://example.com'));
    }

    public function test_it_passes_with_multiple_valid_urls()
    {
        $rule = $this->rule($fails = false);

        $this->assertTrue($rule->passes('redirect', 'https://example.com,https://example2.com'));
    }

    public function test_it_fails_with_a_single_invalid_url()
    {
        $rule = $this->rule($fails = true);

        $this->assertFalse($rule->passes('redirect', 'https://example.com,invalid'));
    }

    private function rule(bool $fails): RedirectRule
    {
        $validator = m::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn($fails);

        $factory = m::mock(Factory::class);
        $factory->shouldReceive('make')->andReturn($validator);

        return new RedirectRule($factory);
    }
}
