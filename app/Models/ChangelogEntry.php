<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChangelogEntry extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [
        'version', 'released_on', 'title', 'summary', 'body',
        'is_published', 'position',
    ];

    protected function casts(): array
    {
        return [
            'released_on' => 'date',
            'is_published' => 'boolean',
        ];
    }

    /** Reused by the audit trait as the human label. */
    public function getNameAttribute(): string
    {
        return trim($this->version.' '.$this->title);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** Newest release first; position breaks ties on the same date. */
    public function scopeTimeline(Builder $query): Builder
    {
        return $query->orderByDesc('released_on')
            ->orderByDesc('position')
            ->orderByDesc('id');
    }

    /** Body is authored as Markdown; render it server-side for display. */
    public function getBodyHtmlAttribute(): string
    {
        return $this->body ? Str::markdown($this->body) : '';
    }
}
