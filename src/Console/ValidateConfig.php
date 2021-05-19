<?php

namespace ROTGP\AuthSodium\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ValidateConfig extends Command
{
    protected $signature = 'authsodium:validate';

    protected $description = 'Validate Auth Sodium configuration';

    public function handle()
    {
        $this->info('Validating Auth Sodium configuration...');
        $results = authSodium()->validateConfig(false);

        // dd($results);
        
        // $this->info("$deleteCount nonces were pruned");

        // return $deleteCount;
    }
}