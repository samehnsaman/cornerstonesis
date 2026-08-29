<?php
namespace App\Models;
use App\Models\Concerns\HasUuidPrimaryKey; use Illuminate\Database\Eloquent\Model;
class Campus extends Model { use HasUuidPrimaryKey; protected $guarded=[]; }
