define(['plugins/stat/libs/highcharts/highcharts'], function () {
  // 设置默认主题
  Highcharts.theme = {
    colors: [
      '#6fb3e0', // 蓝
      '#94B91E', // 绿
      '#EDAD78', // 橙
      '#FBE059', // 黄
      '#9EA1F3',
      '#D5F4AE',
      '#AEF4F0',
      '#78B8ED',
      '#F4CEAE',
      '#F4F0AE'
    ],
    //colors: ["#4A90E2", "#44B549", "#EBCB6B", "#BB7FB2", "#DA7D2A"],
    chart: {
      backgroundColor: 'rgba(255, 255, 255, 0)', // 设置背景透明
      style: {
        fontFamily: '微软雅黑'
      }
    },
    credits: {
      enabled: false
    },
    title: {
      style: {
        color: '#2679b5',
        fontWeight: 'bold',
        fontFamily: '微软雅黑, Trebuchet MS, Verdana, sans-serif',
        fontSize: '15px'
      }
    },
    subtitle: {
      style: {
        color: '#666666',
        fontWeight: 'bold',
        fontSize: '12px',
        fontFamily: '微软雅黑, Trebuchet MS, Verdana, sans-serif'
      }
    },
    xAxis: {
      tickmarkPlacement: 'on',
      lineColor: "#C6C6C6",
      labels: {
        style: {
          color: '#8D8D8D',
          font: '12px 微软雅黑, Trebuchet MS, Verdana, sans-serif'
        }
      },
      title: {
        style: {
          color: '#8D8D8D',
          fontWeight: 'bold',
          fontSize: '12px',
          fontFamily: '微软雅黑, Trebuchet MS, Verdana, sans-serif'

        }
      }
    },
    yAxis: {
      min: 0,
      allowDecimals: false,
      gridLineColor: "#eee",
      labels: {
        style: {
          color: '#8D8D8D',
          font: '12px 微软雅黑, Trebuchet MS, Verdana, sans-serif'
        }
      },
      title: {
        style: {
          color: '#333',
          fontWeight: 'normal',
          fontSize: '12px',
          fontFamily: '微软雅黑, Trebuchet MS, Verdana, sans-serif'
        }
      }
    },
    legend: {
      itemStyle: {
        font: '14px 微软雅黑, Trebuchet MS, Verdana, sans-serif',
        color: 'black'
      },
      itemHoverStyle: {
        color: '#039'
      },
      itemHiddenStyle: {
        color: 'gray'
      }
    },
    labels: {
      style: {
        color: '#99b'
      }
    },
    tooltip: {
      shared: true,
      crosshairs: {
        color: '#6fb3e0',
        dashStyle: 'shortdot'
      },
      backgroundColor: "#555",
      borderRadius: 0,
      borderWidth: 0,
      shadow: false,
      style: {
        fontSize: '12px',
        color: "#fff",
        fontWeight: 'normal',
        fontFamily: '微软雅黑, Trebuchet MS, Verdana, sans-serif'
      },
      headerFormat: '<span style="font-size: 12px;">{point.key}</span><br/><br/>'
    },
    plotOptions: {
      line: {
        dataLabels: {
          enabled: true
        }
      },
      series: {
        dataLabels: {
          color: '#6F6F6F',
          //useHTML: true,
          style: {
            fontSize: '12px',
            fontFamily: "微软雅黑"
          }
        }
      },
      column: {
        borderWidth: 0,
        shadow: false,
        dataLabels: {
          enabled: true
        }
      },
      pie: {
        shadow: false,
        useHTML: true,
        dataLabels: {
          color: '#6F6F6F'
        }
      },
      bar: {
        shadow: false,
        dataLabels: {
          enabled: true
        }
      }
    }
  };
  Highcharts.setOptions(Highcharts.theme);

  /**
   * @deprecated 缺少扩展性
   */
  window.drawColumn = function (ID, width, height, title, xData, xSeries) {
    var chart = new Highcharts.Chart({
      //配置chart选项
      chart: {
        // width:width,
        // height:height,
        renderTo: ID,  //容器名，和body部分的div id要一致
        type: 'column'						//图表类型，这里选择折线图
      },
      //配置链接及名称选项
      credits: {
        enabled: false
      },
      //配置标题
      title: {
        style: {'color': '#26629E', 'font-size': '14'},
        text: title,
        x: 10,
        y: 10
      },
      //配置x轴
      xAxis: {
        categories: xData
      },
      // 配置y轴
      yAxis: {
        title: {
          text: ''
        },
        labels: {
          formatter: function () {
            return this.value
          }
        }
      },
      //配置数据点提示框
      tooltip: {
        crosshairs: true,
        shared: true
      },
      //配置数据使其点显示信息
      plotOptions: {
        spline: {
          dataLabels: {
            enabled: true
          },
          enableMouseTracking: true
        }
      },
      exporting: {
        enabled: false
      },
      legend: {
        enabled: false
      },
      //配置数据列
      series: xSeries
    });
  };

  /**
   * @deprecated 缺少扩展性
   */
  window.drawLine = function (ID, X, ySeries, title) {
    $('#' + ID).highcharts({
      title: {
        text: title,
        x: -20 //center
      },
      xAxis: {
        categories: X
      },
      yAxis: {
        plotLines: [
          {
            value: 0,
            width: 1,
            color: '#808080'
          }
        ],
        min: 0,
        allowDecimals: false,
        title: {
          text: ''
        }
      },
      tooltip: {
        valueSuffix: ''
      },
      legend: {
        layout: 'vertical',
        align: 'center',
        verticalAlign: 'top',
        y: 10,
        borderWidth: 0
      },
      exporting: {
        enabled: true
      },
      credits: {
        enabled: false
      },
      series: ySeries
    });
  }
});
