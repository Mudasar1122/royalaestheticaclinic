
'use strict';

window.royalUi = window.royalUi || {};

window.royalUi.resetActionDropdown = function (menu) {
  if (!(menu instanceof Element)) {
    return;
  }

  menu.classList.remove('open-up');
  menu.style.position = '';
  menu.style.top = '';
  menu.style.left = '';
  menu.style.right = '';
  menu.style.bottom = '';
  menu.style.minWidth = '';
  menu.style.maxWidth = '';
};

window.royalUi.placeActionDropdown = function (button, menu) {
  if (!(button instanceof Element) || !(menu instanceof Element)) {
    return;
  }

  var viewportPadding = 8;
  window.royalUi.resetActionDropdown(menu);

  var buttonRect = button.getBoundingClientRect();
  var menuRect = menu.getBoundingClientRect();
  var menuWidth = Math.max(Math.ceil(menuRect.width), Math.ceil(buttonRect.width));
  var menuHeight = Math.ceil(menuRect.height);
  var spaceBelow = Math.max(0, window.innerHeight - buttonRect.bottom - viewportPadding);
  var spaceAbove = Math.max(0, buttonRect.top - viewportPadding);
  var openUp = spaceBelow < menuHeight + 8 && spaceAbove > spaceBelow;
  var left = Math.min(
    Math.max(viewportPadding, window.innerWidth - menuWidth - viewportPadding),
    Math.max(viewportPadding, buttonRect.right - menuWidth)
  );
  var top = openUp
    ? Math.max(viewportPadding, buttonRect.top - menuHeight - 8)
    : Math.min(window.innerHeight - menuHeight - viewportPadding, buttonRect.bottom + 8);

  menu.style.position = 'fixed';
  menu.style.left = left + 'px';
  menu.style.top = top + 'px';
  menu.style.right = 'auto';
  menu.style.bottom = 'auto';
  menu.style.minWidth = menuWidth + 'px';
  menu.style.maxWidth = 'calc(100vw - 16px)';

  if (openUp) {
    menu.classList.add('open-up');
  }
};

window.royalUi.initStatusFlashes = function (root) {
  var scope = root instanceof Element || root instanceof Document ? root : document;

  scope.querySelectorAll('[data-royal-flash]').forEach(function (flash) {
    if (!(flash instanceof Element) || flash.dataset.flashBound === 'true') {
      return;
    }

    flash.dataset.flashBound = 'true';

    var dismissFlash = function () {
      if (flash.classList.contains('is-hiding')) {
        return;
      }

      flash.classList.add('is-hiding');

      window.setTimeout(function () {
        flash.remove();
      }, 380);
    };

    var closeButton = flash.querySelector('[data-royal-flash-close]');
    var duration = parseInt(flash.getAttribute('data-flash-duration') || '4200', 10);

    if (closeButton) {
      closeButton.addEventListener('click', dismissFlash);
    }

    window.setTimeout(dismissFlash, Number.isFinite(duration) ? duration : 4200);
  });
};

// sidebar submenu collapsible js
document.querySelectorAll(".sidebar-menu .dropdown").forEach(function (dropdown) {
  dropdown.addEventListener("click", function () {
    var item = this;

    // Close all sibling dropdowns
    item.parentNode.querySelectorAll(".dropdown").forEach(function (sibling) {
      if (sibling !== item) {
        sibling.querySelector(".sidebar-submenu").style.display = 'none';
        sibling.classList.remove("dropdown-open");
        sibling.classList.remove("open");
      }
    });

    // Toggle the current dropdown
    var submenu = item.querySelector(".sidebar-submenu");
    submenu.style.display = (submenu.style.display === 'block') ? 'none' : 'block';

    item.classList.toggle("dropdown-open");
  });
});

// Toggle sidebar visibility and active class
const sidebarToggle = document.querySelector(".sidebar-toggle");
if(sidebarToggle) {
  sidebarToggle.addEventListener("click", function() {
    this.classList.toggle("active");
    document.querySelector(".sidebar").classList.toggle("active");
    document.querySelector(".dashboard-main").classList.toggle("active");
  });
}

// Open sidebar in mobile view and add overlay
const sidebarMobileToggle = document.querySelector(".sidebar-mobile-toggle");
if(sidebarMobileToggle) {
  sidebarMobileToggle.addEventListener("click", function() {
    document.querySelector(".sidebar").classList.add("sidebar-open");
    document.body.classList.add("overlay-active");
  });
}

// Close sidebar and remove overlay
const sidebarColseBtn = document.querySelector(".sidebar-close-btn");
if(sidebarColseBtn){
  sidebarColseBtn.addEventListener("click", function() {
    document.querySelector(".sidebar").classList.remove("sidebar-open");
    document.body.classList.remove("overlay-active");
  });
}

//to keep the current page active
document.addEventListener("DOMContentLoaded", function () {
  window.royalUi.initStatusFlashes(document);

  var nk = window.location.href;
  var links = document.querySelectorAll("ul#sidebar-menu a");

  links.forEach(function (link) {
    if (link.href === nk) {
      link.classList.add("active-page"); // anchor
      var parent = link.parentElement;
      parent.classList.add("active-page"); // li

      // Traverse up the DOM tree and add classes to parent elements
      while (parent && parent.tagName !== "BODY") {
        if (parent.tagName === "LI") {
          parent.classList.add("show");
          parent.classList.add("open");
        }
        parent = parent.parentElement;
      }
    }
  });
});




// On page load or when changing themes, best to add inline in `head` to avoid FOUC
if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
  document.documentElement.classList.add('dark');
} else {
  document.documentElement.classList.remove('dark')
}

// light dark version js
var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

// Change the icons inside the button based on previous settings
if(themeToggleDarkIcon || themeToggleLightIcon){
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      themeToggleLightIcon.classList.remove('hidden');
  } else {
      themeToggleDarkIcon.classList.remove('hidden');
  }
}

var themeToggleBtn = document.getElementById('theme-toggle');

if(themeToggleDarkIcon || themeToggleLightIcon || themeToggleBtn){
  themeToggleBtn.addEventListener('click', function() {

    // toggle icons inside button
    themeToggleDarkIcon.classList.toggle('hidden');
    themeToggleLightIcon.classList.toggle('hidden');

    // if set via local storage previously
    if (localStorage.getItem('color-theme')) {
        if (localStorage.getItem('color-theme') === 'light') {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
        }

    // if NOT set via local storage previously
    } else {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('color-theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('color-theme', 'dark');
        }
    }
});
}
