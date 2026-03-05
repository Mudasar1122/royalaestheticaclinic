// Finance dashboard charts and interactions
(function () {
  function renderChart(selector, options) {
    var el = document.querySelector(selector);
    if (!el) {
      return;
    }
    var chart = new ApexCharts(el, options);
    chart.render();
  }

  var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  renderChart('#trendProgramChart', {
    series: [{
      name: 'Programs',
      data: [120, 140, 135, 160, 190, 210, 205, 230, 220, 240, 255, 270]
    }],
    chart: {
      height: 260,
      type: 'line',
      toolbar: { show: false },
      zoom: { enabled: false }
    },
    stroke: { curve: 'smooth', width: 3, colors: ['#487FFF'] },
    dataLabels: { enabled: false },
    grid: { borderColor: '#D1D5DB', strokeDashArray: 4 },
    xaxis: { categories: months },
    yaxis: {
      labels: {
        formatter: function (value) {
          return '$' + value + 'k';
        }
      }
    },
    tooltip: {
      y: {
        formatter: function (value) {
          return '$' + (value * 1000).toLocaleString();
        }
      }
    }
  });

  renderChart('#trendCoworkingChart', {
    series: [{
      name: 'Seats',
      data: [320, 350, 330, 370, 410, 430, 420, 460, 440, 480, 500, 520]
    }],
    chart: {
      height: 260,
      type: 'area',
      toolbar: { show: false },
      zoom: { enabled: false }
    },
    stroke: { curve: 'smooth', width: 3, colors: ['#45B369'] },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 0.4,
        opacityFrom: 0.4,
        opacityTo: 0.1,
        stops: [0, 100]
      }
    },
    dataLabels: { enabled: false },
    grid: { borderColor: '#D1D5DB', strokeDashArray: 4 },
    xaxis: { categories: months },
    yaxis: {
      labels: {
        formatter: function (value) {
          return Math.round(value);
        }
      }
    }
  });

  renderChart('#campusComparisonChart', {
    series: [{
      name: 'Revenue',
      data: [420, 360, 300, 280, 240]
    }],
    chart: {
      height: 260,
      type: 'bar',
      toolbar: { show: false }
    },
    plotOptions: {
      bar: { borderRadius: 6, horizontal: true }
    },
    dataLabels: { enabled: false },
    colors: ['#F59E0B'],
    grid: { borderColor: '#D1D5DB', strokeDashArray: 4 },
    xaxis: {
      categories: ['Campus Downtown', 'Campus North', 'Campus South', 'Campus East', 'Campus West'],
      labels: {
        formatter: function (value) {
          return '$' + value + 'k';
        }
      }
    },
    tooltip: {
      y: {
        formatter: function (value) {
          return '$' + (value * 1000).toLocaleString();
        }
      }
    }
  });

  renderChart('#franchiseComparisonChart', {
    series: [{
      name: 'Revenue',
      data: [310, 260, 240, 210, 190]
    }],
    chart: {
      height: 260,
      type: 'bar',
      toolbar: { show: false }
    },
    plotOptions: {
      bar: { borderRadius: 6, horizontal: true }
    },
    dataLabels: { enabled: false },
    colors: ['#7C3AED'],
    grid: { borderColor: '#D1D5DB', strokeDashArray: 4 },
    xaxis: {
      categories: ['Franchise West', 'Franchise North', 'Franchise South', 'Franchise East', 'Franchise Central'],
      labels: {
        formatter: function (value) {
          return '$' + value + 'k';
        }
      }
    },
    tooltip: {
      y: {
        formatter: function (value) {
          return '$' + (value * 1000).toLocaleString();
        }
      }
    }
  });

  var sections = document.querySelectorAll('[data-finance-section]');
  if (!sections.length) {
    return;
  }

  var tabs = document.querySelectorAll('[data-finance-role="tab"]');
  var cards = document.querySelectorAll('[data-finance-role="card"]');
  var title = document.getElementById('finance-details-title');

  function setActive(target, titleText) {
    sections.forEach(function (section) {
      section.classList.toggle('hidden', section.id !== target);
    });
    tabs.forEach(function (tab) {
      var isActive = tab.dataset.financeTarget === target;
      tab.classList.toggle('bg-primary-600', isActive);
      tab.classList.toggle('text-white', isActive);
      tab.classList.toggle('border-primary-600', isActive);
      tab.classList.toggle('bg-white', !isActive);
      tab.classList.toggle('text-neutral-600', !isActive);
    });
    cards.forEach(function (card) {
      var isActive = card.dataset.financeTarget === target;
      card.classList.toggle('ring-2', isActive);
      card.classList.toggle('ring-primary-600', isActive);
    });
    if (title && titleText) {
      title.textContent = titleText;
    }
  }

  function bind(elements) {
    elements.forEach(function (el) {
      el.addEventListener('click', function () {
        setActive(el.dataset.financeTarget, el.dataset.financeTitle);
      });
    });
  }

  bind(tabs);
  bind(cards);
  setActive('payable-details', 'Payable Details');
})();
