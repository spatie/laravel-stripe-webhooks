<?php

namespace Spatie\StripeWebhooks\Tests;

use Illuminate\Support\Facades\Route;
use Spatie\StripeWebhooks\Test\TestCase;
use Spatie\StripeWebhooks\Middlewares\VerifySignature;

class VerifySignatureTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Route::post('stripe-webhooks', function () {
            return 'ok';
        })->middleware(VerifySignature::class);
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
                'error' => 'The request did not contain a header named `Stripe-Signature`',
            ]);
    }

    /** @test */
    public function it_will_fail_when_the_signing_secret_is_not_set()
    {
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
        config(['stripe-webhooks.signing_secret' => 'secret']);

        $response = $this->postJson(
            'stripe-webhooks',
            ['event' => 'source.chargeable'],
            ['Stripe-Signature' => 'abc']
        );

        $response
            ->assertStatus(400)
            ->assertSee('found in the header named `Stripe-Signature` is invalid');
    }
}
