<x-auth.shell
  title="Lupa Password"
  description="Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat password baru."
>
  <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
    @csrf
    <x-auth.input name="email" type="email" icon="email" placeholder="Email" required autofocus autocomplete="email" />

    <x-auth.button class="mt-6">KIRIM TAUTAN RESET</x-auth.button>
  </form>

  <div class="mt-4 text-center text-xs+ text-slate-400 dark:text-navy-300">
    Ingat password Anda?
    <a href="{{ route('login') }}" class="text-primary hover:underline dark:text-accent-light">Kembali ke login</a>
  </div>
</x-auth.shell>
