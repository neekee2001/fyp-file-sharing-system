<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permissions')->insert([
            ['name' => 'Viewer',
             'description' => 'Able to download the file'],
            ['name' => 'Editor',
             'description' => 'Able to download and rename the file'],
            // Add more permissions as needed
        ]);
    }
}
