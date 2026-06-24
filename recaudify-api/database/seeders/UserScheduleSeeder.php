<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserSchedule;
use Illuminate\Database\Seeder;

class UserScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where("username", "!=", "superadmin")->get();
        //$users = User::where('username', 'admin')->firstOrFail();

        foreach ($users as $user) {
            for ($day = 0; $day <= 6; $day++) {
                UserSchedule::firstOrCreate(
                    ["user_id" => $user->id, "day_of_week" => $day],
                    ["start_time" => "00:00:00", "end_time" => "23:59:00", "show_status" => true],
                );
            }
        }
    }
}
