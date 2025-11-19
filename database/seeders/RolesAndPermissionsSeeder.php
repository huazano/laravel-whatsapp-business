<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos para usuarios de WhatsApp (guard: whatsapp)
        $whatsappPermissions = [
            'chatbot.basic.access',
            'chatbot.premium.access',
            'chatbot.vip.access',
            'chatbot.commands.basic',
            'chatbot.commands.advanced',
            'chatbot.support.request',
            'chatbot.notifications.receive',
        ];

        foreach ($whatsappPermissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'whatsapp']);
        }

        // Roles para usuarios de WhatsApp
        $guestRole = Role::create(['name' => 'guest', 'guard_name' => 'whatsapp']);
        $guestRole->givePermissionTo([
            'chatbot.basic.access',
            'chatbot.commands.basic',
        ]);

        $basicRole = Role::create(['name' => 'basic', 'guard_name' => 'whatsapp']);
        $basicRole->givePermissionTo([
            'chatbot.basic.access',
            'chatbot.commands.basic',
            'chatbot.notifications.receive',
        ]);

        $premiumRole = Role::create(['name' => 'premium', 'guard_name' => 'whatsapp']);
        $premiumRole->givePermissionTo([
            'chatbot.basic.access',
            'chatbot.premium.access',
            'chatbot.commands.basic',
            'chatbot.commands.advanced',
            'chatbot.support.request',
            'chatbot.notifications.receive',
        ]);

        $vipRole = Role::create(['name' => 'vip', 'guard_name' => 'whatsapp']);
        $vipRole->givePermissionTo($whatsappPermissions); // All permissions

        // Permisos para usuarios administradores (guard: web)
        $adminPermissions = [
            'admin.dashboard.view',
            'admin.users.view',
            'admin.users.create',
            'admin.users.edit',
            'admin.users.delete',
            'admin.whatsapp_users.view',
            'admin.whatsapp_users.create',
            'admin.whatsapp_users.edit',
            'admin.whatsapp_users.delete',
            'admin.roles.view',
            'admin.roles.create',
            'admin.roles.edit',
            'admin.roles.delete',
            'admin.permissions.view',
            'admin.permissions.assign',
            'admin.chatbot.configure',
            'admin.chatbot.messages.view',
            'admin.reports.view',
            'admin.reports.export',
        ];

        foreach ($adminPermissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Roles para usuarios administradores
        $superAdminRole = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdminRole->givePermissionTo($adminPermissions); // All admin permissions

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo([
            'admin.dashboard.view',
            'admin.whatsapp_users.view',
            'admin.whatsapp_users.edit',
            'admin.chatbot.messages.view',
            'admin.reports.view',
        ]);

        $supportRole = Role::create(['name' => 'support', 'guard_name' => 'web']);
        $supportRole->givePermissionTo([
            'admin.dashboard.view',
            'admin.whatsapp_users.view',
            'admin.chatbot.messages.view',
        ]);
    }
}
