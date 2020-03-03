<?php

namespace Spatie\StripeWebhooks\Tests;

use Illuminate\Support\Facades\Event;
use Spatie\StripeWebhooks\ProcessStripeWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;

class StripeWebhookCallTest extends TestCase
{
    /** @var \Spatie\StripeWebhooks\ProcessStripeWebhookJob */
    public $processStripeWebhookJob;

    /** @var \Spatie\WebhookClient\Models\WebhookCall */
    public $webhookCall;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['stripe-webhooks.jobs' => ['my_type' => DummyJob::class]]);

        $this->webhookCall = WebhookCall::create([
            'name' => 'stripe',
            'payload' => ['type' => 'my.type', 'name' => 'value'],
        ]);

        $this->processStripeWebhookJob = new ProcessStripeWebhookJob($this->webhookCall);
    }

    /** @test */
    public function it_will_fire_off_the_configured_job()
    {
        $this->processStripeWebhookJob->handle();

        $this->assertEquals($this->webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function it_will_not_dispatch_a_job_for_another_type()
    {
        config(['stripe-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->processStripeWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_not_dispatch_jobs_when_no_jobs_are_configured()
    {
        config(['stripe-webhooks.jobs' => []]);

        $this->processStripeWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_dispatch_events_even_when_no_corresponding_job_is_configured()
    {
        config(['stripe-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->processStripeWebhookJob->handle();

        $webhookCall = $this->webhookCall;

        Event::assertDispatched("stripe-webhooks::{$webhookCall->payload['type']}", function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertNull(cache('dummyjob'));
    }
}
