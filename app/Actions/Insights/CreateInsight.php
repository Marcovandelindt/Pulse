<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Models\Insight;

final class CreateInsight
{
    public function handle(InsightData $data): Insight
    {
        return Insight::create([
            'title'        => $data->title,
            'content'      => $data->content,
            'summary'      => $data->summary,
            'category'     => $data->category,
            'tags'         => $data->tags,
            'is_pinned'    => $data->isPinned,
            'is_quick_ref' => $data->isQuickRef,
        ]);
    }
}
