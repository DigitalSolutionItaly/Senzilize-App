<form action="" method="post" id="loginForm">
	<input type="text" id="username" size="20" placeholder="<?=lang(7)?>" onfocus="$('#error').html('');" class="form-control">
	<input type="password" id="password" size="10" placeholder="<?=lang(8)?>" onfocus="$('#error').html('');" class="form-control">
	<input type="submit" class="btn form-control" value="<?=lang(2)?>" id="login"> <span class="msg"></span>
	<div style="font-size:12px; margin-top:10px;text-align: center;display: none;" id="error"></div>
	<br><br><div style="text-align:center;"><?=lang(9)?> <a href="/signup"><?=lang(10)?></a></div>
	<br><div style="text-align:center;"><a onclick="showResetPassword()" href="#"><?=lang(11)?></a></div>
</form>	
<div id="resetPassword">
	<div class="signupFormGroupTitle" style="text-align:center;margin-top: 0px;"><?=lang(12)?></div>
	<input type="email" id="resetPasswordEmail" size="20" placeholder="<?=lang(13)?>" class="form-control" style="margin-top: 10px;margin-bottom: 10px;">
	<div style="font-size: 12px;display: none;color: red;margin-bottom: 10px;" id="resetPasswordError"><?=lang(14)?></div>
	<input type="button" class="btn form-control" value="<?=lang(15)?>" style="margin-top: 0px;" onclick="sendResetPasswordMail()">
	<br><br><div style="text-align:center;"><?=lang(16)?> <a href="#" onclick="hideResetPassword()"><?=lang(2)?></a></div>
</div>