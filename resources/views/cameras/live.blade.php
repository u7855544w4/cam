<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض الكاميرات - نظام التعرف على الوجوه</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- HLS.js -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
        
        .camera-card {
            transition: all 0.3s ease;
        }
        
        .camera-card:hover {
            transform: scale(1.01);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .video-container {
            background: #000;
            position: relative;
        }
        
        .video-container video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .live-indicator {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .grid-controls {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-900" x-data="cameraView()" x-init="init()">
    
    <!-- Navbar -->
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/dashboard" class="text-gray-400 hover:text-white ml-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <span class="text-xl font-bold text-white">📹 عرض الكاميرات المباشرة</span>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Grid Size Controls -->
                    <div class="flex items-center space-x-2 space-x-reverse bg-gray-700 rounded-lg p-1">
                        <button @click="setGridSize(2)" 
                                :class="gridSize === 2 ? 'bg-blue-600 text-white' : 'bg-gray-600 text-gray-300'"
                                class="px-3 py-1 rounded text-sm font-bold">
                            2x2
                        </button>
                        <button @click="setGridSize(3)" 
                                :class="gridSize === 3 ? 'bg-blue-600 text-white' : 'bg-gray-600 text-gray-300'"
                                class="px-3 py-1 rounded text-sm font-bold">
                            3x3
                        </button>
                        <button @click="setGridSize(4)" 
                                :class="gridSize === 4 ? 'bg-blue-600 text-white' : 'bg-gray-600 text-gray-300'"
                                class="px-3 py-1 rounded text-sm font-bold">
                            4x4
                        </button>
                    </div>
                    
                    <!-- Refresh Button -->
                    <button @click="loadCameras()" class="bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                        🔄 تحديث
                    </button>
                    
                    <!-- Current Time -->
                    <span class="text-gray-400" x-text="currentTime"></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        
        <!-- Stats Bar -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="bg-gray-800 rounded-lg px-4 py-2">
                    <span class="text-gray-400">عدد الكاميرات:</span>
                    <span class="text-white font-bold mr-2" x-text="cameras.length"></span>
                </div>
                <div class="bg-gray-800 rounded-lg px-4 py-2">
                    <span class="text-green-400">●</span>
                    <span class="text-gray-400 mr-1">نشطة:</span>
                    <span class="text-green-400 font-bold" x-text="activeCount"></span>
                </div>
                <div class="bg-gray-800 rounded-lg px-4 py-2">
                    <span class="text-red-400">●</span>
                    <span class="text-gray-400 mr-1">غير نشطة:</span>
                    <span class="text-red-400 font-bold" x-text="inactiveCount"></span>
                </div>
            </div>
        </div>

        <!-- Camera Grid -->
        <div :class="getGridClass()" class="grid gap-4">
            <template x-for="camera in cameras" :key="camera.id">
                <div class="camera-card bg-gray-800 rounded-lg overflow-hidden border border-gray-700"
                     :class="{'border-green-500': camera.is_active, 'border-red-500': !camera.is_active}">
                    
                    <!-- Video Container -->
                    <div class="video-container aspect-video relative">
                        <video :id="'video-' + camera.id" 
                               class="w-full h-full"
                               muted
                               playsinline
                               autoplay></video>
                        
                        <!-- No Signal Overlay -->
                        <div x-show="!isStreaming(camera.id)" 
                             class="absolute inset-0 bg-gray-900 flex items-center justify-center">
                            <div class="text-center">
                                <p class="text-4xl mb-2">📵</p>
                                <p class="text-gray-400">لا يوجد إشارة</p>
                                <p class="text-gray-500 text-sm" x-text="camera.name"></p>
                            </div>
                        </div>
                        
                        <!-- Live Indicator -->
                        <div x-show="isStreaming(camera.id)" 
                             class="absolute top-2 right-2 flex items-center space-x-1 space-x-reverse bg-red-600 px-2 py-1 rounded">
                            <span class="w-2 h-2 bg-white rounded-full live-indicator"></span>
                            <span class="text-white text-xs font-bold">LIVE</span>
                        </div>
                        
                        <!-- Camera Info -->
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-white font-bold text-sm" x-text="camera.name"></p>
                                    <p class="text-gray-400 text-xs" x-text="camera.location || 'غير محدد'"></p>
                                </div>
                                <div class="text-left">
                                    <p class="text-gray-400 text-xs" x-text="camera.nvr?.name || 'Direct'"></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Fullscreen Button -->
                        <button @click="toggleFullscreen(camera.id)" 
                                class="absolute top-2 left-2 bg-black/50 text-white p-1 rounded hover:bg-black/70">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Controls -->
                    <div class="p-3 bg-gray-800 border-t border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <button @click="toggleStream(camera)" 
                                        :class="isStreaming(camera.id) ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'"
                                        class="text-white px-3 py-1 rounded text-sm">
                                    <span x-text="isStreaming(camera.id) ? '⏹ إيقاف' : '▶ تشغيل'"></span>
                                </button>
                                <button @click="testConnection(camera)" 
                                        :disabled="camera.testing"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm disabled:opacity-50">
                                    <span x-show="!camera.testing">🧪 اختبار</span>
                                    <span x-show="camera.testing">...</span>
                                </button>
                            </div>
                            
                            <!-- Status -->
                            <div class="flex items-center space-x-1 space-x-reverse">
                                <span class="w-2 h-2 rounded-full"
                                      :class="getStatusClass(camera)"></span>
                                <span class="text-gray-400 text-xs" 
                                      x-text="getStatusText(camera)"></span>
                            </div>
                        </div>
                        
                        <!-- Test Result -->
                        <div x-show="camera.testResult" class="mt-2 text-xs"
                             :class="camera.testResult?.success ? 'text-green-400' : 'text-red-400'"
                             x-text="camera.testResult?.message || ''">
                        </div>
                    </div>
                </div>
            </template>
            
            <!-- Empty State -->
            <div x-show="cameras.length === 0" class="col-span-full text-center py-16">
                <p class="text-6xl mb-4">📷</p>
                <p class="text-gray-400 text-xl">لا توجد كاميرات</p>
                <a href="/cameras/create" class="inline-block mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                    إضافة كاميرا
                </a>
            </div>
        </div>
    </div>

    <!-- Fullscreen Modal -->
    <div x-show="fullscreenCamera" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black z-50 flex items-center justify-center"
         style="display: none;">
        
        <!-- Close Button -->
        <button @click="closeFullscreen()" 
                class="absolute top-4 right-4 text-white p-2 bg-black/50 rounded hover:bg-black/70 z-10">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        
        <!-- Fullscreen Video -->
        <video :id="'fullscreen-video-' + fullscreenCamera" 
               class="w-full h-full max-h-screen"
               controls
               autoplay></video>
        
        <!-- Camera Info Overlay -->
        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4">
            <template x-for="camera in cameras" :key="camera.id">
                <div x-show="camera.id == fullscreenCamera">
                    <p class="text-white font-bold text-lg" x-text="camera.name"></p>
                    <p class="text-gray-400" x-text="camera.location || 'غير محدد'"></p>
                </div>
            </template>
        </div>
    </div>

    <script>
        function cameraView() {
            return {
                cameras: [],
                gridSize: 2,
                currentTime: '',
                activeStreams: {},
                fullscreenCamera: null,
                streamServiceUrl: 'http://localhost:5001',
                
                async init() {
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);
                    
                    await this.loadCameras();
                    
                    // Auto-start streams for active cameras
                    this.autoStartStreams();
                },
                
                updateTime() {
                    const now = new Date();
                    this.currentTime = now.toLocaleTimeString('ar-SA', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                },
                
                async loadCameras() {
                    try {
                        const response = await fetch('/api/cameras');
                        const data = await response.json();
                        if (data.data) {
                            this.cameras = data.data;
                        }
                    } catch (e) {
                        console.error('Error loading cameras:', e);
                        // Load mock data for demo
                        this.cameras = [
                            { id: 1, name: 'Camera 1', ip: '192.168.1.101', is_active: true, location: 'Main Entrance', nvr: { name: 'NVR-1' }},
                            { id: 2, name: 'Camera 2', ip: '192.168.1.102', is_active: true, location: 'Parking', nvr: { name: 'NVR-1' }},
                            { id: 3, name: 'Camera 3', ip: '192.168.1.103', is_active: false, location: 'Office', nvr: { name: 'NVR-1' }},
                            { id: 4, name: 'Camera 4', ip: '192.168.1.104', is_active: true, location: 'Warehouse', nvr: { name: 'NVR-2' }},
                        ];
                    }
                },
                
                async autoStartStreams() {
                    for (const camera of this.cameras) {
                        if (camera.is_active) {
                            await this.startStream(camera);
                        }
                    }
                },
                
                getGridClass() {
                    return {
                        'grid-cols-1': this.gridSize === 1,
                        'grid-cols-2': this.gridSize === 2,
                        'grid-cols-3': this.gridSize === 3,
                        'grid-cols-4': this.gridSize === 4,
                    };
                },
                
                setGridSize(size) {
                    this.gridSize = size;
                },
                
                get activeCount() {
                    return this.cameras.filter(c => c.is_active).length;
                },
                
                get inactiveCount() {
                    return this.cameras.filter(c => !c.is_active).length;
                },
                
                isStreaming(cameraId) {
                    return this.activeStreams[cameraId] === true;
                },
                
                async toggleStream(camera) {
                    if (this.isStreaming(camera.id)) {
                        await this.stopStream(camera.id);
                    } else {
                        await this.startStream(camera);
                    }
                },
                
                async startStream(camera) {
                    const rtspUrl = this.getRtspUrl(camera);
                    
                    try {
                        // Try to use stream service
                        const response = await fetch(`${this.streamServiceUrl}/api/start`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                camera_id: camera.id,
                                rtsp_url: rtspUrl,
                                name: camera.name
                            })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            this.activeStreams[camera.id] = true;
                            this.playStream(camera.id, data.stream_url);
                        }
                    } catch (e) {
                        console.log('Stream service not available, using direct play');
                        // Fallback: try direct play
                        this.activeStreams[camera.id] = true;
                        this.playStreamDirect(camera.id, rtspUrl);
                    }
                },
                
                async stopStream(cameraId) {
                    try {
                        await fetch(`${this.streamServiceUrl}/api/stop`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ camera_id: cameraId })
                        });
                    } catch (e) {
                        console.log('Stream service not available');
                    }
                    
                    this.activeStreams[cameraId] = false;
                    this.stopVideo(cameraId);
                },
                
                getRtspUrl(camera) {
                    if (camera.rtsp_url) {
                        return camera.rtsp_url;
                    }
                    if (camera.nvr) {
                        return `rtsp://${camera.nvr.ip}:${camera.nvr.port || 554}/channel/${camera.channel}/stream/0`;
                    }
                    return `rtsp://${camera.ip}:554/stream`;
                },
                
                playStream(cameraId, streamUrl) {
                    const video = document.getElementById(`video-${cameraId}`);
                    if (!video) return;
                    
                    if (Hls.isSupported()) {
                        const hls = new Hls();
                        hls.loadSource(streamUrl);
                        hls.attachMedia(video);
                        hls.on(Hls.Events.MANIFEST_PARSED, () => {
                            video.play().catch(e => console.log('Auto-play blocked'));
                        });
                        hls.on(Hls.Events.ERROR, (event, data) => {
                            if (data.fatal) {
                                console.error('HLS error:', data);
                            }
                        });
                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                        video.src = streamUrl;
                        video.play().catch(e => console.log('Auto-play blocked'));
                    }
                },
                
                playStreamDirect(cameraId, rtspUrl) {
                    const video = document.getElementById(`video-${cameraId}`);
                    if (!video) return;
                    
                    // For some browsers, direct RTSP might work
                    video.src = rtspUrl;
                    video.play().catch(e => {
                        console.log('Direct play failed, need proxy');
                    });
                },
                
                stopVideo(cameraId) {
                    const video = document.getElementById(`video-${cameraId}`);
                    if (video) {
                        video.pause();
                        video.src = '';
                        video.load();
                    }
                },
                
                toggleFullscreen(cameraId) {
                    this.fullscreenCamera = cameraId;
                    
                    // Wait for modal to appear
                    this.$nextTick(() => {
                        const fullVideo = document.getElementById(`fullscreen-video-${cameraId}`);
                        const sourceVideo = document.getElementById(`video-${cameraId}`);
                        
                        if (sourceVideo && this.isStreaming(cameraId)) {
                            // Copy stream to fullscreen video
                            if (Hls.isSupported()) {
                                const hls = new Hls();
                                hls.loadSource(`/stream/${cameraId}/playlist.m3u8`);
                                hls.attachMedia(fullVideo);
                                hls.on(Hls.Events.MANIFEST_PARSED, () => {
                                    fullVideo.play();
                                });
                            }
                        }
                    });
                },
                
                closeFullscreen() {
                    this.fullscreenCamera = null;
                },
                
                async testConnection(camera) {
                    // Find camera in array and set testing state
                    const camIndex = this.cameras.findIndex(c => c.id === camera.id);
                    if (camIndex === -1) return;
                    
                    this.cameras[camIndex].testing = true;
                    this.cameras[camIndex].testResult = null;
                    
                    try {
                        // Try direct PHP endpoint first (doesn't need Laravel running)
                        const response = await fetch(`/api/camera-test.php?camera_id=${camera.id}`);
                        const data = await response.json();
                        
                        // If PHP endpoint fails, try API endpoint
                        if (!response.ok) {
                            const apiResponse = await fetch(`/api/cameras/${camera.id}/test`);
                            data = await apiResponse.json();
                        }
                        
                        this.cameras[camIndex].testResult = {
                            success: data.success,
                            message: data.message,
                            online: data.online,
                            rtsp_url: data.rtsp_url
                        };
                        
                        // Update camera is_active based on test result
                        if (data.success && data.online) {
                            this.cameras[camIndex].is_active = true;
                        }
                    } catch (e) {
                        this.cameras[camIndex].testResult = {
                            success: false,
                            message: 'خطأ في الاتصال: ' + e.message
                        };
                    }
                    
                    this.cameras[camIndex].testing = false;
                },
                
                getStatusClass(camera) {
                    if (camera.testResult?.online === true) return 'bg-green-500';
                    if (camera.testResult?.online === false) return 'bg-red-500';
                    if (camera.is_active) return 'bg-yellow-500';
                    return 'bg-gray-500';
                },
                
                getStatusText(camera) {
                    if (camera.testResult?.online === true) return 'متصل';
                    if (camera.testResult?.online === false) return 'غير متصل';
                    if (camera.is_active) return 'نشط';
                    return 'غير نشط';
                }
            };
        }
    </script>
</body>
</html>