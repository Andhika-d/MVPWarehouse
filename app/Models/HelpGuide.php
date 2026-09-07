<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpGuide extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'slug', 'category', 'description', 'audience_role', 'status', 'sort_order', 'created_by', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];

    public function images()
    {
        return $this->hasMany(HelpGuideImage::class)->orderBy('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisibleTo($query, string $role)
    {
        return $query->where('status', 'published')->whereIn('audience_role', ['all', $role]);
    }
}
