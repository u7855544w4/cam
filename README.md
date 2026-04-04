# نظام التعرف على الوجوه - Face Recognition System

نظام متكامل لمراقبة الكاميرات والتعرف على الوجوه مع إشعارات فورية على سطح المكتب.

## المميزات

- 📹 عرض البث المباشر لكاميرات IP
- 👤 التعرف على الوجوه (موظفين / زوار / مواطنين)
- 📝 تسجيل كل ظهور (الشخص + الوقت + الكاميرا)
- 🔔 إشعارات فورية على سطح المكتب عند اكتشاف وجه جديد
- 🔍 البحث بالصورة (Face Search)
- 📊 لوحة تحكم Dashboard
- 📋 تقارير وإحصائيات

## المتطلبات

- PHP 8.4+
- Laravel 11
- Python 3.8+
- MariaDB/MySQL
- FFmpeg (اختياري لتحويل streams)

## التنصيب

### 1. تنصيب المتطلبات

```bash
# PHP & Composer
sudo apt-get install php php-cli php-mbstring php-xml php-curl php-zip php-mysql

# Python & Libraries
pip install face_recognition opencv-python flask flask-cors

# FFmpeg (لتحويل streams)
sudo apt-get install ffmpeg
```

### 2. تنصيب Laravel

```bash
cd /workspace/face-recognition-system

composer install

# إعداد قاعدة البيانات
php artisan migrate
```

### 3. تشغيل خدمات Python

```bash
cd /workspace/face-recognition-system/python-service
python face_service.py
```

### 4. تشغيل Laravel

```bash
cd /workspace/face-recognition-system
php artisan serve --host=0.0.0.0 --port=8000
```

## هيكل النظام

```
face-recognition-system/
├── app/
│   ├── Http/Controllers/
│   │   ├── PersonController.php     # إدارة الأشخاص
│   │   ├── CameraController.php     # إدارة الكاميرات
│   │   └── DetectionController.php # تسجيل الكشفات
│   ├── Models/
│   │   ├── Person.php               # نموذج الأشخاص
│   │   ├── Camera.php               # نموذج الكاميرات
│   │   └── Detection.php            # نموذج الكشفات
│   └── Events/
│       └── FaceDetected.php         # حدث الكشف الفوري
├── python-service/
│   └── face_service.py              # خدمة التعرف على الوجوه
├── public/
│   └── js/
│       └── notifications.js         # إشعارات سطح المكتب
└── resources/
    └── views/
        └── dashboard.blade.php      # لوحة التحكم
```

## API Endpoints

### الأشخاص
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/people` | قائمة الأشخاص |
| POST | `/api/people` | إضافة شخص جديد |
| GET | `/api/people/{id}` | عرض شخص |
| PUT | `/api/people/{id}` | تحديث شخص |
| DELETE | `/api/people/{id}` | حذف شخص |
| POST | `/api/people/search` | البحث بالصورة |

### الكاميرات
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/cameras` | قائمة الكاميرات |
| POST | `/api/cameras` | إضافة كاميرا |
| GET | `/api/cameras/{id}` | عرض كاميرا |
| PUT | `/api/cameras/{id}` | تحديث كاميرا |
| DELETE | `/api/cameras/{id}` | حذف كاميرا |

### الكشفات
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/detections` | قائمة الكشفات |
| POST | `/api/detections` | تسجيل كشف جديد |
| GET | `/api/detections/today` | كشفات اليوم |
| GET | `/api/detections/statistics` | الإحصائيات |

## إشعارات سطح المكتب

النظام يستخدم Web Notifications API لإرسال إشعارات فورية:

```javascript
// طلب إذن الإشعارات
Notification.requestPermission();

// إرسال إشعار
new Notification("👤 شخص جديد!", {
    body: "الموظف: أحمد - الكاميرا: المدخل الرئيسي",
    icon: "/path/to/image.jpg"
});
```

### طريقة العمل:
1. عند اكتشاف وجه جديد، يرسل Python Service النتيجة لـ Laravel
2. Laravel يسجل الكشف ويرسل حدث `FaceDetected`
3. الـ Frontend يستقبل الحدث ويعرض إشعار سطح المكتب

## ربط كاميرات IP

الكاميرات تعطي رابط RTSP:
```
rtsp://192.168.1.10:554/stream
```

لعرضها في المتصفح، استخدم FFmpeg لتحويلها لـ HLS:

```bash
ffmpeg -i rtsp://192.168.1.10:554/stream \
  -hls_time 2 -hls_list_size 3 \
  -hls_flags delete_segments \
  stream.m3u8
```

## المشاكل الشائعة

### 1. خدمة Python لا تستجيب
- تأكد من تشغيل service على المنفذ 5000
- تحقق من الاتصال بـ SQLite database

### 2. إشعارات surface desktop لا تظهر
- تأكد من منح إذن الإشعارات في المتصفح
- استخدم HTTPS أو localhost

### 3. faces لا يتم التعرف عليها
- تأكد من جودة الصورة (إضاءة جيدة، وجه واضح)
- تأكد من حفظ face_encoding في قاعدة البيانات

## متغيرات البيئة

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=face_recognition
DB_USERNAME=root
DB_PASSWORD=

PYTHON_SERVICE_HOST=127.0.0.1
PYTHON_SERVICE_PORT=5000
```

## الترخيص

MIT License