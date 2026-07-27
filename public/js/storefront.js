/**
 * Vitrinin istemci tarafı davranışları.
 * Kaynak: CategoryFilterContext.tsx, ToggleImage.tsx, MobileNavigation.tsx,
 * ProductGallery.tsx, OrderForm.tsx
 */
(function () {
  'use strict';

  var CATEGORY_STORAGE_KEY = 'mg:selected-category';

  function all(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function show(element, visible, display) {
    if (element) element.style.display = visible ? (display || '') : 'none';
  }

  /* ---------- Kategori filtresi (CategoryFilterContext + CatalogSection) ---------- */

  function readSelectedCategory() {
    try {
      return sessionStorage.getItem(CATEGORY_STORAGE_KEY) || '';
    } catch (error) {
      return '';
    }
  }

  function writeSelectedCategory(value) {
    try {
      if (value) sessionStorage.setItem(CATEGORY_STORAGE_KEY, value);
      else sessionStorage.removeItem(CATEGORY_STORAGE_KEY);
    } catch (error) {
      /* private mode: filtre yalnızca bu sayfada geçerli olur */
    }
  }

  function applyCategoryFilter(selected) {
    all('[data-category-pick]').forEach(function (button) {
      button.classList.toggle('selected', button.getAttribute('data-category-pick') === selected);
    });

    var cards = all('[data-product-grid] .product-card');
    if (!cards.length) return;

    var visibleCount = 0;
    cards.forEach(function (card) {
      var matches = !selected || card.getAttribute('data-category-id') === selected;
      show(card, matches);
      if (matches) visibleCount += 1;
    });

    show(document.querySelector('[data-store-empty]'), visibleCount === 0);
  }

  function initCategoryFilter() {
    var selected = readSelectedCategory();
    applyCategoryFilter(selected);

    all('[data-category-pick]').forEach(function (button) {
      button.addEventListener('click', function () {
        var value = button.getAttribute('data-category-pick');
        writeSelectedCategory(value);

        var nav = document.querySelector('[data-mobile-nav]');
        var insideMenu = button.closest('[data-category-list]');

        // Menü içinden seçimde: menüyü kapat ve ürün listesine götür.
        if (insideMenu && nav) {
          closeMobileMenus();
          var locale = nav.getAttribute('data-locale');
          var target = '/' + locale + '#urunler';
          if (document.querySelector('[data-product-grid]')) {
            applyCategoryFilter(value);
            window.location.hash = 'urunler';
            return;
          }
          window.location.href = target;
          return;
        }

        applyCategoryFilter(value);
      });
    });
  }

  /* ---------- Ürün kartı görsel değiştirme (ToggleImage) ---------- */

  function initToggleImages() {
    all('[data-toggle-image]').forEach(function (element) {
      element.addEventListener('click', function () {
        element.classList.toggle('is-active');
      });
    });
  }

  /* ---------- Mobil menü (MobileNavigation) ---------- */

  function closeMobileMenus() {
    var nav = document.querySelector('[data-mobile-nav]');
    if (!nav) return;

    show(nav.querySelector('[data-language-menu]'), false);
    show(nav.querySelector('[data-main-menu]'), false);
    show(nav.querySelector('[data-category-list]'), false);

    var languageTrigger = nav.querySelector('[data-language-trigger]');
    var menuTrigger = nav.querySelector('[data-menu-trigger]');
    var categoryToggle = nav.querySelector('[data-category-toggle]');

    if (languageTrigger) languageTrigger.setAttribute('aria-expanded', 'false');
    if (menuTrigger) menuTrigger.setAttribute('aria-expanded', 'false');
    if (categoryToggle) {
      categoryToggle.setAttribute('aria-expanded', 'false');
      categoryToggle.classList.remove('open');
    }

    show(nav.querySelector('[data-menu-icon="open"]'), true);
    show(nav.querySelector('[data-menu-icon="close"]'), false);
  }

  function initMobileNav() {
    var nav = document.querySelector('[data-mobile-nav]');
    if (!nav) return;

    var languageTrigger = nav.querySelector('[data-language-trigger]');
    var languageMenu = nav.querySelector('[data-language-menu]');
    var menuTrigger = nav.querySelector('[data-menu-trigger]');
    var mainMenu = nav.querySelector('[data-main-menu]');
    var categoryToggle = nav.querySelector('[data-category-toggle]');
    var categoryList = nav.querySelector('[data-category-list]');

    function isOpen(element) {
      return element && element.style.display !== 'none';
    }

    if (languageTrigger) {
      languageTrigger.addEventListener('click', function () {
        var open = !isOpen(languageMenu);
        closeMobileMenus();
        show(languageMenu, open, 'block');
        languageTrigger.setAttribute('aria-expanded', String(open));
      });
    }

    if (menuTrigger) {
      menuTrigger.addEventListener('click', function () {
        var open = !isOpen(mainMenu);
        closeMobileMenus();
        show(mainMenu, open, 'grid');
        menuTrigger.setAttribute('aria-expanded', String(open));
        show(nav.querySelector('[data-menu-icon="open"]'), !open);
        show(nav.querySelector('[data-menu-icon="close"]'), open);
      });
    }

    if (categoryToggle) {
      categoryToggle.addEventListener('click', function () {
        var open = !isOpen(categoryList);
        show(categoryList, open, 'grid');
        categoryToggle.setAttribute('aria-expanded', String(open));
        categoryToggle.classList.toggle('open', open);
      });
    }

    all('a', mainMenu).forEach(function (link) {
      link.addEventListener('click', closeMobileMenus);
    });
    all('a', languageMenu).forEach(function (link) {
      link.addEventListener('click', closeMobileMenus);
    });
  }

  /* ---------- Ürün galerisi + büyüteç (ProductGallery) ---------- */

  function initGallery() {
    var wrap = document.querySelector('[data-zoom-wrap]');
    var main = document.querySelector('[data-zoom-main]');
    var lens = document.querySelector('[data-zoom-lens]');
    if (!wrap || !main || !lens) return;

    var lensVisible = false;

    function moveLens(event) {
      var bounds = wrap.getBoundingClientRect();
      var x = Math.max(0, Math.min(100, ((event.clientX - bounds.left) / bounds.width) * 100));
      var y = Math.max(0, Math.min(100, ((event.clientY - bounds.top) / bounds.height) * 100));

      if (event.pointerType === 'mouse') lensVisible = true;

      lens.style.backgroundPosition = x + '% ' + y + '%';
      lens.style.left = Math.max(28, Math.min(72, x)) + '%';
      lens.style.top = Math.max(28, Math.min(72, y)) + '%';
      lens.classList.toggle('visible', lensVisible);
    }

    wrap.addEventListener('pointerenter', moveLens);
    wrap.addEventListener('pointermove', moveLens);
    wrap.addEventListener('pointerdown', function (event) {
      if (event.pointerType !== 'mouse') {
        lensVisible = !lensVisible;
        lens.classList.toggle('visible', lensVisible);
      }
    });
    wrap.addEventListener('pointerleave', function () {
      lensVisible = false;
      lens.classList.remove('visible');
    });

    all('[data-thumb]').forEach(function (thumb) {
      thumb.addEventListener('click', function () {
        var source = thumb.getAttribute('data-thumb');
        main.src = source;
        lens.style.backgroundImage = 'url(' + source + ')';
        lensVisible = false;
        lens.classList.remove('visible');
        all('[data-thumb]').forEach(function (other) {
          other.classList.toggle('active', other === thumb);
        });
      });
    });
  }

  /* ---------- Sipariş formu → WhatsApp (OrderForm) ---------- */

  function initOrderForm() {
    var form = document.querySelector('[data-order-form]');
    if (!form) return;

    var config = JSON.parse(form.getAttribute('data-order-config') || '{}');
    var number = form.getAttribute('data-whatsapp');
    var submit = form.querySelector('.whatsapp-order');
    var note = form.querySelector('[data-order-note]');
    var selected = [];

    function refresh() {
      var canSubmit = selected.length > 0;
      submit.disabled = !canSubmit;
      note.textContent = canSubmit ? config.ready : config.select;
    }

    all('[data-color]', form).forEach(function (button) {
      button.addEventListener('click', function () {
        var color = button.getAttribute('data-color');
        var index = selected.indexOf(color);

        if (index === -1) selected.push(color);
        else selected.splice(index, 1);

        var active = selected.indexOf(color) !== -1;
        button.classList.toggle('selected', active);
        button.setAttribute('aria-pressed', String(active));
        refresh();
      });
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (!selected.length) return;

      var data = new FormData(form);
      var lines = [
        config.headline,
        config.colorLabel + ': ' + selected.join(', '),
        config.nameLabel + ': ' + (data.get('name') || ''),
        config.phoneLabel + ': ' + (data.get('phone') || ''),
        config.addressLabel + ': ' + (data.get('address') || ''),
        data.get('note') ? config.noteLabel + ': ' + data.get('note') : '',
      ].filter(Boolean);

      window.open('https://wa.me/' + number + '?text=' + encodeURIComponent(lines.join('\n')), '_blank');
    });

    refresh();
  }

  function init() {
    initCategoryFilter();
    initToggleImages();
    initMobileNav();
    initGallery();
    initOrderForm();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
