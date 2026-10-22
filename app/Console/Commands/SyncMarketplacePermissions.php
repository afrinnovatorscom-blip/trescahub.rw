<?php

namespace App\Console\Commands;

use App\Support\PermissionCatalog;
use Illuminate\Console\Command;

class SyncMarketplacePermissions extends Command
{
    protected $signature = 'marketplace:sync-permissions';

    protected $description = 'Add any missing marketplace permissions without removing custom role grants';

    public function handle(): int
    {
        PermissionCatalog::sync();
        $this->info('Marketplace permissions are up to date.');

        return self::SUCCESS;
    }
}
