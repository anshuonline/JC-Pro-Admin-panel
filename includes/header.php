<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JC Pro Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            background-color: #fafaf9; /* Warm very light stone/orange-ish tint */
            background-image: radial-gradient(at 0% 0%, hsla(28,100%,95%,1) 0px, transparent 50%),
                              radial-gradient(at 100% 0%, hsla(140,100%,96%,1) 0px, transparent 50%);
            background-attachment: fixed;
        }
    </style>
</head>
<body class="text-slate-800 h-screen flex overflow-hidden selection:bg-orange-500 selection:text-white">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden relative z-0">
        <!-- Top Header -->
        <header class="bg-white/70 backdrop-blur-md border-b border-slate-200 h-16 flex items-center justify-between px-6 z-10 sticky top-0">
            <div class="flex items-center">
                <button id="mobileMenuBtn" class="md:hidden text-slate-500 hover:text-slate-800 focus:outline-none">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-semibold text-slate-800 ml-4 md:ml-0" id="pageTitle">Dashboard</h2>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <button class="flex items-center space-x-2 text-sm focus:outline-none bg-white/50 px-3 py-1.5 rounded-full border border-slate-200/60 shadow-sm hover:bg-white transition-colors">
                        <img class="h-7 w-7 rounded-full" src="https://ui-avatars.com/api/?name=Admin&background=f97316&color=fff" alt="Admin Avatar">
                        <span class="hidden md:block font-medium text-slate-700">Admin</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Scrollable Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 relative">
