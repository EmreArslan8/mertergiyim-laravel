/**
 * Panel için küçük düzeltmeler.
 *
 * FilePond (görsel yükleme alanı) yüksekliğini kapsayıcısının genişliğinden
 * hesaplıyor. Sekmeli formlarda pasif sekmeler `position: absolute; height: 0`
 * ile gizlendiği için alan sıfır genişlikte ölçülüyor; sekmeye geçildiğinde
 * yeniden ölçüp açılıyor ve düzen "zıplıyordu". Sekme görünür olur olmaz bir
 * resize olayı gönderiliyor: FilePond kendini o anda doğru ölçüyor.
 */
(function () {
    const observers = new Set();
    let isNavigating = false;

    const remeasure = () => {
        if (!isNavigating) {
            window.dispatchEvent(new Event('resize'));
        }
    };

    const observeTabs = () => {
        document.querySelectorAll('.fi-sc-tabs').forEach((tabs) => {
            if (tabs.dataset.merterTabWatch === '1') {
                return;
            }

            tabs.dataset.merterTabWatch = '1';

            // Sekme değişimi sınıf değişikliğiyle oluyor; DOM'u izlemek
            // Alpine/Livewire sürümünden bağımsız çalışır.
            const observer = new MutationObserver(() => {
                if (isNavigating || !tabs.isConnected) {
                    return;
                }

                // İki kare bekle: sekme görünür olduktan sonra ölçülmeli.
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    if (tabs.isConnected) {
                        remeasure();
                    }
                }));
            });
            observer.observe(tabs, {
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'style', 'aria-selected'],
            });

            observers.add(observer);
        });
    };

    document.addEventListener('DOMContentLoaded', observeTabs);
    document.addEventListener('livewire:navigating', () => {
        isNavigating = true;
        observers.forEach((observer) => observer.disconnect());
        observers.clear();
    });
    document.addEventListener('livewire:navigated', () => {
        isNavigating = false;
        observeTabs();
    });
    document.addEventListener('livewire:initialized', observeTabs);
})();

/**
 * Hızlı Ekle: görsel/video sıralamasını sürükle-bırakla değiştirir.
 *
 * Panel dinamik (Livewire) olduğu için ızgara sonradan DOM'a giriyor; her
 * göründüğünde Sortable'a bir kez bağlanıyor. Bırakınca yeni sıra id listesi
 * olarak Livewire bileşenine (reorderImages) gönderiliyor; sunucu aynı sırayla
 * yeniden render ettiği için DOM'la sunucu tutarlı kalıyor.
 */
(function () {
    // Bağlı ızgaralar element referansıyla tutuluyor; DOM attribute'u DEĞİL.
    // Livewire re-render'da sunucu HTML'inde olmayan runtime attribute'ları
    // soyabilir; attribute'a güvenseydik aynı ızgaraya ikinci Sortable bağlanır,
    // olaylar çiftlenirdi. WeakSet element yaşadıkça hatırlar, GC'yi engellemez.
    const bound = new WeakSet();
    let scheduled = false;

    // Sürükle-bırak bittiğinde gerçek bir sıralama olduysa (öğe yer değiştirdi)
    // fare bırakılınca öğe üzerinde bir click olayı tetiklenir. Öğeler button +
    // wire:click="toggleImage" olduğu için bu click görseli seçer/seçimden
    // çıkarır — yani sıralamayla birlikte seçim de bozulur. Sıralamadan sonraki
    // ilk click bu bayrakla bastırılıyor; sonraki tıklar normal çalışır.
    let suppressClick = false;

    document.addEventListener('click', (e) => {
        if (!suppressClick) {
            return;
        }

        suppressClick = false;
        e.preventDefault();
        e.stopPropagation();
    }, true);

    const bindSortableGrids = () => {
        scheduled = false;

        if (!window.Sortable) {
            return;
        }

        document.querySelectorAll('[data-merter-sortable]').forEach((grid) => {
            if (bound.has(grid)) {
                return;
            }

            bound.add(grid);

            window.Sortable.create(grid, {
                animation: 150,
                draggable: '.merter-qa-pick',
                ghostClass: 'merter-qa-pick--ghost',
                // HTML5 native drag yerine Sortable'ın kendi sürüklemesi:
                // native drag touch cihazlarda hiç çalışmaz (dokunmatikte
                // dragstart olayı yok), button öğelerinde de tutarsızdır.
                // Fallback hem farede hem dokunmatikte aynı davranır.
                forceFallback: true,
                // Sürüklenen kare modaldan dışarı taşsa bile modalın
                // kaydırma kutusu tarafından kırpılmasın.
                fallbackOnBody: true,
                // Tık ile sürüklemeyi ayırır: 8px'den az hareket
                // "sürükleme" sayılmaz, düz tık seçim aynen çalışır.
                fallbackTolerance: 8,
                // Dokunmatikte önce basılı tut: sayfa kaydırmayla karışmasın.
                // Fareyle anında sürüklenir, tek tık seçim aynen çalışır.
                delay: 150,
                delayOnTouchOnly: true,
                onEnd: (event) => {
                    // Yerinde bırakıldıysa (sıra değişmedi) sunucuya gitme;
                    // o zaman trailing click de gerçek bir tıktır, seçimi yapar.
                    if (event.oldIndex === event.newIndex) {
                        return;
                    }

                    // Sıralama değişti: bırakma anındaki click'i bastır ki
                    // sürüklenen görsel seçimden düşmesin (ya da seçilmesin).
                    suppressClick = true;

                    const ids = Array.from(
                        event.to.querySelectorAll('.merter-qa-pick')
                    )
                        .map((el) => el.dataset.imageId)
                        .filter(Boolean);

                    const root = grid.closest('[wire\\:id]');

                    if (!root || !window.Livewire) {
                        return;
                    }

                    const component = window.Livewire.find(root.getAttribute('wire:id'));
                    component?.call('reorderImages', ids);
                },
            });
        });
    };

    // Body mutasyonları sık gelir (bildirim, morph...); bir kareye topluyoruz.
    const schedule = () => {
        if (scheduled) {
            return;
        }

        scheduled = true;
        requestAnimationFrame(bindSortableGrids);
    };

    // Modal açılıp kapandıkça ızgara DOM'a girip çıkıyor; body'yi izleyip yeni
    // eklenen ızgaraya bağlanıyoruz. Livewire re-render'ları da buradan yakalanır.
    const observer = new MutationObserver(schedule);

    const start = () => {
        bindSortableGrids();
        observer.observe(document.body, { childList: true, subtree: true });
    };

    document.addEventListener('DOMContentLoaded', start);
    document.addEventListener('livewire:navigated', bindSortableGrids);
})();
