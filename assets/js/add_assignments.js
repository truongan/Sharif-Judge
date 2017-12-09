
shj.row ='<tr><td>SPID</td>';
shj.row +=			'<td><input type="text" name="name[]" class="form-control medium_text" value="Problem SPID "/></td>';
shj.row +=			'<td><input type="number" name="score[]" class="form-control short_text" value="100" /></td>';
shj.row +=			'<td><input type="number" name="c_time_limit[]" class="form-control short_text"  value="500" /></td>';
shj.row +=			'<td><input type="number" name="python_time_limit[]" class="form-control short_text"  value="1500"/></td>';
shj.row +=			'<td><input type="number" name="java_time_limit[]" class="form-control short_text"  value="2000"/></td>';
shj.row +=			'<td><input type="number" name="memory_limit[]" class="form-control short_text"  value="50000"/></td>';
shj.row +=			'<input id="allowed_langPID" type="hidden" name="allowed_languages[PID]" class="form-control" />';
shj.row +=			'<input id="submit_langPID" type="hidden" name="languages[PID]" class="form-control"/>';
shj.row +=			'<td>';
shj.row +=				'<select id="langPID" name="select_languages[PID][]"  multiple>';
shj.row +=					'<option value="C">C</option>';
shj.row +=					'<option value="C++" selected>C++</option>';
shj.row +=					'<option value="Python 2">Python 2</option>';
shj.row +=					'<option value="Python 3">Python 3</option>';
shj.row +=					'<option value="Java">Java</option>';
shj.row +=				'</select>';
shj.row +=			'</td>';
shj.row +=			'<td><input type="text" name="diff_cmd[]" class="form-control short_text" value="diff" /></td>';
shj.row +=			'<td><input type="text" name="diff_arg[]" class="form-control short_text" value="-bB"/></td>';
shj.row +=			'<td><input type="checkbox" name="is_upload_only[]" class="check" value="" }}/></td>';
shj.row +=			'<td><span class="btn btn-danger delete_problem"><i class="fa fa-times-circle fa-lg fa-fw pointer"></i></span></td>';
shj.row +=		'</tr>';
	$(document).ready(function(){
		$("#add").click(function(){
			$('#problems_table>tbody').append(shj.row
							.replace(/SPID/g, (shj.num_of_problems+1))
							.replace(/PID/g, (shj.num_of_problems))

				);
			$("select").select2();
			shj.num_of_problems++;

			$('#nop').attr('value', shj.num_of_problems);
		});
		$(document).on('click', '.delete_problem', function(){
			if (shj.num_of_problems==1) return;
			var row = $(this).parents('tr');
			row.remove();
			var i = 0;
			$('#problems_table>tbody').children('tr').each(function(){
				i++;
				$(this).children(':first').html(i);
				$(this).find('[type="checkbox"]').attr('value',i);
			});
			shj.num_of_problems--;
			$('#nop').attr('value',shj.num_of_problems);
		});
	});


/*
	Wecode judge
	author: truongan
	date: 20160330
*/

$(document).ready(function(){
	var nop = $("[name='number_of_problems']").val();

	for (var i = 0; i < nop; i++) {
		var allow_langs = $("#allowed_lang" + i).val().split(",")

		$("#lang" + i).val(allow_langs);
	}

	$("form").submit(function(event){
		var nop = $("[name='number_of_problems']").val();
		$("#start_time").val($("#start_date").val() + " " + $("#start__time").val());
		$("#finish_time").val($("#finish_date").val() + " " + $("#finish__time").val());
		for (var i = 0; i < nop; i++) {
			$("#submit_lang" + i).val($("#lang" + i).val().join());
			$("#lang" + i).val(allow_langs);
		}
	});
	$("select").select2();
});
