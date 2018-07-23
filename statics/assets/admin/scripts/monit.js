AM.monit = {
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
            pid = $(this).attr('data-id');
            that.editItem(pid);
        });

        $('#save-btn').click(function(){
            that.saveItem();
        });

        $('#item_list').on('click','.del-btn',function(){
            pid = $(this).attr('data-id');
            that.delItem(pid);
        });

        $('#batch-del-btn').on('click',function(){
            var pids = [];
            $('#item_list tbody tr .checkboxes:checked').each(function(){
                pids.push($(this).val());
            });
            that.delItem(pids.join(','));
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
        myform.find('select').each(function(){
            $(this).find('option').eq(0).attr("selected", true);
        });
    },
    showModal : function(type,data){
        $('#form_alert').html('');
        myform = $("#editModal");
        if(type == 'add'){
            if(myform.find('input[name="pid"]').val()){
                this.clearForm(myform);
            }
            myform.find('.modal-title').html('监控设置');
            myform.modal('show');
        }else if(type == 'edit'){
            myform.find('.modal-title').html('监控设置');


            myform.find('input[name="pid"]').val(data.pid);
            myform.find('input[name="pname"]').val(data.pname);
            myform.find('input[name="pname"]').prop('disabled',true);
            myform.find('textarea[name="alarm_email"]').val(data.alarm_email);
            myform.find('textarea[name="alarm_mobile"]').val(data.alarm_mobile);
            myform.find('input[name="slow_time"]').val(data.slow_time);
            myform.find('input[name="error_status"]').val(data.error_status);

            myform.find('input[name="alarm_mode"]').prop('checked',false);
            myform.find('#slow_alarm').bootstrapSwitch('state',false);
            myform.find('#error_alarm').bootstrapSwitch('state',false);

            if(data.alarm_mode){
                alarm_mode_arr = data.alarm_mode.split(',');
                $(alarm_mode_arr).each(function(i,v){
                    myform.find('input[name="alarm_mode"][value="'+v+'"]').prop('checked',true);
                });
            }
            myform.find('#slow_alarm').bootstrapSwitch('state',data.slow_alarm == 1 ? true : false);
            myform.find('#error_alarm').bootstrapSwitch('state',data.error_alarm == 1 ? true : false);

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

        $('#item_list').DataTable({
            ajax: {
                //指定数据源
                url: "/monit/get_lists"
            },
            language: {
                "lengthMenu": "每页 _MENU_ 条记录",
                "zeroRecords": "没有找到记录",
                "info": "第 _PAGE_ 页 ( 总共 _PAGES_ 页 )",
                "infoEmpty": "无记录",
                "infoFiltered": "(从 _MAX_ 条记录过滤)"
            },
            //每页显示数据
            pageLength: 10,
            columns: [
                {
                    "data": "pid"
                },
                {
                    "data": "pname"
                },
                {
                    "data": "alarm_mode"
                },
                {
                    "data": "slow_alarm"
                },
                {
                    "data": "error_alarm"
                },
                {
                    "data": null
                }
            ],
            "ordering": false,
            "columnDefs": [
                {
                    "render": function(data, type, row, meta) {
                        //渲染 把数据源中的标题和url组成超链接
                        return '<td data-id="'+row['pid']+'"><label class="mt-checkbox mt-checkbox-single mt-checkbox-outline"><input type="checkbox" class="checkboxes" value="'+row['pid']+'" /><span></span></label></td>';
                    },
                    //指定是第三列
                    "targets": 0
                },
                {
                    "render": function(data, type, row, meta) {
                        str = row['slow_alarm'] == 1 ? '<span class="label label-sm label-success">开</span>': '<span class="label label-sm label-danger">关</span>';
                        return '<td>'+str+'<td>';
                    },
                    "targets": 3
                },
                {
                    "render": function(data, type, row, meta) {
                        str = row['error_alarm'] == 1 ? '<span class="label label-sm label-success">开</span>': '<span class="label label-sm label-danger">关</span>';
                        return '<td>'+str+'<td>';
                    },
                    "targets": 4
                },
                {
                    "render": function(data, type, row, meta) {
                        return '<td><div class=" btn-group-sm btn-group-solid" ><a role="button" class="btn blue btn-sx edit-btn" data-toggle="modal" data-id="'+row['pid']+'"> <i class="fa fa-pencil"></i> 修改 </a> </div><td>';

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
    editItem : function(pid){
        var that = this;
        that.loading('show');
        $.ajax({
            "url":"/monit/get_info",
            "data":{pid:pid},
            "dataType":"json",
            "type":"POST",
            "success":function (rdata) {
                that.loading('hide');
                that.showModal('edit',rdata);
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
    delItem : function(pid){
        var that = this;

        if(!pid){
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
                    "url":"/monit/remove",
                    "data":{pid:pid},
                    "dataType":"json",
                    "type":"POST",
                    "success":function (rdata) {
                        that.loading('hide');
                        window.location.reload();
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

        saveForm = $('#editModal');
        pid=saveForm.find("input[name=pid]").val();

        var alarm_mode_checked = [];
        saveForm.find('input[name="alarm_mode"]:checked').each(function(){
            alarm_mode_checked.push($(this).val());
        });
        alarm_mode = alarm_mode_checked.join(',');
        slow_alarm = saveForm.find('input[name="slow_alarm"]:checked').val();
        slow_alarm = slow_alarm === 'on' ? 1 : 2;
        error_alarm = saveForm.find('input[name="error_alarm"]:checked').val();
        error_alarm = error_alarm === 'on' ? 1 : 2;
        alarm_email=saveForm.find("textarea[name=alarm_email]").val();
        alarm_mobile=saveForm.find("textarea[name=alarm_mobile]").val();
        slow_time=saveForm.find("input[name=slow_time]").val();
        error_status=saveForm.find("input[name=error_status]").val();

        $.ajax({
            "url":"/monit/save",
            "data":{pid:pid,alarm_mode:alarm_mode,slow_alarm:slow_alarm,error_alarm:error_alarm,alarm_email:alarm_email,alarm_mobile:alarm_mobile,error_status:error_status,slow_time:slow_time},
            "dataType":"json",
            "type":"POST",
            "success":function (rdata) {
                console.log(rdata);
                that.loading('hide');
                that.formSuccess();

                setTimeout(function(){
                    window.location.reload();
                },1000);
                //window.location.reload();
            },
            "error":function(res){
                console.log(res);
                that.loading('hide');
                http_status = res.status;
                json_data = res.responseJSON;
                if(http_status == 400){
                    that.formError(json_data.message+'('+json_data.code+')');
                    //that.alert('数据格式不正确');
                }else if(http_status == 500){
                    that.formError('服务器错误');
                    //that.alert('服务器错误.');
                }else{
                    that.formError('未知错误');
                    //that.alert('未知错误.');
                }
            }
        });
    }
};

$(function(){
    AM.monit.init();
})