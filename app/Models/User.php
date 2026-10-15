<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    protected $fillable = ['name','email','phone','password','role'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array { return ['email_verified_at'=>'datetime','password'=>'hashed','wallet_balance'=>'decimal:2']; }
    public function products(){ return $this->hasMany(Product::class); }
    public function addresses(){ return $this->hasMany(UserAddress::class); }
    public function walletTransactions(){ return $this->hasMany(WalletTransaction::class); }
    public function orders(){ return $this->hasMany(Order::class, 'buyer_id'); }
}
