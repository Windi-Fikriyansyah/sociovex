<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ZernioService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncZernioProfiles extends Command
{
    protected $signature   = 'zernio:sync-profiles {--force : Buat ulang profile meskipun sudah ada}';
    protected $description = 'Buat Zernio profile untuk semua tenant yang belum punya zernio_profile_id';

    public function __construct(private ZernioService $zernio)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = $this->option('force')
            ? Tenant::query()
            : Tenant::whereNull('zernio_profile_id')->orWhere('zernio_profile_id', '');

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->info('Semua tenant sudah punya Zernio profile.');
            return self::SUCCESS;
        }

        $this->info("Memproses {$tenants->count()} tenant...");
        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        $success = 0;
        $failed  = 0;

        foreach ($tenants as $tenant) {
            try {
                $result    = $this->zernio->createProfile($tenant->business_name . '_' . \Illuminate\Support\Str::random(6));
                $profileId = $result['profile']['_id'] ?? null;

                if (!$profileId) {
                    throw new \RuntimeException('createProfile returned no profile ID.');
                }

                $tenant->update(['zernio_profile_id' => $profileId]);

                // Register webhook for all relevant events
                try {
                    $this->zernio->registerWebhook(
                        $profileId,
                        route('webhook.zernio'),
                        ['new_message', 'new_comment', 'post_published', 'post_failed']
                    );
                } catch (\RuntimeException $e) {
                    Log::warning("Webhook registration failed for tenant {$tenant->id}: {$e->getMessage()}");
                }

                $success++;
            } catch (\RuntimeException $e) {
                $this->newLine();
                $this->error("Tenant #{$tenant->id} ({$tenant->business_name}): {$e->getMessage()}");
                Log::error("SyncZernioProfiles failed for tenant {$tenant->id}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai. Berhasil: {$success} | Gagal: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
