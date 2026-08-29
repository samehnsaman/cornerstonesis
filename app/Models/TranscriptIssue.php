<?php
namespace App\Models;
use App\Models\Concerns\HasUuidPrimaryKey; use Illuminate\Database\Eloquent\Model;
class TranscriptIssue extends Model { use HasUuidPrimaryKey; protected $guarded=[]; protected function casts(): array{return ['issued_at'=>'datetime','revoked_at'=>'datetime'];} }
