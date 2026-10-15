<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WalletTransaction extends Model { protected $fillable=['user_id','order_id','type','amount','description']; protected function casts():array{return ['amount'=>'decimal:2'];} public function user(){return $this->belongsTo(User::class);} public function order(){return $this->belongsTo(Order::class);} }
