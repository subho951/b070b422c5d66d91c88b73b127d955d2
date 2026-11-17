<?php

use App\Support\MediaUsage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $removedFiles = DB::transaction(function (): array {
            DB::table('services')->where('slug', 'digital-media')->delete();
            DB::table('pages')->where('slug', 'services')->where('description', 'Ten service lines. One practical approach. From the first sketch to ongoing support, we build technology around your business.')->update([
                'description' => 'Practical technology services. One connected approach. From the first sketch to ongoing support, we build technology around your business.',
                'updated_at' => now(),
            ]);
            $files = [];
            foreach (DB::table('media_assets')->where('disk', 'public')->where('path', 'images/services/digital-media.webp')->get() as $asset) {
                if (! app(MediaUsage::class)->isInUse((int) $asset->id)) {
                    DB::table('media_assets')->where('id', $asset->id)->delete();
                    $files[] = $asset->path;
                }
            }

            return $files;
        });
        Storage::disk('public')->delete($removedFiles);
    }

    /**
     * Retiring a service is not reversed automatically to avoid republishing it.
     */
    public function down(): void {}
};
