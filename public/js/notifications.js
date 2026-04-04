// ============================================
// Face Recognition System - Frontend
// Real-time Desktop Notifications
// ============================================

(function() {
    'use strict';

    // Check if browser supports notifications
    if (!('Notification' in window)) {
        console.log('This browser does not support desktop notifications');
        return;
    }

    // Request notification permission
    function requestPermission() {
        if (Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    console.log('Notification permission granted');
                }
            });
        }
    }

    // Show desktop notification
    function showNotification(data) {
        if (Notification.permission !== 'granted') {
            return;
        }

        const title = `👤 ${data.person.name}`;
        const body = `
            نوع: ${getTypeLabel(data.person.type)}
            الكاميرا: ${data.camera.name}
            الموقع: ${data.camera.location || 'غير محدد'}
            الوقت: ${new Date(data.detected_at).toLocaleTimeString('ar-SA')}
        `;

        const notification = new Notification(title, {
            body: body,
            icon: data.person.image ? `/storage/${data.person.image}` : '/favicon.ico',
            tag: `detection-${data.id}`,
            requireInteraction: false,
            vibrate: [200, 100, 200]
        });

        // Play sound
        playAlertSound();

        // Click handler
        notification.onclick = function() {
            window.focus();
            this.close();
        };

        // Auto close after 5 seconds
        setTimeout(() => {
            notification.close();
        }, 5000);
    }

    // Get Arabic type label
    function getTypeLabel(type) {
        const labels = {
            'employee': 'موظف',
            'visitor': 'زائر',
            'citizen': 'مواطن'
        };
        return labels[type] || type;
    }

    // Play alert sound
    function playAlertSound() {
        try {
            const audio = new Audio('/sounds/alert.mp3');
            audio.volume = 0.5;
            audio.play().catch(() => {});
        } catch (e) {
            // Ignore audio errors
        }
    }

    // Connect to Laravel Echo for real-time updates
    function initRealtime() {
        if (typeof Echo === 'undefined') {
            console.log('Echo not initialized, using polling instead');
            startPolling();
            return;
        }

        Echo.channel('detections')
            .listen('FaceDetected', (data) => {
                console.log('Face detected:', data);
                showNotification(data);
                addDetectionToUI(data);
            });
    }

    // Fallback: Polling for real-time updates
    function startPolling() {
        let lastId = 0;
        
        setInterval(async () => {
            try {
                const response = await fetch('/api/detections?limit=1');
                const data = await response.json();
                
                if (data.data && data.data.length > 0) {
                    const latest = data.data[0];
                    if (latest.id > lastId) {
                        lastId = latest.id;
                        showNotification(latest);
                        addDetectionToUI(latest);
                    }
                }
            } catch (e) {
                console.error('Polling error:', e);
            }
        }, 5000); // Check every 5 seconds
    }

    // Add detection to UI
    function addDetectionToUI(data) {
        const container = document.getElementById('recent-detections');
        if (!container) return;

        const html = `
            <div class="detection-card fade-in">
                <img src="${data.person.image ? '/storage/' + data.person.image : '/images/default-avatar.png'}" 
                     alt="${data.person.name}" class="avatar">
                <div class="info">
                    <h4>${data.person.name}</h4>
                    <p>${getTypeLabel(data.person.type)} - ${data.camera.name}</p>
                    <span class="time">${new Date(data.detected_at).toLocaleTimeString('ar-SA')}</span>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('afterbegin', html);

        // Keep only last 10 detections in UI
        const cards = container.querySelectorAll('.detection-card');
        if (cards.length > 10) {
            cards[cards.length - 1].remove();
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        requestPermission();
        initRealtime();
    });

    // Export for manual triggering
    window.FaceNotifications = {
        show: showNotification,
        requestPermission: requestPermission,
        showAlert: function(title, message) {
            if (Notification.permission === 'granted') {
                new Notification(title, { body: message });
            }
        }
    };
})();

// Also export globally for immediate access
window.requestNotificationPermission = function() {
    if ('Notification' in window) {
        if (Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    alert('✅ تم تفعيل الإشعارات بنجاح!');
                }
            });
        } else if (Notification.permission === 'granted') {
            alert('ℹ️ الإشعارات مفعلة بالفعل');
        } else {
            alert('⚠️ الإشعارات محجوبة. يرجى السماح لها في إعدادات المتصفح');
        }
    }
};