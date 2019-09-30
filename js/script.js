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


$('[data-fancybox="watermark"]').fancybox({
    protect: true,
    slideClass: 'watermark',
    toolbar: false,
    smallBtn: true
});

// Preload watermark image
// Please, use your own image
(new Image()).src = "http://fancyapps.com/GJbkSPU.png";