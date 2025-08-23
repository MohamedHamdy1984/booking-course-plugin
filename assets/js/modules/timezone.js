/**
 * SOOB Timezone utility (UMD)
 * - Detects client IANA timezone via Intl API
 * - Validates against IANA list (Intl.supportedValuesOf or localized fallback)
 * - Optionally persists to backend via REST with nonce
 * - Exposes getTimezone(), setTimezoneOverride(), clearOverride()
 *
 * Namespaced global: window.SOOBTimezone
 *
 * Security:
 * - Uses REST X-WP-Nonce (localized as soob_tz.nonce)
 * - Only posts when explicitly called (no automatic data sharing)
 *
 * i18n: No user-facing strings here.
 */
(function (root, factory) {
  if (typeof define === 'function' && define.amd) {
    define([], factory);
  } else if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    root.SOOBTimezone = factory();
  }
}(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  var LS_OVERRIDE_KEY = 'soob_tz_override';
  var LS_OVERRIDE_MODE = 'soob_tz_override_mode';

  /**
   * Safe access to localized config injected by WordPress enqueue
   * Expected shape:
   * window.soob_tz = {
   *   supported: string[],      // fallback IANA list
   *   rest_url: string,         // REST endpoint URL
   *   nonce: string             // X-WP-Nonce for REST
   * }
   */
  function getConfig() {
    if (typeof window !== 'undefined' && window.soob_tz) {
      return window.soob_tz;
    }
    return { supported: ['UTC'], rest_url: '', nonce: '' };
  }

  /**
   * Get supported IANA list from Intl or fallback localized list
   */
  function getSupportedIANA() {
    try {
      if (typeof Intl !== 'undefined' && typeof Intl.supportedValuesOf === 'function') {
        var vals = Intl.supportedValuesOf('timeZone');
        if (Array.isArray(vals) && vals.length > 0) {
          return vals;
        }
      }
    } catch (e) {
      // ignore
    }
    var cfg = getConfig();
    return Array.isArray(cfg.supported) && cfg.supported.length ? cfg.supported : ['UTC'];
  }

  /**
   * Validate timezone string against supported IANA list
   */
  function validate(tz) {
    if (!tz || typeof tz !== 'string') return false;
    var list = getSupportedIANA();
    return list.indexOf(tz) !== -1;
  }

  /**
   * Detect timezone using Intl API
   */
  function detect() {
    try {
      if (typeof Intl !== 'undefined' && Intl.DateTimeFormat) {
        var tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
        if (validate(tz)) return tz;
      }
    } catch (e) {
      // ignore
    }
    return 'UTC';
  }

  /**
   * Get effective timezone:
   * - If manual override exists in localStorage and valid, use it
   * - Else use detected timezone (validated)
   */
  function getTimezone() {
    try {
      var savedMode = localStorage.getItem(LS_OVERRIDE_MODE);
      var saved = localStorage.getItem(LS_OVERRIDE_KEY);
      if (savedMode === 'manual' && saved && validate(saved)) {
        return saved;
      }
    } catch (e) {
      // ignore
    }
    var tz = detect();
    return validate(tz) ? tz : 'UTC';
  }

  /**
   * Post timezone to backend via REST (explicit opt-in)
   * mode: 'auto' | 'manual'
   */
  async function postTimezone(tz, mode) {
    var cfg = getConfig();
    if (!cfg.rest_url || !cfg.nonce) return { success: false, posted: false };
    if (!validate(tz)) return { success: false, error: 'invalid_tz', posted: false };
    try {
      var res = await fetch(cfg.rest_url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': cfg.nonce,
        },
        credentials: 'same-origin',
        body: JSON.stringify({ timezone: tz, mode: mode || 'auto' })
      });
      if (!res.ok) {
        return { success: false, status: res.status, posted: true };
      }
      var data = await res.json();
      return { success: true, data: data, posted: true };
    } catch (e) {
      return { success: false, error: e && e.message ? e.message : 'network_error', posted: true };
    }
  }

  /**
   * Manually override timezone (persists to localStorage and optionally REST)
   */
  async function setTimezoneOverride(tz, alsoPost) {
    if (!validate(tz)) return { success: false, error: 'invalid_tz' };
    try {
      localStorage.setItem(LS_OVERRIDE_KEY, tz);
      localStorage.setItem(LS_OVERRIDE_MODE, 'manual');
    } catch (e) {
      // ignore storage failure
    }
    var result = { success: true };
    if (alsoPost) {
      result = await postTimezone(tz, 'manual');
    }
    return result;
  }

  /**
   * Clear manual override (revert to auto detection)
   */
  async function clearOverride(alsoPost) {
    try {
      localStorage.removeItem(LS_OVERRIDE_KEY);
      localStorage.setItem(LS_OVERRIDE_MODE, 'auto');
    } catch (e) {
      // ignore
    }
    var result = { success: true };
    if (alsoPost) {
      var tz = detect();
      result = await postTimezone(tz, 'auto');
    }
    return result;
  }

  return {
    getSupportedIANA: getSupportedIANA,
    validate: validate,
    detect: detect,
    getTimezone: getTimezone,
    setTimezoneOverride: setTimezoneOverride,
    clearOverride: clearOverride,
    postTimezone: postTimezone
  };
}));