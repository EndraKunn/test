@component('layouts.admin', ['title' => 'Buat Tenant'])
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 24px; font-weight: 700;">
            Buat Tenant
        </h1>

        <p style="margin-top: 6px; color: #64748b;">
            Daftarkan tenant, penanggung jawab, dan rekening bank utama.
        </p>
    </div>

    @if ($errors->any())
        <div
            role="alert"
            style="margin-bottom: 20px; padding: 12px; border: 1px solid #fecaca; border-radius: 8px; background: #fef2f2; color: #b91c1c;"
        >
            <strong>Periksa kembali data yang kamu masukkan.</strong>

            <ul style="margin: 8px 0 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('admin.tenants.store') }}"
        style="max-width: 680px;"
    >
        @csrf

        <section style="margin-bottom: 24px;">
            <h2 style="margin-bottom: 16px; font-size: 18px; font-weight: 700;">
                Informasi Tenant
            </h2>

            <div style="margin-bottom: 16px;">
                <label for="display_name" style="display: block; margin-bottom: 6px; font-weight: 600;">
                    Nama Tenant
                </label>

                <x-input
                    id="display_name"
                    name="display_name"
                    placeholder="Contoh: Warung Nusantara"
                    :value="old('display_name')"
                    required
                    maxlength="120"
                />

                @error('display_name')
                    <p style="margin-top: 4px; color: #b91c1c;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 16px;">
                <label for="code" style="display: block; margin-bottom: 6px; font-weight: 600;">
                    Kode Tenant
                </label>

                <x-input
                    id="code"
                    name="code"
                    placeholder="Contoh: TEN-NUSA"
                    :value="old('code')"
                    required
                    maxlength="30"
                />

                @error('code')
                    <p style="margin-top: 4px; color: #b91c1c;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 16px;">
                <label for="slug" style="display: block; margin-bottom: 6px; font-weight: 600;">
                    Slug Tenant
                </label>

                <x-input
                    id="slug"
                    name="slug"
                    placeholder="Contoh: warung-nusantara"
                    :value="old('slug')"
                    required
                    maxlength="100"
                />

                @error('slug')
                    <p style="margin-top: 4px; color: #b91c1c;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 16px;">
                <label for="commission_rate" style="display: block; margin-bottom: 6px; font-weight: 600;">
                    Tarif Komisi (%)
                </label>

                <x-input
                    id="commission_rate"
                    type="number"
                    name="commission_rate"
                    placeholder="15"
                    :value="old('commission_rate', '15')"
                    required
                    min="0"
                    max="100"
                    step="0.01"
                />

                <p style="margin-top: 5px; font-size: 13px; color: #64748b;">
                    Masukkan angka persen. Contoh 15 berarti komisi 15%.
                </p>

                @error('commission_rate')
                    <p style="margin-top: 4px; color: #b91c1c;">{{ $message }}</p>
                @enderror
            </div>
        </section>

        <section style="margin-bottom: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <h2 style="margin-bottom: 16px; font-size: 18px; font-weight: 700;">
                Penanggung Jawab (Owner)
            </h2>

            <div style="margin-bottom: 16px;">
                <label for="pic_name" style="display: block; margin-bottom: 6px; font-weight: 600;">
                    Nama Owner
                </label>

                <x-input
                    id="pic_name"
                    name="pic_name"
                    placeholder="Nama lengkap penanggung jawab"
                    :value="old('pic_name')"
                    required
                    maxlength="255"
                />

                @error('pic_name')
                    <p style="margin-top: 4px; color: #b91c1c;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 16px;">
                <label for="pic_email" style="display: block; margin-bottom: 6px; font-weight: 600;">
                    Email Owner
                </label>

                <x-input
                    id="pic_email"
                    type="email"
                    name="pic_email"
                    placeholder="owner@contoh.com"
                    :value="old('pic_email')"
                    required
                    maxlength="255"
                    autocomplete="email"
                />

                <p style="margin-top: 5px; font-size: 13px; color: #64748b;">
                    Email ini digunakan untuk mengirim tautan pengaturan kata sandi.
                </p>

                @error('pic_email')
                    <p style="margin-top: 4px; color: #b91c1c;">{{ $message }}</p>
                @enderror
            </div>
        </section>

        <section style="margin-bottom: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <h2 style="margin-bottom: 16px; font-size: 18px; font-weight: 700;">
                Rekening Bank Utama
            </h2>

            <div style="margin-bottom: 16px;">
                <label for="bank_code" style="display: block; margin-bottom: 6px; font-weight: 600;">
                    Kode Bank
                </label>

                <x-input
                    id="bank_code"
                    name="bank_code"
                    placeholder="Contoh: BCA"
                    :value="old('bank_code')"
                    required
                    maxlength="20"
                />

                @error('bank_code')
                    <p style="margin-top: 4px; color: #b91c1c;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 16px;">
                <label for="account_holder" style="display: block; margin-bottom: 6px; font-weight: 600;">
                    Nama Pemilik Rekening
                </label>

                <x-input
                    id="account_holder"
                    name="account_holder"
                    placeholder="Nama sesuai rekening bank"
                    :value="old('account_holder')"
                    required
                    maxlength="120"
                />

                @error('account_holder')
                    <p style="margin-top: 4px; color: #b91c1c;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 16px;">
                <label for="account_number" style="display: block; margin-bottom: 6px; font-weight: 600;">
                    Nomor Rekening
                </label>

                <x-input
                    id="account_number"
                    type="text"
                    name="account_number"
                    placeholder="Masukkan 6–20 digit nomor rekening"
                    required
                    inputmode="numeric"
                    pattern="[0-9]{6,20}"
                    minlength="6"
                    maxlength="20"
                    autocomplete="off"
                />

                <p style="margin-top: 5px; font-size: 13px; color: #64748b;">
                    Nomor rekening tidak akan diisi kembali secara otomatis jika validasi gagal.
                </p>

                @error('account_number')
                    <p style="margin-top: 4px; color: #b91c1c;">{{ $message }}</p>
                @enderror
            </div>
        </section>

        <div style="display: flex; align-items: center; gap: 12px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
            <a
                href="{{ route('admin.tenants.index') }}"
                style="display: inline-block; padding: 10px 16px; border: 1px solid #cbd5e1; border-radius: 8px; color: #334155; text-decoration: none;"
            >
                Batal
            </a>

            <x-button type="submit">
                Simpan Tenant
            </x-button>
        </div>
    </form>
@endcomponent