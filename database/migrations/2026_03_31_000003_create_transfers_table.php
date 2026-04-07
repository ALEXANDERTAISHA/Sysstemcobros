<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('restrict');
            $table->date('transfer_date');
            $table->string('sender_name');
            $table->string('receiver_name');
            $table->decimal('amount', 12, 2);
            $table->decimal('commission', 10, 2)->default(0);
            $table->string('transaction_code')->nullable();
            // sent, pending, resent, cancelled
            $table->enum('status', ['sent', 'pending', 'resent', 'cancelled'])->default('pending');
            $table->datetime('sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
