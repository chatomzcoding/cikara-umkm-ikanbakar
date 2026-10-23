<x-auth.shell
  title="Verifikasi Email"
  description="Klik tautan yang kami kirim ke email Anda untuk mengaktifkan akun."
>
  <form action="{{ route('verification.send') }}" method="POST">
    @csrf
    <x-auth.button>KIRIM ULANG EMAIL VERIFIKASI</x-auth.button>
  </form>

  <form action="{{ route('logout') }}" method="POST" class="mt-4 text-center">
    @csrf
    <button type="submit" class="text-xs+ text-primary hover:underline dark:text-accent-light">Keluar</button>
  </form>
</x-auth.shell>
