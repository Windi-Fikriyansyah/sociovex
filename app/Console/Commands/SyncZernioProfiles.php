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

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = \App\Models\ZernioApiKey::where('is_active', true);
        if (!$this->option('force')) {
            $query->where(function($q) {
                $q->whereNull('zernio_profile_id')->orWhere('zernio_profile_id', '');
            });
        }

        $apiKeys = $query->with('tenant')->get();

        if ($apiKeys->isEmpty()) {
            $this->info('Semua API key yang aktif sudah memiliki Zernio profile.');
            return self::SUCCESS;
        }

        $this->info("Memproses {$apiKeys->count()} API key...");
        $bar = $this->output->createProgressBar($apiKeys->count());
        $bar->start();

        $success = 0;
        $failed  = 0;
        $skipped = 0;

        foreach ($apiKeys as $apiKey) {
            $tenant = $apiKey->tenant;

            if (!$tenant) {
                $this->newLine();
                $this->warn("API Key #{$apiKey->id} ({$apiKey->label}): Tenant tidak ditemukan, dilewati.");
                $skipped++;
                $bar->advance();
                continue;
            }

            $zernio = new ZernioService($apiKey->api_key);

            try {
                $profileName = $tenant->business_name . '_' . $apiKey->label . '_' . \Illuminate\Support\Str::random(4);
                $result    = $zernio->createProfile($profileName);
                $profileId = $result['profile']['_id'] ?? null;

                if (!$profileId) {
                    throw new \RuntimeException('createProfile returned no profile ID.');
                }

                $apiKey->update([
                    'zernio_profile_id' => $profileId,
                    'profile_created_at' => now(),
                ]);

                // Register webhook for all relevant events
                try {
                    $zernio->registerWebhook(
                        $profileId,
                        route('webhook.zernio'),
                        ['new_message', 'new_comment', 'post_published', 'post_failed']
                    );
                } catch (\RuntimeException $e) {
                    Log::warning("Webhook registration failed for API Key {$apiKey->id}: {$e->getMessage()}");
                }

                $success++;
            } catch (\RuntimeException $e) {
                $this->newLine();
                $this->error("API Key #{$apiKey->id} ({$apiKey->label}): {$e->getMessage()}");
                Log::error("SyncZernioProfiles failed for API Key {$apiKey->id}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai. Berhasil: {$success} | Gagal: {$failed} | Dilewati (tanpa API key): {$skipped}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
