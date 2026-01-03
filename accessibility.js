/**
 * Accessibility Manager - JavaScript terpisah untuk semua halaman
 * 
 * Cara penggunaan:
 * 1. Include file ini di semua halaman
 * 2. Include accessibility.css di semua halaman
 * 3. Tambahkan tombol toggle dengan onclick="window.accessibility.toggleHighContrast()"
 * 4. Semua halaman akan otomatis sinkronisasi
 */

class AccessibilityManager {
    constructor() {
        this.settings = this.loadSettings();
        this.init();
    }

    init() {
        this.applySettings();
        this.setupEventListeners();
        this.setupKeyboardShortcuts();
        this.createNotificationElement();
        this.createAccessibilityToggle();
        
        // Listen for changes from other tabs/windows
        window.addEventListener('storage', (e) => {
            if (e.key === 'accessibilitySettings') {
                this.settings = JSON.parse(e.newValue || '{}');
                this.applySettings();
                this.updateToggleSwitches();
            }
        });

        // Listen for custom events
        window.addEventListener('accessibilityChange', (e) => {
            this.settings = { ...this.settings, ...e.detail };
            this.saveSettings();
            this.applySettings();
            this.updateToggleSwitches();
        });
    }

    loadSettings() {
        try {
            const saved = localStorage.getItem('accessibilitySettings');
            return saved ? JSON.parse(saved) : {
                highContrast: false,
                compactView: false,
                darkMode: false,
                animations: true,
                fontSize: 'medium'
            };
        } catch (error) {
            console.warn('Error loading accessibility settings:', error);
            return {
                highContrast: false,
                compactView: false,
                darkMode: false,
                animations: true,
                fontSize: 'medium'
            };
        }
    }

    saveSettings() {
        try {
            localStorage.setItem('accessibilitySettings', JSON.stringify(this.settings));
            
            // Trigger storage event for other tabs
            window.dispatchEvent(new StorageEvent('storage', {
                key: 'accessibilitySettings',
                newValue: JSON.stringify(this.settings),
                oldValue: null
            }));
        } catch (error) {
            console.warn('Error saving accessibility settings:', error);
        }
    }

    applySettings() {
        const body = document.body;
        
        // Remove all accessibility classes first
        body.classList.remove('high-contrast-mode', 'compact-view', 'dark-mode', 'no-animations');
        
        // Apply high contrast mode
        if (this.settings.highContrast) {
            body.classList.add('high-contrast-mode');
        }

        // Apply compact view
        if (this.settings.compactView) {
            body.classList.add('compact-view');
        }

        // Apply dark mode
        if (this.settings.darkMode) {
            body.classList.add('dark-mode');
        }

        // Apply animations
        if (!this.settings.animations) {
            body.classList.add('no-animations');
        }

        // Apply font size
        this.applyFontSize();
    }

    applyFontSize() {
        const root = document.documentElement;
        const fontSizes = {
            'small': '14px',
            'medium': '16px',
            'large': '18px',
            'extra-large': '20px'
        };
        
        root.style.fontSize = fontSizes[this.settings.fontSize] || '16px';
    }

    setupEventListeners() {
        // Auto-update when settings change
        this.observeSettingsChange();
    }

    observeSettingsChange() {
        // Create a MutationObserver to detect changes
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        this.updateSettingsFromBody();
                    }
                });
            });

            observer.observe(document.body, {
                attributes: true,
                attributeFilter: ['class']
            });
        }
    }

    updateSettingsFromBody() {
        const body = document.body;
        const newSettings = {
            ...this.settings,
            highContrast: body.classList.contains('high-contrast-mode'),
            compactView: body.classList.contains('compact-view'),
            darkMode: body.classList.contains('dark-mode'),
            animations: !body.classList.contains('no-animations')
        };

        if (JSON.stringify(newSettings) !== JSON.stringify(this.settings)) {
            this.settings = newSettings;
            this.saveSettings();
        }
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + Shift + H: Toggle High Contrast
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'H') {
                e.preventDefault();
                this.toggleHighContrast();
            }

            // Ctrl/Cmd + Shift + C: Toggle Compact View
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'C') {
                e.preventDefault();
                this.toggleCompactView();
            }

            // Ctrl/Cmd + Shift + D: Toggle Dark Mode
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                this.toggleDarkMode();
            }

            // Ctrl/Cmd + Shift + A: Toggle Animations
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'A') {
                e.preventDefault();
                this.toggleAnimations();
            }

            // Ctrl/Cmd + Shift + Plus: Increase Font Size
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === '+') {
                e.preventDefault();
                this.increaseFontSize();
            }

            // Ctrl/Cmd + Shift + Minus: Decrease Font Size
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === '-') {
                e.preventDefault();
                this.decreaseFontSize();
            }
        });
    }

    createNotificationElement() {
        if (document.getElementById('accessibility-notification')) {
            return;
        }

        const notification = document.createElement('div');
        notification.id = 'accessibility-notification';
        notification.className = 'accessibility-notification';
        notification.innerHTML = `
            <div class="accessibility-notification-title" id="accessibility-notification-title"></div>
            <div class="accessibility-notification-message" id="accessibility-notification-message"></div>
        `;
        document.body.appendChild(notification);
    }

    createAccessibilityToggle() {
        if (document.getElementById('accessibility-toggle')) {
            return;
        }

        const toggle = document.createElement('div');
        toggle.id = 'accessibility-toggle';
        toggle.className = 'accessibility-toggle';
        toggle.innerHTML = `
            <h4>Aksesibilitas</h4>
            <div class="toggle-item">
                <span class="toggle-label">Kontras Tinggi</span>
                <div class="toggle-switch" id="high-contrast-toggle" onclick="window.accessibility.toggleHighContrast()"></div>
            </div>
            <div class="toggle-item">
                <span class="toggle-label">Tampilan Ringkas</span>
                <div class="toggle-switch" id="compact-view-toggle" onclick="window.accessibility.toggleCompactView()"></div>
            </div>
            <div class="toggle-item">
                <span class="toggle-label">Mode Gelap</span>
                <div class="toggle-switch" id="dark-mode-toggle" onclick="window.accessibility.toggleDarkMode()"></div>
            </div>
            <div class="toggle-item">
                <span class="toggle-label">Animasi</span>
                <div class="toggle-switch" id="animations-toggle" onclick="window.accessibility.toggleAnimations()"></div>
            </div>
        `;
        document.body.appendChild(toggle);
        
        this.updateToggleSwitches();
    }

    updateToggleSwitches() {
        const toggles = {
            'high-contrast-toggle': this.settings.highContrast,
            'compact-view-toggle': this.settings.compactView,
            'dark-mode-toggle': this.settings.darkMode,
            'animations-toggle': this.settings.animations
        };

        Object.entries(toggles).forEach(([id, isActive]) => {
            const toggle = document.getElementById(id);
            if (toggle) {
                toggle.classList.toggle('active', isActive);
            }
        });
    }

    showNotification(title, message) {
        const notification = document.getElementById('accessibility-notification');
        const titleElement = document.getElementById('accessibility-notification-title');
        const messageElement = document.getElementById('accessibility-notification-message');

        if (titleElement) titleElement.textContent = title;
        if (messageElement) messageElement.textContent = message;

        if (notification) {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
    }

    // Toggle functions
    toggleHighContrast() {
        this.settings.highContrast = !this.settings.highContrast;
        this.saveSettings();
        this.applySettings();
        this.updateToggleSwitches();
        this.showNotification('Mode Kontras Tinggi', this.settings.highContrast ? 'Diaktifkan' : 'Dinonaktifkan');
    }

    toggleCompactView() {
        this.settings.compactView = !this.settings.compactView;
        this.saveSettings();
        this.applySettings();
        this.updateToggleSwitches();
        this.showNotification('Tampilan Ringkas', this.settings.compactView ? 'Diaktifkan' : 'Dinonaktifkan');
    }

    toggleDarkMode() {
        this.settings.darkMode = !this.settings.darkMode;
        this.saveSettings();
        this.applySettings();
        this.updateToggleSwitches();
        this.showNotification('Mode Gelap', this.settings.darkMode ? 'Diaktifkan' : 'Dinonaktifkan');
    }

    toggleAnimations() {
        this.settings.animations = !this.settings.animations;
        this.saveSettings();
        this.applySettings();
        this.updateToggleSwitches();
        this.showNotification('Animasi', this.settings.animations ? 'Diaktifkan' : 'Dinonaktifkan');
    }

    // Font size functions
    increaseFontSize() {
        const sizes = ['small', 'medium', 'large', 'extra-large'];
        const currentIndex = sizes.indexOf(this.settings.fontSize);
        const nextIndex = Math.min(currentIndex + 1, sizes.length - 1);
        this.settings.fontSize = sizes[nextIndex];
        this.saveSettings();
        this.applySettings();
        this.showNotification('Ukuran Font', `Diubah ke ${this.settings.fontSize}`);
    }

    decreaseFontSize() {
        const sizes = ['small', 'medium', 'large', 'extra-large'];
        const currentIndex = sizes.indexOf(this.settings.fontSize);
        const prevIndex = Math.max(currentIndex - 1, 0);
        this.settings.fontSize = sizes[prevIndex];
        this.saveSettings();
        this.applySettings();
        this.showNotification('Ukuran Font', `Diubah ke ${this.settings.fontSize}`);
    }

    // Reset function
    resetSettings() {
        this.settings = {
            highContrast: false,
            compactView: false,
            darkMode: false,
            animations: true,
            fontSize: 'medium'
        };
        this.saveSettings();
        this.applySettings();
        this.updateToggleSwitches();
        this.showNotification('Pengaturan', 'Semua pengaturan telah direset ke default');
    }

    // Get current settings
    getSettings() {
        return { ...this.settings };
    }

    // Set specific setting
    setSetting(key, value) {
        if (this.settings.hasOwnProperty(key)) {
            this.settings[key] = value;
            this.saveSettings();
            this.applySettings();
            this.updateToggleSwitches();
        }
    }
}

// Initialize and make globally available
let accessibilityManager;

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        accessibilityManager = new AccessibilityManager();
        window.accessibility = accessibilityManager;
    });
} else {
    accessibilityManager = new AccessibilityManager();
    window.accessibility = accessibilityManager;
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AccessibilityManager;
}

// Global functions for backward compatibility
window.toggleHighContrast = function() {
    if (window.accessibility) {
        window.accessibility.toggleHighContrast();
    }
};

window.toggleCompactView = function() {
    if (window.accessibility) {
        window.accessibility.toggleCompactView();
    }
};

window.toggleDarkMode = function() {
    if (window.accessibility) {
        window.accessibility.toggleDarkMode();
    }
};

window.toggleAnimations = function() {
    if (window.accessibility) {
        window.accessibility.toggleAnimations();
    }
};

window.resetAccessibilitySettings = function() {
    if (window.accessibility) {
        window.accessibility.resetSettings();
    }
};