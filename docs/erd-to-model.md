\# ERD to Eloquent Model



\## Canteen



Database:

\- canteens



Model:

\- App\\Models\\Canteen



Relasi:

\- hasMany Tenant

\- hasMany DiningTable



\## Tenant



Database:

\- tenants



Model:

\- App\\Models\\Tenant



Relasi:

\- belongsTo Canteen

\- hasMany MenuCategory

\- hasMany Menu

\- hasMany ModifierGroup

\- hasMany ModifierOption

\- hasMany CommissionScheme



\## TenantBalance



Database:

\- tenant\_balances



Model:

\- App\\Models\\TenantBalance



Relasi:

\- belongsTo Tenant



\## MenuCategory



Database:

\- menu\_categories



Model:

\- App\\Models\\MenuCategory



Relasi:

\- belongsTo Tenant

\- hasMany Menu



\## Menu



Database:

\- menus



Model:

\- App\\Models\\Menu



Relasi:

\- belongsTo Tenant

\- belongsTo MenuCategory



Constraint:

\- `(tenant\_id, category\_id)` harus menunjuk ke category milik tenant yang sama.



\## ModifierGroup



Database:

\- modifier\_groups



Model:

\- App\\Models\\ModifierGroup



Relasi:

\- belongsTo Tenant

\- hasMany ModifierOption



\## ModifierOption



Database:

\- modifier\_options



Model:

\- App\\Models\\ModifierOption



Relasi:

\- belongsTo Tenant

\- belongsTo ModifierGroup



Constraint:

\- `(tenant\_id, group\_id)` harus menunjuk ke group milik tenant yang sama.



\## CommissionScheme



Database:

\- commission\_schemes



Model:

\- App\\Models\\CommissionScheme



Relasi:

\- belongsTo Tenant



\## UserTenantRole



Database:

\- user\_tenant\_roles



Model:

\- App\\Models\\UserTenantRole



Relasi:

\- belongsTo User

\- belongsTo Tenant

## DiningTable

Database:

- dining_tables

Model:

- App\Models\DiningTable

Relasi:

- belongsTo Canteen


## Order

Database:

- orders

Model:

- App\Models\Order

Relasi:

- belongsTo Canteen
- hasMany TenantOrder

Catatan:

- Order merupakan order induk.
- Order tidak memiliki `tenant_id`.
- Satu order dapat memiliki beberapa TenantOrder.


## TenantOrder

Database:

- tenant_orders

Model:

- App\Models\TenantOrder

Relasi:

- belongsTo Order
- belongsTo Tenant
- hasMany OrderItem

Constraint:

- `(tenant_id, commission_id)` harus menunjuk ke commission scheme milik tenant yang sama.


## OrderItem

Database:

- order_items

Model:

- App\Models\OrderItem

Relasi:

- belongsTo TenantOrder

Constraint:

- `(tenant_id, menu_id)` harus menunjuk ke menu milik tenant yang sama.
- `(tenant_id, tenant_order_id)` harus menunjuk ke tenant order milik tenant yang sama.

Catatan:

- Model saat ini mendefinisikan relasi `tenantOrder()`.


## Withdrawal

Database:

- withdrawals

Model:

- App\Models\Withdrawal

Relasi:

- belongsTo Tenant

Constraint:

- `tenant_id` harus menunjuk ke tenant yang valid.
- `bank_account_id` harus menunjuk ke rekening bank tenant.
- `requested_by` harus menunjuk ke user yang valid.
- `reviewed_by` dapat bernilai null.
- `idempotency_key` harus unique.
