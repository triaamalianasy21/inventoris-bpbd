<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/Config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?? 'PRANATA' ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" type="image/png" href="<?= $base_url ?? '' ?>favicon.png">
</head>

<body class="bg-[#EFEDEB] min-h-screen">
  <?php include __DIR__ . '/sidebar.php'; ?>

  <div id="sidebarBackdrop" class="fixed inset-0 z-40 hidden bg-black/40 lg:hidden"></div>

  <div class="ml-0 lg:ml-[260px] min-h-screen flex flex-col">
    <?php include __DIR__ . '/header.php'; ?>

    <main class="flex-1 p-4 sm:p-6 lg:p-8">
      <?= $content ?? '<p class="text-gray-600">Tidak ada konten.</p>' ?>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
  </div>

  <div id="logoutModal"
       class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
    <div class="w-[360px] max-w-[92vw] rounded-2xl bg-white p-5 shadow-xl">
      <div class="text-lg font-bold text-[#4A2A12]">Konfirmasi Keluar</div>
      <p class="mt-2 text-sm text-[#6B4B35]">Yakin ingin keluar?</p>
      <div class="mt-5 flex items-center justify-end gap-2">
        <button id="logoutCancel"
                class="px-4 py-2 rounded-xl border border-black/10 text-sm text-[#4A2A12] hover:bg-black/5">
          Batal
        </button>
        <button id="logoutConfirm"
                class="px-4 py-2 rounded-xl bg-[#F29A2E] text-white text-sm hover:bg-[#E4891B]">
          Keluar
        </button>
      </div>
    </div>
  </div>

  <script>
    (function() {
      const sidebar = document.getElementById('sidebar');
      const backdrop = document.getElementById('sidebarBackdrop');
      const toggle = document.getElementById('sidebarToggle');
      if (!sidebar || !backdrop || !toggle) return;

      const open = () => {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
      };
      const close = () => {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
      };

      toggle.addEventListener('click', open);
      backdrop.addEventListener('click', close);
      window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) close();
      });
    })();

    const logoutSidebar = document.getElementById('logoutSidebar');
    const logoutModal = document.getElementById('logoutModal');
    const logoutCancel = document.getElementById('logoutCancel');
    const logoutConfirm = document.getElementById('logoutConfirm');

    logoutSidebar?.addEventListener('click', function(e) {
      e.preventDefault();
      logoutModal?.classList.remove('hidden');
      logoutModal?.classList.add('flex');
      logoutConfirm?.setAttribute('data-href', this.href);
    });

    logoutCancel?.addEventListener('click', function() {
      logoutModal?.classList.add('hidden');
      logoutModal?.classList.remove('flex');
    });

    logoutModal?.addEventListener('click', function(e) {
      if (e.target === logoutModal) {
        logoutModal.classList.add('hidden');
        logoutModal.classList.remove('flex');
      }
    });

    logoutConfirm?.addEventListener('click', function() {
      const href = logoutConfirm.getAttribute('data-href');
      if (href) window.location.href = href;
    });
  </script>
</body>
</html>
