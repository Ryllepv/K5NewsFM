// Interactive Dashboard with Real-time Features
class InteractiveDashboard {
    constructor() {
        this.widgets = new Map();
        this.realTimeData = {
            listeners: 1247,
            currentSong: { title: "Bohemian Rhapsody", artist: "Queen" },
            weather: { temp: 72, condition: "Sunny" },
            traffic: "Light",
            newsAlerts: 0
        };
        this.updateInterval = null;
        this.chartInstances = new Map();
        
        this.init();
    }
    
    init() {
        this.createDashboard();
        this.initializeWidgets();
        this.startRealTimeUpdates();
        this.setupInteractiveElements();
        this.initializeCharts();
    }
    
    createDashboard() {
        const dashboardHTML = `
            <div class="interactive-dashboard">
                <div class="dashboard-header">
                    <h2 class="dashboard-title">K5 News FM Live Dashboard</h2>
                    <div class="dashboard-controls">
                        <button class="dashboard-btn" id="refreshBtn">
                            <i class="icon-refresh"></i> Refresh
                        </button>
                        <button class="dashboard-btn" id="fullscreenBtn">
                            <i class="icon-fullscreen"></i> Fullscreen
                        </button>
                        <div class="theme-toggle">
                            <input type="checkbox" id="themeToggle" class="theme-checkbox">
                            <label for="themeToggle" class="theme-label">
                                <span class="theme-sun">☀️</span>
                                <span class="theme-moon">🌙</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-grid">
                    <!-- Live Stats Widget -->
                    <div class="dashboard-widget stats-widget" data-widget="stats">
                        <div class="widget-header">
                            <h3>Live Statistics</h3>
                            <div class="widget-actions">
                                <button class="widget-btn" data-action="minimize">−</button>
                                <button class="widget-btn" data-action="close">×</button>
                            </div>
                        </div>
                        <div class="widget-content">
                            <div class="stat-grid">
                                <div class="stat-item">
                                    <div class="stat-value" id="listenerCount">1,247</div>
                                    <div class="stat-label">Live Listeners</div>
                                    <div class="stat-trend positive">+12%</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value" id="songCount">142</div>
                                    <div class="stat-label">Songs Today</div>
                                    <div class="stat-trend positive">+5</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value" id="callsCount">23</div>
                                    <div class="stat-label">Calls/Requests</div>
                                    <div class="stat-trend neutral">0</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value" id="socialMentions">89</div>
                                    <div class="stat-label">Social Mentions</div>
                                    <div class="stat-trend positive">+15</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Now Playing Widget -->
                    <div class="dashboard-widget now-playing-widget" data-widget="nowplaying">
                        <div class="widget-header">
                            <h3>Now Playing</h3>
                        </div>
                        <div class="widget-content">
                            <div class="now-playing-info">
                                <div class="album-art">
                                    <img src="https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=100&h=100&fit=crop" alt="Album Art" id="albumArt">
                                    <div class="playing-indicator">
                                        <div class="wave-bar"></div>
                                        <div class="wave-bar"></div>
                                        <div class="wave-bar"></div>
                                        <div class="wave-bar"></div>
                                    </div>
                                </div>
                                <div class="track-details">
                                    <h4 id="trackTitle">Bohemian Rhapsody</h4>
                                    <p id="trackArtist">Queen</p>
                                    <div class="track-progress">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: 45%"></div>
                                        </div>
                                        <div class="time-info">
                                            <span>2:45</span>
                                            <span>6:07</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Listener Analytics Chart -->
                    <div class="dashboard-widget chart-widget" data-widget="analytics">
                        <div class="widget-header">
                            <h3>Listener Analytics</h3>
                            <select class="chart-period" id="chartPeriod">
                                <option value="24h">Last 24 Hours</option>
                                <option value="7d">Last 7 Days</option>
                                <option value="30d">Last 30 Days</option>
                            </select>
                        </div>
                        <div class="widget-content">
                            <canvas id="listenerChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    
                    <!-- Weather Widget -->
                    <div class="dashboard-widget weather-widget" data-widget="weather">
                        <div class="widget-header">
                            <h3>Local Weather</h3>
                        </div>
                        <div class="widget-content">
                            <div class="weather-info">
                                <div class="weather-icon">☀️</div>
                                <div class="weather-temp">72°F</div>
                                <div class="weather-condition">Sunny</div>
                                <div class="weather-details">
                                    <div class="weather-detail">
                                        <span>Humidity</span>
                                        <span>45%</span>
                                    </div>
                                    <div class="weather-detail">
                                        <span>Wind</span>
                                        <span>8 mph</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Media Feed -->
                    <div class="dashboard-widget social-widget" data-widget="social">
                        <div class="widget-header">
                            <h3>Social Media</h3>
                            <div class="social-tabs">
                                <button class="social-tab active" data-platform="twitter">Twitter</button>
                                <button class="social-tab" data-platform="facebook">Facebook</button>
                                <button class="social-tab" data-platform="instagram">Instagram</button>
                            </div>
                        </div>
                        <div class="widget-content">
                            <div class="social-feed" id="socialFeed">
                                <!-- Social posts will be populated here -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions Widget -->
                    <div class="dashboard-widget actions-widget" data-widget="actions">
                        <div class="widget-header">
                            <h3>Quick Actions</h3>
                        </div>
                        <div class="widget-content">
                            <div class="action-grid">
                                <button class="action-btn emergency" data-action="emergency">
                                    <i class="icon-alert"></i>
                                    <span>Emergency Alert</span>
                                </button>
                                <button class="action-btn weather" data-action="weather-alert">
                                    <i class="icon-weather"></i>
                                    <span>Weather Alert</span>
                                </button>
                                <button class="action-btn traffic" data-action="traffic-update">
                                    <i class="icon-traffic"></i>
                                    <span>Traffic Update</span>
                                </button>
                                <button class="action-btn contest" data-action="contest">
                                    <i class="icon-gift"></i>
                                    <span>Start Contest</span>
                                </button>
                                <button class="action-btn news" data-action="breaking-news">
                                    <i class="icon-news"></i>
                                    <span>Breaking News</span>
                                </button>
                                <button class="action-btn social" data-action="social-post">
                                    <i class="icon-share"></i>
                                    <span>Social Post</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Insert dashboard into page
        const dashboardContainer = document.createElement('div');
        dashboardContainer.innerHTML = dashboardHTML;
        document.body.appendChild(dashboardContainer);
    }
    
    initializeWidgets() {
        // Make widgets draggable and resizable
        this.setupDraggableWidgets();
        this.setupWidgetActions();
        this.setupSocialTabs();
        this.populateSocialFeed();
    }
    
    setupDraggableWidgets() {
        const widgets = document.querySelectorAll('.dashboard-widget');
        
        widgets.forEach(widget => {
            let isDragging = false;
            let startX, startY, startLeft, startTop;
            
            const header = widget.querySelector('.widget-header h3');
            
            header.addEventListener('mousedown', (e) => {
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                startLeft = widget.offsetLeft;
                startTop = widget.offsetTop;
                
                widget.style.position = 'absolute';
                widget.style.zIndex = '1000';
                widget.classList.add('dragging');
                
                e.preventDefault();
            });
            
            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                
                const deltaX = e.clientX - startX;
                const deltaY = e.clientY - startY;
                
                widget.style.left = `${startLeft + deltaX}px`;
                widget.style.top = `${startTop + deltaY}px`;
            });
            
            document.addEventListener('mouseup', () => {
                if (isDragging) {
                    isDragging = false;
                    widget.classList.remove('dragging');
                    widget.style.zIndex = '';
                }
            });
        });
    }
    
    setupWidgetActions() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="minimize"]')) {
                const widget = e.target.closest('.dashboard-widget');
                widget.classList.toggle('minimized');
            }
            
            if (e.target.matches('[data-action="close"]')) {
                const widget = e.target.closest('.dashboard-widget');
                widget.style.display = 'none';
            }
            
            if (e.target.matches('.action-btn')) {
                const action = e.target.dataset.action;
                this.handleQuickAction(action);
            }
        });
    }
    
    setupSocialTabs() {
        const socialTabs = document.querySelectorAll('.social-tab');
        
        socialTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                socialTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                const platform = tab.dataset.platform;
                this.loadSocialFeed(platform);
            });
        });
    }
    
    populateSocialFeed() {
        const socialFeed = document.getElementById('socialFeed');
        const posts = [
            {
                user: "@RadioFan123",
                content: "Loving the morning show on @K5NewsFM! Great music selection 🎵",
                time: "2m ago",
                likes: 12,
                retweets: 3
            },
            {
                user: "@LocalNews",
                content: "Thanks @K5NewsFM for covering the community center story! 📰",
                time: "15m ago",
                likes: 28,
                retweets: 8
            },
            {
                user: "@MusicLover",
                content: "That Queen song on K5 just made my day! 🎸",
                time: "32m ago",
                likes: 15,
                retweets: 2
            }
        ];
        
        socialFeed.innerHTML = posts.map(post => `
            <div class="social-post">
                <div class="post-header">
                    <strong>${post.user}</strong>
                    <span class="post-time">${post.time}</span>
                </div>
                <div class="post-content">${post.content}</div>
                <div class="post-actions">
                    <span class="post-stat">❤️ ${post.likes}</span>
                    <span class="post-stat">🔄 ${post.retweets}</span>
                </div>
            </div>
        `).join('');
    }
    
    loadSocialFeed(platform) {
        // Simulate loading different platform feeds
        const feeds = {
            twitter: [
                { user: "@RadioFan123", content: "Loving the morning show! 🎵", time: "2m ago" },
                { user: "@LocalNews", content: "Thanks for the coverage! 📰", time: "15m ago" }
            ],
            facebook: [
                { user: "John Smith", content: "Great music on K5 today!", time: "5m ago" },
                { user: "Sarah Johnson", content: "Love listening during my commute", time: "20m ago" }
            ],
            instagram: [
                { user: "music_lover_92", content: "📻 K5 News FM vibes", time: "10m ago" },
                { user: "local_events", content: "Tune in for event updates!", time: "1h ago" }
            ]
        };
        
        const socialFeed = document.getElementById('socialFeed');
        const posts = feeds[platform] || [];
        
        socialFeed.innerHTML = posts.map(post => `
            <div class="social-post">
                <div class="post-header">
                    <strong>${post.user}</strong>
                    <span class="post-time">${post.time}</span>
                </div>
                <div class="post-content">${post.content}</div>
            </div>
        `).join('');
    }
    
    handleQuickAction(action) {
        const actions = {
            'emergency': () => this.showModal('Emergency Alert', 'Send emergency broadcast alert?'),
            'weather-alert': () => this.showModal('Weather Alert', 'Send weather warning to listeners?'),
            'traffic-update': () => this.showModal('Traffic Update', 'Broadcast traffic information?'),
            'contest': () => this.showModal('Contest', 'Start new listener contest?'),
            'breaking-news': () => this.showModal('Breaking News', 'Send breaking news alert?'),
            'social-post': () => this.showModal('Social Post', 'Create new social media post?')
        };
        
        if (actions[action]) {
            actions[action]();
        }
    }
    
    showModal(title, message) {
        const modal = document.createElement('div');
        modal.className = 'dashboard-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <h3>${title}</h3>
                <p>${message}</p>
                <div class="modal-actions">
                    <button class="btn-cancel">Cancel</button>
                    <button class="btn-confirm">Confirm</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        modal.querySelector('.btn-cancel').addEventListener('click', () => modal.remove());
        modal.querySelector('.btn-confirm').addEventListener('click', () => {
            this.showNotification(`${title} sent successfully!`, 'success');
            modal.remove();
        });
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `dashboard-notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    initializeCharts() {
        const canvas = document.getElementById('listenerChart');
        const ctx = canvas.getContext('2d');
        
        // Generate sample data
        const hours = Array.from({length: 24}, (_, i) => i);
        const listenerData = hours.map(hour => {
            const baseListeners = 800;
            const peakHours = [7, 8, 9, 17, 18, 19]; // Morning and evening peaks
            const isPeak = peakHours.includes(hour);
            const variation = Math.random() * 200;
            return Math.floor(baseListeners + (isPeak ? 400 : 0) + variation);
        });
        
        this.drawChart(ctx, canvas, listenerData, hours);
    }
    
    drawChart(ctx, canvas, data, labels) {
        const padding = 40;
        const chartWidth = canvas.width - padding * 2;
        const chartHeight = canvas.height - padding * 2;
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw grid
        ctx.strokeStyle = '#e0e0e0';
        ctx.lineWidth = 1;
        
        for (let i = 0; i <= 5; i++) {
            const y = padding + (chartHeight / 5) * i;
            ctx.beginPath();
            ctx.moveTo(padding, y);
            ctx.lineTo(padding + chartWidth, y);
            ctx.stroke();
        }
        
        // Draw line chart
        const maxValue = Math.max(...data);
        const minValue = Math.min(...data);
        const valueRange = maxValue - minValue;
        
        ctx.strokeStyle = '#0d6efd';
        ctx.lineWidth = 3;
        ctx.beginPath();
        
        data.forEach((value, index) => {
            const x = padding + (chartWidth / (data.length - 1)) * index;
            const y = padding + chartHeight - ((value - minValue) / valueRange) * chartHeight;
            
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        
        ctx.stroke();
        
        // Draw data points
        ctx.fillStyle = '#0d6efd';
        data.forEach((value, index) => {
            const x = padding + (chartWidth / (data.length - 1)) * index;
            const y = padding + chartHeight - ((value - minValue) / valueRange) * chartHeight;
            
            ctx.beginPath();
            ctx.arc(x, y, 4, 0, Math.PI * 2);
            ctx.fill();
        });
    }
    
    startRealTimeUpdates() {
        this.updateInterval = setInterval(() => {
            this.updateStats();
            this.updateNowPlaying();
        }, 5000);
    }
    
    updateStats() {
        // Simulate real-time stat updates
        const listenerCount = document.getElementById('listenerCount');
        const currentCount = parseInt(listenerCount.textContent.replace(',', ''));
        const variation = Math.floor(Math.random() * 20) - 10;
        const newCount = Math.max(1000, currentCount + variation);
        
        this.animateNumber(listenerCount, currentCount, newCount);
    }
    
    updateNowPlaying() {
        const songs = [
            { title: "Hotel California", artist: "Eagles" },
            { title: "Stairway to Heaven", artist: "Led Zeppelin" },
            { title: "Sweet Child O' Mine", artist: "Guns N' Roses" },
            { title: "Imagine", artist: "John Lennon" }
        ];
        
        if (Math.random() < 0.3) { // 30% chance to change song
            const randomSong = songs[Math.floor(Math.random() * songs.length)];
            document.getElementById('trackTitle').textContent = randomSong.title;
            document.getElementById('trackArtist').textContent = randomSong.artist;
            
            // Reset progress bar
            const progressFill = document.querySelector('.progress-fill');
            progressFill.style.width = '0%';
            
            // Animate progress
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 0.5;
                progressFill.style.width = `${progress}%`;
                if (progress >= 100) {
                    clearInterval(progressInterval);
                }
            }, 100);
        }
    }
    
    animateNumber(element, start, end) {
        const duration = 1000;
        const startTime = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = Math.floor(start + (end - start) * progress);
            element.textContent = current.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }
    
    destroy() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.interactiveDashboard = new InteractiveDashboard();
});
