<?php

namespace App\Console\Commands;

use App\Models\Achievement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ResizeAchievementImages extends Command
{
    protected $signature = 'achievements:resize-images {--max=400 : Max width in pixels} {--dry : Dry run (do not modify files or DB)}';

    protected $description = 'Resize achievement images to a maximum width (default 400px) and update stored path to resized version.';

    public function handle(): int
    {
        $maxWidth = (int)$this->option('max');
        $dry = (bool)$this->option('dry');
        if ($maxWidth < 50) {
            $this->error('Max width too small. Use >= 50');
            return 1;
        }

        $achievements = Achievement::whereNotNull('image_path')->get();
        if ($achievements->isEmpty()) {
            $this->info('No achievements with image_path.');
            return 0;
        }

        $this->info("Processing {$achievements->count()} achievement images (max width = {$maxWidth}px)" . ($dry ? ' [DRY RUN]' : ''));

        $processed = 0; $skipped = 0; $errors = 0; $already = 0; $resized = 0;

        $this->output->progressStart($achievements->count());

        foreach ($achievements as $achievement) {
            $this->output->progressAdvance();
            $path = $achievement->image_path;
            if (!$path) { $skipped++; continue; }

            $disk = $this->resolveDisk($path);
            if (!$disk) { $errors++; $this->warn("\nFile not found on any disk: {$path}"); continue; }

            try {
                $stream = Storage::disk($disk)->readStream($path);
                if (!$stream) { throw new \RuntimeException('Cannot read stream'); }
                $imageData = stream_get_contents($stream);
                if ($stream && is_resource($stream)) { fclose($stream); }

                [$width, $height, $type] = @getimagesizefromstring($imageData) ?: [null,null,null];
                if (!$width || !$height) { $errors++; $this->warn("\nUnrecognized image: {$path}"); continue; }

                if ($width <= $maxWidth) { $already++; continue; }

                $ratio = $height / $width; $newWidth = $maxWidth; $newHeight = (int)round($newWidth * $ratio);

                $src = $this->createImageResource($imageData, $type);
                if (!$src) { $errors++; $this->warn("\nUnsupported image type for: {$path}"); continue; }

                $dst = imagecreatetruecolor($newHeight > 0 ? $newWidth : 1, $newHeight > 0 ? $newHeight : 1);

                // Handle transparency for PNG/GIF
                if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF])) {
                    imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                }

                imagecopyresampled($dst, $src, 0,0,0,0, $newWidth, $newHeight, $width, $height);

                $newPath = $this->deriveNewPath($path, $newWidth);
                if (!$dry) {
                    $saved = $this->saveImageResource($dst, $type, $disk, $newPath);
                    if (!$saved) { $errors++; imagedestroy($src); imagedestroy($dst); $this->warn("\nFailed saving: {$newPath}"); continue; }
                    // Update DB to point to new path
                    $achievement->image_path = $newPath;
                    $achievement->save();
                }

                imagedestroy($src); imagedestroy($dst);
                $resized++; $processed++;

            } catch (\Throwable $e) {
                $errors++; $this->warn("\nError processing {$path}: " . $e->getMessage());
                continue;
            }
        }

        $this->output->progressFinish();

        $this->line('');
        $this->info('Summary:');
        $this->info("  Total with images: {$achievements->count()}");
        $this->info("  Already <= max:   {$already}");
        $this->info("  Resized:          {$resized}" . ($dry ? ' (simulated)' : ''));
        $this->info("  Skipped (no path): {$skipped}");
        $this->info("  Errors:           {$errors}");

        if ($dry) {
            $this->comment('Dry run complete. No files or database records were changed.');
        }

        return $errors > 0 ? 1 : 0;
    }

    protected function resolveDisk(string $path): ?string
    {
        foreach (['s3_public','public'] as $disk) {
            if (array_key_exists($disk, config('filesystems.disks')) && Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }
        return null;
    }

    protected function createImageResource(string $imageData, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromstring($imageData),
            IMAGETYPE_PNG => imagecreatefromstring($imageData),
            IMAGETYPE_GIF => imagecreatefromstring($imageData),
            default => null,
        };
    }

    protected function deriveNewPath(string $original, int $newWidth): string
    {
        $ext = pathinfo($original, PATHINFO_EXTENSION) ?: 'jpg';
        $base = pathinfo($original, PATHINFO_FILENAME);
        $dir = trim(pathinfo($original, PATHINFO_DIRNAME), '.');
        $dir = $dir === '' || $dir === '/' ? 'achievements' : $dir;
        return $dir . '/resized/' . $base . "_w{$newWidth}." . strtolower($ext);
    }

    protected function saveImageResource($resource, int $type, string $disk, string $path): bool
    {
        ob_start();
        $ok = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($resource, null, 85),
            IMAGETYPE_PNG => imagepng($resource),
            IMAGETYPE_GIF => imagegif($resource),
            default => false,
        };
        $binary = ob_get_clean();
        if (!$ok || !$binary) return false;
        return Storage::disk($disk)->put($path, $binary, 'public');
    }
}

