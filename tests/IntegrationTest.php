<?php

namespace Spatie\StripeWebhooks\Tests;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Spatie\StripeWebhooks\StripeWebhookCall;

class IntegrationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Event::fake();

        Bus::fake();

        Route::stripeWebhooks('stripe-webhooks');

        config(['stripe-webhooks.jobs' => ['my_type' => DummyJob::class]]);

        $this->withoutMiddleware();
    }

    /** @test */
    public function it_can_handle_a_valid_call()
    {
        $payload = [
            'type' => 'my.type',
            'key' => 'value'
        ];

        $this
            ->post('stripe-webhooks', $payload)
            ->assertSuccessful();

        $this->assertCount(1, StripeWebhookCall::get());

        $webhookCall = StripeWebhookCall::first();

        $this->assertEquals('my.type', $webhookCall->type);
        $this->assertEquals($payload, $webhookCall->payload);
        $this->assertNull($webhookCall->exception);

        Event::assertDispatched('stripe-webhooks::my.type', function($event, $eventPayload) use ($webhookCall) {
            if ( ! $eventPayload instanceof StripeWebhookCall) {
                return false;
            }

            return $eventPayload->id === $webhookCall->id;
        });

        Bus::assertDispatched(DummyJob::class, function(DummyJob $job) use ($webhookCall) {
            return $job->stripeWebhookCall->id === $webhookCall->id;
        });
    }

    /** @test */
    public function it_can_handle_an_invalid_call()
    {
        $this
            ->post('stripe-webhooks', ['invalid_payload'])
            ->assertStatus(400);

        $this->assertCount(1, StripeWebhookCall::get());

        $webhookCall = StripeWebhookCall::first();

        $this->assertEquals('', $webhookCall->type);
        $this->assertEquals(['invalid_payload'], $webhookCall->payload);
        $this->assertEquals('Webhook call id `1` did not contain a type. Valid Stripe webhook calls should always contain a type.', $webhookCall->exception['message']);

        Event::assertNotDispatched('stripe-webhooks::my.type');

        Bus::assertNotDispatched(DummyJob::class);
    }
}