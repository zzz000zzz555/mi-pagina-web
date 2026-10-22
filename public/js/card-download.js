document.addEventListener('DOMContentLoaded', function () {
    const instituteName = document.querySelector('#cardModal .institute-name');
    if (instituteName) instituteName.textContent = 'IES JOSÉ SANTOS CHOCANO';
    const oldIcon = document.querySelector('#cardModal .credential-head > i');
    if (oldIcon) {
        const logo = document.createElement('img');
        logo.src = 'assets/img/logo-jsc-hd.png?v=1';
        logo.alt = 'Logo IES José Santos Chocano';
        oldIcon.replaceWith(logo);
    }

    const printButton = document.querySelector('#cardModal .modal-footer .btn-primary');
    if (!printButton) return;

    const imageButton = document.createElement('button');
    imageButton.type = 'button';
    imageButton.className = 'btn btn-success';
    imageButton.innerHTML = '<i class="bi bi-file-earmark-image me-1"></i> Descargar PNG 4K';
    imageButton.addEventListener('click', downloadStudentCardPng);
    printButton.before(imageButton);
});

async function downloadStudentCardPng() {
    const card = document.getElementById('printableArea');
    const name = document.getElementById('cardName').textContent.trim() || 'Estudiante';
    const button = document.querySelector('#cardModal .modal-footer .btn-success');
    const originalContent = button.innerHTML;

    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generando PNG';

    try {
        const images = Array.from(card.querySelectorAll('img'));
        await Promise.all(images.map(function (image) {
            if (image.complete) return Promise.resolve();
            return new Promise(function (resolve) {
                image.addEventListener('load', resolve, { once: true });
                image.addEventListener('error', resolve, { once: true });
            });
        }));

        await rasterizeQrImages(card);
        const cardWidth = card.getBoundingClientRect().width;
        const fourKScale = Math.max(3, 3840 / cardWidth);
        const canvas = await html2canvas(card, {
            scale: fourKScale,
            useCORS: true,
            backgroundColor: null,
            logging: false,
            imageTimeout: 15000
        });
        const link = document.createElement('a');
        link.download = 'Carnet_' + name.replace(/[^a-zA-Z0-9ÁÉÍÓÚÜÑáéíóúüñ]+/g, '_') + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    } finally {
        button.disabled = false;
        button.innerHTML = originalContent;
    }
}

async function rasterizeQrImages(container) {
    const qrImages = Array.from(container.querySelectorAll('.credential-qr, .qr'));
    await Promise.all(qrImages.map(async function (qrImage) {
        if (qrImage.dataset.rasterized === 'true') return;

        const response = await fetch(qrImage.src, { credentials: 'same-origin' });
        if (!response.ok) throw new Error('No se pudo cargar el código QR.');
        const svgText = await response.text();
        const svgBlob = new Blob([svgText], { type: 'image/svg+xml;charset=utf-8' });
        const objectUrl = URL.createObjectURL(svgBlob);

        try {
            const sourceImage = new Image();
            await new Promise(function (resolve, reject) {
                sourceImage.onload = resolve;
                sourceImage.onerror = reject;
                sourceImage.src = objectUrl;
            });

            const qrCanvas = document.createElement('canvas');
            qrCanvas.width = 768;
            qrCanvas.height = 768;
            const context = qrCanvas.getContext('2d');
            context.imageSmoothingEnabled = false;
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, qrCanvas.width, qrCanvas.height);
            context.drawImage(sourceImage, 0, 0, qrCanvas.width, qrCanvas.height);
            qrImage.src = qrCanvas.toDataURL('image/png');
            qrImage.dataset.rasterized = 'true';
            await qrImage.decode();
        } finally {
            URL.revokeObjectURL(objectUrl);
        }
    }));
}
