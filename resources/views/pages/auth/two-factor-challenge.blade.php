<x-auth.shell
  title="Verifikasi Dua Langkah"
  description="Masukkan kode dari aplikasi authenticator Anda."
  page-title="Verifikasi Dua Langkah"
>
  <form action="{{ route('two-factor.login.store') }}" method="POST" class="space-y-4">
    @csrf
    <div data-two-factor-mode="code">
      <x-auth.input name="code" icon="key" placeholder="Kode 6 digit" inputmode="numeric" autocomplete="one-time-code" autofocus />
    </div>
    <div data-two-factor-mode="recovery" class="hidden">
      <x-auth.input name="recovery_code" icon="key" placeholder="Kode pemulihan" autocomplete="off" />
    </div>

    <x-auth.button class="mt-6">VERIFIKASI</x-auth.button>
  </form>

  <div class="mt-4 text-center text-xs+">
    <button type="button" id="two-factor-switch" class="text-primary hover:underline dark:text-accent-light">
      Gunakan kode pemulihan
    </button>
  </div>

  <script>
    (function () {
      var button = document.getElementById("two-factor-switch");
      var code = document.querySelector("[data-two-factor-mode=code]");
      var recovery = document.querySelector("[data-two-factor-mode=recovery]");

      button.addEventListener("click", function () {
        var useRecovery = recovery.classList.contains("hidden");
        code.classList.toggle("hidden", useRecovery);
        recovery.classList.toggle("hidden", !useRecovery);
        code.querySelector("input").value = "";
        recovery.querySelector("input").value = "";
        (useRecovery ? recovery : code).querySelector("input").focus();
        button.textContent = useRecovery ? "Gunakan kode authenticator" : "Gunakan kode pemulihan";
      });
    })();
  </script>
</x-auth.shell>
