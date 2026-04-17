<?php

namespace Severite\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

class CreateSeveriteMigrationCommand extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'severite:migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the migration for severite is use the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->callSilent('vendor:publish', ['--tag' => 'severite-migration']);
    }
}
