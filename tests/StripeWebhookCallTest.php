<?php

namespace Spatie\StripeWebhooks\Tests;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Spatie\StripeWebhooks\ProcessStripeWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;

class StripeWebhookCallTest extends TestCase
{
    public ProcessStripeWebhookJob $processStripeWebhookJob;

    public WebhookCall $webhookCall;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['stripe-webhooks.jobs' => ['my_type' => DummyJob::class]]);

        $this->webhookCall = WebhookCall::create([
            'name' => 'stripe',
            'url' => '/stripe',
            'payload' => ['type' => 'my.type', 'name' => 'value'],
        ]);

        $this->processStripeWebhookJob = new ProcessStripeWebhookJob($this->webhookCall);
    }

    #[Test]
    public function it_will_fire_off_the_configured_job()
    {
        $this->processStripeWebhookJob->handle();

        $this->assertEquals($this->webhookCall->id, cache('dummyjob')->id);
    }

    #[Test]
    public function it_will_not_dispatch_a_job_for_another_type()
    {
        config(['stripe-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->processStripeWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    #[Test]
    public function it_will_not_dispatch_jobs_when_no_jobs_are_configured()
    {
        config(['stripe-webhooks.jobs' => []]);

        $this->processStripeWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    #[Test]
    public function it_will_dispatch_jobs_when_default_job_is_configured()
    {
        config([
            'stripe-webhooks.jobs' => [],
            'stripe-webhooks.default_job' => DummyJob::class,
        ]);

        $this->processStripeWebhookJob->handle();

        $this->assertEquals($this->webhookCall->id, cache('dummyjob')->id);
    }

    #[Test]
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

    #[Test]
    public function it_can_specify_a_connection_in_the_config()
    {
        config(['stripe-webhooks.connection' => 'some-connection']);

        $processStripeWebhookJob = new ProcessStripeWebhookJob($this->webhookCall);

        $this->assertEquals('some-connection', $processStripeWebhookJob->connection);
    }

    #[Test]
    public function it_can_specify_a_queue_in_the_config()
    {
        config(['stripe-webhooks.queue' => 'some-queue']);

        $processStripeWebhookJob = new ProcessStripeWebhookJob($this->webhookCall);

        $this->assertEquals('some-queue', $processStripeWebhookJob->queue);
    }

    #[Test]
    public function it_dispatches_the_configured_job_on_the_configured_connection_and_queue()
    {
        Bus::fake();

        config([
            'stripe-webhooks.connection' => 'some-connection',
            'stripe-webhooks.queue' => 'some-queue',
        ]);

        $processStripeWebhookJob = new ProcessStripeWebhookJob($this->webhookCall);
        $processStripeWebhookJob->handle();

        Bus::assertDispatched(DummyJob::class, function (DummyJob $job) {
            return $job->connection === 'some-connection' && $job->queue === 'some-queue';
        });
    }
}
