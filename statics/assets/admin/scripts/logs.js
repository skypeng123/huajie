AM.logs = {
    init : function() {
        this.loadLists();
        this.bindEvent();
    },
    bindEvent : function() {
        var that = this;
        $('#item_list').on('click','.del-btn',function(){
            ids = $(this).attr('data-id');
            that.delItem(ids);
        });

        $('#batch-del-btn').on('click',function(){
            var ids = [];
            $('#item_list tbody tr .checkboxes:checked').each(function(){
                ids.push($(this).val());
            });
            that.delItem(ids.join(','));
        });
    },
    loading : function(action){
        $('#loading').modal(action);
    },
    alert : function(message){
        $('#alert-modal .modal-body').html(message);
        $('#alert-modal').modal('show');
    },
    checkboxs : function(){
        $('#item_list').find('.group-checkable').change(function () {
            var set = jQuery(this).attr("data-set");
            var checked = jQuery(this).is(":checked");
            jQuery(set).each(function () {
                if (checked) {
                    $(this).prop("checked", true);
                    $(this).parents('tr').addClass("active");
                } else {
                    $(this).prop("checked", false);
                    $(this).parents('tr').removeClass("active");
                }
            });
        });

        $('#item_list').on('change', 'tbody tr .checkboxes', function () {
            $(this).parents('tr').toggleClass("active");
        });
    },
    loadLists : function(){
        $('#item_list').DataTable({
            ajax: {
                //指定数据源
                url: admin_url+"logs/getlist"
            },
            language: {
                "search": "过滤记录:",
                "searchPlaceholder": "关键字",
                "lengthMenu": "每页 _MENU_ 条记录",
                "zeroRecords": "没有找到记录",
                "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
                "infoEmpty": "",
                "infoFiltered": "(从 _MAX_ 条记录过滤)"
            },
            //每页显示数据
            pageLength: 10,
            columns: [
                {
                    "data": "id"
                },
                {
                    "data": "username"
                },
                {
                    "data": "message"
                },
                {
                    "data": "addtime"
                },
                {
                    "data": null
                }
            ],
            "order": [],
            "columnDefs": [
                {
                    'targets' : [0,1,2,3,4],    //除第2，第7列外，都默认不排序
                    'orderable' : false
                },
                {
                    "render": function(data, type, row, meta) {
                        //渲染 把数据源中的标题和url组成超链接
                        return '<td data-id="'+row['id']+'"><label class="mt-checkbox mt-checkbox-single mt-checkbox-outline"><input type="checkbox" class="checkboxes" value="'+row['id']+'" /><span></span></label></td>';
                    },
                    //指定是第三列
                    "targets": 0
                },
                {
                    "render": function(data, type, row, meta) {
                        return '<td><div class=" btn-group-sm btn-group-solid" > <a class="btn red btn-sx del-btn" role="button" data-id="'+row['id']+'"> <i class="fa fa-remove"></i>  删除 </a></div><td>';

                    },
                    "targets": 4
                }
            ]

        });

        this.checkboxs();
    },
    delItem : function(lid){
        var that = this;

        if(!lid){
            that.alert('请选择要删除的对象。');
            return;
        }
        bootbox.confirm({
            title: '操作确认',
            message: '确定要删除吗？删除后将无法恢复。',
            buttons: {
                confirm: {
                    label: "确定"
                },
                cancel: {
                    label: "取消"
                }
            },
            callback: function (result) {
                if (!result)
                    return;

                that.loading('show');
                $.ajax({
                    "url":admin_url+"logs/del",
                    "data":{id:lid},
                    "dataType":"json",
                    "type":"POST",
                    "success":function (rdata) {
                        if(rdata.code == 200){
                            that.loading('hide');
                            window.location.reload();
                        }else if(rdata.code == 401){
                            that.loading('hide');
                            that.alert(rdata.msg);
                            setTimeout(function(){
                                window.location.href=admin_url+'login?reurl='+rdata.data.reurl;
                            },3000);
                        }else{
                            that.loading('hide');
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
            }
        });
    },

};

$(function(){
    AM.logs.init();
})