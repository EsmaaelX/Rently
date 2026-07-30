// assets/js/form_recovery.js
// LocalStorage Form Recovery with Debounce, Offline/Online Toast Notifications, and Dynamic Field Restoration

function initFormRecovery(formId, draftKey) {
    const form = document.getElementById(formId);
    if (!form) return;

    // Create Toast/Alert Notification container dynamically if not exists
    let toastContainer = document.getElementById('network-toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'network-toast-container';
        toastContainer.style.position = 'fixed';
        toastContainer.style.bottom = '20px';
        toastContainer.style.right = '20px';
        toastContainer.style.zIndex = '99999';
        toastContainer.style.display = 'flex';
        toastContainer.style.flexDirection = 'column';
        toastContainer.style.gap = '10px';
        document.body.appendChild(toastContainer);
    }

    // Show custom toast message
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type}`;
        toast.style.margin = '0';
        toast.style.padding = '12px 24px';
        toast.style.borderRadius = '10px';
        toast.style.boxShadow = '0 10px 30px rgba(0,0,0,0.15)';
        toast.style.minWidth = '250px';
        toast.innerHTML = message;
        
        toastContainer.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.5s ease';
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }

    // Dynamic banner for restore/discard draft
    function showRestoreBanner(draftData) {
        const banner = document.createElement('div');
        banner.style.background = 'var(--card-bg)';
        banner.style.border = '2px solid var(--primary-color)';
        banner.style.padding = '20px';
        banner.style.borderRadius = '12px';
        banner.style.marginBottom = '20px';
        banner.style.boxShadow = 'var(--shadow-light)';
        banner.style.display = 'flex';
        banner.style.justifyContent = 'space-between';
        banner.style.alignItems = 'center';
        banner.style.flexWrap = 'wrap';
        banner.style.gap = '15px';
        banner.id = 'restoreBanner';

        banner.innerHTML = `
            <div>
                <strong style="color:var(--primary-color);">📝 Unsaved Draft Found</strong>
                <p style="margin: 5px 0 0 0; font-size:13px; color:#718096;">We found a draft of this listing. Would you like to restore it? (Note: images must be re-selected)</p>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="button" id="btnRestoreDraft" class="btn btn-primary" style="padding: 8px 16px; font-size:13px;">Restore</button>
                <button type="button" id="btnDiscardDraft" class="btn" style="padding: 8px 16px; font-size:13px; background:#e2e8f0; color:#4a5568;">Discard</button>
            </div>
        `;

        form.parentNode.insertBefore(banner, form);

        document.getElementById('btnRestoreDraft').addEventListener('click', () => {
            restoreDraft(draftData);
            banner.remove();
            showToast('Draft restored successfully!', 'success');
        });

        document.getElementById('btnDiscardDraft').addEventListener('click', () => {
            localStorage.removeItem(draftKey);
            banner.remove();
            showToast('Draft discarded.', 'info');
        });
    }

    // Check online/offline status
    window.addEventListener('online', () => {
        showToast('🟢 Connection restored. Saving online.', 'success');
    });
    window.addEventListener('offline', () => {
        showToast('🔴 Offline. Changes will be saved locally.', 'error');
    });

    if (!navigator.onLine) {
        showToast('🔴 You are currently offline. Working in offline mode.', 'error');
    }

    // Debounce function
    let timeout;
    function debounceSave() {
        clearTimeout(timeout);
        timeout = setTimeout(saveDraft, 500); // 500ms delay
    }

    // Save Form to LocalStorage
    function saveDraft() {
        const formData = {};
        const elements = form.querySelectorAll('input, select, textarea');
        
        elements.forEach(el => {
            if (el.name && el.type !== 'password' && el.type !== 'file' && el.name !== 'csrf_token' && el.name !== 'submit' && el.name !== 'update') {
                if (el.type === 'checkbox') {
                    formData[el.name] = el.checked;
                } else if (el.type === 'radio') {
                    if (el.checked) formData[el.name] = el.value;
                } else {
                    formData[el.name] = el.value;
                }
            }
        });

        localStorage.setItem(draftKey, JSON.stringify(formData));
    }

    // Restore draft elements
    function restoreDraft(draftData) {
        Object.keys(draftData).forEach(name => {
            const val = draftData[name];
            const el = form.querySelector(`[name="${name}"]`);
            if (el) {
                if (el.type === 'checkbox') {
                    el.checked = !!val;
                } else if (el.type === 'radio') {
                    const radio = form.querySelector(`[name="${name}"][value="${val}"]`);
                    if (radio) radio.checked = true;
                } else {
                    el.value = val;
                }
                
                // Dispatch change event to trigger dynamic UI category updates
                el.dispatchEvent(new Event('change'));
            }
        });
    }

    // Listen to changes
    form.addEventListener('input', debounceSave);
    form.addEventListener('change', debounceSave);

    // Load Check
    const savedDraft = localStorage.getItem(draftKey);
    if (savedDraft) {
        try {
            const draftData = JSON.parse(savedDraft);
            if (Object.keys(draftData).length > 0) {
                showRestoreBanner(draftData);
            }
        } catch (e) {
            localStorage.removeItem(draftKey);
        }
    }

    // Intercept form submission to mark it for deletion
    form.addEventListener('submit', () => {
        // We will clear the draft after submission success is confirmed.
        // But to make sure, we set a flag so PHP script can inject a remover,
        // or we can remove it immediately on submit (though rules state: "Clear the draft only after successful database submission").
        // Thus, we let the PHP page reload handle the clearing.
    });
}
