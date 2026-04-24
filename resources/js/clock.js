export function init() {

    const canvas = document.getElementById('clock');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const cardCircle = canvas.parentNode; // Získáme rodičovský element card-circle
    let animationFrameId;
    let radius = 0;

    //Barva pro světlý režim
    const LIGHT_MODE_COLORS = {
        dialBorder: 'rgba(0, 0, 0, 0.15)', // Jemný tmavý okraj ciferníku
        elements: '#343a40',               // Tmavá barva pro ručičky, čárky, text (z --clr-text-heading)
        centerDot: '#343a40'               // Tmavá barva pro středový bod
    };

    // Barva pro černý režim
    const DARK_MODE_COLORS = {
        dialBorder: '#990000',             // Původní červený okraj ciferníku
        elements: '#ffffff',               // Bílá barva pro ručičky, čárky, text
        centerDot: '#ffffff'               // Bílá barva pro středový bod
    };

    let currentColors = LIGHT_MODE_COLORS; // Výchozí barvy (budou aktualizovány při prvním drawClock)

    function getThemeColors() {
        const isDarkMode = document.body.classList.contains('dark-mode');
        return isDarkMode ? DARK_MODE_COLORS : LIGHT_MODE_COLORS;
    }
    function resizeCanvas() {
        if (!cardCircle || cardCircle.offsetWidth === 0 || cardCircle.offsetHeight === 0) {
            console.warn('Karta pro hodiny nebyla nalezena nebo má nula rozměry');
            return;
        }
        const size = Math.min(cardCircle.offsetWidth, cardCircle.offsetHeight); // Vezmeme menší z rozměrů, aby byl kruhový
        canvas.width = size;
        canvas.height = size;
        radius = canvas.width / 2; // Aktualizujeme poloměr
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.translate(radius, radius); // Přeposuneme počátek do středu
        drawClock(); // Překreslíme hodiny s novými rozměry
    }

    function drawClock() {
        ctx.clearRect(-radius, -radius, canvas.width, canvas.height); // Vyčistíme plátno s ohledem na posunutý počátek
        currentColors = getThemeColors();//Aktualizace barev

        // Ciferník
        ctx.beginPath();
        ctx.arc(0, 0, radius * 0.95, 0, 2 * Math.PI);
        ctx.fillStyle = 'transparent';
        ctx.fill();
        ctx.lineWidth = 8 * (radius / 121); // Přizpůsobíme tloušťku čáry
        ctx.strokeStyle = currentColors.dialBorder; // Dynamická barva
        ctx.stroke();

        // Čárky hodin
        for (let i = 0; i < 12; i++) {
            ctx.save();
            ctx.rotate(i * Math.PI / 6);
            ctx.beginPath();
            ctx.moveTo(0, -radius * 0.8);
            ctx.lineTo(0, -radius * 0.9);
            ctx.lineWidth = 4 * (radius / 121); // Přizpůsobíme tloušťku čáry
            ctx.strokeStyle = currentColors.elements; // Dynamická barva
            ctx.stroke();
            ctx.restore();
        }

        // Čárky minut
        for (let i = 0; i < 60; i++) {
            if (i % 5 !== 0) {
                ctx.save();
                ctx.rotate(i * Math.PI / 30);
                ctx.beginPath();
                ctx.moveTo(0, -radius * 0.85);
                ctx.lineTo(0, -radius * 0.9);
                ctx.lineWidth = 1.5 * (radius / 121); // Přizpůsobíme tloušťku čáry
                ctx.strokeStyle = currentColors.elements; // Dynamická barva
                ctx.stroke();
                ctx.restore();
            }
        }

        // Ručičky
        const now = new Date();
        let hour = now.getHours();
        let minute = now.getMinutes();
        let second = now.getSeconds();

        // Hodinová ručička
        ctx.save();
        ctx.rotate(((hour % 12) + minute / 60) * Math.PI / 6);
        ctx.beginPath();
        ctx.moveTo(0, 10 * (radius / 121)); // Přizpůsobíme délku
        ctx.lineTo(0, -radius * 0.5);
        ctx.lineWidth = 6 * (radius / 121); // Přizpůsobíme tloušťku
        ctx.strokeStyle = currentColors.elements; // Dynamická barva
        ctx.lineCap = 'round';
        ctx.stroke();
        ctx.restore();

        // Minutová ručička
        ctx.save();
        ctx.rotate((minute + second / 60) * Math.PI / 30);
        ctx.beginPath();
        ctx.moveTo(0, 20 * (radius / 121)); // Přizpůsobíme délku
        ctx.lineTo(0, -radius * 0.75);
        ctx.lineWidth = 4 * (radius / 121); // Přizpůsobíme tloušťku
        ctx.strokeStyle = currentColors.elements; // Dynamická barva
        ctx.lineCap = 'round';
        ctx.stroke();
        ctx.restore();

        // Vteřinová ručička
        ctx.save();
        ctx.rotate(second * Math.PI / 30);
        ctx.beginPath();
        ctx.moveTo(0, 30 * (radius / 121)); // Přizpůsobíme délku
        ctx.lineTo(0, -radius * 0.8);
        ctx.lineWidth = 2 * (radius / 121); // Přizpůsobíme tloušťku
        ctx.strokeStyle = currentColors.elements; // Dynamická barva
        ctx.lineCap = 'round';
        ctx.stroke();
        ctx.restore();

        // Středový bod
        ctx.beginPath();
        ctx.arc(0, 0, 8 * (radius / 121), 0, 2 * Math.PI); // Přizpůsobíme poloměr
        ctx.strokeStyle = currentColors.elements; // Dynamická barva
        ctx.fill();

        // **Digitální zobrazení**
        const fontSizeDigital = radius * 0.2;
        ctx.font = `${fontSizeDigital}px monospace`;
        ctx.fillStyle = currentColors.elements; // Dynamická barva
        ctx.textAlign = "center";
        ctx.textBaseline = "top";
        ctx.fillText(`${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}:${now.getSeconds().toString().padStart(2, '0')}`, 0, radius * 0.3);

        // **Zobrazení data**
        const fontSizeDate = radius * 0.14;
        ctx.font = `${fontSizeDate}px sans-serif`;
        ctx.fillStyle = currentColors.elements; // Dynamická barva
        ctx.textAlign = "center";
        ctx.textBaseline = "bottom";
        ctx.fillText(`${now.getDate().toString().padStart(2, '0')}.${((now.getMonth() + 1)).toString().padStart(1, '0')}.${now.getFullYear()}`, 0, -radius * 0.4);
    }

    function animate() {
        drawClock();
        animationFrameId = requestAnimationFrame(animate);
    }

// Inicializace a spuštění animace při změně velikosti okna
    window.addEventListener('resize', resizeCanvas);

    const themeToggleButton = document.getElementById('theme-toggle');
    if (themeToggleButton) {
        themeToggleButton.addEventListener('click', () => {
            // Po kliknutí na přepínač se změní třída na body, takže stačí překreslit
            drawClock();
        });
    }
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                console.log('Body class changed, redrawing clock.');
                drawClock(); // Překreslíme hodiny s novými barvami
            }
        });
    });

    // Začneme pozorovat změny atributů na elementu <body>
    observer.observe(document.body, { attributes: true });
// Inicializace při načtení stránky
    resizeCanvas();

// Spuštění animace
    animate();
}
