<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Laravel\Passport\Http\Rules\RedirectRule;
use PHPUnit\Framework\TestCase;

class RedirectRuleTest extends TestCase
{
    public function test_it_passes_with_a_single_valid_url()
    {
        $rule = $this->rule();

        $this->assertTrue($rule->passes('redirect', 'https://example.com'));
    }

    public function test_it_passes_with_multiple_valid_urls()
    {
        $rule = $this->rule();

        $this->assertTrue($rule->passes('redirect', 'https://example.com,https://example2.com'));
    }

    public function test_it_fails_with_a_single_invalid_url()
    {
        $rule = $this->rule();

        $this->assertFalse($rule->passes('redirect', 'https://example.com,invalid'));
    }

    private function rule(): RedirectRule
    {
        return new RedirectRule(new Factory(new Translator(new ArrayLoader, 'en')));
    }
}
