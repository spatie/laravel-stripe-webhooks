
### From v1 to v2

Version 2 was created because to use our `laravel-webhooks-client` package under the hood, you'll have to make a few changes to make this work.

Publish the new migration with `php artisan vendor:publish --provider="Spatie\WebhookClient\WebhookClientServiceProvider" --tag="migrations"`. You can keep the old `stripe_webhook_calls` table if you want, all new calls will be stored in the new `webhook_calls` table.

Change the `model` key in the `stripe-webhooks.php` config file to `'model' => \Spatie\StripeWebhooks\ProcessStripeWebhookJob::class` or if you have a custom model, make sure it extends the new `ProcessStripeWebhookJob` class and update your implementation accordingly.

Change your webhook handlers/listeners to accept the new `\Spatie\WebhookClient\Models\WebhookCall` model instead of the `\Spatie\StripeWebhooks\StripeWebhookCall` model.

Retrying a webhook call has changed, this is now done by dispatching the new job: `dispatch(new ProcessStripeWebhookJob(WebhookCall::find($id)));`


### From v2 to v3
#### Create Migration
```bash
php artisan make:migration add_columns_to_webhook_calls
```

###  Here's an example how your migration should look.
```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToWebhookCalls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('webhook_calls', function (Blueprint $table) {
            $table->string('url')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('webhook_calls', function (Blueprint $table) {
            $table->dropColumn('url');
            $table->dropColumn('headers');
            $table->text('payload')->change();
        });
    }
}
```


### Config file changes

If you have published a config file previously please follow these steps:

1, Update "model" to point to the following WebhookCall class:

```php
    /*
     * The classname of the model to be used. The class should equal or extend
     * Spatie\WebhookClient\Models\WebhookCall.
     */
    'model' => \Spatie\WebhookClient\Models\WebhookCall::class,
```

2, Add two new references for profile and verify_signature:

```php
    /*
     * This class determines if the webhook call should be stored and processed.
     */
    'profile' => \Spatie\StripeWebhooks\StripeWebhookProfile::class,

    /*
     * When disabled, the package will not verify if the signature is valid.
     * This can be handy in local environments.
     */
    'verify_signature' => env('STRIPE_SIGNATURE_VERIFY', true),

    ```

Please see the readme for more information on verify_signature and how its used.
