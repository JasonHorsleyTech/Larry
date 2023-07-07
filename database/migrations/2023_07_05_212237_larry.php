<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gpt_exchanges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->integer('prompts_required')->default(1);

            $table->timestamps();
        });

        Schema::create('gpt_user_transcripts', function (Blueprint $table) {
            $table->id();

            // Larry\Larry\Models\Exchange
            $table->string('transcriptable_type');
            $table->unsignedBigInteger('transcriptable_id');

            $table->string('said');
            $table->float('confidence');
            $table->timestamps();
        });

        Schema::create('gpt_prompt_responses', function (Blueprint $table) {
            $table->id();

            // I suspect we'll want nothing else (prompt, completion, whatever else they think up)
            $table->string('type')->default('chat');
            // Extended Component used to generate payload
            $table->string('component');
            // Exactly what we sent to GPT
            $table->json('payload');
            // Exactly what we got back
            $table->json('response')->nullable();
            // Exactly what we got back
            $table->text('error_message')->nullable();

            $table->timestamps();
            $table->timestamp('received_at')->nullable();
        });

        Schema::create('gpt_exchange_prompt_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_id')->constrained('gpt_exchanges')->onDelete('cascade');
            $table->foreignId('prompt_response_id')->constrained('gpt_prompt_responses')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gpt_exchanges');
        Schema::dropIfExists('gpt_prompt_responses');
    }
};
