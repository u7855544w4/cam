<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة شخص جديد - نظام التعرف على الوجوه</title>
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
            <h2 class="text-2xl font-bold mb-6">➕ إضافة شخص جديد</h2>
            
            <form id="addPersonForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium mb-2">الاسم</label>
                    <input type="text" name="name" required 
                        class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="أدخل اسم الشخص">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">النوع</label>
                    <select name="type" required class="w-full border rounded-lg px-4 py-2">
                        <option value="employee">موظف</option>
                        <option value="visitor">زائر</option>
                        <option value="citizen">مواطن</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">الصورة</label>
                    <input type="file" name="image" accept="image/*" required
                        class="w-full border rounded-lg px-4 py-2">
                    <p class="text-sm text-gray-500 mt-1">يجب أن يكون الوجه واضحاً في الصورة</p>
                </div>

                <button type="submit" 
                    class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition">
                    💾 حفظ
                </button>
            </form>

            <div id="message" class="mt-4 p-4 rounded-lg hidden"></div>
        </div>
    </div>

    <script>
        document.getElementById('addPersonForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const messageDiv = document.getElementById('message');

            try {
                const response = await fetch('/api/people', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    messageDiv.className = 'mt-4 p-4 rounded-lg bg-green-100 text-green-700';
                    messageDiv.textContent = '✅ تم إضافة الشخص بنجاح!';
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