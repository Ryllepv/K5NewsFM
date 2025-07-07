// Advanced Frontend Features for K5 News FM
// Cutting-edge vanilla JavaScript with modern web APIs

class AdvancedK5Frontend {
    constructor() {
        this.audioContext = null;
        this.analyser = null;
        this.dataArray = null;
        this.animationId = null;
        this.particles = [];
        this.mousePosition = { x: 0, y: 0 };
        this.isPlaying = false;
        this.currentTheme = 'auto';
        
        this.init();
    }
    
    async init() {
        this.setupAdvancedAudioVisualizer();
        this.initParticleSystem();
        this.setupAdvancedScrollEffects();
        this.initAdvancedInteractions();
        this.setupRealTimeFeatures();
        this.initAdvancedAnimations();
        this.setupAdvancedUI();
        this.initThemeSystem();
        this.setupAdvancedSearch();
        this.initVirtualScrolling();
    }
    
    // Advanced Audio Visualizer with Web Audio API
    setupAdvancedAudioVisualizer() {
        const canvas = document.createElement('canvas');
        canvas.id = 'audioVisualizer';
        canvas.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            opacity: 0.7;
        `;
        document.body.appendChild(canvas);
        
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.resizeCanvas();
        
        window.addEventListener('resize', () => this.resizeCanvas());
        
        // Create audio context when user interacts
        document.addEventListener('click', () => this.initAudioContext(), { once: true });
    }
    
    async initAudioContext() {
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            this.analyser = this.audioContext.createAnalyser();
            this.analyser.fftSize = 256;
            this.dataArray = new Uint8Array(this.analyser.frequencyBinCount);
            
            // Connect to audio element when available
            const audioElement = document.querySelector('audio');
            if (audioElement) {
                const source = this.audioContext.createMediaElementSource(audioElement);
                source.connect(this.analyser);
                this.analyser.connect(this.audioContext.destination);
            }
            
            this.startVisualization();
        } catch (error) {
            console.warn('Audio context initialization failed:', error);
        }
    }
    
    startVisualization() {
        const animate = () => {
            this.animationId = requestAnimationFrame(animate);
            this.drawVisualization();
        };
        animate();
    }
    
    drawVisualization() {
        if (!this.analyser) return;
        
        this.analyser.getByteFrequencyData(this.dataArray);
        
        this.ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        
        const barWidth = this.canvas.width / this.dataArray.length * 2;
        let x = 0;
        
        for (let i = 0; i < this.dataArray.length; i++) {
            const barHeight = (this.dataArray[i] / 255) * this.canvas.height * 0.5;
            
            // Create gradient for each bar
            const gradient = this.ctx.createLinearGradient(0, this.canvas.height, 0, this.canvas.height - barHeight);
            gradient.addColorStop(0, `hsl(${i * 2}, 70%, 50%)`);
            gradient.addColorStop(1, `hsl(${i * 2 + 60}, 70%, 70%)`);
            
            this.ctx.fillStyle = gradient;
            this.ctx.fillRect(x, this.canvas.height - barHeight, barWidth, barHeight);
            
            x += barWidth + 1;
        }
        
        // Draw particles that react to audio
        this.updateAudioParticles();
    }
    
    updateAudioParticles() {
        if (!this.dataArray) return;
        
        // Create new particles based on audio intensity
        const avgFrequency = this.dataArray.reduce((a, b) => a + b) / this.dataArray.length;
        
        if (avgFrequency > 50 && Math.random() < 0.3) {
            this.particles.push({
                x: Math.random() * this.canvas.width,
                y: this.canvas.height,
                vx: (Math.random() - 0.5) * 4,
                vy: -Math.random() * 8 - 2,
                life: 1,
                decay: 0.02,
                size: Math.random() * 4 + 2,
                hue: Math.random() * 360
            });
        }
        
        // Update and draw particles
        this.particles = this.particles.filter(particle => {
            particle.x += particle.vx;
            particle.y += particle.vy;
            particle.vy += 0.1; // gravity
            particle.life -= particle.decay;
            
            if (particle.life > 0) {
                this.ctx.save();
                this.ctx.globalAlpha = particle.life;
                this.ctx.fillStyle = `hsl(${particle.hue}, 70%, 60%)`;
                this.ctx.beginPath();
                this.ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
                this.ctx.fill();
                this.ctx.restore();
                return true;
            }
            return false;
        });
    }
    
    resizeCanvas() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
    }
    
    // Advanced Particle System
    initParticleSystem() {
        this.mouseParticles = [];
        
        document.addEventListener('mousemove', (e) => {
            this.mousePosition.x = e.clientX;
            this.mousePosition.y = e.clientY;
            
            // Create mouse trail particles
            if (Math.random() < 0.1) {
                this.mouseParticles.push({
                    x: e.clientX,
                    y: e.clientY,
                    vx: (Math.random() - 0.5) * 2,
                    vy: (Math.random() - 0.5) * 2,
                    life: 1,
                    decay: 0.05,
                    size: Math.random() * 3 + 1
                });
            }
        });
        
        this.animateMouseParticles();
    }
    
    animateMouseParticles() {
        const animate = () => {
            if (this.ctx) {
                this.mouseParticles = this.mouseParticles.filter(particle => {
                    particle.x += particle.vx;
                    particle.y += particle.vy;
                    particle.life -= particle.decay;
                    
                    if (particle.life > 0) {
                        this.ctx.save();
                        this.ctx.globalAlpha = particle.life * 0.5;
                        this.ctx.fillStyle = '#0d6efd';
                        this.ctx.beginPath();
                        this.ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
                        this.ctx.fill();
                        this.ctx.restore();
                        return true;
                    }
                    return false;
                });
            }
            requestAnimationFrame(animate);
        };
        animate();
    }
    
    // Advanced Scroll Effects with Intersection Observer
    setupAdvancedScrollEffects() {
        // Parallax scrolling
        const parallaxElements = document.querySelectorAll('[data-parallax]');
        
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            
            parallaxElements.forEach(element => {
                const rate = scrolled * (element.dataset.parallax || 0.5);
                element.style.transform = `translateY(${rate}px)`;
            });
        });
        
        // Advanced reveal animations
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const animationType = element.dataset.reveal || 'fadeInUp';
                    
                    element.style.animation = `${animationType} 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards`;
                    revealObserver.unobserve(element);
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('[data-reveal]').forEach(el => {
            revealObserver.observe(el);
        });
        
        // Morphing scroll progress indicator
        this.createScrollProgress();
    }
    
    createScrollProgress() {
        const progressBar = document.createElement('div');
        progressBar.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4);
            background-size: 400% 100%;
            z-index: 9999;
            animation: morphingGradient 3s ease infinite;
            transition: width 0.1s ease;
        `;
        document.body.appendChild(progressBar);
        
        window.addEventListener('scroll', () => {
            const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
            progressBar.style.width = `${scrollPercent}%`;
        });
    }
    
    // Advanced Interactive Elements
    initAdvancedInteractions() {
        this.setupMagneticButtons();
        this.setupTiltEffects();
        this.setupRippleEffects();
        this.setupAdvancedHovers();
    }
    
    setupMagneticButtons() {
        const magneticElements = document.querySelectorAll('.magnetic');
        
        magneticElements.forEach(element => {
            element.addEventListener('mousemove', (e) => {
                const rect = element.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                
                const distance = Math.sqrt(x * x + y * y);
                const maxDistance = 100;
                
                if (distance < maxDistance) {
                    const strength = (maxDistance - distance) / maxDistance;
                    const moveX = x * strength * 0.3;
                    const moveY = y * strength * 0.3;
                    
                    element.style.transform = `translate(${moveX}px, ${moveY}px)`;
                }
            });
            
            element.addEventListener('mouseleave', () => {
                element.style.transform = 'translate(0, 0)';
            });
        });
    }
    
    setupTiltEffects() {
        const tiltElements = document.querySelectorAll('.tilt-effect');
        
        tiltElements.forEach(element => {
            element.addEventListener('mousemove', (e) => {
                const rect = element.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / centerY * -10;
                const rotateY = (x - centerX) / centerX * 10;
                
                element.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            });
            
            element.addEventListener('mouseleave', () => {
                element.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
            });
        });
    }
    
    setupRippleEffects() {
        const rippleElements = document.querySelectorAll('.ripple-effect');
        
        rippleElements.forEach(element => {
            element.addEventListener('click', (e) => {
                const rect = element.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const ripple = document.createElement('span');
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    left: ${x}px;
                    top: ${y}px;
                    width: 20px;
                    height: 20px;
                    margin-left: -10px;
                    margin-top: -10px;
                `;
                
                element.style.position = 'relative';
                element.style.overflow = 'hidden';
                element.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });
    }
    
    setupAdvancedHovers() {
        const hoverElements = document.querySelectorAll('.advanced-hover');
        
        hoverElements.forEach(element => {
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
                transform: translateX(-100%);
                transition: transform 0.6s ease;
                pointer-events: none;
            `;
            
            element.style.position = 'relative';
            element.style.overflow = 'hidden';
            element.appendChild(overlay);
            
            element.addEventListener('mouseenter', () => {
                overlay.style.transform = 'translateX(100%)';
            });
            
            element.addEventListener('mouseleave', () => {
                overlay.style.transform = 'translateX(-100%)';
            });
        });
    }
    
    // Real-time Features Simulation
    setupRealTimeFeatures() {
        this.simulateRealTimeUpdates();
        this.setupLiveListenerCount();
        this.setupRealTimeNotifications();
    }
    
    simulateRealTimeUpdates() {
        // Simulate real-time track updates
        setInterval(() => {
            this.updateCurrentTrack();
        }, 30000); // Every 30 seconds
        
        // Simulate listener count updates
        setInterval(() => {
            this.updateListenerCount();
        }, 5000); // Every 5 seconds
    }
    
    updateCurrentTrack() {
        const tracks = [
            { title: "Bohemian Rhapsody", artist: "Queen" },
            { title: "Hotel California", artist: "Eagles" },
            { title: "Stairway to Heaven", artist: "Led Zeppelin" },
            { title: "Sweet Child O' Mine", artist: "Guns N' Roses" },
            { title: "Imagine", artist: "John Lennon" }
        ];
        
        const randomTrack = tracks[Math.floor(Math.random() * tracks.length)];
        
        // Update track info with smooth animation
        const trackElements = document.querySelectorAll('.current-track');
        trackElements.forEach(element => {
            element.style.opacity = '0';
            setTimeout(() => {
                element.textContent = `${randomTrack.title} - ${randomTrack.artist}`;
                element.style.opacity = '1';
            }, 300);
        });
        
        // Trigger notification
        this.showNotification(`Now Playing: ${randomTrack.title} by ${randomTrack.artist}`, 'music');
    }
    
    updateListenerCount() {
        const baseCount = 1247;
        const variation = Math.floor(Math.random() * 100) - 50;
        const count = baseCount + variation;
        
        const countElements = document.querySelectorAll('.listener-count');
        countElements.forEach(element => {
            this.animateNumber(element, parseInt(element.textContent) || baseCount, count);
        });
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
    
    setupRealTimeNotifications() {
        // Simulate breaking news notifications
        setTimeout(() => {
            this.showNotification("Breaking: City Council approves new community center", 'news');
        }, 10000);
        
        setTimeout(() => {
            this.showNotification("Contest Alert: Call now to win concert tickets!", 'contest');
        }, 25000);
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">${this.getNotificationIcon(type)}</div>
                <div class="notification-message">${message}</div>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            padding: 16px;
            max-width: 400px;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
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
        }, 5000);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        });
    }
    
    getNotificationIcon(type) {
        const icons = {
            music: '🎵',
            news: '📰',
            contest: '🎉',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    }
}

// Initialize advanced frontend
document.addEventListener('DOMContentLoaded', () => {
    window.advancedK5 = new AdvancedK5Frontend();
});

// Add advanced CSS animations
const advancedStyles = document.createElement('style');
advancedStyles.textContent = `
    @keyframes morphingGradient {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .notification-icon {
        font-size: 20px;
    }
    
    .notification-message {
        flex: 1;
        font-size: 14px;
        color: #333;
    }
    
    .notification-close {
        background: none;
        border: none;
        font-size: 18px;
        color: #999;
        cursor: pointer;
        padding: 0;
        width: 20px;
        height: 20px;
    }
`;
document.head.appendChild(advancedStyles);
