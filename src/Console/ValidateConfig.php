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
        $this->newLine(2);
        $this->info('Validating Auth Sodium configuration...');
        $this->newLine();
        
        $results = authSodium()->validateConfig(false);

        foreach ($results as $key => $value) {
            $ok = !array_key_exists('error', $value);
            $icon = $ok ? "\u{2705}" : "\u{274C}";
            $this->line($icon . ' ' . $value['msg']);
            if (!$ok) {
                $this->error($value['error']);
            }
            $this->newLine();
        }
    }
}