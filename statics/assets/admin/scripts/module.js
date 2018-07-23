AM.module = {
    init : function() {
        this.loadLists();
        this.bindEvent();
    },
    bindEvent : function() {
        var that = this;
        $('#add-btn').click(function(){
            that.addItem();
        });

        $('#edit-btn').click(function(){
            var ids = [];
            selected = $('#module_tree').jstree().get_selected(true);
            $(selected).each(function(i,row){
                console.log(row.id);
                ids.push(row.id);
            });

            if(ids.length == 0){
                that.alert('必须允许选中一条记录.');
                return;
            }

            mid = ids[0];
            that.editItem(mid);
        });

        $('#save-btn').click(function(){
            that.saveItem();
        });

        $('#batch-del-btn').on('click',function(){
            var ids = [];
            selected = $('#module_tree').jstree().get_selected();
            $(selected).each(function(i,row){
                ids.push(row);
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
        myform.find('select').each(function(){
            $(this).find('option').eq(0).attr("selected", true);
        });
        myform.find("input[name='permissions[]']").each(function() {
            $(this).prop("checked",false);
        });
    },
    showModal : function(type,data){
        $('#form_alert').html('');
        myform = $("#infoModal");
        if(type == 'add'){
            if(myform.find('input[name="id"]').val()){
                this.clearForm(myform);
            }
	        myform.find("input[name='permissions[]']").each(function() {
	            $(this).prop("checked",true);
	        });
            myform.find('.modal-title').html('添加模块');
            myform.find('input[name=state]').eq(0).prop('checked',true);
            myform.modal('show');
        }else if(type == 'edit'){
            myform.find('.modal-title').html('修改模块');

            myform.find('input[name="id"]').val(data.id);
            myform.find('input[name="name"]').val(data.name);
            myform.find('input[name="tag"]').val(data.tag);
            myform.find('input[name="url"]').val(data.url);
            myform.find('select[name="pid"] option[value=' + data.pid + ']').attr("selected", true);
            myform.find('input[name="state"][value=' + data.state + ']').prop("checked", true);
            myform.find('input[name="display_order"]').val(data.display_order);
            myform.find('input[name="icon"]').val(data.icon);
            perm_arr = data.permissions.split(',');
            myform.find("input[name='permissions[]']").each(function() {
                var thisval = $(this).val();
                if ($.inArray(thisval, perm_arr) !== -1) {
                    $(this).prop("checked",true);
                } else {
                    $(this).prop("checked",false);
                }
            });

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

        $("#module_tree").jstree({
            "plugins" : [ "dnd", "state", "types","checkbox" ],
            "core" : {
                "themes" : {
                    "responsive": false
                },
                // so that create works
                "check_callback" : true,
                'data' : {
                    'url' : function (node) {
                        return admin_url+'modules/getlist';
                    },
                    'data' : function (node) {
                        return { 'parent' : node.pid };
                    }
                }
            },
            "types" : {
                "default" : {
                    "icon" : "fa fa-folder icon-state-warning icon-lg"
                },
                "file" : {
                    "icon" : "fa fa-file icon-state-warning icon-lg"
                }
            },
            "state" : { "key" : "status" }

        });



        //this.checkboxs();
    },
    addItem : function(){
        this.showModal('add');
    },
    editItem : function(id){
        var that = this;
        that.loading('show');
        $.ajax({
            "url":admin_url+"modules/getinfo",
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
    delItem : function(id){
        var that = this;

        if(!id){
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
                    "url":admin_url+"modules/del",
                    "data":{id:id},
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

        var saveForm = $('#infoModal');
        id=saveForm.find("input[name=id]").val();
        name=saveForm.find("input[name=name]").val();
        tag=saveForm.find("input[name=tag]").val();
        url=saveForm.find("input[name=url]").val();
        pid=saveForm.find("select[name=pid]").val();
        status=saveForm.find("input[name=status]:checked").val();
        display_order=saveForm.find("input[name=display_order]").val();
        icon=saveForm.find("input[name=icon]").val();
        permissions = [];
        saveForm.find("input[name='permissions[]']:checked").each(function(i){
            permissions.push($(this).val());
        });
        permissionstr = permissions.join(',');


        $.ajax({
            "url":admin_url+"modules/save",
            "data":{id:id,name:name,tag:tag,url:url,pid:pid,status:status,display_order:display_order,icon:icon,permissions:permissionstr},
            "dataType":"json",
            "type":"POST",
            "success":function (rdata) {
                //console.log(rdata);
                if(rdata.code == 200){
                    that.loading('hide');
                    that.formSuccess();

                    setTimeout(function(){
                        window.location.reload();
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
                    //alert('数据格式不正确');
                }else if(http_status == 500){
                    that.formError('服务器错误');
                    //alert('服务器错误.');
                }else{
                    that.formError('未知错误');
                    //alert('未知错误.');
                }
            }
        });
    }
};

$(function(){
    AM.module.init();
})