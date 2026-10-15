<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | PRANATA</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" type="image/png" href="<?= $base_url ?? '' ?>favicon.png" />
</head>

<body class="min-h-screen flex items-center justify-center bg-[#F8E4A6] px-4">
  <div class="w-full max-w-md">

    <!-- Logo + Title -->
    <div class="flex flex-col items-center mb-6">
      <div class="w-10 h-10 rounded-xl bg-[#E07A2F] flex items-center justify-center shadow-sm">
        <!-- icon cube simple -->
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M12 2l8 4.5v11L12 22 4 17.5v-11L12 2z" stroke="white" stroke-width="1.8" />
          <path d="M12 2v20" stroke="white" stroke-width="1.2" opacity="0.7" />
          <path d="M4 6.5l8 4 8-4" stroke="white" stroke-width="1.2" opacity="0.7" />
        </svg>
      </div>
      <h1 class="mt-3 text-2xl font-extrabold tracking-wide text-[#7A2F15]">PRANATA</h1>
      <p class="text-sm text-[#9A4A25]">Masuk ke akun anda</p>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-md p-8">

      <!-- Success/Error message dari controller -->
      <?php if (isset($_SESSION['success'])) : ?>
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
          <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['error'])) : ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
          <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <form method="POST" action="index.php?page=login_process" class="space-y-4">

        <!-- Username -->
        <div>
          <label class="block text-sm font-semibold text-[#7A2F15] mb-2">Username</label>
          <input
            type="text"
            name="username"
            placeholder="Masukkan username anda"
            required
            class="w-full rounded-lg border border-[#E7B38D] px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-[#F1A46E] focus:border-[#F1A46E] placeholder:text-[#C7A08A]" />
        </div>

        <!-- Password + Lupa -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="block text-sm font-semibold text-[#7A2F15]">Password</label>
          </div>

          <div class="relative">
            <input
              id="password"
              type="password"
              name="password"
              placeholder="Masukkan password anda"
              required
              class="w-full rounded-lg border border-[#E7B38D] px-4 py-2.5 pr-11 text-sm outline-none focus:ring-2 focus:ring-[#F1A46E] focus:border-[#F1A46E] placeholder:text-[#C7A08A]" />

            <!-- toggle eye -->
            <button
              type="button"
              id="togglePassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-[#7A2F15]/70 hover:text-[#7A2F15]"
              aria-label="Tampilkan/Sembunyikan password">
              <svg id="eyeOpen" width="20" height="20" viewBox="0 0 24 24" fill="none" class="hidden" aria-hidden="true">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" stroke="currentColor" stroke-width="1.8" />
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8" />
              </svg>
              <svg id="eyeClosed" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.8" />
                <path d="M10.6 10.6A3 3 0 0012 15a3 3 0 001.4-.35" stroke="currentColor" stroke-width="1.8" />
                <path d="M6.5 6.5C4 8.7 2 12 2 12s3.5 7 10 7c2 0 3.7-.6 5.1-1.4" stroke="currentColor" stroke-width="1.8" />
                <path d="M14.5 9.2C13.8 9 13 8.9 12 8.9c-1 0-1.8.1-2.5.3" stroke="currentColor" stroke-width="1.8" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Button -->
        <button
          type="submit"
          class="w-full rounded-lg bg-[#E07A2F] py-2.5 text-sm font-semibold text-white shadow-sm hover:brightness-95 active:brightness-90">
          Masuk
        </button>

        <!-- Register -->
        <p class="text-center text-sm text-[#9A4A25] pt-1">
          Belum Punya Akun?
          <a href="index.php?page=register" class="font-semibold text-[#E07A2F] hover:underline">Daftar Sekarang</a>
        </p>

      </form>
    </div>
  </div>

  <script>
    const btn = document.getElementById('togglePassword');
    const input = document.getElementById('password');
    const eyeOpen = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');

    btn?.addEventListener('click', () => {
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      eyeOpen.classList.toggle('hidden', !isPassword);
      eyeClosed.classList.toggle('hidden', isPassword);
    });
  </script>
</body>

</html>
