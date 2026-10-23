<x-auth.shell
  title="Konfirmasi Password"
  description="Ini area aman. Masukkan password Anda untuk melanjutkan."
>
  <form action="{{ route('password.confirm.store') }}" method="POST" class="space-y-4">
    @csrf
    <x-auth.input name="password" type="password" icon="lock" placeholder="Password" required autofocus autocomplete="current-password" />

    <x-auth.button class="mt-6">KONFIRMASI</x-auth.button>
  </form>
</x-auth.shell>
