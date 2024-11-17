$(document).on('af_complete', function(event, response) {
    var form = response.form;
    var smsWrap = form.find('.jsSmsCodeWrap');
    var smsInput = form.find('.jsSmsCodeInput');
    var repeatHiddenInput = form.find('input[name=repeat_sms]');
    
    if(smsInput.length) {
    
        var smsFieldName = smsInput.attr('name');
        if(response.data.hasOwnProperty(smsFieldName) && response.data[smsFieldName] != 'undefined') {
        
            repeatHiddenInput.val('');
            smsWrap.fadeIn();
            
            // повторная отправка СМС
            setTimeout(function() {
                form.find('.jsSmsRepeat').on('click', function(e) {
                    e.preventDefault();
                    repeatHiddenInput.val('1');
                    form.trigger('submit');
                });
            }, 1);
            
        } else {
            smsWrap.fadeOut();
        }
        
    } else {
        console.log('нет служебного класса поля для СМС-кода');
    }
});