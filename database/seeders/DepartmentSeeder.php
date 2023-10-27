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
            ['dep_name' => 'Pejabat Ketua Pengarah'],
            ['dep_name' => 'Bahagian Undang-Undang'],
            ['dep_name' => 'Bahagian Penyelidikan'],
            ['dep_name' => 'Bahagian Penerbitan'],
            ['dep_name' => 'Bahagian Penyiaran'],
            ['dep_name' => 'Bahagian Pengurusan Sumber Manusia'],
            ['dep_name' => 'Bahagian Kewangan'],
            ['dep_name' => 'Bahagian Pengurusan Maklumat'],
            // Add more departments as needed
        ]);
    }
}
