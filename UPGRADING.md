
### From v1 to v2

Version 2 was created because to use our `laravel-webhooks-client` package under the hood, you'll have to make a few changes to make this work.

Publish the new migration with `php artisan vendor:publish --provider="Spatie\WebhookClient\WebhookClientServiceProvider" --tag="migrations"`. You can keep the old `stripe_webhook_calls` table if you want, all new calls will be stored in the new `webhook_calls` table.

Change the `model` key in the `stripe-webhooks.php` config file to `'model' => \Spatie\StripeWebhooks\ProcessStripeWebhookJob::class` or if you have a custom model, make sure it extends the new `ProcessStripeWebhookJob` class and update your implementation accordingly.

Change your webhook handlers/listeners to accept the new `\Spatie\WebhookClient\Models\WebhookCall` model instead of the `\Spatie\StripeWebhooks\StripeWebhookCall` model.

Retrying a webhook call has changed, this is now done by dispatching the new job: `dispatch(new ProcessStripeWebhookJob(WebhookCall::find($id)));`
