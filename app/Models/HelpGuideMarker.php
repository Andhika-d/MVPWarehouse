<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpGuideMarker extends Model
{
    protected $fillable = ['help_guide_image_id', 'number', 'position_x', 'position_y', 'title', 'description', 'sort_order'];

    protected $casts = ['position_x' => 'float', 'position_y' => 'float'];

    public function image()
    {
        return $this->belongsTo(HelpGuideImage::class, 'help_guide_image_id');
    }
}
