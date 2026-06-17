<?php
// C:\xampp\htdocs\JC Pro Admin panel\reviews.php
require_once 'config.php';

// Fetch top feedback
$feedback = [];
$res = $conn->query("SELECT * FROM feedback WHERE overall_rating >= 4 ORDER BY id DESC LIMIT 50");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $feedback[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Japa Counter Pro - User Testimonials</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="antialiased text-slate-800">

<!-- Header -->
<div class="bg-white/80 backdrop-blur-md border-b border-orange-100 sticky top-0 z-50">
    <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-orange-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-orange-600/30">
                <i class="fa-solid fa-om"></i>
            </div>
            <div>
                <h1 class="text-xl font-extrabold tracking-tight text-slate-900">Japa Counter Pro</h1>
                <p class="text-xs font-semibold text-orange-600 uppercase tracking-widest">Public Reviews</p>
            </div>
        </div>
        <div class="hidden sm:flex items-center gap-2 bg-orange-50 px-4 py-1.5 rounded-full border border-orange-200">
            <i class="fa-solid fa-star text-orange-500"></i>
            <span class="font-bold text-orange-700 text-sm">4.9 Average Rating</span>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-5xl mx-auto px-6 py-12">
    <div class="text-center mb-12">
        <h2 class="text-4xl md:text-5xl font-extrabold text-slate-900 tracking-tight mb-4">Loved by Devotees <br/><span class="text-orange-600">Worldwide</span></h2>
        <p class="text-lg text-slate-600 max-w-2xl mx-auto">See what our users have to say about their spiritual journey with Japa Counter Pro. We are committed to providing the best ad-free meditation experience.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($feedback as $f): ?>
            <?php if (!empty($f['likes_most'])): ?>
            <div class="bg-white rounded-2xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 flex flex-col h-full">
                
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold text-lg shadow-sm">
                            <?php echo strtoupper(substr($f['name'] ?: 'A', 0, 1)); ?>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 text-sm leading-tight">
                                <?php echo htmlspecialchars($f['name'] ?: 'Anonymous'); ?>
                            </h3>
                            <p class="text-xs font-medium text-slate-500">
                                <?php echo htmlspecialchars($f['app_usage']); ?> User
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-0.5 text-orange-400 text-xs">
                        <?php for($i=0; $i<$f['overall_rating']; $i++) echo '<i class="fa-solid fa-star"></i>'; ?>
                        <?php for($i=0; $i<(5-$f['overall_rating']); $i++) echo '<i class="fa-regular fa-star text-slate-200"></i>'; ?>
                    </div>
                </div>

                <div class="flex-grow">
                    <p class="text-slate-600 text-sm leading-relaxed italic relative">
                        <i class="fa-solid fa-quote-left text-orange-200 text-2xl absolute -top-2 -left-2 opacity-50 z-0"></i>
                        <span class="relative z-10">"<?php echo htmlspecialchars($f['likes_most']); ?>"</span>
                    </p>
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100 flex justify-between items-center text-xs text-slate-400 font-medium">
                    <span>Verified User <i class="fa-solid fa-check-circle text-green-500 ml-0.5"></i></span>
                    <span><?php echo date('M j, Y', strtotime($f['submitted_at'])); ?></span>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if (count($feedback) === 0): ?>
        <div class="text-center py-20 bg-white rounded-3xl border border-slate-200 border-dashed">
            <i class="fa-regular fa-comments text-4xl text-slate-300 mb-3"></i>
            <h3 class="text-xl font-bold text-slate-700">No reviews yet</h3>
            <p class="text-slate-500 mt-2">Check back later for user testimonials.</p>
        </div>
    <?php endif; ?>

    <div class="mt-16 text-center">
        <p class="text-sm text-slate-500 font-medium">&copy; <?php echo date('Y'); ?> Japa Counter Pro. All rights reserved.</p>
    </div>
</div>

</body>
</html>
