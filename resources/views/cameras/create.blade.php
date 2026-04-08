<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة كاميرا - نظام التعرف على الوجوه</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Cairo', sans-serif; } </style>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg mb-8">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/" class="text-xl font-bold text-blue-600">📹 نظام التعرف على الوجوه</a>
            <a href="/" class="text-gray-600 hover:text-blue-600">← العودة للوحة التحكم</a>
        </div>
    </nav>

    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-6">📷 إضافة كاميرا</h2>
            
            <form id="addCameraForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium mb-2">اسم الكاميرا</label>
                    <input type="text" name="name" required 
                        class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500"
                        placeholder="مثال: مدخل الرئيسي">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">جهاز NVR</label>
                    <select name="nvr_id" id="nvrSelect" class="w-full border rounded-lg px-4 py-2">
                        <option value="">-- اختر NVR --</option>
                    </select>
                </div>

                <div id="nvrFields" class="hidden">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">رقم القناة</label>
                            <input type="number" name="channel" min="1" 
                                class="w-full border rounded-lg px-4 py-2"
                                placeholder="1">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">الموقع</label>
                            <input type="text" name="location" 
                                class="w-full border rounded-lg px-4 py-2"
                                placeholder="مثال: المدخل">
                        </div>
                    </div>
                </div>

                <div id="standaloneFields">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">عنوان IP</label>
                            <input type="text" name="ip" 
                                class="w-full border rounded-lg px-4 py-2"
                                placeholder="192.168.1.100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">الموقع</label>
                            <input type="text" name="location" 
                                class="w-full border rounded-lg px-4 py-2"
                                placeholder="مثال: المدخل الرئيسي">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-medium mb-2">رابط RTSP (اختياري)</label>
                        <input type="text" name="rtsp_url" 
                            class="w-full border rounded-lg px-4 py-2"
                            placeholder="اتركه فارغاً لكاميرات UNV">
                        <p class="text-xs text-gray-500 mt-1">اتركه فارغاً لكاميرات UNV (سيُستخدم rtsp://IP:554/media/video1)</p>
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" checked 
                        class="w-4 h-4 text-blue-600 rounded">
                    <label class="mr-2">فعالة</label>
                </div>

                <button type="submit" 
                    class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition">
                    💾 حفظ
                </button>
            </form>

            <div id="message" class="mt-4 p-4 rounded-lg hidden"></div>
        </div>
    </div>

    <script>
        // Load NVRs
        fetch('/api/nvrs')
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById('nvrSelect');
                if (data.data) {
                    data.data.forEach(nvr => {
                        const option = document.createElement('option');
                        option.value = nvr.id;
                        option.textContent = `${nvr.name} (${nvr.ip})`;
                        select.appendChild(option);
                    });
                }
            });

        // Toggle between NVR and standalone
        document.getElementById('nvrSelect').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('nvrFields').classList.remove('hidden');
                document.getElementById('standaloneFields').classList.add('hidden');
            } else {
                document.getElementById('nvrFields').classList.add('hidden');
                document.getElementById('standaloneFields').classList.remove('hidden');
            }
        });

        document.getElementById('addCameraForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const messageDiv = document.getElementById('message');

            try {
                const response = await fetch('/api/cameras', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    messageDiv.className = 'mt-4 p-4 rounded-lg bg-green-100 text-green-700';
                    messageDiv.textContent = '✅ تم إضافة الكاميرا بنجاح!';
                    form.reset();
                    setTimeout(() => window.location.href = '/', 1500);
                } else {
                    messageDiv.className = 'mt-4 p-4 rounded-lg bg-red-100 text-red-700';
                    messageDiv.textContent = '❌ ' + (data.message || 'حدث خطأ');
                }
            } catch (error) {
                messageDiv.className = 'mt-4 p-4 rounded-lg bg-red-100 text-red-700';
                messageDiv.textContent = '❌ حدث خطأ في الاتصال';
            }
        });
    </script>
</body>
</html>