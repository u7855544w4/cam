<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البحث بالصورة - نظام التعرف على الوجوه</title>
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

    <div class="max-w-3xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-6">🔍 البحث بالصورة</h2>
            
            <form id="searchForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium mb-2">اختر صورة للبحث</label>
                    <input type="file" name="image" accept="image/*" required
                        class="w-full border rounded-lg px-4 py-4 bg-gray-50">
                </div>

                <button type="submit" 
                    class="w-full bg-purple-600 text-white py-3 rounded-lg hover:bg-purple-700 transition">
                    🔍 بحث
                </button>
            </form>

            <div id="loading" class="hidden text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-purple-500 border-t-transparent"></div>
                <p class="mt-2 text-gray-500">جاري البحث...</p>
            </div>

            <div id="results" class="mt-8 hidden">
                <h3 class="font-bold text-lg mb-4">نتائج البحث</h3>
                <div id="resultsList" class="space-y-4"></div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('searchForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            
            document.getElementById('loading').classList.remove('hidden');
            document.getElementById('results').classList.add('hidden');

            try {
                const response = await fetch('/api/people/search', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                document.getElementById('loading').classList.add('hidden');
                document.getElementById('results').classList.remove('hidden');

                const resultsDiv = document.getElementById('resultsList');
                resultsDiv.innerHTML = '';

                if (data.detections && data.detections.length > 0) {
                    data.detections.forEach(d => {
                        resultsDiv.innerHTML += `
                            <div class="p-4 border rounded-lg flex items-center">
                                <div class="flex-1">
                                    <p class="font-bold">${d.person?.name || 'غير معروف'}</p>
                                    <p class="text-sm text-gray-500">${d.camera?.name || ''} - ${d.detected_at}</p>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    resultsDiv.innerHTML = '<p class="text-gray-500">لم يتم العثور على نتائج</p>';
                }
            } catch (error) {
                document.getElementById('loading').classList.add('hidden');
                alert('حدث خطأ في البحث');
            }
        });
    </script>
</body>
</html>