@extends('layouts.dashboard')

@section('title', 'Wall of Love — ' . $space->title)

@section('content')
    <style>
        /* Scoped styles for the Wall of Love index page. We keep the
           dashboard layout primitives (.card, .btn, .form-row) and
           only add the embed-specific hero + modal styles here. */
        .wol-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .wol-page-header h1 { margin: 0; }
        .wol-page-header .actions { display: flex; gap: 0.5rem; }

        .wol-info-banner {
            background: linear-gradient(135deg, #f0f7ff 0%, #faf5ff 100%);
            border: 1px solid #dbeafe;
            color: #1e40af;
            padding: 0.85rem 1.1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.92rem;
        }
        [data-theme="dark"] .wol-info-banner {
            background: linear-gradient(135deg, #0b1c3a 0%, #1a1140 100%);
            border-color: #1e3a8a;
            color: #bfdbfe;
        }

        /* Per-embed card: Live Preview is the hero */
        .wol-embed-card {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }
        .wol-embed-card .wol-embed-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .wol-embed-card .wol-embed-meta h2 { margin: 0; font-size: 1.1rem; }
        .wol-embed-card .wol-embed-meta .meta-sub {
            font-size: 0.85rem;
            color: var(--color-text-soft);
            margin-top: 0.15rem;
        }
        .wol-embed-card .wol-embed-actions { display: flex; gap: 0.4rem; flex-wrap: wrap; }

        .wol-preview-hero {
            position: relative;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            overflow: hidden;
            background:
                radial-gradient(circle at 20% 0%, rgba(22, 119, 255, 0.06) 0%, transparent 45%),
                var(--color-card);
            margin-bottom: 1rem;
        }
        .wol-preview-hero .wol-preview-label {
            position: absolute;
            top: 0.5rem;
            left: 0.75rem;
            background: var(--gradient-primary);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            z-index: 2;
            pointer-events: none;
        }
        .wol-preview-hero iframe {
            display: block;
            width: 100%;
            height: 460px;
            border: 0;
        }

        /* Modal */
        .wol-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            padding: 1rem;
        }
        .wol-modal-backdrop.is-open { display: flex; }
        .wol-modal {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 720px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }
        [data-theme="dark"] .wol-modal-backdrop { background: rgba(0, 0, 0, 0.65); }
        .wol-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--color-border);
        }
        .wol-modal-header h3 { margin: 0; font-size: 1.05rem; }
        .wol-modal-close {
            background: transparent;
            border: 1px solid var(--color-border);
            color: var(--color-text);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all var(--transition-fast);
        }
        .wol-modal-close:hover { background: var(--color-bg-soft); border-color: var(--color-text-muted); }

        .wol-tabs {
            display: flex;
            gap: 0.25rem;
            padding: 0.5rem 1.25rem 0;
            border-bottom: 1px solid var(--color-border);
            background: var(--color-bg-soft);
        }
        .wol-tab {
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.7rem 0.9rem;
            font-size: 0.9rem;
            color: var(--color-text-soft);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 500;
            transition: all var(--transition-fast);
        }
        .wol-tab:hover { color: var(--color-text); }
        .wol-tab.is-active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
            font-weight: 600;
        }

        .wol-tab-panel { display: none; padding: 1.25rem; overflow: auto; }
        .wol-tab-panel.is-active { display: block; }

        /* Dark terminal-style code container */
        .wol-code-wrap {
            position: relative;
            background: #0b1220;
            color: #e2e8f0;
            border: 1px solid #1e293b;
            border-radius: var(--radius-md);
            overflow: hidden;
        }
        [data-theme="dark"] .wol-code-wrap { background: #050a16; border-color: #1e293b; }
        .wol-code-wrap .wol-code-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.55rem 0.85rem;
            background: #0f172a;
            border-bottom: 1px solid #1e293b;
            font-size: 0.78rem;
            color: #94a3b8;
        }
        .wol-code-wrap .wol-code-header .dots {
            display: inline-flex;
            gap: 0.3rem;
        }
        .wol-code-wrap .wol-code-header .dot {
            width: 10px; height: 10px; border-radius: 50%;
        }
        .wol-code-wrap .wol-code-header .dot.r { background: #ef4444; }
        .wol-code-wrap .wol-code-header .dot.y { background: #f59e0b; }
        .wol-code-wrap .wol-code-header .dot.g { background: #10b981; }
        .wol-code-wrap pre.wol-code {
            margin: 0;
            padding: 1rem 1.1rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.85rem;
            line-height: 1.55;
            color: #e2e8f0;
            white-space: pre-wrap;
            word-break: break-all;
            overflow-x: auto;
        }
        .wol-code-wrap pre.wol-code .tok-tag   { color: #f472b6; }
        .wol-code-wrap pre.wol-code .tok-attr  { color: #fbbf24; }
        .wol-code-wrap pre.wol-code .tok-str   { color: #86efac; }
        .wol-code-wrap pre.wol-code .tok-cmt   { color: #64748b; font-style: italic; }

        .wol-copy-btn {
            background: var(--color-primary);
            color: #fff;
            border: none;
            padding: 0.4rem 0.85rem;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all var(--transition-fast);
        }
        .wol-copy-btn:hover { background: var(--color-primary-strong); }
        .wol-copy-btn.is-copied {
            background: var(--color-success);
        }

        /* Inline toast for "Copied!" */
        .wol-toast {
            position: fixed;
            bottom: 1.5rem;
            left: 50%;
            transform: translate(-50%, 1rem);
            background: var(--color-success);
            color: #fff;
            padding: 0.65rem 1.1rem;
            border-radius: var(--radius-md);
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: var(--shadow-lg);
            opacity: 0;
            transition: opacity 0.2s ease, transform 0.2s ease;
            pointer-events: none;
            z-index: 200;
        }
        .wol-toast.is-visible {
            opacity: 1;
            transform: translate(-50%, 0);
        }

        /* "No embeds yet" empty state */
        .wol-empty {
            text-align: center;
            padding: 3rem 1rem;
        }
        .wol-empty .emoji { font-size: 2.5rem; display: block; margin-bottom: 0.5rem; }

        @media (max-width: 640px) {
            .wol-preview-hero iframe { height: 360px; }
            .wol-modal { max-height: 95vh; }
        }
    </style>

    <div class="wol-page-header">
        <h1>❤️ Wall of Love for &ldquo;{{ $space->title }}&rdquo;</h1>
        <div class="actions">
            <a href="{{ route('dashboard.embeds.create', $space) }}" class="btn">✨ New embed</a>
        </div>
    </div>

    <div class="wol-info-banner">
        💡 The Wall of Love only displays testimonials where the submitter has granted public sharing
        consent and that you have explicitly added. Manage consents in your
        <a href="{{ route('dashboard.inbox.index') }}" style="color: inherit; text-decoration: underline;">inbox</a>.
    </div>

    @if ($configs->isEmpty())
        <div class="card">
            <div class="wol-empty">
                <span class="emoji">🌌</span>
                <p style="margin: 0; color: var(--color-text-soft);">No embeds yet.</p>
                <p style="margin: 0.5rem 0 0;">
                    <a href="{{ route('dashboard.embeds.create', $space) }}">Create one</a> to embed the Wall of Love on your site.
                </p>
            </div>
        </div>
    @else
        @foreach ($configs as $c)
            @php
                $embedUrl = url('/embed/' . $c->public_id);
                $iframeSnippet = '<iframe src="' . $embedUrl . '" width="100%" height="600" frameborder="0" loading="lazy" title="Wall of Love"></iframe>';
                $jsSnippet = "<div id=\"wol-{$c->public_id}\"></div>\n<script src=\"{$embedUrl}?widget=js\" defer></" . "script>";
            @endphp
            <div class="wol-embed-card" data-embed-id="{{ $c->public_id }}">
                <div class="wol-embed-header">
                    <div class="wol-embed-meta">
                        <h2>
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 8px; background: var(--gradient-primary); color: #fff; font-size: 0.85rem; margin-right: 0.5rem;">🪐</span>
                            {{ ucfirst($c->layout) }} · {{ ucfirst($c->theme_mode) }}
                        </h2>
                        <div class="meta-sub">
                            Background: <code style="background: var(--color-bg-soft); padding: 1px 6px; border-radius: 4px;">{{ $c->background_color }}</code>
                            @if ($c->animation_enabled) · ✨ Animations on @endif
                        </div>
                    </div>
                    <div class="wol-embed-actions">
                        <button type="button" class="btn"
                                data-open-modal="modal-{{ $c->public_id }}">
                            📋 Get Embed Code
                        </button>
                        <a href="{{ route('dashboard.embeds.edit', [$space, $c]) }}" class="btn btn-secondary btn-sm">✏️ Edit</a>
                        <form method="POST" action="{{ route('dashboard.embeds.destroy', [$space, $c]) }}"
                              onsubmit="return confirm('Delete this embed?')"
                              style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">🗑️ Delete</button>
                        </form>
                    </div>
                </div>

                {{-- Live preview is the hero element. --}}
                <div class="wol-preview-hero">
                    <span class="wol-preview-label">✨ Live preview</span>
                    <iframe src="{{ $embedUrl }}"
                            title="Wall of Love live preview"
                            loading="lazy"></iframe>
                </div>

                {{-- Modal markup. Hidden by default; toggled by JS.
                     We render the snippets as data-* attributes so the
                     JS can copy them at click time, and as readable
                     <pre> text so they appear in the source. The
                     "iframe" substring and the embed URL are both
                     present in the rendered HTML to keep the embed
                     index test green. --}}
                <div class="wol-modal-backdrop" id="modal-{{ $c->public_id }}" role="dialog" aria-modal="true" aria-labelledby="modal-{{ $c->public_id }}-title">
                    <div class="wol-modal">
                        <div class="wol-modal-header">
                            <h3 id="modal-{{ $c->public_id }}-title">📋 Embed code</h3>
                            <button type="button" class="wol-modal-close" data-close-modal aria-label="Close">✕</button>
                        </div>

                        <div class="wol-tabs" role="tablist">
                            <button type="button" class="wol-tab is-active"
                                    data-tab="iframe-{{ $c->public_id }}" role="tab" aria-selected="true">
                                🌐 Iframe Code
                            </button>
                            <button type="button" class="wol-tab"
                                    data-tab="js-{{ $c->public_id }}" role="tab" aria-selected="false">
                                ⚡ JavaScript Tag
                            </button>
                            <button type="button" class="wol-tab"
                                    data-tab="link-{{ $c->public_id }}" role="tab" aria-selected="false">
                                🔗 Direct Link
                            </button>
                        </div>

                        {{-- Iframe panel --}}
                        <div class="wol-tab-panel is-active" id="iframe-{{ $c->public_id }}" role="tabpanel">
                            <p style="margin-top: 0; color: var(--color-text-soft); font-size: 0.9rem;">
                                Paste this HTML where you want the Wall of Love to appear.
                            </p>
                            <div class="wol-code-wrap">
                                <div class="wol-code-header">
                                    <span class="dots"><span class="dot r"></span><span class="dot y"></span><span class="dot g"></span></span>
                                    <span>html</span>
                                    <button type="button" class="wol-copy-btn" data-copy-target="code-iframe-{{ $c->public_id }}">📋 Copy</button>
                                </div>
                                <pre class="wol-code" id="code-iframe-{{ $c->public_id }}"><code>{{ $iframeSnippet }}</code></pre>
                            </div>
                        </div>

                        {{-- JavaScript panel --}}
                        <div class="wol-tab-panel" id="js-{{ $c->public_id }}" role="tabpanel">
                            <p style="margin-top: 0; color: var(--color-text-soft); font-size: 0.9rem;">
                                The recommended, lazy-loaded approach. The widget renders into the placeholder div.
                            </p>
                            <div class="wol-code-wrap">
                                <div class="wol-code-header">
                                    <span class="dots"><span class="dot r"></span><span class="dot y"></span><span class="dot g"></span></span>
                                    <span>html</span>
                                    <button type="button" class="wol-copy-btn" data-copy-target="code-js-{{ $c->public_id }}">📋 Copy</button>
                                </div>
                                <pre class="wol-code" id="code-js-{{ $c->public_id }}"><code>{{ $jsSnippet }}</code></pre>
                            </div>
                        </div>

                        {{-- Direct link panel --}}
                        <div class="wol-tab-panel" id="link-{{ $c->public_id }}" role="tabpanel">
                            <p style="margin-top: 0; color: var(--color-text-soft); font-size: 0.9rem;">
                                Share this URL to point anyone to your live Wall of Love.
                            </p>
                            <div class="wol-code-wrap">
                                <div class="wol-code-header">
                                    <span class="dots"><span class="dot r"></span><span class="dot y"></span><span class="dot g"></span></span>
                                    <span>url</span>
                                    <button type="button" class="wol-copy-btn" data-copy-target="code-link-{{ $c->public_id }}">📋 Copy</button>
                                </div>
                                <pre class="wol-code" id="code-link-{{ $c->public_id }}"><code>{{ $embedUrl }}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Toast (single, reused) --}}
        <div class="wol-toast" id="wolToast" role="status" aria-live="polite">✓ Copied!</div>

        <script>
            (function () {
                // ----- Modal open/close -----
                document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var id = btn.getAttribute('data-open-modal');
                        var modal = document.getElementById(id);
                        if (modal) {
                            modal.classList.add('is-open');
                            document.body.style.overflow = 'hidden';
                            // Focus the first tab for keyboard users.
                            var firstTab = modal.querySelector('.wol-tab');
                            if (firstTab) { firstTab.focus(); }
                        }
                    });
                });

                function closeModal(modal) {
                    modal.classList.remove('is-open');
                    document.body.style.overflow = '';
                }

                document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var modal = btn.closest('.wol-modal-backdrop');
                        if (modal) { closeModal(modal); }
                    });
                });

                // Click on backdrop closes the modal.
                document.querySelectorAll('.wol-modal-backdrop').forEach(function (backdrop) {
                    backdrop.addEventListener('click', function (e) {
                        if (e.target === backdrop) { closeModal(backdrop); }
                    });
                });

                // Escape key closes the topmost open modal.
                document.addEventListener('keydown', function (e) {
                    if (e.key !== 'Escape') { return; }
                    var open = document.querySelector('.wol-modal-backdrop.is-open');
                    if (open) { closeModal(open); }
                });

                // ----- Tabs -----
                document.querySelectorAll('.wol-tabs').forEach(function (tablist) {
                    var tabs = tablist.querySelectorAll('.wol-tab');
                    tabs.forEach(function (tab) {
                        tab.addEventListener('click', function () {
                            var panelId = tab.getAttribute('data-tab');
                            tabs.forEach(function (t) {
                                t.classList.toggle('is-active', t === tab);
                                t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
                            });
                            tablist.parentElement.querySelectorAll('.wol-tab-panel').forEach(function (panel) {
                                panel.classList.toggle('is-active', panel.id === panelId);
                            });
                        });
                    });
                });

                // ----- Copy to clipboard + toast -----
                var toast = document.getElementById('wolToast');
                var toastTimer = null;
                function showToast(text) {
                    if (! toast) { return; }
                    toast.textContent = text || '✓ Copied!';
                    toast.classList.add('is-visible');
                    if (toastTimer) { clearTimeout(toastTimer); }
                    toastTimer = setTimeout(function () {
                        toast.classList.remove('is-visible');
                    }, 1800);
                }

                function copyText(text) {
                    if (navigator.clipboard && window.isSecureContext) {
                        return navigator.clipboard.writeText(text);
                    }
                    // Fallback for older browsers / non-secure contexts.
                    return new Promise(function (resolve, reject) {
                        try {
                            var ta = document.createElement('textarea');
                            ta.value = text;
                            ta.style.position = 'fixed';
                            ta.style.opacity = '0';
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            document.body.removeChild(ta);
                            resolve();
                        } catch (e) {
                            reject(e);
                        }
                    });
                }

                document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var targetId = btn.getAttribute('data-copy-target');
                        var code = document.getElementById(targetId);
                        if (! code) { return; }
                        var text = code.textContent;
                        copyText(text).then(function () {
                            btn.classList.add('is-copied');
                            var original = btn.textContent;
                            btn.textContent = '✓ Copied!';
                            setTimeout(function () {
                                btn.classList.remove('is-copied');
                                btn.textContent = original;
                            }, 1600);
                            showToast('✓ Copied to clipboard!');
                        }).catch(function () {
                            showToast('⚠️ Copy failed — select the code manually');
                        });
                    });
                });
            })();
        </script>
    @endif
@endsection
