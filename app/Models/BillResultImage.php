<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillResultImage extends Model
{
    protected $fillable = [
        'bill_result_id',
        'uploaded_by',
        'path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function result()
    {
        return $this->belongsTo(BillResult::class, 'bill_result_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
