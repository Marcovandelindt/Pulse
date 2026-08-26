<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Models\Insight;

final class UpdateInsight
{
    public function handle(Insight $insight, InsightData $data): Insight
    {
        $insight->update([
            'title'        => $data->title,
            'content'      => $data->content,
            'summary'      => $data->summary,
            'category'     => $data->category,
            'tags'         => $data->tags,
            'is_pinned'    => $data->isPinned,
            'is_quick_ref' => $data->isQuickRef,
        ]);

        return $insight;
    }
}
