/*
	Wecode judge
	author: truongan
	date: 20160330
*/

$(document).ready(function(){
	$("form").submit(function(event){	
		$("#start_time").val($("#start_date").val() + " " + $("#start__time").val());
		$("#finish_time").val($("#finish_date").val() + " " + $("#finish__time").val());
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
			var id = $(item).find('input[name="problem_id[]"]').val();
			console.log(id);
			console.log($('option[data-id="' + id + '"]'));
			$('option[data-id="' + id + '"]').prop("selected", false);
			
			$('.all_problems').trigger('change');
			$('.all_problems').select2('close');

			if (Sortable.utils.is(ctrl, ".list_remover")) {  // Click on remove button
				item.parentNode.removeChild(item); // remove sortable item
			}
		}
	});

	$('.all_problems').select2({
		placeholder : "Select problem to add to this assignment"
	});
	$('.all_problems').on('select2:select', function (e){

		console.log(selected_data = $(e.params.data.element).data());
		var dummy_row = $('.list-group-item.d-none');
		var new_row = dummy_row.clone()
		new_row.removeClass('d-none');
		
		new_row.find('input[name="problem_id[]"]').val(selected_data.id);
		new_row.find('input[name="problem_name[]"]').val(selected_data.name);

		new_row.find('.lead').html(selected_data.name 
				+ '<span class="badge badge-light">'
				+ selected_data.id
				+'</span>'
			);
		new_row.find('.admin_note').html(selected_data.note);


		new_row.insertAfter(dummy_row).slideDown();

	})
	$('.all_problems').on('select2:unselecting', function (e){
		e.preventDefault();
	})
});
