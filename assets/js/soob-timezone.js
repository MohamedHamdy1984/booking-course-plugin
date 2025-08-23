(function (root, factory) {
  if (typeof define === 'function' && define.amd) {
    define([], function () { return factory(root); });
  } else if (typeof module === 'object' && module.exports) {
    module.exports = factory(typeof globalThis !== 'undefined' ? globalThis : root);
  } else {
    root.SOOBTimezone = factory(root);
  }
}(typeof self !== 'undefined' ? self : this, function (root) {
  'use strict';

  // Default strings (can be overridden via window.soob_i18n.tz)
  var STRINGS = {
    DETECT_FAIL: 'Could not determine timezone automatically. Please select manually.'
  };
  if (root && root.soob_i18n && root.soob_i18n.tz && typeof root.soob_i18n.tz === 'object') {
    try {
      for (var k in root.soob_i18n.tz) {
        if (Object.prototype.hasOwnProperty.call(root.soob_i18n.tz, k)) {
          STRINGS[k] = root.soob_i18n.tz[k];
        }
      }
    } catch (e) {}
  }

  function detect() {
    try {
      if (typeof Intl !== 'undefined' && Intl.DateTimeFormat) {
        var tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
        return tz || null;
      }
    } catch (e) {}
    return null;
  }

  function validate(selectEl, value) {
    if (!selectEl || !value) return false;
    var opts = selectEl.options;
    for (var i = 0; i < opts.length; i++) {
      if (opts[i].value === value) return true;
    }
    return false;
  }

  function triggerChange(selectEl) {
    if (!selectEl) return;
    var ev;
    try {
      ev = new Event('change', { bubbles: true });
    } catch (e) {
      ev = document.createEvent('Event');
      ev.initEvent('change', true, false);
    }
    selectEl.dispatchEvent(ev);
  }

  function closestByClassList(el, classNames) {
    if (!el) return null;
    var node = el;
    while (node && node !== document) {
      if (node.classList) {
        for (var i = 0; i < classNames.length; i++) {
          if (node.classList.contains(classNames[i])) return node;
        }
      }
      node = node.parentNode;
    }
    return null;
  }

  function showInlineAlert(selectEl, message) {
    if (!selectEl) return;
    clearInlineAlert(selectEl);
    var alert = document.createElement('div');
    // Use classes only (no id); styled via CSS
    alert.className = 'soob-tz-inline-notice soob-notice soob-notice-warning';
    alert.setAttribute('role', 'alert');
    alert.setAttribute('aria-live', 'polite');
    alert.textContent = message || STRINGS.DETECT_FAIL;

    var wrapper = closestByClassList(selectEl, ['form-field', 'field', 'soob-field', 'soob-row']);
    if (wrapper) {
      wrapper.appendChild(alert);
    } else if (selectEl.parentNode) {
      selectEl.parentNode.insertBefore(alert, selectEl.nextSibling);
    }
  }

  function clearInlineAlert(selectEl) {
    var nodes = document.querySelectorAll('.soob-tz-inline-notice');
    if (!nodes || !nodes.length) return;
    for (var i = 0; i < nodes.length; i++) {
      if (nodes[i] && nodes[i].parentNode) {
        nodes[i].parentNode.removeChild(nodes[i]);
      }
    }
  }

  function applyToSelect(selectEl, opts) {
    opts = opts || {};
    var overrideIfEmpty = (opts.overrideIfEmpty !== false);
    var overrideIfUTC = (opts.overrideIfUTC !== false);

    if (!selectEl) return;

    var currentValue = selectEl.value;
    var shouldOverride = false;
    if ((!currentValue || currentValue === '') && overrideIfEmpty) {
      shouldOverride = true;
    } else if (currentValue === 'UTC' && overrideIfUTC) {
      shouldOverride = true;
    }

    if (!shouldOverride) {
      return;
    }

    var tz = detect();
    if (!tz) {
      if (typeof opts.onFail === 'function') { try { opts.onFail(); } catch (e) {} }
      return;
    }

    if (!validate(selectEl, tz)) {
      if (typeof opts.onFail === 'function') { try { opts.onFail(); } catch (e) {} }
      return;
    }

    if (typeof opts.onBeforeSet === 'function') {
      try { opts.onBeforeSet(tz); } catch (e) {}
    }

    selectEl.value = tz;
    triggerChange(selectEl);
    clearInlineAlert(selectEl);

    if (typeof opts.onAfterSet === 'function') {
      try { opts.onAfterSet(tz); } catch (e) {}
    }
  }

  var api = {
    STRINGS: STRINGS,
    detect: detect,
    validate: validate,
    applyToSelect: applyToSelect,
    ui: {
      showInlineAlert: showInlineAlert,
      clearInlineAlert: clearInlineAlert
    }
  };

  return api;
}));