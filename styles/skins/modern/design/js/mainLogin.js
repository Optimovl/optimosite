$(function() {

	$('#login_field').focus();

	$('select').selectize({
		allowEmptyOption: true,
		create: false,
		hideSelected: true
	});

	$('.checkbox input:checked').parent().addClass('checked');

	$('.checkbox').click(function() {
		$(this).toggleClass('checked');
	});

	$('input:radio').click(function() {
		$('#forget').find('input:text').attr('name', $(this).attr('id'));
	});

	var $container = $('.container');
	$container.hide();

	$('#forgetLabel').click(function() {
		if ($container.is(':visible')) {
			$container.hide('slide');
		} else {
			$container.show('slide');
		}
	});

	/** Обработчик нажатия на кнопку "Выслать пароль" */
	$('#submit_reset_field').click(function(e) {
		e.preventDefault();
		var $forgetForm = $('#forget');

		$.ajax({
			url: $forgetForm.attr('action'),
			type: 'POST',
			data: $forgetForm.serialize(),
			success: function(response) {
				var $error = $(response).find('.error');
				var $forgetFormAnswer = $('#resetFormAnswer');
				var $resetContainer = $('.reset_container:eq(0)');
				$resetContainer.hide();

				if ($error.length > 0) {
					$forgetFormAnswer.html($error.html());
					$forgetFormAnswer.css('color', 'red');
				} else {
					$forgetFormAnswer.html(getLabel('js-restore-password-success'));
					$forgetFormAnswer.css('color', 'green');
				}

				$resetContainer.show();
			}
		})
	});

	initBubbles();

	function initBubbles() {
		var d = document.querySelector('.bubbles'),
				e = document.querySelector('.bubbles-front'),
				f = function(a) {
					var b = document.body.offsetWidth,
							c = document.body.offsetHeight,
							f = 0.04,
							g = 0.04,
							h = (b / 2 - a.clientX) * f,
							i = (c / 2 - a.clientY) * g;
					d.style.marginLeft = h + 'px',
							d.style.marginTop = i + 'px',
							e.style.marginLeft = 0.2 * h + 'px',
							e.style.marginTop = 0.2 * i + 'px'
				},
				g = !0;

		document.onmousemove = function(a) {
			g && (d.className = 'bubbles visible', e.className = 'bubbles-front visible', g = !1),
					f(a)
		}
	}

});
