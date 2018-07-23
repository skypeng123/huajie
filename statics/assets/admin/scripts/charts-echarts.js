jQuery(document).ready(function() {
    // ECHARTS
    require.config({
        paths: {
            echarts: statics_url+'assets/global/plugins/echarts/'
        }
    });

    // DEMOS
    require(
        [
            'echarts',
            'echarts/chart/bar',
            'echarts/chart/chord',
            'echarts/chart/eventRiver',
            'echarts/chart/force',
            'echarts/chart/funnel',
            'echarts/chart/gauge',
            'echarts/chart/heatmap',
            'echarts/chart/k',
            'echarts/chart/line',
            'echarts/chart/map',
            'echarts/chart/pie',
            'echarts/chart/radar',
            'echarts/chart/scatter',
            'echarts/chart/tree',
            'echarts/chart/treemap',
            'echarts/chart/venn',
            'echarts/chart/wordCloud'
        ],
        function(ec) {
            // -- SCATTER --
            var myChart3 = ec.init(document.getElementById('echarts_scatter'));
            myChart3.setOption({
                tooltip: {
                    trigger: 'item'
                },
                toolbox: {
                    show: true,
                    feature: {
                        mark: {
                            show: true
                        },
                        dataZoom: {
                            show: true
                        },
                        dataView: {
                            show: true,
                            readOnly: false
                        },
                        restore: {
                            show: true
                        },
                        saveAsImage: {
                            show: true
                        }
                    }
                },
                dataRange: {
                    min: 0,
                    max: 100,
                    y: 'center',
                    text: ['High', 'Low'],
                    color: ['lightgreen', 'yellow'],
                    calculable: true
                },
                xAxis: [{
                    type: 'value',
                    scale: true
                }],
                yAxis: [{
                    type: 'value',
                    position: 'right',
                    scale: true
                }],
                animation: false,
                series: [{
                    name: 'scatter1',
                    type: 'scatter',
                    symbolSize: 5,
                    data: []
                }]
            });



        }
    );
});