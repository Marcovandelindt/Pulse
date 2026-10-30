<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gaming\StoreCodScreenshotRequest;
use App\Jobs\ProcessCodScreenshotJob;
use App\Models\CodScreenshot;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

final class CodScreenshotController extends Controller
{
    public function store(StoreCodScreenshotRequest $request): RedirectResponse
    {
        $queued = 0;
        $skipped = 0;

        foreach ($request->file('screenshots') as $file) {
            if ($this->dispatchFile($file)) {
                $queued++;
            } else {
                $skipped++;
            }
        }

        return redirect()->route('playstation.cod.index')
            ->with('upload_queued', ['queued' => $queued, 'skipped' => $skipped]);
    }

    private function dispatchFile(UploadedFile $file): bool
    {
        $realPath = (string) $file->getRealPath();
        $fileHash = hash_file('sha256', $realPath);

        if ($fileHash === false) {
            throw new \RuntimeException('Could not hash uploaded file: '.$file->getClientOriginalName());
        }

        // Skip exact duplicate files — no point queuing them again.
        if (CodScreenshot::where('file_hash', $fileHash)->whereHas('matches')->exists()) {
            return false;
        }

        $filename = $file->hashName();
        $file->storeAs('cod-screenshots', $filename, 'local');

        ProcessCodScreenshotJob::dispatch(
            storedPath: storage_path('app/private/cod-screenshots/'.$filename),
            filename: $filename,
            fileHash: $fileHash,
            takenAt: $this->parseTakenAt($file->getClientOriginalName()),
        );

        return true;
    }

    private function parseTakenAt(string $filename): Carbon
    {
        if (preg_match('/(\d{4}-\d{2}-\d{2})[_ ](\d{2}-\d{2}-\d{2})/', $filename, $m)) {
            $datetime = $m[1].' '.str_replace('-', ':', $m[2]);

            return Carbon::createFromFormat('Y-m-d H:i:s', $datetime) ?? Carbon::now();
        }

        return Carbon::now();
    }
}
