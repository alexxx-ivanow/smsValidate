<!DOCTYPE html>
<html lang="ru">
    <body>
        <div class="container">
            {$_modx->runSnippet('!AjaxForm', [
            	'snippet' => 'FormIt',
            	'form' => 'smsvalidate_form',
            	'renderHooks' => 'smsValidateInit',
            	'hooks' => 'FormItSaveForm,email',
            	'formName' =>'Тестовая форма',
            	'formFields' => 'name,phone,email,sms_code',
            	'fieldNames' => 'name==Имя отправителя,phone==Телефон отправителя,email==Email,Код СМС==sms_code'
            	'emailTpl' => 'smsvalidate_email_tpl',
            	'emailSubject' => 'Сообщение с сайта',
            	'emailTo' => 'test@test.test',
            	'customValidators' => 'smsValidate',
            	'validate'=>'name:required,email:email:required,phone:required,sms_code:smsValidate=^phone^',
            	'validationErrorMessage'=>'The form contains errors!',
            	'successMessage' => 'Message sent successfully'
            ])}
        </div>
        <!--Если необходимо, подключаем jquery-->
        <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    </body>
</html>