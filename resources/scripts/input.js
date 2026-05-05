(function () {
  let activeItemEl;
  let activeAllowedGroups = null;
  let isOpen = false;
  let dialogEl = null;

  function initializeField(el) {
    // Guard against double-init from both acf.add_action('ready append', …)
    // and the MutationObserver fallback firing for the same element.
    if (el.dataset.acfsipInitialized === '1') {
      return;
    }
    el.dataset.acfsipInitialized = '1';

    const trigger = el.querySelector('.acf-svg-icon-picker__icon');
    const input = el.querySelector('input');
    const removeBtn = el.querySelector('.acf-svg-icon-picker__remove');

    if (trigger) {
      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        if (isOpen) {
          return;
        }
        activeItemEl = trigger.closest('.acf-svg-icon-picker__selector');
        // Per-field group allowlist from the field wrapper. Empty/missing → show all.
        const fieldWrapper = trigger.closest('.acf-svg-icon-picker');
        const allowed = fieldWrapper ? fieldWrapper.getAttribute('data-allowed-groups') : '';
        activeAllowedGroups = allowed
          ? allowed
              .split(',')
              .map((s) => s.trim())
              .filter(Boolean)
          : null;
        renderPopup();
        buildIconsList();
        setupFilter();
      });
    }

    // Show the remove button if there is an icon selected.
    if (input && input.value.length !== 0 && removeBtn) {
      removeBtn.classList.add('acf-svg-icon-picker__remove--active');
    }

    if (removeBtn) {
      removeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        const parent = removeBtn.closest('.acf-svg-icon-picker');
        if (!parent) {
          return;
        }
        const innerInput = parent.querySelector('input');
        const iconBtn = parent.querySelector('.acf-svg-icon-picker__icon');
        if (innerInput) {
          innerInput.value = '';
          innerInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (iconBtn) {
          iconBtn.innerHTML = '<span aria-hidden="true">&plus;</span>';
        }
        removeBtn.classList.remove('acf-svg-icon-picker__remove--active');
      });
    }
  }

  function escapeHtml(str) {
    return String(str).replace(
      /[&<>"']/g,
      (c) =>
        ({
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;',
        })[c],
    );
  }

  function normalize(str) {
    return String(str)
      .normalize('NFD')
      .replace(/[̀-ͯ]/g, '')
      .toLowerCase();
  }

  function renderIcon(key, svg) {
    // data-search is a pre-normalised lowercase, diacritic-stripped copy of
    // the title so applyFilter() can do a single string include() per tile
    // per keystroke. Computing it once here is much cheaper than normalising
    // every title on every keystroke.
    const search = normalize(svg.title);
    return `
      <li>
        <button
          type="button"
          class="acf-svg-icon-picker__option"
          data-svg="${escapeHtml(key)}"
          data-search="${escapeHtml(search)}"
          aria-label="${escapeHtml(svg.title)}"
          tabindex="-1"
        >
          <img src="${escapeHtml(svg.url)}" alt="" />
          <span aria-hidden="true">${escapeHtml(svg.title)}</span>
        </button>
      </li>
    `;
  }

  // Visible options only — keyboard nav skips tiles hidden by the active filter
  // (otherwise ArrowDown could jump to invisible tiles and "stick").
  function getOptions() {
    if (!dialogEl) {
      return [];
    }
    return Array.from(dialogEl.querySelectorAll('.acf-svg-icon-picker__option')).filter(
      (opt) => !opt.closest('li[hidden]'),
    );
  }

  // One sub-array per visible group <ul>. Each group is a self-contained grid
  // for the purposes of ArrowUp/ArrowDown — column position is preserved when
  // the user crosses into the adjacent group, rather than treating every tile
  // in the popup as one long linear list (which made ArrowDown from a partial
  // last row land deep into the next group at the wrong column). Filter-hidden
  // <ul>s and tiles are excluded.
  function getOptionGroups() {
    if (!dialogEl) {
      return [];
    }
    return Array.from(
      dialogEl.querySelectorAll('.acf-svg-icon-picker__popup-contents ul:not([hidden])'),
    )
      .map((ul) => Array.from(ul.querySelectorAll('li:not([hidden]) .acf-svg-icon-picker__option')))
      .filter((group) => group.length > 0);
  }

  // Roving tabindex: only one option is in the natural tab order so Tab from
  // the filter input lands on the grid once, then arrow keys move within it.
  function setRovingTabindex(focusedIdx = 0) {
    const options = getOptions();
    if (options.length === 0) {
      return;
    }
    const target = Math.max(0, Math.min(focusedIdx, options.length - 1));
    options.forEach((opt, i) => {
      opt.setAttribute('tabindex', i === target ? '0' : '-1');
    });
  }

  function getColumnCount(option) {
    const ul = option.closest('ul');
    if (!ul) {
      return 1;
    }
    const cols = window.getComputedStyle(ul).gridTemplateColumns;
    return cols.split(' ').filter(Boolean).length || 1;
  }

  function moveFocusTo(el) {
    if (!el) {
      return;
    }
    getOptions().forEach((opt) => {
      opt.setAttribute('tabindex', opt === el ? '0' : '-1');
    });
    el.focus();
  }

  // Resolve the focused element to its (group, row, col) position so the
  // vertical-nav code can reason about group boundaries.
  function findGroupCoords(activeEl, groups) {
    for (let g = 0; g < groups.length; g++) {
      const idx = groups[g].indexOf(activeEl);
      if (idx === -1) {
        continue;
      }
      const cols = getColumnCount(groups[g][0]);
      return {
        groupIdx: g,
        cols,
        row: Math.floor(idx / cols),
        col: idx % cols,
      };
    }
    return null;
  }

  function handleGridKeydown(e) {
    const options = getOptions();
    const activeEl = dialogEl.ownerDocument.activeElement;
    const flatIdx = options.indexOf(activeEl);
    if (flatIdx === -1) {
      return; // focus isn't inside the grid
    }

    let target = null;

    if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
      // Left/right is linear across the whole popup — natural reading order.
      const next = e.key === 'ArrowRight' ? flatIdx + 1 : flatIdx - 1;
      if (next >= 0 && next < options.length) {
        target = options[next];
      }
    } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      const groups = getOptionGroups();
      const coords = findGroupCoords(activeEl, groups);
      if (!coords) {
        return;
      }
      const { groupIdx, cols, row, col } = coords;
      const group = groups[groupIdx];
      const lastRow = Math.floor((group.length - 1) / cols);

      if (e.key === 'ArrowDown') {
        if (row < lastRow) {
          const next = (row + 1) * cols + col;
          target = group[Math.min(next, group.length - 1)];
        } else if (groupIdx + 1 < groups.length) {
          // Drop into the next group's first row at the same column. Fall back
          // to the last available column if the target group is narrower.
          const next = groups[groupIdx + 1];
          const nextCols = getColumnCount(next[0]);
          target = next[Math.min(col, nextCols - 1, next.length - 1)];
        }
      } else {
        if (row > 0) {
          target = group[(row - 1) * cols + col];
        } else if (groupIdx > 0) {
          // Climb into the previous group's last row at the same column.
          const prev = groups[groupIdx - 1];
          const prevCols = getColumnCount(prev[0]);
          const prevLastRow = Math.floor((prev.length - 1) / prevCols);
          const candidate = prevLastRow * prevCols + Math.min(col, prevCols - 1);
          target = prev[Math.min(candidate, prev.length - 1)];
        }
      }
    } else if (e.key === 'Home') {
      target = options[0];
    } else if (e.key === 'End') {
      target = options[options.length - 1];
    } else {
      return;
    }

    if (!target) {
      return;
    }
    e.preventDefault();
    moveFocusTo(target);
  }

  // Build the full icon DOM once on dialog open. Per-field allowlist is
  // applied here (it's per-dialog-open, never per-keystroke). The keystroke
  // filter then just toggles `hidden` on already-rendered `<li>`s — much
  // cheaper than rebuilding innerHTML on every input event, especially with
  // multi-thousand-icon sets.
  function buildIconsList() {
    const { svgs, groups } = acfSvgIconPicker;
    const container = dialogEl && dialogEl.querySelector('.acf-svg-icon-picker__popup-contents');
    if (!container) {
      return;
    }

    if (!svgs || Object.keys(svgs).length === 0) {
      // noIconsMsg is pre-translated and already contains a `<code>` tag —
      // intentionally not escaped.
      container.innerHTML = `<p>${acfSvgIconPicker.noIconsMsg}</p>`;
      return;
    }

    // Per-field allowlist: when set AND groups are configured, restrict the
    // visible groups (and the flat-svgs view) to icons in those groups. If
    // groups aren't configured at all (flat-mode site), ignore the allowlist
    // entirely and show every icon — better than failing closed when a
    // field's saved allowed_groups no longer match the live config.
    const groupsConfigured = Array.isArray(groups) && groups.length > 0;
    const useAllowlist = groupsConfigured && activeAllowedGroups;
    let visibleGroups = [];
    if (groupsConfigured) {
      visibleGroups = useAllowlist
        ? groups.filter((g) => activeAllowedGroups.includes(g.key))
        : groups;
    }

    const allowedKeySet = useAllowlist
      ? new Set(visibleGroups.flatMap((g) => g.icons || []))
      : null;

    const allowed = (key) => !allowedKeySet || allowedKeySet.has(key);

    let html = '';

    if (visibleGroups.length > 0) {
      html = visibleGroups
        .map((group) => {
          const allowedKeys = (group.icons || []).filter(allowed);
          if (allowedKeys.length === 0) {
            return '';
          }
          // Slugify for a valid HTML id; falls back to a stable index if the
          // group provides nothing usable.
          const slug = String(group.key || group.name || '')
            .toLowerCase()
            .replace(/[^a-z0-9_-]+/g, '-')
            .replace(/(^-|-$)/g, '');
          const headingId = `acfsip-group-${slug || 'unnamed'}`;
          const heading = group.name
            ? `<h3 id="${headingId}" class="acf-svg-icon-picker__group-heading" data-group="${escapeHtml(group.key || '')}">${escapeHtml(group.name)}</h3>`
            : '';
          // When there's no rendered heading, point at a label string instead
          // of an aria-labelledby pointing at a non-existent id.
          const listLabelAttr = group.name
            ? `aria-labelledby="${headingId}"`
            : `aria-label="${escapeHtml(String(group.key || 'Icons'))}"`;
          const list = allowedKeys.map((key) => renderIcon(key, svgs[key])).join('');
          return `${heading}<ul ${listLabelAttr}>${list}</ul>`;
        })
        .join('');
    } else {
      const keys = Object.keys(svgs).filter(allowed);
      html =
        keys.length > 0 ? `<ul>${keys.map((key) => renderIcon(key, svgs[key])).join('')}</ul>` : '';
    }

    container.innerHTML = html;
    applyFilter('');
  }

  // Toggle visibility on already-rendered tiles. Cheap: just sets the `hidden`
  // attribute and updates roving tabindex. Group headings (and their adjacent
  // <ul>) get hidden when none of their tiles match.
  function applyFilter(filter) {
    if (!dialogEl) {
      return;
    }
    const needle = normalize(filter);
    const lists = Array.from(dialogEl.querySelectorAll('.acf-svg-icon-picker__popup-contents ul'));

    lists.forEach((ul) => {
      const tiles = Array.from(ul.querySelectorAll('li'));
      let visible = 0;

      tiles.forEach((li) => {
        const btn = li.querySelector('.acf-svg-icon-picker__option');
        // data-search is set once at render time (lowercased + diacritic-stripped
        // title) so per-keystroke filtering is a single string include() — no
        // re-normalisation per tile per keystroke.
        const searchKey = btn ? btn.getAttribute('data-search') || '' : '';
        const match = !needle || searchKey.includes(needle);
        if (match) {
          li.removeAttribute('hidden');
          visible++;
        } else {
          li.setAttribute('hidden', '');
        }
      });

      // Hide the list (and its preceding heading, if any) when nothing matched.
      if (visible === 0) {
        ul.setAttribute('hidden', '');
        const heading = ul.previousElementSibling;
        if (heading && heading.classList.contains('acf-svg-icon-picker__group-heading')) {
          heading.setAttribute('hidden', '');
        }
      } else {
        ul.removeAttribute('hidden');
        const heading = ul.previousElementSibling;
        if (heading && heading.classList.contains('acf-svg-icon-picker__group-heading')) {
          heading.removeAttribute('hidden');
        }
      }
    });

    setRovingTabindex();
  }

  function renderPopup() {
    // Native <dialog> handles focus trap, Esc to close, focus restoration, and
    // making the page-behind inert when opened with .showModal(). The browser
    // also implies role="dialog" and aria-modal="true" — no need to set them.
    // Shell markup + i18n strings live in the PHP-rendered <template> so we
    // can clone instead of building innerHTML by hand.
    const template = document.getElementById('acfsip-dialog-template');
    if (!template) {
      return;
    }
    const fragment = template.content.cloneNode(true);
    dialogEl = fragment.querySelector('dialog');
    if (!dialogEl) {
      return;
    }

    document.body.appendChild(dialogEl);
    dialogEl.showModal();
    isOpen = true;

    // Close on backdrop click. The click target is the dialog itself when
    // the user clicks the dimmed area outside the popup body.
    dialogEl.addEventListener('click', function (e) {
      if (e.target === dialogEl) {
        dialogEl.close();
      }
    });

    // Close button.
    dialogEl
      .querySelector('.acf-svg-icon-picker__popup-close')
      .addEventListener('click', function () {
        dialogEl.close();
      });

    // Arrow-key navigation in the icon grid (roving tabindex).
    dialogEl.addEventListener('keydown', handleGridKeydown);

    // Native dialog already restores focus to the trigger on close. We only
    // need to clean up the DOM and reset state.
    dialogEl.addEventListener('close', function () {
      dialogEl.remove();
      dialogEl = null;
      isOpen = false;
    });

    // Pick an icon (delegated since options are rendered after the popup mounts).
    dialogEl.addEventListener('click', function (e) {
      const btn = e.target.closest('.acf-svg-icon-picker__option');
      if (!btn || !activeItemEl) {
        return;
      }
      const val = btn.getAttribute('data-svg');
      const img = btn.querySelector('img');
      const src = img ? img.getAttribute('src') : '';
      const input = activeItemEl.querySelector('input');
      const iconBtn = activeItemEl.querySelector('.acf-svg-icon-picker__icon');
      if (input) {
        input.value = val;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
      if (iconBtn) {
        iconBtn.innerHTML = `<img src="${src}" alt=""/>`;
      }
      const removeBtn = activeItemEl
        .closest('.acf-svg-icon-picker')
        ?.querySelector('.acf-svg-icon-picker__remove');
      if (removeBtn) {
        removeBtn.classList.add('acf-svg-icon-picker__remove--active');
      }
      dialogEl.close();
    });
  }

  // ACF integration: fires when fields render or are appended (repeaters etc).
  // `acf.get_fields(...)` returns a jQuery collection but `.each(this)` exposes
  // the raw DOM node, so we never reach for $() ourselves.
  if (typeof acf !== 'undefined' && typeof acf.add_action !== 'undefined') {
    acf.add_action('ready append', function ($el) {
      acf.get_fields({ type: 'svg_icon_picker' }, $el).each(function () {
        initializeField(this);
      });
    });
  }

  function setupFilter() {
    const iconsFilter = dialogEl.querySelector('.acf-svg-icon-picker__filter');
    if (!iconsFilter) {
      return;
    }

    function displayResults() {
      applyFilter(this.value);
    }

    function debounce(func, wait) {
      let timeout;
      return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
      };
    }

    iconsFilter.focus();
    // 'input' fires on every value change including paste, autocomplete,
    // the search field's clear button, and IME/composition events — broader
    // than 'keyup' which misses non-keyboard edits.
    iconsFilter.addEventListener('input', debounce(displayResults, 300));
  }

  // MutationObserver as a fallback for fields rendered outside the ACF lifecycle
  // (e.g. some block-editor flows). Skips text nodes and re-uses initializeField.
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType !== 1) {
          return;
        }
        if (node.matches?.('.acf-svg-icon-picker')) {
          initializeField(node);
        }
        node.querySelectorAll?.('.acf-svg-icon-picker').forEach(initializeField);
      });
    });
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });
})();
