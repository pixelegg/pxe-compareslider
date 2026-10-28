/*
 * pxe_compareslider – before/after image comparison.
 *
 * Dependency-free. Valid both as a classic (deferred) script and as an ES module: it
 * declares nothing global and only touches `.compareslider` elements. Each slider is
 * initialised once; sliders added to the DOM later are picked up automatically.
 *
 * Markup contract (see Compareslider.html):
 *   .compareslider[data-orientation=horizontal|vertical][data-start=0..100]
 *     .compareslider__handle[role=slider]
 * The position is written to the custom property --cs-pos (0–100) on the root element.
 */
(function () {
  'use strict';

  var ROOT_SELECTOR = '.compareslider';
  var HANDLE_SELECTOR = '.compareslider__handle';
  var INITIALISED = 'csInitialised';

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function init(root) {
    if (root.dataset[INITIALISED]) {
      return;
    }
    root.dataset[INITIALISED] = '1';

    var vertical = root.dataset.orientation === 'vertical';
    var handle = root.querySelector(HANDLE_SELECTOR);
    var position = clamp(parseFloat(root.dataset.start), 0, 100);
    if (isNaN(position)) {
      position = 50;
    }

    function apply() {
      root.style.setProperty('--cs-pos', String(position));
      if (handle) {
        var rounded = String(Math.round(position));
        handle.setAttribute('aria-valuenow', rounded);
        handle.setAttribute('aria-valuetext', rounded + ' %');
      }
    }

    function setFromPointer(clientX, clientY) {
      var rect = root.getBoundingClientRect();
      var ratio = vertical
        ? (clientY - rect.top) / rect.height
        : (clientX - rect.left) / rect.width;
      position = clamp(ratio * 100, 0, 100);
      apply();
    }

    var activePointer = null;

    root.addEventListener('pointerdown', function (event) {
      // Primary button / first touch only; ignore right click and multi-touch.
      if (event.button !== 0 || activePointer !== null) {
        return;
      }
      activePointer = event.pointerId;
      event.preventDefault();
      if (root.setPointerCapture) {
        try {
          root.setPointerCapture(event.pointerId);
        } catch (e) {
          // Pointer already released (e.g. synthetic events) – dragging still works via move/up.
        }
      }
      setFromPointer(event.clientX, event.clientY);
    });
    root.addEventListener('pointermove', function (event) {
      if (event.pointerId === activePointer) {
        setFromPointer(event.clientX, event.clientY);
      }
    });
    function release(event) {
      if (event.pointerId === activePointer) {
        activePointer = null;
      }
    }
    root.addEventListener('pointerup', release);
    root.addEventListener('pointercancel', release);
    root.addEventListener('lostpointercapture', release);

    if (handle) {
      handle.addEventListener('keydown', function (event) {
        var step = event.shiftKey ? 10 : 2;
        // Spatial mapping: the key moves the handle in the direction of the arrow.
        var decrease = vertical ? ['ArrowUp', 'ArrowLeft'] : ['ArrowLeft', 'ArrowDown'];
        var increase = vertical ? ['ArrowDown', 'ArrowRight'] : ['ArrowRight', 'ArrowUp'];
        if (decrease.indexOf(event.key) !== -1) {
          position = clamp(position - step, 0, 100);
        } else if (increase.indexOf(event.key) !== -1) {
          position = clamp(position + step, 0, 100);
        } else if (event.key === 'PageDown') {
          position = clamp(position - 10, 0, 100);
        } else if (event.key === 'PageUp') {
          position = clamp(position + 10, 0, 100);
        } else if (event.key === 'Home') {
          position = 0;
        } else if (event.key === 'End') {
          position = 100;
        } else {
          return;
        }
        event.preventDefault();
        apply();
      });
    }

    apply();
  }

  function boot() {
    document.querySelectorAll(ROOT_SELECTOR).forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  // Sliders rendered later (AJAX, tabs, accordions that re-render) are initialised too.
  if (typeof MutationObserver === 'function') {
    new MutationObserver(function (mutations) {
      for (var i = 0; i < mutations.length; i++) {
        var added = mutations[i].addedNodes;
        for (var j = 0; j < added.length; j++) {
          var node = added[j];
          if (node.nodeType !== 1) {
            continue;
          }
          if (node.matches && node.matches(ROOT_SELECTOR)) {
            init(node);
          } else if (node.querySelectorAll) {
            node.querySelectorAll(ROOT_SELECTOR).forEach(init);
          }
        }
      }
    }).observe(document.documentElement, { childList: true, subtree: true });
  }
})();
