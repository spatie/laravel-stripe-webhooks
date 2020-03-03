<?php

namespace Spatie\StripeWebhooks\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Spatie\WebhookClient\Models\WebhookCall;

class IntegrationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        Route::stripeWebhooks('stripe-webhooks');
        Route::stripeWebhooks('stripe-webhooks/{configKey}');

        config(['stripe-webhooks.jobs' => ['my_type' => DummyJob::class]]);
        cache()->clear();
    }

    /** @test */
    public function it_can_handle_a_valid_request()
    {
        $this->withoutExceptionHandling();

        $payload = [
            'type' => 'my.type',
            'key' => 'value',
        ];

        $headers = ['Stripe-Signature' => $this->determineStripeSignature($payload)];

        $this
            ->postJson('stripe-webhooks', $payload, $headers)
            ->assertSuccessful();

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertEquals('my.type', $webhookCall->payload['type']);
        $this->assertEquals($payload, $webhookCall->payload);
        $this->assertNull($webhookCall->exception);

        Event::assertDispatched('stripe-webhooks::my.type', function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertEquals($webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function a_request_with_an_invalid_signature_wont_be_logged()
    {
        $payload = [
            'type' => 'my.type',
            'key' => 'value',
        ];

        $headers = ['Stripe-Signature' => 'invalid_signature'];

        $this
            ->postJson('stripe-webhooks', $payload, $headers)
            ->assertStatus(500);

        $this->assertCount(0, WebhookCall::get());

        Event::assertNotDispatched('stripe-webhooks::my.type');

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function a_request_with_an_invalid_payload_will_be_logged_but_events_and_jobs_will_not_be_dispatched()
    {
        $payload = ['invalid_payload'];

        $headers = ['Stripe-Signature' => $this->determineStripeSignature($payload)];

        $this
            ->postJson('stripe-webhooks', $payload, $headers)
            ->assertStatus(400);

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertFalse(isset($webhookCall->payload['type']));
        $this->assertEquals(['invalid_payload'], $webhookCall->payload);

        $this->assertEquals('Webhook call id `1` did not contain a type. Valid Stripe webhook calls should always contain a type.', $webhookCall->exception['message']);

        Event::assertNotDispatched('stripe-webhooks::my.type');

        $this->assertNull(cache('dummyjob'));
    }

    /** @test * */
    public function a_request_with_a_config_key_will_use_the_correct_signing_secret()
    {
        config()->set('stripe-webhooks.signing_secret', 'secret1');
        config()->set('stripe-webhooks.signing_secret_somekey', 'secret2');

        $payload = [
            'type' => 'my.type',
            'key' => 'value',
        ];

        $headers = ['Stripe-Signature' => $this->determineStripeSignature($payload, 'somekey')];

        $this
            ->postJson('stripe-webhooks/somekey', $payload, $headers)
            ->assertSuccessful();
    }
}
