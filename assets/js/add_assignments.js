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
	var a = Sortable.create(problem_list, {
		handle : '.list_handle',
		ghostClass: 'list-group-item-secondary',
		chosenClass : 'list-group-item-primary',
		animation: 150,
		filter: '.list_remover',
		onFilter: function (evt) {
			var item = evt.item,
				ctrl = evt.target;
	
			if (Sortable.utils.is(ctrl, ".list_remover")) {  // Click on remove button
				item.parentNode.removeChild(item); // remove sortable item
			}
		}
	});
});
