/**
 * Real-time Progress Bar Component
 * Usage: new ProgressBar(taskId, options)
 */

class ProgressBar {
    constructor(taskId, options = {}) {
        this.taskId = taskId;
        this.options = {
            pollInterval: 500, // Poll every 500ms
            onComplete: null,
            onError: null,
            ...options
        };

        this.overlay = null;
        this.pollTimer = null;
        this.isPolling = false;

        this.init();
    }

    init() {
        // Create overlay HTML
        this.createOverlay();
        // Start polling
        this.startPolling();
    }

    createOverlay() {
        const html = `
            <div class="progress-overlay" id="progress-overlay">
                <div class="progress-modal">
                    <div class="progress-header">
                        <div class="progress-icon">
                            <span id="progress-emoji">🚀</span>
                        </div>
                        <h2 class="progress-title">กำลังแปลงไฟล์</h2>
                        <p class="progress-status" id="progress-status">กำลังเริ่มต้น...</p>
                    </div>
                    
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" id="progress-bar-fill" style="width: 0%"></div>
                    </div>
                    
                    <div class="progress-percentage" id="progress-percentage">0%</div>
                    <p class="progress-message" id="progress-message"></p>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);
        this.overlay = document.getElementById('progress-overlay');

        // Show overlay
        setTimeout(() => {
            this.overlay.classList.add('active');
        }, 100);
    }

    async startPolling() {
        this.isPolling = true;
        this.poll();
    }

    async poll() {
        if (!this.isPolling) return;

        try {
            const response = await fetch(`/api/progress/${this.taskId}/`);
            const data = await response.json();

            if (data.success) {
                this.updateProgress(data.progress);

                // Check if complete or error
                if (data.progress.status === 'Complete') {
                    this.handleComplete(data.progress);
                    return;
                } else if (data.progress.status === 'Error') {
                    this.handleError(data.progress);
                    return;
                }
            }

            // Continue polling
            this.pollTimer = setTimeout(() => this.poll(), this.options.pollInterval);

        } catch (error) {
            console.error('Progress polling error:', error);
            this.handleError({ message: 'เกิดข้อผิดพลาดในการตรวจสอบสถานะ' });
        }
    }

    updateProgress(progress) {
        const { percent, status, message } = progress;

        // Update progress bar
        const fillEl = document.getElementById('progress-bar-fill');
        if (fillEl) fillEl.style.width = `${percent}%`;

        // Update percentage text
        const percentEl = document.getElementById('progress-percentage');
        if (percentEl) percentEl.textContent = `${Math.round(percent)}%`;

        // Update status
        const statusEl = document.getElementById('progress-status');
        if (statusEl) statusEl.textContent = this.getStatusText(status);

        // Update message
        const messageEl = document.getElementById('progress-message');
        if (messageEl) messageEl.textContent = message || '';

        // Update emoji based on progress
        const emojiEl = document.getElementById('progress-emoji');
        if (emojiEl) {
            if (percent < 30) emojiEl.textContent = '🚀';
            else if (percent < 60) emojiEl.textContent = '⚡';
            else if (percent < 90) emojiEl.textContent = '🔥';
            else emojiEl.textContent = '✨';
        }
    }

    getStatusText(status) {
        const statusMap = {
            'Uploading': 'กำลังอัปโหลดไฟล์...',
            'Processing': 'กำลังประมวลผล...',
            'Converting': 'กำลังแปลงไฟล์...',
            'Finalizing': 'กำลังเตรียมไฟล์...',
            'Complete': 'เสร็จสิ้น!',
            'Error': 'เกิดข้อผิดพลาด'
        };
        return statusMap[status] || status;
    }

    handleComplete(progress) {
        this.stopPolling();

        // Update UI to show completion
        const modal = this.overlay.querySelector('.progress-modal');
        modal.innerHTML = `
            <div class="progress-complete">
                <div class="progress-complete-icon">✅</div>
                <h2 class="progress-complete-text">แปลงไฟล์สำเร็จ!</h2>
                <p style="color: #6b7280; margin-bottom: 20px;">${progress.message || 'ไฟล์ของคุณพร้อมแล้ว'}</p>
            </div>
        `;

        // Call callback if provided
        if (this.options.onComplete) {
            this.options.onComplete(progress);
        }

        // Auto close after 2 seconds
        setTimeout(() => {
            this.close();
        }, 2000);
    }

    handleError(progress) {
        this.stopPolling();

        // Update UI to show error
        const modal = this.overlay.querySelector('.progress-modal');
        modal.innerHTML = `
            <div class="progress-error">
                <div class="progress-error-icon">❌</div>
                <h2 class="progress-error-text">เกิดข้อผิดพลาด</h2>
                <p style="color: #6b7280; margin-bottom: 20px;">${progress.message || 'กรุณาลองใหม่อีกครั้ง'}</p>
                <button onclick="location.reload()" class="btn btn-primary">ลองใหม่</button>
            </div>
        `;

        // Call callback if provided
        if (this.options.onError) {
            this.options.onError(progress);
        }
    }

    stopPolling() {
        this.isPolling = false;
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
            this.pollTimer = null;
        }
    }

    close() {
        this.stopPolling();
        if (this.overlay) {
            this.overlay.classList.remove('active');
            setTimeout(() => {
                this.overlay.remove();
            }, 300);
        }
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProgressBar;
}
