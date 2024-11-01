(function ($) {
  let active_item;
  let isOpen = false;

  function initialize_field($el) {
    $el.find('.acf-svg-icon-picker__selector').on('click', function (e) {
      e.preventDefault();
      active_item = $(this);

      if (isOpen) {
        console.log('Popup is already open');
        return;
      }

      renderPopup();

      if (acfSvgIconPicker.svgs.length > 0) {
        renderIconsList();
      }

      setupFilter();

      // Closing
      document
        .querySelector('.acf-svg-icon-picker__popup-close')
        .addEventListener('click', function (e) {
          document.querySelector('.acf-svg-icon-picker__popup-overlay').remove();
          isOpen = false;
        });
    });

    // show the remove button if there is an icon selected
    const $input = $el.find('input');
    if ($input.length && $input.val().length != 0) {
      $el.find('.acf-svg-icon-picker__remove').addClass('acf-svg-icon-picker__remove--active');
    }

    $el.find('.acf-svg-icon-picker__remove').on('click', function (e) {
      e.preventDefault();
      const parent = $(this).parents('.acf-svg-icon-picker');
      parent.find('input').val('');
      parent.find('.acf-svg-icon-picker__icon').html('<span>+</span>');

      jQuery('.acf-svg-icon-picker__selector input').trigger('change');

      parent
        .find('.acf-svg-icon-picker__remove')
        .removeClass('acf-svg-icon-picker__remove--active');
    });
  }

  function renderIconsList(svgs = acfSvgIconPicker.svgs) {
    let popupContents = '';

    if (acfSvgIconPicker.svgs.length === 0) {
      popupContents = `<p>${acfSvgIconPicker.msgs.no_icons}</p>`;
    } else {
      const iconsList = svgs
        .map((svg) => {
          const filename = svg['name'].split('.');
          const name = filename[0].replace(/[-_]/g, ' ');
          const src = `${acfSvgIconPicker.path}${svg['icon']}`;

          return `
            <li data-svg="${svg['name']}">
              <img src="${src}" alt="${name}"/>
              <span>${name}</span>
            </li>
          `;
        })
        .join('');

      popupContents = `<ul>${iconsList}</ul>`;
    }

    document.querySelector('.acf-svg-icon-picker__popup-contents').innerHTML = popupContents;
  }

  function renderPopup() {
    const popup = `
      <div class="acf-svg-icon-picker__popup-overlay" style="--acfsip-columns: ${acfSvgIconPicker.columns};">
        <div class="acf-svg-icon-picker__popup">
          <div class="acf-svg-icon-picker__popup-header">
            <h4>${acfSvgIconPicker.msgs.title}</h4>
            <button class="acf-svg-icon-picker__popup-close" title="${acfSvgIconPicker.msgs.close}">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path></svg>
            </button>
            <input class="acf-svg-icon-picker__filter" type="search" id="filterIcons" placeholder="${acfSvgIconPicker.msgs.filter}" />
          </div>
          <div class="acf-svg-icon-picker__popup-contents">
            <!-- Icons rendered here -->
          </div>
        </div>
      </div>
    `;

    jQuery('body').append(popup);
    isOpen = true;

    jQuery('.acf-svg-icon-picker__popup-overlay').on('close', function () {
      jQuery('.acf-svg-icon-picker__popup-overlay').remove();
      isOpen = false;
    });
  }

  if (typeof acf.add_action !== 'undefined') {
    acf.add_action('ready append', function ($el) {
      acf.get_fields({ type: 'svg_icon_picker' }, $el).each(function () {
        initialize_field($(this));
      });
    });
  }

  function setupFilter() {
    const iconsFilter = document.querySelector('#filterIcons');

    function filterIcons(wordToMatch) {
      const normalizedWord = wordToMatch
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();
      return acfSvgIconPicker.svgs.filter((icon) => {
        const name = icon.name
          .replace(/[-_]/g, ' ')
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .toLowerCase();
        const regex = new RegExp(normalizedWord, 'gi');
        return name.match(regex);
      });
    }

    function displayResults() {
      svgs = filterIcons($(this).val());
      renderIconsList(svgs);
    }

    function debounce(func, wait) {
      let timeout;
      return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
      };
    }

    iconsFilter.focus();
    iconsFilter.addEventListener('keyup', debounce(displayResults, 300));
  }

  jQuery(document).on('click', 'li[data-svg]', function () {
    const val = jQuery(this).attr('data-svg');
    const src = jQuery(this).find('img').attr('src');
    active_item.find('input').val(val);
    active_item.find('.acf-svg-icon-picker__icon').html(`<img src="${src}" alt=""/>`);
    jQuery('.acf-svg-icon-picker__popup-overlay').trigger('close');
    jQuery('.acf-svg-icon-picker__popup-overlay').remove();
    jQuery('.acf-svg-icon-picker__img input').trigger('change');

    active_item
      .parents('.acf-svg-icon-picker')
      .find('.acf-svg-icon-picker__remove')
      .addClass('acf-svg-icon-picker__remove--active');
  });

  // Use MutationObserver to detect changes in the DOM
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.addedNodes.length) {
        $(mutation.addedNodes)
          .find('.acf-svg-icon-picker')
          .each(function () {
            initialize_field($(this));
          });
      }
    });
  });

  // Start observing the document body for changes
  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });
})(jQuery);
