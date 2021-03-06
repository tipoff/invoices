<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->index()->unique(); // Generated by system. This is identifier used to communicate with customers about their invoice.
            $table->foreignIdFor(app('order'))->index();
            $table->foreignIdFor(app('user')); // User who was initially billed/sent the invoice. May not be the same user that makes the payment.

            $table->unsignedInteger('amount'); // Amount is in cents. It is net, excluding taxes and fees. An accessor for total_amount adds the 3 columns
            // Taxes and fees not broken out for invoices. The order total amount and associated taxes and fees are shown at the top of the invoice. The amount due for the particular invoice is shown under that. A line about other payments and invoices should be added to it so it makes sense.

            $table->string('note')->nullable(); // Shown on the invoice. Internal notes about invoice can be made in admin panel.

            $table->dateTime('invoiced_at')->nullable(); // different than created_at because this is when invoice was emailed to customer
            $table->date('due_at')->nullable(); // Null when invoice created. Added when invoice is sent.

            $table->foreignIdFor(app('user'), 'creator_id');
            $table->foreignIdFor(app('user'), 'updater_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }
}
