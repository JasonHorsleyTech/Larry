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
        Schema::create('gpt_conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()
                ->constrained()
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('prompt');

            $table->string('summary')->nullable();
            $table->boolean('finished')->default(false);

            $table->timestamps();
        });

        // An "exchange" is an exchange between a real human "user" and the gpt chatbot.
        Schema::create('gpt_exchanges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')->nullable()
                ->constrained()
                ->on('gpt_conversations')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            // NOT WORKING??
            $table->integer('prompts_required')->default(1);

            $table->timestamps();
        });

        // This is what the user said. Users cannot "say" things outside the context of a GPT exchange.
        Schema::create('gpt_user_transcripts', function (Blueprint $table) {
            $table->id();

            // Larry\Larry\Models\Exchange
            $table->string('transcriptable_type');
            $table->unsignedBigInteger('transcriptable_id');

            $table->string('said');
            $table->float('confidence');
            $table->timestamps();
        });

        // We ran a prompt (through GptService), here's the result.
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

            $table->foreignId('exchange_id')->nullable()
                ->constrained()
                ->on('gpt_exchanges')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->timestamps();
            $table->timestamp('received_at')->nullable();
        });

        // GPT asked we call a function. Here's the function and args, here's the result.
        Schema::create('gpt_function_requests', function (Blueprint $table) {
            $table->id();

            $table->string('name'); // function
            $table->string('function'); // path/to/function
            $table->json('arguments')->nullable();
            $table->json('result')->nullable();

            $table->foreignId('exchange_id')->nullable()
                ->constrained()
                ->on('gpt_exchanges')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gpt_conversations');
        Schema::dropIfExists('gpt_exchanges');
        Schema::dropIfExists('gpt_user_transcripts');
        Schema::dropIfExists('gpt_prompt_responses');
        Schema::dropIfExists('gpt_function_requests');
    }
};
