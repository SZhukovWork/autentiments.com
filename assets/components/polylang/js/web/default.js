;(function (window, document, $, polylangConfig) {
    var Polylang = function (options) {
        this.options = {
            actionUrl: '/assets/components/polylang/action.php',
            trigger: 'polylang-toggle',
        };
        this.options = $.extend(this.options, options || {});
        this.setup();
    };
    Polylang.prototype.setup = function () {
        this.$doc = $(document);
        this.bindEvents();
    };
    Polylang.prototype.bindEvents = function () {
        var self = this;
        this.$doc.on('click', '.' + this.options.trigger, function (e) {
            e.preventDefault();
            self.toggleLanguage($(this).attr('href'));
        });
    };
    Polylang.prototype.toggleLanguage = function (url) {
        if (url == undefined) return;
        $.ajax({
            dataType: 'json',
            type: 'POST',
            cache: false,
            url: this.options.actionUrl,
            data: {
                action: 'language/toggle',
            },
            complete: function (e) {
                window.location.href = url;
            },
            error: function (e) {
                console.error('error toggle language', e);
            }
        });
    };
    $(document).ready(function () {
        var polylang = new Polylang(polylangConfig || {});
    });
})(window, document, jQuery, polylangConfig);