<?php

declare(strict_types=1);

namespace App\Actions\Waze;

use App\Models\WazeDrive;
use App\Services\Waze\WazeCsvParser;
use Illuminate\Http\UploadedFile;
use ZipArchive;

final class ImportWazeDrivesAction
{
    public function __construct(
        private readonly WazeCsvParser $parser,
    ) {}

    public function handle(UploadedFile $file): ImportResult
    {
        $csvContents = $this->extractCsvContents($file);

        $imported = 0;
        $skipped = 0;

        foreach ($csvContents as $csv) {
            $drives = $this->parser->parse($csv);

            foreach ($drives as $drive) {
                $exists = WazeDrive::where('driven_at', $drive['driven_at'])->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                WazeDrive::create($drive);
                $imported++;
            }
        }

        return new ImportResult(imported: $imported, skipped: $skipped);
    }

    /** @return list<string> */
    private function extractCsvContents(UploadedFile $file): array
    {
        if ($file->getClientOriginalExtension() === 'zip') {
            return $this->extractFromZip($file->getRealPath());
        }

        $content = file_get_contents($file->getRealPath());

        return $content !== false ? [$content] : [];
    }

    /** @return list<string> */
    private function extractFromZip(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return [];
        }

        $contents = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if ($name === false) {
                continue;
            }

            if (! str_starts_with(basename($name), 'account_activity') || ! str_ends_with($name, '.csv')) {
                continue;
            }

            $content = $zip->getFromIndex($i);

            if ($content !== false && $content !== '') {
                $contents[] = $content;
            }
        }

        $zip->close();

        return $contents;
    }
}
