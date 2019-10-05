$(window).on('load', function () {
    $(function () {

        var removeLoader = function () {

            //vars
            var $preloader = $("#page-preloader");
            var $loader = $preloader.find(".dot-loader");

            //effects
            $loader.delay(250).fadeOut("250");
            $preloader.delay(250).fadeOut("250");

            $("#backgroundVideo, .mainSlider, .mainSliderDots").addClass("loaded");

        };

        setTimeout(removeLoader, 150);

    });
});

$('[data-fancybox]').fancybox({
    protect: true,
    buttons: [
        'zoom',
        'thumbs',
        'close'
    ]
});

    $('.contact-form').submit(function(e) {
        e.preventDefault();

        var form = $(this);

        var formData = new FormData();

        formData.append('name', form.find('input[name="name"]').val())
        formData.append('email', form.find('input[name="email"]').val())
        formData.append('phone', form.find('input[name="phone"]').val())
        formData.append('message', form.find('textarea[name="message"]').val())
        formData.append('file', document.querySelector('input[name="file"]').files[0])

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "/optimo/templates/optimo/php/mail/mailer.php", true);
        xhr.onreadystatechange = function() {
            if (this.readyState == 4) {
                if (this.status == 200) {

                    var resp = this.responseText;
		
                    if (resp.trim() == "true") {
                        alert('Сообщение отправлено успешно!')
                    } else {
                        alert('Ошибка, попробуйте еще или позвоните нам!')
                    }
                }
            }
        };

        xhr.send(formData);
    })


$('[data-fancybox="watermark"]').fancybox({
    protect: true,
    slideClass: 'watermark',
    toolbar: false,
    smallBtn: true
});

// Preload watermark image
// Please, use your own image
(new Image()).src = "https://fancyapps.com/GJbkSPU.png";