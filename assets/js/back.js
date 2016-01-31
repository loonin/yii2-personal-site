var _$_ = _$_ || {};

_$_ = {
	items: {},
	preLoader: {},
	deleteImage: function () {
		$('.close').on('click', function () {
			var parent = $(this).parent('.have-siblings');
			var id = parent.data('id');

			_$_.preLoader.prependTo(parent);

			$.ajax({
				url:      '/back/delete-image/',
				type:     'POST',
				dataType: 'json',
				data:     { 'id': id },
				success: function (response) {
					if (response.error) {
						alert(response.error);
						return;
					}

					_$_.preLoader.remove();
					parent.fadeOut();
				},
				error: function (error) {
					alert('Ошибка ' + error.status);
					console.log(error.responseText);
				}
			});
		});
	},
	initPreLoader: function () {
		$.get('/assets/js/templates/wave-preloader.html', function (data) {
			_$_.preLoader = $(data).css({
				'margin-top' : '35px',
				'margin-bottom' : '35px'
			});
		});
	},
	initSortable: function () {
		$sortable = $("#sortable");

		_$_.items = $sortable.clone();

		$sortable
			.sortable({
				placeholder: "ui-state-highlight",
				update: function( event, ui ) {
					$( "#sortable" ).children().each(function (i) {
						$( this ).data("order", i+1);
					});
				}
			})
			.disableSelection()
			.children('tr')
			.each(function (i) {
				$(this).data("order", i+1);
			});

		$("#items-update").on("click", function () {
			var data = {};

			$("#sortable").children('tr').each(function (i) {
				var $this = $(this);
				data[$this.data("id")] = $this.data("order");
			});

			_$_.preLoader.css({
				'margin-top' : '3px',
				'margin-bottom' : '3px',
				'height' : '14px'
			});

			console.log(_$_.preLoader);

			$this = $(this);

			$this.css('color', '#5cb85c').prepend(_$_.preLoader);

			$.ajax({
				type: 'POST',
				url: '/back/update',
				data: { items: data },
				dataType: 'json',
				success:  function(response) {
					_$_.preLoader.remove();
					$this.css('color', '#ffffff');
					if ($('.alert-success').is(':visible')){
						return;
					}

					_$_.items = $("#sortable").clone();

					var message = $('<div class="alert alert-success">').html(response.success).css('display', 'none');
					$('h1').after(message);
					message.fadeIn().delay(4000).fadeOut(2000);
				},
				error: function(error) {
					if (error.status == 0) {
						return;
					}

					alert('Ошибка ' + error.status);
					console.log(error.responseText);
				}
			});
		});

		$("#items-cancel").on("click", function () {
			$("#sortable").remove();
			$(".t-main").append(_$_.items);
			_$_.initSortable();
		});
	}
};

$(function () {
	_$_.initPreLoader();
	_$_.deleteImage();
	_$_.initSortable();
});
