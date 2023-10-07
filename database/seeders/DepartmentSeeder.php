<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('departments')->insert([
            ['name' => 'Pejabat Ketua Pengarah'],
            ['name' => 'Bahagian Undang-Undang'],
            ['name' => 'Bahagian Penyelidikan'],
            ['name' => 'Bahagian Penerbitan'],
            ['name' => 'Bahagian Penyiaran'],
            ['name' => 'Bahagian Pengurusan Sumber Manusia'],
            ['name' => 'Bahagian Kewangan'],
            ['name' => 'Bahagian Pengurusan Maklumat'],
            // Add more departments as needed
        ]);
    }
}
