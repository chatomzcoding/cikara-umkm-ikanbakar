<x-auth.shell title="Selamat Datang" page-title="Login">
  <form action="{{ route('login.store') }}" method="POST" class="space-y-4">
    @csrf
    <x-auth.input name="email" type="email" icon="email" placeholder="Email" required autofocus autocomplete="email" />
    <x-auth.input name="password" type="password" icon="lock" placeholder="Password" required autocomplete="current-password" />

    <div class="flex items-center justify-between">
      <label class="inline-flex items-center space-x-2">
        <input
          type="checkbox"
          name="remember"
          class="form-checkbox is-basic size-4.5 rounded border-slate-400/70 checked:border-primary checked:bg-primary dark:border-navy-400 dark:checked:border-accent dark:checked:bg-accent"
        />
        <span class="text-xs+ text-slate-500 dark:text-navy-100">Ingat saya</span>
      </label>
      @if (Route::has('password.request'))
        <a href="{{ route('password.request') }}" class="text-xs+ text-primary hover:underline dark:text-accent-light">
          Lupa password?
        </a>
      @endif
    </div>

    <x-auth.button class="mt-6">MASUK</x-auth.button>
  </form>

  @if (Route::has('register'))
    <div class="mt-4 text-center text-xs+ text-slate-400 dark:text-navy-300">
      Belum punya akun?
      <a href="{{ route('register') }}" class="text-primary hover:underline dark:text-accent-light">Daftar</a>
    </div>
  @endif
</x-auth.shell>
