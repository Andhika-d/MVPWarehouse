<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpGuideImage extends Model
{
    protected $fillable = ['help_guide_id', 'image_path', 'image_alt', 'sort_order'];

    public function guide()
    {
        return $this->belongsTo(HelpGuide::class, 'help_guide_id');
    }

    public function markers()
    {
        return $this->hasMany(HelpGuideMarker::class)->orderBy('sort_order');
    }
}
