/**
 * Vitrinin istemci tarafı davranışları.
 * Kaynak: CategoryFilterContext.tsx, ToggleImage.tsx, MobileNavigation.tsx,
 * ProductGallery.tsx, OrderForm.tsx
 */
(function () {
  'use strict';

  var CATEGORY_STORAGE_KEY = 'mg:selected-category';
  var CART_STORAGE_KEY = 'mg:cart:v1';

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

  /* ---------- Yerel sepet ---------- */

  function readCart() {
    try {
      var parsed = JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || '[]');
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      return [];
    }
  }

  function writeCart(items) {
    try {
      localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items));
    } catch (error) {
      /* private mode / kota: arayüz çalışmaya devam eder */
    }
    refreshCartCount(items);
  }

  function cartItemKey(item) {
    /* Varyant kimliği varsa onu kullan; eski sepetlerde yalnızca ad bulunur. */
    return [
      item.product_id,
      item.size_id || item.size || '',
      item.color_id || item.color || ''
    ].join('|');
  }

  function refreshCartCount(items) {
    var count = (items || readCart()).reduce(function (sum, item) {
      return sum + Math.max(1, Number(item.quantity) || 1);
    }, 0);

    all('[data-cart-count]').forEach(function (badge) {
      badge.textContent = String(count);
      badge.hidden = count === 0;
    });
  }

  function money(amount, currency) {
    try {
      return new Intl.NumberFormat(document.documentElement.lang || 'tr', {
        style: 'currency',
        currency: currency || 'TRY',
        minimumFractionDigits: 2
      }).format(Number(amount) || 0);
    } catch (error) {
      return (Number(amount) || 0).toFixed(2) + ' ' + (currency || '');
    }
  }

  function cartToast(message) {
    var toast = document.createElement('div');
    toast.className = 'storefront-cart-toast';
    toast.textContent = message;
    toast.setAttribute('role', 'status');
    document.body.appendChild(toast);
    requestAnimationFrame(function () { toast.classList.add('visible'); });
    window.setTimeout(function () {
      toast.classList.remove('visible');
      window.setTimeout(function () { toast.remove(); }, 250);
    }, 2200);
  }

  /* ---------- Ürün seçimi → sepete ekle ---------- */

  function initOrderForm() {
    var form = document.querySelector('[data-order-form]');
    if (!form) return;

    var config = JSON.parse(form.getAttribute('data-order-config') || '{}');
    var submit = form.querySelector('.whatsapp-order');
    var note = form.querySelector('[data-order-note]');
    var selectedColor = '';
    var selectedColorId = '';
    var selectedSize = '';
    var selectedSizeId = '';
    var sizeButtons = all('[data-size]', form);
    var colorButtons = all('[data-color]', form);

    function refresh() {
      var canSubmit = (!colorButtons.length || selectedColor) && (!sizeButtons.length || selectedSize);
      submit.disabled = !canSubmit;
      note.textContent = canSubmit ? config.ready : config.select;
    }

    colorButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        selectedColor = button.getAttribute('data-color');
        selectedColorId = button.getAttribute('data-color-id') || '';
        colorButtons.forEach(function (item) {
          var active = item === button;
          item.classList.toggle('selected', active);
          item.setAttribute('aria-pressed', String(active));
        });
        refresh();
      });
    });

    sizeButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        selectedSize = button.getAttribute('data-size');
        selectedSizeId = button.getAttribute('data-size-id') || '';
        sizeButtons.forEach(function (item) {
          var active = item === button;
          item.classList.toggle('selected', active);
          item.setAttribute('aria-pressed', String(active));
        });
        refresh();
      });
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (submit.disabled) return;

      var quantityInput = form.querySelector('[name=quantity]');
      var quantity = Math.min(99, Math.max(1, Number(quantityInput && quantityInput.value) || 1));
      var item = {
        product_id: form.getAttribute('data-product-id'),
        slug: form.getAttribute('data-product-slug'),
        name: form.getAttribute('data-product-name'),
        code: form.getAttribute('data-product-code'),
        price: Number(form.getAttribute('data-product-price')) || 0,
        currency: form.getAttribute('data-product-currency') || 'TRY',
        image: form.getAttribute('data-product-image') || '',
        size: selectedSize,
        size_id: selectedSizeId,
        color: selectedColor,
        color_id: selectedColorId,
        quantity: quantity
      };

      var items = readCart();
      var key = cartItemKey(item);
      var existing = items.find(function (candidate) { return cartItemKey(candidate) === key; });
      if (existing) existing.quantity = Math.min(99, Number(existing.quantity || 0) + quantity);
      else items.push(item);

      writeCart(items);
      cartToast(config.added || 'Ürün sepete eklendi.');
    });

    refresh();
  }

  function initCartPage() {
    var page = document.querySelector('[data-cart-page]');
    if (!page) return;

    var list = page.querySelector('[data-cart-items]');
    var empty = page.querySelector('[data-cart-empty]');
    var total = page.querySelector('[data-cart-total]');
    var payload = page.querySelector('[data-cart-payload]');
    var submit = page.querySelector('[data-checkout-submit]');
    var template = page.querySelector('[data-cart-row-template]');
    var form = page.querySelector('[data-checkout-form]');
    var submitted = false;

    /* Çift tıklamada ikinci istek hiç yola çıkmasın; sunucu tarafında da
       order_key ile aynı sipariş bir kez oluşturuluyor. */
    if (form) {
      form.addEventListener('submit', function (event) {
        if (submitted) {
          event.preventDefault();
          return;
        }
        submitted = true;
        submit.disabled = true;
      });
    }

    function render() {
      var items = readCart();
      list.innerHTML = '';
      show(empty, items.length === 0);
      submit.disabled = submitted || items.length === 0;

      var sum = 0;
      var currency = items[0] ? items[0].currency : 'TRY';

      items.forEach(function (item) {
        var fragment = template.content.cloneNode(true);
        var row = fragment.querySelector('.cart-line');
        var image = row.querySelector('img');
        var quantity = Math.min(99, Math.max(1, Number(item.quantity) || 1));
        var lineTotal = (Number(item.price) || 0) * quantity;
        sum += lineTotal;

        if (item.image) {
          image.src = item.image;
          image.alt = item.name || '';
        } else {
          row.querySelector('.cart-line-image').hidden = true;
        }
        row.querySelector('[data-line-code]').textContent = item.code || '';
        row.querySelector('[data-line-name]').textContent = item.name || '';
        row.querySelector('[data-line-options]').textContent = [item.size, item.color].filter(Boolean).join(' · ');
        row.querySelector('[data-line-total]').textContent = money(lineTotal, item.currency);
        row.querySelector('[data-cart-quantity]').value = String(quantity);

        row.querySelector('[data-cart-quantity]').addEventListener('change', function (event) {
          item.quantity = Math.min(99, Math.max(1, Number(event.target.value) || 1));
          writeCart(items);
          render();
        });
        row.querySelector('[data-cart-remove]').addEventListener('click', function () {
          writeCart(items.filter(function (candidate) {
            return cartItemKey(candidate) !== cartItemKey(item);
          }));
          render();
        });

        list.appendChild(fragment);
      });

      total.textContent = items.length ? money(sum, currency) : '—';
      payload.value = JSON.stringify(items.map(function (item) {
        return {
          product_id: item.product_id,
          size: item.size || null,
          size_id: item.size_id || null,
          color: item.color || null,
          color_id: item.color_id || null,
          quantity: Math.min(99, Math.max(1, Number(item.quantity) || 1))
        };
      }));
    }

    render();
  }

  function clearCartAfterOrder() {
    if (!document.querySelector('[data-order-success]')) return;
    writeCart([]);
  }

  function init() {
    initCategoryFilter();
    initToggleImages();
    initMobileNav();
    initGallery();
    initOrderForm();
    initCartPage();
    clearCartAfterOrder();
    refreshCartCount();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
