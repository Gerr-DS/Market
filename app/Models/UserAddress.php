<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UserAddress extends Model { protected $fillable=['user_id','recipient_name','phone','full_address','city_id','district_id','postal_code','is_primary']; protected function casts():array{return ['is_primary'=>'boolean'];} public function user(){return $this->belongsTo(User::class);} }
