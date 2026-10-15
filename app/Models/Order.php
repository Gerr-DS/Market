<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Order extends Model { use SoftDeletes; protected $fillable=['order_number','buyer_id','product_id','fulfillment_type','shipping_address','shipping_courier','tracking_number','item_amount','shipping_amount','total_amount','payment_status','order_status','delivered_at']; protected function casts():array{return ['shipping_address'=>'array','delivered_at'=>'datetime','item_amount'=>'decimal:2','shipping_amount'=>'decimal:2','total_amount'=>'decimal:2'];} public function buyer(){return $this->belongsTo(User::class,'buyer_id');} public function product(){return $this->belongsTo(Product::class)->withTrashed();} public function payments(){return $this->hasMany(Payment::class);} public function reports(){return $this->hasMany(Report::class);} }
