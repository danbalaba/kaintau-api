<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Initial Admin Account
        User::create([
            'name' => 'KainTAU Admin',
            'email' => 'admin@kaintau.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create a Test Student Account
        $student = User::create([
            'name' => 'Demo Student',
            'email' => 'student@kaintau.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'department' => 'College of Engineering and Technology',
            'student_id' => '2026-10394',
            'email_verified_at' => now(),
        ]);

        // Seed realistic notifications for demo purposes
        \App\Models\Notification::create([
            'user_id' => $student->id,
            'title' => 'Order Ready for Pickup!',
            'message' => 'Your order #1024 (1x Cheesy Beef Burger) is ready for pickup at the Engineering Canteen.',
            'type' => 'order',
            'is_read' => false,
            'created_at' => now()->subMinutes(10),
        ]);

        \App\Models\Notification::create([
            'user_id' => $student->id,
            'title' => 'Wallet Top-up Successful!',
            'message' => 'PHP 500.00 has been credited successfully to your campus food wallet.',
            'type' => 'wallet',
            'is_read' => false,
            'created_at' => now()->subHours(2),
        ]);

        \App\Models\Notification::create([
            'user_id' => $student->id,
            'title' => 'Flash Promotion: Happy Hour!',
            'message' => 'Get 20% off all hot beverages at the Education Canteen from 3 PM to 5 PM today.',
            'type' => 'sale',
            'is_read' => true,
            'read_at' => now()->subHours(20),
            'created_at' => now()->subDays(1),
        ]);

        \App\Models\Notification::create([
            'user_id' => $student->id,
            'title' => 'Order Completed Successfully',
            'message' => 'Your order #1012 has been picked up. Thank you for eating with KainTAU!',
            'type' => 'order',
            'is_read' => true,
            'read_at' => now()->subDays(2),
            'created_at' => now()->subDays(2),
        ]);

        $this->call(MenuSeeder::class);
    }
}
