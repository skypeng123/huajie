AM.news = {

    init : function() {
        this.loadLists();
        this.bindEvent();
    },
    bindEvent : function() {
        var that = this;

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

        $('#save-btn').click(function(){
            that.saveItem();
        });

        if($("#newsForm").length > 0){

            that.loadEditor();

            that.loadSWFupload();

            $(".remove_pic").on('click',function(){
                $(this).closest('.pic').remove();
            });
        }
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
    loadEditor : function(){
        this.ue = UE.getEditor('content',{
            toolbars: [
                ['fullscreen', 'undo', 'redo', 'bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat', 'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|', 'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', 'selectall', 'cleardoc','|','simpleupload','insertimage','|','source']
            ],
            autoHeightEnabled: true,
            autoFloatEnabled: true,
            initialFrameHeight: 400,
            serverUrl:site_url+"ueditor/controller.php"
        });
    },
    loadSWFupload : function(){
        window.onload = function () {
            this.swfu = new SWFUpload({
                // Backend Settings
                upload_url: admin_url+"news/upload",
                post_params: {"sessid": sessid},
                file_post_name: "pic",

                // File Upload Settings
                file_size_limit : "2 MB",	// 2MB
                file_types : "*.jpg;*.png;*.gif",
                file_types_description : "Images",
                file_upload_limit : 0,
                file_queue_limit : 1,

                // Event Handler Settings - these functions as defined in Handlers.js
                //  The handlers are not part of SWFUpload but are part of my website and control how
                //  my website reacts to the SWFUpload events.
                swfupload_preload_handler : preLoad,
                swfupload_load_failed_handler : loadFailed,
                file_queue_error_handler : fileQueueError,
                file_dialog_complete_handler : fileDialogComplete,
                upload_progress_handler : uploadProgress,
                upload_error_handler : uploadError,
                upload_success_handler : uploadSuccess,
                upload_complete_handler : uploadComplete,

                // Button Settings
                button_image_url : "",
                button_placeholder_id : "spanButtonPlaceholder",
                button_width: 180,
                button_height: 35,
                button_text : '<span class="upbtn">上传图片</span><span class="buttonSmall"> (大小限制：2M)</span>',
                button_text_style : '.upbtn { color:#ffffff;line-height:35px;font-family: Helvetica, Arial, sans-serif; font-size: 12px;} .buttonSmall { color:#ffffff;font-size: 10pt; }',
                button_text_top_padding: 0,
                button_text_left_padding: 15,
                button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
                button_cursor: SWFUpload.CURSOR.HAND,
                button_action : SWFUpload.BUTTON_ACTION.SELECT_FILE,

                // Flash Settings
                flash_url : statics_url+"swfupload/core/swfupload.swf",
                flash9_url : statics_url+"swfupload/core/swfupload_FP9.swf",

                custom_settings : {
                    upload_target : "divFileProgressContainer"
                },

                // Debug Settings
                debug: false
            });
        };
    },
    clearForm : function(myform){
        myform.find('input[type=text]').val('');
        myform.find('input[type=hidden]').val('');
        myform.find('select').each(function(){
            $(this).find('option').eq(0).attr("selected", true);
        });
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
                url: admin_url+"news/getlist"
            },
            language: {
                "search": "过滤记录:",
                "searchPlaceholder": "用户名/手机号",
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
                    "data": "catname"
                },
                {
                    "data": "title"
                },
                {
                    "data": "recommend"
                },
                {
                    "data": "views"
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
                    'targets' : [0,1,2,3,4,5,6,7],   //默认不排序
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
                            str = '<span style="color:green">是</span>';
                        }else{
                            str = '<span class="">否</span>';
                        }
                        return '<td>'+str+'<td>';

                    },
                    "targets": 3
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
                    "targets": 6
                },
                {
                    "render": function(data, type, row, meta) {
                        return '<td><div class=" btn-group-sm btn-group-solid" ><a role="button" class="btn blue btn-sx edit-btn" href="'+admin_url+'news/edit/'+row['id']+'"> <i class="fa fa-pencil"></i> 修改 </a>  <a class="btn red btn-sx del-btn" role="button" data-id="'+row['id']+'"> <i class="fa fa-remove"></i>  删除 </a></div><td>';

                    },
                    "targets": 8
                }
            ]

        });

        this.checkboxs();
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
                    "url":admin_url+"news/del",
                    "data":{ids:ids},
                    "dataType":"json",
                    "type":"POST",
                    "success":function (rdata) {
                        if(rdata.code == 200){
                            that.loading('hide');
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

        var saveForm = $('#newsForm');
        id=saveForm.find("input[name=id]").val();
        title=saveForm.find("input[name=title]").val();
        views=saveForm.find("input[name=views]").val();
        order_num=saveForm.find("input[name=order_num]").val();
        catid=saveForm.find("select[name=catid]").val();
        url=saveForm.find("input[name=url]").val();
        state=saveForm.find("input[name=state]:checked").val();
        recommend=saveForm.find("input[name=recommend]:checked").val();
        content = that.ue.getContent();
        pic = $('#thumbnails img').attr("src");

        $.ajax({
            "url":admin_url+"news/save",
            "data":{id:id,title:title,views:views,order_num:order_num,catid:catid,url:url,state:state,content:content,recommend:recommend,pic:pic},
            "dataType":"json",
            "type":"POST",
            "success":function (rdata) {
                if(rdata.code == 200){
                    that.loading('hide');
                    that.formSuccess();

                    setTimeout(function(){
                        window.location.href=admin_url+'news'
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
                    that.alert(rdata.msg);
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
    AM.news.init();
})