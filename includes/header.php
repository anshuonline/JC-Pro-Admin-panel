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
        body { font-family: 'Inter', sans-serif; }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1f2937; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #6b7280; }
    </style>
</head>
<body class="bg-gray-900 text-gray-200 h-screen flex overflow-hidden selection:bg-indigo-500 selection:text-white">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Header -->
        <header class="bg-gray-800 shadow-sm border-b border-gray-700 h-16 flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <button id="mobileMenuBtn" class="md:hidden text-gray-400 hover:text-white focus:outline-none">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h2 class="text-xl font-semibold text-white ml-4 md:ml-0" id="pageTitle">Dashboard</h2>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <button class="flex items-center space-x-2 text-sm focus:outline-none">
                        <img class="h-8 w-8 rounded-full border border-indigo-500 p-0.5" src="https://ui-avatars.com/api/?name=Admin&background=6366f1&color=fff" alt="Admin Avatar">
                        <span class="hidden md:block font-medium text-gray-300">Admin</span>
                        <i class="fa-solid fa-chevron-down text-xs text-gray-500"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Scrollable Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-900 p-6">
