var _$_ = _$_ || {};

_$_ = {
	details:          {},
	detailsOpenSpeed: 700,
	detailsOpenedId:  0,
	detailsTemplate: {},
	preLoader: {},
	initDetailsTemplate: function () {
		$.get('/assets/js/templates/details.html', function (data) {
			_$_.detailsTemplate = data;
		});
	},
	initPreLoader: function () {
		$.get('/assets/js/templates/wave-preloader.html', function (data) {
			_$_.preLoader = $(data)
		});
	},
	getDetails: function (id, data) {
		var compiled = Handlebars.compile(_$_.detailsTemplate);
		var html = compiled(data);

		_$_.details[id] = $(html);
	},
	showDetails: function () {
		$('.b-card').on('click', function () {
			var $this = $(this);
			var id = $this.data('id');
			var position = 3 - Number($this.data('order'));
			var afterBlock = $this;
			var next = {};

			for (var i = 0; i < position; i++) {
				next = afterBlock.next('.b-card');

				if (next.length > 0) {
					afterBlock = next;
				} else {
					break;
				}
			}

			if ($('.b-details').is(':visible')) {
				$('.b-details').slideUp(_$_.detailsOpenSpeed, function () {
					$(this).remove();

					if (id != _$_.detailsOpenedId) {
						_$_.prepareDetailsBlock(id, afterBlock, $this);
					}
				});
			} else {
				_$_.prepareDetailsBlock(id, afterBlock, $this);
			}
		});
	},
	prepareDetailsBlock: function (id, afterBlock, $this) {
		if (_.has(_$_.details, id)) {
			_$_.openDetailsBlock(id, afterBlock, _$_.details[id]);
		} else {
			_$_.preLoader.prependTo($this);

			$.ajax({
				url:      '/site/get-item/',
				type:     'POST',
				dataType: 'json',
				data:     { 'id': id },
				success: function (response) {
					if (response.error) {
						alert(response.error);
						return;
					}

					_$_.preLoader.remove();
					_$_.getDetails(id, response);
					_$_.openDetailsBlock(id, afterBlock, _$_.details[id]);
				},
				error: function (error) {
					alert('Ошибка ' + error.status);
					console.log(error.responseText);
				}
			});
		}
	},
	openDetailsBlock: function (id, afterBlock, details) {
		afterBlock.after(details);
		details.delay(10).slideDown(_$_.detailsOpenSpeed)
			.find('.button')
			.on('click', function () {
				details.slideUp(_$_.detailsOpenSpeed);
			})
			.end()
			.find('.img, .more-images')
			.jScrollPane(
			{
				autoReinitialise: true,
				verticalDragMinHeight: 18,
				verticalDragMaxHeight: 18
			}
		);

		_$_.detailsOpenedId = id;
	},
	feedbackFormInit: function () {
		$('input.element').on('click',function () {
			$(this).attr('value', '');
			if ($(this).val() == '' || $(this).val() == $(this).data('value')) {
				$(this).val('');
			}
			;
		}).bind('blur', function () {
				if ($(this).val() == '') {
					$(this).val($(this).data('value'));
				}
			});
		$('textarea.element').on('click',function () {
			if ($(this).val() == '' || $(this).val() == $(this).data('value')) {
				$(this).val('');
			}
			;
		}).bind('blur', function () {
				if ($(this).val() == '') {
					$(this).val($(this).data('value'));
				}
			});

		// send to the mail
		$('[name=submit]').on('click', function (e) {
			e.preventDefault();

			$('p.error').remove();

			$('.element').each(function () {
				if ($(this).val() == '' || $(this).val() == $(this).data('value')) {
					$(this).data('send', '');
				} else {
					$(this).data('send', $(this).val());
				}
			});

			// нельзя использовать $('form').serialize(), т.к. при необходимом, но незаполненном поле отправится текст по-умолчанию
			$.ajax({
				url: '/site/send-feedback',
				type: 'POST',
				dataType: 'json',
				data: {
					submit:  "send",
					name:    $('input[name=name]').data('send'),
					email:   $('input[name=email]').data('send'),
					phone:   $('input[name=phone]').data('send'),
					message: $('textarea[name=message]').data('send')
				},
				success: function (response) {
					if (response.errors) {
						var currentErrors = $('.errors');

						if (currentErrors.is(':visible')) {
							currentErrors.slideUp();
						}

						var errors = $('<div class="errors">');

						$.each(response.errors, function (i, val) {
							errors.append($('<p>').html(val));
						})

						errors.hide().appendTo('form').slideDown();
					}
					if (response.success) {
						var formBlock = $('form');
						var formParent = formBlock.parent();
						_$_.hideForm();
						$('<div class="success">')
							.hide()
							.html(response.success)
							.appendTo(formParent)
							.delay(1000)
							.slideDown(1000);
					}
				},
				error: function (error) {
					alert('Ошибка ' + error.status);
					console.log(error.responseText);
				}
			});
		});
	},
	feedbackIsOpened: false,
	feedbackOpen: function () {
		$('.b-feedback').animate({
			top: "+=365"
		}, 200);

		$('.b-under-feedback').fadeIn(200);

		_$_.feedbackIsOpened = true;
	},
	feedbackClose: function () {
		$('.b-feedback')
			.animate({top: "+=10"}, 100)
			.animate({top: "-=375"}, 200, 'swing', function () {
				_$_.showForm();
			});

		$('.b-under-feedback').fadeOut(200);

		_$_.feedbackIsOpened = false;
	},
	feedbackClick: function () {
		$('.feedback-button').on('click', function () {
			if (_$_.feedbackIsOpened) {
				_$_.feedbackClose();

			} else {
				_$_.feedbackOpen();

				$(document).bind('click.myEvent', function (e) {
					if (_$_.feedbackIsOpened && $(e.target).closest('.b-feedback').length == 0) {
						_$_.feedbackClose();
						$(document).unbind('click.myEvent');
					}
				});
			}
		});
	},
	showForm: function () {
		var form = $('form');
		var message = $('.success');
		var errors = form.find('.errors');

		if (!form.is(':visible')) {
			form.slideDown(1000);
		}

		if (message.is(':visible')) {
			message.slideUp(1000);
		}

		if (errors.is(':visible')) {
			errors.hide();
		}
	},
	hideForm: function () {
		var form = $('form');

		if (form.is(':visible')) {
			form.slideUp(1000);
		}
	}
};

$(function () {
	Handlebars.registerHelper('list', function (items, options) {
		var out = '';

		for (var i = 0, l = items.length; i < l; i++) {
			out = out + options.fn(items[i]);
		}

		return out;
	});

	_$_.initDetailsTemplate();
	_$_.initPreLoader();
	_$_.showDetails();
	_$_.feedbackFormInit();
	_$_.feedbackClick();
});
