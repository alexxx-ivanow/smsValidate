$(document).on('af_complete', function(event, response) {
    var form = response.form;
    
    var smsInput = form.find('.jsSmsCodeInput');
    if(smsInput.length) {
    
        var smsFieldName = smsInput.attr('name');
        if(response.data.hasOwnProperty(smsFieldName) && response.data[smsFieldName] != 'undefined') {
        
            $('input[name=repeat_sms]').val('');
            $('.jsSmsCodeWrap').fadeIn();
            
            // повторная отправка СМС
            setTimeout(function() {
                $('.jsSmsRepeat').on('click', function(e) {
                    e.preventDefault();
                    $('input[name=repeat_sms]').val('1');
                    form.trigger('submit');
                });
            }, 1);
            
        } else {
            $('.jsSmsCodeWrap').fadeOut();
        }
        
    } else {
        console.log('нет служебного класса поля для СМС-кода');
    }
});