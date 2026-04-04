<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام التعرف على الوجوه - لوحة التحكم</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .detection-card {
            transition: all 0.3s ease;
        }
        
        .detection-card:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50" x-data="dashboard()" x-init="init()">
    
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-2xl font-bold text-blue-600">📹 نظام التعرف على الوجوه</span>
                </div>
                <div class="flex items-center space-x-4 space-x-reverse">
                    <button @click="requestNotificationPermission()" class="bg-blue-100 text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-200">
                        🔔 تفعيل الإشعارات
                    </button>
                    <span class="text-gray-500" x-text="currentTime"></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-full">
                        <span class="text-2xl">👥</span>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-500">الكشف اليوم</p>
                        <p class="text-2xl font-bold" x-text="stats.today_detections">0</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-full">
                        <span class="text-2xl">📊</span>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-500">أسبوع</p>
                        <p class="text-2xl font-bold" x-text="stats.week_detections">0</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="bg-purple-100 p-3 rounded-full">
                        <span class="text-2xl">👤</span>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-500">أشخاص فريدين</p>
                        <p class="text-2xl font-bold" x-text="stats.unique_people_today">0</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <span class="text-2xl">📷</span>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm text-gray-500">الكاميرات النشطة</p>
                        <p class="text-2xl font-bold" x-text="stats.active_cameras">0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Detections -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Detections List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <h2 class="text-xl font-bold">آخر الكشفات</h2>
                    </div>
                    <div class="p-6">
                        <div id="recent-detections" class="space-y-4">
                            <template x-for="detection in detections" :key="detection.id">
                                <div class="detection-card flex items-center p-4 bg-gray-50 rounded-lg">
                                    <img :src="detection.person?.image || '/images/default-avatar.png'" 
                                         class="w-16 h-16 rounded-full object-cover">
                                    <div class="mr-4 flex-1">
                                        <h4 class="font-bold text-lg" x-text="detection.person?.name || 'غير معروف'"></h4>
                                        <p class="text-gray-500" x-text="getTypeLabel(detection.person?.type)"></p>
                                        <p class="text-sm text-gray-400">
                                            <span x-text="detection.camera?.name || 'كاميرا'"></span>
                                            -
                                            <span x-text="formatTime(detection.detected_at)"></span>
                                        </p>
                                    </div>
                                    <div class="text-left">
                                        <span class="px-3 py-1 rounded-full text-sm"
                                              :class="getConfidenceClass(detection.confidence)">
                                            <span x-text="detection.confidence"></span>%
                                        </span>
                                    </div>
                                </div>
                            </template>
                            
                            <div x-show="detections.length === 0" class="text-center py-8 text-gray-500">
                                <p class="text-4xl mb-2">🔍</p>
                                <p>لا توجد كشفات حديثة</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-bold text-lg mb-4">⚡ إجراءات سريعة</h3>
                    <div class="space-y-3">
                        <a href="/people/create" class="block bg-blue-600 text-white text-center py-3 rounded-lg hover:bg-blue-700">
                            ➕ إضافة شخص جديد
                        </a>
                        <a href="/cameras/create" class="block bg-green-600 text-white text-center py-3 rounded-lg hover:bg-green-700">
                            📷 إضافة كاميرا
                        </a>
                        <a href="/search" class="block bg-purple-600 text-white text-center py-3 rounded-lg hover:bg-purple-700">
                            🔍 البحث بالصورة
                        </a>
                        <a href="/reports" class="block bg-yellow-600 text-white text-center py-3 rounded-lg hover:bg-yellow-700">
                            📋 التقارير
                        </a>
                    </div>
                </div>

                <!-- Notifications Log -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-bold text-lg mb-4">📝 سجل الإشعارات</h3>
                    <div id="notifications-log" class="space-y-3 max-h-64 overflow-y-auto">
                        <template x-for="notif in notifications" :key="notif.id">
                            <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <p class="font-bold" x-text="notif.title"></p>
                                <p class="text-sm text-gray-600" x-text="notif.body"></p>
                                <p class="text-xs text-gray-400" x-text="notif.time"></p>
                            </div>
                        </template>
                        
                        <div x-show="notifications.length === 0" class="text-gray-400 text-center">
                            لا توجد إشعارات
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Permission Banner -->
    <div x-show="showPermissionBanner" 
         class="fixed bottom-4 left-4 bg-blue-600 text-white p-4 rounded-lg shadow-lg flex items-center"
         style="display: none;">
        <div class="flex-1">
            <p class="font-bold">تفعيل إشعارات سطح المكتب</p>
            <p class="text-sm">ستتلقى إشعارات فورية عند اكتشاف الوجوه</p>
        </div>
        <button @click="requestNotificationPermission()" class="mr-4 bg-white text-blue-600 px-4 py-2 rounded">
            تفعيل
        </button>
        <button @click="showPermissionBanner = false" class="mr-2 text-white">✕</button>
    </div>

    <!-- JavaScript -->
    <script src="/js/notifications.js"></script>
    
    <script>
        function dashboard() {
            return {
                stats: {
                    today_detections: 0,
                    week_detections: 0,
                    unique_people_today: 0,
                    active_cameras: 0
                },
                detections: [],
                notifications: [],
                currentTime: '',
                showPermissionBanner: false,
                
                async init() {
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);
                    
                    // Check notification permission
                    if (Notification.permission === 'default') {
                        this.showPermissionBanner = true;
                    }
                    
                    // Load initial data
                    await this.loadStats();
                    await this.loadDetections();
                    
                    // Start polling for new detections
                    this.startPolling();
                    
                    // Initialize real-time notifications
                    this.initNotifications();
                },
                
                updateTime() {
                    const now = new Date();
                    this.currentTime = now.toLocaleTimeString('ar-SA', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                },
                
                async loadStats() {
                    try {
                        const response = await fetch('/api/dashboard');
                        const data = await response.json();
                        this.stats = data;
                    } catch (e) {
                        console.error('Error loading stats:', e);
                    }
                },
                
                async loadDetections() {
                    try {
                        const response = await fetch('/api/detections?limit=10');
                        const data = await response.json();
                        if (data.data) {
                            this.detections = data.data;
                        }
                    } catch (e) {
                        console.error('Error loading detections:', e);
                    }
                },
                
                startPolling() {
                    setInterval(async () => {
                        await this.loadStats();
                        await this.loadDetections();
                    }, 5000);
                },
                
                initNotifications() {
                    if (typeof FaceNotifications !== 'undefined') {
                        FaceNotifications.requestPermission();
                        
                        // Start polling for real-time notifications
                        let lastId = 0;
                        
                        setInterval(async () => {
                            try {
                                const response = await fetch('/api/detections?limit=1');
                                const data = await response.json();
                                
                                if (data.data && data.data.length > 0) {
                                    const latest = data.data[0];
                                    if (latest.id > lastId) {
                                        lastId = latest.id;
                                        this.handleNewDetection(latest);
                                    }
                                }
                            } catch (e) {
                                console.error('Polling error:', e);
                            }
                        }, 5000);
                    }
                },
                
                handleNewDetection(detection) {
                    // Show desktop notification
                    if (typeof FaceNotifications !== 'undefined') {
                        FaceNotifications.show(detection);
                    }
                    
                    // Add to notifications log
                    this.notifications.unshift({
                        id: Date.now(),
                        title: `👤 ${detection.person?.name || 'شخص جديد'}`,
                        body: `${detection.camera?.name || 'كاميرا'}`,
                        time: new Date(detection.detected_at).toLocaleTimeString('ar-SA')
                    });
                    
                    // Keep only last 10 notifications
                    if (this.notifications.length > 10) {
                        this.notifications.pop();
                    }
                },
                
                requestNotificationPermission() {
                    if (typeof FaceNotifications !== 'undefined') {
                        FaceNotifications.requestPermission();
                    }
                    this.showPermissionBanner = false;
                },
                
                getTypeLabel(type) {
                    const labels = {
                        'employee': 'موظف',
                        'visitor': 'زائر',
                        'citizen': 'مواطن'
                    };
                    return labels[type] || type || 'غير معروف';
                },
                
                formatTime(dateString) {
                    if (!dateString) return '';
                    return new Date(dateString).toLocaleTimeString('ar-SA');
                },
                
                getConfidenceClass(confidence) {
                    if (confidence >= 80) return 'bg-green-100 text-green-800';
                    if (confidence >= 60) return 'bg-yellow-100 text-yellow-800';
                    return 'bg-red-100 text-red-800';
                }
            };
        }
    </script>
</body>
</html>