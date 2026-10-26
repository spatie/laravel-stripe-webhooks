<?php

namespace Spatie\StripeWebhooks\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
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

    #[Test]
    public function a_request_with_invalid_signature_with_verification_disabled_will_pass()
    {
        config(['stripe-webhooks.verify_signature' => false]);
        cache()->clear();

        $this->withoutExceptionHandling();

        $payload = [
            'type' => 'my.type',
            'key' => 'value',
        ];

        $headers = ['Stripe-Signature' => 'invalid signature'];

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

    #[Test]
    public function a_request_without_signature_with_verification_disabled()
    {
        // had to explicit disable signature verification
        config(['stripe-webhooks.verify_signature' => false]);
        cache()->clear();

        $this->withoutExceptionHandling();

        $payload = [
            'type' => 'my.type',
            'key' => 'value',
        ];

        $headers = [];

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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function a_request_will_only_be_processed_once()
    {
        $payload = [
            'type' => 'my.type',
            'id' => 'evt_123',
        ];

        $headers = ['Stripe-Signature' => $this->determineStripeSignature($payload)];

        $this
            ->postJson('stripe-webhooks', $payload, $headers)
            ->assertSuccessful();

        $this
            ->postJson('stripe-webhooks', $payload, $headers)
            ->assertSuccessful();

        $this->assertCount(1, WebhookCall::where('payload->id', $payload['id'])->get());
        Event::assertDispatchedTimes('stripe-webhooks::my.type', 1);
    }

    #[Test]
    public function the_payload_event_id_takes_precedence_over_query_parameters()
    {
        $payload = [
            'type' => 'my.type',
            'id' => 'evt_body',
        ];

        $headers = ['Stripe-Signature' => $this->determineStripeSignature($payload)];

        $this->postJson('stripe-webhooks', $payload, $headers)->assertSuccessful();
        $this->postJson('stripe-webhooks?id=evt_query', $payload, $headers)->assertSuccessful();

        $this->assertCount(1, WebhookCall::get());
        Event::assertDispatchedTimes('stripe-webhooks::my.type', 1);

        $payload['id'] = 'evt_next';
        $headers = ['Stripe-Signature' => $this->determineStripeSignature($payload)];

        $this->postJson('stripe-webhooks?id=evt_body', $payload, $headers)->assertSuccessful();

        $this->assertEquals(['evt_body', 'evt_next'], WebhookCall::orderBy('id')->get()->pluck('payload.id')->all());
        Event::assertDispatchedTimes('stripe-webhooks::my.type', 2);
        $this->assertEquals('evt_next', cache('dummyjob')->payload['id']);
    }

    #[Test]
    public function an_event_id_from_another_webhook_source_does_not_suppress_a_stripe_event()
    {
        WebhookCall::create([
            'name' => 'another-provider',
            'url' => '/another-webhook',
            'payload' => ['id' => 'evt_shared', 'type' => 'my.type'],
        ]);

        $payload = ['id' => 'evt_shared', 'type' => 'my.type'];
        $headers = ['Stripe-Signature' => $this->determineStripeSignature($payload)];

        $this->postJson('stripe-webhooks', $payload, $headers)->assertSuccessful();
        $this->postJson('stripe-webhooks', $payload, $headers)->assertSuccessful();

        $this->assertCount(2, WebhookCall::get());
        $this->assertCount(1, WebhookCall::where('name', 'stripe')->get());
        Event::assertDispatchedTimes('stripe-webhooks::my.type', 1);
        $this->assertEquals('stripe', cache('dummyjob')->name);
    }

    #[Test]
    public function a_duplicate_event_id_is_not_processed_again_for_a_different_stripe_event_type()
    {
        $payload = ['id' => 'evt_shared', 'type' => 'my.type'];
        $headers = ['Stripe-Signature' => $this->determineStripeSignature($payload)];

        $this->postJson('stripe-webhooks', $payload, $headers)->assertSuccessful();

        $payload['type'] = 'another.type';
        $headers = ['Stripe-Signature' => $this->determineStripeSignature($payload)];

        $this->postJson('stripe-webhooks', $payload, $headers)->assertSuccessful();

        $this->assertCount(1, WebhookCall::get());
        $this->assertEquals('my.type', WebhookCall::first()->payload['type']);
        Event::assertDispatchedTimes('stripe-webhooks::my.type', 1);
        Event::assertNotDispatched('stripe-webhooks::another.type');
    }
}
