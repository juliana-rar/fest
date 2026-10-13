<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo administrator (see README). Change the password in production.
        User::query()->updateOrCreate(
            ['email' => 'admin@festesmajors.test'],
            ['name' => 'Administració', 'password' => 'festes2026'],
        )->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();

        $this->call([
            ComarcaSeeder::class,
            FiestaSeeder::class,
        ]);
    }
}
