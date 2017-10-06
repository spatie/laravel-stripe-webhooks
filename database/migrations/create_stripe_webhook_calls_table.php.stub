<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStripeWebhookCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stripe_webhook_calls', function (Blueprint $table) {
            $table->increments('id');

            $table->string('type')->nullable();
            $table->text('payload')->nullable();
            $table->text('exception')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stripe_webhook_calls');
    }
}
