<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Withdrawal extends Model { protected $fillable=['user_id','amount','bank_name','account_number','account_holder_name','status']; protected function casts():array{return ['amount'=>'decimal:2'];} public function user(){return $this->belongsTo(User::class);} }
