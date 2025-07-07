// Advanced K5 News FM JavaScript Features
// Modern ES6+ patterns, Web APIs, and advanced functionality

class K5RadioApp {
    constructor() {
        this.state = new Proxy({
            isPlaying: false,
            currentShow: null,
            volume: 0.5,
            listeners: 0,
            recentTracks: [],
            notifications: [],
            theme: 'light'
        }, {
            set: (target, property, value) => {
                target[property] = value;
                this.notifyStateChange(property, value);
                return true;
            }
        });
        
        this.eventBus = new EventTarget();
        this.components = new Map();
        this.observers = new Map();
        
        this.init();
    }
    
    async init() {
        await this.initDatabase();
        this.initWebComponents();
        this.initIntersectionObservers();
        this.initPerformanceMonitoring();
        this.initAdvancedFeatures();
        this.loadUserPreferences();
    }
    
    // IndexedDB for offline storage
    async initDatabase() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('K5RadioDB', 1);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve();
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Store for user preferences
                if (!db.objectStoreNames.contains('preferences')) {
                    db.createObjectStore('preferences', { keyPath: 'key' });
                }
                
                // Store for offline content
                if (!db.objectStoreNames.contains('content')) {
                    const contentStore = db.createObjectStore('content', { keyPath: 'id' });
                    contentStore.createIndex('type', 'type', { unique: false });
                    contentStore.createIndex('timestamp', 'timestamp', { unique: false });
                }
                
                // Store for analytics
                if (!db.objectStoreNames.contains('analytics')) {
                    const analyticsStore = db.createObjectStore('analytics', { keyPath: 'id', autoIncrement: true });
                    analyticsStore.createIndex('event', 'event', { unique: false });
                    analyticsStore.createIndex('timestamp', 'timestamp', { unique: false });
                }
            };
        });
    }
    
    // Advanced Intersection Observer for performance
    initIntersectionObservers() {
        // Lazy loading with fade-in effect
        const lazyImageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('fade-in');
                    lazyImageObserver.unobserve(img);
                }
            });
        }, { rootMargin: '50px' });
        
        // Animation trigger observer
        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, { threshold: 0.1 });
        
        // Apply observers
        document.querySelectorAll('img[data-src]').forEach(img => {
            lazyImageObserver.observe(img);
        });
        
        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            animationObserver.observe(el);
        });
        
        this.observers.set('lazyImage', lazyImageObserver);
        this.observers.set('animation', animationObserver);
    }
    
    // Performance monitoring
    initPerformanceMonitoring() {
        // Monitor Core Web Vitals
        if ('PerformanceObserver' in window) {
            // Largest Contentful Paint
            new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                const lastEntry = entries[entries.length - 1];
                this.trackAnalytics('performance', {
                    metric: 'LCP',
                    value: lastEntry.startTime,
                    timestamp: Date.now()
                });
            }).observe({ entryTypes: ['largest-contentful-paint'] });
            
            // First Input Delay
            new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                entries.forEach(entry => {
                    this.trackAnalytics('performance', {
                        metric: 'FID',
                        value: entry.processingStart - entry.startTime,
                        timestamp: Date.now()
                    });
                });
            }).observe({ entryTypes: ['first-input'] });
            
            // Cumulative Layout Shift
            let clsValue = 0;
            new PerformanceObserver((entryList) => {
                for (const entry of entryList.getEntries()) {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                    }
                }
                this.trackAnalytics('performance', {
                    metric: 'CLS',
                    value: clsValue,
                    timestamp: Date.now()
                });
            }).observe({ entryTypes: ['layout-shift'] });
        }
    }
    
    // Advanced features initialization
    initAdvancedFeatures() {
        this.initVirtualScrolling();
        this.initAdvancedSearch();
        this.initKeyboardShortcuts();
        this.initGestureSupport();
        this.initVoiceCommands();
    }
    
    // Virtual scrolling for large lists
    initVirtualScrolling() {
        const virtualLists = document.querySelectorAll('.virtual-scroll');
        virtualLists.forEach(list => {
            new VirtualScrollManager(list);
        });
    }
    
    // Advanced search with fuzzy matching
    initAdvancedSearch() {
        const searchInputs = document.querySelectorAll('.advanced-search');
        searchInputs.forEach(input => {
            let searchTimeout;
            input.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performAdvancedSearch(e.target.value);
                }, 300);
            });
        });
    }
    
    performAdvancedSearch(query) {
        if (!query.trim()) return;
        
        // Fuzzy search implementation
        const searchData = this.getAllSearchableContent();
        const results = this.fuzzySearch(query, searchData);
        this.displaySearchResults(results);
    }
    
    fuzzySearch(query, data) {
        const threshold = 0.6;
        return data.filter(item => {
            const score = this.calculateSimilarity(query.toLowerCase(), item.text.toLowerCase());
            return score >= threshold;
        }).sort((a, b) => b.score - a.score);
    }
    
    calculateSimilarity(str1, str2) {
        const longer = str1.length > str2.length ? str1 : str2;
        const shorter = str1.length > str2.length ? str2 : str1;
        
        if (longer.length === 0) return 1.0;
        
        const editDistance = this.levenshteinDistance(longer, shorter);
        return (longer.length - editDistance) / longer.length;
    }
    
    levenshteinDistance(str1, str2) {
        const matrix = Array(str2.length + 1).fill().map(() => Array(str1.length + 1).fill(0));
        
        for (let i = 0; i <= str1.length; i++) matrix[0][i] = i;
        for (let j = 0; j <= str2.length; j++) matrix[j][0] = j;
        
        for (let j = 1; j <= str2.length; j++) {
            for (let i = 1; i <= str1.length; i++) {
                const cost = str1[i - 1] === str2[j - 1] ? 0 : 1;
                matrix[j][i] = Math.min(
                    matrix[j][i - 1] + 1,
                    matrix[j - 1][i] + 1,
                    matrix[j - 1][i - 1] + cost
                );
            }
        }
        
        return matrix[str2.length][str1.length];
    }
    
    // Keyboard shortcuts
    initKeyboardShortcuts() {
        const shortcuts = {
            'Space': () => this.togglePlayback(),
            'ArrowUp': () => this.adjustVolume(0.1),
            'ArrowDown': () => this.adjustVolume(-0.1),
            'KeyM': () => this.toggleMute(),
            'KeyF': () => this.toggleFullscreen(),
            'Escape': () => this.closeModals(),
            'KeyS': (e) => {
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    this.saveUserPreferences();
                }
            }
        };
        
        document.addEventListener('keydown', (e) => {
            const handler = shortcuts[e.code];
            if (handler && !e.target.matches('input, textarea, select')) {
                e.preventDefault();
                handler(e);
            }
        });
    }
    
    // Touch gesture support
    initGestureSupport() {
        let startX, startY, startTime;
        
        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            startTime = Date.now();
        });
        
        document.addEventListener('touchend', (e) => {
            if (!startX || !startY) return;
            
            const endX = e.changedTouches[0].clientX;
            const endY = e.changedTouches[0].clientY;
            const endTime = Date.now();
            
            const deltaX = endX - startX;
            const deltaY = endY - startY;
            const deltaTime = endTime - startTime;
            
            // Swipe detection
            if (Math.abs(deltaX) > 50 && deltaTime < 300) {
                if (deltaX > 0) {
                    this.handleSwipeRight();
                } else {
                    this.handleSwipeLeft();
                }
            }
            
            // Reset
            startX = startY = null;
        });
    }
    
    // Voice commands using Web Speech API
    initVoiceCommands() {
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            
            this.recognition.continuous = false;
            this.recognition.interimResults = false;
            this.recognition.lang = 'en-US';
            
            this.recognition.onresult = (event) => {
                const command = event.results[0][0].transcript.toLowerCase();
                this.processVoiceCommand(command);
            };
            
            // Add voice command button
            this.addVoiceCommandButton();
        }
    }
    
    processVoiceCommand(command) {
        const commands = {
            'play': () => this.startPlayback(),
            'pause': () => this.pausePlayback(),
            'stop': () => this.stopPlayback(),
            'volume up': () => this.adjustVolume(0.2),
            'volume down': () => this.adjustVolume(-0.2),
            'mute': () => this.toggleMute(),
            'next show': () => this.showNextProgram(),
            'show schedule': () => this.scrollToSection('schedule'),
            'contact': () => this.scrollToSection('contact')
        };
        
        for (const [trigger, action] of Object.entries(commands)) {
            if (command.includes(trigger)) {
                action();
                this.showNotification(`Voice command executed: ${trigger}`);
                break;
            }
        }
    }
    
    // State management and notifications
    notifyStateChange(property, value) {
        this.eventBus.dispatchEvent(new CustomEvent('stateChange', {
            detail: { property, value }
        }));
        
        // Persist important state changes
        if (['volume', 'theme'].includes(property)) {
            this.savePreference(property, value);
        }
    }
    
    // Database operations
    async savePreference(key, value) {
        if (!this.db) return;
        
        const transaction = this.db.transaction(['preferences'], 'readwrite');
        const store = transaction.objectStore('preferences');
        await store.put({ key, value, timestamp: Date.now() });
    }
    
    async loadUserPreferences() {
        if (!this.db) return;
        
        const transaction = this.db.transaction(['preferences'], 'readonly');
        const store = transaction.objectStore('preferences');
        
        const preferences = ['volume', 'theme', 'notifications'];
        for (const pref of preferences) {
            try {
                const result = await store.get(pref);
                if (result) {
                    this.state[pref] = result.value;
                }
            } catch (error) {
                console.warn(`Failed to load preference: ${pref}`, error);
            }
        }
    }
    
    async trackAnalytics(event, data) {
        if (!this.db) return;
        
        const transaction = this.db.transaction(['analytics'], 'readwrite');
        const store = transaction.objectStore('analytics');
        await store.add({
            event,
            data,
            timestamp: Date.now(),
            url: window.location.href,
            userAgent: navigator.userAgent
        });
    }
    
    // Utility methods
    showNotification(message, type = 'info') {
        this.eventBus.dispatchEvent(new CustomEvent('notification', {
            detail: { message, type }
        }));
    }
    
    scrollToSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.scrollIntoView({ behavior: 'smooth' });
        }
    }
}

// Virtual Scroll Manager for performance
class VirtualScrollManager {
    constructor(container) {
        this.container = container;
        this.itemHeight = 60; // Default item height
        this.visibleItems = Math.ceil(container.clientHeight / this.itemHeight) + 2;
        this.scrollTop = 0;
        this.totalItems = 0;
        
        this.init();
    }
    
    init() {
        this.container.addEventListener('scroll', this.handleScroll.bind(this));
        this.render();
    }
    
    handleScroll() {
        this.scrollTop = this.container.scrollTop;
        this.render();
    }
    
    render() {
        const startIndex = Math.floor(this.scrollTop / this.itemHeight);
        const endIndex = Math.min(startIndex + this.visibleItems, this.totalItems);
        
        // Render only visible items
        this.renderItems(startIndex, endIndex);
    }
    
    renderItems(start, end) {
        // Implementation depends on data source
        // This is a framework for virtual scrolling
    }
}

// Initialize the advanced app
window.K5App = new K5RadioApp();
