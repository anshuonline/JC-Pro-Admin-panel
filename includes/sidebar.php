<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-white/60 backdrop-blur-2xl border-r border-slate-200/60 hidden md:flex md:flex-col shadow-[4px_0_24px_rgb(0,0,0,0.02)] z-20 transition-transform duration-300" id="sidebar">
    <div class="h-16 flex items-center px-6 border-b border-slate-200/60">
        <div class="w-8 h-8 bg-gradient-to-tr from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center mr-3 shadow-md shadow-blue-500/20">
            <i class="fa-solid fa-om text-white text-sm"></i>
        </div>
        <span class="text-slate-800 text-lg font-bold tracking-tight">JC Pro</span>
        <button id="closeSidebar" class="ml-auto md:hidden text-slate-400 hover:text-slate-800">
            <i class="fa-solid fa-times"></i>
        </button>
    </div>
    
    <div class="overflow-y-auto overflow-x-hidden flex-grow py-6">
        <div class="px-5 mb-3">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Menu</p>
        </div>
        <nav class="space-y-1.5 px-3">
            <a href="dashboard.php" class="<?php echo $currentPage == 'dashboard.php' ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 border border-transparent'; ?> group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                <i class="fa-solid fa-chart-line mr-3 w-5 text-center <?php echo $currentPage == 'dashboard.php' ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-500'; ?>"></i>
                Dashboard
            </a>
            
            <a href="users.php" class="<?php echo $currentPage == 'users.php' ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 border border-transparent'; ?> group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                <i class="fa-solid fa-users mr-3 w-5 text-center <?php echo $currentPage == 'users.php' ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-500'; ?>"></i>
                Users
            </a>
            

            <a href="content.php" class="<?php echo $currentPage == 'content.php' ? 'bg-blue-50 text-blue-700 shadow-sm border border-blue-100' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 border border-transparent'; ?> group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                <i class="fa-solid fa-file-lines mr-3 w-5 text-center <?php echo $currentPage == 'content.php' ? 'text-blue-600' : 'text-slate-400 group-hover:text-blue-500'; ?>"></i>
                Content Pages
            </a>
        </nav>

        <div class="px-5 mt-8 mb-3">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Account</p>
        </div>
        <nav class="space-y-1.5 px-3">
            <a href="logout.php" class="text-red-600 hover:bg-red-50 hover:text-red-700 border border-transparent hover:border-red-100 group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                <i class="fa-solid fa-right-from-bracket mr-3 w-5 text-center text-red-400 group-hover:text-red-600"></i>
                Sign Out
            </a>
        </nav>
    </div>
    
    <div class="p-4 border-t border-slate-200/60 bg-white/30 backdrop-blur-sm">
        <div class="flex items-center">
            <div class="ml-2">
                <p class="text-xs font-bold text-slate-800">Version 1.0</p>
                <p class="text-xs text-slate-500 font-medium">JapaCounter Pro</p>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile Overlay -->
<div id="mobileOverlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-10 hidden transition-opacity"></div>

<script>
    // Simple sidebar toggle for mobile
    const sidebar = document.getElementById('sidebar');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const closeSidebar = document.getElementById('closeSidebar');
    const mobileOverlay = document.getElementById('mobileOverlay');

    function toggleSidebar() {
        sidebar.classList.toggle('hidden');
        sidebar.classList.toggle('absolute');
        sidebar.classList.toggle('h-full');
        mobileOverlay.classList.toggle('hidden');
    }

    mobileMenuBtn?.addEventListener('click', toggleSidebar);
    closeSidebar?.addEventListener('click', toggleSidebar);
    mobileOverlay?.addEventListener('click', toggleSidebar);
</script>
