// Modern K5 News FM Application
class ModernK5App {
    constructor() {
        this.isPlaying = false;
        this.currentTrack = {
            title: "Bohemian Rhapsody",
            artist: "Queen",
            album: "A Night at the Opera",
            duration: 367, // seconds
            currentTime: 165
        };
        this.volume = 0.75;
        this.tracks = [];
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.initializeAudioVisualizer();
        this.populateRecentlyPlayed();
        this.startAnimations();
        this.initializeStats();
    }
    
    setupEventListeners() {
        // Play/Pause Button in Player
        const playPauseBtn = document.querySelector('.control-btn.main');
        if (playPauseBtn) {
            playPauseBtn.addEventListener('click', () => this.togglePlayback());
        }
        
        // Volume Control
        const volumeSlider = document.querySelector('.volume-slider');
        if (volumeSlider) {
            volumeSlider.addEventListener('input', (e) => {
                this.setVolume(e.target.value / 100);
            });
        }
        
        // Progress Bar
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.addEventListener('click', (e) => this.seekTrack(e));
        }
        
        // Refresh Button
        const refreshBtn = document.querySelector('.refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshTracks());
        }
        
        // Track Action Buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.track-btn')) {
                this.handleTrackAction(e.target.closest('.track-btn'));
            }
            
            if (e.target.closest('.action-btn')) {
                this.handlePlayerAction(e.target.closest('.action-btn'));
            }
        });
        
        // Smooth Scrolling for Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
        
        // Navbar Scroll Effect
        window.addEventListener('scroll', () => this.handleNavbarScroll());
    }
    
    togglePlayback() {
        this.isPlaying = !this.isPlaying;
        this.updatePlayButton();
        
        if (this.isPlaying) {
            this.startProgressAnimation();
            this.showNotification('🎵 Now playing live stream', 'success');
        } else {
            this.stopProgressAnimation();
            this.showNotification('⏸️ Playback paused', 'info');
        }
    }
    
    updatePlayButton() {
        const playButtons = document.querySelectorAll('.control-btn.main');
        playButtons.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                if (this.isPlaying) {
                    icon.className = 'bi bi-pause-fill';
                    btn.querySelector('.btn-text')?.textContent = 'Pause Live';
                } else {
                    icon.className = 'bi bi-play-fill';
                    btn.querySelector('.btn-text')?.textContent = 'Listen Live';
                }
            }
        });
    }
    
    setVolume(volume) {
        this.volume = volume;
        // Update volume slider visual
        const volumeSlider = document.querySelector('.volume-slider');
        if (volumeSlider) {
            volumeSlider.value = volume * 100;
        }
    }
    
    seekTrack(event) {
        const progressBar = event.currentTarget;
        const rect = progressBar.getBoundingClientRect();
        const percent = (event.clientX - rect.left) / rect.width;
        
        this.currentTrack.currentTime = Math.floor(percent * this.currentTrack.duration);
        this.updateProgress();
    }
    
    updateProgress() {
        const progressFill = document.querySelector('.progress-fill');
        const currentTimeEl = document.querySelector('.current-time');
        const totalTimeEl = document.querySelector('.total-time');
        
        if (progressFill) {
            const percent = (this.currentTrack.currentTime / this.currentTrack.duration) * 100;
            progressFill.style.width = `${percent}%`;
        }
        
        if (currentTimeEl) {
            currentTimeEl.textContent = this.formatTime(this.currentTrack.currentTime);
        }
        
        if (totalTimeEl) {
            totalTimeEl.textContent = this.formatTime(this.currentTrack.duration);
        }
    }
    
    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
    
    startProgressAnimation() {
        this.progressInterval = setInterval(() => {
            if (this.isPlaying && this.currentTrack.currentTime < this.currentTrack.duration) {
                this.currentTrack.currentTime++;
                this.updateProgress();
            }
        }, 1000);
    }
    
    stopProgressAnimation() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }
    }
    
    populateRecentlyPlayed() {
        this.tracks = [
            {
                title: "Hotel California",
                artist: "Eagles",
                album: "Hotel California",
                time: "3 min ago",
                artwork: "https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=60&h=60&fit=crop"
            },
            {
                title: "Sweet Child O' Mine",
                artist: "Guns N' Roses",
                album: "Appetite for Destruction",
                time: "8 min ago",
                artwork: "https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=60&h=60&fit=crop"
            },
            {
                title: "Stairway to Heaven",
                artist: "Led Zeppelin",
                album: "Led Zeppelin IV",
                time: "15 min ago",
                artwork: "https://images.unsplash.com/photo-1516280440614-37939bbacd81?w=60&h=60&fit=crop"
            },
            {
                title: "Imagine",
                artist: "John Lennon",
                album: "Imagine",
                time: "22 min ago",
                artwork: "https://images.unsplash.com/photo-1478737270239-2f02b77fc618?w=60&h=60&fit=crop"
            },
            {
                title: "Don't Stop Believin'",
                artist: "Journey",
                album: "Escape",
                time: "28 min ago",
                artwork: "https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=60&h=60&fit=crop"
            }
        ];
        
        this.renderTracks();
    }
    
    renderTracks() {
        const tracksList = document.querySelector('.tracks-list');
        if (!tracksList) return;
        
        tracksList.innerHTML = this.tracks.map((track, index) => `
            <div class="track-item" style="animation-delay: ${index * 0.1}s">
                <div class="track-artwork-small">
                    <img src="${track.artwork}" alt="${track.title}" loading="lazy">
                    <div class="play-overlay-small">
                        <i class="bi bi-play-fill"></i>
                    </div>
                </div>
                <div class="track-info-small">
                    <h6>${track.title}</h6>
                    <p>${track.artist}</p>
                    <small>${track.album}</small>
                </div>
                <div class="track-time-small">
                    ${track.time}
                </div>
            </div>
        `).join('');
    }
    
    refreshTracks() {
        const newTracks = [
            "Purple Rain - Prince",
            "Thunderstruck - AC/DC",
            "Livin' on a Prayer - Bon Jovi",
            "We Will Rock You - Queen"
        ];
        
        const randomTrack = newTracks[Math.floor(Math.random() * newTracks.length)];
        const [title, artist] = randomTrack.split(' - ');
        
        // Add new track to the beginning
        this.tracks.unshift({
            title,
            artist,
            album: "Latest Hit",
            time: "Just now",
            artwork: "https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=60&h=60&fit=crop"
        });
        
        // Keep only 5 tracks
        this.tracks = this.tracks.slice(0, 5);
        
        this.renderTracks();
        this.showNotification(`🎵 Added: ${title} by ${artist}`, 'success');
    }
    
    handleTrackAction(button) {
        const action = button.querySelector('span')?.textContent;
        const trackTitle = this.currentTrack.title;
        
        switch(action) {
            case 'Like':
                this.showNotification(`❤️ Liked: ${trackTitle}`, 'success');
                break;
            case 'Share':
                this.showNotification(`📤 Shared: ${trackTitle}`, 'info');
                break;
            case 'Add to Playlist':
                this.showNotification(`➕ Added to playlist: ${trackTitle}`, 'success');
                break;
        }
    }
    
    handlePlayerAction(button) {
        const icon = button.querySelector('i').className;
        
        if (icon.includes('share')) {
            this.showNotification('📤 Share link copied to clipboard', 'info');
        } else if (icon.includes('heart')) {
            this.showNotification('❤️ Added to favorites', 'success');
        } else if (icon.includes('download')) {
            this.showNotification('📥 Download started', 'info');
        }
    }
    
    initializeAudioVisualizer() {
        const canvas = document.getElementById('audioVisualizer');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const bars = 32;
        const barWidth = canvas.width / bars;
        
        const animate = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            for (let i = 0; i < bars; i++) {
                const barHeight = this.isPlaying 
                    ? Math.random() * canvas.height * 0.8 + 10
                    : Math.sin(Date.now() * 0.001 + i * 0.5) * 20 + 30;
                
                const gradient = ctx.createLinearGradient(0, canvas.height, 0, canvas.height - barHeight);
                gradient.addColorStop(0, '#6366f1');
                gradient.addColorStop(1, '#8b5cf6');
                
                ctx.fillStyle = gradient;
                ctx.fillRect(i * barWidth, canvas.height - barHeight, barWidth - 2, barHeight);
            }
            
            requestAnimationFrame(animate);
        };
        
        animate();
    }
    
    initializeStats() {
        // Animate stat numbers
        const statNumbers = document.querySelectorAll('.stat-number[data-count]');
        
        const animateNumber = (element, target) => {
            const duration = 2000;
            const start = 0;
            const startTime = performance.now();
            
            const animate = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const current = Math.floor(start + (target - start) * progress);
                element.textContent = current.toLocaleString();
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            };
            
            requestAnimationFrame(animate);
        };
        
        // Intersection Observer for stats animation
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = parseInt(entry.target.dataset.count);
                    animateNumber(entry.target, target);
                    observer.unobserve(entry.target);
                }
            });
        });
        
        statNumbers.forEach(stat => observer.observe(stat));
    }
    
    startAnimations() {
        // Update progress initially
        this.updateProgress();
        
        // Simulate live listener count updates
        setInterval(() => {
            const listenerElements = document.querySelectorAll('.listener-count');
            const variation = Math.floor(Math.random() * 20) - 10;
            const newCount = Math.max(1200, 1247 + variation);
            
            listenerElements.forEach(el => {
                el.textContent = newCount.toLocaleString();
            });
        }, 10000);
    }
    
    handleNavbarScroll() {
        const navbar = document.querySelector('.glass-nav');
        if (window.scrollY > 100) {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
        }
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-left: 4px solid ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#6366f1'};
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            max-width: 400px;
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Auto remove
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        });
    }
}

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.modernK5App = new ModernK5App();
});

// Add notification styles
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    .notification-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    
    .notification-message {
        font-size: 14px;
        color: #374151;
        font-weight: 500;
    }
    
    .notification-close {
        background: none;
        border: none;
        font-size: 18px;
        color: #9ca3af;
        cursor: pointer;
        padding: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .notification-close:hover {
        color: #374151;
    }
    
    .track-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        animation: slideInFromRight 0.3s ease forwards;
    }
    
    .track-item:last-child {
        border-bottom: none;
    }
    
    .track-item:hover {
        background: rgba(99, 102, 241, 0.05);
        transform: translateX(4px);
    }
    
    .track-artwork-small {
        position: relative;
        width: 48px;
        height: 48px;
        border-radius: 8px;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .track-artwork-small img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .play-overlay-small {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        opacity: 0;
        transition: opacity 0.3s ease;
        cursor: pointer;
    }
    
    .track-artwork-small:hover .play-overlay-small {
        opacity: 1;
    }
    
    .track-info-small {
        flex: 1;
        min-width: 0;
    }
    
    .track-info-small h6 {
        margin: 0 0 4px 0;
        font-size: 14px;
        font-weight: 600;
        color: #111827;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .track-info-small p {
        margin: 0 0 2px 0;
        font-size: 13px;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .track-info-small small {
        font-size: 11px;
        color: #9ca3af;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }
    
    .track-time-small {
        font-size: 12px;
        color: #9ca3af;
        font-weight: 500;
        flex-shrink: 0;
    }
    
    @keyframes slideInFromRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
`;
document.head.appendChild(notificationStyles);
