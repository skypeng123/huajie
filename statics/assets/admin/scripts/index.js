AM.index = {
    init : function(){
        this.loadData();
    },
    loadData : function(){
        var that = this;
        $.ajax({
            "url":admin_url+"index/getSiteStatistics",
            "data":{},
            "dataType":"json",
            "type":"GET",
            "success":function (rdata) {
                if(rdata.code == 200){
                    chartData = [];
                    $(rdata.data).each(function(k,v){
                        chartData.push({
                            date: v.date,
                            value: parseInt(v.value)
                        });
                    });
                    console.log(chartData);
                    that.initChart(chartData);
                }else if(rdata.code == 401){
                    alert(rdata.msg);
                    setTimeout(function(){
                        window.location.href=admin_url+'login?reurl='+rdata.data.reurl;
                    },3000);
                }else{
                    alert(rdata.msg);
                }
            },
            "error":function(res){
                http_status = res.status;
                json_data = res.responseJSON;
                that.loading('hide');
                if(http_status == 400){
                    alert(json_data.message+'('+json_data.code+')');
                }else if(http_status == 500){
                    alert('服务器错误.');
                }else{
                    alert('网络错误.');
                }
            }
        });
    },
    initChart : function(chartData){
        AmCharts.useUTC = false;
        AmCharts.monthNames = [
            "1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"
        ];
        AmCharts.shortMonthNames = [
            "1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"
        ];
        var chart = AmCharts.makeChart("chart_12", {
            "hideCredits":true,
            "type": "stock",
            "theme": "light",
            "pathToImages": statics_url+"assets/global/plugins/amcharts/amcharts/images/",
            "fontFamily": 'Open Sans',
            "color":    '#888',
            "dataDateFormat": "YYYY-MM-DD",
            "categoryAxis": {
                "parseDates":true
            },
            dataSets: [{
                color: "#b0de09",
                fieldMappings: [{
                    fromField: "value",
                    toField: "value"
                }],
                dataProvider: chartData,
                categoryField: "date",
                // EVENTS
                stockEvents: []
            }],


            panels: [{
                title: "访问量",
                percentHeight: 70,

                stockGraphs: [{
                    id: "g1",
                    valueField: "value"
                }],

                stockLegend: {
                    valueTextRegular: " ",
                    markerType: "none"
                }
            }],

            chartScrollbarSettings: {
                graph: "g1"
            },

            chartCursorSettings: {
                valueBalloonsEnabled: true,
                graphBulletSize: 1,
                valueLineBalloonEnabled:true,
                valueLineEnabled:true,
                valueLineAlpha:0.5
            },

            periodSelector: {
                periods: [{
                    "period": "DD",
                    "selected": true,
                    "count": 7,
                    "label": "7天"
                },{
                    period: "MM",
                    count: 1,
                    label: "1月"
                }, {
                    period: "YYYY",
                    count: 1,
                    label: "1年"
                }]
            },

            panelsSettings: {
                usePrefixes: true
            }
        });

        $('#chart_12').closest('.portlet').find('.fullscreen').click(function() {
            chart.invalidateSize();
        });
    }
};


$(function(){
    AM.index.init();
    $("a[title='JS chart by amCharts']").remove();
})