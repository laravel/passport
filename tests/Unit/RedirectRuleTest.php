<?php

namespace Laravel\Passport\Tests\Unit;

use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Double;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Laravel\Passport\Http\Rules\RedirectRule;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class RedirectRuleTest extends TestCase
{
    use VerifiesDoubles;

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
        $validator = Double::for(Validator::class);
        $validator->allows('fails')->returns($fails);

        $factory = Double::for(Factory::class);
        $factory->allows('make')->returns($validator);

        return new RedirectRule($factory);
    }
}
