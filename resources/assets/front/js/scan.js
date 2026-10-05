import { Modal } from 'bootstrap';

// Scan d'un numéro de lot avec la caméra (API BarcodeDetector du navigateur).
// Sans caméra ou sur un navigateur non compatible, la saisie manuelle reste disponible.
export function initScan() {
    const boutons = document.querySelectorAll('[data-scan]');
    const modalEl = document.getElementById('scanModal');
    if (!boutons.length || !modalEl) {
        return;
    }

    const video = modalEl.querySelector('video');
    const message = modalEl.querySelector('[data-scan-message]');
    const modal = Modal.getOrCreateInstance(modalEl);
    let flux = null;
    let champ = null;
    let actif = false;

    const arreter = () => {
        actif = false;
        flux?.getTracks().forEach((piste) => piste.stop());
        flux = null;
        video.srcObject = null;
    };

    const afficher = (texte) => {
        message.textContent = texte;
        message.hidden = false;
    };

    const detecter = async (detecteur) => {
        if (!actif) {
            return;
        }
        try {
            const codes = await detecteur.detect(video);
            const valeur = codes.find((code) => code.rawValue)?.rawValue;
            if (valeur) {
                champ.value = valeur.trim().toUpperCase();
                modal.hide();
                champ.form?.requestSubmit();
                return;
            }
        } catch {
            // Image pas encore prête : on réessaie à la frame suivante.
        }
        requestAnimationFrame(() => detecter(detecteur));
    };

    boutons.forEach((bouton) => {
        bouton.addEventListener('click', async () => {
            champ = document.querySelector(bouton.dataset.scan);
            message.hidden = true;
            modal.show();

            if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
                afficher('Le scan par caméra n\'est pas pris en charge par ce navigateur. Saisissez le numéro de lot manuellement.');
                return;
            }

            try {
                flux = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = flux;
                await video.play();
                actif = true;
                detecter(new window.BarcodeDetector({ formats: ['qr_code', 'code_128', 'code_39', 'ean_13', 'data_matrix'] }));
            } catch {
                afficher('Impossible d\'accéder à la caméra. Autorisez-la dans votre navigateur ou saisissez le numéro manuellement.');
            }
        });
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        arreter();
        champ?.focus();
    });
}
