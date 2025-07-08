// K5 News Radio - Complete JavaScript Application
class K5NewsRadio {
    constructor() {
        this.isPlaying = false;
        this.volume = 0.75;
        this.currentProgram = {
            name: "Morning News Briefing",
            hosts: "Maria Santos & John Cruz",
            startTime: "6:00 AM",
            endTime: "9:00 AM"
        };
        this.audioContext = null;
        this.audioStream = null;

        // Radio stream URL - Replace with your actual stream URL
        // For testing, you can use a demo stream like this:
        // this.streamUrl = 'https://stream.zeno.fm/your-station-id'; // Zeno.FM example
        // this.streamUrl = 'https://ice1.somafm.com/groovesalad-256-mp3'; // Demo stream for testing
        this.streamUrl = 'https://your-radio-stream-url.com/stream'; // UPDATE THIS WITH YOUR ACTUAL STREAM URL

        // Alternative demo stream for testing (remove when you have your real stream)
        this.demoStreamUrl = 'https://ice1.somafm.com/groovesalad-256-mp3';

        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.initializeClock();
        this.initializeAudioVisualizer();
        this.initializeEqualizer();
        this.setupSmoothScrolling();
        this.initializeNewsUpdates();
        this.setupFormHandlers();
        this.initializeWeatherUpdates();
        this.initializeTrafficUpdates();
    }
    
    setupEventListeners() {
        // Play/Pause buttons
        const playButtons = document.querySelectorAll('#mainPlayBtn, #mainPlayerBtn, .play-btn');
        playButtons.forEach(btn => {
            btn.addEventListener('click', () => this.togglePlayback());
        });
        
        // Volume controls
        const volumeSliders = document.querySelectorAll('.volume-slider, .volume-slider-main');
        volumeSliders.forEach(slider => {
            slider.addEventListener('input', (e) => this.setVolume(e.target.value / 100));
        });
        
        // Contact form
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', (e) => this.handleContactForm(e));
        }
        
        // News alerts form
        const alertsForm = document.getElementById('alertsForm');
        if (alertsForm) {
            alertsForm.addEventListener('submit', (e) => this.handleNewsAlerts(e));
        }

        // Navigation scroll effect
        window.addEventListener('scroll', () => this.handleNavbarScroll());
        
        // Breaking news ticker pause on hover
        const ticker = document.querySelector('.ticker-text span');
        if (ticker) {
            ticker.addEventListener('mouseenter', () => {
                ticker.style.animationPlayState = 'paused';
            });
            ticker.addEventListener('mouseleave', () => {
                ticker.style.animationPlayState = 'running';
            });
        }
    }
    
    togglePlayback() {
        this.isPlaying = !this.isPlaying;
        this.updatePlayButtons();
        
        if (this.isPlaying) {
            this.startAudioStream();
            this.showNotification('📻 Now listening to K5 News Radio 88.7 FM live!', 'success');
        } else {
            this.stopAudioStream();
            this.showNotification('⏸️ 88.7 FM stream paused', 'info');
        }
        
        this.updateVisualizerState();
    }
    
    updatePlayButtons() {
        const playButtons = document.querySelectorAll('#mainPlayBtn, #mainPlayerBtn, .play-btn');
        playButtons.forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                if (this.isPlaying) {
                    icon.className = 'bi bi-pause-fill';
                    if (btn.textContent.includes('Listen')) {
                        btn.innerHTML = '<i class="bi bi-pause-fill me-2"></i>Pause Live';
                    }
                } else {
                    icon.className = 'bi bi-play-fill';
                    if (btn.textContent.includes('Pause')) {
                        btn.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i>Listen Live Now';
                    }
                }
            }
        });
    }
    
    setVolume(volume) {
        this.volume = volume;
        const volumeSliders = document.querySelectorAll('.volume-slider, .volume-slider-main');
        volumeSliders.forEach(slider => {
            slider.value = volume * 100;
        });
        
        if (this.audioStream) {
            this.audioStream.volume = volume;
        }
    }
    
    async startAudioStream() {
        try {
            // Create new audio element for the stream
            this.audioStream = new Audio();
            this.audioStream.volume = this.volume;
            this.audioStream.crossOrigin = "anonymous";

            // Set the stream URL - UPDATE THIS WITH YOUR ACTUAL STREAM URL
            // Use demo stream if real stream URL is not configured
            const streamToUse = this.streamUrl.includes('your-radio-stream-url.com')
                ? this.demoStreamUrl
                : this.streamUrl;

            this.audioStream.src = streamToUse;

            if (streamToUse === this.demoStreamUrl) {
                console.log('Using demo stream. Please update streamUrl with your actual radio stream.');
            }

            // Add event listeners for better user experience
            this.audioStream.addEventListener('loadstart', () => {
                this.showNotification('📡 Connecting to 88.7 FM...', 'info');
                this.updatePlayButtonsLoading(true);
            });

            this.audioStream.addEventListener('canplay', () => {
                this.showNotification('📻 Connected to K5 News Radio 88.7 FM!', 'success');
                this.updatePlayButtonsLoading(false);
            });

            this.audioStream.addEventListener('error', (e) => {
                console.error('Audio stream error:', e);
                this.handleStreamError();
            });

            this.audioStream.addEventListener('ended', () => {
                this.handleStreamEnded();
            });

            // Attempt to play the stream
            await this.audioStream.play();

            console.log('Audio stream started successfully');

        } catch (error) {
            console.error('Failed to start audio stream:', error);
            this.handleStreamError();
        }
    }
    
    stopAudioStream() {
        if (this.audioStream) {
            this.audioStream.pause();
            this.audioStream.currentTime = 0;
            this.audioStream.src = '';
            this.audioStream = null;
        }
        this.updatePlayButtonsLoading(false);
        console.log('Audio stream stopped');
    }

    handleStreamError() {
        this.isPlaying = false;
        this.updatePlayButtons();
        this.updatePlayButtonsLoading(false);
        this.showNotification('❌ Unable to connect to radio stream. Please check your internet connection or try again later.', 'error');
    }

    handleStreamEnded() {
        this.isPlaying = false;
        this.updatePlayButtons();
        this.showNotification('📻 Stream ended. Attempting to reconnect...', 'info');

        // Attempt to reconnect after 3 seconds
        setTimeout(() => {
            if (!this.isPlaying) {
                this.togglePlayback();
            }
        }, 3000);
    }

    updatePlayButtonsLoading(isLoading) {
        const playButtons = document.querySelectorAll('#mainPlayBtn, #mainPlayerBtn, .play-btn');
        playButtons.forEach(btn => {
            if (isLoading) {
                btn.disabled = true;
                btn.style.opacity = '0.7';
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.className = 'bi bi-arrow-clockwise spin';
                }
            } else {
                btn.disabled = false;
                btn.style.opacity = '1';
            }
        });
    }
    
    initializeClock() {
        const updateClock = () => {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: 'numeric',
                minute: '2-digit'
            });
            
            const clockElements = document.querySelectorAll('#currentTime, .current-time');
            clockElements.forEach(element => {
                if (element) element.textContent = timeString;
            });
        };
        
        updateClock();
        setInterval(updateClock, 1000);
    }
    
    initializeAudioVisualizer() {
        const visualizerBars = document.querySelectorAll('.visualizer-bar');
        
        const animateBars = () => {
            visualizerBars.forEach((bar, index) => {
                const height = this.isPlaying 
                    ? Math.random() * 40 + 10 
                    : Math.sin(Date.now() * 0.001 + index * 0.5) * 15 + 20;
                
                bar.style.height = `${height}px`;
            });
            
            requestAnimationFrame(animateBars);
        };
        
        animateBars();
    }
    
    initializeEqualizer() {
        const eqBars = document.querySelectorAll('.eq-bar');
        
        const animateEqualizer = () => {
            eqBars.forEach((bar, index) => {
                const height = this.isPlaying 
                    ? Math.random() * 60 + 20 
                    : Math.sin(Date.now() * 0.002 + index * 0.3) * 20 + 30;
                
                bar.style.height = `${height}px`;
            });
            
            requestAnimationFrame(animateEqualizer);
        };
        
        animateEqualizer();
    }
    
    updateVisualizerState() {
        const visualizers = document.querySelectorAll('.visualizer-bar, .eq-bar');
        visualizers.forEach(bar => {
            if (this.isPlaying) {
                bar.style.animationPlayState = 'running';
            } else {
                bar.style.animationPlayState = 'paused';
            }
        });
    }
    
    setupSmoothScrolling() {
        const navLinks = document.querySelectorAll('.nav-link[href^="#"]');
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                const targetId = link.getAttribute('href');
                const targetSection = document.querySelector(targetId);
                
                if (targetSection) {
                    const offsetTop = targetSection.offsetTop - 80; // Account for fixed navbar
                    
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                    
                    // Close mobile menu if open
                    const navbarCollapse = document.querySelector('.navbar-collapse');
                    if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                        const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                        bsCollapse.hide();
                    }
                    
                    // Update active nav link
                    navLinks.forEach(nl => nl.classList.remove('active'));
                    link.classList.add('active');
                }
            });
        });
    }
    
    initializeNewsUpdates() {
        // Simulate breaking news updates
        const breakingNews = [
            "City Council approves new infrastructure projects",
            "Weather alert: Heavy rains expected this weekend", 
            "Local business awards ceremony tonight at 7PM",
            "Traffic update: Road construction on Rizal Avenue",
            "Community event: Christmas festival preparations underway"
        ];
        
        let currentNewsIndex = 0;
        
        setInterval(() => {
            const ticker = document.querySelector('.ticker-text span');
            if (ticker) {
                currentNewsIndex = (currentNewsIndex + 1) % breakingNews.length;
                ticker.textContent = breakingNews[currentNewsIndex];
            }
        }, 30000); // Update every 30 seconds
    }
    
    handleContactForm(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = {
            firstName: formData.get('firstName'),
            lastName: formData.get('lastName'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            subject: formData.get('subject'),
            message: formData.get('message')
        };
        
        // Validate required fields
        if (!data.firstName || !data.lastName || !data.email || !data.subject || !data.message) {
            this.showNotification('❌ Please fill in all required fields', 'error');
            return;
        }
        
        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(data.email)) {
            this.showNotification('❌ Please enter a valid email address', 'error');
            return;
        }
        
        // Simulate form submission
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin me-2"></i>Sending...';
        submitBtn.disabled = true;
        
        setTimeout(() => {
            this.showNotification('✅ Message sent successfully! We\'ll get back to you soon.', 'success');
            e.target.reset();
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 2000);
    }
    
    handleNewsAlerts(e) {
        e.preventDefault();
        
        const email = document.getElementById('alertEmail').value;
        const phone = document.getElementById('alertPhone').value;
        
        if (!email) {
            this.showNotification('❌ Email address is required', 'error');
            return;
        }
        
        // Simulate subscription
        setTimeout(() => {
            this.showNotification('✅ Successfully subscribed to news alerts!', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('newsAlertsModal'));
            if (modal) modal.hide();
            
            // Reset form
            e.target.reset();
        }, 1000);
    }
    
    setupFormHandlers() {
        // Handle all form submissions with loading states
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                form.addEventListener('submit', () => {
                    submitBtn.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        submitBtn.style.transform = 'scale(1)';
                    }, 150);
                });
            }
        });
    }
    
    initializeWeatherUpdates() {
        // Simulate weather data updates
        const weatherData = [
            { temp: 28, condition: "Partly Cloudy", icon: "☀️" },
            { temp: 26, condition: "Cloudy", icon: "☁️" },
            { temp: 30, condition: "Sunny", icon: "☀️" },
            { temp: 24, condition: "Light Rain", icon: "🌧️" }
        ];
        
        let currentWeatherIndex = 0;
        
        setInterval(() => {
            const weather = weatherData[currentWeatherIndex];
            const tempElements = document.querySelectorAll('.temperature, .temp-value');
            const conditionElements = document.querySelectorAll('.condition, .condition-text');
            
            tempElements.forEach(el => {
                if (el) el.textContent = weather.temp;
            });
            
            conditionElements.forEach(el => {
                if (el) el.textContent = weather.condition;
            });
            
            currentWeatherIndex = (currentWeatherIndex + 1) % weatherData.length;
        }, 60000); // Update every minute
    }
    
    initializeTrafficUpdates() {
        // Simulate traffic status updates
        const routes = [
            { name: "EDSA Extension", status: ["light", "moderate", "heavy"] },
            { name: "Rizal Avenue", status: ["moderate", "heavy", "light"] },
            { name: "Magsaysay Drive", status: ["light", "light", "moderate"] },
            { name: "Gordon Avenue", status: ["light", "moderate", "light"] }
        ];
        
        setInterval(() => {
            routes.forEach((route, index) => {
                const routeElement = document.querySelector(`.route-item:nth-child(${index + 1}) .route-status`);
                if (routeElement) {
                    const randomStatus = route.status[Math.floor(Math.random() * route.status.length)];
                    routeElement.className = `route-status ${randomStatus}`;
                    routeElement.querySelector('span:last-child').textContent = 
                        randomStatus.charAt(0).toUpperCase() + randomStatus.slice(1);
                }
            });
        }, 45000); // Update every 45 seconds
    }
    
    handleNavbarScroll() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 100) {
            navbar.style.backgroundColor = 'rgba(33, 37, 41, 0.98)';
            navbar.style.backdropFilter = 'blur(10px)';
        } else {
            navbar.style.backgroundColor = '';
            navbar.style.backdropFilter = '';
        }
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            info: '#17a2b8',
            warning: '#ffc107'
        };
        
        notification.innerHTML = `
            <div class="notification-content">
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            color: #333;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-left: 4px solid ${colors[type]};
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            max-width: 400px;
            font-size: 14px;
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


}

// Initialize the application when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.k5NewsRadio = new K5NewsRadio();
});

// Add notification styles
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    .notification-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
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
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .notification-close:hover {
        color: #333;
    }
    
    .spin {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(notificationStyles);

// Function to handle Facebook video rendering
document.addEventListener('DOMContentLoaded', function() {
    if (typeof FB !== 'undefined') {
        setTimeout(function() {
            FB.XFBML.parse();
        }, 1000);
    }
});
