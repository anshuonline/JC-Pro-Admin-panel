<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $pass_md5 = md5($password);
    $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? AND password_md5 = ?");
    
    if (!$stmt) {
        $error = "Database Error: " . $conn->error;
    } else {
        $stmt->bind_param("ss", $username, $pass_md5);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Invalid credentials!';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JC Pro Admin - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            background-color: #f8fafc;
            background-image: radial-gradient(at 0% 0%, hsla(210,100%,93%,1) 0px, transparent 50%),
                              radial-gradient(at 100% 0%, hsla(240,100%,93%,1) 0px, transparent 50%);
            background-attachment: fixed;
        }
    </style>
</head>
<body class="text-slate-800 min-h-screen flex items-center justify-center p-4">
    
    <div class="bg-white/60 backdrop-blur-xl p-8 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white/60 w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-tr from-blue-500 to-indigo-500 rounded-full flex items-center justify-center mx-auto mb-5 shadow-lg shadow-blue-500/30">
                <i class="fa-solid fa-om text-4xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">JC Pro Admin</h1>
            <p class="text-slate-500 mt-2 font-medium">Sign in to your control panel</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2 font-medium">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php" class="space-y-5">
            <div>
                <label for="username" class="block text-sm font-semibold text-slate-700 mb-1.5">Admin ID</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-user text-slate-400"></i>
                    </div>
                    <input type="text" name="username" id="username" required
                        class="block w-full pl-10 pr-3 py-3 border border-slate-200/60 rounded-xl bg-white/50 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 focus:bg-white transition-all shadow-sm"
                        placeholder="Enter admin ID">
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-lock text-slate-400"></i>
                    </div>
                    <input type="password" name="password" id="password" required
                        class="block w-full pl-10 pr-3 py-3 border border-slate-200/60 rounded-xl bg-white/50 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 focus:bg-white transition-all shadow-sm"
                        placeholder="••••••••">
                </div>
            </div>

            <button type="submit"
                class="w-full mt-2 flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all hover:shadow-md">
                Sign In
            </button>
        </form>
    </div>

</body>
</html>
