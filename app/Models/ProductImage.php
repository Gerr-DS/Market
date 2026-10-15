<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductImage extends Model { protected $fillable=['product_id','image_path','image_type']; public function product(){return $this->belongsTo(Product::class);} }
