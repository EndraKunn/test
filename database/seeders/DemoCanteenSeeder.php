<?php

namespace Database\Seeders;

use App\Models\Canteen;
use App\Models\CommissionScheme;
use App\Models\DiningTable;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Tenant;
use App\Models\TenantBalance;
use App\Models\User;
use App\Models\UserTenantRole;
use Illuminate\Database\Seeder;

class DemoCanteenSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * 1. Satu kantin utama
         */
        $canteen = Canteen::updateOrCreate(
            ['code' => 'KNT-DEMO'],
            [
                'slug' => 'kantin-demo',
                'name' => 'Kantin Demo Poliwangi',
                'tax_rate' => 0.1000,
                'service_fee_rate' => 0.0200,
                'status' => 'active',
            ]
        );

        /*
         * 2. Dua tenant dalam kantin yang sama
         */
        $tenantA = Tenant::updateOrCreate(
            [
                'canteen_id' => $canteen->id,
                'code' => 'TEN-A',
            ],
            [
                'slug' => 'tenant-a',
                'display_name' => 'Warung Nusantara',
                'status' => 'active',
            ]
        );

        $tenantB = Tenant::updateOrCreate(
            [
                'canteen_id' => $canteen->id,
                'code' => 'TEN-B',
            ],
            [
                'slug' => 'tenant-b',
                'display_name' => 'Kedai Mantap',
                'status' => 'active',
            ]
        );

        /*
         * 3. Saldo masing-masing tenant
         */
        TenantBalance::updateOrCreate(
            ['tenant_id' => $tenantA->id],
            [
                'available_amount' => 500000,
                'held_amount' => 0,
            ]
        );

        TenantBalance::updateOrCreate(
            ['tenant_id' => $tenantB->id],
            [
                'available_amount' => 750000,
                'held_amount' => 0,
            ]
        );

        /*
         * 4. User untuk masing-masing tenant
         */
        $ownerA = User::updateOrCreate(
            ['email' => 'owner-a@example.com'],
            [
                'name' => 'Owner Tenant A',
                'password' => 'password',
                'role' => 'owner',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $ownerB = User::updateOrCreate(
            ['email' => 'owner-b@example.com'],
            [
                'name' => 'Owner Tenant B',
                'password' => 'password',
                'role' => 'owner',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        /*
         * 5. Hubungkan user dengan tenant
         */
        UserTenantRole::updateOrCreate(
            [
                'user_id' => $ownerA->id,
                'tenant_id' => $tenantA->id,
                'role' => 'owner',
            ],
            []
        );

        UserTenantRole::updateOrCreate(
            [
                'user_id' => $ownerB->id,
                'tenant_id' => $tenantB->id,
                'role' => 'owner',
            ],
            []
        );

        /*
 * 6. Meja kantin
 */
        foreach ([
            ['code' => 'T1', 'label' => 'Meja 1', 'zone' => 'Indoor'],
            ['code' => 'T2', 'label' => 'Meja 2', 'zone' => 'Indoor'],
            ['code' => 'T3', 'label' => 'Meja 3', 'zone' => 'Outdoor'],
        ] as $table) {
            DiningTable::updateOrCreate(
                [
                    'canteen_id' => $canteen->id,
                    'code' => $table['code'],
                ],
                [
                    'label' => $table['label'],
                    'zone' => $table['zone'],
                    'status' => 'active',
                ]
            );
        }
        /*
         * 7. Data tenant A
         */
        $categoryA = MenuCategory::updateOrCreate(
            [
                'tenant_id' => $tenantA->id,
                'name' => 'Makanan',
            ],
            [
                'sort_order' => 0,
                'is_active' => true,
            ]
        );

        Menu::updateOrCreate(
            [
                'tenant_id' => $tenantA->id,
                'name' => 'Nasi Goreng',
            ],
            [
                'category_id' => $categoryA->id,
                'base_price' => 15000,
                'stock_qty' => 50,
                'is_available' => true,
                'prep_minutes' => 10,
            ]
        );

        $modifierGroupA = ModifierGroup::updateOrCreate(
            [
                'tenant_id' => $tenantA->id,
                'name' => 'Pilihan Level',
            ],
            [
                'min_select' => 0,
                'max_select' => 1,
                'is_active' => true,
            ]
        );

        ModifierOption::updateOrCreate(
            [
                'tenant_id' => $tenantA->id,
                'group_id' => $modifierGroupA->id,
                'name' => 'Pedas',
            ],
            [
                'price_delta' => 0,
                'stock_qty' => 50,
                'is_available' => true,
            ]
        );

        CommissionScheme::firstOrCreate(
            ['tenant_id' => $tenantA->id],
            [
                'commission_rate' => 0.1500,
                'valid_from' => now()->subMonth(),
                'valid_to' => null,
            ]
        );

        /*
         * 8. Data tenant B
         */
        $categoryB = MenuCategory::updateOrCreate(
            [
                'tenant_id' => $tenantB->id,
                'name' => 'Minuman',
            ],
            [
                'sort_order' => 0,
                'is_active' => true,
            ]
        );

        Menu::updateOrCreate(
            [
                'tenant_id' => $tenantB->id,
                'name' => 'Es Teh',
            ],
            [
                'category_id' => $categoryB->id,
                'base_price' => 5000,
                'stock_qty' => 50,
                'is_available' => true,
                'prep_minutes' => 5,
            ]
        );

        $modifierGroupB = ModifierGroup::updateOrCreate(
            [
                'tenant_id' => $tenantB->id,
                'name' => 'Ukuran',
            ],
            [
                'min_select' => 0,
                'max_select' => 1,
                'is_active' => true,
            ]
        );

        ModifierOption::updateOrCreate(
            [
                'tenant_id' => $tenantB->id,
                'group_id' => $modifierGroupB->id,
                'name' => 'Large',
            ],
            [
                'price_delta' => 2000,
                'stock_qty' => 50,
                'is_available' => true,
            ]
        );

        CommissionScheme::firstOrCreate(
            ['tenant_id' => $tenantB->id],
            [
                'commission_rate' => 0.1500,
                'valid_from' => now()->subMonth(),
                'valid_to' => null,
            ]
        );

        $this->command->info('Demo canteen data berhasil dibuat/diperbarui.');
    }
}
