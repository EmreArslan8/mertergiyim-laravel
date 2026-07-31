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

    var emptyState = document.querySelector('[data-store-empty]');
    show(emptyState, visibleCount === 0);

    if (emptyState && visibleCount === 0) {
      var filtered = Boolean(selected);
      var title = emptyState.querySelector('[data-empty-title]');
      var description = emptyState.querySelector('[data-empty-description]');
      var reset = emptyState.querySelector('[data-empty-reset]');

      if (title) {
        title.textContent = title.getAttribute(filtered ? 'data-filter-text' : 'data-default-text');
      }
      if (description) {
        description.textContent = description.getAttribute(filtered ? 'data-filter-text' : 'data-default-text');
      }
      if (reset) reset.hidden = !filtered;
    }
  }

  function initCategoryFilter() {
    var selected = readSelectedCategory();
    applyCategoryFilter(selected);

    document.addEventListener('click', function (event) {
      var button = event.target.closest('[data-category-pick]');
      if (!button) return;

      event.preventDefault();
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
  }

  function initCategoryOverflow() {
    var pills = document.querySelector('[data-category-pills]');
    if (!pills) return;

    var items = all('[data-category-pill]', pills);
    var overflow = pills.querySelector('[data-category-overflow]');
    var trigger = pills.querySelector('[data-category-overflow-trigger]');
    var menu = pills.querySelector('[data-category-overflow-menu]');
    if (!items.length || !overflow || !trigger || !menu) return;

    function close() {
      trigger.setAttribute('aria-expanded', 'false');
      menu.hidden = true;
    }

    function fitItems() {
      close();
      items.forEach(function (item) {
        item.hidden = false;
      });
      overflow.hidden = true;
      menu.replaceChildren();

      var gap = parseFloat(window.getComputedStyle(pills).columnGap) || 0;
      var widths = items.map(function (item) {
        return item.getBoundingClientRect().width;
      });
      var required = widths.reduce(function (sum, width) {
        return sum + width;
      }, Math.max(0, items.length - 1) * gap);

      if (required <= pills.clientWidth) return;

      trigger.textContent = '+' + items.length;
      overflow.hidden = false;
      var overflowWidth = overflow.getBoundingClientRect().width;
      var available = Math.max(0, pills.clientWidth - overflowWidth - gap);
      var used = 0;
      var visibleCount = 0;
      var reachedLimit = false;

      widths.forEach(function (width, index) {
        var nextWidth = used + (visibleCount ? gap : 0) + width;
        if (!reachedLimit && nextWidth <= available) {
          used = nextWidth;
          visibleCount += 1;
        } else {
          reachedLimit = true;
          items[index].hidden = true;
        }
      });

      var hiddenItems = items.filter(function (item) {
        return item.hidden;
      });

      trigger.textContent = '+' + hiddenItems.length;
      hiddenItems.forEach(function (item) {
        var clone = item.cloneNode(true);
        clone.hidden = false;
        clone.removeAttribute('data-category-pill');
        menu.appendChild(clone);
      });
    }

    trigger.addEventListener('click', function () {
      var open = menu.hidden;
      menu.hidden = !open;
      trigger.setAttribute('aria-expanded', String(open));
    });

    menu.addEventListener('click', function (event) {
      if (event.target.closest('[data-category-pick]')) close();
    });

    document.addEventListener('click', function (event) {
      if (!overflow.contains(event.target)) close();
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') close();
    });

    if ('ResizeObserver' in window) {
      new ResizeObserver(fitItems).observe(pills);
    } else {
      window.addEventListener('resize', fitItems);
    }
    fitItems();
  }

  /* ---------- Mobil ürün kartı galerisi ---------- */

  function initProductCardGalleries() {
    all('[data-card-gallery]').forEach(function (gallery) {
      var slides = all('.product-card-slide', gallery);
      if (slides.length < 2) return;
      var dotContainer = gallery.parentElement.querySelector('[data-card-dots]');
      var dots = dotContainer ? all('i', dotContainer) : [];
      var touchStartX = 0;
      var touchStartY = 0;
      var touchStartIndex = 0;
      var suppressClickUntil = 0;

      function loadSlide(index) {
        var slide = slides[index];
        if (!slide) return;
        var image = slide.querySelector('img[data-src]');
        if (!image) return;
        image.src = image.getAttribute('data-src');
        image.removeAttribute('data-src');
      }

      function currentIndex() {
        return Math.round(Math.abs(gallery.scrollLeft) / Math.max(1, gallery.clientWidth));
      }

      function updateDots(index) {
        dots.forEach(function (dot, dotIndex) {
          dot.classList.toggle('selected', dotIndex === index);
        });
      }

      function goTo(index) {
        index = Math.max(0, Math.min(slides.length - 1, index));
        loadSlide(index);
        loadSlide(index + 1);
        gallery.scrollTo({
          left: index * gallery.clientWidth,
          behavior: 'smooth'
        });
        updateDots(index);
      }

      // Masaüstü hover yalnızca ikinci görseli hazırlar.
      gallery.addEventListener('mouseenter', function () {
        loadSlide(1);
      }, { once: true });

      // Native link/scroll davranışına güvenmeden yatay swipe'ı açıkça yönet.
      gallery.addEventListener('touchstart', function (event) {
        touchStartX = event.changedTouches[0].clientX;
        touchStartY = event.changedTouches[0].clientY;
        touchStartIndex = currentIndex();
        loadSlide(touchStartIndex - 1);
        loadSlide(touchStartIndex + 1);
      }, { passive: true });

      gallery.addEventListener('touchend', function (event) {
        var deltaX = event.changedTouches[0].clientX - touchStartX;
        var deltaY = event.changedTouches[0].clientY - touchStartY;

        if (Math.abs(deltaX) < 35 || Math.abs(deltaX) <= Math.abs(deltaY)) return;

        suppressClickUntil = Date.now() + 500;
        goTo(touchStartIndex + (deltaX < 0 ? 1 : -1));
      }, { passive: true });

      gallery.addEventListener('click', function (event) {
        if (Date.now() >= suppressClickUntil) return;
        event.preventDefault();
        event.stopPropagation();
      }, true);

      if ('IntersectionObserver' in window && window.matchMedia('(max-width: 767px)').matches) {
        var observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            var index = slides.indexOf(entry.target);
            loadSlide(index);
            loadSlide(index + 1);
          });
        }, { root: gallery, threshold: 0.08 });

        slides.slice(1).forEach(function (slide) {
          observer.observe(slide);
        });
      }

      var frame = null;
      gallery.addEventListener('scroll', function () {
        if (frame) cancelAnimationFrame(frame);
        frame = requestAnimationFrame(function () {
          var index = currentIndex();
          loadSlide(index);
          loadSlide(index + 1);
          updateDots(index);
        });
      }, { passive: true });
    });
  }

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
    document.body.classList.remove('mobile-menu-open');

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
    var categoryBack = nav.querySelector('[data-category-back]');

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
        document.body.classList.toggle('mobile-menu-open', open);
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

    if (categoryBack) {
      categoryBack.addEventListener('click', function () {
        show(categoryList, false);
        categoryToggle.setAttribute('aria-expanded', 'false');
        categoryToggle.classList.remove('open');
        categoryToggle.focus();
      });
    }

    all('a', mainMenu).forEach(function (link) {
      link.addEventListener('click', closeMobileMenus);
    });
    all('a', languageMenu).forEach(function (link) {
      link.addEventListener('click', closeMobileMenus);
    });
  }

  function initDesktopCategoryMenu() {
    var menu = document.querySelector('[data-desktop-category-menu]');
    if (!menu) return;

    var trigger = menu.querySelector('[data-desktop-category-trigger]');
    var dropdown = menu.querySelector('[data-desktop-category-dropdown]');

    function close() {
      dropdown.classList.remove('open');
      trigger.setAttribute('aria-expanded', 'false');
    }

    trigger.addEventListener('click', function () {
      var open = !dropdown.classList.contains('open');
      dropdown.classList.toggle('open', open);
      trigger.setAttribute('aria-expanded', String(open));
    });

    menu.addEventListener('mouseenter', function () {
      trigger.setAttribute('aria-expanded', 'true');
    });

    menu.addEventListener('mouseleave', function () {
      close();
    });

    document.addEventListener('click', function (event) {
      if (!menu.contains(event.target)) close();
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') close();
    });
  }

  function initTrackingForm() {
    var form = document.querySelector('[data-tracking-form]');
    if (!form) return;

    var submit = form.querySelector('[data-tracking-submit]');
    var label = form.querySelector('[data-tracking-submit-label]');

    form.addEventListener('submit', function () {
      if (!form.checkValidity() || !submit) return;
      submit.disabled = true;
      if (label && label.getAttribute('data-loading-text')) {
        label.textContent = label.getAttribute('data-loading-text');
      }
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

      // Büyüteç görselin dışına taşmadan köşelere kadar gidebilsin:
      // merkez, mercek yarı ölçüsü kadar içeride sınırlanır (sabit %28-72
      // sınırı köşelere ulaşmayı engelliyordu).
      var lensBounds = lens.getBoundingClientRect();
      var halfX = bounds.width ? (lensBounds.width / bounds.width) * 50 : 0;
      var halfY = bounds.height ? (lensBounds.height / bounds.height) * 50 : 0;

      lens.style.backgroundPosition = x + '% ' + y + '%';
      lens.style.left = Math.max(halfX, Math.min(100 - halfX, x)) + '%';
      lens.style.top = Math.max(halfY, Math.min(100 - halfY, y)) + '%';
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
    /* Toptan satışta beden seçilmez; aynı ürün ve renk tek sepet satırıdır. */
    return [
      item.product_id,
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
      var numericAmount = Number(amount) || 0;
      var fractionDigits = numericAmount % 1 === 0 ? 0 : 2;
      return new Intl.NumberFormat(document.documentElement.lang || 'tr', {
        style: 'currency',
        currency: currency || 'TRY',
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: 2
      }).format(numericAmount);
    } catch (error) {
      return (Number(amount) || 0).toFixed(2) + ' ' + (currency || '');
    }
  }

  function cartToast(message, productName, linkLabel, cartHref) {
    var toast = document.createElement('div');
    toast.className = 'storefront-cart-toast';
    toast.setAttribute('role', 'status');

    var title = document.createElement('strong');
    title.textContent = message;
    toast.appendChild(title);

    if (productName) {
      var product = document.createElement('span');
      product.textContent = productName;
      toast.appendChild(product);
    }

    var link = document.createElement('a');
    link.href = cartHref || '/';
    link.textContent = linkLabel || 'Sepete git';
    toast.appendChild(link);

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
    var submits = all('[data-order-submit]', form);
    var submit = submits[0];
    var note = form.querySelector('[data-order-note]');
    var selectedColors = [];
    var colorButtons = all('[data-color]', form);
    var quantityInput = form.querySelector('[name=quantity]');
    var quantityValues = all('[data-quantity-value]', form);
    var decreases = all('[data-quantity-decrease]', form);
    var increases = all('[data-quantity-increase]', form);
    var previousValues = all('[data-quantity-previous]', form);
    var nextValues = all('[data-quantity-next]', form);

    function setQuantity(value) {
      var quantity = Math.min(99, Math.max(1, Number(value) || 1));
      if (quantityInput) quantityInput.value = String(quantity);
      quantityValues.forEach(function (element) {
        element.textContent = String(quantity);
      });
      previousValues.forEach(function (element) {
        element.textContent = quantity > 1 ? String(quantity - 1) : '';
      });
      nextValues.forEach(function (element) {
        element.textContent = quantity < 99 ? String(quantity + 1) : '';
      });
    }

    function refresh() {
      var canSubmit = !colorButtons.length || selectedColors.length > 0;
      submits.forEach(function (button) {
        button.disabled = !canSubmit;
      });
      note.textContent = canSubmit ? config.ready : config.select;
    }

    colorButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        var color = {
          name: button.getAttribute('data-color') || '',
          id: button.getAttribute('data-color-id') || '',
          hex: button.getAttribute('data-color-hex') || ''
        };
        var index = selectedColors.findIndex(function (item) {
          return (item.id || item.name) === (color.id || color.name);
        });
        var active = index === -1;

        if (active) selectedColors.push(color);
        else selectedColors.splice(index, 1);

        button.classList.toggle('selected', active);
        button.setAttribute('aria-pressed', String(active));
        refresh();
      });
    });

    decreases.forEach(function (decrease) {
      decrease.addEventListener('click', function () {
        setQuantity((Number(quantityInput && quantityInput.value) || 1) - 1);
      });
    });
    increases.forEach(function (increase) {
      increase.addEventListener('click', function () {
        setQuantity((Number(quantityInput && quantityInput.value) || 1) + 1);
      });
    });

    /* Tek renkli ürünlerde gereksiz bir seçim adımı bırakma. */
    if (colorButtons.length === 1) colorButtons[0].click();

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (submit.disabled) return;

      var quantity = Math.min(99, Math.max(1, Number(quantityInput && quantityInput.value) || 1));
      var items = readCart();
      var colorsToAdd = selectedColors.length ? selectedColors : [{ name: '', id: '' }];

      colorsToAdd.forEach(function (color) {
        var item = {
          product_id: form.getAttribute('data-product-id'),
          slug: form.getAttribute('data-product-slug'),
          name: form.getAttribute('data-product-name'),
          code: form.getAttribute('data-product-code'),
          price: Number(form.getAttribute('data-product-price')) || 0,
          currency: form.getAttribute('data-product-currency') || 'TRY',
          image: form.getAttribute('data-product-image') || '',
          color: color.name,
          color_id: color.id,
          color_hex: color.hex,
          pack_size: Number(form.getAttribute('data-product-pack-size')) || 1,
          package_content: form.getAttribute('data-product-package-content') || '',
          package_content_source: form.getAttribute('data-product-package-content-source') || '',
          quantity: quantity
        };
        var key = cartItemKey(item);
        var existing = items.find(function (candidate) { return cartItemKey(candidate) === key; });

        if (existing) existing.quantity = Math.min(99, Number(existing.quantity || 0) + quantity);
        else items.push(item);
      });

      writeCart(items);
      cartToast(
        config.added || '',
        form.getAttribute('data-product-name') || '',
        config.goToCart || '',
        config.cartHref || '/'
      );
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
      show(total.closest('.cart-total-row'), items.length > 0, 'flex');
      submit.disabled = submitted || items.length === 0;

      var sum = 0;
      var currency = items[0] ? items[0].currency : 'TRY';

      items.forEach(function (item) {
        var fragment = template.content.cloneNode(true);
        var row = fragment.querySelector('.cart-line');
        var image = row.querySelector('img');
        var productHref = '/' + (document.documentElement.lang || 'tr') + '/product/' + (item.slug || '');
        var quantity = Math.min(99, Math.max(1, Number(item.quantity) || 1));
        var lineTotal = (Number(item.price) || 0) * quantity;
        sum += lineTotal;

        if (item.image) {
          image.src = item.image;
          image.alt = item.name || '';
        } else {
          row.querySelector('.cart-line-image').hidden = true;
        }
        all('[data-line-product-link]', row).forEach(function (link) {
          link.href = productHref;
        });
        row.querySelector('[data-line-code]').textContent = item.code ? 'KOD ' + item.code : '';
        row.querySelector('[data-line-name]').textContent = item.name || '';
        row.querySelector('[data-line-package-price]').textContent = money(Number(item.price) || 0, item.currency);
        row.querySelector('[data-line-color]').textContent = item.color || '—';
        row.querySelector('[data-line-color-dot]').style.backgroundColor = item.color_hex || '#fff';
        row.querySelector('[data-cart-quantity]').textContent = String(quantity);

        var packSizeWrap = row.querySelector('[data-line-pack-size-wrap]');
        if (item.pack_size) row.querySelector('[data-line-pack-size]').textContent = String(item.pack_size);
        else packSizeWrap.hidden = true;

        var packageWrap = row.querySelector('[data-line-package-wrap]');
        if (item.package_content && ['database', 'fallback'].includes(item.package_content_source)) {
          row.querySelector('[data-line-package]').textContent = item.package_content;
        }
        else packageWrap.hidden = true;

        row.querySelector('[data-cart-decrease]').addEventListener('click', function () {
          item.quantity = Math.max(1, quantity - 1);
          writeCart(items);
          render();
        });
        row.querySelector('[data-cart-increase]').addEventListener('click', function () {
          item.quantity = Math.min(99, quantity + 1);
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

  /* ---------- Multimedya albümü ve erişilebilir görüntüleyici ---------- */

  function initMediaViewer() {
    var viewer = document.querySelector('[data-media-viewer]');
    var tiles = all('[data-media-post]');
    if (!viewer || !tiles.length) return;

    var panel = viewer.querySelector('.media-viewer-panel');
    var stage = viewer.querySelector('.media-viewer-stage');
    var asset = viewer.querySelector('[data-media-asset]');
    var title = viewer.querySelector('[data-media-title]');
    var description = viewer.querySelector('[data-media-description]');
    var counter = viewer.querySelector('[data-media-counter]');
    var thumbnails = viewer.querySelector('[data-media-thumbnails]');
    var previous = viewer.querySelector('[data-media-previous]');
    var next = viewer.querySelector('[data-media-next]');
    var closeButton = viewer.querySelector('.media-viewer-close');
    var posts = [];
    var postIndex = 0;
    var fileIndex = 0;
    var lastFocused = null;
    var closeTimer = null;
    var touchStartX = 0;

    tiles.forEach(function (tile) {
      var source = tile.querySelector('[data-media-post-data]');
      if (!source) return;

      try {
        posts.push(JSON.parse(source.textContent));
      } catch (error) {
        posts.push(null);
      }
    });

    function currentPost() {
      return posts[postIndex] || null;
    }

    function currentFile() {
      var post = currentPost();
      return post && post.files ? post.files[fileIndex] : null;
    }

    function makeAsset(file, post) {
      var element;

      if (file.type === 'video') {
        element = document.createElement('video');
        element.src = file.url;
        element.controls = true;
        element.autoplay = true;
        element.playsInline = true;
        element.setAttribute('aria-label', file.alt || post.title || '');
        return element;
      }

      if (file.type === 'document') {
        element = document.createElement('a');
        element.className = 'media-viewer-document';
        element.href = file.url;
        element.target = '_blank';
        element.rel = 'noreferrer';
        element.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v6h5M10 14h6M10 18h4" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        var label = document.createElement('span');
        label.textContent = viewer.getAttribute('data-label-open-document') || '';
        element.appendChild(label);
        return element;
      }

      element = document.createElement('img');
      element.src = file.url;
      element.alt = file.alt || post.title || '';
      element.decoding = 'async';
      return element;
    }

    function renderAsset() {
      var post = currentPost();
      var file = currentFile();
      if (!post || !file) return;

      var playingVideo = asset.querySelector('video');
      if (playingVideo) playingVideo.pause();

      asset.replaceChildren(makeAsset(file, post));
      title.textContent = post.title || '';
      description.textContent = post.description || '';
      counter.textContent = String(fileIndex + 1).padStart(2, '0') + ' / ' + String(post.files.length).padStart(2, '0');

      var multiple = post.files.length > 1;
      previous.hidden = !multiple;
      next.hidden = !multiple;

      all('.media-viewer-thumb', thumbnails).forEach(function (thumb, index) {
        var active = index === fileIndex;
        thumb.classList.toggle('is-active', active);
        thumb.setAttribute('aria-current', active ? 'true' : 'false');
      });
    }

    function buildThumbnails() {
      var post = currentPost();
      thumbnails.replaceChildren();
      if (!post || post.files.length < 2) {
        thumbnails.hidden = true;
        return;
      }

      thumbnails.hidden = false;
      post.files.forEach(function (file, index) {
        var thumb = document.createElement('button');
        thumb.type = 'button';
        thumb.className = 'media-viewer-thumb';
        thumb.setAttribute('aria-label', (post.title || '') + ' ' + (index + 1));

        if (file.type === 'image') {
          var image = document.createElement('img');
          image.src = file.url;
          image.alt = '';
          image.loading = 'lazy';
          thumb.appendChild(image);
        } else if (file.type === 'video') {
          var video = document.createElement('video');
          video.src = file.url;
          video.muted = true;
          video.playsInline = true;
          video.preload = 'metadata';
          thumb.appendChild(video);
        } else {
          var documentLabel = document.createElement('span');
          documentLabel.textContent = 'PDF';
          thumb.appendChild(documentLabel);
        }

        thumb.addEventListener('click', function () {
          fileIndex = index;
          renderAsset();
        });
        thumbnails.appendChild(thumb);
      });
    }

    function move(direction) {
      var post = currentPost();
      if (!post || post.files.length < 2) return;
      fileIndex = (fileIndex + direction + post.files.length) % post.files.length;
      renderAsset();
    }

    function open(index, trigger) {
      if (!posts[index]) return;
      if (closeTimer) window.clearTimeout(closeTimer);

      postIndex = index;
      fileIndex = 0;
      lastFocused = trigger;
      buildThumbnails();
      renderAsset();
      viewer.hidden = false;
      document.body.classList.add('media-viewer-open');

      window.requestAnimationFrame(function () {
        viewer.classList.add('is-open');
        closeButton.focus({ preventScroll: true });
      });
    }

    function close() {
      if (viewer.hidden) return;
      viewer.classList.remove('is-open');
      document.body.classList.remove('media-viewer-open');

      var video = asset.querySelector('video');
      if (video) video.pause();

      closeTimer = window.setTimeout(function () {
        viewer.hidden = true;
        asset.replaceChildren();
        if (lastFocused) lastFocused.focus({ preventScroll: true });
      }, 240);
    }

    tiles.forEach(function (tile, index) {
      tile.addEventListener('click', function () {
        open(index, tile);
      });
    });

    all('[data-media-close]', viewer).forEach(function (button) {
      button.addEventListener('click', close);
    });
    previous.addEventListener('click', function () { move(-1); });
    next.addEventListener('click', function () { move(1); });

    viewer.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        close();
        return;
      }
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        move(document.documentElement.dir === 'rtl' ? 1 : -1);
        return;
      }
      if (event.key === 'ArrowRight') {
        event.preventDefault();
        move(document.documentElement.dir === 'rtl' ? -1 : 1);
        return;
      }
      if (event.key !== 'Tab') return;

      var focusable = all('button:not([hidden]), a[href], video[controls]', panel);
      if (!focusable.length) return;
      var first = focusable[0];
      var last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    stage.addEventListener('touchstart', function (event) {
      touchStartX = event.changedTouches[0].clientX;
    }, { passive: true });

    stage.addEventListener('touchend', function (event) {
      var distance = event.changedTouches[0].clientX - touchStartX;
      if (Math.abs(distance) < 48) return;
      move(distance > 0 ? -1 : 1);
    }, { passive: true });
  }

  function init() {
    initCategoryFilter();
    initCategoryOverflow();
    initProductCardGalleries();
    initToggleImages();
    initMobileNav();
    initDesktopCategoryMenu();
    initTrackingForm();
    initGallery();
    initOrderForm();
    initCartPage();
    initMediaViewer();
    clearCartAfterOrder();
    refreshCartCount();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
