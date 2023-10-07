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
            ['name' => 'Ketua Pengarah'],
            ['name' => 'Timbalan Ketua Pengarah'],
            ['name' => 'Pengarah Kanan'],
            ['name' => 'Penolong Pengarah Kanan'],
            ['name' => 'Penolong Pegawai'],
            ['name' => 'Pembantu Tadbir'],
            ['name' => 'Setiausaha Pejabat'],
            // Add more roles as needed
        ]);
    }
}
