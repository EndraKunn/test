\# Data Dictionary — Kantin Multi-Tenant



\## 1. Canteens



| Field | Type | Keterangan |

|---|---|---|

| id | bigint unsigned | Primary key |

| code | varchar(30) | Kode kantin, unique |

| slug | varchar(100) | URL slug, unique |

| name | varchar(120) | Nama kantin |

| tax\_rate | decimal(6,4) | Tarif pajak |

| service\_fee\_rate | decimal(6,4) | Tarif service |

| status | varchar(20) | Status kantin |

| created\_at | timestamp(6) | Waktu dibuat |

| updated\_at | timestamp(6) | Waktu diperbarui |



\## 2. Tenants



| Field | Type | Keterangan |

|---|---|---|

| id | bigint unsigned | Primary key |

| canteen\_id | bigint unsigned | FK ke canteens |

| code | varchar(30) | Kode tenant |

| slug | varchar(100) | URL slug |

| display\_name | varchar(120) | Nama tenant |

| status | varchar(20) | Status tenant |

| created\_at | timestamp(6) | Waktu dibuat |

| updated\_at | timestamp(6) | Waktu diperbarui |

| deleted\_at | timestamp(6) | Soft delete |



Constraint penting:

\- UNIQUE `(canteen\_id, code)`

\- UNIQUE `(canteen\_id, slug)`

\- UNIQUE `(id, canteen\_id)`



\## 3. Tenant Balances



| Field | Type | Keterangan |

|---|---|---|

| tenant\_id | bigint unsigned | PK dan FK ke tenants |

| available\_amount | bigint unsigned | Saldo tersedia |

| held\_amount | bigint unsigned | Saldo ditahan |

| created\_at | timestamp(6) | Waktu dibuat |

| updated\_at | timestamp(6) | Waktu diperbarui |



\## 4. User Tenant Roles



| Field | Type | Keterangan |

|---|---|---|

| id | bigint unsigned | Primary key |

| user\_id | bigint unsigned | FK ke users |

| tenant\_id | bigint unsigned | FK ke tenants |

| role | varchar(30) | owner/operator/cashier |

| created\_at | timestamp(6) | Waktu dibuat |

| updated\_at | timestamp(6) | Waktu diperbarui |



Constraint:

\- UNIQUE `(user\_id, tenant\_id, role)`



\## 5. Menu Categories



| Field | Keterangan |

|---|---|

| id | Primary key |

| tenant\_id | Pemilik tenant |

| name | Nama kategori |

| sort\_order | Urutan kategori |

| is\_active | Status aktif |



\## 6. Menus



| Field | Keterangan |

|---|---|

| id | Primary key |

| tenant\_id | Pemilik tenant |

| category\_id | Kategori menu |

| name | Nama menu |

| base\_price | Harga dasar |

| stock\_qty | Jumlah stok |

| is\_available | Ketersediaan |

| prep\_minutes | Waktu persiapan |



Composite FK:

`(tenant\_id, category\_id)` → `menu\_categories(tenant\_id, id)`



\## 7. Modifier Groups



| Field | Keterangan |

|---|---|

| id | Primary key |

| tenant\_id | Pemilik tenant |

| name | Nama grup modifier |

| min\_select | Minimal pilihan |

| max\_select | Maksimal pilihan |

| is\_active | Status aktif |



\## 8. Modifier Options



| Field | Keterangan |

|---|---|

| id | Primary key |

| tenant\_id | Pemilik tenant |

| group\_id | Grup modifier |

| name | Nama pilihan |

| price\_delta | Perubahan harga |

| stock\_qty | Jumlah stok |

| is\_available | Ketersediaan |



Composite FK:

`(tenant\_id, group\_id)` → `modifier\_groups(tenant\_id, id)`



\## 9. Commission Schemes



| Field | Keterangan |

|---|---|

| id | Primary key |

| tenant\_id | Pemilik tenant |

| commission\_rate | Persentase komisi |

| valid\_from | Mulai berlaku |

| valid\_to | Akhir berlaku |

## 10. Dining Tables

| Field | Type | Keterangan |
|---|---|---|
| id | bigint unsigned | Primary key |
| canteen_id | bigint unsigned | FK ke canteens |
| code | varchar(30) | Kode meja |
| label | varchar(60) | Label/nama meja |
| zone | varchar(60), nullable | Zona meja |
| status | varchar(20) | Status meja |
| created_at | timestamp(6) | Waktu dibuat |
| updated_at | timestamp(6) | Waktu diperbarui |

Constraint:

- UNIQUE `(canteen_id, code)`
- FK `canteen_id` → `canteens.id`


## 11. Orders

| Field | Type | Keterangan |
|---|---|---|
| id | bigint unsigned | Primary key |
| public_id | char(36) | ID publik order |
| order_number | varchar(30) | Nomor order |
| canteen_id | bigint unsigned | FK ke canteens |
| customer_session_id | char(26), nullable | Sesi customer |
| checkout_key | varchar(100) | Kunci checkout, unique |
| tracking_token_hash | varbinary(32) | Hash token tracking |
| status | varchar(30) | Status order |
| subtotal_amount | bigint unsigned | Subtotal |
| tax_amount | bigint unsigned | Pajak |
| service_fee_amount | bigint unsigned | Biaya layanan |
| grand_total_amount | bigint unsigned | Total akhir |
| customer_snapshot | json, nullable | Snapshot data customer |
| table_snapshot | json, nullable | Snapshot data meja |
| placed_at | timestamp(6), nullable | Waktu order ditempatkan |
| created_at | timestamp(6), nullable | Waktu dibuat |
| updated_at | timestamp(6), nullable | Waktu diperbarui |

Constraint penting:

- `public_id` unique
- `order_number` unique
- `checkout_key` unique
- `tracking_token_hash` unique
- FK `canteen_id` → `canteens.id`
- `orders` tidak memiliki `tenant_id`

Catatan:

Order merupakan order induk pada level platform/kantin. Satu order dapat memiliki beberapa `tenant_orders`.


## 12. Tenant Orders

| Field | Type | Keterangan |
|---|---|---|
| id | bigint unsigned | Primary key |
| order_id | bigint unsigned | FK ke orders |
| tenant_id | bigint unsigned | FK ke tenants |
| commission_id | bigint unsigned | FK ke commission_schemes |
| status | varchar(30) | Status tenant order |
| scheduled_at | timestamp(6), nullable | Waktu terjadwal |
| commission_rate_snapshot | decimal(6,4) | Snapshot komisi |
| subtotal_amount | bigint unsigned | Subtotal |
| tax_amount | bigint unsigned | Pajak |
| service_fee_amount | bigint unsigned | Biaya layanan |
| commission_amount | bigint unsigned | Nilai komisi |
| net_amount | bigint unsigned | Nilai bersih |
| created_at | timestamp(6), nullable | Waktu dibuat |
| updated_at | timestamp(6), nullable | Waktu diperbarui |

Constraint penting:

- UNIQUE `(order_id, tenant_id)`
- UNIQUE `(tenant_id, id)`
- Composite FK `(tenant_id, commission_id)` → `commission_schemes(tenant_id, id)`
- FK `tenant_id` → `tenants.id`

Relasi ini memungkinkan satu `orders` memiliki beberapa `tenant_orders` untuk tenant yang berbeda.


## 13. Order Items

| Field | Type | Keterangan |
|---|---|---|
| id | bigint unsigned | Primary key |
| tenant_id | bigint unsigned | FK ke tenants |
| tenant_order_id | bigint unsigned | FK ke tenant_orders |
| menu_id | bigint unsigned | FK ke menus |
| name_snapshot | varchar(120) | Snapshot nama menu |
| unit_price_snapshot | bigint unsigned | Snapshot harga satuan |
| prep_minutes_snapshot | smallint unsigned | Snapshot waktu persiapan |
| quantity | int unsigned | Jumlah |
| modifier_total | bigint unsigned | Total modifier |
| line_total | bigint unsigned | Total baris |
| created_at | timestamp(6), nullable | Waktu dibuat |
| updated_at | timestamp(6), nullable | Waktu diperbarui |

Constraint penting:

- UNIQUE `(tenant_id, id)`
- Composite FK `(tenant_id, menu_id)` → `menus(tenant_id, id)`
- Composite FK `(tenant_id, tenant_order_id)` → `tenant_orders(tenant_id, id)`

Catatan:

Order item menyimpan snapshot nama, harga, dan waktu persiapan menu pada saat transaksi.


## 14. Withdrawals

| Field | Type | Keterangan |
|---|---|---|
| id | bigint unsigned | Primary key |
| tenant_id | bigint unsigned | FK ke tenants |
| bank_account_id | bigint unsigned | FK ke tenant_bank_accounts |
| requested_by | bigint unsigned | User yang meminta withdrawal |
| reviewed_by | bigint unsigned, nullable | User yang melakukan review |
| idempotency_key | varchar(100) | Kunci idempotensi |
| amount | bigint unsigned | Jumlah withdrawal |
| status | varchar(30) | Status withdrawal |
| transfer_snapshot | json, nullable | Snapshot transfer |
| active_tenant_lock | bigint unsigned, nullable | Lock withdrawal aktif |
| created_at | timestamp(6), nullable | Waktu dibuat |
| updated_at | timestamp(6), nullable | Waktu diperbarui |

Constraint penting:

- UNIQUE `idempotency_key`
- UNIQUE `active_tenant_lock`
- FK `tenant_id` → `tenants.id`
- FK `bank_account_id` → `tenant_bank_accounts.id`
- FK `requested_by` → `users.id`
- FK `reviewed_by` → `users.id`
