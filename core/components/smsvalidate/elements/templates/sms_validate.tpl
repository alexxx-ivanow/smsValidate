<!DOCTYPE html>
<html lang="ru">
    <body>
        <div class="main-content">
            <div class="container">
                {$_modx->runSnippet('!AjaxForm', [
                	'snippet' => 'FormIt',
                	'form' => 'modal_form_discount',
                	'renderHooks' => 'smsValidateInit',
                	'hooks' => 'FormItSaveForm,email',
                	'formName' =>'Тестовая форма',
                	'formFields' => 'name,phone,email,sms_code',
                	'fieldNames' => 'name==Имя отправителя,phone==Телефон отправителя,email==Email,Код СМС==sms_code'
                	'emailTpl' => 'modal_report_discount_tpl',
                	'emailSubject' => 'Сообщение с сайта',
                	'emailTo' => 'test@test.test',
                	'customValidators' => 'smsValidate',
                	'validate'=>'name:required,email:email:required,phone:required,sms_code:smsValidate=^phone^',
                	'validationErrorMessage'=>'The form contains errors!',
                	'successMessage' => 'Message sent successfully'
                ])}
            </div>
        </div>
        
        <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    </body>
</html>