    {{-- Anggota dan role tenant --}}
    @can('assignRole', $tenant)
        <section style="margin-top: 24px; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px; background: white;">
            <h2 style="font-size: 18px; font-weight: 700;">
                Anggota Tenant
            </h2>

            <p style="margin-top: 6px; color: #64748b; font-size: 14px;">
                Kelola pengguna yang memiliki akses ke tenant ini.
            </p>

            @error('role')
                <div style="margin-top: 12px; padding: 10px; border-radius: 8px; background: #fef2f2; color: #b91c1c;">
                    {{ $message }}
                </div>
            @enderror

            <div style="display: grid; gap: 12px; margin-top: 16px;">
                @forelse ($tenant->tenantRoles as $membership)
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; padding: 14px; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <div>
                            <p style="font-weight: 700;">
                                {{ $membership->user->name }}
                            </p>

                            <p style="margin-top: 4px; font-size: 14px; color: #64748b;">
                                {{ $membership->user->email }}
                            </p>

                            <p style="margin-top: 4px; font-size: 14px;">
                                Role: <strong>{{ $membership->role }}</strong>
                            </p>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('admin.tenants.roles.destroy', [$tenant, $membership->user]) }}"
                            onsubmit="return confirm('Yakin ingin mencabut role {{ $membership->role }} dari pengguna ini?')"
                        >
                            @csrf
                            @method('DELETE')

                            <input
                                type="hidden"
                                name="role"
                                value="{{ $membership->role }}"
                            >

                            <button
                                type="submit"
                                style="padding: 8px 12px; border: 1px solid #dc2626; border-radius: 6px; background: white; color: #dc2626; cursor: pointer;"
                            >
                                Cabut Role
                            </button>
                        </form>
                    </div>
                @empty
                    <p style="padding: 14px; border: 1px dashed #cbd5e1; border-radius: 8px; color: #64748b;">
                        Belum ada anggota yang ditetapkan pada tenant ini.
                    </p>
                @endforelse
            </div>

            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <h3 style="font-size: 17px; font-weight: 700;">
                    Tetapkan Role Baru
                </h3>

                <form
                    method="POST"
                    action="{{ route('admin.tenants.roles.store', $tenant) }}"
                    style="display: grid; gap: 16px; max-width: 520px; margin-top: 16px;"
                >
                    @csrf

                    <div>
                        <label for="member_email" style="display: block; margin-bottom: 6px; font-weight: 600;">
                            Email Pengguna
                        </label>

                        <input
                            id="member_email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            maxlength="255"
                            required
                            style="width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px;"
                        >

                        @error('email')
                            <p style="margin-top: 5px; color: #b91c1c;">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="member_role" style="display: block; margin-bottom: 6px; font-weight: 600;">
                            Role
                        </label>

                        <select
                            id="member_role"
                            name="role"
                            required
                            style="width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px;"
                        >
                            <option value="">Pilih role</option>
                            <option value="owner" @selected(old('role') === 'owner')>
                                Owner
                            </option>
                            <option value="operator" @selected(old('role') === 'operator')>
                                Operator
                            </option>
                            <option value="cashier" @selected(old('role') === 'cashier')>
                                Cashier
                            </option>
                        </select>

                        @error('role')
                            <p style="margin-top: 5px; color: #b91c1c;">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <button
                            type="submit"
                            style="padding: 10px 16px; border: 0; border-radius: 8px; background: #1e293b; color: white; cursor: pointer;"
                        >
                            Tetapkan Role
                        </button>
                    </div>
                </form>
            </div>
        </section>
    @endcan

@component('layouts.admin', ['title' => 'Kelola Tenant'])
    @if (session('status'))
        <div style="margin-bottom: 20px; padding: 12px 16px; border: 1px solid #bbf7d0; border-radius: 8px; background: #f0fdf4; color: #166534;">
            {{ session('status') }}
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
        <div>
            <h1 style="font-size: 24px; font-weight: 700;">
                {{ $tenant->display_name }}
            </h1>

            <p style="margin-top: 6px; color: #64748b;">
                Kode: {{ $tenant->code }} | Slug: {{ $tenant->slug }}
            </p>
        </div>

        <x-status-badge :status="$tenant->status">
            {{ $tenant->status }}
        </x-status-badge>
    </div>

    {{-- Saldo tenant --}}
    <section style="padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px; background: white;">
        <h2 style="font-size: 18px; font-weight: 700;">Saldo Tenant</h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-top: 16px;">
            <div>
                <p style="font-size: 14px; color: #64748b;">Saldo Tersedia</p>
                <p style="margin-top: 5px; font-size: 20px; font-weight: 700;">
                    Rp {{ number_format($tenant->balance?->available_amount ?? 0, 0, ',', '.') }}
                </p>
            </div>

            <div>
                <p style="font-size: 14px; color: #64748b;">Saldo Ditahan</p>
                <p style="margin-top: 5px; font-size: 20px; font-weight: 700;">
                    Rp {{ number_format($tenant->balance?->held_amount ?? 0, 0, ',', '.') }}
                </p>
            </div>
        </div>
    </section>

    {{-- Riwayat komisi --}}
    <section style="margin-top: 24px; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px; background: white;">
        <h2 style="font-size: 18px; font-weight: 700;">Riwayat Skema Komisi</h2>

        <div style="display: grid; gap: 10px; margin-top: 16px;">
            @forelse ($commissions as $commission)
                <div style="padding: 14px; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <p style="font-size: 18px; font-weight: 700;">
                        {{ number_format((float) $commission->commission_rate * 100, 2) }}%
                    </p>

                    <p style="margin-top: 5px; font-size: 13px; color: #64748b;">
                        Mulai: {{ $commission->valid_from }}
                    </p>

                    <p style="font-size: 13px; color: #64748b;">
                        Berakhir: {{ $commission->valid_to ?? 'Belum ditentukan' }}
                    </p>
                </div>
            @empty
                <p style="color: #64748b;">Belum ada skema komisi.</p>
            @endforelse
        </div>
    </section>

    {{-- Form penjadwalan komisi --}}
    @can('scheduleCommission', $tenant)
        <section style="margin-top: 24px; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px; background: white;">
            <h2 style="font-size: 18px; font-weight: 700;">Jadwalkan Komisi Baru</h2>

            <p style="margin: 8px 0 16px; font-size: 14px; color: #64748b;">
                Tarif baru berlaku pada waktu yang dipilih. Skema sebelumnya tetap tersimpan sebagai riwayat.
            </p>

            <form
                method="POST"
                action="{{ route('admin.tenants.commission.store', $tenant) }}"
                style="display: grid; gap: 16px; max-width: 520px;"
            >
                @csrf

                <div>
                    <label for="commission_rate" style="display: block; margin-bottom: 6px; font-weight: 600;">
                        Tarif Komisi (%)
                    </label>

                    <input
                        id="commission_rate"
                        type="number"
                        name="commission_rate"
                        value="{{ old('commission_rate') }}"
                        min="0"
                        max="100"
                        step="0.01"
                        required
                        style="width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px;"
                    >

                    @error('commission_rate')
                        <p style="margin-top: 5px; color: #b91c1c;">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="effective_at" style="display: block; margin-bottom: 6px; font-weight: 600;">
                        Waktu Mulai Berlaku
                    </label>

                    <input
                        id="effective_at"
                        type="datetime-local"
                        name="effective_at"
                        value="{{ old('effective_at', now()->addDay()->format('Y-m-d\TH:i')) }}"
                        required
                        style="width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px;"
                    >

                    @error('effective_at')
                        <p style="margin-top: 5px; color: #b91c1c;">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <button
                        type="submit"
                        style="padding: 10px 16px; border: 0; border-radius: 8px; background: #1e293b; color: white; cursor: pointer;"
                    >
                        Jadwalkan Komisi
                    </button>
                </div>
            </form>
        </section>
    @endcan

    {{-- Rekening bank --}}
    @can('manageBank', $tenant)
        <section style="margin-top: 24px; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px; background: white;">
            <h2 style="font-size: 18px; font-weight: 700;">Rekening Bank Tenant</h2>

            <p style="margin-top: 6px; font-size: 14px; color: #64748b;">
                Nomor rekening lengkap tidak ditampilkan. Hanya empat digit terakhir yang terlihat.
            </p>

            <div style="display: grid; gap: 12px; margin-top: 16px;">
                @forelse ($tenant->bankAccounts as $account)
                    <div style="padding: 16px; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
                            <div>
                                <h3 style="font-weight: 700;">
                                    {{ $account->bank_code }}
                                    - {{ $account->account_holder }}
                                </h3>

                                <p style="margin-top: 5px; color: #64748b;">
                                    Rekening berakhiran ****{{ $account->account_last4 }}
                                </p>

                                <p style="margin-top: 5px; font-size: 14px;">
                                    Status: {{ $account->status }}
                                </p>

                                @if ($account->is_primary)
                                    <p style="margin-top: 5px; color: #15803d; font-weight: 600;">
                                        Rekening Utama
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px;">
                            @if ($account->status !== 'verified')
                                <form
                                    method="POST"
                                    action="{{ route('admin.tenants.bank.verify', [$tenant, $account]) }}"
                                >
                                    @csrf

                                    <input type="hidden" name="status" value="verified">

                                    <button
                                        type="submit"
                                        style="padding: 8px 12px; border: 1px solid #16a34a; border-radius: 6px; background: white; color: #15803d; cursor: pointer;"
                                    >
                                        Verifikasi
                                    </button>
                                </form>
                            @endif

                            @if ($account->status !== 'rejected')
                                <form
                                    method="POST"
                                    action="{{ route('admin.tenants.bank.verify', [$tenant, $account]) }}"
                                >
                                    @csrf

                                    <input type="hidden" name="status" value="rejected">

                                    <button
                                        type="submit"
                                        style="padding: 8px 12px; border: 1px solid #dc2626; border-radius: 6px; background: white; color: #dc2626; cursor: pointer;"
                                    >
                                        Tolak
                                    </button>
                                </form>
                            @endif

                            @if (! $account->is_primary)
                                <form
                                    method="POST"
                                    action="{{ route('admin.tenants.bank.primary', [$tenant, $account]) }}"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        style="padding: 8px 12px; border: 1px solid #64748b; border-radius: 6px; background: white; color: #334155; cursor: pointer;"
                                    >
                                        Jadikan Rekening Utama
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="padding: 16px; border: 1px dashed #cbd5e1; border-radius: 8px; color: #64748b;">
                        Belum ada rekening bank yang terdaftar.
                    </p>
                @endforelse
            </div>

            {{-- Form tambah rekening --}}
            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <h3 style="font-size: 17px; font-weight: 700;">Tambah Rekening Bank</h3>

                <form
                    method="POST"
                    action="{{ route('admin.tenants.bank.store', $tenant) }}"
                    style="display: grid; gap: 16px; max-width: 520px; margin-top: 16px;"
                >
                    @csrf

                    <div>
                        <label for="bank_code" style="display: block; margin-bottom: 6px; font-weight: 600;">
                            Kode Bank
                        </label>

                        <input
                            id="bank_code"
                            type="text"
                            name="bank_code"
                            value="{{ old('bank_code') }}"
                            maxlength="20"
                            required
                            style="width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px;"
                        >

                        @error('bank_code')
                            <p style="margin-top: 5px; color: #b91c1c;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="account_holder" style="display: block; margin-bottom: 6px; font-weight: 600;">
                            Nama Pemilik Rekening
                        </label>

                        <input
                            id="account_holder"
                            type="text"
                            name="account_holder"
                            value="{{ old('account_holder') }}"
                            maxlength="120"
                            required
                            style="width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px;"
                        >

                        @error('account_holder')
                            <p style="margin-top: 5px; color: #b91c1c;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="account_number" style="display: block; margin-bottom: 6px; font-weight: 600;">
                            Nomor Rekening
                        </label>

                        <input
                            id="account_number"
                            type="text"
                            name="account_number"
                            inputmode="numeric"
                            pattern="[0-9]{6,20}"
                            minlength="6"
                            maxlength="20"
                            autocomplete="off"
                            required
                            style="width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px;"
                        >

                        <p style="margin-top: 5px; font-size: 13px; color: #64748b;">
                            Masukkan 6-20 digit. Nomor rekening tidak akan diisi kembali otomatis setelah validasi gagal.
                        </p>

                        @error('account_number')
                            <p style="margin-top: 5px; color: #b91c1c;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <button
                            type="submit"
                            style="padding: 10px 16px; border: 0; border-radius: 8px; background: #1e293b; color: white; cursor: pointer;"
                        >
                            Simpan Rekening
                        </button>
                    </div>
                </form>
            </div>
        </section>
    @endcan

    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
        <a
            href="{{ route('admin.tenants.index') }}"
            style="color: #334155; text-decoration: underline;"
        >
            Kembali ke Daftar Tenant
        </a>
    </div>
@endcomponent