<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            ['role_name' => 'Ketua Pengarah'],
            ['role_name' => 'Timbalan Ketua Pengarah'],
            ['role_name' => 'Pengarah Kanan'],
            ['role_name' => 'Penolong Pengarah Kanan'],
            ['role_name' => 'Penolong Pegawai'],
            ['role_name' => 'Pembantu Tadbir'],
            ['role_name' => 'Setiausaha Pejabat'],
            // Add more roles as needed
        ]);
    }
}
