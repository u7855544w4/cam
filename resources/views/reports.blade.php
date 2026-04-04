<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير - نظام التعرف على الوجوه</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style> body { font-family: 'Cairo', sans-serif; } </style>
</head>
<body class="bg-gray-50" x-data="reportsData">
    <nav class="bg-white shadow-lg mb-8">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/" class="text-xl font-bold text-blue-600">📹 نظام التعرف على الوجوه</a>
            <a href="/" class="text-gray-600 hover:text-blue-600">← العودة للوحة التحكم</a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-2xl font-bold mb-6">📋 التقارير</h2>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">من تاريخ</label>
                    <input type="date" x-model="filters.from_date" class="w-full border rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">إلى تاريخ</label>
                    <input type="date" x-model="filters.to_date" class="w-full border rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">الشخص</label>
                    <select x-model="filters.person_id" class="w-full border rounded-lg px-4 py-2">
                        <option value="">الكل</option>
                        <template x-for="person in people" :key="person.id">
                            <option :value="person.id" x-text="person.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">الكاميرا</label>
                    <select x-model="filters.camera_id" class="w-full border rounded-lg px-4 py-2">
                        <option value="">الكل</option>
                        <template x-for="camera in cameras" :key="camera.id">
                            <option :value="camera.id" x-text="camera.name"></option>
                        </template>
                    </select>
                </div>
            </div>
            <button @click="loadDetections()" class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                🔍 بحث
            </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">إجمالي الكشفات</p>
                <p class="text-2xl font-bold" x-text="stats.total"></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">أشخاص فريدين</p>
                <p class="text-2xl font-bold" x-text="stats.unique_people"></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">كاميرات نشطة</p>
                <p class="text-2xl font-bold" x-text="stats.active_cameras"></p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">الساعة المزدحمة</p>
                <p class="text-2xl font-bold" x-text="stats.peak_hour"></p>
            </div>
        </div>

        <!-- Detections Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right">الوقت</th>
                        <th class="px-4 py-3 text-right">الشخص</th>
                        <th class="px-4 py-3 text-right">النوع</th>
                        <th class="px-4 py-3 text-right">الكاميرا</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="d in detections" :key="d.id">
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3" x-text="d.detected_at"></td>
                            <td class="px-4 py-3 font-bold" x-text="d.person?.name || 'غير معروف'"></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-sm"
                                    :class="{
                                        'bg-blue-100 text-blue-700': d.person?.type === 'employee',
                                        'bg-green-100 text-green-700': d.person?.type === 'visitor',
                                        'bg-purple-100 text-purple-700': d.person?.type === 'citizen'
                                    }"
                                    x-text="getTypeLabel(d.person?.type)"></span>
                            </td>
                            <td class="px-4 py-3" x-text="d.camera?.name || '-'"></td>
                        </tr>
                    </template>
                    <tr x-show="detections.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">لا توجد بيانات</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function reportsData() {
            return {
                filters: {
                    from_date: '',
                    to_date: '',
                    person_id: '',
                    camera_id: ''
                },
                people: [],
                cameras: [],
                detections: [],
                stats: { total: 0, unique_people: 0, active_cameras: 0, peak_hour: '-' },
                
                async init() {
                    await this.loadFilters();
                    await this.loadDetections();
                },

                async loadFilters() {
                    const [peopleRes, camerasRes] = await Promise.all([
                        fetch('/api/people'),
                        fetch('/api/cameras')
                    ]);
                    const peopleData = await peopleRes.json();
                    const camerasData = await camerasRes.json();
                    this.people = peopleData.data || [];
                    this.cameras = camerasData.data || [];
                },

                async loadDetections() {
                    let url = '/api/detections?';
                    if (this.filters.from_date) url += `from_date=${this.filters.from_date}&`;
                    if (this.filters.to_date) url += `to_date=${this.filters.to_date}&`;
                    if (this.filters.person_id) url += `person_id=${this.filters.person_id}&`;
                    if (this.filters.camera_id) url += `camera_id=${this.filters.camera_id}&`;
                    
                    const res = await fetch(url);
                    const data = await res.json();
                    this.detections = data.data || [];
                    
                    // Calculate stats
                    this.stats.total = this.detections.length;
                    const unique = new Set(this.detections.map(d => d.person_id).filter(Boolean));
                    this.stats.unique_people = unique.size;
                },

                getTypeLabel(type) {
                    const labels = { employee: 'موظف', visitor: 'زائر', citizen: ' citizen' };
                    return labels[type] || type;
                }
            }
        }
    </script>
</body>
</html>