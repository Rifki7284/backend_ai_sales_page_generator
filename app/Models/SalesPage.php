<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_name',
        'input_data',
        'generated_content',
        'template',
    ];

    /**
     * Cast JSON ke array otomatis
     */
    protected $casts = [
        'input_data' => 'array',
        'generated_content' => 'array',
    ];

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
