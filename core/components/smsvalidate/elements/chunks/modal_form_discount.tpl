<form class="form" action="[[~[[*id]]?scheme=`full`]]" method="post">
	<div>
		<p>ФИО* </p>
		<input type="text" name="name" value="">
	</div>
	<div>
		<p>Телефон*</p>
		<input type="tel" name="phone" value="">
	</div>
	
	<!-- СМС-валидация-->
	<div class="jsSmsCodeWrap" style="display:none">
		<p>Код из sms*</p>
		<input class="jsSmsCodeInput" type="text" name="sms_code" value="">
		<input type="hidden" name="repeat_sms" value="">
		<span class="error_sms_code"></span>
	</div>
	<!-- СМС-валидация end -->

	<div>
		<p>Email* </p>
		<input type="email" name="email" value="">
	</div><br>
	<button type="submit">Отправить</button>	 
</form>