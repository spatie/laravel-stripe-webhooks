<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

class StripeWebhookCall extends Model
{
    public $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'exception' => 'array',
    ];

    public function process()
    {
        $this->clearException();

        $jobClass = $this->determineJobClass($this->type);

        if ($jobClass === '') {
            return;
        }

        dispatch(new $jobClass($this));
    }

    protected function determineJobClass(string $eventType): string
    {
        $jobConfigKey = str_replace('.', '_', $eventType);

        return config("stripe-webhooks.jobs.{$jobConfigKey}", '');
    }

    protected function clearException() {
        $this->exception = null;

        $this->save();

        return $this;
    }

    protected function saveException(Exception $exception)
    {
        $this->exception = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'exception' => $exception->getTraceAsString(),
        ];

        $this->save();

        return $this;
    }
}
