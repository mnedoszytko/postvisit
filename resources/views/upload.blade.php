<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upload Photo — PostVisit.ai</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f9fafb;
            color: #1f2937;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 20px;
            text-align: center;
        }
        .logo {
            font-size: 18px;
            font-weight: 700;
            color: #059669;
        }
        .logo span { color: #6b7280; font-weight: 400; }
        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 20px;
            gap: 20px;
        }
        .visit-info {
            font-size: 13px;
            color: #6b7280;
            text-align: center;
            max-width: 300px;
        }
        .upload-area {
            width: 100%;
            max-width: 340px;
        }
        .upload-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 18px 24px;
            background: #059669;
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }
        .upload-btn:hover { background: #047857; }
        .upload-btn:active { background: #065f46; }
        .upload-btn:disabled { background: #9ca3af; cursor: not-allowed; }
        .upload-btn svg { width: 24px; height: 24px; }
        .hint {
            text-align: center;
            font-size: 13px;
            color: #9ca3af;
            margin-top: 8px;
        }
        .type-select {
            width: 100%;
            max-width: 340px;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            font-size: 15px;
            background: white;
            color: #374151;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
        }

        /* States */
        .state { display: none; flex-direction: column; align-items: center; gap: 16px; }
        .state.active { display: flex; }

        /* Progress */
        .spinner {
            width: 48px; height: 48px;
            border: 4px solid #e5e7eb;
            border-top-color: #059669;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .progress-text { font-size: 15px; color: #6b7280; }

        /* Success */
        .success-icon {
            width: 64px; height: 64px;
            background: #d1fae5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-icon svg { width: 32px; height: 32px; color: #059669; }
        .success-title { font-size: 20px; font-weight: 700; color: #059669; }
        .success-text { font-size: 14px; color: #6b7280; text-align: center; }

        /* Error */
        .error-icon {
            width: 64px; height: 64px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-icon svg { width: 32px; height: 32px; color: #dc2626; }
        .error-title { font-size: 20px; font-weight: 700; color: #dc2626; }
        .error-text { font-size: 14px; color: #6b7280; text-align: center; max-width: 300px; }
        .retry-btn {
            padding: 12px 32px;
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
        }
        .retry-btn:hover { background: #f9fafb; }

        /* Expired */
        .expired-icon {
            width: 64px; height: 64px;
            background: #fef3c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .expired-icon svg { width: 32px; height: 32px; color: #d97706; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">PostVisit<span>.ai</span></div>
    </div>

    <div class="content">
        <!-- Ready state -->
        <div id="state-ready" class="state active">
            @if($visitReason)
                <div class="visit-info">
                    Uploading for visit: <strong>{{ $visitReason }}</strong>
                </div>
            @endif
            <div class="upload-area">
                <input type="file" id="file-input" accept="image/*" capture="environment" style="display:none">
                <select id="doc-type" class="type-select">
                    <option value="photo">Photo</option>
                    <option value="ecg">ECG</option>
                    <option value="imaging">Imaging</option>
                    <option value="lab_result">Lab Result</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="upload-area">
                <button id="upload-btn" class="upload-btn" type="button">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                    </svg>
                    Take Photo
                </button>
                <p class="hint">or choose an existing image from your gallery</p>
            </div>
        </div>

        <!-- Uploading state -->
        <div id="state-uploading" class="state">
            <div class="spinner"></div>
            <div class="progress-text">Uploading photo...</div>
        </div>

        <!-- Success state -->
        <div id="state-success" class="state">
            <div class="success-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <div class="success-title">Photo uploaded</div>
            <div class="success-text">Your photo has been sent to your desktop. You can close this page.</div>
        </div>

        <!-- Error state -->
        <div id="state-error" class="state">
            <div class="error-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>
            <div class="error-title">Upload failed</div>
            <div class="error-text" id="error-message">Something went wrong. Please try again.</div>
            <button class="retry-btn" id="retry-btn" type="button">Try Again</button>
        </div>

        <!-- Expired state -->
        <div id="state-expired" class="state">
            <div class="expired-icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="error-title" style="color: #d97706;">Link expired</div>
            <div class="error-text">This upload link has expired. Please generate a new QR code from your desktop.</div>
        </div>
    </div>

    <script>
        (function() {
            const token = @json($token);
            const expiresAt = new Date(@json($expiresAt));
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            const fileInput = document.getElementById('file-input');
            const uploadBtn = document.getElementById('upload-btn');
            const docType = document.getElementById('doc-type');
            const retryBtn = document.getElementById('retry-btn');

            function showState(name) {
                document.querySelectorAll('.state').forEach(el => el.classList.remove('active'));
                document.getElementById('state-' + name).classList.add('active');
            }

            // Check expiry
            if (new Date() >= expiresAt) {
                showState('expired');
                return;
            }

            // Auto-expire
            const timeLeft = expiresAt.getTime() - Date.now();
            if (timeLeft > 0) {
                setTimeout(() => showState('expired'), timeLeft);
            }

            uploadBtn.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', async function() {
                const file = this.files[0];
                if (!file) return;

                showState('uploading');

                const formData = new FormData();
                formData.append('file', file);
                formData.append('document_type', docType.value);

                try {
                    const response = await fetch('/upload/' + token, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    if (response.status === 410) {
                        showState('expired');
                        return;
                    }

                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));
                        throw new Error(data.error || data.message || 'Upload failed');
                    }

                    showState('success');
                } catch (err) {
                    document.getElementById('error-message').textContent = err.message || 'Something went wrong. Please try again.';
                    showState('error');
                }

                // Reset file input for retry
                this.value = '';
            });

            retryBtn.addEventListener('click', () => showState('ready'));
        })();
    </script>
</body>
</html>
