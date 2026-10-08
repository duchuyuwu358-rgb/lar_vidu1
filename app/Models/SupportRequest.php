<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportRequest extends Model
{
    use HasFactory;

    protected $table = 'support_requests';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'attachment_path',
        'status',
    ];
}