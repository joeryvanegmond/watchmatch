<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Watch extends Model
{
    protected $fillable = ['brand','model', 'url', 'image_url','variant', 'price', 'legit'];

    public function similarWatches()
    {
        return $this->belongsToMany(
            Watch::class,
            'watch_similarities',
            'watch_id',
            'similar_watch_id'
        )->withPivot('link_strength')->withTimestamps();
    }
}
