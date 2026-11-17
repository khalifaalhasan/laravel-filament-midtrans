{{-- Akun Saya. No dedicated reference: uses the public layout and form panels from 05-checkout-detail-pesanan.png --}}
<x-public-layout title="Akun Saya">
    <div class="mx-auto max-w-2xl space-y-6">
        <h1 class="text-xl font-semibold">Akun Saya</h1>

        @if (session('status'))
            <p role="status" class="rounded-md bg-brand-50 px-3 py-2 text-sm text-brand-700">
                {{ ['profile-updated' => 'Profil berhasil disimpan.', 'password-updated' => 'Password berhasil diubah.'][session('status')] ?? session('status') }}
            </p>
        @endif

        <x-ui.panel title="Data Diri" icon="heroicon-s-user">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('patch')
                <x-ui.input name="name" label="Nama Lengkap" required autocomplete="name" :value="$user->name" />
                <x-ui.input name="email" type="email" label="Email" required autocomplete="username" :value="$user->email" />
                <x-ui.phone-input name="phone" :value="$user->phone" placeholder="81234567890" />
                <div class="flex justify-end">
                    <x-ui.button type="submit">Simpan</x-ui.button>
                </div>
            </form>
        </x-ui.panel>

        <x-ui.panel title="Ganti Password" icon="heroicon-s-lock-closed">
            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                @method('put')
                <x-ui.input name="current_password" type="password" label="Password Saat Ini" required bag="updatePassword" id="update_password_current_password" autocomplete="current-password" />
                <x-ui.input name="password" type="password" label="Password Baru" required bag="updatePassword" id="update_password_password" autocomplete="new-password" />
                <x-ui.input name="password_confirmation" type="password" label="Konfirmasi Password Baru" required bag="updatePassword" id="update_password_password_confirmation" autocomplete="new-password" />
                <div class="flex justify-end">
                    <x-ui.button type="submit">Ubah Password</x-ui.button>
                </div>
            </form>
        </x-ui.panel>

        <x-ui.panel title="Hapus Akun" icon="heroicon-s-trash">
            <div x-data="{ open: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
                <p class="text-sm text-gray-600">Akun dan data diri Anda akan dihapus permanen. Akun yang sudah punya pesanan tidak bisa dihapus karena data transaksi wajib disimpan.</p>
                <x-ui.button variant="outline" class="mt-4 !border-danger !text-danger hover:!bg-red-50" x-on:click="open = true" x-show="! open">Hapus akun saya</x-ui.button>
                <form method="POST" action="{{ route('profile.destroy') }}" class="mt-4 space-y-4" x-cloak x-show="open">
                    @csrf
                    @method('delete')
                    <x-ui.input name="password" type="password" label="Masukkan password untuk konfirmasi" required bag="userDeletion" id="delete_password" autocomplete="current-password" />
                    <div class="flex justify-end gap-3">
                        <x-ui.button variant="outline" x-on:click="open = false">Batal</x-ui.button>
                        <x-ui.button type="submit" class="!bg-danger hover:!bg-red-700">Hapus permanen</x-ui.button>
                    </div>
                </form>
            </div>
        </x-ui.panel>
    </div>
</x-public-layout>
