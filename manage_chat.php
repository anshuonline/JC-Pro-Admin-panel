<?php
require_once 'config.php';
check_auth();
?>
<?php include 'includes/header.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('pageTitle').innerText = 'Manage Global Chat';
    });
</script>

<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">Global Chat Moderation</h1>
        <p class="text-slate-500 mt-1 text-sm md:text-base">Monitor real-time messages, ban spammers, and manage banned words.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Live Chat Panel -->
        <div class="lg:col-span-2 flex flex-col h-[700px]">
            <div class="bg-white rounded-t-2xl shadow-sm border border-slate-200 border-b-0 p-4 flex justify-between items-center bg-slate-50">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <h2 class="text-lg font-bold text-slate-800">Live Chat</h2>
                </div>
            </div>
            
            <div id="chatBox" class="flex-1 bg-slate-100 border border-slate-200 overflow-y-auto p-4 space-y-4">
                <div class="text-center text-slate-400 text-sm mt-10">Loading messages...</div>
            </div>
            
            <div class="bg-white rounded-b-2xl shadow-sm border border-slate-200 border-t-0 p-4">
                <div id="replyBanner" class="hidden flex items-center justify-between bg-slate-100 p-2 rounded-t-xl border border-slate-200 border-b-0">
                    <div class="flex items-center gap-2 overflow-hidden">
                        <i class="fa-solid fa-reply text-orange-500 text-sm"></i>
                        <span id="replyUsername" class="text-sm font-bold text-slate-700"></span>
                        <span id="replyMessageSnippet" class="text-xs text-slate-500 truncate whitespace-nowrap"></span>
                    </div>
                    <button type="button" onclick="cancelReply()" class="text-slate-400 hover:text-red-500 px-2">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <form id="chatForm" class="flex gap-2">
                    <input type="text" id="chatMessage" class="flex-1 px-4 py-2 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-none transition-all" placeholder="Type a message as Admin..." required autocomplete="off">
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-6 rounded-xl transition-colors shadow-sm">
                        Send
                    </button>
                </form>
            </div>
        </div>

        <!-- Banned Words Config -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-6">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fa-solid fa-filter text-orange-500"></i>
                    <h2 class="text-lg font-bold text-slate-800">Profanity Filter</h2>
                </div>
                <p class="text-sm text-slate-500 mb-4">Messages containing these words will have them replaced with <code class="bg-slate-100 text-slate-700 px-1 rounded">***</code>. Separate words with commas.</p>
                
                <form id="bannedWordsForm">
                    <div class="mb-4">
                        <textarea id="bannedWordsInput" rows="10" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-none transition-all resize-y" placeholder="e.g. badword1, badword2..."></textarea>
                    </div>
                    <button type="submit" id="saveWordsBtn" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-xl transition-colors shadow-sm">
                        Save Filter
                    </button>
                    <div id="wordsStatus" class="mt-3 text-sm font-medium text-center hidden"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let chatPollingInterval;
    let isAutoScroll = true;
    let currentReplyId = null;

    document.addEventListener('DOMContentLoaded', () => {
        fetchChats();
        fetchBannedWords();
        chatPollingInterval = setInterval(fetchChats, 3000);

        const chatBox = document.getElementById('chatBox');
        chatBox.addEventListener('scroll', () => {
            // Check if user scrolled up
            if (chatBox.scrollTop + chatBox.clientHeight < chatBox.scrollHeight - 50) {
                isAutoScroll = false;
            } else {
                isAutoScroll = true;
            }
        });

        document.getElementById('chatForm').addEventListener('submit', (e) => {
            e.preventDefault();
            sendChat();
        });

        document.getElementById('bannedWordsForm').addEventListener('submit', (e) => {
            e.preventDefault();
            updateBannedWords();
        });
    });

    async function fetchChats() {
        try {
            const res = await fetch('admin_api_chat.php?action=get_chats');
            const text = await res.text();
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    renderChats(data.data);
                } else {
                    document.getElementById('chatBox').innerHTML = '<div class="text-center text-red-500 text-sm mt-10 p-4 font-bold border border-red-200 bg-red-50 rounded">Database Error: ' + data.message + '</div>';
                }
            } catch (e) {
                console.error('JSON Parse Error:', text);
                document.getElementById('chatBox').innerHTML = '<div class="text-center text-red-500 text-sm mt-10 p-4 font-bold border border-red-200 bg-red-50 rounded">Server Error:<br>' + text.substring(0, 200) + '...</div>';
            }
        } catch (e) {
            console.error('Failed to fetch chats');
            document.getElementById('chatBox').innerHTML = '<div class="text-center text-red-500 text-sm mt-10">Network Error. Check console.</div>';
        }
    }

    function renderChats(chats) {
        const chatBox = document.getElementById('chatBox');
        if (chats.length === 0) {
            chatBox.innerHTML = '<div class="text-center text-slate-400 text-sm mt-10">No messages yet.</div>';
            return;
        }

        // We receive newest first (DESC), so we reverse to render top to bottom
        chats.reverse();

        let html = '';
        chats.forEach(chat => {
            const date = new Date(chat.created_at);
            const timeStr = date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            const isAdmin = chat.google_uid === 'admin_uid';
            const isBanned = chat.is_chat_banned == 1;
            const profileUrl = chat.profile_picture || 'https://ui-avatars.com/api/?name=' + chat.username;
            
            const banBtnHtml = isAdmin ? '' : `
                <button onclick="toggleBan('${chat.google_uid}')" class="text-xs px-2 py-1 rounded ${isBanned ? 'bg-red-100 text-red-600 hover:bg-red-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300'} transition-colors" title="${isBanned ? 'Unban User' : 'Ban User'}">
                    <i class="fa-solid ${isBanned ? 'fa-user-check' : 'fa-ban'}"></i> ${isBanned ? 'Unban' : 'Ban'}
                </button>
            `;
            
            const muteBtnHtml = isAdmin ? '' : `
                <select onchange="if(this.value) muteUser('${chat.google_uid}', this.value); this.value='';" class="text-xs px-2 py-1 rounded bg-slate-200 text-slate-600 hover:bg-slate-300 outline-none cursor-pointer" title="Mute User">
                    <option value="">Mute</option>
                    <option value="1">1 Hour</option>
                    <option value="24">24 Hours</option>
                    <option value="168">7 Days</option>
                </select>
            `;
            
            let replyHtml = '';
            if (chat.reply_to_id) {
                const rName = chat.reply_username || 'Unknown';
                const rMsg = chat.reply_message ? (chat.reply_message.substring(0, 30) + (chat.reply_message.length > 30 ? '...' : '')) : 'Deleted message';
                replyHtml = `
                    <div class="flex items-center gap-1 text-[11px] text-slate-500 mb-1 bg-slate-100 rounded px-2 py-0.5 w-fit border border-slate-200">
                        <i class="fa-solid fa-reply text-[9px] text-orange-400"></i>
                        <span class="font-bold text-slate-600">${rName}</span>
                        <span class="truncate max-w-[200px]">${rMsg}</span>
                    </div>
                `;
            }
            
            html += `
                <div class="flex gap-3 hover:bg-white/50 p-2 rounded-lg transition-colors group">
                    <img src="${profileUrl}" alt="${chat.username}" class="w-10 h-10 rounded-full bg-slate-200 object-cover shrink-0 ${chat.reply_to_id ? 'mt-4' : ''}">
                    <div class="flex-1 min-w-0">
                        ${replyHtml}
                        <div class="flex items-baseline justify-between mb-0.5">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-sm ${isAdmin ? 'text-orange-600' : 'text-slate-800'}">${chat.username}</span>
                                ${chat.is_premium == 1 && !isAdmin ? '<span class="text-[10px] font-bold text-orange-500 bg-orange-50 px-1 rounded">PRO</span>' : ''}
                                ${isAdmin ? '<span class="text-[10px] font-bold text-white bg-orange-500 px-1.5 py-0.5 rounded">ADMIN</span>' : ''}
                                <span class="text-xs text-slate-400">${timeStr}</span>
                            </div>
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick="replyTo(${chat.id}, '${chat.username.replace(/'/g, "\\'")}', '${chat.message.replace(/'/g, "\\'").substring(0,20)}')" class="text-xs px-2 py-1 rounded bg-orange-100 text-orange-600 hover:bg-orange-200 transition-colors" title="Reply to Message">
                                    <i class="fa-solid fa-reply"></i>
                                </button>
                                ${muteBtnHtml}
                                ${banBtnHtml}
                                <button onclick="deleteChat(${chat.id})" class="text-xs px-2 py-1 rounded bg-red-100 text-red-600 hover:bg-red-200 transition-colors" title="Delete Message">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <p class="text-sm text-slate-700 break-words ${isBanned ? 'line-through opacity-50' : ''}">${chat.message}</p>
                    </div>
                </div>
            `;
        });

        chatBox.innerHTML = html;
        if (isAutoScroll) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }

    function replyTo(id, username, message) {
        currentReplyId = id;
        document.getElementById('replyUsername').innerText = 'Replying to ' + username + ':';
        document.getElementById('replyMessageSnippet').innerText = message + (message.length >= 20 ? '...' : '');
        document.getElementById('replyBanner').classList.remove('hidden');
        document.getElementById('chatMessage').focus();
    }

    function cancelReply() {
        currentReplyId = null;
        document.getElementById('replyBanner').classList.add('hidden');
    }

    async function sendChat() {
        const input = document.getElementById('chatMessage');
        const message = input.value.trim();
        if (!message) return;

        input.disabled = true;
        const formData = new FormData();
        formData.append('action', 'send_chat');
        formData.append('message', message);
        if (currentReplyId !== null) {
            formData.append('reply_to_id', currentReplyId);
        }

        try {
            const res = await fetch('admin_api_chat.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                input.value = '';
                cancelReply();
                isAutoScroll = true; // Force scroll to bottom when sending
                fetchChats();
            } else {
                alert('Error sending message: ' + data.message);
            }
        } catch (e) {
            alert('Request failed');
        } finally {
            input.disabled = false;
            input.focus();
        }
    }

    async function deleteChat(id) {
        if (!confirm('Delete this message permanently?')) return;
        
        const formData = new FormData();
        formData.append('action', 'delete_chat');
        formData.append('id', id);

        try {
            const res = await fetch('admin_api_chat.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                fetchChats();
            } else {
                alert('Error deleting: ' + data.message);
            }
        } catch (e) {
            alert('Request failed');
        }
    }

    async function toggleBan(google_uid) {
        if (!confirm('Toggle ban for this user?')) return;
        
        try {
            const fd = new FormData();
            fd.append('action', 'ban_user');
            fd.append('google_uid', google_uid);
            
            const res = await fetch('admin_api_chat.php', { method: 'POST', body: fd });
            const data = await res.json();
            
            if (data.success) {
                fetchChats();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            console.error(e);
            alert('Failed to ban/unban user');
        }
    }

    async function muteUser(google_uid, duration) {
        const hours = parseInt(duration);
        const durationStr = hours === 1 ? '1 Hour' : (hours === 24 ? '24 Hours' : '7 Days');
        if (!confirm('Mute this user for ' + durationStr + '?')) return;
        
        try {
            const fd = new FormData();
            fd.append('action', 'mute_user');
            fd.append('google_uid', google_uid);
            fd.append('duration', duration);
            
            const res = await fetch('admin_api_chat.php', { method: 'POST', body: fd });
            const data = await res.json();
            
            if (data.success) {
                fetchChats();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            console.error(e);
            alert('Failed to mute user');
        }
    }

    async function fetchBannedWords() {
        try {
            const res = await fetch('admin_api_chat.php?action=get_banned_words');
            const data = await res.json();
            if (data.success) {
                document.getElementById('bannedWordsInput').value = data.words;
            }
        } catch (e) {
            console.error('Failed to fetch banned words');
        }
    }

    async function updateBannedWords() {
        const btn = document.getElementById('saveWordsBtn');
        const words = document.getElementById('bannedWordsInput').value;
        const status = document.getElementById('wordsStatus');

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        
        const formData = new FormData();
        formData.append('action', 'update_banned_words');
        formData.append('words', words);

        try {
            const res = await fetch('admin_api_chat.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            status.classList.remove('hidden', 'text-red-600', 'text-emerald-600');
            if (data.success) {
                status.classList.add('text-emerald-600');
                status.innerHTML = '<i class="fa-solid fa-check mr-1"></i> Filter updated successfully';
            } else {
                status.classList.add('text-red-600');
                status.innerHTML = '<i class="fa-solid fa-times mr-1"></i> Error updating filter';
            }
        } catch (e) {
            status.classList.remove('hidden');
            status.classList.add('text-red-600');
            status.innerHTML = 'Request failed';
        } finally {
            btn.disabled = false;
            btn.innerText = 'Save Filter';
            setTimeout(() => { status.classList.add('hidden'); }, 3000);
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
