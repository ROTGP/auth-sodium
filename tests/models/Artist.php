<?php

namespace ROTGP\AuthSodium\Test\Models;

use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    protected $fillable = [
        'name',
        'biography',
        'record_label_id',
        'fan_mail_address'
    ];

    public function albums()
    {
        return $this->hasMany(Album::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function recordLabel()
    {
        return $this->belongsTo(RecordLabel::class);
    }

    public function scopeNameLike($query, $searchTerm)
    {
        return $query->where('name', 'like', "%" . $searchTerm . "%");
    }

    public function scopeRecordLabels($query, $recordLabelIds)
    {
        return $query->whereIn('record_label_id', (array) $recordLabelIds);
    }
}
