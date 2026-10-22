function safeCardFilename(card, index) {
    const name = card.querySelector('.field-value')?.textContent.trim() || ('Estudiante_' + (index + 1));
    return 'Carnet_' + name.replace(/[^a-zA-Z0-9ÁÉÍÓÚÜÑáéíóúüñ]+/g, '_');
}

async function downloadCardPng(card, index) {
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
    link.download = safeCardFilename(card, index) + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

async function downloadAllCardsPng() {
    const button = document.getElementById('downloadPngButton');
    const cards = Array.from(document.querySelectorAll('.student-card'));
    button.disabled = true;

    try {
        for (let index = 0; index < cards.length; index++) {
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generando ' + (index + 1) + ' de ' + cards.length;
            await downloadCardPng(cards[index], index);
            await new Promise(function (resolve) { setTimeout(resolve, 300); });
        }
    } finally {
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-file-earmark-image me-1"></i> Descargar PNG 4K';
    }
}

async function downloadAllCardsPdf() {
    const button = document.getElementById('downloadPdfButton');
    const cards = Array.from(document.querySelectorAll('.student-card'));
    if (!cards.length || !window.jspdf) return;

    const originalContent = button.innerHTML;
    button.disabled = true;
    try {
        // A4 vertical: 2 columnas × 4 filas = hasta 8 carnets por hoja.
        // Se conserva el tamaño estándar de un carnet (85.6 × 54 mm).
        const pdf = new window.jspdf.jsPDF({
            orientation: 'portrait', unit: 'mm', format: 'a4', compress: true
        });
        const cardWidthMm = 85.6;
        const cardHeightMm = 54;
        const columns = 2;
        const rows = 4;
        const cardsPerPage = columns * rows;
        const columnGapMm = 8;
        const rowGapMm = 7;
        const pageWidthMm = 210;
        const pageHeightMm = 297;
        const gridWidthMm = (columns * cardWidthMm) + ((columns - 1) * columnGapMm);
        const gridHeightMm = (rows * cardHeightMm) + ((rows - 1) * rowGapMm);
        const startX = (pageWidthMm - gridWidthMm) / 2;
        const startY = (pageHeightMm - gridHeightMm) / 2;
        for (let index = 0; index < cards.length; index++) {
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generando ' + (index + 1) + ' de ' + cards.length;
            await rasterizeQrImages(cards[index]);
            const canvas = await html2canvas(cards[index], {
                scale: 3, useCORS: true, backgroundColor: '#ffffff', logging: false, imageTimeout: 15000
            });
            if (index && index % cardsPerPage === 0) pdf.addPage('a4', 'portrait');
            const positionInPage = index % cardsPerPage;
            const column = positionInPage % columns;
            const row = Math.floor(positionInPage / columns);
            const x = startX + (column * (cardWidthMm + columnGapMm));
            const y = startY + (row * (cardHeightMm + rowGapMm));
            pdf.addImage(canvas.toDataURL('image/jpeg', 0.95), 'JPEG', x, y, cardWidthMm, cardHeightMm, undefined, 'FAST');
        }
        pdf.save('Carnets_estudiantiles.pdf');
    } finally {
        button.disabled = false;
        button.innerHTML = originalContent;
    }
}
