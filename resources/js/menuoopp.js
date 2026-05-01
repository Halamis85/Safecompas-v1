
 function menuoopp() {

    const ordersContainer = document.getElementById('orders-container');
    const cekaCountElem = document.getElementById('ceka-count');
    const objedCountElem = document.getElementById('obje-count');
    const displayDateFullElement = document.getElementById('display-date-full');
    const holidayFoundSection = document.getElementById('holiday-found-section');
    const holidayNameElement = document.getElementById('holiday-name');
    const publicHolidayWarning = document.getElementById('public-holiday-warning');
    const noHolidaySection = document.getElementById('no-holiday-section');
    const loadingOrErrorMessage = document.getElementById('loading-or-error-message');
    const weatherInfoContainer = document.getElementById('weather-info');
    const weatherCardElement = document.querySelector('#weather-info').closest('.card-circle-weather');



    fetchAndDisplayTodayHoliday()
        .catch(error => {
            console.error("Kritická neošetřená chyba ve fetchAndDisplayTodayHoliday:", error);
        });

    // Funkce pro získání dnešního data ve formátu YYYY-MM-DD
    function getTodayDateString() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Asynchronní funkce pro načtení a zobrazení dnešního svátku
    async function fetchAndDisplayTodayHoliday() {
        const todayDate = getTodayDateString();
        const currentYear = new Date().getFullYear();
        const countryCode = 'CZ';

        const url = `/holidays?year=${currentYear}&country_code=${countryCode}`;

        // Zobrazení skeletonu a skrytí ostatních sekcí na začátku
        if (loadingOrErrorMessage) {
            loadingOrErrorMessage.classList.remove('d-none');
        }
        if (holidayFoundSection) holidayFoundSection.classList.add('d-none');
        if (noHolidaySection) noHolidaySection.classList.add('d-none');
        if (publicHolidayWarning) publicHolidayWarning.classList.add('d-none');
        // Formátování dnešního data pro zobrazení
        let displayDate = new Date().toLocaleDateString('cs-CZ', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
        displayDate = displayDate.charAt(0).toUpperCase() + displayDate.slice(1);

        // Aktualizace textu dnešního data (vždy se zobrazí)
        if (displayDateFullElement) {
            displayDateFullElement.textContent = displayDate;
        }

        try {
            const response = await fetch(url);

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: 'Neznámá chyba API' }));
                console.error('Chyba při načítání svátků:', response.status, response.statusText, errorData.message);
                if (loadingOrErrorMessage) {
                    loadingOrErrorMessage.textContent = `Chyba (${response.status}): ${errorData.message || response.statusText}. Nepodařilo se načíst svátky.`;
                }
                return;
            }

            const holidays = await response.json();
            const todayHoliday = Array.isArray(holidays) ? holidays.find(h => h.date === todayDate) : null;

            loadingOrErrorMessage.classList.add('d-none'); // Skryjeme načítací zprávu

            if (todayHoliday) {
                holidayFoundSection.classList.remove('d-none'); // Zobrazíme sekci, když je svátek
                noHolidaySection.classList.add('d-none'); // Skryjeme "nikdo nemá svátek"

                holidayNameElement.textContent = todayHoliday.name;

                if (todayHoliday.is_public_holiday) {
                    publicHolidayWarning.classList.remove('d-none'); // Zobrazíme varování, pokud je státní svátek
                } else {
                    publicHolidayWarning.classList.add('d-none'); // Skryjeme, pokud není
                }
            } else {
                holidayFoundSection.classList.add('d-none'); // Skryjeme sekci svátku
                noHolidaySection.classList.remove('d-none'); // Zobrazíme "nikdo nemá svátek"
            }

        } catch (error) {
            console.error('Kritická chyba při volání API svátků (možná síťová chyba):', error);
            loadingOrErrorMessage.textContent = 'Došlo k chybě připojení. Zkuste to prosím znovu.';
            loadingOrErrorMessage.classList.remove('d-none'); // Ujistíme se, že chybová zpráva je viditelná
        }
    }
    // --- Funkce pro počasí ---

    function getAnimatedWeatherIconUrl(iconCode) {
        if (!iconCode) {
            return ''; // Vrátí prázdný řetězec, pokud není kód ikony
        }
        const iconMap = {
            '01d': '/images/Weather/01d.png', // Jasno, den
            '01n': '/images/Weather/01n.png', // Jasno, noc
            '02d': '/images/Weather/02d.png', // Polojasno, den
            '02n': '/images/Weather/02n.png', // Polojasno, noc
            '03d': '/images/Weather/03d.png', // Oblačno, den
            '03n': '/images/Weather/03n.png', // Oblačno, noc
            '04d': '/images/Weather/04d.png', // Zataženo, den
            '04n': '/images/Weather/04n.png', // Zataženo, noc
            '09d': '/images/Weather/09d.png', // Slabý déšť, den
            '09n': '/images/Weather/09n.png', // Slabý déšť, noc
            '10d': '/images/Weather/10d.png', // Déšť, den
            '10n': '/images/Weather/10n.png', // Déšť, noc
            '11d': '/images/Weather/11d.png', // Bouřka, den
            '11n': '/images/Weather/11n.png', // Bouřka, noc
            '13d': '/images/Weather/13d.png', // Sníh, den
            '13n': '/images/Weather/13n.png', // Sníh, noc
            '50d': '/images/Weather/50d.png', // Mlha, den
            '50n': '/images/Weather/50n.png', // Mlha, noc
            // Přidejte další kódy ikon, pokud je vaše sada obsahuje
        };

        // Vrátí URL z mapování, nebo prázdný řetězec, pokud ikona není nalezena
        return iconMap[iconCode] || '';
    }

    async function fetchAndDisplayWeather(city = 'Liberec', countryCode = 'CZ') {
        const url = `/weather/current?city=${encodeURIComponent(city)}&country_code=${encodeURIComponent(countryCode)}`;

        // Skeleton loader je v HTML, nemusíme ho sem vkládat dynamicky, 
        // ale zajistíme, aby se starý obsah vymazal pokud voláme znovu
        if (weatherInfoContainer.querySelector('strong')) {
             weatherInfoContainer.innerHTML = '<div class="skeleton skeleton-text mx-auto" style="width: 60%;"></div><div class="skeleton skeleton-text mx-auto" style="width: 40%;"></div>';
        }

        weatherCardElement.classList.remove('shadow-sunny', 'shadow-partly-cloudy', 'shadow-cloudy-rain', 'shadow-snow');

        try {
            const response = await fetch(url);

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: 'Neznámá chyba' }));
                console.error('Chyba při načítání počasí:', response.status, response.statusText, errorData.message);
                weatherInfoContainer.innerHTML = `<p class="text-danger">Chyba (${response.status}): ${errorData.message || response.statusText}.</p>`;
                return;
            }

            const weather = await response.json();

            if (weather.location) {
                // Získáme kód ikony z OpenWeatherMap
                const iconCode = weather.icon_url ? weather.icon_url.match(/([0-9]{2}[dn])/)[1] : null;
                // Získáme URL naší animované ikony
                const animatedIconUrl = getAnimatedWeatherIconUrl(iconCode);
                weatherInfoContainer.innerHTML = `
                            <span>
                                 ${animatedIconUrl ? `<img src="${animatedIconUrl}" alt="${weather.weather_description}" style="width: 110px; height: 110px; vertical-align: middle;">` : ''}
                              <br>${weather.temperature_celsius ? `<strong class="mt-1">${Math.round(weather.temperature_celsius)}°C ${weather.location}</strong>` : ''}
                            </span>
                            <p class="text-menu fs-8 mt-2">
                                Vlhkost: ${weather.humidity}% <br> Vítr: ${weather.wind_speed_mps} m/s
                            </p>
                        `;

                const description = weather.weather_description.toLowerCase();

                weatherCardElement.classList.remove('shadow-sunny', 'shadow-partly-cloudy', 'shadow-cloudy-rain', 'shadow-snow');

                if (description.includes('jasno') || description.includes('slunečno') || iconCode === '01d' || iconCode === '01n') { // Použijeme i ikonové kódy pro robustnost
                    weatherCardElement.classList.add('shadow-sunny');
                } else if (description.includes('oblačno') || description.includes('částečně') || description.includes('polojasno') || description.includes('mraky') || iconCode === '02d' || iconCode === '02n' || iconCode === '03d' || iconCode === '03n' || iconCode === '04d' || iconCode === '04n') {
                    weatherCardElement.classList.add('shadow-partly-cloudy');
                } else if (description.includes('déšť') || description.includes('přeháňky') || description.includes('bouřka') || description.includes('mrholení') || iconCode && iconCode.startsWith('09') || iconCode && iconCode.startsWith('10') || iconCode && iconCode.startsWith('11') || iconCode && iconCode.startsWith('13')) { // Ikonové kódy pro déšť/bouřku/sníh
                    // 09 = déšť, 10 = déšť, 11 = bouřka, 13 = sníh (OpenWeatherMap)
                    if (description.includes('sníh') || iconCode && iconCode.startsWith('13')) { // Specificky pro sníh
                        weatherCardElement.classList.add('shadow-snow');
                    } else { // Jinak je to déšť/zataženo
                        weatherCardElement.classList.add('shadow-cloudy-rain');
                    }
                } else if (description.includes('sníh') || iconCode && iconCode.startsWith('13')) { // Zde i pro čistý sníh, pokud by nebyl déšť
                    weatherCardElement.classList.add('shadow-snow');
                } else if (description.includes('mlha') || description.includes('opar') || iconCode && iconCode.startsWith('50')) { // Mlha
                    weatherCardElement.classList.add('shadow-cloudy-rain'); // Může být i šedá pro mlhu
                }

            } else {
                weatherInfoContainer.innerHTML = '<p class="text-muted">Nepodařilo se získat detailní data o počasí.</p>';
            }

        } catch (error) {
            console.error('Kritická chyba při volání API počasí:', error);
            weatherInfoContainer.innerHTML = '<p class="text-danger">Došlo k chybě připojení k API počasí. Zkuste to prosím znovu.</p>';
        }
    }

    fetch('/objednavkyMenu') // nové API
        .then(response => response.json())
        .then(data => {
            const allowedStatuses = ['cekajici', 'objednano'];
            const counts = { cekajici: 0, objednano: 0 };

            const filteredOrders = data.filter(order => {
                const status = order.status.toLowerCase().trim();
                if (allowedStatuses.includes(status)) {
                    counts[status]++;
                    return true;
                }
                return false;
            });

            const cekaLoading = document.getElementById('loading-ceka');
            const objeLoading = document.getElementById('loading-obje');

            if (cekaLoading) cekaLoading.classList.add('d-none');
            if (objeLoading) objeLoading.classList.add('d-none');

            cekaCountElem.classList.remove('d-none');
            objedCountElem.classList.remove('d-none');

            cekaCountElem.textContent = counts.cekajici;
            objedCountElem.textContent = counts.objednano;

            filteredOrders.forEach(order => {
                const orderElement = document.createElement('div');
                orderElement.classList.add('order-item');
                orderElement.style.width = '250px';

                const img = order.obrazek
                    ? `<img src="/images/OOPP/${order.obrazek.trim()}" class="rounded-circle produck-circle"
                        alt="Obrázek produktu" style="width: 150px; height: 150px; object-fit: contain;">`
                    : 'Obrázek u produktu není';

                let statusText;
                switch (order.status.toLowerCase()) {
                    case 'cekajici':
                        statusText = 'Čekající';
                        break;
                    case 'objednano':
                        statusText = 'Objednáno';
                        break;
                    default:
                        statusText = order.status;
                }

                orderElement.innerHTML = `
                    ${img}
                    <div class="order-name">${order.jmeno} ${order.prijmeni}</div>
                    <div class="order-status fs-6 "><span>Status objednávky: </span><span class="text-cek"> ${statusText}</span></div>
                `;

                ordersContainer.appendChild(orderElement);
            });
        })
        .catch(error => console.error('Chyba při načítání objednávek:', error));

    fetchAndDisplayWeather().catch(error => {
        console.error("Kritická neošetřená chyba při inicializaci počasí:", error);
    });
    //Pro zobrazení roku a částky v kruh

     async function nacistStatistiky(rok = new Date().getFullYear()) {
         try {
             const response = await fetch(`/statistiky/souhrn?rok=${rok}`);
             const data = await response.json();

             // Aktualizace HTML elementů
             const castkaLoading = document.getElementById('loading-castka');
             const castkaElem = document.getElementById('celkova-castka');

             if (castkaLoading) castkaLoading.classList.add('d-none');
             if (castkaElem) {
                 castkaElem.classList.remove('d-none');
                 castkaElem.textContent = new Intl.NumberFormat('cs-CZ', {
                     style: 'currency',
                     currency: 'CZK'
                 }).format(data.celkove_vydaje);
             }
             
             document.getElementById('rok-zobrazeni').textContent = data.rok;


         } catch (error) {
             console.error('Chyba při načítání statistik:', error);
             document.getElementById('celkova-castka').textContent = 'Chyba načítání';
         }
     }
     nacistStatistiky().catch(error => {
         console.error("Chyba při načítání statistik:", error);
     });
}


document.addEventListener('DOMContentLoaded', menuoopp);
export { menuoopp };
