<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionseeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name'=>'tambah-user']);
        Permission::create(['name' => 'edit-user']);
        Permission::create(['name' => 'hapus-user']);
        Permission::create(['name' => 'lihat-user']);

        Permission::create(['name' => 'tambah-bahan']);
        Permission::create(['name' => 'edit-bahan']);
        Permission::create(['name' => 'hapus-bahan']);
        Permission::create(['name' => 'lihat-bahan']);


        Role::create(['name' => 'administrator']);
        Role::create(['name' => 'purchasing']);

        $roleAdmin = Role::findByName('administrator');
        $roleAdmin->givePermissionTo('tambah-user');
        $roleAdmin->givePermissionTo('edit-user');
        $roleAdmin->givePermissionTo('hapus-user');
        $roleAdmin->givePermissionTo('lihat-user');

        $rolePurchasing = Role::findByName('purchasing');
        $rolePurchasing->givePermissionTo('tambah-bahan');
        $rolePurchasing->givePermissionTo('edit-bahan');
        $rolePurchasing->givePermissionTo('hapus-bahan');
        $rolePurchasing->givePermissionTo('lihat-bahan');
    }
}
