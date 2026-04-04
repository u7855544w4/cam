<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة NVR - نظام التعرف على الوجوه</title>
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
            <h2 class="text-2xl font-bold mb-6">🖥️ إضافة NVR</h2>
            
            <form id="addNvrForm" class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">اسم NVR</label>
                        <input type="text" name="name" required 
                            class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500"
                            placeholder="مثال: NVR الرئيسي">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">الشركة المصنعة</label>
                        <select name="manufacturer" class="w-full border rounded-lg px-4 py-2">
                            <option value="Hikvision">Hikvision</option>
                            <option value="Dahua">Dahua</option>
                            <option value="Axis">Axis</option>
                            <option value="Bosch">Bosch</option>
                            <option value="Other">أخرى</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">عنوان IP</label>
                        <input type="text" name="ip" required 
                            class="w-full border rounded-lg px-4 py-2"
                            placeholder="192.168.1.100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">المنفذ</label>
                        <input type="number" name="port" value="554" 
                            class="w-full border rounded-lg px-4 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">اسم المستخدم</label>
                        <input type="text" name="username" 
                            class="w-full border rounded-lg px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">كلمة المرور</label>
                        <input type="password" name="password" 
                            class="w-full border rounded-lg px-4 py-2">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">الموديل (اختياري)</label>
                    <input type="text" name="model" 
                        class="w-full border rounded-lg px-4 py-2"
                        placeholder="مثال: DS-7608NI-K2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">ملاحظات</label>
                    <textarea name="notes" rows="2" 
                        class="w-full border rounded-lg px-4 py-2"></textarea>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" checked 
                        class="w-4 h-4 text-blue-600 rounded">
                    <label class="mr-2">فعالة</label>
                </div>

                <button type="submit" 
                    class="w-full bg-purple-600 text-white py-3 rounded-lg hover:bg-purple-700 transition">
                    💾 حفظ
                </button>
            </form>

            <div id="message" class="mt-4 p-4 rounded-lg hidden"></div>
        </div>
    </div>

    <script>
        document.getElementById('addNvrForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const messageDiv = document.getElementById('message');

            try {
                const response = await fetch('/api/nvrs', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    messageDiv.className = 'mt-4 p-4 rounded-lg bg-green-100 text-green-700';
                    messageDiv.textContent = '✅ تم إضافة NVR بنجاح!';
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