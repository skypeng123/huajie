var Login = function() {
    var handleLogin = function() {
        var that = this;
        $('.login-form').validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            rules: {
                username: {
                    required: true
                },
                password: {
                    required: true
                },
                remember: {
                    required: false
                }
            },

            messages: {
                username: {
                    required: "Username is required."
                },
                password: {
                    required: "Password is required."
                }
            },

            invalidHandler: function (event, validator) { //display error alert on form submit
                $('.alert-danger', $('.login-form')).show();
            },

            highlight: function (element) { // hightlight error inputs
                $(element)
                    .closest('.form-group').addClass('has-error'); // set error class to the control group
            },

            success: function (label) {
                label.closest('.form-group').removeClass('has-error');
                label.remove();
            },

            errorPlacement: function (error, element) {
                error.insertAfter(element.closest('.input-icon'));
            },

            submitHandler: function (form) {
                //form.submit();
                loginSubmit();

            }
        });

        $('.login-form input').keypress(function (e) {
            if (e.which == 13) {
                if ($('.login-form').validate().form()) {
                    //$('.login-form').submit();
                    loginSubmit();
                }
                return false;
            }
        });
    };

    var loginSubmit = function(){
        $(".alert").hide();

        username=$("input[name=username]").val();
        password=$("input[name=password]").val();
        remember=$("input[name=remember]:checked").val();
        var reurl = $("input[name=reurl]").val();
        if(reurl == "") reurl = admin_url;

        $.ajax({
            "url":admin_url+"login/submit",
            "data":{username:username,password:password,remember:remember},
            "dataType":"json",
            "type":"POST",
            "success":function (res) {
                if(res.code == 200){
                    window.location.href=reurl;
                }else{
                    $(".alert").children('span').html(res.msg+'('+res.code+')');
                    $(".alert").show();
                }
            },
            "error":function(res){
                http_status = res.status;
                json_data = res.responseJSON;

                if(http_status == 500){
                    $(".alert").children('span').html('服务器错误.');
                    $(".alert").show();

                }else{
                    $(".alert").children('span').html('未知错误.');
                    $(".alert").show();
                }
            }
        });
    }

    return {
        //main function to initiate the module
        init: function() {

            handleLogin();

            // init background slide images
            $('.login-bg').backstretch([
                statics_url+"assets/pages/img/login/1.jpg",
                statics_url+"assets/pages/img/login/2.jpg",
                statics_url+"assets/pages/img/login/3.jpg"
                ], {
                    fade: 1000,
                    duration: 8000
                }
            );

            $('.forget-form').hide();



        }

    }
}();

jQuery(document).ready(function() {
    Login.init();
});