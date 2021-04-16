<?php

namespace ROTGP\AuthSodium\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PruneNonces extends Command
{
    protected $signature = 'authsodium:prune';

    protected $description = 'Prune expired nonces from nonces table';

    public function handle()
    {
        $this->info('Pruning nonces...');

        $deleteCount = authSodium()->pruneNonces();
        
        $this->info("$deleteCount nonces were pruned");

        return $deleteCount;
    }
}