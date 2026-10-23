<x-auth.shell title="Buat Password Baru" description="Masukkan password baru untuk akun Anda." page-title="Reset Password">
  <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ request()->route('token') }}" />

    <x-auth.input name="email" type="email" icon="email" placeholder="Email" :value="old('email', request('email'))" required autocomplete="email" />
    <x-auth.input name="password" type="password" icon="lock" placeholder="Password baru" required autofocus autocomplete="new-password" />
    <x-auth.input name="password_confirmation" type="password" icon="lock" placeholder="Ulangi password baru" required autocomplete="new-password" />

    <x-auth.button class="mt-6">SIMPAN PASSWORD</x-auth.button>
  </form>
</x-auth.shell>
