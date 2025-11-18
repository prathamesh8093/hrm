<?php
// includes/footer.php
// Closes layout wrapper and includes mobile sidebar toggle script
?>
    </div> <!-- /.min-h-screen.flex (closed main layout) -->

    <!-- Mobile: show sidebar as collapsible panel -->
    <div class="lg:hidden fixed bottom-6 right-6 z-50">
      <button id="openSidebar" class="bg-violet-600 text-white p-3 rounded-full shadow-lg">☰</button>
    </div>

    <script>
      // mobile sidebar toggle: toggles #main-sidebar visibility and fixed positioning
      (function(){
        const openBtn = document.getElementById('openSidebar');
        if (!openBtn) return;
        openBtn.addEventListener('click', () => {
          const sidebar = document.getElementById('main-sidebar');
          if (!sidebar) return;
          sidebar.classList.toggle('hidden');
          sidebar.classList.toggle('fixed');
          sidebar.classList.toggle('left-0');
          sidebar.classList.toggle('top-0');
          sidebar.classList.toggle('h-full');
          sidebar.classList.toggle('z-50');
        });
      })();
    </script>
</body>
</html>
