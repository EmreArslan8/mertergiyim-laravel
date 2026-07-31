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
    const remeasure = () => window.dispatchEvent(new Event('resize'));

    const observeTabs = () => {
        document.querySelectorAll('.fi-sc-tabs').forEach((tabs) => {
            if (tabs.dataset.merterTabWatch === '1') {
                return;
            }

            tabs.dataset.merterTabWatch = '1';

            // Sekme değişimi sınıf değişikliğiyle oluyor; DOM'u izlemek
            // Alpine/Livewire sürümünden bağımsız çalışır.
            new MutationObserver(() => {
                // İki kare bekle: sekme görünür olduktan sonra ölçülmeli.
                requestAnimationFrame(() => requestAnimationFrame(remeasure));
            }).observe(tabs, {
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'style', 'aria-selected'],
            });
        });
    };

    document.addEventListener('DOMContentLoaded', observeTabs);
    document.addEventListener('livewire:navigated', observeTabs);
    document.addEventListener('livewire:initialized', observeTabs);
})();
