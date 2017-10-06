<?php

namespace Spatie\StripeWebhooks\Tests;

use Exception;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Spatie\StripeWebhooks\StripeWebhookCall;

class StripeWebhookCallTest extends TestCase
{
    /** @var \Spatie\StripeWebhooks\StripeWebhookCall */
    public $stripeWebhookCall;

    public function setUp()
    {
        parent::setUp();

        Bus::fake();

        Event::fake();

        config(['stripe-webhooks.jobs' => ['my_type' => DummyJob::class]]);

        $this->stripeWebhookCall = StripeWebhookCall::create([
            'type' => 'my.type',
            'payload' => ['name' => 'value'],
        ]);
    }

    /** @test */
    public function it_will_fire_off_the_configured_job()
    {
        $this->stripeWebhookCall->process();

        Bus::assertDispatched(DummyJob::class, function (DummyJob $job) {
            return $job->stripeWebhookCall->id === $this->stripeWebhookCall->id;
        });
    }

    /** @test */
    public function it_will_not_dispatch_a_job_for_another_type()
    {
        config(['stripe-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->stripeWebhookCall->process();

        Bus::assertNotDispatched(DummyJob::class);
    }

    /** @test */
    public function it_will_not_dispatch_jobs_when_no_jobs_are_configured()
    {
        config(['stripe-webhooks.jobs' => []]);

        $this->stripeWebhookCall->process();

        Bus::assertNotDispatched(DummyJob::class);
    }

    public function it_will_dispatch_events_even_when_no_corresponding_job_is_configured()
    {
        $payload = [
            'type' => 'yet.another.type',
            'payload' => ['name' => 'value'],
        ];

        $webhookCall = StripeWebhookCall::create($payload);

        Event::assertDispatched("stripe-webhooks::{$payload['type']}", $payload);
    }

    /** @test */
    public function it_can_save_an_exception()
    {
        $this->stripeWebhookCall->saveException(new Exception('my message', 123));

        $this->assertEquals(123, $this->stripeWebhookCall->exception['code']);
        $this->assertEquals('my message', $this->stripeWebhookCall->exception['message']);
        $this->assertGreaterThan(200, strlen($this->stripeWebhookCall->exception['trace']));
    }

    /** @test */
    public function processing_a_webhook_will_clear_the_exception()
    {
        $this->stripeWebhookCall->saveException(new Exception('my message', 123));

        $this->stripeWebhookCall->process();

        $this->assertNull($this->stripeWebhookCall->exception);
    }
}
