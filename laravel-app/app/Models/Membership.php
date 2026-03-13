<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Membership extends Model
{
    use SoftDeletes;

    protected $table = 'membership';

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'type',
        'mplan',
        'start_date',
        'end_date',
        'member_status',
        'total',
        'duration',
        'pob',
        'dob',
        'address',
        'city',
        'nationality',
        'phone_no',
        'ename',
        'ephone',
        'mailsent',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'dob' => 'date',
            'total' => 'decimal:2',
            'mailsent' => 'boolean',
        ];
    }
}
