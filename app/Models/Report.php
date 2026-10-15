<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Report extends Model { protected $fillable=['order_id','reporter_id','reason','status']; public function order(){return $this->belongsTo(Order::class);} public function reporter(){return $this->belongsTo(User::class,'reporter_id');} }
