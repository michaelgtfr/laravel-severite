<?php

namespace Severite\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

class InstallSeveriteCommand extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'severite:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installation of severite package configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->callSilent('vendor:publish', ['--tag' => 'severite-config']);

        $this->callSilent('vendor:publish', ['--tag' => 'severite-assets']);

        $this->callSilent('vendor:publish', ['--tag' => 'severite-blade']);
    }
}
