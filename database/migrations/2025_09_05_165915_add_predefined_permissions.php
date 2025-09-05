<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            // Vulnerability Management - the main focus
            'view_vulnerabilities',
            'create_vulnerabilities',
            'edit_vulnerabilities',
            'delete_vulnerabilities',
            
            // Admin Dashboard access
            'view_admin_dashboard',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Create roles and assign permissions
        $admin = Role::create(['name' => 'admin']);
        $securityAnalyst = Role::create(['name' => 'security_analyst']);
        
        // Admin gets all permissions
        $admin->givePermissionTo([
            'view_vulnerabilities',
            'create_vulnerabilities', 
            'edit_vulnerabilities',
            'delete_vulnerabilities',
            'view_admin_dashboard',
        ]);

        // Security Analyst can view and create vulnerabilities but not delete
        $securityAnalyst->givePermissionTo([
            'view_vulnerabilities',
            'create_vulnerabilities',
            'edit_vulnerabilities',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete roles (this will also remove role-permission assignments)
        Role::whereIn('name', ['admin', 'security_analyst'])->delete();
        
        // Delete permissions
        Permission::whereIn('name', [
            'view_vulnerabilities', 'create_vulnerabilities', 'edit_vulnerabilities', 'delete_vulnerabilities',
            'view_admin_dashboard',
        ])->delete();
    }
};
