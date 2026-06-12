<?php
// C:\xampp\htdocs\JC Pro Admin panel\meditation.php
require_once 'config.php';
check_auth();
include 'includes/header.php';
?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Meditation Dashboard</h1>
        <p class="text-slate-500 text-sm mt-1 font-medium">Live tracking and overall statistics of user meditations.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" id="statsContainer">
    <!-- These will be updated via AJAX -->
    <div class="bg-white/60 backdrop-blur-xl rounded-2xl p-6 border border-white shadow-[0_4px_24px_rgb(0,0,0,0.02)] relative overflow-hidden group hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all">
        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
            <i class="fa-solid fa-satellite-dish text-6xl text-rose-500"></i>
        </div>
        <h3 class="text-slate-500 text-sm font-semibold mb-1">Active Meditators</h3>
        <p class="text-3xl font-bold text-slate-800" id="statActive">0</p>
        <div class="mt-4 flex items-center text-sm font-medium">
            <span class="text-rose-600 flex items-center"><i class="fa-solid fa-circle-dot mr-1.5 animate-pulse"></i> Live Now</span>
        </div>
    </div>

    <div class="bg-white/60 backdrop-blur-xl rounded-2xl p-6 border border-white shadow-[0_4px_24px_rgb(0,0,0,0.02)] relative overflow-hidden group hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all">
        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
            <i class="fa-solid fa-hourglass text-6xl text-blue-500"></i>
        </div>
        <h3 class="text-slate-500 text-sm font-semibold mb-1">Total Hours Meditated</h3>
        <p class="text-3xl font-bold text-slate-800" id="statHours">0</p>
        <div class="mt-4 flex items-center text-sm font-medium">
            <span class="text-blue-600 flex items-center"><i class="fa-solid fa-clock mr-1.5"></i> Cumulative Time</span>
        </div>
    </div>

    <div class="bg-white/60 backdrop-blur-xl rounded-2xl p-6 border border-white shadow-[0_4px_24px_rgb(0,0,0,0.02)] relative overflow-hidden group hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all">
        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
            <i class="fa-solid fa-users text-6xl text-emerald-500"></i>
        </div>
        <h3 class="text-slate-500 text-sm font-semibold mb-1">Total Participants</h3>
        <p class="text-3xl font-bold text-slate-800" id="statPeople">0</p>
        <div class="mt-4 flex items-center text-sm font-medium">
            <span class="text-emerald-600 flex items-center"><i class="fa-solid fa-user-check mr-1.5"></i> Unique Users</span>
        </div>
    </div>

    <div class="bg-white/60 backdrop-blur-xl rounded-2xl p-6 border border-white shadow-[0_4px_24px_rgb(0,0,0,0.02)] relative overflow-hidden group hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all">
        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
            <i class="fa-solid fa-award text-6xl text-amber-500"></i>
        </div>
        <h3 class="text-slate-500 text-sm font-semibold mb-1">Max Meditation Time</h3>
        <p class="text-3xl font-bold text-slate-800" id="statMax">0 <span class="text-lg text-slate-500 font-medium">min</span></p>
        <div class="mt-4 flex items-center text-sm font-medium">
            <span class="text-amber-600 flex items-center"><i class="fa-solid fa-fire mr-1.5"></i> Single Session</span>
        </div>
    </div>
</div>

<div class="bg-white/60 backdrop-blur-xl border border-white rounded-2xl shadow-[0_4px_24px_rgb(0,0,0,0.02)] overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-100 bg-white/40 flex items-center justify-between">
        <h3 class="text-lg font-bold text-slate-800">Currently Active Users</h3>
        <span class="text-sm text-slate-500 font-medium">Auto-refreshes every 5s</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="text-xs uppercase bg-slate-50/50 text-slate-500 border-b border-slate-100">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold">Username</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Expected Duration</th>
                    <th scope="col" class="px-6 py-4 font-semibold text-right">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100/60" id="activeUsersList">
                <tr>
                    <td colspan="3" class="px-6 py-10 text-center text-slate-500">
                        <i class="fa-solid fa-spinner fa-spin text-2xl mb-3 block opacity-30"></i>
                        <span class="font-medium">Loading live data...</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    function updateStats() {
        fetch('api/japacounterpro/get_meditation_stats.php')
            .then(res => res.json())
            .then(data => {
                document.getElementById('statActive').innerText = data.active_count;
                document.getElementById('statHours').innerText = data.total_hours;
                document.getElementById('statPeople').innerText = data.total_people;
                document.getElementById('statMax').innerHTML = data.max_minutes + ' <span class="text-lg text-slate-500 font-medium">min</span>';
                
                const list = document.getElementById('activeUsersList');
                if (data.active_users.length === 0) {
                    list.innerHTML = `
                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center text-slate-500">
                                <i class="fa-solid fa-moon text-4xl mb-3 block opacity-30"></i>
                                <span class="font-medium">No active meditators right now.</span>
                            </td>
                        </tr>
                    `;
                } else {
                    let html = '';
                    data.active_users.forEach(user => {
                        html += `
                        <tr class="hover:bg-white/60 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-800 flex items-center">
                                <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mr-3 font-bold border border-rose-200/50">
                                    ${user.username.charAt(0).toUpperCase()}
                                </div>
                                ${user.username}
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-600">
                                ${Math.round(user.expected_duration_seconds / 60)} minutes
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="bg-rose-50 text-rose-600 py-1 px-3 rounded-full text-xs font-bold border border-rose-200/50 inline-flex items-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5 animate-pulse"></span>
                                    Meditating
                                </span>
                            </td>
                        </tr>
                        `;
                    });
                    list.innerHTML = html;
                }
            })
            .catch(err => console.error("Error fetching stats:", err));
    }

    // Initial load
    updateStats();
    
    // Refresh every 5 seconds
    setInterval(updateStats, 5000);
</script>

<?php include 'includes/footer.php'; ?>
