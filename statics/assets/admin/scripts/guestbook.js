AM.guestbook = {

    init : function() {
        this.loadLists();
        this.bindEvent();
    },
    bindEvent : function() {
        var that = this;
  
        $('#item_list').on('click','.view-btn',function(){
            id = $(this).attr('data-id');
            that.viewItem(id);
        });

        $('#item_list').on('click','.del-btn',function(){
            id = $(this).attr('data-id');
            that.delItem(id);
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

    clearForm : function(myform){
        myform.find('input[type=text]').val('');
        myform.find('input[type=hidden]').val('');
        myform.find('textarea').val('');
        myform.find('select').each(function(){
            $(this).find('option').eq(0).attr("selected", true);
        });
    },
    showModal : function(type,data){
        $('#form_alert').html('');
        myform = $("#editModal");
        console.log(data);
        if(type == 'view'){
            myform.find('.modal-title').html('查看留言');
            myform.find('input[name="id"]').val(data.id);
            myform.find('input[name="name"]').val(data.name);
            myform.find('input[name="mobile"]').val(data.mobile);
            myform.find('input[name="email"]').val(data.email);
            myform.find('textarea[name="content"]').val(data.content);
            myform.find('input[name="addtime"]').val(data.addtime);

            myform.modal('show');
        }

    },
    formError : function(message){
        App.alert({
            container: '#form_alert',
            place: 'prepend',
            type: 'danger',
            message: message,
            reset: true,
            focus: true,
            close: true,
            closeInSeconds: 10000,
            icon : 'fa fa-warning'
        });
    },
    formSuccess : function(){
        App.alert({
            container: '#form_alert',
            place: 'prepend',
            type: 'success',
            message: '保存成功',
            reset: true,
            focus: true,
            close: true,
            closeInSeconds: 10000,
            icon : 'fa fa-check'
        });
    },
    loadLists : function(){
        this.table = $('#item_list').DataTable({
            ajax: {
                //指定数据源
                url: admin_url+"guestbook/getlist"
            },
            language: {
                "search": "过滤记录:",
                "searchPlaceholder": "手机号/邮箱",
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
                    "data": "name"
                },
                {
                    "data": "mobile"
                },
                {
                    "data": "email"
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
                    'targets' : [0,1,2,3,4,5],   //默认不排序
                    'orderable' : false
                },
                {
                    "render": function(data, type, row, meta) {
                        //渲染 把数据源中的标题和url组成超链接
                        return '<td data-id="'+row['id']+'"><label class="mt-checkbox mt-checkbox-single mt-checkbox-outline"><input type="checkbox" class="checkboxes" value="'+data+'" /><span></span></label></td>';
                    },
                    //指定是第三列
                    "targets": 0
                },

                {
                    "render": function(data, type, row, meta) {
                        return '<td><div class=" btn-group-sm btn-group-solid" ><a role="button" class="btn blue btn-sx view-btn" data-toggle="modal" data-id="'+row['id']+'"> <i class="fa fa-eye"></i> 查看 </a>  <a class="btn red btn-sx del-btn" role="button" data-id="'+row['id']+'"> <i class="fa fa-remove"></i>  删除 </a></div><td>';

                    },
                    "targets": 5
                }
            ]

        });

        this.checkboxs();
    },
    viewItem : function(id){
        var that = this;
        that.loading('show');
        $.ajax({
            "url":admin_url+"guestbook/getinfo",
            "data":{id:id},
            "dataType":"json",
            "type":"POST",
            "success":function (rdata) {
                if(rdata.code == 200){
                    that.loading('hide');
                    that.showModal('view',rdata.data);
                }else if(rdata.code == 401){
                    that.loading('hide');
                    that.alert(rdata.msg);
                    setTimeout(function(){
                        window.location.href=admin_url+'login?reurl='+rdata.data.reurl;
                    },3000);
                }else{
                    that.loading('hide');
                    that.alert(rdata.msg);
                }
            },
            "error":function(res){
                that.loading('hide');

                http_status = res.status;
                json_data = res.responseJSON;
                if(http_status == 400){
                     that.alert(json_data.message+'('+json_data.code+')');
                }else if(http_status == 500){
                     that.alert('服务器错误.');
                }else{
                     that.alert('网络错误.');
                }
            }
        });
    },
    delItem : function(ids){
        var that = this;

        if(!ids){
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
                    "url":admin_url+"guestbook/del",
                    "data":{ids:ids},
                    "dataType":"json",
                    "type":"POST",
                    "success":function (rdata) {
                        if(rdata.code == 200){
                            that.loading('hide');
                            //window.location.reload();
                            that.table.ajax.reload();
                        }else if(rdata.code == 401){
                            that.loading('hide');
                            that.alert(rdata.msg);
                            setTimeout(function(){
                                window.location.href=admin_url+'login?reurl='+rdata.data.reurl;
                            },3000);
                        }else{
                            that.loading('hide');
                            that.alert(rdata.msg);
                        }
                    },
                    "error":function(res){
                        http_status = res.status;
                        json_data = res.responseJSON;
                        that.loading('hide');
                        if(http_status == 400){
                             that.alert(json_data.message+'('+json_data.code+')');
                        }else if(http_status == 500){
                             that.alert('服务器错误.');
                        }else{
                             that.alert('网络错误.');
                        }
                    }
                });
            }
        });
    }
};

$(function(){
    AM.guestbook.init();
})