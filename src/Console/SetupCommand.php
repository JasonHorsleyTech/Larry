<?php

namespace JasonHorsleyTech\GptAssistant\Console;

use Illuminate\Console\Command;

class SetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gpt-assistant:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup GPT assistant.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Adding tables under gpt_assistant_ prefix');
    }
}
