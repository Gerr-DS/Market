<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (['Makanan','Elektronik','Fashion','Rumah Tangga','Lainnya'] as $name) {
            Category::firstOrCreate(['slug'=>str($name)->slug()->toString()], ['name'=>$name]);
        }
        if (env('ADMIN_EMAIL') && env('ADMIN_PASSWORD')) {
            User::updateOrCreate(['email'=>env('ADMIN_EMAIL')], [
                'name'=>'Administrator', 'phone'=>'0000000000', 'role'=>'admin',
                'password'=>Hash::make(env('ADMIN_PASSWORD')),
            ]);
        }
    }
}
