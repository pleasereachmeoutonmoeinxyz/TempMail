(function(window,document,$,undefined){
    $(document).ready(function(){
        $("#mailform input[type=button]").on('click',function(){
            $.post('get',$("#mailform").serialize())
                .done(function(data){
                    data    =   JSON.parse(data);
                    if (data.success === true){
                        $("#mailform").fadeOut();
                        $("#get header h2").slideUp().html('<br><br>'+data.email).slideDown();
                    } else {
                        if (data.error === "INVALID"){
                            $("#error").html('Please enter valid mail address.');
                        } else if(data.error === "LIMITATION") {
                            $("#error").html('Limitation for getting temp mail,please wait one minutes.');
                        }
                    }
                });
        });
    });
})(window,document,jQuery);