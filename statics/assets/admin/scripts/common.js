AM.common = {

    loading : function(action){
        $('#loading').modal(action);
    },
    alert : function(message){
        $('#alert-modal .modal-body').html(message);
        $('#alert-modal').modal('show');
    },
    formError : function(message){
        App.alert({
            container: '.form_alert',
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
            container: '.form_alert',
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
    bindEvent : function() {
        var that = this;
        $('#edit-pwd-btn').click(function(){
            uid = $(this).attr('data-id');
            that.editPwdShow(uid);
        });

        $('#pwd_form #pwd-save-btn').click(function(){
            that.editPwdSubmit();
        });
    },
    editPwdShow : function(uid){
        var that = this;
        that.loading('show');
        $.ajax({
            "url":admin_url+"user/getinfo",
            "data":{uid:uid},
            "dataType":"json",
            "type":"POST",
            "success":function (rdata) {
                if(rdata.code == 200){
                    that.loading('hide');
                    data = rdata.data;
                    pwdForm = $('#editPwdModal');
                    pwdForm.find('.form_alert').html('');
                    pwdForm.find('input[name="uid"]').val(data.uid);
                    pwdForm.find('input[name="status"]').val(data.status);
                    pwdForm.modal('show');

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
    editPwdSubmit : function(){
        var that = this;
        that.loading('show');

        var saveForm = $('#editPwdModal');
        uid=saveForm.find("input[name=uid]").val();
        oldpassword=saveForm.find("input[name=oldpwd]").val();
        password=saveForm.find("input[name=pwd]").val();
        repassword=saveForm.find("input[name=repwd]").val();

        $.ajax({
            "url":admin_url+"user/save",
            "data":{uid:uid,oldpassword:oldpassword,password:password,repassword:repassword},
            "dataType":"json",
            "type":"POST",
            "success":function (rdata) {
                if(rdata.code == 200){
                    that.loading('hide');
                    that.formSuccess();

                    setTimeout(function(){
                        saveForm.modal('hide');
                    },1000);

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
    },

    init : function(){
        this.bindEvent();
    }
};

jQuery(document).ready(function() {
    AM.common.init();
});