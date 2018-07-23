AM.bidding_category = {

    init : function() {
        this.loadLists();
        this.bindEvent();
    },
    bindEvent : function() {
        var that = this;
        $('#add-btn').click(function(){
            that.addItem();
        });

        $('#item_list').on('click','.edit-btn',function(){
            id = $(this).attr('data-id');
            that.editItem(id);
        });

        $('#save-btn').click(function(){
            that.saveItem();
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

    },
    showModal : function(type,data){
        $('#form_alert').html('');
        myform = $("#editModal");

        if(type == 'add'){
            this.clearForm(myform);

            myform.find('.modal-title').html('添加分类');
            myform.find('input[name=state]').eq(0).prop('checked',true);
            myform.modal('show');
        }else if(type == 'edit'){
            myform.find('.modal-title').html('修改分类');
            myform.find('input[name="id"]').val(data.id);
            myform.find('input[name="name"]').val(data.name);
            myform.find('input[name="order_num"]').val(data.order_num);
            myform.find('input[name="state"][value=' + data.state + ']').prop("checked", true);

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
                url: admin_url+"bidding_category/getlist"
            },
            language: {
                "search": "过滤记录:",
                "searchPlaceholder": "分类名称",
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
                    "data": "order_num"
                },
                {
                    "data": "state"
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
                        if(data == 1){
                            str = '<span class="label label-sm label-success">启用</span>';
                        }else{
                            str = '<span class="label label-sm label-danger">禁用</span>';
                        }
                        return '<td>'+str+'<td>';

                    },
                    "targets": 3
                },
                {
                    "render": function(data, type, row, meta) {
                        return '<td><div class=" btn-group-sm btn-group-solid" ><a role="button" class="btn blue btn-sx edit-btn" data-toggle="modal" data-id="'+row['id']+'"> <i class="fa fa-pencil"></i> 修改 </a>  <a class="btn red btn-sx del-btn" role="button" data-id="'+row['id']+'"> <i class="fa fa-remove"></i>  删除 </a></div><td>';

                    },
                    "targets": 5
                }
            ]

        });

        this.checkboxs();
    },
    addItem : function(){
        this.showModal('add');
    },
    editItem : function(id){
        var that = this;
        that.loading('show');
        $.ajax({
            "url":admin_url+"bidding_category/getinfo",
            "data":{id:id},
            "dataType":"json",
            "type":"POST",
            "success":function (rdata) {
                if(rdata.code == 200){
                    that.loading('hide');
                    that.showModal('edit',rdata.data);
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
                    "url":admin_url+"bidding_category/del",
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
    },
    saveItem : function(){
        var that = this;
        that.loading('show');

        var saveForm = $('#editModal');
        id=saveForm.find("input[name=id]").val();
        name=saveForm.find("input[name=name]").val();
        order_num=saveForm.find("input[name=order_num]").val();
        state=saveForm.find("input[name=state]:checked").val();

        $.ajax({
            "url":admin_url+"bidding_category/save",
            "data":{id:id,name:name,order_num:order_num,state:state},
            "dataType":"json",
            "type":"POST",
            "success":function (rdata) {
                if(rdata.code == 200){
                    that.loading('hide');
                    that.formSuccess();

                    setTimeout(function(){
                        that.table.ajax.reload();
                        saveForm.modal('hide');
                    },1000);
                    //window.location.reload();
                }else if(rdata.code == 401){
                    that.loading('hide');
                    that.alert(rdata.msg);
                    setTimeout(function(){
                        window.location.href=admin_url+'login?reurl='+rdata.data.reurl;
                    },3000);
                }else{
                    that.loading('hide');
                    that.formError(rdata.msg);
                }
            },
            "error":function(res){
                console.log(res);
                that.loading('hide');
                http_status = res.status;
                json_data = res.responseJSON;
                if(http_status == 400){
                    that.formError(json_data.message+'('+json_data.code+')');
                    // that.alert('数据格式不正确');
                }else if(http_status == 500){
                    that.formError('服务器错误');
                    // that.alert('服务器错误.');
                }else{
                    that.formError('未知错误');
                    // that.alert('未知错误.');
                }
            }
        });
    }
};

$(function(){
    AM.bidding_category.init();
})