		<div id=signupFormContainer>
			<form id="signupForm">
				<div class="signupFormGroupTitle" style="text-align:center;"><?=lang(36)?></div>
				<div class="signupRow">
					<div class="signupField">
						<input type="text" name="Name" class="form-control form-required" placeholder="<?=lang(37)?>">
						<div class="form-alert-message" data-fieldnotcompleted="<?=lang(38)?>"></div>	
					</div>
				</div>
				<div class="signupRow">
					<div class="signupField">
						<input type="text" name="Lastname" class="form-control form-required" placeholder="<?=lang(39)?>">	
						<div class="form-alert-message" data-fieldnotcompleted="<?=lang(40)?>"></div>
					</div>
				</div>
				<div class="signupRow">
					<div class="signupField">
						<input type="text" name="Email" class="form-control form-email form-required" placeholder="<?=lang(7)?>">
						<div class="form-alert-message" data-fieldnotcompleted="<?=lang(13)?>" data-emailnotcorrect="<?=lang(41)?>"></div>
					</div>
				</div>
				<div class="signupRow">
					<div class="signupField">
						<input type="text" name="FarmName" class="form-control form-required" placeholder="<?=lang(42)?>">
						<div class="form-alert-message" data-fieldnotcompleted="<?=lang(43)?>"></div>
					</div>
				</div>
				<input name="LangID" type="hidden" value="<?=$LangID?>">
				<div class="signupRow">
					<div class="signupField" style="font-size: 11px;">
						<?=lang(44)?>
					</div>
				</div>	
			</form>
			<div id="userExist" class="form-alert-message" style="padding-bottom: 15px;"><?=lang(45)?></div>
			<input type="submit" value="<?=lang(46)?>" class="btn" id="SignupFormButton" onclick="submitSignupForm();" style="width:100%;">	
			<div id="SignupFormButtonLoading"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i></div>
			<br><br><div style="text-align:center;"><?=lang(47)?> <a href="/"><?=lang(2)?></a></div>
		</div>

<script>	
function submitSignupForm(){
	var errors=0;																												
	$("#signupForm .form-required").each(function( index ) {
		console.log($(this).attr("name") +": "+ $(this).val())
		if ($(this).val()==""){
			errors=errors+1;
			var AlertMessage=$(this).parent().find(".form-alert-message").data("fieldnotcompleted");
			$(this).parent().find(".form-alert-message").html(AlertMessage);
			$(this).parent().find(".form-alert-message").slideDown();
		}else{
			$(this).parent().find(".form-alert-message").html("");
			$(this).parent().find(".form-alert-message").slideUp();																												
		}
	});	
	if (errors==0){
		var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
		if(re.test($("#signupForm .form-email").val())) {

			$("#signupForm input.form-email").parent().find(".form-alert-message").html("");
			$("#signupForm input.form-email").parent().find(".form-alert-message").slideUp();
			$.ajax({
				url: "/include/signup_mgr.php?a=check_user",
				data: $("#signupForm").serialize(),
				method: "POST",
				success: function(response,stato) {
					if(response=="ERR"){
						$("#userExist").slideDown();
					}else{
						$("#userExist").slideUp();
						$("#SignupFormButton").hide();
						$("#SignupFormButtonLoading").show();
						$.ajax({
							url: "/include/signup_mgr.php?a=signup",
							data: $("#signupForm").serialize(),
							method: "POST",
							success: function(response,stato) {
								$("#signupFormContainer").html(response);
							},
							error : function (richiesta,stato,errori) {
								alert("Errore!");
								$("#SignupFormButtonLoading").hide();
								$("#SignupFormButton").show();
							}
						});	
					}
				},
				error : function (richiesta,stato,errori) {
					alert("Errore!");
					$("#SignupFormButtonLoading").hide();
					$("#SignupFormButton").show();
				}
			});																																		
		}  else {
			var AlertMessage=$("#signupForm input.form-email").parent().find(".form-alert-message").data("emailnotcorrect");
			$("#signupForm input.form-email").parent().find(".form-alert-message").html(AlertMessage);
			$("#signupForm input.form-email").parent().find(".form-alert-message").slideDown();	
		} 
	}
}
</script>