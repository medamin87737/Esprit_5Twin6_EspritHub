@extends('layouts.front')

@section('title', 'Assistant Labels')

@section('content')
    <x-front.page-header eyebrow="IA · Module 5" title="Assistant Labels NutriTrace" image="consommateur-salade.webp"
        subtitle="Posez une question sur un label bio, local ou équitable. L'IA répond instantanément sur sa validité, son organisme certificateur et le produit concerné." />

    <section class="page-section bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    {{-- Numéro pré-rempli --}}
                    @if ($numero)
                        <div class="alert alert-info small mb-4">
                            <i class="bi bi-patch-check me-1" aria-hidden="true"></i>
                            Vous vérifiez le label <strong>{{ $numero }}</strong>. Posez votre question ci-dessous.
                        </div>
                    @endif

                    {{-- Fenêtre chat --}}
                    <div class="data-card mb-3">
                        <div id="chat-messages" style="min-height:260px; max-height:420px; overflow-y:auto; padding:1.25rem; display:flex; flex-direction:column; gap:.75rem;">
                            <div class="chat-msg chat-msg--assistant">
                                <span class="chat-bubble">
                                    👋 Bonjour ! Je suis l'assistant NutriTrace. Demandez-moi si un label est valide, qui l'a délivré, ou quand il expire.<br>
                                    <small class="text-muted">Ex. : « Le label BIO-TN-2026-001 est-il valide ? »</small>
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Carte certification --}}
                    <div id="chat-certification" class="data-card mb-3" style="display:none">
                        <div class="p-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-award fs-5" aria-hidden="true"></i>
                                <strong id="cert-numero"></strong>
                                <span id="cert-statut" class="status-pill ms-auto"></span>
                            </div>
                            <div class="small text-muted" id="cert-details"></div>
                        </div>
                    </div>

                    {{-- Formulaire --}}
                    <form id="chat-form" class="d-flex gap-2" autocomplete="off">
                        @csrf
                        <input type="hidden" id="chat-numero" value="{{ $numero }}">
                        <input type="text" id="chat-input" class="form-control" placeholder="Votre question sur un label…" maxlength="500" required aria-label="Message">
                        <button type="submit" class="btn btn-primary px-4" id="chat-btn">
                            <i class="bi bi-send" aria-hidden="true"></i>
                        </button>
                    </form>

                    <p class="small text-muted mt-2">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        L'assistant utilise OpenRouter (Gemini). Vérifiez toujours sur
                        <a href="{{ route('front.certifications.index') }}">la page officielle</a>.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <style>
    .chat-msg { display: flex; }
    .chat-msg--user { justify-content: flex-end; }
    .chat-bubble { display: inline-block; padding: .55rem .9rem; border-radius: 1rem; max-width: 80%; line-height: 1.5; font-size: .9rem; }
    .chat-msg--assistant .chat-bubble { background: #f1f3f5; color: #212529; border-bottom-left-radius: .25rem; }
    .chat-msg--user .chat-bubble { background: #0d6efd; color: #fff; border-bottom-right-radius: .25rem; }
    </style>

    <script>
    const chatUrl  = '{{ route('front.certifications.chat.repondre') }}';
    const csrf     = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    let historique = [];

    const messages  = document.getElementById('chat-messages');
    const form      = document.getElementById('chat-form');
    const input     = document.getElementById('chat-input');
    const btn       = document.getElementById('chat-btn');
    const certCard  = document.getElementById('chat-certification');

    function ajouterMsg(role, texte) {
        const div = document.createElement('div');
        div.className = `chat-msg chat-msg--${role}`;
        const bubble = document.createElement('span');
        bubble.className = 'chat-bubble';
        bubble.innerHTML = texte.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
        div.appendChild(bubble);
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    function afficherCertification(cert) {
        if (!cert) { certCard.style.display = 'none'; return; }
        certCard.style.display = '';
        document.getElementById('cert-numero').textContent = cert.numero + ' · ' + cert.type;
        const statut = document.getElementById('cert-statut');
        statut.textContent = cert.statut;
        statut.className = 'status-pill ' + cert.statut_classe;
        document.getElementById('cert-details').textContent =
            (cert.organisme || '') + (cert.produit ? ' · ' + cert.produit : '') +
            (cert.date_expiration ? ' · expire le ' + cert.date_expiration : '');
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const msg = input.value.trim();
        if (!msg) return;

        ajouterMsg('user', msg);
        historique.push({ role: 'user', content: msg });
        input.value = '';
        btn.disabled = true;
        ajouterMsg('assistant', '<span class="spinner-border spinner-border-sm me-1"></span> …');

        try {
            const r = await fetch(chatUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({
                    message: msg,
                    historique: historique.slice(-10),
                    numero: document.getElementById('chat-numero').value || null,
                })
            });
            const d = await r.json();
            messages.lastElementChild.remove();

            if (d.ok) {
                ajouterMsg('assistant', d.reponse);
                historique.push({ role: 'assistant', content: d.reponse });
                afficherCertification(d.certification);
            } else {
                ajouterMsg('assistant', '❌ ' + (d.message || 'Erreur IA.'));
            }
        } catch {
            messages.lastElementChild.remove();
            ajouterMsg('assistant', '❌ Erreur réseau. Réessayez.');
        }

        btn.disabled = false;
        input.focus();
    });
    </script>
@endsection
