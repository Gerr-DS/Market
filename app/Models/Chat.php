<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Chat extends Model { protected $fillable=['product_id','sender_id','receiver_id','message','is_read']; protected function casts():array{return ['is_read'=>'boolean'];} public function product(){return $this->belongsTo(Product::class);} public function sender(){return $this->belongsTo(User::class,'sender_id');} public function receiver(){return $this->belongsTo(User::class,'receiver_id');} }
