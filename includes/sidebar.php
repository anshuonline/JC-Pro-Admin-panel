<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-gray-800 border-r border-gray-700 hidden md:flex md:flex-col shadow-xl z-20 transition-transform duration-300" id="sidebar">
    <div class="h-16 flex items-center px-6 border-b border-gray-700">
        <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center mr-3 shadow-lg shadow-indigo-500/30">
            <i class="fa-solid fa-om text-white text-sm"></i>
        </div>
        <span class="text-white text-lg font-bold tracking-wider">JC Pro</span>
        <button id="closeSidebar" class="ml-auto md:hidden text-gray-400 hover:text-white">
            <i class="fa-solid fa-times"></i>
        </button>
    </div>
    
    <div class="overflow-y-auto overflow-x-hidden flex-grow py-6">
        <div class="px-4 mb-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Menu</p>
        </div>
        <nav class="space-y-1 px-3">
            <a href="dashboard.php" class="<?php echo $currentPage == 'dashboard.php' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200">
                <i class="fa-solid fa-chart-line mr-3 w-5 text-center <?php echo $currentPage == 'dashboard.php' ? 'text-white' : 'text-gray-400 group-hover:text-white'; ?>"></i>
                Dashboard
            </a>
            
            <a href="users.php" class="<?php echo $currentPage == 'users.php' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200">
                <i class="fa-solid fa-users mr-3 w-5 text-center <?php echo $currentPage == 'users.php' ? 'text-white' : 'text-gray-400 group-hover:text-white'; ?>"></i>
                Users
            </a>
            
            <a href="challenges.php" class="<?php echo $currentPage == 'challenges.php' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200">
                <i class="fa-solid fa-trophy mr-3 w-5 text-center <?php echo $currentPage == 'challenges.php' ? 'text-white' : 'text-gray-400 group-hover:text-white'; ?>"></i>
                Challenges
            </a>
            
            <a href="content.php" class="<?php echo $currentPage == 'content.php' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200">
                <i class="fa-solid fa-file-lines mr-3 w-5 text-center <?php echo $currentPage == 'content.php' ? 'text-white' : 'text-gray-400 group-hover:text-white'; ?>"></i>
                Content Pages
            </a>
        </nav>

        <div class="px-4 mt-8 mb-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Account</p>
        </div>
        <nav class="space-y-1 px-3">
            <a href="logout.php" class="text-red-400 hover:bg-red-500/10 hover:text-red-300 group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors">
                <i class="fa-solid fa-right-from-bracket mr-3 w-5 text-center"></i>
                Sign Out
            </a>
        </nav>
    </div>
    
    <div class="p-4 border-t border-gray-700">
        <div class="flex items-center">
            <div class="ml-3">
                <p class="text-sm font-medium text-white">Version 1.0</p>
                <p class="text-xs text-gray-400">JapaCounter Pro</p>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile Overlay -->
<div id="mobileOverlay" class="fixed inset-0 bg-gray-900 bg-opacity-75 z-10 hidden transition-opacity"></div>

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
