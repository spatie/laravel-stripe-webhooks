<?php

namespace Spatie\StripeWebhooks\Tests\Middlewares;

use Illuminate\Support\Facades\Route;
use Spatie\StripeWebhooks\Tests\TestCase;
use Spatie\StripeWebhooks\Middlewares\VerifySignature;

class VerifySignatureTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Route::post('stripe-webhooks/{configKey?}', function () {
            return 'ok';
        })->middleware(VerifySignature::class);
    }

    /** @test */
    public function it_will_succeed_when_the_request_has_a_valid_signature()
    {
        $payload = ['event' => 'source.chargeable'];

        $response = $this->postJson(
            'stripe-webhooks',
            $payload,
            ['Stripe-Signature' => $this->determineStripeSignature($payload)]
        );

        $response
            ->assertStatus(200)
            ->assertSee('ok');
    }

    /** @test */
    public function it_will_fail_when_the_signature_header_is_not_set()
    {
        $response = $this->postJson(
            'stripe-webhooks',
            ['event' => 'source.chargeable']
        );

        $response
            ->assertStatus(400)
            ->assertJson([
                'error' => 'The request did not contain a header named `Stripe-Signature`.',
            ]);
    }

    /** @test */
    public function it_will_fail_when_the_signing_secret_is_not_set()
    {
        config(['stripe-webhooks.signing_secret' => '']);

        $response = $this->postJson(
            'stripe-webhooks',
            ['event' => 'source.chargeable'],
            ['Stripe-Signature' => 'abc']
        );

        $response
            ->assertStatus(400)
            ->assertSee('The Stripe webhook signing secret is not set');
    }


    /** @test */
    public function it_will_fail_when_the_signature_is_invalid()
    {
        $response = $this->postJson(
            'stripe-webhooks',
            ['event' => 'source.chargeable'],
            ['Stripe-Signature' => 'abc']
        );

        $response
            ->assertStatus(400)
            ->assertSee('found in the header named `Stripe-Signature` is invalid');
    }

    /** @test */
    public function it_will_succeed_when_using_a_named_config_key()
    {
        config(['stripe-webhooks.signing_secret_named' => 'test_signing_secret']);

        $payload = ['event' => 'source.chargeable'];

        $response = $this->postJson(
            'stripe-webhooks/named',
            $payload,
            ['Stripe-Signature' => $this->determineStripeSignature($payload)]
        );

        $response
            ->assertStatus(200)
            ->assertSee('ok');
    }

    /** @test */
    public function it_will_fail_when_the_named_signing_secret_is_not_set()
    {
        config(['stripe-webhooks.signing_secret_named' => '']);

        $response = $this->postJson(
            'stripe-webhooks/named',
            ['event' => 'source.chargeable'],
            ['Stripe-Signature' => 'abc']
        );

        $response
            ->assertStatus(400)
            ->assertSee('The Stripe webhook signing secret is not set');
    }
}
