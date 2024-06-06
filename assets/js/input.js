(function ($) {
  var active_item;
  var item_width = 125;
  var item_height = 116 + 6;
  var recycled_items = [];

  jQuery(document).on('click', 'li[data-svg]', function () {
    var val = jQuery(this).attr('data-svg');
    active_item.find('input').val(val);
    active_item.find('.acf-svg-icon-picker__svg').html(
      '<img src="' +
      jQuery(this)
        .find('img')
        .attr('src') +
      '" alt=""/>'
    );
    jQuery('.acf-svg-icon-picker__popup-holder').trigger('close');
    jQuery('.acf-svg-icon-picker__popup-holder').remove();
    jQuery('.acf-svg-icon-picker__img input').trigger('change');

    active_item
      .parents('.acf-svg-icon-picker')
      .find('.acf-svg-icon-picker__remove')
      .addClass('acf-svg-icon-picker__remove--active');
  });

  function initialize_field($el) {
    $el.find('.acf-svg-icon-picker__img').on('click', function (e) {
      e.preventDefault();
      var is_open = true;
      active_item = $(this);

      if (acfSvgIconPicker.svgs.length == 0) {
        var list = '<p>' + acfSvgIconPicker.no_icons_msg + '</p>';
      } else {
        var list = `<ul id="icons-list">`;
        list += `</ul>`;
      }

      jQuery('body').append(
        `<div class="acf-svg-icon-picker__popup-holder">
        <div class="acf-svg-icon-picker__popup">
        <a class="acf-svg-icon-picker__popup__close" href="javascript:">close</a>
        <h4 class="acf-svg-icon-picker__popup__title">ACF Icon Picker - Choose icon</h4>
        <input class="acf-svg-icon-picker__filter" type="search" id="filterIcons" placeholder="Start typing to filter icons" />
          ${list}
        </div>
      </div>`
      );

      jQuery('.acf-svg-icon-picker__popup-holder').on('close', function () {
        is_open = false;
      });

      var $list = $('#icons-list');
      var margin = 200; // number of px to show above and below.
      var columns = 4;
      var svgs = acfSvgIconPicker.svgs;

      function setListHeight() {
        var total_lines = Math.ceil(svgs.length / columns);
        $list.height(total_lines * item_height);
      }

      function removeAllItems() {
        $('[data-acf-icon-index]').each(function (i, el) {
          var $el = $(el);
          recycled_items.push($el);
          $el.remove();
        });
      }

      function render() {
        if (!is_open) return;

        var scroll_top = $('.acf-svg-icon-picker__popup').scrollTop();
        var scroll_min = scroll_top - item_height - margin;
        var scroll_max = scroll_top + $('.acf-svg-icon-picker__popup').height() + margin;
        // Get the index of the first and last element from array we will show.
        var index_min = Math.ceil(scroll_min / item_height) * columns;
        var index_max = Math.ceil(scroll_max / item_height) * columns;

        // remove unneeded items and add them to recycled items.
        $('[data-acf-icon-index]').each(function (i, el) {
          var $el = $(el);
          var index = $el.attr('data-acf-icon-index');
          var name = $el.attr('data-svg');
          // Check if we have the element in the resulting array.
          var elementExist = function () {
            return svgs.find(function (svg) {
              return svg.name === name;
            });
          }

          if (index < index_min || index > index_max || !elementExist()) {
            recycled_items.push($el);
            $el.remove();
          }
        });

        for (var i = index_min; i < index_max; i++) {
          if (i < 0 || i >= svgs.length) continue;
          var svg = svgs[i];
          // Calculate the position of the item.
          var y = Math.floor(i / columns) * item_height;
          var x = i % columns * item_width;

          // If we already have the element visible we can continue
          var $el = $(`[data-acf-icon-index="${i}"][data-svg="${svg.name}"]`);
          // If item already exist we can skip.
          if ($el.length) continue;

          if (recycled_items.length) {
            // If there are recycled items reuse one.
            $el = recycled_items.pop();
          }
          else {
            // Or create a new element.
            $el = $(`<li>
              <div class="acf-svg-icon-picker__popup-svg">
                <img src="" alt=""/>
              </div>
              <span class="icons-list__name"></span>
            </li>`);
          }

          // We use attr instead of data since we want to use css selector.
          $el.attr({
            'data-svg': svg.name,
            'data-acf-icon-index': i
          }).css({
            transform: `translate(${x}px, ${y}px)`
          });
          const filename = svg['name'].split('.');
          const name = filename[0].replace(/[-_]/g, ' ');
          $el.find('.icons-list__name').text(name);
          $el.find('img').attr('src', `${acfSvgIconPicker.path}${svg['icon']}`);
          $list.append($el);
        }

        requestAnimationFrame(render);
      }
      if (svgs.length) {
        setListHeight();
        render();
      }

      const iconsFilter = document.querySelector('#filterIcons');

      function filterIcons(wordToMatch) {
        return acfSvgIconPicker.svgs.filter(icon => {
          var name = icon.name.replace(/[-_]/g, ' ');
          const regex = new RegExp(wordToMatch, 'gi');
          return name.match(regex);
        });
      }

      function displayResults() {
        svgs = filterIcons($(this).val());
        removeAllItems();
        setListHeight();
      }

      function debounce(func, wait) {
        var timeout;
        return function (...args) {
          clearTimeout(timeout);
          timeout = setTimeout(() => func.apply(this, args), wait);
        };
      }

      iconsFilter.focus();

      iconsFilter.addEventListener('keyup', debounce(displayResults, 300));

      // Closing
      jQuery('.acf-svg-icon-picker__popup__close').on('click', function (e) {
        e.stopPropagation();
        is_open = false;
        jQuery('.acf-svg-icon-picker__popup-holder').remove();
      });
    });

    // show the remove button if there is an icon selected
    const $input = $el.find('input')
    if ($input.length && $input.val().length != 0) {
      $el
        .find('.acf-svg-icon-picker__remove')
        .addClass('acf-svg-icon-picker__remove--active');
    }

    $el.find('.acf-svg-icon-picker__remove').on('click', function (e) {
      e.preventDefault();
      var parent = $(this).parents('.acf-svg-icon-picker');
      parent.find('input').val('');
      parent
        .find('.acf-svg-icon-picker__svg')
        .html('<span class="acf-svg-icon-picker__svg--span">+</span>');

      jQuery('.acf-svg-icon-picker__img input').trigger('change');

      parent
        .find('.acf-svg-icon-picker__remove')
        .removeClass('acf-svg-icon-picker__remove--active');
    });
  }

  if (typeof acf.add_action !== 'undefined') {
    acf.add_action('ready append', function ($el) {
      acf.get_fields({ type: 'svg_icon_picker' }, $el).each(function () {
        initialize_field($(this));
      });
    });
  }
})(jQuery);
