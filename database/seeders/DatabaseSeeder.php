<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Additional predefined users
        $users = [
            ['name'=>'Admin User','username'=>'admin','email'=>'admin@example.com','password'=>Hash::make('Admin123!'),'role'=>'admin'],
            ['name'=>'Organizer One','username'=>'organizer1','email'=>'org1@example.com','password'=>Hash::make('OrgPass123!'),'role'=>'organizer'],
            ['name'=>'Coach One','username'=>'coach1','email'=>'coach1@example.com','password'=>Hash::make('Coach123!'),'role'=>'organizer'],
            ['name'=>'Player One','username'=>'player1','email'=>'player1@example.com','password'=>Hash::make('PlayerPass1!'),'role'=>'player'],
            ['name'=>'Player Two','username'=>'player2','email'=>'player2@example.com','password'=>Hash::make('PlayerPass2!'),'role'=>'player'],
            ['name'=>'Player Three','username'=>'player3','email'=>'player3@example.com','password'=>Hash::make('PlayerPass3!'),'role'=>'player'],
            ['name'=>'Test User','username'=>'testuser','email'=>'test@example.com','password'=>Hash::make('password'),'role'=>'player'],
        ];

        foreach ($users as $user) {
            // Use updateOrCreate to avoid duplicate/key errors and ensure username is present
            User::updateOrCreate(['email' => $user['email']], $user);
        }
    }
}
