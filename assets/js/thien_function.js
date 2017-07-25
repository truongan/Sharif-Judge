$(document).ready(function() {
/**
 * "DS Problem" page
 */
    $(".tag_edit").click(function () {
    	var tag = JSON.parse($(this).attr('data-id'));
		var tag_id = tag.loaibt_id;
		var tag_name = tag.loaibt_name;
		$("#edit_tag_name").val(tag_name);
		$("#edit_tag_id").val(tag_id);
    });

    $(".tag_delete").click(function () {
    	var tag_id = $(this).attr('data-id');
		$("#delete_tag_id").val(tag_id);
    });

    $(document).on("click", ".problems_edit", function () {
    	var problems = JSON.parse($(this).attr('data-id'));
		$("#edit_problems_id").val(problems.id);
		$("#edit_problems_name").val(problems.name);
		$("#edit_problems_difficulty").val(problems.difficulty);
		$("#edit_problems_score").val(problems.score);
		$("#edit_time_competition").val(problems.time_competition);
		$("#edit_problems_c_time_limit").val(problems.c_time_limit);
		$("#edit_problems_python_time_limit").val(problems.python_time_limit);
		$("#edit_problems_java_time_limit").val(problems.java_time_limit);
		$("#edit_problems_memory_limit").val(problems.memory_limit);
		$("#edit_problems_difficulty").selectpicker('val', problems.difficulty);
		$("#edit_problems_tag").selectpicker('val', problems.loaibt_name.split(','));
		$("#edit_problems_select_languages").selectpicker('val', problems.allowed_languages.split(','));
    });

    $(document).on("click", ".user_edit", function () {
    	var users = JSON.parse($(this).attr('data-id'));
		$("#edit_username").val(users.username);
		$("#edit_class").val(users.class);
		$("#edit_display_name").val(users.display_name);
		$("#edit_email").val(users.email);
		$(".filter-option").html(users.role);
    });


    $(document).on("click", ".problems_delete", function () {
    	var problems_id = $(this).attr('data-id');
		$("#delete_problems_id").val(problems_id);
    });
/**
 * "User" page
 */
 	$(document).on("click", ".users_delete", function () {
    	var user_id = $(this).attr('data-id');
		$("#delete_users_id").val(user_id);
    });
/**
 * "Login" page
 */
    $('#login-form-link').click(function(e) {
    	$("#login-form").delay(100).fadeIn(100);
 		$("#register_form").fadeOut(100);
		$('#register-form-link').removeClass('active');
		$(this).addClass('active');
		e.preventDefault();
	});

	$('#register-form-link').click(function(e) {
		$("#register_form").delay(100).fadeIn(100);
 		$("#login-form").fadeOut(100);
		$('#login-form-link').removeClass('active');
		$(this).addClass('active');
		e.preventDefault();
	});

	$("#register-submit").click(function(e) {
		console.log("hello");
	    $.ajax({
	            type: "POST",
	            url: "http://wecode.dev/index.php/login/register",
	            data: $("#register_form").serialize(), 
	           success: function(data) {
				if(data!=''){
					$("#dear").html("Dear " + data);
					$("#register_success").modal('show');
				}
	           }
	        });
	});
/**
 * "Problem" page
 */
    $("#testcase").submit(function(e) {
	    $.ajax({
           	type: "POST",
           	url: shj.site_url + "problems/testcase",
            data: $("#testcase").serialize(),
           success: function (data)
           {
           		console.log(data);
                $("#output").html(data); 
           }
        });
	    e.preventDefault();
	});
/**
 * "Competion" page
 */
// $("#find_username").keyup(function(event){
//     if(event.keyCode == 13){
//     	var username = $(this).val();
//     	$("#fight_username").val(username);
//         $("#fight").modal('show');
//     }
// });
	$("#validate_user").submit(function(e) {
		$("#fight_username").val($("#name_user").val());

	    $.ajax({
	           type: "POST",
	           url: shj.site_url + "competition/validate_user",
	           data: $("#validate_user").serialize(), 
	           success: function(data)
	           {
	           	console.log(data);
	               if(data == "no"){
	               		$("#message").html("<span class='glyphicon glyphicon-exclamation-sign'></span>  There is no such user");
	               		$("#message_competiton").modal('show');
	               	}
	               	else if(data=='off'){
	               		$("#message").html("<span class='glyphicon glyphicon-exclamation-sign'></span>  This user is off");
	               		$("#message_competiton").modal('show');
	               	}
	               	else if(data=='onl'){
	               		$("#fight").modal('show');
	               	}
	               	else if(data=='you'){
	               		$("#message").html("<span class='glyphicon glyphicon-exclamation-sign'></span>  You are crazy");
	               		$("#message_competiton").modal('show');
	               	}
	               	else if(data=='exist'){
	               		$("#message").html("<span class='glyphicon glyphicon-exclamation-sign'></span>  You are/He is fighting");
	               		$("#message_competiton").modal('show');
	               	}
	           }
	         });

	    e.preventDefault(); 
	});

	$("#insert_database").submit(function(e) {

	    $.ajax({
	           type: "POST",
	           url: shj.site_url + "competition/insert_fight_database",
	           data: $("#insert_database").serialize(), 
	           success: function(data)
	           {
	           		$("#fight").modal("toggle");
	               	$("#message").attr("class","alert alert-info");
               		$("#message").html("<span class='glyphicon glyphicon-ok-sign'></span>  Challenges success");
               		$("#message_competiton").modal('show');
	           		check_time_out();
	           }
	         }); 
	    e.preventDefault();
	});

	$("#insert_answer_yes").submit(function(e) {
		$.ajax({
			type: 'POST',
			url: shj.site_url + 'competition/insert_answer_yes',
			data: $("#insert_answer_yes").serialize(), 
			success: function (data) {
				window.location = data+"?check=1";
			}
			});
		e.preventDefault();
	});

});