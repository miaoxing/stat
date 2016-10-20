define(function () {
  var Stat = function () {

  };

  $.extend(Stat.prototype, {
    $el: $('body'),
    $: function (selector) {
      return this.$el.find(selector);
    },

    /**
     * 各图表的配置
     */
    charts: [],

    /**
     * 后台返回的二维数组数据
     */
    data: [],

    /**
     * 事件是否已绑定
     */
    bind: false,

    /**
     * 图表的默认配置
     */
    defaults: {
      chart: {
        type: 'line',
        height: 300
      },
      title: false,
      xAxis: {
        categoriesSource: 'statDate'
      },
      yAxis: {
        min: 0,
        title: false
      },
      plotOptions: {
        line: {
          dataLabels: false
        }
      },
      series: []
    },

    /**
     * 渲染图表
     */
    renderChart: function (options) {
      $.extend(this, options);

      this.charts = this.fillData(this.charts, this.data);

      this.initEvents();

      this.showChart(this.$('.js-chart-tabs .active a'));
    },

    initEvents: function () {
      var self = this;

      if (this.bind) {
        return;
      }
      this.bind = true;

      // 点击tab显示图表数据
      this.$('.js-chart-tabs a').on('shown.bs.tab', function () {
        self.showChart(this);
      });
    },

    /**
     * 展示指定标签对应的图表
     */
    showChart: function (link) {
      var target = $(link).attr('href');
      var id = target.substr(1);
      $.each(this.charts, function (key, chart) {
        if (chart.id == id) {
          $(target).highcharts(chart);
          return false;
        }
      });
    },

    /**
     * 根据图表配置加载数据
     */
    fillData: function (charts, data) {
      $.each(charts, $.proxy(function (key, chart) {
        chart = $.extend({}, this.defaults, chart);

        // 载入横坐标数据
        if (chart.xAxis.categoriesSource) {
          chart.xAxis.categories = this.getCol(data, chart.xAxis.categoriesSource);
        }

        $.each(chart.series, $.proxy(function (key, row) {
          // 载入数据列数据
          if (row.dataSource) {
            chart.series[key].data = this.getCol(data, row.dataSource)
          }
        }, this));

        charts[key] = chart;
      }, this));

      return charts;
    },

    /**
     * 获取某一列的数据
     */
    getCol: function (matrix, col) {
      var column = [];
      for (var i = 0; i < matrix.length; i++) {
        column.push(matrix[i][col]);
      }
      return column;
    }
  });

  return new Stat();
});
