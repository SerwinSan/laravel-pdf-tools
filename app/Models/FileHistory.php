<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FileHistory extends Model
{
    use HasFactory;
    protected $guarded = []; // Agar kita bisa isi semua kolom sekaligus
}