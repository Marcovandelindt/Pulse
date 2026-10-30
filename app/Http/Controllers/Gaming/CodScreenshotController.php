<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Actions\PlayStation\Cod\ProcessCodScreenshot;
use App\Actions\PlayStation\Cod\ProcessResult;
use App\Actions\PlayStation\Cod\ProcessResultStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gaming\StoreCodScreenshotRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

final class CodScreenshotController extends Controller
{
    public function __construct(
        private readonly ProcessCodScreenshot $processor,
    ) {}

    public function store(StoreCodScreenshotRequest $request): RedirectResponse
    {
        $counts = [
            ProcessResultStatus::Confirmed->value => 0,
            ProcessResultStatus::PendingReview->value => 0,
            ProcessResultStatus::MatchDuplicate->value => 0,
            ProcessResultStatus::ScreenshotDuplicate->value => 0,
        ];

        foreach ($request->file('screenshots') as $file) {
            $result = $this->processFile($file);
            $counts[$result->status->value]++;
        }

        return redirect()->route('playstation.cod.index')
            ->with('upload_summary', $counts);
    }

    private function processFile(UploadedFile $file): ProcessResult
    {
        $takenAt = $this->parseTakenAt($file->getClientOriginalName());
        $realPath = (string) $file->getRealPath();
        $fileHash = hash_file('sha256', $realPath);

        if ($fileHash === false) {
            throw new \RuntimeException('Could not hash uploaded file: '.$file->getClientOriginalName());
        }

        $filename = $file->hashName();

        $file->storeAs('cod-screenshots', $filename, 'local');

        $storedPath = storage_path('app/private/cod-screenshots/'.$filename);

        return $this->processor->handle(
            storedPath: $storedPath,
            filename: $filename,
            fileHash: $fileHash,
            takenAt: $takenAt,
        );
    }

    /**
     * PS5 screenshot filenames contain the capture timestamp.
     * Supported formats: "2024-01-15 18-30-45.jpg"
     * Falls back to now() if the filename does not match.
     */
    private function parseTakenAt(string $filename): Carbon
    {
        if (preg_match('/(\d{4}-\d{2}-\d{2})[_ ](\d{2}-\d{2}-\d{2})/', $filename, $m)) {
            $datetime = $m[1].' '.str_replace('-', ':', $m[2]);

            return Carbon::createFromFormat('Y-m-d H:i:s', $datetime) ?? Carbon::now();
        }

        return Carbon::now();
    }
}
