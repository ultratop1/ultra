$(function () {
	'use strict';

	$('[type="tel"]').inputmask('+38 999 999-99-99');

	if ($(this).scrollTop() > 250) $('#scrollup').fadeIn(300);

	$(window).scroll(function () {
		if ($(this).scrollTop() > 250) $('#scrollup').fadeIn(300);
		else $('#scrollup').fadeOut(300);
	});

	$('#scrollup').click(function () {
		$("html, body").animate({ scrollTop: 0 }, 1000);
		return false;
	});

	$(document).on('click', 'a.scroll-link', function (event) {
		event.preventDefault();
		event.stopPropagation();
		let target = $(this).attr('href');
		$('a.scroll-link').removeClass('active');
		$('html, body').animate({ scrollTop: $(target).offset().top - 100 }, 1000);
		$(this).addClass('active');
		return false;
	});

	$('.problems-main').css('background-image', 'url('+$('#ProblemAccordion .collapse.show').data('background')+')');
	$('#ProblemAccordion .collapse').on('show.bs.collapse', function () {
		$('.problems-main').css('background-image', 'url('+$(this).data('background')+')');
	})

	$('.owl-reviews').owlCarousel({
		loop: false,
		margin: 0,
		nav: true,
		navText: ['<img src="/theme/prev.png" alt="" />', '<img src="/theme/next.png" alt="" />'],
		dots: false,
		items: 1
	});

	$('.owl-serts').owlCarousel({
		loop: false,
		margin: 0,
		nav: true,
		navText: ['<img src="/theme/prev.png" alt="" />', '<img src="/theme/next.png" alt="" />'],
		dots: false,
		items: 1,
		responsive:{
			0:{
				items:1
			},
			768:{
				items:2
			},
			960:{
				items:3
			},
			1200:{
				items:4
			}
		}
	});

	let date = new Date();
	let now = date.getTime() / 1000;
	date.setHours(24, 0, 0, 0);
	let tommorow = date.getTime() / 1000;
	var clock = $('.flipclock').FlipClock(tommorow - now, {
		countdown: true,
		language: 'russian'
	});


	let price = parseInt($('[name=price]').val());
	let promocount = 0;
	function updateTotal() {
		let quantity = parseInt($('[name=quantity]').val());
		$('.total-value').text((price*quantity)-(promocount*quantity));
	}

	$(document).on('change', '[name=quantity]', function(event) {
		updateTotal();
	});

	$(document).on('change', '[name=promo]', function(event) {
		event.preventDefault();
		$('.help').remove();
		let promo = $(this).val();
		promocount = 0;
		updateTotal();
		if (promo != '') {
			$.ajax({
				url: '/mail/getpromo.php',
				type: 'post',
				dataType: 'json',
				data: {promo: promo},
			})
			.done(function(data) {
				// console.log(data);
				if (data.error) {
					$('[name=promo]').after('<div class="help text-danger pl-3"><small>'+data.message+'</small></div>');
				}else{
					$('[name=promo]').after('<div class="help text-success pl-3"><small>'+data.message+'</small></div>');
					promocount = parseInt(data.count);
				}
				updateTotal();
			})
			.fail(function(data) {
				console.log(data);
			})
		}
	});

	$(document).on('submit', 'form', function (event) {
		event.preventDefault();
		event.stopPropagation();
		let form = $(this);
		form.addClass('was-validated');
		form.find('[type="tel"]').removeClass('is-invalid');
		let btn = form.find('button[type=submit]');
		if (form[0].checkValidity() !== false) {
			btn.hide();
			if(!form.find('[type="tel"]').inputmask("isComplete")){
				form.find('[type="tel"]').addClass('is-invalid');
				btn.show();
				return false;
			}
			$.ajax({
				type: "POST",
				url: "/mail/sendmail.php",
				data: form.serialize(),
				success: function(data) {
					console.log(data);
					btn.remove();
					form[0].reset();
					form.removeClass('was-validated');
					swal({
						title: 'Ваша заявка принята.',
						text: 'Вскоре Вам перезвонит наш консультант. Заявки, поступившие в нерабочее время и в выходные, будут обработаны на следующий рабочий день.',
						type: 'success',
						timer: 5000
					});
					let formId = form.attr('id');
					console.log("'event', formId, {'event_category': 'FormID"+formId+"', 'event_action': 'Submit'}");
					gtag('event', formId, {'event_category': 'FormID'+formId, 'event_action': 'Submit'});

				},
				error: function(data) {
					swal({
						text: 'Ошибка отправки!',
						type: 'error',
						timer: 5000
					});
				}
			});
		}
	});

});
