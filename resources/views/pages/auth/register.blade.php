<x-auth.shell title="Buat Akun" description="Isi data berikut untuk mendaftar." page-title="Daftar">
  <form action="{{ route('register.store') }}" method="POST" class="space-y-4">
    @csrf
    <x-auth.input name="name" icon="user" placeholder="Nama lengkap" required autofocus autocomplete="name" />
    <x-auth.input name="email" type="email" icon="email" placeholder="Email" required autocomplete="email" />
    <x-auth.input name="password" type="password" icon="lock" placeholder="Password" required autocomplete="new-password" />
    <x-auth.input name="password_confirmation" type="password" icon="lock" placeholder="Ulangi password" required autocomplete="new-password" />

    <x-auth.button class="mt-6">DAFTAR</x-auth.button>
  </form>

  <div class="mt-4 text-center text-xs+ text-slate-400 dark:text-navy-300">
    Sudah punya akun?
    <a href="{{ route('login') }}" class="text-primary hover:underline dark:text-accent-light">Masuk</a>
  </div>
</x-auth.shell>
