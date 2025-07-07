// Advanced Web Components for K5 News FM
// Custom elements using modern Web Components API

// Advanced Audio Player Component
class K5AudioPlayer extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this.isPlaying = false;
        this.currentTime = 0;
        this.duration = 0;
        this.volume = 0.5;
    }
    
    connectedCallback() {
        this.render();
        this.setupEventListeners();
        this.initializeAudioContext();
    }
    
    render() {
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: block;
                    background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
                    border-radius: 15px;
                    padding: 20px;
                    color: white;
                    font-family: 'Segoe UI', sans-serif;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                }
                
                .player-container {
                    display: grid;
                    grid-template-columns: auto 1fr auto;
                    gap: 20px;
                    align-items: center;
                }
                
                .play-button {
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    border: none;
                    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
                    color: white;
                    font-size: 24px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .play-button:hover {
                    transform: scale(1.05);
                    box-shadow: 0 4px 20px rgba(255, 107, 107, 0.4);
                }
                
                .player-info {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }
                
                .track-info {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }
                
                .track-title {
                    font-size: 16px;
                    font-weight: 600;
                    margin: 0;
                }
                
                .track-artist {
                    font-size: 14px;
                    opacity: 0.8;
                    margin: 0;
                }
                
                .progress-container {
                    position: relative;
                    height: 6px;
                    background: rgba(255, 255, 255, 0.2);
                    border-radius: 3px;
                    cursor: pointer;
                }
                
                .progress-bar {
                    height: 100%;
                    background: linear-gradient(90deg, #ff6b6b, #ee5a24);
                    border-radius: 3px;
                    width: 0%;
                    transition: width 0.1s ease;
                }
                
                .time-display {
                    display: flex;
                    justify-content: space-between;
                    font-size: 12px;
                    opacity: 0.7;
                    margin-top: 5px;
                }
                
                .controls {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    align-items: center;
                }
                
                .volume-control {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                
                .volume-slider {
                    width: 80px;
                    height: 4px;
                    background: rgba(255, 255, 255, 0.2);
                    border-radius: 2px;
                    outline: none;
                    cursor: pointer;
                }
                
                .visualizer {
                    width: 100px;
                    height: 40px;
                    display: flex;
                    align-items: end;
                    gap: 2px;
                    justify-content: center;
                }
                
                .visualizer-bar {
                    width: 3px;
                    background: linear-gradient(to top, #ff6b6b, #ee5a24);
                    border-radius: 1px;
                    transition: height 0.1s ease;
                }
                
                @media (max-width: 768px) {
                    .player-container {
                        grid-template-columns: 1fr;
                        text-align: center;
                    }
                }
            </style>
            
            <div class="player-container">
                <button class="play-button" id="playBtn">
                    <span id="playIcon">▶</span>
                </button>
                
                <div class="player-info">
                    <div class="track-info">
                        <h3 class="track-title" id="trackTitle">K5 News FM Live</h3>
                        <p class="track-artist" id="trackArtist">Now Playing</p>
                    </div>
                    
                    <div class="progress-container" id="progressContainer">
                        <div class="progress-bar" id="progressBar"></div>
                    </div>
                    
                    <div class="time-display">
                        <span id="currentTime">0:00</span>
                        <span id="duration">∞</span>
                    </div>
                </div>
                
                <div class="controls">
                    <div class="volume-control">
                        <span>🔊</span>
                        <input type="range" class="volume-slider" id="volumeSlider" 
                               min="0" max="100" value="50">
                    </div>
                    
                    <div class="visualizer" id="visualizer">
                        ${Array.from({length: 20}, () => '<div class="visualizer-bar"></div>').join('')}
                    </div>
                </div>
            </div>
            
            <audio id="audioElement" preload="none"></audio>
        `;
    }
    
    setupEventListeners() {
        const playBtn = this.shadowRoot.getElementById('playBtn');
        const volumeSlider = this.shadowRoot.getElementById('volumeSlider');
        const progressContainer = this.shadowRoot.getElementById('progressContainer');
        const audioElement = this.shadowRoot.getElementById('audioElement');
        
        playBtn.addEventListener('click', () => this.togglePlayback());
        volumeSlider.addEventListener('input', (e) => this.setVolume(e.target.value / 100));
        progressContainer.addEventListener('click', (e) => this.seek(e));
        
        audioElement.addEventListener('loadstart', () => this.onLoadStart());
        audioElement.addEventListener('canplay', () => this.onCanPlay());
        audioElement.addEventListener('timeupdate', () => this.onTimeUpdate());
        audioElement.addEventListener('ended', () => this.onEnded());
    }
    
    initializeAudioContext() {
        if (window.AudioContext || window.webkitAudioContext) {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            this.setupAudioAnalyzer();
        }
    }
    
    setupAudioAnalyzer() {
        const audioElement = this.shadowRoot.getElementById('audioElement');
        this.source = this.audioContext.createMediaElementSource(audioElement);
        this.analyzer = this.audioContext.createAnalyser();
        this.analyzer.fftSize = 64;
        
        this.source.connect(this.analyzer);
        this.analyzer.connect(this.audioContext.destination);
        
        this.dataArray = new Uint8Array(this.analyzer.frequencyBinCount);
        this.startVisualization();
    }
    
    startVisualization() {
        const visualizerBars = this.shadowRoot.querySelectorAll('.visualizer-bar');
        
        const animate = () => {
            if (this.isPlaying) {
                this.analyzer.getByteFrequencyData(this.dataArray);
                
                visualizerBars.forEach((bar, index) => {
                    const value = this.dataArray[index] || 0;
                    const height = (value / 255) * 40;
                    bar.style.height = `${Math.max(2, height)}px`;
                });
            }
            
            requestAnimationFrame(animate);
        };
        
        animate();
    }
    
    togglePlayback() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    }
    
    async play() {
        const audioElement = this.shadowRoot.getElementById('audioElement');
        const playIcon = this.shadowRoot.getElementById('playIcon');
        
        if (!audioElement.src) {
            audioElement.src = this.getAttribute('src') || 'https://example.com/stream';
        }
        
        try {
            if (this.audioContext && this.audioContext.state === 'suspended') {
                await this.audioContext.resume();
            }
            
            await audioElement.play();
            this.isPlaying = true;
            playIcon.textContent = '⏸';
            
            this.dispatchEvent(new CustomEvent('play', {
                detail: { src: audioElement.src }
            }));
        } catch (error) {
            console.error('Playback failed:', error);
            this.dispatchEvent(new CustomEvent('error', {
                detail: { error: error.message }
            }));
        }
    }
    
    pause() {
        const audioElement = this.shadowRoot.getElementById('audioElement');
        const playIcon = this.shadowRoot.getElementById('playIcon');
        
        audioElement.pause();
        this.isPlaying = false;
        playIcon.textContent = '▶';
        
        this.dispatchEvent(new CustomEvent('pause'));
    }
    
    setVolume(volume) {
        const audioElement = this.shadowRoot.getElementById('audioElement');
        audioElement.volume = volume;
        this.volume = volume;
        
        this.dispatchEvent(new CustomEvent('volumechange', {
            detail: { volume }
        }));
    }
    
    seek(event) {
        const audioElement = this.shadowRoot.getElementById('audioElement');
        if (audioElement.duration) {
            const rect = event.currentTarget.getBoundingClientRect();
            const percent = (event.clientX - rect.left) / rect.width;
            audioElement.currentTime = percent * audioElement.duration;
        }
    }
    
    onTimeUpdate() {
        const audioElement = this.shadowRoot.getElementById('audioElement');
        const progressBar = this.shadowRoot.getElementById('progressBar');
        const currentTimeEl = this.shadowRoot.getElementById('currentTime');
        
        if (audioElement.duration) {
            const percent = (audioElement.currentTime / audioElement.duration) * 100;
            progressBar.style.width = `${percent}%`;
            currentTimeEl.textContent = this.formatTime(audioElement.currentTime);
        }
    }
    
    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
    
    // Public API
    updateTrackInfo(title, artist) {
        this.shadowRoot.getElementById('trackTitle').textContent = title;
        this.shadowRoot.getElementById('trackArtist').textContent = artist;
    }
    
    static get observedAttributes() {
        return ['src', 'title', 'artist'];
    }
    
    attributeChangedCallback(name, oldValue, newValue) {
        if (name === 'src') {
            const audioElement = this.shadowRoot.getElementById('audioElement');
            if (audioElement) audioElement.src = newValue;
        } else if (name === 'title' || name === 'artist') {
            this.updateTrackInfo(
                this.getAttribute('title') || 'K5 News FM Live',
                this.getAttribute('artist') || 'Now Playing'
            );
        }
    }
}

// Advanced Toast Notification Component
class K5Toast extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
    }
    
    connectedCallback() {
        this.render();
        this.show();
    }
    
    render() {
        const type = this.getAttribute('type') || 'info';
        const message = this.getAttribute('message') || '';
        const duration = parseInt(this.getAttribute('duration')) || 3000;
        
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    max-width: 400px;
                    animation: slideIn 0.3s ease-out;
                }
                
                .toast {
                    background: white;
                    border-radius: 8px;
                    padding: 16px 20px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                    border-left: 4px solid var(--accent-color);
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    position: relative;
                    overflow: hidden;
                }
                
                .toast.success {
                    --accent-color: #28a745;
                }
                
                .toast.error {
                    --accent-color: #dc3545;
                }
                
                .toast.warning {
                    --accent-color: #ffc107;
                }
                
                .toast.info {
                    --accent-color: #17a2b8;
                }
                
                .icon {
                    font-size: 20px;
                    color: var(--accent-color);
                }
                
                .message {
                    flex: 1;
                    font-size: 14px;
                    color: #333;
                }
                
                .close-btn {
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
                
                .progress {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    height: 3px;
                    background: var(--accent-color);
                    animation: progress ${duration}ms linear;
                }
                
                @keyframes slideIn {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @keyframes slideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
                
                @keyframes progress {
                    from { width: 100%; }
                    to { width: 0%; }
                }
            </style>
            
            <div class="toast ${type}">
                <div class="icon">${this.getIcon(type)}</div>
                <div class="message">${message}</div>
                <button class="close-btn" onclick="this.getRootNode().host.hide()">×</button>
                <div class="progress"></div>
            </div>
        `;
        
        // Auto-hide after duration
        setTimeout(() => this.hide(), duration);
    }
    
    getIcon(type) {
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        return icons[type] || icons.info;
    }
    
    show() {
        this.style.display = 'block';
    }
    
    hide() {
        this.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => this.remove(), 300);
    }
}

// Advanced Modal Component
class K5Modal extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
    }
    
    connectedCallback() {
        this.render();
        this.setupEventListeners();
    }
    
    render() {
        const title = this.getAttribute('title') || 'Modal';
        const size = this.getAttribute('size') || 'medium';
        
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    opacity: 0;
                    animation: fadeIn 0.3s ease-out forwards;
                }
                
                .modal {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    max-height: 90vh;
                    overflow: hidden;
                    transform: scale(0.9);
                    animation: scaleIn 0.3s ease-out forwards;
                }
                
                .modal.small { width: 400px; }
                .modal.medium { width: 600px; }
                .modal.large { width: 800px; }
                .modal.fullscreen { width: 95vw; height: 95vh; }
                
                .modal-header {
                    padding: 20px 24px;
                    border-bottom: 1px solid #eee;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }
                
                .modal-title {
                    margin: 0;
                    font-size: 18px;
                    font-weight: 600;
                    color: #333;
                }
                
                .close-btn {
                    background: none;
                    border: none;
                    font-size: 24px;
                    color: #999;
                    cursor: pointer;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    transition: background-color 0.2s;
                }
                
                .close-btn:hover {
                    background: #f5f5f5;
                }
                
                .modal-body {
                    padding: 24px;
                    max-height: calc(90vh - 140px);
                    overflow-y: auto;
                }
                
                .modal-footer {
                    padding: 16px 24px;
                    border-top: 1px solid #eee;
                    display: flex;
                    justify-content: flex-end;
                    gap: 12px;
                }
                
                @keyframes fadeIn {
                    to { opacity: 1; }
                }
                
                @keyframes scaleIn {
                    to { transform: scale(1); }
                }
                
                @media (max-width: 768px) {
                    .modal {
                        width: 95vw !important;
                        margin: 20px;
                    }
                }
            </style>
            
            <div class="modal ${size}" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h2 class="modal-title">${title}</h2>
                    <button class="close-btn" onclick="this.getRootNode().host.close()">×</button>
                </div>
                <div class="modal-body">
                    <slot></slot>
                </div>
                <div class="modal-footer">
                    <slot name="footer"></slot>
                </div>
            </div>
        `;
    }
    
    setupEventListeners() {
        // Close on backdrop click
        this.addEventListener('click', () => this.close());
        
        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.close();
        });
    }
    
    close() {
        this.style.animation = 'fadeIn 0.3s ease-in reverse';
        setTimeout(() => this.remove(), 300);
        
        this.dispatchEvent(new CustomEvent('close'));
    }
    
    static show(content, options = {}) {
        const modal = document.createElement('k5-modal');
        if (options.title) modal.setAttribute('title', options.title);
        if (options.size) modal.setAttribute('size', options.size);
        
        modal.innerHTML = content;
        document.body.appendChild(modal);
        
        return modal;
    }
}

// Register all components
customElements.define('k5-audio-player', K5AudioPlayer);
customElements.define('k5-toast', K5Toast);
customElements.define('k5-modal', K5Modal);

// Export for use in other modules
window.K5Components = {
    AudioPlayer: K5AudioPlayer,
    Toast: K5Toast,
    Modal: K5Modal
};
