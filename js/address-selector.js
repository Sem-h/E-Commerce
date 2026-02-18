/**
 * Türkiye İl/İlçe/Mahalle Cascading Selector
 * Usage: initAddressSelector(citySelectId, districtSelectId, [options])
 */
function initAddressSelector(cityId, districtId, options = {}) {
    const cityEl = document.getElementById(cityId);
    const districtEl = document.getElementById(districtId);
    if (!cityEl || !districtEl) return;

    const baseUrl = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/api/address.php';
    const preselectedCity = options.city || '';
    const preselectedDistrict = options.district || '';

    // İlleri yükle
    fetch(baseUrl + '?action=provinces')
        .then(r => r.json())
        .then(provinces => {
            cityEl.innerHTML = '<option value="">İl seçiniz...</option>';
            provinces.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p;
                opt.textContent = p;
                if (p === preselectedCity) opt.selected = true;
                cityEl.appendChild(opt);
            });
            // Pre-selected city varsa ilçeleri de yükle
            if (preselectedCity) {
                loadDistricts(preselectedCity, preselectedDistrict);
            }
        })
        .catch(() => {
            cityEl.innerHTML = '<option value="">Yüklenemedi</option>';
        });

    // İl değiştiğinde ilçeleri yükle
    cityEl.addEventListener('change', function () {
        const city = this.value;
        if (city) {
            loadDistricts(city, '');
        } else {
            districtEl.innerHTML = '<option value="">Önce il seçiniz...</option>';
        }
    });

    function loadDistricts(city, preselect) {
        districtEl.innerHTML = '<option value="">Yükleniyor...</option>';
        fetch(baseUrl + '?action=districts&city=' + encodeURIComponent(city))
            .then(r => r.json())
            .then(districts => {
                districtEl.innerHTML = '<option value="">İlçe seçiniz...</option>';
                districts.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d;
                    opt.textContent = d;
                    if (d === preselect) opt.selected = true;
                    districtEl.appendChild(opt);
                });
            })
            .catch(() => {
                districtEl.innerHTML = '<option value="">Yüklenemedi</option>';
            });
    }
}
