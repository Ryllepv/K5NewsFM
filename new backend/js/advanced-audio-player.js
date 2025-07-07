// Advanced Audio Player with Real-time Visualizations
class AdvancedAudioPlayer {
    constructor(container) {
        this.container = container;
        this.audioContext = null;
        this.analyser = null;
        this.source = null;
        this.gainNode = null;
        this.isPlaying = false;
        this.currentFrequencyData = null;
        this.visualizationMode = 'bars';
        this.theme = 'neon';
        
        this.init();
    }
    
    init() {
        this.createPlayerInterface();
        this.setupEventListeners();
        this.initializeAudioContext();
        this.startVisualizationLoop();
    }
    
    createPlayerInterface() {
        this.container.innerHTML = `
            <div class="advanced-audio-player">
                <div class="player-header">
                    <div class="station-info">
                        <h3 class="station-name">K5 News FM</h3>
                        <p class="station-tagline">Live • 24/7</p>
                    </div>
                    <div class="listener-count">
                        <span class="count-number">1,247</span>
                        <span class="count-label">listeners</span>
                    </div>
                </div>
                
                <div class="visualization-container">
                    <canvas id="audioVisualization" width="800" height="200"></canvas>
                    <div class="visualization-controls">
                        <button class="viz-btn active" data-mode="bars">Bars</button>
                        <button class="viz-btn" data-mode="wave">Wave</button>
                        <button class="viz-btn" data-mode="circular">Circular</button>
                        <button class="viz-btn" data-mode="particles">Particles</button>
                    </div>
                </div>
                
                <div class="track-info">
                    <div class="track-artwork">
                        <img src="https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=100&h=100&fit=crop" alt="Now Playing" class="artwork-image">
                        <div class="artwork-overlay">
                            <div class="pulse-ring"></div>
                        </div>
                    </div>
                    <div class="track-details">
                        <h4 class="track-title">Bohemian Rhapsody</h4>
                        <p class="track-artist">Queen</p>
                        <div class="track-meta">
                            <span class="track-time">3:42</span>
                            <span class="track-genre">Classic Rock</span>
                        </div>
                    </div>
                </div>
                
                <div class="player-controls">
                    <button class="control-btn secondary" id="prevBtn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/>
                        </svg>
                    </button>
                    
                    <button class="control-btn primary play-pause" id="playPauseBtn">
                        <svg class="play-icon" width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        <svg class="pause-icon hidden" width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                        </svg>
                    </button>
                    
                    <button class="control-btn secondary" id="nextBtn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/>
                        </svg>
                    </button>
                </div>
                
                <div class="volume-control">
                    <button class="volume-btn" id="volumeBtn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                        </svg>
                    </button>
                    <div class="volume-slider-container">
                        <input type="range" class="volume-slider" id="volumeSlider" min="0" max="100" value="75">
                        <div class="volume-fill"></div>
                    </div>
                </div>
                
                <div class="equalizer-container">
                    <button class="eq-toggle" id="eqToggle">EQ</button>
                    <div class="equalizer hidden" id="equalizer">
                        <div class="eq-band">
                            <label>60Hz</label>
                            <input type="range" class="eq-slider" data-frequency="60" min="-12" max="12" value="0">
                        </div>
                        <div class="eq-band">
                            <label>170Hz</label>
                            <input type="range" class="eq-slider" data-frequency="170" min="-12" max="12" value="0">
                        </div>
                        <div class="eq-band">
                            <label>350Hz</label>
                            <input type="range" class="eq-slider" data-frequency="350" min="-12" max="12" value="0">
                        </div>
                        <div class="eq-band">
                            <label>1kHz</label>
                            <input type="range" class="eq-slider" data-frequency="1000" min="-12" max="12" value="0">
                        </div>
                        <div class="eq-band">
                            <label>3.5kHz</label>
                            <input type="range" class="eq-slider" data-frequency="3500" min="-12" max="12" value="0">
                        </div>
                        <div class="eq-band">
                            <label>10kHz</label>
                            <input type="range" class="eq-slider" data-frequency="10000" min="-12" max="12" value="0">
                        </div>
                    </div>
                </div>
                
                <audio id="audioElement" crossorigin="anonymous"></audio>
            </div>
        `;
        
        this.canvas = this.container.querySelector('#audioVisualization');
        this.ctx = this.canvas.getContext('2d');
        this.audioElement = this.container.querySelector('#audioElement');
        
        this.resizeCanvas();
        window.addEventListener('resize', () => this.resizeCanvas());
    }
    
    setupEventListeners() {
        // Play/Pause button
        const playPauseBtn = this.container.querySelector('#playPauseBtn');
        playPauseBtn.addEventListener('click', () => this.togglePlayback());
        
        // Volume control
        const volumeSlider = this.container.querySelector('#volumeSlider');
        volumeSlider.addEventListener('input', (e) => this.setVolume(e.target.value / 100));
        
        // Visualization mode buttons
        const vizBtns = this.container.querySelectorAll('.viz-btn');
        vizBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                vizBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.visualizationMode = btn.dataset.mode;
            });
        });
        
        // Equalizer toggle
        const eqToggle = this.container.querySelector('#eqToggle');
        const equalizer = this.container.querySelector('#equalizer');
        eqToggle.addEventListener('click', () => {
            equalizer.classList.toggle('hidden');
            eqToggle.classList.toggle('active');
        });
        
        // Equalizer sliders
        const eqSliders = this.container.querySelectorAll('.eq-slider');
        eqSliders.forEach(slider => {
            slider.addEventListener('input', (e) => {
                this.adjustEqualizer(e.target.dataset.frequency, e.target.value);
            });
        });
        
        // Volume slider visual feedback
        volumeSlider.addEventListener('input', (e) => {
            const fill = this.container.querySelector('.volume-fill');
            fill.style.width = `${e.target.value}%`;
        });
    }
    
    async initializeAudioContext() {
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Create audio nodes
            this.analyser = this.audioContext.createAnalyser();
            this.analyser.fftSize = 2048;
            this.analyser.smoothingTimeConstant = 0.8;
            
            this.gainNode = this.audioContext.createGain();
            
            // Create equalizer bands
            this.createEqualizer();
            
            this.currentFrequencyData = new Uint8Array(this.analyser.frequencyBinCount);
            this.currentTimeDomainData = new Uint8Array(this.analyser.fftSize);
            
        } catch (error) {
            console.error('Failed to initialize audio context:', error);
        }
    }
    
    createEqualizer() {
        this.eqBands = [];
        const frequencies = [60, 170, 350, 1000, 3500, 10000];
        
        frequencies.forEach(freq => {
            const filter = this.audioContext.createBiquadFilter();
            filter.type = 'peaking';
            filter.frequency.value = freq;
            filter.Q.value = 1;
            filter.gain.value = 0;
            this.eqBands.push(filter);
        });
        
        // Connect equalizer chain
        let previousNode = this.gainNode;
        this.eqBands.forEach(band => {
            previousNode.connect(band);
            previousNode = band;
        });
        
        previousNode.connect(this.analyser);
        this.analyser.connect(this.audioContext.destination);
    }
    
    adjustEqualizer(frequency, gain) {
        const band = this.eqBands.find(b => b.frequency.value == frequency);
        if (band) {
            band.gain.value = parseFloat(gain);
        }
    }
    
    async togglePlayback() {
        if (this.isPlaying) {
            this.pause();
        } else {
            await this.play();
        }
    }
    
    async play() {
        try {
            if (this.audioContext.state === 'suspended') {
                await this.audioContext.resume();
            }
            
            if (!this.source) {
                this.source = this.audioContext.createMediaElementSource(this.audioElement);
                this.source.connect(this.gainNode);
            }
            
            // Set stream URL (replace with actual stream)
            this.audioElement.src = 'https://example.com/stream'; // Replace with actual stream
            await this.audioElement.play();
            
            this.isPlaying = true;
            this.updatePlayButton();
            this.startPulseAnimation();
            
        } catch (error) {
            console.error('Playback failed:', error);
        }
    }
    
    pause() {
        this.audioElement.pause();
        this.isPlaying = false;
        this.updatePlayButton();
        this.stopPulseAnimation();
    }
    
    setVolume(volume) {
        if (this.gainNode) {
            this.gainNode.gain.value = volume;
        }
        this.audioElement.volume = volume;
    }
    
    updatePlayButton() {
        const playIcon = this.container.querySelector('.play-icon');
        const pauseIcon = this.container.querySelector('.pause-icon');
        
        if (this.isPlaying) {
            playIcon.classList.add('hidden');
            pauseIcon.classList.remove('hidden');
        } else {
            playIcon.classList.remove('hidden');
            pauseIcon.classList.add('hidden');
        }
    }
    
    startPulseAnimation() {
        const pulseRing = this.container.querySelector('.pulse-ring');
        pulseRing.style.animation = 'pulse 2s infinite';
    }
    
    stopPulseAnimation() {
        const pulseRing = this.container.querySelector('.pulse-ring');
        pulseRing.style.animation = 'none';
    }
    
    startVisualizationLoop() {
        const animate = () => {
            if (this.analyser && this.isPlaying) {
                this.analyser.getByteFrequencyData(this.currentFrequencyData);
                this.analyser.getByteTimeDomainData(this.currentTimeDomainData);
                this.drawVisualization();
            } else {
                this.drawIdleVisualization();
            }
            requestAnimationFrame(animate);
        };
        animate();
    }
    
    drawVisualization() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        switch (this.visualizationMode) {
            case 'bars':
                this.drawBars();
                break;
            case 'wave':
                this.drawWave();
                break;
            case 'circular':
                this.drawCircular();
                break;
            case 'particles':
                this.drawParticles();
                break;
        }
    }
    
    drawBars() {
        const barWidth = this.canvas.width / this.currentFrequencyData.length * 2;
        let x = 0;
        
        for (let i = 0; i < this.currentFrequencyData.length; i++) {
            const barHeight = (this.currentFrequencyData[i] / 255) * this.canvas.height;
            
            const gradient = this.ctx.createLinearGradient(0, this.canvas.height, 0, this.canvas.height - barHeight);
            gradient.addColorStop(0, `hsl(${i * 2}, 70%, 50%)`);
            gradient.addColorStop(1, `hsl(${i * 2 + 60}, 70%, 70%)`);
            
            this.ctx.fillStyle = gradient;
            this.ctx.fillRect(x, this.canvas.height - barHeight, barWidth, barHeight);
            
            x += barWidth + 1;
        }
    }
    
    drawWave() {
        this.ctx.lineWidth = 2;
        this.ctx.strokeStyle = '#0d6efd';
        this.ctx.beginPath();
        
        const sliceWidth = this.canvas.width / this.currentTimeDomainData.length;
        let x = 0;
        
        for (let i = 0; i < this.currentTimeDomainData.length; i++) {
            const v = this.currentTimeDomainData[i] / 128.0;
            const y = v * this.canvas.height / 2;
            
            if (i === 0) {
                this.ctx.moveTo(x, y);
            } else {
                this.ctx.lineTo(x, y);
            }
            
            x += sliceWidth;
        }
        
        this.ctx.stroke();
    }
    
    drawCircular() {
        const centerX = this.canvas.width / 2;
        const centerY = this.canvas.height / 2;
        const radius = Math.min(centerX, centerY) - 20;
        
        for (let i = 0; i < this.currentFrequencyData.length; i++) {
            const angle = (i / this.currentFrequencyData.length) * Math.PI * 2;
            const barHeight = (this.currentFrequencyData[i] / 255) * radius * 0.5;
            
            const x1 = centerX + Math.cos(angle) * radius;
            const y1 = centerY + Math.sin(angle) * radius;
            const x2 = centerX + Math.cos(angle) * (radius + barHeight);
            const y2 = centerY + Math.sin(angle) * (radius + barHeight);
            
            this.ctx.strokeStyle = `hsl(${i * 2}, 70%, 60%)`;
            this.ctx.lineWidth = 2;
            this.ctx.beginPath();
            this.ctx.moveTo(x1, y1);
            this.ctx.lineTo(x2, y2);
            this.ctx.stroke();
        }
    }
    
    drawParticles() {
        // Simplified particle visualization
        for (let i = 0; i < this.currentFrequencyData.length; i += 10) {
            const intensity = this.currentFrequencyData[i] / 255;
            if (intensity > 0.5) {
                const x = Math.random() * this.canvas.width;
                const y = Math.random() * this.canvas.height;
                const size = intensity * 10;
                
                this.ctx.fillStyle = `hsla(${i * 2}, 70%, 60%, ${intensity})`;
                this.ctx.beginPath();
                this.ctx.arc(x, y, size, 0, Math.PI * 2);
                this.ctx.fill();
            }
        }
    }
    
    drawIdleVisualization() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Draw subtle idle animation
        const time = Date.now() * 0.001;
        const centerX = this.canvas.width / 2;
        const centerY = this.canvas.height / 2;
        
        for (let i = 0; i < 50; i++) {
            const angle = (i / 50) * Math.PI * 2 + time;
            const radius = 30 + Math.sin(time + i * 0.1) * 10;
            const x = centerX + Math.cos(angle) * radius;
            const y = centerY + Math.sin(angle) * radius;
            
            this.ctx.fillStyle = `hsla(${i * 7}, 50%, 60%, 0.3)`;
            this.ctx.beginPath();
            this.ctx.arc(x, y, 2, 0, Math.PI * 2);
            this.ctx.fill();
        }
    }
    
    resizeCanvas() {
        const rect = this.canvas.getBoundingClientRect();
        this.canvas.width = rect.width * window.devicePixelRatio;
        this.canvas.height = rect.height * window.devicePixelRatio;
        this.ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const playerContainer = document.querySelector('#advanced-audio-player');
    if (playerContainer) {
        new AdvancedAudioPlayer(playerContainer);
    }
});
